<?php

namespace AhgSecurityClearance\Services;

use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CeremonyStep\CeremonyStepManager;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\CredentialRecord;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * WebAuthnService — FIDO2 / WebAuthn / passkey MFA backend (#126 / #721).
 *
 * PSIS-parity port of the Heratio AhgSecurityClearance\Services\WebAuthnService.
 * Wraps web-auth/webauthn-lib ^5.3 (installed in atom-framework/vendor) against
 * the ahg_webauthn_credential table. Symfony adaptations: Capsule DB, sfUser
 * session attributes for the pending challenge, error_log, date().
 *
 * @package ahgSecurityClearancePlugin
 */
class WebAuthnService
{
    private const SESSION_NS = 'webauthn';
    private const SESSION_REGISTER_OPTIONS = 'webauthn_register_options';
    private const SESSION_ASSERT_OPTIONS = 'webauthn_assert_options';

    private SerializerInterface $serializer;
    private AttestationStatementSupportManager $attestationManager;
    private CeremonyStepManager $creationCeremony;
    private CeremonyStepManager $requestCeremony;

    public function __construct()
    {
        // Ensure the framework vendor autoload (Webauthn\, Cose\, Symfony\) is present.
        if (!class_exists('Webauthn\\PublicKeyCredentialCreationOptions')) {
            $autoload = \sfConfig::get('sf_root_dir', '/usr/share/nginx/archive') . '/atom-framework/vendor/autoload.php';
            if (is_file($autoload)) {
                require_once $autoload;
            }
        }
        $this->attestationManager = AttestationStatementSupportManager::create();
        $this->attestationManager->add(NoneAttestationStatementSupport::create());
        $this->serializer = (new WebauthnSerializerFactory($this->attestationManager))->create();

        // web-auth/webauthn-lib 5.x verifies via CeremonyStepManagers built once.
        // Default origin check (CheckOrigin) validates the clientData origin host
        // against the RP id, which is correct for same-origin passkeys.
        $csmFactory = new CeremonyStepManagerFactory();
        $csmFactory->setAttestationStatementSupportManager($this->attestationManager);
        $this->creationCeremony = $csmFactory->creationCeremony();
        $this->requestCeremony = $csmFactory->requestCeremony();
    }

    // ─── session (Symfony 1.x sfUser attribute holder) ────────────────────

    private function sessionPut(string $key, string $val): void
    {
        \sfContext::getInstance()->getUser()->setAttribute($key, $val, self::SESSION_NS);
    }

    private function sessionPull(string $key): ?string
    {
        $user = \sfContext::getInstance()->getUser();
        $val = $user->getAttribute($key, null, self::SESSION_NS);
        $user->getAttributeHolder()->remove($key, null, self::SESSION_NS);

        return $val !== null ? (string) $val : null;
    }

    // ─── public API ───────────────────────────────────────────────────────

    /** @return array<string,mixed> options for navigator.credentials.create() */
    public function beginRegistration(int $userId, string $username, string $displayName, string $rpId, string $rpName): array
    {
        $userEntity = PublicKeyCredentialUserEntity::create($username, (string) $userId, $displayName);
        $rpEntity = PublicKeyCredentialRpEntity::create($rpName, $rpId);

        $params = [
            PublicKeyCredentialParameters::create('public-key', -7),    // ES256
            PublicKeyCredentialParameters::create('public-key', -257),  // RS256
            PublicKeyCredentialParameters::create('public-key', -8),    // EdDSA
        ];

        $excludeCredentials = array_map(
            static fn (PublicKeyCredentialSource $src) => $src->getPublicKeyCredentialDescriptor(),
            $this->findAllForUserHandle((string) $userId),
        );

        // web-auth/webauthn-lib 5.x: create() takes (authenticatorAttachment,
        // userVerification, residentKey) — no $attachment / $requireResidentKey.
        $authenticatorSelection = AuthenticatorSelectionCriteria::create(
            userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED,
            residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_PREFERRED,
        );

        $options = PublicKeyCredentialCreationOptions::create(
            $rpEntity,
            $userEntity,
            random_bytes(32),
            $params,
            authenticatorSelection: $authenticatorSelection,
            attestation: PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            excludeCredentials: $excludeCredentials,
            timeout: 60000,
        );

        $json = $this->serializer->serialize($options, 'json', ['json_encode_options' => JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES]);
        $this->sessionPut(self::SESSION_REGISTER_OPTIONS, $json);

        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    /** @param array<string,mixed> $browserResponse PublicKeyCredential JSON */
    public function completeRegistration(int $userId, array $browserResponse, string $label, string $rpId): bool
    {
        $optionsJson = $this->sessionPull(self::SESSION_REGISTER_OPTIONS);
        if (!$optionsJson) {
            return false;
        }
        /** @var PublicKeyCredentialCreationOptions $options */
        $options = $this->serializer->deserialize($optionsJson, PublicKeyCredentialCreationOptions::class, 'json');

        /** @var PublicKeyCredential $pkc */
        $pkc = $this->serializer->deserialize(
            json_encode($browserResponse, JSON_THROW_ON_ERROR), PublicKeyCredential::class, 'json',
        );
        $response = $pkc->response;
        if (!$response instanceof AuthenticatorAttestationResponse) {
            return false;
        }

        $validator = AuthenticatorAttestationResponseValidator::create($this->creationCeremony);
        try {
            $record = $validator->check($response, $options, $rpId);
        } catch (\Throwable $e) {
            error_log('webauthn.register.invalid user=' . $userId . ': ' . $e->getMessage());

            return false;
        }
        $this->persistSource(PublicKeyCredentialSource::fromCredentialRecord($record), $userId, $label);

        return true;
    }

    /** @return array<string,mixed> options for navigator.credentials.get() */
    public function beginAssertion(int $userId, string $rpId): array
    {
        $allowCredentials = array_map(
            static fn (PublicKeyCredentialSource $src) => $src->getPublicKeyCredentialDescriptor(),
            $this->findAllForUserHandle((string) $userId),
        );

        $options = PublicKeyCredentialRequestOptions::create(
            random_bytes(32),
            rpId: $rpId,
            allowCredentials: $allowCredentials,
            userVerification: PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED,
            timeout: 60000,
        );

        $json = $this->serializer->serialize($options, 'json', ['json_encode_options' => JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES]);
        $this->sessionPut(self::SESSION_ASSERT_OPTIONS, $json);

        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    /** @param array<string,mixed> $browserResponse */
    public function completeAssertion(int $userId, array $browserResponse, string $rpId): bool
    {
        $optionsJson = $this->sessionPull(self::SESSION_ASSERT_OPTIONS);
        if (!$optionsJson) {
            return false;
        }
        /** @var PublicKeyCredentialRequestOptions $options */
        $options = $this->serializer->deserialize($optionsJson, PublicKeyCredentialRequestOptions::class, 'json');

        /** @var PublicKeyCredential $pkc */
        $pkc = $this->serializer->deserialize(
            json_encode($browserResponse, JSON_THROW_ON_ERROR), PublicKeyCredential::class, 'json',
        );
        $response = $pkc->response;
        if (!$response instanceof AuthenticatorAssertionResponse) {
            return false;
        }

        // v5 validators take the *stored* credential record (not the raw id).
        $stored = $this->findOneByCredentialId($pkc->rawId);
        if ($stored === null) {
            return false;
        }

        $validator = AuthenticatorAssertionResponseValidator::create($this->requestCeremony);
        try {
            $updated = $validator->check($stored, $response, $options, $rpId, (string) $userId);
        } catch (\Throwable $e) {
            error_log('webauthn.assert.invalid user=' . $userId . ': ' . $e->getMessage());

            return false;
        }
        $updatedSource = PublicKeyCredentialSource::fromCredentialRecord($updated);
        $this->saveCredentialSource($updatedSource);
        DB::table('ahg_webauthn_credential')
            ->whereRaw('credential_id = UNHEX(?)', [bin2hex($updatedSource->publicKeyCredentialId)])
            ->update(['last_used_at' => date('Y-m-d H:i:s')]);

        return true;
    }

    public function userHasCredential(int $userId): bool
    {
        return DB::table('ahg_webauthn_credential')->where('user_id', $userId)->exists();
    }

    /** @return array<int,object> */
    public function listForUser(int $userId): array
    {
        return DB::table('ahg_webauthn_credential')->where('user_id', $userId)->orderByDesc('created_at')
            ->get(['id', 'label', 'aaguid', 'sign_count', 'transports', 'last_used_at', 'created_at'])->all();
    }

    public function deleteCredential(int $userId, int $credentialRowId): bool
    {
        return DB::table('ahg_webauthn_credential')->where('id', $credentialRowId)->where('user_id', $userId)->delete() > 0;
    }

    public function disable(int $userId): void
    {
        DB::table('ahg_webauthn_credential')->where('user_id', $userId)->delete();
    }

    // ─── credential-source repository (used by the lib v5 validators) ──────

    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        // Compare via UNHEX: binding a raw-binary param over a utf8mb4 connection
        // mangles non-UTF8 bytes so `credential_id = ?` never matches a VARBINARY.
        $row = DB::table('ahg_webauthn_credential')
            ->whereRaw('credential_id = UNHEX(?)', [bin2hex($publicKeyCredentialId)])
            ->first();

        return $row ? $this->hydrateSource($row->public_key) : null;
    }

    /** @return array<int,PublicKeyCredentialSource> */
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $userEntity): array
    {
        return $this->findAllForUserHandle($userEntity->getId());
    }

    public function saveCredentialSource(PublicKeyCredentialSource $source): void
    {
        $serialized = $this->serializer->serialize($source, 'json', ['json_encode_options' => JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES]);
        DB::table('ahg_webauthn_credential')
            ->whereRaw('credential_id = UNHEX(?)', [bin2hex($source->publicKeyCredentialId)])
            ->update(['public_key' => $serialized, 'sign_count' => $source->counter]);
    }

    // ─── internals ────────────────────────────────────────────────────────

    /** @return array<int,PublicKeyCredentialSource> */
    private function findAllForUserHandle(string $userHandle): array
    {
        $sources = [];
        foreach (DB::table('ahg_webauthn_credential')->where('user_id', (int) $userHandle)->get(['public_key']) as $row) {
            $source = $this->hydrateSource($row->public_key);
            if ($source) {
                $sources[] = $source;
            }
        }

        return $sources;
    }

    private function hydrateSource(string $serialized): ?PublicKeyCredentialSource
    {
        try {
            // v5 serializes credentials as CredentialRecord; deserialize to that
            // canonical type then wrap (PublicKeyCredentialSource is deprecated).
            $record = $this->serializer->deserialize($serialized, CredentialRecord::class, 'json');

            return PublicKeyCredentialSource::fromCredentialRecord($record);
        } catch (\Throwable $e) {
            error_log('webauthn.hydrate.failed: ' . $e->getMessage());

            return null;
        }
    }

    private function persistSource(PublicKeyCredentialSource $source, int $userId, string $label): void
    {
        $serialized = $this->serializer->serialize($source, 'json', ['json_encode_options' => JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES]);
        DB::table('ahg_webauthn_credential')->insert([
            'user_id' => $userId,
            'credential_id' => $source->publicKeyCredentialId,
            'public_key' => $serialized,
            'attestation_type' => $source->attestationType,
            'aaguid' => $source->aaguid->__toString(),
            'sign_count' => $source->counter,
            'transports' => json_encode($source->transports, JSON_UNESCAPED_SLASHES),
            'label' => $label !== '' ? $label : 'Passkey',
            'last_used_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
