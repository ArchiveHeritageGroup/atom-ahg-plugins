<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * From a verified identity to an AtoM user (#200).
 *
 * The user is found by e-mail address. With "create on first sign-in" on, a
 * missing user is created; otherwise only existing users may sign in. Groups
 * from the identity provider are mapped to AtoM groups and added, never taken
 * away, so a group granted by hand in AtoM survives the next sign-in. Nothing
 * makes a user an administrator unless the group map says so explicitly.
 */
class SsoUserProvisioner
{
    /** @return QubitUser the user to sign in */
    public static function resolve(array $identity): QubitUser
    {
        $userId = DB::table('user')->whereRaw('LOWER(email) = ?', [$identity['email']])->value('id');
        $user = $userId ? QubitUser::getById((int) $userId) : null;

        if (null === $user) {
            if (!SsoSettings::on('sso_jit_create')) {
                throw new RuntimeException('There is no account for '.$identity['email'].' here. Ask an administrator to create one.');
            }
            $user = self::create($identity);
        } elseif (!$user->active) {
            throw new RuntimeException('This account is disabled.');
        }

        self::addGroups($user, $identity['groups'] ?? []);

        return $user;
    }

    private static function create(array $identity): QubitUser
    {
        $base = preg_replace('/[^a-z0-9._-]/', '', strtolower(strstr($identity['email'], '@', true))) ?: 'user';
        $username = $base;
        for ($i = 2; DB::table('user')->where('username', $username)->exists(); ++$i) {
            $username = $base.$i;
        }

        $user = new QubitUser();
        $user->username = $username;
        $user->email = $identity['email'];
        $user->setPassword(bin2hex(random_bytes(24))); // never used: this account signs in through the provider
        $user->active = true;
        $user->authorizedFormOfName = '' !== ($identity['name'] ?? '') ? $identity['name'] : $username;
        $user->save();

        error_log('sso.user_created: '.$username.' <'.$identity['email'].'>');

        return $user;
    }

    private static function addGroups(QubitUser $user, array $idpGroups): void
    {
        $names = [];
        if ('' !== SsoSettings::get('sso_default_group')) {
            $names[] = strtolower(SsoSettings::get('sso_default_group'));
        }
        $map = SsoSettings::groupMap();
        foreach ($idpGroups as $group) {
            if (isset($map[$group])) {
                $names[] = $map[$group];
            }
        }
        $names = array_unique(array_intersect($names, ['administrator', 'editor', 'contributor', 'translator']));
        if (!$names) {
            return;
        }

        $ids = DB::table('acl_group_i18n')->where('culture', 'en')->whereIn(DB::raw('LOWER(name)'), $names)->pluck('id')->all();
        $have = DB::table('acl_user_group')->where('user_id', $user->id)->pluck('group_id')->all();
        foreach (array_diff(array_map('intval', $ids), array_map('intval', $have)) as $groupId) {
            $link = new QubitAclUserGroup();
            $link->userId = $user->id;
            $link->groupId = $groupId;
            $link->save();
        }
    }
}
