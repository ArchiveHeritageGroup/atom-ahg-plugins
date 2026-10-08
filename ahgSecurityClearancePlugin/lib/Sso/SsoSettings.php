<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Single sign-on settings (#200), kept in ahg_settings under group "sso".
 * The OIDC client secret is stored there too and is never shown back in full.
 */
class SsoSettings
{
    public const GROUP = 'sso';

    public const DEFAULTS = [
        'sso_oidc_enabled' => 'false',
        'sso_oidc_label' => 'Google',
        'sso_oidc_issuer' => 'https://accounts.google.com',
        'sso_oidc_client_id' => '',
        'sso_oidc_client_secret' => '',
        'sso_oidc_allowed_domains' => '',
        'sso_saml_enabled' => 'false',
        'sso_saml_label' => 'Institution sign-in (SAML)',
        'sso_saml_idp_entity_id' => '',
        'sso_saml_idp_sso_url' => '',
        'sso_saml_idp_x509cert' => '',
        'sso_saml_email_attribute' => 'urn:oid:0.9.2342.19200300.100.1.3',
        'sso_saml_name_attribute' => 'urn:oid:2.16.840.1.113730.3.1.241',
        'sso_saml_groups_attribute' => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.7',
        'sso_jit_create' => 'true',
        'sso_default_group' => '',
        'sso_group_map' => '',
        'sso_oidc_groups_claim' => 'groups',
    ];

    private static ?array $values = null;

    public static function all(): array
    {
        if (null === self::$values) {
            $stored = [];
            try {
                $stored = DB::table('ahg_settings')->where('setting_group', self::GROUP)->pluck('setting_value', 'setting_key')->all();
            } catch (\Throwable $e) {
                $stored = [];
            }
            self::$values = array_merge(self::DEFAULTS, array_intersect_key($stored, self::DEFAULTS));
        }

        return self::$values;
    }

    /** Use these values instead of the database (for testing/sso-check.php). */
    public static function useValues(array $values): void
    {
        self::$values = array_merge(self::DEFAULTS, $values);
    }

    public static function get(string $key): string
    {
        return (string) (self::all()[$key] ?? '');
    }

    public static function on(string $key): bool
    {
        return 'true' === self::get($key);
    }

    public static function oidcReady(): bool
    {
        return self::on('sso_oidc_enabled') && '' !== self::get('sso_oidc_client_id') && '' !== self::get('sso_oidc_client_secret');
    }

    public static function samlReady(): bool
    {
        return self::on('sso_saml_enabled') && class_exists('\\OneLogin\\Saml2\\Auth')
            && '' !== self::get('sso_saml_idp_sso_url') && '' !== self::get('sso_saml_idp_x509cert');
    }

    /** Save posted values; an empty secret field keeps the stored secret. */
    public static function save(array $posted): void
    {
        foreach (self::DEFAULTS as $key => $default) {
            if (!array_key_exists($key, $posted) && !str_ends_with($key, '_enabled') && 'sso_jit_create' !== $key) {
                continue;
            }
            $value = $posted[$key] ?? 'false';
            if ('sso_oidc_client_secret' === $key && '' === trim((string) $value)) {
                continue;
            }
            if (str_ends_with($key, '_enabled') || 'sso_jit_create' === $key) {
                $value = in_array($value, ['true', '1', 'on'], true) ? 'true' : 'false';
            }
            DB::table('ahg_settings')->updateOrInsert(
                ['setting_key' => $key],
                ['setting_value' => trim((string) $value), 'setting_group' => self::GROUP, 'updated_at' => DB::raw('NOW()')]
            );
        }
        self::$values = null;
    }

    /**
     * "idp-group = atom-group" lines as a map. AtoM groups are named by their
     * English name: administrator, editor, contributor, translator.
     *
     * @return array<string, string>
     */
    public static function groupMap(): array
    {
        $map = [];
        foreach (preg_split('/\R/', self::get('sso_group_map')) as $line) {
            if (false !== strpos($line, '=')) {
                [$idp, $atom] = array_map('trim', explode('=', $line, 2));
                if ('' !== $idp && '' !== $atom) {
                    $map[$idp] = strtolower($atom);
                }
            }
        }

        return $map;
    }
}
