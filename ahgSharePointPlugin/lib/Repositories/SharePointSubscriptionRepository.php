<?php

namespace AtomExtensions\SharePoint\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * SharePointSubscriptionRepository — CRUD for sharepoint_subscription rows.
 *
 * Per Phase 2 decision (plan §6.4): every ingest-enabled drive has TWO
 * subscriptions — one on /drives/{id}/root, one on /sites/{site}/lists/{list}.
 *
 * @phase 2
 */
class SharePointSubscriptionRepository
{
    public const RESOURCE_DRIVE_ITEM = 'driveItem';
    public const RESOURCE_LIST = 'list';

    public function find(int $id): ?\stdClass
    {
        return DB::table('sharepoint_subscription')->where('id', $id)->first();
    }

    public function findBySubscriptionId(string $subscriptionId): ?\stdClass
    {
        return DB::table('sharepoint_subscription')
            ->where('subscription_id', $subscriptionId)
            ->first();
    }

    /** @return array<int, \stdClass> */
    public function forDrive(int $driveId): array
    {
        return DB::table('sharepoint_subscription')
            ->where('drive_id', $driveId)
            ->get()
            ->all();
    }

    /**
     * Active subscriptions whose expiry falls inside the given window.
     * Used by sharepoint:renew-subscriptions cron task.
     *
     * @return array<int, \stdClass>
     */
    public function expiringWithin(string $intervalSql = 'INTERVAL 12 HOUR'): array
    {
        return DB::table('sharepoint_subscription')
            ->where('status', 'active')
            ->whereRaw("expires_at < DATE_ADD(NOW(), {$intervalSql})")
            ->get()
            ->all();
    }

    public function create(array $attributes): int
    {
        return (int) DB::table('sharepoint_subscription')->insertGetId($attributes);
    }

    public function update(int $id, array $attributes): void
    {
        DB::table('sharepoint_subscription')->where('id', $id)->update($attributes);
    }

    public function markRenewed(int $id, \DateTimeInterface $newExpiry): void
    {
        DB::table('sharepoint_subscription')
            ->where('id', $id)
            ->update([
                'expires_at' => $newExpiry->format('Y-m-d H:i:s'),
                'last_renewed_at' => date('Y-m-d H:i:s'),
                'status' => 'active',
            ]);
    }

    public function markStatus(int $id, string $status): void
    {
        DB::table('sharepoint_subscription')
            ->where('id', $id)
            ->update(['status' => $status]);
    }

    public function delete(int $id): void
    {
        DB::table('sharepoint_subscription')->where('id', $id)->delete();
    }
}
