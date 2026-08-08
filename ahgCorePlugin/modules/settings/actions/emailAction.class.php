<?php

/*
 * This file is part of the Access to Memory (AtoM) software.
 *
 * Access to Memory (AtoM) is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Access to Memory (AtoM) is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Access to Memory (AtoM).  If not, see <http://www.gnu.org/licenses/>.
 */

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Outgoing email (SMTP) settings.
 *
 * These live in the email_setting table rather than in AtoM's `setting`, which is
 * why this does not extend SettingsEditAction like its neighbours. There was no
 * interface for them at all: the table shipped with smtp_enabled 0 and empty
 * host, username and from address, and the only way to populate it was by hand
 * against the database.
 *
 * That mattered more than a missing admin page usually does, because password
 * reset depends on it. With SMTP unconfigured the reset flow stored a token, sent
 * nothing and told the user to check their inbox.
 *
 * The settings module is administrator-only in base AtoM's security.yml, so this
 * inherits that.
 */
class SettingsEmailAction extends sfAction
{
    public static $NAMES = [
        'smtp_enabled',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_username',
        'smtp_password',
        'smtp_from_email',
        'smtp_from_name',
    ];

    public function execute($request)
    {
        \AhgCore\Core\AhgDb::init();

        $this->settings = $this->load();

        if ($request->isMethod('post')) {
            foreach (self::$NAMES as $key) {
                $value = $request->getParameter($key);

                if ('smtp_enabled' === $key) {
                    $value = $value ? '1' : '0';
                }

                // An empty password field means "leave the stored one alone",
                // so that saving the form does not silently wipe it.
                if ('smtp_password' === $key && '' === (string) $value) {
                    continue;
                }

                $this->save($key, (string) $value);
            }

            $this->getUser()->setFlash('notice', $this->context->i18n->__('Email settings saved.'));

            $this->redirect(['module' => 'settings', 'action' => 'email']);
        }
    }

    /**
     * Current values, keyed by setting name.
     */
    protected function load(): array
    {
        $out = array_fill_keys(self::$NAMES, '');

        try {
            foreach (DB::table('email_setting')->whereIn('setting_key', self::$NAMES)->get() as $row) {
                $out[$row->setting_key] = (string) $row->setting_value;
            }
        } catch (\Throwable $e) {
            // The table belongs to this plugin and is created by its install.sql,
            // so its absence means an incomplete install rather than an optional
            // feature. Report it on the page instead of failing the request.
            $this->tableMissing = true;
        }

        return $out;
    }

    protected function save(string $key, string $value): void
    {
        try {
            $exists = DB::table('email_setting')->where('setting_key', $key)->exists();

            if ($exists) {
                DB::table('email_setting')->where('setting_key', $key)->update([
                    'setting_value' => $value,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                return;
            }

            DB::table('email_setting')->insert([
                'setting_key' => $key,
                'setting_value' => $value,
                'setting_group' => 'smtp',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            $this->getUser()->setFlash('error', $this->context->i18n->__('Could not save email settings: %1%', ['%1%' => $e->getMessage()]));
        }
    }
}
