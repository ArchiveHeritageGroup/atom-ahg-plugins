<?php

namespace AhgJobsManage\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Service for browsing and managing queue jobs in the admin UI.
 *
 * Provides paginated queries against ahg_queue_job, ahg_queue_batch,
 * and ahg_queue_failed tables with filtering and sorting.
 */
class QueueJobsService
{
    /**
     * Browse queue jobs with filters and pagination.
     *
     * @param array $filters Optional keys: queue, status, job_type, batch_id, user_id
     * @param int   $limit   Results per page
     * @param int   $page    Current page
     *
     * @return array ['items' => [...], 'total' => N, 'page' => N, 'pages' => N]
     */
    public function browseQueueJobs(array $filters = [], int $limit = 25, int $page = 1): array
    {
        $page = max(1, $page);
        $limit = max(1, min(100, $limit));

        $query = DB::table('ahg_queue_job')
            ->leftJoin('user', 'user.id', '=', 'ahg_queue_job.user_id')
            ->leftJoin('actor_i18n', function ($join) {
                $join->on('actor_i18n.id', '=', 'ahg_queue_job.user_id')
                    ->where('actor_i18n.culture', '=', 'en');
            })
            ->select([
                'ahg_queue_job.*',
                'user.username',
                DB::raw("COALESCE(actor_i18n.authorized_form_of_name, user.username) as user_name"),
            ]);

        if (!empty($filters['queue'])) {
            $query->where('ahg_queue_job.queue', $filters['queue']);
        }
        if (!empty($filters['status'])) {
            $query->where('ahg_queue_job.status', $filters['status']);
        }
        if (!empty($filters['job_type'])) {
            $query->where('ahg_queue_job.job_type', $filters['job_type']);
        }
        if (!empty($filters['batch_id'])) {
            $query->where('ahg_queue_job.batch_id', $filters['batch_id']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('ahg_queue_job.user_id', $filters['user_id']);
        }

        $total = $query->count();
        $offset = ($page - 1) * $limit;

        $items = (clone $query)
            ->orderByDesc('ahg_queue_job.id')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return [
            'items' => $items->toArray(),
            'total' => $total,
            'page' => $page,
            'pages' => $total > 0 ? (int) ceil($total / $limit) : 0,
        ];
    }

    /**
     * Get a single queue job by ID.
     */
    public function getQueueJob(int $id): ?object
    {
        return DB::table('ahg_queue_job')
            ->leftJoin('user', 'user.id', '=', 'ahg_queue_job.user_id')
            ->leftJoin('actor_i18n', function ($join) {
                $join->on('actor_i18n.id', '=', 'ahg_queue_job.user_id')
                    ->where('actor_i18n.culture', '=', 'en');
            })
            ->select([
                'ahg_queue_job.*',
                'user.username',
                DB::raw("COALESCE(actor_i18n.authorized_form_of_name, user.username) as user_name"),
            ])
            ->where('ahg_queue_job.id', $id)
            ->first();
    }

    /**
     * Get queue statistics grouped by queue name.
     */
    public function getQueueStats(): array
    {
        $stats = DB::table('ahg_queue_job')
            ->selectRaw("
                queue,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'reserved' THEN 1 ELSE 0 END) as reserved,
                SUM(CASE WHEN status = 'running' THEN 1 ELSE 0 END) as running,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            ")
            ->groupBy('queue')
            ->get()
            ->toArray();

        // Also get totals
        $totals = DB::table('ahg_queue_job')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status IN ('reserved', 'running') THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            ")
            ->first();

        $failedCount = DB::table('ahg_queue_failed')->count();

        return [
            'queues' => $stats,
            'total' => (int) ($totals->total ?? 0),
            'pending' => (int) ($totals->pending ?? 0),
            'active' => (int) ($totals->active ?? 0),
            'completed' => (int) ($totals->completed ?? 0),
            'failed' => (int) ($totals->failed ?? 0),
            'archived_failed' => $failedCount,
        ];
    }

    /**
     * Browse batches with pagination.
     */
    public function browseQueueBatches(int $limit = 25, int $page = 1): array
    {
        $page = max(1, $page);
        $limit = max(1, min(100, $limit));

        $total = DB::table('ahg_queue_batch')->count();
        $offset = ($page - 1) * $limit;

        $items = DB::table('ahg_queue_batch')
            ->leftJoin('user', 'user.id', '=', 'ahg_queue_batch.user_id')
            ->leftJoin('actor_i18n', function ($join) {
                $join->on('actor_i18n.id', '=', 'ahg_queue_batch.user_id')
                    ->where('actor_i18n.culture', '=', 'en');
            })
            ->select([
                'ahg_queue_batch.*',
                DB::raw("COALESCE(actor_i18n.authorized_form_of_name, user.username) as user_name"),
            ])
            ->orderByDesc('ahg_queue_batch.id')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return [
            'items' => $items->toArray(),
            'total' => $total,
            'page' => $page,
            'pages' => $total > 0 ? (int) ceil($total / $limit) : 0,
        ];
    }
}
