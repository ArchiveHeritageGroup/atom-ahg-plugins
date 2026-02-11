<?php

namespace AhgJobsManage\Services;

use Illuminate\Database\Capsule\Manager as DB;

class JobsService
{
    /**
     * Status term IDs from the AtoM taxonomy.
     */
    const STATUS_IN_PROGRESS = 183;
    const STATUS_COMPLETED = 184;
    const STATUS_ERROR = 185;

    /**
     * Human-readable status labels.
     */
    const STATUS_LABELS = [
        183 => 'In progress',
        184 => 'Completed',
        185 => 'Error',
    ];

    /**
     * CSS badge classes for each status.
     */
    const STATUS_BADGES = [
        183 => 'primary',
        184 => 'success',
        185 => 'danger',
    ];

    /**
     * Get paginated job list with user info and object slugs.
     *
     * @param array $filters Filters: status (all/active/failed), sort, sortDir
     * @param int   $userId  Current user ID
     * @param bool  $isAdmin Whether the current user is an administrator
     * @param int   $limit   Results per page
     * @param int   $page    Current page number
     *
     * @return array ['items' => [...], 'total' => N, 'page' => N, 'pages' => N]
     */
    public function browse(array $filters, int $userId, bool $isAdmin, int $limit = 25, int $page = 1): array
    {
        $page = max(1, $page);
        $limit = max(1, min(100, $limit));

        $query = DB::table('job')
            ->join('object', 'object.id', '=', 'job.id')
            ->leftJoin('user', 'user.id', '=', 'job.user_id')
            ->leftJoin('actor_i18n', function ($join) {
                $join->on('actor_i18n.id', '=', 'job.user_id')
                    ->where('actor_i18n.culture', '=', 'en');
            })
            ->leftJoin('slug', 'slug.object_id', '=', 'job.object_id')
            ->select([
                'job.id',
                'job.name',
                'job.download_path',
                'job.completed_at',
                'job.user_id',
                'job.object_id',
                'job.status_id',
                'object.created_at',
                'user.username',
                DB::raw("COALESCE(actor_i18n.authorized_form_of_name, user.username) as user_name"),
                'slug.slug as object_slug',
            ]);

        // Non-admins only see their own jobs
        if (!$isAdmin) {
            $query->where('job.user_id', $userId);
        }

        // Status filter
        $status = $filters['status'] ?? 'all';
        switch ($status) {
            case 'active':
                $query->where('job.status_id', self::STATUS_IN_PROGRESS);
                break;
            case 'failed':
                $query->where('job.status_id', self::STATUS_ERROR);
                break;
            case 'completed':
                $query->where('job.status_id', self::STATUS_COMPLETED);
                break;
            // 'all' — no filter
        }

        // Get total before pagination
        $total = $query->count();

        // Sort
        $sort = $filters['sort'] ?? 'date';
        $sortDir = $filters['sortDir'] ?? 'desc';
        $sortDir = in_array(strtolower($sortDir), ['asc', 'desc']) ? strtolower($sortDir) : 'desc';

        switch ($sort) {
            case 'name':
                $query->orderBy('job.name', $sortDir);
                break;
            case 'status':
                $query->orderBy('job.status_id', $sortDir);
                break;
            case 'user':
                $query->orderBy('user_name', $sortDir);
                break;
            default:
                $query->orderBy('object.created_at', $sortDir);
                break;
        }

        $offset = ($page - 1) * $limit;
        $items = $query->offset($offset)->limit($limit)->get();

        $pages = (int) ceil($total / $limit);

        return [
            'items' => $items->toArray(),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ];
    }

    /**
     * Get a single job by ID with user name and object slug.
     *
     * @param int $id Job ID
     *
     * @return object|null
     */
    public function getById(int $id): ?object
    {
        $row = DB::table('job')
            ->join('object', 'object.id', '=', 'job.id')
            ->leftJoin('user', 'user.id', '=', 'job.user_id')
            ->leftJoin('actor_i18n', function ($join) {
                $join->on('actor_i18n.id', '=', 'job.user_id')
                    ->where('actor_i18n.culture', '=', 'en');
            })
            ->leftJoin('slug', 'slug.object_id', '=', 'job.object_id')
            ->select([
                'job.id',
                'job.name',
                'job.download_path',
                'job.completed_at',
                'job.user_id',
                'job.object_id',
                'job.status_id',
                'job.output',
                'object.created_at',
                'user.username',
                DB::raw("COALESCE(actor_i18n.authorized_form_of_name, user.username) as user_name"),
                'slug.slug as object_slug',
            ])
            ->where('job.id', $id)
            ->first();

        return $row ?: null;
    }

    /**
     * Get notes for a job from the note and note_i18n tables.
     *
     * @param int $jobId Job ID
     *
     * @return array
     */
    public function getNotes(int $jobId): array
    {
        return DB::table('note')
            ->leftJoin('note_i18n', function ($join) {
                $join->on('note_i18n.id', '=', 'note.id')
                    ->where('note_i18n.culture', '=', 'en');
            })
            ->where('note.object_id', $jobId)
            ->select([
                'note.id',
                'note.type_id',
                'note_i18n.content',
            ])
            ->orderBy('note.id')
            ->get()
            ->toArray();
    }

    /**
     * Delete completed and error jobs. Non-admins delete only their own.
     *
     * @param int  $userId  Current user ID
     * @param bool $isAdmin Whether the current user is an administrator
     *
     * @return int Number of jobs deleted
     */
    public function deleteInactive(int $userId, bool $isAdmin): int
    {
        // Get IDs of jobs to delete (completed or error status)
        $query = DB::table('job')
            ->whereIn('job.status_id', [self::STATUS_COMPLETED, self::STATUS_ERROR]);

        if (!$isAdmin) {
            $query->where('job.user_id', $userId);
        }

        $jobIds = $query->pluck('id')->toArray();

        if (empty($jobIds)) {
            return 0;
        }

        return $this->deleteJobsByIds($jobIds);
    }

    /**
     * Delete a specific job if owned by the user or user is admin.
     *
     * @param int  $id      Job ID
     * @param int  $userId  Current user ID
     * @param bool $isAdmin Whether the current user is an administrator
     *
     * @return bool
     */
    public function deleteSingle(int $id, int $userId, bool $isAdmin): bool
    {
        $job = DB::table('job')->where('id', $id)->first();

        if (!$job) {
            return false;
        }

        // Non-admins can only delete their own jobs
        if (!$isAdmin && $job->user_id != $userId) {
            return false;
        }

        // Do not allow deleting in-progress jobs
        if ($job->status_id == self::STATUS_IN_PROGRESS) {
            return false;
        }

        return $this->deleteJobsByIds([$id]) > 0;
    }

    /**
     * Get job statistics.
     *
     * @param int  $userId  Current user ID
     * @param bool $isAdmin Whether the current user is an administrator
     *
     * @return array ['total' => N, 'active' => N, 'completed' => N, 'failed' => N]
     */
    public function getStats(int $userId, bool $isAdmin): array
    {
        $query = DB::table('job');

        if (!$isAdmin) {
            $query->where('user_id', $userId);
        }

        $counts = $query
            ->select([
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status_id = " . self::STATUS_IN_PROGRESS . " THEN 1 ELSE 0 END) as active"),
                DB::raw("SUM(CASE WHEN status_id = " . self::STATUS_COMPLETED . " THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN status_id = " . self::STATUS_ERROR . " THEN 1 ELSE 0 END) as failed"),
            ])
            ->first();

        return [
            'total' => (int) ($counts->total ?? 0),
            'active' => (int) ($counts->active ?? 0),
            'completed' => (int) ($counts->completed ?? 0),
            'failed' => (int) ($counts->failed ?? 0),
        ];
    }

    /**
     * Get all jobs for CSV export.
     *
     * @param int  $userId  Current user ID
     * @param bool $isAdmin Whether the current user is an administrator
     *
     * @return array
     */
    public function exportCsv(int $userId, bool $isAdmin): array
    {
        $query = DB::table('job')
            ->join('object', 'object.id', '=', 'job.id')
            ->leftJoin('user', 'user.id', '=', 'job.user_id')
            ->leftJoin('actor_i18n', function ($join) {
                $join->on('actor_i18n.id', '=', 'job.user_id')
                    ->where('actor_i18n.culture', '=', 'en');
            })
            ->leftJoin('slug', 'slug.object_id', '=', 'job.object_id')
            ->select([
                'job.id',
                'job.name',
                'job.status_id',
                'object.created_at',
                'job.completed_at',
                DB::raw("COALESCE(actor_i18n.authorized_form_of_name, user.username) as user_name"),
                'slug.slug as object_slug',
                'job.output',
            ])
            ->orderBy('object.created_at', 'desc');

        if (!$isAdmin) {
            $query->where('job.user_id', $userId);
        }

        return $query->get()->toArray();
    }

    /**
     * Get a human-readable status label.
     *
     * @param int $statusId Status term ID
     *
     * @return string
     */
    public static function getStatusLabel(int $statusId): string
    {
        return self::STATUS_LABELS[$statusId] ?? 'Unknown';
    }

    /**
     * Get the Bootstrap badge class for a status.
     *
     * @param int $statusId Status term ID
     *
     * @return string
     */
    public static function getStatusBadge(int $statusId): string
    {
        return self::STATUS_BADGES[$statusId] ?? 'secondary';
    }

    /**
     * Delete jobs by their IDs, including related notes and object rows.
     *
     * Jobs inherit from object, and may have notes. We need to clean up
     * all related data in the correct order.
     *
     * @param array $jobIds Array of job IDs
     *
     * @return int Number of jobs deleted
     */
    private function deleteJobsByIds(array $jobIds): int
    {
        if (empty($jobIds)) {
            return 0;
        }

        // Delete notes related to these jobs (note_i18n cascades via FK)
        $noteIds = DB::table('note')
            ->whereIn('object_id', $jobIds)
            ->pluck('id')
            ->toArray();

        if (!empty($noteIds)) {
            DB::table('note_i18n')->whereIn('id', $noteIds)->delete();
            DB::table('note')->whereIn('id', $noteIds)->delete();
        }

        // Delete the job rows
        DB::table('job')->whereIn('id', $jobIds)->delete();

        // Delete the object rows (parent table)
        DB::table('object')->whereIn('id', $jobIds)->delete();

        return count($jobIds);
    }
}
