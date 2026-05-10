<?php

namespace AtomExtensions\SharePoint\Services;

use AtomExtensions\SharePoint\Repositories\SharePointUserMappingRepository;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * SharePointUserMappingService — resolves AAD claims to AtoM user identity.
 *
 * Three lookup behaviors per locked decision (plan §6.5.5):
 *   1. Existing mapping (preferred path)
 *   2. Auto-create on first push (default true, gated by setting
 *      sharepoint_push_user_create_enabled)
 *   3. Reject if auto-create disabled and no mapping exists (returns null)
 *
 * @phase 2.B
 */
class SharePointUserMappingService
{
    public function __construct(
        private SharePointUserMappingRepository $mappings,
    ) {
    }

    /**
     * Resolve an AAD claim set to an AtoM user id, optionally creating one.
     *
     * @param array $claims Output of GraphTokenValidatorService::validate()
     * @return int|null AtoM user.id, or null if rejected.
     */
    public function resolve(array $claims): ?int
    {
        $oid = (string) ($claims['oid'] ?? '');
        if ($oid === '') {
            return null;
        }

        $mapping = $this->mappings->findByAadOid($oid);
        if ($mapping !== null) {
            $this->mappings->touchLastSeen((int) $mapping->id);
            return (int) $mapping->atom_user_id;
        }

        if (!$this->autoCreateEnabled()) {
            return null;
        }

        $atomUserId = $this->createAtomUser($claims);
        if ($atomUserId === null) {
            return null;
        }

        $this->mappings->create([
            'aad_object_id' => $oid,
            'aad_upn' => $claims['upn'] ?? null,
            'aad_email' => $claims['email'] ?? null,
            'atom_user_id' => $atomUserId,
            'created_by' => 'auto',
            'last_seen_at' => date('Y-m-d H:i:s'),
        ]);

        return $atomUserId;
    }

    private function autoCreateEnabled(): bool
    {
        $row = DB::table('ahg_settings')
            ->where('setting_group', 'sharepoint')
            ->where('setting_key', 'sharepoint_push_user_create_enabled')
            ->first();
        if ($row === null) {
            return true; // default true per locked decision
        }
        return (string) $row->setting_value === 'true' || (string) $row->setting_value === '1';
    }

    /**
     * Create an AtoM user record. AtoM's user table extends actor (FK chain),
     * so this is non-trivial — it must INSERT actor first, then user.
     *
     * Phase 2.B implementation note (TODO): use the framework's
     * Services\Write\PropelUserWriteService instead of hand-rolling INSERTs.
     */
    private function createAtomUser(array $claims): ?int
    {
        // TODO (Phase 2.B integration):
        //   1. Resolve writer: \AtomFramework\Services\Write\WriteServiceFactory::user()
        //   2. INSERT actor (object first, then actor row).
        //   3. INSERT user with username = upn or email, email = email claim,
        //      role = configurable default (default 'editor').
        //   4. Return user.id.
        // For now, fail loudly so this gap is obvious during integration.
        throw new \RuntimeException(
            'SharePointUserMappingService::createAtomUser not implemented yet — '
            . 'wire to WriteServiceFactory::user()->create($claims).'
        );
    }
}
