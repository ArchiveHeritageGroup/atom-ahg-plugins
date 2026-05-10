<?php

namespace AtomExtensions\SharePoint\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * SharePointEventRepository — CRUD + idempotency for sharepoint_event rows.
 *
 * Idempotency strategy (plan §6.3 step 2):
 *   Reject duplicate (drive_id, sp_item_id, sp_etag) where status='completed'.
 *   This keeps Graph re-deliveries from re-creating AtoM records.
 *
 * @phase 2
 */
class SharePointEventRepository
{
    public function find(int $id): ?\stdClass
    {
        return DB::table('sharepoint_event')->where('id', $id)->first();
    }

    public function create(array $attributes): int
    {
        $attributes['received_at'] ??= date('Y-m-d H:i:s');
        $attributes['status'] ??= 'received';
        return (int) DB::table('sharepoint_event')->insertGetId($attributes);
    }

    public function update(int $id, array $attributes): void
    {
        DB::table('sharepoint_event')->where('id', $id)->update($attributes);
    }

    public function markStatus(int $id, string $status, ?string $error = null): void
    {
        $update = ['status' => $status];
        if ($error !== null) {
            $update['last_error'] = $error;
        }
        if ($status === 'completed' || $status === 'skipped_duplicate' || $status === 'failed') {
            $update['processed_at'] = date('Y-m-d H:i:s');
        }
        DB::table('sharepoint_event')->where('id', $id)->update($update);
    }

    public function incrementAttempts(int $id): void
    {
        DB::table('sharepoint_event')
            ->where('id', $id)
            ->increment('attempts');
    }

    /**
     * Returns true if we have already successfully ingested this exact item+etag
     * via a different event row.
     */
    public function isDuplicate(int $driveId, ?string $itemId, ?string $etag, int $excludeEventId): bool
    {
        if ($itemId === null || $etag === null) {
            return false;
        }
        return DB::table('sharepoint_event')
            ->where('drive_id', $driveId)
            ->where('sp_item_id', $itemId)
            ->where('sp_etag', $etag)
            ->where('status', 'completed')
            ->where('id', '<>', $excludeEventId)
            ->exists();
    }

    /** @return array<string, int> Keyed by status. */
    public function statusCounts(?string $sinceSql = '24 HOUR'): array
    {
        $query = DB::table('sharepoint_event')->select('status', DB::raw('COUNT(*) as n'))->groupBy('status');
        if ($sinceSql !== null) {
            $query->whereRaw("received_at >= DATE_SUB(NOW(), INTERVAL {$sinceSql})");
        }
        $rows = $query->get()->all();
        $out = [];
        foreach ($rows as $row) {
            $out[$row->status] = (int) $row->n;
        }
        return $out;
    }
}
