<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Comment Service for Report Builder.
 *
 * Manages section-level comments for report review and collaboration.
 */
class CommentService
{
    /**
     * Get comments for a report, optionally filtered by section.
     *
     * @param int      $reportId  The report ID
     * @param int|null $sectionId Optional section ID filter (null = all for report)
     *
     * @return array The comments with user information
     */
    public function getComments(int $reportId, ?int $sectionId = null): array
    {
        $query = DB::table('report_comment as rc')
            ->leftJoin('user as u', 'rc.user_id', '=', 'u.id')
            ->leftJoin('user as ru', 'rc.resolved_by', '=', 'ru.id')
            ->where('rc.report_id', $reportId)
            ->select(
                'rc.id',
                'rc.report_id',
                'rc.section_id',
                'rc.user_id',
                'rc.content',
                'rc.is_resolved',
                'rc.resolved_by',
                'rc.resolved_at',
                'rc.created_at',
                'rc.updated_at',
                'u.username as author_name',
                'ru.username as resolver_name'
            )
            ->orderBy('rc.created_at', 'asc');

        if ($sectionId !== null) {
            $query->where('rc.section_id', $sectionId);
        }

        return $query->get()->toArray();
    }

    /**
     * Create a new comment.
     *
     * @param array $data The comment data (report_id, section_id, user_id, content)
     *
     * @return int The new comment ID
     */
    public function create(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        return DB::table('report_comment')->insertGetId([
            'report_id' => $data['report_id'],
            'section_id' => $data['section_id'] ?? null,
            'user_id' => $data['user_id'],
            'content' => $data['content'],
            'is_resolved' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Resolve a comment.
     *
     * @param int $commentId The comment ID
     * @param int $userId    The user resolving the comment
     *
     * @return bool True if resolved
     */
    public function resolve(int $commentId, int $userId): bool
    {
        return DB::table('report_comment')
            ->where('id', $commentId)
            ->update([
                'is_resolved' => 1,
                'resolved_by' => $userId,
                'resolved_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Unresolve a comment.
     *
     * @param int $commentId The comment ID
     *
     * @return bool True if unresolved
     */
    public function unresolve(int $commentId): bool
    {
        return DB::table('report_comment')
            ->where('id', $commentId)
            ->update([
                'is_resolved' => 0,
                'resolved_by' => null,
                'resolved_at' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Delete a comment.
     *
     * @param int $commentId The comment ID
     *
     * @return bool True if deleted
     */
    public function delete(int $commentId): bool
    {
        return DB::table('report_comment')
            ->where('id', $commentId)
            ->delete() > 0;
    }

    /**
     * Get the count of unresolved comments for a report.
     *
     * @param int $reportId The report ID
     *
     * @return int The count of unresolved comments
     */
    public function getUnresolvedCount(int $reportId): int
    {
        return DB::table('report_comment')
            ->where('report_id', $reportId)
            ->where('is_resolved', 0)
            ->count();
    }
}
