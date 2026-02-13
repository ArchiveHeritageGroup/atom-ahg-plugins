<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * CommentService - Comments on Reports, Notes, Journal Entries, Collections
 *
 * Supports threaded comments with resolution tracking.
 *
 * @package ahgResearchPlugin
 * @version 2.1.0
 */
class CommentService
{
    /**
     * Add a comment to an entity.
     */
    public function addComment(int $researcherId, string $entityType, int $entityId, string $content, ?int $parentId = null): int
    {
        return DB::table('research_comment')->insertGetId([
            'researcher_id' => $researcherId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'parent_id' => $parentId,
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get comments for an entity (threaded).
     */
    public function getComments(string $entityType, int $entityId): array
    {
        $comments = DB::table('research_comment as c')
            ->join('research_researcher as r', 'c.researcher_id', '=', 'r.id')
            ->where('c.entity_type', $entityType)
            ->where('c.entity_id', $entityId)
            ->select('c.*', 'r.first_name', 'r.last_name')
            ->orderBy('c.created_at')
            ->get()
            ->toArray();

        // Build thread tree
        $threaded = [];
        $byId = [];
        foreach ($comments as $comment) {
            $comment->replies = [];
            $byId[$comment->id] = $comment;
        }
        foreach ($byId as $comment) {
            if ($comment->parent_id && isset($byId[$comment->parent_id])) {
                $byId[$comment->parent_id]->replies[] = $comment;
            } else {
                $threaded[] = $comment;
            }
        }

        return $threaded;
    }

    /**
     * Resolve a comment.
     */
    public function resolveComment(int $id, int $resolvedBy): bool
    {
        return DB::table('research_comment')
            ->where('id', $id)
            ->update([
                'is_resolved' => 1,
                'resolved_by' => $resolvedBy,
                'resolved_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Delete a comment (only by its author).
     */
    public function deleteComment(int $id, int $researcherId): bool
    {
        // Also delete child comments
        DB::table('research_comment')
            ->where('parent_id', $id)
            ->delete();

        return DB::table('research_comment')
            ->where('id', $id)
            ->where('researcher_id', $researcherId)
            ->delete() > 0;
    }

    /**
     * Get comment count for an entity.
     */
    public function getCommentCount(string $entityType, int $entityId): int
    {
        return DB::table('research_comment')
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->count();
    }
}
