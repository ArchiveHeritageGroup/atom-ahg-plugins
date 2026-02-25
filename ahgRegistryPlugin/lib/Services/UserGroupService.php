<?php

namespace AhgRegistry\Services;

use Illuminate\Database\Capsule\Manager as DB;

class UserGroupService
{
    protected string $culture;
    protected string $table = 'registry_user_group';
    protected string $memberTable = 'registry_user_group_member';

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
    }

    // =========================================================================
    // Browse & View
    // =========================================================================

    /**
     * Paginated browse with filters.
     */
    public function browse(array $params = []): array
    {
        $query = DB::table($this->table);
        if (empty($params['admin_mode'])) {
            $query->where('is_active', 1);
        }

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

        $searchTerm = $params['search'] ?? ($params['query'] ?? '');
        $usedLikeFallback = false;
        if (!empty($searchTerm)) {
            $query->whereRaw("MATCH(name, description) AGAINST(? IN BOOLEAN MODE)", [$searchTerm]);
        }

        $total = $query->count();

        // If FULLTEXT returned 0, fall back to LIKE search
        if ($total === 0 && !empty($searchTerm)) {
            $likeTerm = '%' . $searchTerm . '%';
            $query = DB::table($this->table);
            if (empty($params['admin_mode'])) {
                $query->where('is_active', 1);
            }

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

            $query->where(function ($q) use ($likeTerm) {
                $q->where('name', 'LIKE', $likeTerm)
                  ->orWhere('description', 'LIKE', $likeTerm)
                  ->orWhere('country', 'LIKE', $likeTerm)
                  ->orWhere('region', 'LIKE', $likeTerm);
            });

            $total = $query->count();
            $usedLikeFallback = true;
        }

        $sort = $params['sort'] ?? 'name';
        $direction = $params['direction'] ?? 'asc';
        $limit = $params['limit'] ?? 20;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $items = $query->orderBy($sort, $direction)
            ->limit($limit)
            ->offset($offset)
            ->get();

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Get group by slug with member count, recent discussions, and next meeting.
     */
    public function view(string $slug): ?array
    {
        $group = DB::table($this->table)->where('slug', $slug)->first();
        if (!$group) {
            return null;
        }

        $id = $group->id;

        // Member count (active only)
        $memberCount = DB::table($this->memberTable)
            ->where('group_id', $id)
            ->where('is_active', 1)
            ->count();

        // Recent discussions
        $recentDiscussions = DB::table('registry_discussion')
            ->where('group_id', $id)
            ->where('status', 'active')
            ->orderBy('is_pinned', 'desc')
            ->orderBy('last_reply_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->all();

        // Upcoming meeting
        $nextMeeting = null;
        if ($group->next_meeting_at && strtotime($group->next_meeting_at) > time()) {
            $nextMeeting = [
                'date' => $group->next_meeting_at,
                'details' => $group->next_meeting_details,
                'format' => $group->meeting_format,
                'platform' => $group->meeting_platform,
            ];
        }

        return [
            'group' => $group,
            'member_count' => $memberCount,
            'recent_discussions' => $recentDiscussions,
            'next_meeting' => $nextMeeting,
        ];
    }

    // =========================================================================
    // CRUD
    // =========================================================================

    /**
     * Create a new user group.
     */
    public function create(array $data): array
    {
        if (empty($data['name'])) {
            return ['success' => false, 'error' => 'Group name is required'];
        }

        $data['slug'] = $this->generateSlug($data['name']);
        $data['is_active'] = $data['is_active'] ?? 1;
        $data['member_count'] = 0;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Handle JSON fields
        if (isset($data['focus_areas']) && is_array($data['focus_areas'])) {
            $data['focus_areas'] = json_encode($data['focus_areas']);
        }

        $id = DB::table($this->table)->insertGetId($data);

        return ['success' => true, 'id' => $id, 'slug' => $data['slug']];
    }

    /**
     * Update an existing user group.
     */
    public function update(int $id, array $data): array
    {
        $group = DB::table($this->table)->where('id', $id)->first();
        if (!$group) {
            return ['success' => false, 'error' => 'Group not found'];
        }

        if (isset($data['name']) && $data['name'] !== $group->name) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        if (isset($data['focus_areas']) && is_array($data['focus_areas'])) {
            $data['focus_areas'] = json_encode($data['focus_areas']);
        }

        $data['updated_at'] = date('Y-m-d H:i:s');

        DB::table($this->table)->where('id', $id)->update($data);

        return ['success' => true];
    }

    /**
     * Toggle the featured flag on a group.
     */
    public function toggleFeatured(int $id): array
    {
        $group = DB::table($this->table)->where('id', $id)->first();
        if (!$group) {
            return ['success' => false, 'error' => 'Group not found'];
        }

        DB::table($this->table)->where('id', $id)->update([
            'is_featured' => $group->is_featured ? 0 : 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true, 'is_featured' => !$group->is_featured];
    }

    /**
     * Delete group and cascade to members and discussions.
     */
    public function delete(int $id): array
    {
        $group = DB::table($this->table)->where('id', $id)->first();
        if (!$group) {
            return ['success' => false, 'error' => 'Group not found'];
        }

        // Delete discussion replies first
        $discussionIds = DB::table('registry_discussion')
            ->where('group_id', $id)
            ->pluck('id')
            ->all();

        if (!empty($discussionIds)) {
            DB::table('registry_discussion_reply')
                ->whereIn('discussion_id', $discussionIds)
                ->delete();

            // Delete attachments on discussions
            DB::table('registry_attachment')
                ->where('entity_type', 'discussion')
                ->whereIn('entity_id', $discussionIds)
                ->delete();

            DB::table('registry_discussion')
                ->where('group_id', $id)
                ->delete();
        }

        // Delete members
        DB::table($this->memberTable)->where('group_id', $id)->delete();

        // Delete group
        DB::table($this->table)->where('id', $id)->delete();

        return ['success' => true];
    }

    // =========================================================================
    // Membership
    // =========================================================================

    /**
     * Join a group.
     */
    public function join(string $groupSlug, string $email, ?string $name = null, ?int $userId = null, ?int $institutionId = null): array
    {
        $group = DB::table($this->table)->where('slug', $groupSlug)->where('is_active', 1)->first();
        if (!$group) {
            return ['success' => false, 'error' => 'Group not found'];
        }

        if (empty($email)) {
            return ['success' => false, 'error' => 'Email is required'];
        }

        // Check if already a member
        $existing = DB::table($this->memberTable)
            ->where('group_id', $group->id)
            ->where('email', $email)
            ->first();

        if ($existing) {
            if ($existing->is_active) {
                return ['success' => false, 'error' => 'Already a member of this group'];
            }
            // Reactivate membership
            DB::table($this->memberTable)->where('id', $existing->id)->update([
                'is_active' => 1,
                'name' => $name ?? $existing->name,
                'user_id' => $userId ?? $existing->user_id,
                'institution_id' => $institutionId ?? $existing->institution_id,
            ]);
        } else {
            DB::table($this->memberTable)->insert([
                'group_id' => $group->id,
                'user_id' => $userId,
                'name' => $name,
                'email' => $email,
                'institution_id' => $institutionId,
                'role' => 'member',
                'is_active' => 1,
                'joined_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // Increment member count
        $this->recalculateMemberCount($group->id);

        return ['success' => true];
    }

    /**
     * Leave a group.
     */
    public function leave(string $groupSlug, string $email): array
    {
        $group = DB::table($this->table)->where('slug', $groupSlug)->first();
        if (!$group) {
            return ['success' => false, 'error' => 'Group not found'];
        }

        $member = DB::table($this->memberTable)
            ->where('group_id', $group->id)
            ->where('email', $email)
            ->where('is_active', 1)
            ->first();

        if (!$member) {
            return ['success' => false, 'error' => 'Not a member of this group'];
        }

        DB::table($this->memberTable)->where('id', $member->id)->update([
            'is_active' => 0,
        ]);

        // Decrement member count
        $this->recalculateMemberCount($group->id);

        return ['success' => true];
    }

    /**
     * Check if an email is a member of a group.
     */
    public function isMember(int $groupId, string $email): bool
    {
        return DB::table($this->memberTable)
            ->where('group_id', $groupId)
            ->where('email', $email)
            ->where('is_active', 1)
            ->exists();
    }

    /**
     * Get group members with roles.
     */
    public function getMembers(int $groupId): array
    {
        return DB::table($this->memberTable)
            ->where('group_id', $groupId)
            ->where('is_active', 1)
            ->orderByRaw("FIELD(role, 'organizer', 'co_organizer', 'speaker', 'sponsor', 'member')")
            ->orderBy('name', 'asc')
            ->get()
            ->all();
    }

    /**
     * Update a member's role.
     */
    public function updateMemberRole(int $groupId, string $email, string $role): array
    {
        $validRoles = ['organizer', 'co_organizer', 'member', 'speaker', 'sponsor'];
        if (!in_array($role, $validRoles)) {
            return ['success' => false, 'error' => 'Invalid role. Must be one of: ' . implode(', ', $validRoles)];
        }

        $member = DB::table($this->memberTable)
            ->where('group_id', $groupId)
            ->where('email', $email)
            ->where('is_active', 1)
            ->first();

        if (!$member) {
            return ['success' => false, 'error' => 'Member not found'];
        }

        DB::table($this->memberTable)->where('id', $member->id)->update([
            'role' => $role,
        ]);

        return ['success' => true];
    }

    /**
     * Get group by ID.
     */
    public function findById(int $id): ?object
    {
        return DB::table($this->table)->where('id', $id)->first();
    }

    /**
     * Get all members (including inactive) for admin.
     */
    public function getAllMembers(int $groupId, array $params = []): array
    {
        $query = DB::table($this->memberTable)->where('group_id', $groupId);

        if (isset($params['is_active']) && '' !== $params['is_active']) {
            $query->where('is_active', (int) $params['is_active']);
        }
        if (!empty($params['search'])) {
            $like = '%' . $params['search'] . '%';
            $query->where(function ($q) use ($like) {
                $q->where('email', 'LIKE', $like)->orWhere('name', 'LIKE', $like);
            });
        }

        $total = $query->count();
        $limit = $params['limit'] ?? 50;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $items = $query->orderByRaw("FIELD(role, 'organizer', 'co_organizer', 'speaker', 'sponsor', 'member')")
            ->orderBy('name', 'asc')
            ->limit($limit)->offset($offset)->get()->all();

        return ['items' => $items, 'total' => $total, 'page' => (int) $page];
    }

    /**
     * Remove a member by ID.
     */
    public function removeMember(int $memberId): array
    {
        $member = DB::table($this->memberTable)->where('id', $memberId)->first();
        if (!$member) {
            return ['success' => false, 'error' => 'Member not found'];
        }

        DB::table($this->memberTable)->where('id', $memberId)->delete();
        $this->recalculateMemberCount($member->group_id);

        return ['success' => true];
    }

    /**
     * Toggle active status for a member.
     */
    public function toggleMemberActive(int $memberId): array
    {
        $member = DB::table($this->memberTable)->where('id', $memberId)->first();
        if (!$member) {
            return ['success' => false, 'error' => 'Member not found'];
        }

        $newStatus = $member->is_active ? 0 : 1;
        DB::table($this->memberTable)->where('id', $memberId)->update(['is_active' => $newStatus]);
        $this->recalculateMemberCount($member->group_id);

        return ['success' => true, 'is_active' => $newStatus];
    }

    /**
     * Get groups that a user belongs to.
     */
    public function getMyGroups(string $email): array
    {
        return DB::table("{$this->memberTable} as m")
            ->leftJoin("{$this->table} as g", 'g.id', '=', 'm.group_id')
            ->where('m.email', $email)
            ->where('m.is_active', 1)
            ->where('g.is_active', 1)
            ->select('g.*', 'm.role', 'm.joined_at')
            ->orderBy('g.name', 'asc')
            ->get()
            ->all();
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Recalculate member count for a group.
     */
    private function recalculateMemberCount(int $groupId): void
    {
        $count = DB::table($this->memberTable)
            ->where('group_id', $groupId)
            ->where('is_active', 1)
            ->count();

        DB::table($this->table)->where('id', $groupId)->update([
            'member_count' => $count,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Generate a unique URL-safe slug.
     */
    public function generateSlug(string $name): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        $slug = preg_replace('/-+/', '-', $slug);

        $baseSlug = $slug;
        $counter = 1;
        while (DB::table($this->table)->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
