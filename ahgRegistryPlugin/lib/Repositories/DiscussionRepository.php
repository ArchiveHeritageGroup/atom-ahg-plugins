<?php

namespace AhgRegistry\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

class DiscussionRepository
{
    protected string $table = 'registry_discussion';
    protected string $replyTable = 'registry_discussion_reply';

    // -------------------------------------------------------
    // Discussions
    // -------------------------------------------------------

    public function findById(int $id): ?object
    {
        return DB::table($this->table)->where('id', $id)->first();
    }

    public function findByGroup(int $groupId, array $params = []): array
    {
        $query = DB::table($this->table)
            ->where('group_id', $groupId)
            ->where('status', '!=', 'spam');

        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }
        if (!empty($params['topic_type'])) {
            $query->where('topic_type', $params['topic_type']);
        }
        if (isset($params['is_pinned'])) {
            $query->where('is_pinned', (int) $params['is_pinned']);
        }
        if (isset($params['is_resolved'])) {
            $query->where('is_resolved', (int) $params['is_resolved']);
        }

        $total = $query->count();

        $sort = $params['sort'] ?? 'last_reply_at';
        $direction = $params['direction'] ?? 'desc';
        $limit = $params['limit'] ?? 20;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        // Pinned discussions always first
        $items = $query->orderBy('is_pinned', 'desc')
                       ->orderBy($sort, $direction)
                       ->limit($limit)
                       ->offset($offset)
                       ->get();

        return ['items' => $items, 'total' => $total, 'page' => (int) $page];
    }

    public function search(string $term, array $params = []): array
    {
        $query = DB::table($this->table)
            ->where('status', 'active')
            ->whereRaw("MATCH(title, content) AGAINST(? IN BOOLEAN MODE)", [$term]);

        if (!empty($params['group_id'])) {
            $query->where('group_id', $params['group_id']);
        }

        $total = $query->count();

        $limit = $params['limit'] ?? 20;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $items = $query->orderByRaw("MATCH(title, content) AGAINST(? IN BOOLEAN MODE) DESC", [$term])
                       ->limit($limit)
                       ->offset($offset)
                       ->get();

        return ['items' => $items, 'total' => $total, 'page' => (int) $page];
    }

    public function create(array $data): int
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');

        return DB::table($this->table)->insertGetId($data);
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return DB::table($this->table)->where('id', $id)->update($data) >= 0;
    }

    public function delete(int $id): bool
    {
        // Delete all replies first
        DB::table($this->replyTable)->where('discussion_id', $id)->delete();

        return DB::table($this->table)->where('id', $id)->delete() > 0;
    }

    public function incrementViewCount(int $id): void
    {
        DB::table($this->table)->where('id', $id)->increment('view_count');
    }

    public function getPinned(int $groupId): array
    {
        return DB::table($this->table)
            ->where('group_id', $groupId)
            ->where('is_pinned', 1)
            ->where('status', 'active')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->all();
    }

    public function getRecent(int $limit = 10, ?int $groupId = null): array
    {
        $query = DB::table($this->table)->where('status', 'active');

        if ($groupId !== null) {
            $query->where('group_id', $groupId);
        }

        return $query->orderBy('last_reply_at', 'desc')
                     ->orderBy('created_at', 'desc')
                     ->limit($limit)
                     ->get()
                     ->all();
    }

    // -------------------------------------------------------
    // Replies
    // -------------------------------------------------------

    public function findReplyById(int $id): ?object
    {
        return DB::table($this->replyTable)->where('id', $id)->first();
    }

    public function findByDiscussion(int $discussionId, array $params = []): array
    {
        $query = DB::table($this->replyTable)
            ->where('discussion_id', $discussionId)
            ->where('status', 'active');

        $total = $query->count();

        $sort = $params['sort'] ?? 'created_at';
        $direction = $params['direction'] ?? 'asc';
        $limit = $params['limit'] ?? 50;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $items = $query->orderBy($sort, $direction)
                       ->limit($limit)
                       ->offset($offset)
                       ->get();

        return ['items' => $items, 'total' => $total, 'page' => (int) $page];
    }

    public function createReply(array $data): int
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');

        $id = DB::table($this->replyTable)->insertGetId($data);

        // Update discussion reply count and last reply info
        $now = date('Y-m-d H:i:s');
        DB::table($this->table)->where('id', $data['discussion_id'])->update([
            'reply_count' => DB::raw('reply_count + 1'),
            'last_reply_at' => $now,
            'last_reply_by' => $data['author_name'] ?? $data['author_email'],
            'updated_at' => $now,
        ]);

        return $id;
    }

    public function updateReply(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return DB::table($this->replyTable)->where('id', $id)->update($data) >= 0;
    }

    public function deleteReply(int $id): bool
    {
        $reply = DB::table($this->replyTable)->where('id', $id)->first();
        $deleted = DB::table($this->replyTable)->where('id', $id)->delete() > 0;

        if ($deleted && $reply) {
            // Decrement reply count
            DB::table($this->table)
                ->where('id', $reply->discussion_id)
                ->where('reply_count', '>', 0)
                ->update([
                    'reply_count' => DB::raw('reply_count - 1'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }

        return $deleted;
    }

    public function markAccepted(int $replyId): bool
    {
        $reply = DB::table($this->replyTable)->where('id', $replyId)->first();
        if (!$reply) {
            return false;
        }

        // Clear any existing accepted answer for this discussion
        DB::table($this->replyTable)
            ->where('discussion_id', $reply->discussion_id)
            ->where('is_accepted_answer', 1)
            ->update(['is_accepted_answer' => 0]);

        // Mark this reply as accepted
        DB::table($this->replyTable)->where('id', $replyId)->update(['is_accepted_answer' => 1]);

        // Mark discussion as resolved
        DB::table($this->table)->where('id', $reply->discussion_id)->update([
            'is_resolved' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return true;
    }
}
