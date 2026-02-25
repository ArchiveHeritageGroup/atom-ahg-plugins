<?php

namespace AhgRegistry\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

class UserGroupRepository
{
    protected string $table = 'registry_user_group';
    protected string $memberTable = 'registry_user_group_member';

    // -------------------------------------------------------
    // Groups
    // -------------------------------------------------------

    public function findById(int $id): ?object
    {
        return DB::table($this->table)->where('id', $id)->first();
    }

    public function findBySlug(string $slug): ?object
    {
        return DB::table($this->table)->where('slug', $slug)->first();
    }

    public function findAll(array $params = []): array
    {
        $query = DB::table($this->table)->where('is_active', 1);

        if (!empty($params['type'])) {
            $query->where('group_type', $params['type']);
        }
        if (!empty($params['country'])) {
            $query->where('country', $params['country']);
        }
        if (!empty($params['region'])) {
            $query->where('region', $params['region']);
        }
        if (isset($params['is_virtual']) && $params['is_virtual'] !== '') {
            $query->where('is_virtual', (int) $params['is_virtual']);
        }
        if (isset($params['is_featured']) && $params['is_featured'] !== '') {
            $query->where('is_featured', (int) $params['is_featured']);
        }

        $total = $query->count();

        $sort = $params['sort'] ?? 'name';
        $direction = $params['direction'] ?? 'asc';
        $limit = $params['limit'] ?? 20;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $items = $query->orderBy($sort, $direction)
                       ->limit($limit)
                       ->offset($offset)
                       ->get();

        return ['items' => $items, 'total' => $total, 'page' => (int) $page];
    }

    public function search(string $term, array $params = []): array
    {
        // Try FULLTEXT search first
        $query = DB::table($this->table)
            ->where('is_active', 1)
            ->whereRaw("MATCH(name, description) AGAINST(? IN BOOLEAN MODE)", [$term]);

        $total = $query->count();

        // If FULLTEXT returns no results, fall back to LIKE search
        if ($total === 0) {
            $likeTerm = '%' . $term . '%';
            $query = DB::table($this->table)
                ->where('is_active', 1)
                ->where(function ($q) use ($likeTerm) {
                    $q->where('name', 'LIKE', $likeTerm)
                      ->orWhere('description', 'LIKE', $likeTerm)
                      ->orWhere('country', 'LIKE', $likeTerm)
                      ->orWhere('region', 'LIKE', $likeTerm);
                });

            $total = $query->count();

            $limit = $params['limit'] ?? 20;
            $page = $params['page'] ?? 1;
            $offset = ($page - 1) * $limit;

            $items = $query->orderBy('name', 'asc')
                           ->limit($limit)
                           ->offset($offset)
                           ->get();

            return ['items' => $items, 'total' => $total, 'page' => (int) $page];
        }

        $limit = $params['limit'] ?? 20;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $items = $query->orderByRaw("MATCH(name, description) AGAINST(? IN BOOLEAN MODE) DESC", [$term])
                       ->limit($limit)
                       ->offset($offset)
                       ->get();

        return ['items' => $items, 'total' => $total, 'page' => (int) $page];
    }

    public function count(array $filters = []): int
    {
        $query = DB::table($this->table)->where('is_active', 1);

        if (!empty($filters['type'])) {
            $query->where('group_type', $filters['type']);
        }
        if (!empty($filters['country'])) {
            $query->where('country', $filters['country']);
        }

        return $query->count();
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
        return DB::table($this->table)->where('id', $id)->delete() > 0;
    }

    public function getFeatured(int $limit = 6): array
    {
        return DB::table($this->table)
            ->where('is_active', 1)
            ->where('is_featured', 1)
            ->orderBy('member_count', 'desc')
            ->orderBy('name', 'asc')
            ->limit($limit)
            ->get()
            ->all();
    }

    // -------------------------------------------------------
    // Members
    // -------------------------------------------------------

    public function findMembers(int $groupId, array $params = []): array
    {
        $query = DB::table($this->memberTable)
            ->where('group_id', $groupId)
            ->where('is_active', 1);

        if (!empty($params['role'])) {
            $query->where('role', $params['role']);
        }

        $total = $query->count();

        $sort = $params['sort'] ?? 'joined_at';
        $direction = $params['direction'] ?? 'desc';
        $limit = $params['limit'] ?? 50;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $items = $query->orderBy($sort, $direction)
                       ->limit($limit)
                       ->offset($offset)
                       ->get();

        return ['items' => $items, 'total' => $total, 'page' => (int) $page];
    }

    public function findMember(int $groupId, string $email): ?object
    {
        return DB::table($this->memberTable)
            ->where('group_id', $groupId)
            ->where('email', $email)
            ->first();
    }

    public function addMember(array $data): int
    {
        $data['joined_at'] = $data['joined_at'] ?? date('Y-m-d H:i:s');

        $id = DB::table($this->memberTable)->insertGetId($data);

        // Update group member count
        $this->refreshMemberCount($data['group_id']);

        return $id;
    }

    public function updateMember(int $id, array $data): bool
    {
        return DB::table($this->memberTable)->where('id', $id)->update($data) >= 0;
    }

    public function removeMember(int $id): bool
    {
        $member = DB::table($this->memberTable)->where('id', $id)->first();
        $deleted = DB::table($this->memberTable)->where('id', $id)->delete() > 0;

        if ($deleted && $member) {
            $this->refreshMemberCount($member->group_id);
        }

        return $deleted;
    }

    public function countMembers(int $groupId): int
    {
        return DB::table($this->memberTable)
            ->where('group_id', $groupId)
            ->where('is_active', 1)
            ->count();
    }

    public function isMember(int $groupId, string $email): bool
    {
        return DB::table($this->memberTable)
            ->where('group_id', $groupId)
            ->where('email', $email)
            ->where('is_active', 1)
            ->exists();
    }

    protected function refreshMemberCount(int $groupId): void
    {
        $count = $this->countMembers($groupId);
        DB::table($this->table)->where('id', $groupId)->update([
            'member_count' => $count,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
