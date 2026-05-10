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
        // TODO:
        //   1. Fetch row, read client_secret_ref.
        //   2. Pull ciphertext from ahg_settings group=sharepoint, key=client_secret_ref.
        //   3. Call \AtomFramework\Core\Security\EncryptionService::decrypt($ciphertext, 'sharepoint').
        //   4. Return plaintext (do NOT log).
        throw new \RuntimeException('SharePointTenantRepository::resolveSecret not implemented yet');
    }
}
