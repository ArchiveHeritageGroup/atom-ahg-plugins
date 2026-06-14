<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Group ACL management (AtoM-native model) for ahgSecurityClearancePlugin.
 *
 * Ports the proven Heratio ahg-acl AclService logic: ACL groups, membership,
 * and a grant/deny/inherit permission matrix over the four describable classes
 * (information object, actor, repository, term). Permissions live in
 * acl_permission with object_id NULL = root/class-level, `constants` JSON for
 * repository scoping, and class derived via the object.class_name join.
 *
 * NOTE: this service is MANAGEMENT + STORAGE only. checkPermission() is provided
 * for a future enforcement layer but is intentionally NOT wired into any live
 * access decision yet (enforcement is gated until reviewed).
 *
 * Global namespace + Laravel QB, matching SecurityClearanceService.
 */
class AclGroupService
{
    public const GRANT = 1;
    public const DENY = 0;
    public const INHERIT = -1;

    public const IO_ACTIONS = [
        'read' => 'Read', 'create' => 'Create', 'update' => 'Update', 'delete' => 'Delete',
        'viewDraft' => 'View draft', 'publish' => 'Publish',
        'readMaster' => 'Access master', 'readReference' => 'Access reference', 'readThumbnail' => 'Access thumbnail',
    ];
    public const ACTOR_ACTIONS = self::IO_ACTIONS;
    public const REPOSITORY_ACTIONS = ['read' => 'Read', 'create' => 'Create', 'update' => 'Update', 'delete' => 'Delete'];
    public const TERM_ACTIONS = ['create' => 'Create', 'update' => 'Update', 'delete' => 'Delete'];

    public const CLASS_ACTIONS = [
        'QubitInformationObject' => self::IO_ACTIONS,
        'QubitActor' => self::ACTOR_ACTIONS,
        'QubitRepository' => self::REPOSITORY_ACTIONS,
        'QubitTerm' => self::TERM_ACTIONS,
    ];

    // ------------------------------------------------------------------
    // Groups
    // ------------------------------------------------------------------

    public function getGroups(): array
    {
        return DB::table('acl_group as g')
            ->leftJoin('acl_group_i18n as gi', function ($j) {
                $j->on('gi.id', '=', 'g.id')->where('gi.culture', '=', 'en');
            })
            ->leftJoin(DB::raw('(SELECT group_id, COUNT(*) AS member_count FROM acl_user_group GROUP BY group_id) AS mc'), 'mc.group_id', '=', 'g.id')
            ->select('g.id', 'g.parent_id', 'g.created_at', 'g.updated_at', 'gi.name', 'gi.description', DB::raw('COALESCE(mc.member_count, 0) AS member_count'))
            ->orderBy('gi.name')
            ->get()->all();
    }

    public function getGroup(int $id): ?object
    {
        $group = DB::table('acl_group as g')
            ->leftJoin('acl_group_i18n as gi', function ($j) {
                $j->on('gi.id', '=', 'g.id')->where('gi.culture', '=', 'en');
            })
            ->select('g.id', 'g.parent_id', 'g.created_at', 'g.updated_at', 'gi.name', 'gi.description')
            ->where('g.id', $id)
            ->first();

        if (!$group) {
            return null;
        }

        $group->members = $this->getMembers($id);
        $group->translate = $this->getGroupTranslateFlag($id);

        return $group;
    }

    public function createGroup(string $name, ?string $description = null): int
    {
        $now = date('Y-m-d H:i:s');
        $id = DB::table('acl_group')->insertGetId([
            'source_culture' => 'en',
            'serial_number' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('acl_group_i18n')->insert([
            'id' => $id, 'culture' => 'en', 'name' => $name, 'description' => $description, 'serial_number' => 0,
        ]);

        return $id;
    }

    public function deleteGroup(int $id): void
    {
        DB::table('acl_permission')->where('group_id', $id)->delete();
        DB::table('acl_user_group')->where('group_id', $id)->delete();
        DB::table('acl_group_i18n')->where('id', $id)->delete();
        DB::table('acl_group')->where('id', $id)->delete();
    }

    /**
     * Save the Profile tab: name, description, and the single translate grant.
     */
    public function saveGroupProfile(int $groupId, array $data): void
    {
        $now = date('Y-m-d H:i:s');
        $existing = DB::table('acl_group_i18n')->where('id', $groupId)->where('culture', 'en')->first();
        if ($existing) {
            DB::table('acl_group_i18n')->where('id', $groupId)->where('culture', 'en')
                ->update(['name' => $data['name'] ?? null, 'description' => $data['description'] ?? null]);
        } else {
            DB::table('acl_group_i18n')->insert([
                'id' => $groupId, 'culture' => 'en', 'name' => $data['name'] ?? null, 'description' => $data['description'] ?? null, 'serial_number' => 0,
            ]);
        }
        DB::table('acl_group')->where('id', $groupId)->update(['updated_at' => $now]);

        $translate = !empty($data['translate']);
        $row = DB::table('acl_permission')->where('group_id', $groupId)->where('action', 'translate')->whereNull('object_id')->first();
        if ($translate && !$row) {
            DB::table('acl_permission')->insert([
                'group_id' => $groupId, 'action' => 'translate', 'grant_deny' => 1, 'created_at' => $now, 'updated_at' => $now, 'serial_number' => 0,
            ]);
        } elseif (!$translate && $row) {
            DB::table('acl_permission')->where('id', $row->id)->delete();
        }
    }

    public function getGroupTranslateFlag(int $groupId): bool
    {
        return DB::table('acl_permission')->where('group_id', $groupId)->where('action', 'translate')
            ->whereNull('object_id')->where('grant_deny', 1)->exists();
    }

    // ------------------------------------------------------------------
    // Membership
    // ------------------------------------------------------------------

    public function getMembers(int $groupId): array
    {
        return DB::table('acl_user_group as ug')
            ->join('user as u', 'u.id', '=', 'ug.user_id')
            ->leftJoin('actor_i18n as ai', function ($j) {
                $j->on('ai.id', '=', 'u.id')->where('ai.culture', '=', 'en');
            })
            ->select('ug.id as membership_id', 'ug.user_id', 'u.username', 'u.email', 'ai.authorized_form_of_name as display_name')
            ->where('ug.group_id', $groupId)
            ->orderBy('ai.authorized_form_of_name')
            ->get()->all();
    }

    public function addMember(int $groupId, int $userId): void
    {
        $exists = DB::table('acl_user_group')->where('group_id', $groupId)->where('user_id', $userId)->exists();
        if (!$exists) {
            DB::table('acl_user_group')->insert(['group_id' => $groupId, 'user_id' => $userId, 'serial_number' => 0]);
        }
    }

    public function removeMember(int $membershipId): void
    {
        DB::table('acl_user_group')->where('id', $membershipId)->delete();
    }

    // ------------------------------------------------------------------
    // Permissions
    // ------------------------------------------------------------------

    public function getGroupPermissionsByClass(int $groupId, string $className): array
    {
        return DB::table('acl_permission as p')
            ->leftJoin('object as o', 'o.id', '=', 'p.object_id')
            ->where('p.group_id', $groupId)
            ->where(function ($q) use ($className) {
                $q->whereNull('p.object_id')->orWhere('o.class_name', $className);
            })
            ->select('p.id', 'p.object_id', 'p.action', 'p.grant_deny as grantDeny', 'p.constants', 'o.class_name')
            ->orderBy('p.object_id')->orderBy('p.action')
            ->get()->all();
    }

    /**
     * Bucket a permission list by scope: root / per-repository / per-object.
     */
    public function bucketPermissions(array $perms): array
    {
        $root = [];
        $repos = [];
        $objs = [];
        foreach ($perms as $p) {
            $repoSlug = null;
            if (!empty($p->constants)) {
                $c = json_decode($p->constants, true) ?: [];
                $repoSlug = $c['repository'] ?? null;
            }
            if ($p->object_id === null && $repoSlug === null) {
                $root[$p->action] = $p;
            } elseif ($repoSlug !== null) {
                $repos[$repoSlug][$p->action] = $p;
            } else {
                $objs[$p->object_id][$p->action] = $p;
            }
        }

        return ['root' => $root, 'repositories' => $repos, 'objects' => $objs];
    }

    /**
     * Apply matrix form data (`acl[<perm_id|action_scope>] = 1|0|-1`) for one
     * action set + class. Existing perm + grant/deny → update; + inherit → delete;
     * new key + grant/deny → insert (root/object/repo scope from the key).
     */
    public function applyAclForm(int $groupId, array $form, array $allowedActions, string $className): void
    {
        $now = date('Y-m-d H:i:s');
        $existing = [];
        foreach ($this->getGroupPermissionsByClass($groupId, $className) as $p) {
            $existing[$p->id] = $p;
        }

        foreach ($form as $key => $value) {
            $value = (int) $value;

            if (ctype_digit((string) $key)) {
                $permId = (int) $key;
                if (!isset($existing[$permId])) {
                    continue;
                }
                if (self::INHERIT === $value) {
                    DB::table('acl_permission')->where('id', $permId)->delete();
                } elseif (in_array($value, [self::GRANT, self::DENY], true)) {
                    DB::table('acl_permission')->where('id', $permId)->update(['grant_deny' => $value, 'updated_at' => $now]);
                }

                continue;
            }

            if (!preg_match('/^([a-zA-Z]+)_(.+)$/', $key, $m)) {
                continue;
            }
            [, $action, $scopeKey] = $m;
            if (!isset($allowedActions[$action]) || ($value !== self::GRANT && $value !== self::DENY)) {
                continue;
            }

            $objectId = null;
            $constants = null;
            if ('root' === $scopeKey) {
                // root/class-level grant
            } elseif (ctype_digit($scopeKey)) {
                $objectId = (int) $scopeKey;
            } else {
                $constants = json_encode(['repository' => $scopeKey]);
            }

            DB::table('acl_permission')->insert([
                'group_id' => $groupId,
                'object_id' => $objectId,
                'action' => $action,
                'grant_deny' => $value,
                'constants' => $constants,
                'created_at' => $now,
                'updated_at' => $now,
                'serial_number' => 0,
            ]);
        }
    }

    public function deletePermission(int $id): bool
    {
        return DB::table('acl_permission')->where('id', $id)->delete() > 0;
    }

    // ------------------------------------------------------------------
    // Enforcement (DEFERRED — NOT wired into any live access decision yet)
    // ------------------------------------------------------------------

    /**
     * Resolve whether a user is granted an action at root/class level via their
     * groups. Provided for a future enforcement layer; intentionally unused so
     * that creating groups/permissions cannot change live access until the
     * enforcement integration is reviewed and explicitly enabled.
     */
    public function checkPermission(int $userId, string $action, ?int $objectId = null): ?bool
    {
        $rows = DB::table('acl_permission as p')
            ->join('acl_user_group as ug', 'ug.group_id', '=', 'p.group_id')
            ->where('ug.user_id', $userId)
            ->where('p.action', $action)
            ->where(function ($q) use ($objectId) {
                $q->whereNull('p.object_id');
                if ($objectId) {
                    $q->orWhere('p.object_id', $objectId);
                }
            })
            ->select('p.grant_deny', 'p.object_id')
            ->get();

        if ($rows->isEmpty()) {
            return null; // inherit / no opinion
        }
        // Explicit deny wins; object-level beats root-level.
        $decision = null;
        foreach ($rows as $r) {
            if ((int) $r->grant_deny === self::DENY) {
                return false;
            }
            $decision = true;
        }

        return $decision;
    }
}
