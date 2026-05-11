<?php

namespace AtomExtensions\SharePoint\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * SharePointTenantRepository — CRUD + secret resolution for sharepoint_tenant rows.
 *
 * Secrets are NEVER stored in this table. client_secret_ref points to an
 * encrypted blob in ahg_settings; resolveSecret() decrypts via framework
 * EncryptionService.
 *
 * @phase 1
 */
class SharePointTenantRepository
{
    public function find(int $id): ?\stdClass
    {
        return DB::table('sharepoint_tenant')->where('id', $id)->first();
    }

    /** @return array<int, \stdClass> */
    public function all(): array
    {
        return DB::table('sharepoint_tenant')->orderBy('name')->get()->all();
    }

    public function create(array $attributes): int
    {
        // TODO: validate required keys; generate webhook_client_state if absent.
        return (int) DB::table('sharepoint_tenant')->insertGetId($attributes);
    }

    public function update(int $id, array $attributes): void
    {
        DB::table('sharepoint_tenant')->where('id', $id)->update($attributes);
    }

    public function delete(int $id): void
    {
        DB::table('sharepoint_tenant')->where('id', $id)->delete();
    }

    /**
     * Resolve the decrypted client_secret for a tenant via EncryptionService.
     * Throws if the secret_ref points to a missing or undecryptable blob.
     */
    public function resolveSecret(int $tenantId): string
    {
        $tenant = $this->find($tenantId);
        if ($tenant === null) {
            throw new \RuntimeException("Tenant {$tenantId} not found");
        }

        $ref = (string) ($tenant->client_secret_ref ?? '');
        if ($ref === '') {
            throw new \RuntimeException("Tenant {$tenantId} has no client_secret_ref");
        }

        $row = DB::table('ahg_settings')
            ->where('setting_group', 'sharepoint')
            ->where('setting_key', $ref)
            ->first();

        if ($row === null || empty($row->setting_value)) {
            throw new \RuntimeException("Encrypted client_secret not found at ahg_settings(sharepoint, {$ref})");
        }

        if (!class_exists('\\AtomFramework\\Core\\Security\\EncryptionService')) {
            throw new \RuntimeException('EncryptionService not available — cannot decrypt client_secret');
        }

        try {
            // ahg_settings.setting_value is utf8mb4 TEXT — secrets are base64 wrapped at rest
            $blob = base64_decode((string) $row->setting_value, true);
            if ($blob === false) {
                throw new \RuntimeException('client_secret blob is not valid base64');
            }
            return \AtomFramework\Core\Security\EncryptionService::decrypt($blob);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to decrypt client_secret: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Persist a new client_secret encrypted at rest.
     * Used by tenant-edit admin UI on save.
     */
    public function persistSecret(int $tenantId, string $plaintextSecret): string
    {
        if (!class_exists('\\AtomFramework\\Core\\Security\\EncryptionService')) {
            throw new \RuntimeException('EncryptionService not available — cannot encrypt client_secret');
        }
        // EncryptionService returns raw binary; base64 wrap so it survives utf8mb4 TEXT column
        $ciphertext = base64_encode(\AtomFramework\Core\Security\EncryptionService::encrypt($plaintextSecret));
        $ref = "client_secret_{$tenantId}";

        DB::table('ahg_settings')->updateOrInsert(
            ['setting_group' => 'sharepoint', 'setting_key' => $ref],
            ['setting_value' => $ciphertext, 'updated_at' => date('Y-m-d H:i:s')],
        );

        $this->update($tenantId, ['client_secret_ref' => $ref]);
        return $ref;
    }
}
