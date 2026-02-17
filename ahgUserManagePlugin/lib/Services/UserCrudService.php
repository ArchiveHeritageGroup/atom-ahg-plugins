<?php

namespace AhgUserManage\Services;

use Illuminate\Database\Capsule\Manager as DB;
use AhgCore\Services\ObjectService;

class UserCrudService
{
    /**
     * Get a user by ID.
     */
    public static function getById(int $id): ?array
    {
        $user = DB::table('user')
            ->join('object', 'user.id', '=', 'object.id')
            ->join('slug', 'user.id', '=', 'slug.object_id')
            ->where('user.id', $id)
            ->select([
                'user.id as user_id', 'user.username', 'user.email',
                'user.active', 'user.password_hash', 'user.salt',
                'slug.slug', 'object.serial_number',
            ])
            ->first();

        if (!$user) {
            return null;
        }

        $groups = self::getUserGroups((int) $user->user_id);

        return [
            'id' => (int) $user->user_id,
            'slug' => $user->slug,
            'username' => $user->username ?? '',
            'email' => $user->email ?? '',
            'active' => (bool) $user->active,
            'passwordHash' => $user->password_hash,
            'salt' => $user->salt,
            'groups' => $groups,
            'serialNumber' => $user->serial_number ?? 0,
        ];
    }

    /**
     * Get a user by slug.
     */
    public static function getBySlug(string $slug): ?array
    {
        $row = DB::table('slug')
            ->join('object', 'slug.object_id', '=', 'object.id')
            ->where('slug.slug', $slug)
            ->where('object.class_name', 'QubitUser')
            ->select(['slug.object_id'])
            ->first();

        if (!$row) {
            return null;
        }

        return self::getById($row->object_id);
    }

    /**
     * Get groups assigned to a user.
     */
    public static function getUserGroups(int $userId): array
    {
        $culture = \sfContext::getInstance()->getUser()->getCulture();

        return DB::table('acl_user_group')
            ->join('acl_group', 'acl_user_group.group_id', '=', 'acl_group.id')
            ->leftJoin('acl_group_i18n', function ($join) use ($culture) {
                $join->on('acl_group.id', '=', 'acl_group_i18n.id')
                     ->where('acl_group_i18n.culture', '=', $culture);
            })
            ->where('acl_user_group.user_id', $userId)
            ->select(['acl_group.id', 'acl_group_i18n.name'])
            ->get()
            ->all();
    }

    /**
     * Get all assignable groups (ID > 99).
     */
    public static function getAssignableGroups(string $culture = 'en'): array
    {
        return DB::table('acl_group')
            ->leftJoin('acl_group_i18n', function ($join) use ($culture) {
                $join->on('acl_group.id', '=', 'acl_group_i18n.id')
                     ->where('acl_group_i18n.culture', '=', $culture);
            })
            ->where('acl_group.id', '>', 99)
            ->select(['acl_group.id', 'acl_group_i18n.name'])
            ->orderBy('acl_group.id')
            ->get()
            ->all();
    }

    /**
     * Create a new user.
     */
    public static function create(array $data): int
    {
        return DB::transaction(function () use ($data) {
            $id = ObjectService::create('QubitUser');

            ObjectService::generateSlug($id, $data['username'] ?? null);

            // User extends Actor in AtoM — actor row must exist first (FK constraint)
            DB::table('actor')->insert([
                'id' => $id,
                'parent_id' => \QubitActor::ROOT_ID,
                'source_culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
            ]);

            // Hash password using AtoM's dual-layer approach
            $salt = md5(rand(100000, 999999) . ($data['email'] ?? ''));
            $sha1Hash = sha1($salt . ($data['password'] ?? ''));

            $hashAlgo = defined('PASSWORD_ARGON2I') ? PASSWORD_ARGON2I : PASSWORD_DEFAULT;
            $passwordHash = password_hash($sha1Hash, $hashAlgo);

            DB::table('user')->insert([
                'id' => $id,
                'username' => $data['username'] ?? '',
                'email' => $data['email'] ?? '',
                'password_hash' => $passwordHash,
                'salt' => $salt,
                'active' => isset($data['active']) ? (int) $data['active'] : 1,
            ]);

            // Assign groups
            // Always add 'authenticated' group (99)
            DB::table('acl_user_group')->insert([
                'user_id' => $id,
                'group_id' => 99,
            ]);

            if (!empty($data['groups'])) {
                foreach ($data['groups'] as $groupId) {
                    $groupId = (int) $groupId;
                    if ($groupId > 99) {
                        DB::table('acl_user_group')->insert([
                            'user_id' => $id,
                            'group_id' => $groupId,
                        ]);
                    }
                }
            }

            return $id;
        });
    }

    /**
     * Update an existing user.
     */
    public static function update(int $id, array $data): void
    {
        $updateFields = [];

        if (isset($data['username'])) {
            $updateFields['username'] = $data['username'];
        }
        if (isset($data['email'])) {
            $updateFields['email'] = $data['email'];
        }
        if (isset($data['active'])) {
            $updateFields['active'] = (int) $data['active'];
        }

        // Update password if provided
        if (!empty($data['password'])) {
            $salt = md5(rand(100000, 999999) . ($data['email'] ?? ''));
            $sha1Hash = sha1($salt . $data['password']);

            $hashAlgo = defined('PASSWORD_ARGON2I') ? PASSWORD_ARGON2I : PASSWORD_DEFAULT;
            $updateFields['password_hash'] = password_hash($sha1Hash, $hashAlgo);
            $updateFields['salt'] = $salt;
        }

        if (!empty($updateFields)) {
            DB::table('user')->where('id', $id)->update($updateFields);
        }

        // Update groups if provided
        if (isset($data['groups'])) {
            // Remove existing non-system groups
            DB::table('acl_user_group')
                ->where('user_id', $id)
                ->where('group_id', '>', 99)
                ->delete();

            foreach ($data['groups'] as $groupId) {
                $groupId = (int) $groupId;
                if ($groupId > 99) {
                    DB::table('acl_user_group')->insert([
                        'user_id' => $id,
                        'group_id' => $groupId,
                    ]);
                }
            }
        }

        ObjectService::touch($id);
    }

    /**
     * Delete a user.
     */
    public static function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            // Delete group assignments
            DB::table('acl_user_group')->where('user_id', $id)->delete();

            // Delete ACL permissions
            DB::table('acl_permission')->where('user_id', $id)->delete();

            // Delete properties (API keys etc.)
            DB::table('property')->where('object_id', $id)->delete();
            DB::table('property_i18n')
                ->whereIn('id', function ($q) use ($id) {
                    $q->select('id')->from('property')->where('object_id', $id);
                })
                ->delete();

            // Delete user record
            DB::table('user')->where('id', $id)->delete();

            // Delete actor record (user extends actor)
            DB::table('actor')->where('id', $id)->delete();

            // Delete slug and object
            ObjectService::deleteObject($id);
        });
    }

    /**
     * Get an API key for a user.
     *
     * @param string $keyName 'RestApiKey' or 'OaiApiKey'
     */
    public static function getApiKey(int $userId, string $keyName): ?string
    {
        $prop = DB::table('property')
            ->where('object_id', $userId)
            ->where('name', $keyName)
            ->first();

        if (!$prop) {
            return null;
        }

        $i18n = DB::table('property_i18n')
            ->where('id', $prop->id)
            ->first();

        return $i18n->value ?? null;
    }

    /**
     * Generate (or regenerate) an API key for a user.
     *
     * @param string $keyName 'RestApiKey' or 'OaiApiKey'
     */
    public static function generateApiKey(int $userId, string $keyName): string
    {
        $newKey = bin2hex(openssl_random_pseudo_bytes(8));

        $prop = DB::table('property')
            ->where('object_id', $userId)
            ->where('name', $keyName)
            ->first();

        if ($prop) {
            // Update existing
            DB::table('property_i18n')
                ->where('id', $prop->id)
                ->update(['value' => $newKey]);
        } else {
            // Create new property
            $propId = DB::table('property')->insertGetId([
                'object_id' => $userId,
                'name' => $keyName,
                'source_culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
            ]);

            DB::table('property_i18n')->insert([
                'id' => $propId,
                'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
                'value' => $newKey,
            ]);
        }

        return $newKey;
    }

    /**
     * Delete an API key for a user.
     *
     * @param string $keyName 'RestApiKey' or 'OaiApiKey'
     */
    public static function deleteApiKey(int $userId, string $keyName): void
    {
        $prop = DB::table('property')
            ->where('object_id', $userId)
            ->where('name', $keyName)
            ->first();

        if ($prop) {
            DB::table('property_i18n')->where('id', $prop->id)->delete();
            DB::table('property')->where('id', $prop->id)->delete();
        }
    }

    /**
     * Verify a user's password.
     */
    public static function verifyPassword(int $userId, string $password): bool
    {
        $user = DB::table('user')
            ->where('id', $userId)
            ->select(['password_hash', 'salt'])
            ->first();

        if (!$user || !$user->password_hash || !$user->salt) {
            return false;
        }

        $sha1Hash = sha1($user->salt . $password);

        return password_verify($sha1Hash, $user->password_hash);
    }

    /**
     * Get allowed translation languages for a user.
     */
    public static function getTranslateLanguages(int $userId): array
    {
        $perm = DB::table('acl_permission')
            ->where('user_id', $userId)
            ->where('action', 'translate')
            ->first();

        if (!$perm || empty($perm->constants)) {
            return [];
        }

        $decoded = @unserialize($perm->constants);
        if (is_array($decoded) && isset($decoded['languages'])) {
            return $decoded['languages'];
        }

        return [];
    }

    /**
     * Save allowed translation languages for a user.
     */
    public static function saveTranslateLanguages(int $userId, array $languages): void
    {
        $now = date('Y-m-d H:i:s');

        $perm = DB::table('acl_permission')
            ->where('user_id', $userId)
            ->where('action', 'translate')
            ->first();

        if (empty($languages)) {
            // Remove permission if no languages selected
            if ($perm) {
                DB::table('acl_permission')->where('id', $perm->id)->delete();
            }

            return;
        }

        $constants = serialize(['languages' => array_values($languages)]);
        $conditional = 'in_array(%p[language], %k[languages])';

        if ($perm) {
            DB::table('acl_permission')
                ->where('id', $perm->id)
                ->update([
                    'constants' => $constants,
                    'conditional' => $conditional,
                    'updated_at' => $now,
                ]);
        } else {
            DB::table('acl_permission')->insert([
                'user_id' => $userId,
                'action' => 'translate',
                'grant_deny' => 1,
                'conditional' => $conditional,
                'constants' => $constants,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Get all available languages in the system.
     */
    public static function getAvailableLanguages(): array
    {
        // Get languages from sfConfig (admin-configured)
        $configured = \sfConfig::get('app_i18n_languages', ['en']);

        // Also get languages actually in use
        $inUse = DB::table('information_object_i18n')
            ->select('culture')
            ->distinct()
            ->pluck('culture')
            ->toArray();

        $all = array_unique(array_merge($configured, $inUse));
        sort($all);

        return $all;
    }

    /**
     * Check if a username is already taken (optionally excluding a user ID).
     */
    public static function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $query = DB::table('user')->where('username', $username);
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Check if an email is already taken (optionally excluding a user ID).
     */
    public static function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = DB::table('user')->where('email', $email);
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
