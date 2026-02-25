<?php

namespace AhgRegistry\Services;

use Illuminate\Database\Capsule\Manager as DB;

class DiscussionService
{
    protected string $culture;
    protected string $table = 'registry_discussion';
    protected string $replyTable = 'registry_discussion_reply';

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
    }

    // =========================================================================
    // Browse & View
    // =========================================================================

    /**
     * Paginated discussions for a group with filters.
     */
    public function browse(int $groupId, array $params = []): array
    {
        $query = DB::table($this->table)->where('group_id', $groupId);

        if (!empty($params['topic_type'])) {
            $query->where('topic_type', $params['topic_type']);
        }
        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        } else {
            // Default: exclude hidden and spam
            $query->whereIn('status', ['active', 'closed']);
        }
        if (!empty($params['query'])) {
            $query->whereRaw("MATCH(title, content) AGAINST(? IN BOOLEAN MODE)", [$params['query']]);
        }

        $total = $query->count();

        $limit = $params['limit'] ?? 20;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $items = $query
            ->orderBy('is_pinned', 'desc')
            ->orderBy('last_reply_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Get a discussion with replies (nested), increment view count.
     */
    public function view(int $id): ?array
    {
        $discussion = DB::table($this->table)->where('id', $id)->first();
        if (!$discussion) {
            return null;
        }

        // Increment view count
        DB::table($this->table)->where('id', $id)->increment('view_count');

        // Get all replies
        $replies = DB::table($this->replyTable)
            ->where('discussion_id', $id)
            ->where('status', 'active')
            ->orderBy('created_at', 'asc')
            ->get()
            ->all();

        // Build nested reply tree
        $replyTree = $this->buildReplyTree($replies);

        return [
            'discussion' => $discussion,
            'replies' => $replyTree,
            'reply_count' => count($replies),
        ];
    }

    // =========================================================================
    // CRUD
    // =========================================================================

    /**
     * Create a new discussion in a group.
     */
    public function create(int $groupId, array $data): array
    {
        // Verify group exists and is active
        $group = DB::table('registry_user_group')
            ->where('id', $groupId)
            ->where('is_active', 1)
            ->first();

        if (!$group) {
            return ['success' => false, 'error' => 'Group not found or inactive'];
        }

        if (empty($data['title'])) {
            return ['success' => false, 'error' => 'Discussion title is required'];
        }
        if (empty($data['content'])) {
            return ['success' => false, 'error' => 'Discussion content is required'];
        }
        if (empty($data['author_email'])) {
            return ['success' => false, 'error' => 'Author email is required'];
        }

        // Handle JSON fields
        if (isset($data['tags']) && is_array($data['tags'])) {
            $data['tags'] = json_encode($data['tags']);
        }

        $data['group_id'] = $groupId;
        $data['status'] = $data['status'] ?? 'active';
        $data['reply_count'] = 0;
        $data['view_count'] = 0;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $id = DB::table($this->table)->insertGetId($data);

        return ['success' => true, 'id' => $id];
    }

    /**
     * Update an existing discussion.
     */
    public function update(int $id, array $data): array
    {
        $discussion = DB::table($this->table)->where('id', $id)->first();
        if (!$discussion) {
            return ['success' => false, 'error' => 'Discussion not found'];
        }

        if (isset($data['tags']) && is_array($data['tags'])) {
            $data['tags'] = json_encode($data['tags']);
        }

        $data['updated_at'] = date('Y-m-d H:i:s');

        DB::table($this->table)->where('id', $id)->update($data);

        return ['success' => true];
    }

    /**
     * Delete a discussion and all its replies.
     */
    public function delete(int $id): array
    {
        $discussion = DB::table($this->table)->where('id', $id)->first();
        if (!$discussion) {
            return ['success' => false, 'error' => 'Discussion not found'];
        }

        // Delete reply attachments
        $replyIds = DB::table($this->replyTable)
            ->where('discussion_id', $id)
            ->pluck('id')
            ->all();

        if (!empty($replyIds)) {
            DB::table('registry_attachment')
                ->where('entity_type', 'reply')
                ->whereIn('entity_id', $replyIds)
                ->delete();
        }

        // Delete replies
        DB::table($this->replyTable)->where('discussion_id', $id)->delete();

        // Delete discussion attachments
        DB::table('registry_attachment')
            ->where('entity_type', 'discussion')
            ->where('entity_id', $id)
            ->delete();

        // Delete discussion
        DB::table($this->table)->where('id', $id)->delete();

        return ['success' => true];
    }

    // =========================================================================
    // Replies
    // =========================================================================

    /**
     * Add a reply to a discussion.
     */
    public function reply(int $discussionId, array $data): array
    {
        $discussion = DB::table($this->table)->where('id', $discussionId)->first();
        if (!$discussion) {
            return ['success' => false, 'error' => 'Discussion not found'];
        }

        if ($discussion->is_locked) {
            return ['success' => false, 'error' => 'Discussion is locked'];
        }

        if (empty($data['content'])) {
            return ['success' => false, 'error' => 'Reply content is required'];
        }
        if (empty($data['author_email'])) {
            return ['success' => false, 'error' => 'Author email is required'];
        }

        $data['discussion_id'] = $discussionId;
        $data['status'] = 'active';
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $replyId = DB::table($this->replyTable)->insertGetId($data);

        // Update discussion reply_count and last_reply_at
        $replyCount = DB::table($this->replyTable)
            ->where('discussion_id', $discussionId)
            ->where('status', 'active')
            ->count();

        DB::table($this->table)->where('id', $discussionId)->update([
            'reply_count' => $replyCount,
            'last_reply_at' => date('Y-m-d H:i:s'),
            'last_reply_by' => $data['author_name'] ?? $data['author_email'],
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true, 'id' => $replyId];
    }

    /**
     * Delete a reply and recalculate reply count.
     */
    public function deleteReply(int $replyId): array
    {
        $reply = DB::table($this->replyTable)->where('id', $replyId)->first();
        if (!$reply) {
            return ['success' => false, 'error' => 'Reply not found'];
        }

        $discussionId = $reply->discussion_id;

        // Delete any child replies (nested)
        DB::table($this->replyTable)->where('parent_reply_id', $replyId)->delete();

        // Delete reply attachments
        DB::table('registry_attachment')
            ->where('entity_type', 'reply')
            ->where('entity_id', $replyId)
            ->delete();

        // Delete the reply
        DB::table($this->replyTable)->where('id', $replyId)->delete();

        // Recalculate reply count
        $replyCount = DB::table($this->replyTable)
            ->where('discussion_id', $discussionId)
            ->where('status', 'active')
            ->count();

        // Get latest reply
        $latestReply = DB::table($this->replyTable)
            ->where('discussion_id', $discussionId)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->first();

        DB::table($this->table)->where('id', $discussionId)->update([
            'reply_count' => $replyCount,
            'last_reply_at' => $latestReply ? $latestReply->created_at : null,
            'last_reply_by' => $latestReply ? ($latestReply->author_name ?? $latestReply->author_email) : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true];
    }

    // =========================================================================
    // Moderation
    // =========================================================================

    /**
     * Pin a discussion.
     */
    public function pin(int $id): array
    {
        $discussion = DB::table($this->table)->where('id', $id)->first();
        if (!$discussion) {
            return ['success' => false, 'error' => 'Discussion not found'];
        }

        DB::table($this->table)->where('id', $id)->update([
            'is_pinned' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true];
    }

    /**
     * Unpin a discussion.
     */
    public function unpin(int $id): array
    {
        $discussion = DB::table($this->table)->where('id', $id)->first();
        if (!$discussion) {
            return ['success' => false, 'error' => 'Discussion not found'];
        }

        DB::table($this->table)->where('id', $id)->update([
            'is_pinned' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true];
    }

    /**
     * Lock a discussion (no new replies).
     */
    public function lock(int $id): array
    {
        $discussion = DB::table($this->table)->where('id', $id)->first();
        if (!$discussion) {
            return ['success' => false, 'error' => 'Discussion not found'];
        }

        DB::table($this->table)->where('id', $id)->update([
            'is_locked' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true];
    }

    /**
     * Unlock a discussion.
     */
    public function unlock(int $id): array
    {
        $discussion = DB::table($this->table)->where('id', $id)->first();
        if (!$discussion) {
            return ['success' => false, 'error' => 'Discussion not found'];
        }

        DB::table($this->table)->where('id', $id)->update([
            'is_locked' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true];
    }

    /**
     * Mark a discussion as resolved.
     */
    public function resolve(int $id): array
    {
        $discussion = DB::table($this->table)->where('id', $id)->first();
        if (!$discussion) {
            return ['success' => false, 'error' => 'Discussion not found'];
        }

        DB::table($this->table)->where('id', $id)->update([
            'is_resolved' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true];
    }

    /**
     * Mark a reply as the accepted answer.
     */
    public function markAcceptedAnswer(int $replyId): array
    {
        $reply = DB::table($this->replyTable)->where('id', $replyId)->first();
        if (!$reply) {
            return ['success' => false, 'error' => 'Reply not found'];
        }

        // Unmark any previously accepted answer for this discussion
        DB::table($this->replyTable)
            ->where('discussion_id', $reply->discussion_id)
            ->where('is_accepted_answer', 1)
            ->update(['is_accepted_answer' => 0]);

        // Mark this reply
        DB::table($this->replyTable)->where('id', $replyId)->update([
            'is_accepted_answer' => 1,
        ]);

        // Also resolve the discussion
        DB::table($this->table)->where('id', $reply->discussion_id)->update([
            'is_resolved' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true];
    }

    // =========================================================================
    // Cross-Group Queries
    // =========================================================================

    /**
     * Get recent/trending discussions across all groups.
     */
    public function getRecentAcrossGroups(int $limit = 10): array
    {
        return DB::table("{$this->table} as d")
            ->leftJoin('registry_user_group as g', 'g.id', '=', 'd.group_id')
            ->where('d.status', 'active')
            ->where('g.is_active', 1)
            ->select('d.*', 'g.name as group_name', 'g.slug as group_slug')
            ->orderBy('d.last_reply_at', 'desc')
            ->orderBy('d.created_at', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Build nested reply tree from flat list using parent_reply_id.
     */
    public function buildReplyTree(array $replies): array
    {
        $indexed = [];
        foreach ($replies as $reply) {
            $r = (array) $reply;
            $r['children'] = [];
            $indexed[$r['id']] = $r;
        }

        $tree = [];
        foreach ($indexed as $id => &$reply) {
            $parentId = $reply['parent_reply_id'] ?? null;
            if ($parentId && isset($indexed[$parentId])) {
                $indexed[$parentId]['children'][] = &$reply;
            } else {
                $tree[] = &$reply;
            }
        }
        unset($reply);

        // Convert back to objects for template compatibility
        return $this->arrayTreeToObjects($tree);
    }

    private function arrayTreeToObjects(array $tree): array
    {
        $result = [];
        foreach ($tree as $node) {
            $children = $node['children'] ?? [];
            unset($node['children']);
            $obj = (object) $node;
            $obj->children = !empty($children) ? $this->arrayTreeToObjects($children) : [];
            $result[] = $obj;
        }

        return $result;
    }
}
