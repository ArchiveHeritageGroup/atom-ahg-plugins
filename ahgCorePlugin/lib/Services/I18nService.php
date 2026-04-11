<?php

namespace AhgCore\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * I18n Service
 *
 * Handles internationalized (i18n) data for all AtoM entities.
 * All *_i18n tables share the same pattern: composite PK of (id, culture).
 */
class I18nService
{
    /**
     * Save (upsert) i18n data for an entity.
     *
     * @param string $table   The i18n table name (e.g. 'actor_i18n', 'term_i18n')
     * @param int    $id      The entity ID
     * @param string $culture The culture code (e.g. 'en', 'af')
     * @param array  $data    Column => value pairs to save
     */
    public static function save(string $table, int $id, string $culture, array $data): void
    {
        if (empty($data)) {
            return;
        }

        $exists = DB::table($table)
            ->where('id', $id)
            ->where('culture', $culture)
            ->exists();

        if ($exists) {
            DB::table($table)
                ->where('id', $id)
                ->where('culture', $culture)
                ->update($data);
        } else {
            DB::table($table)->insert(array_merge($data, [
                'id' => $id,
                'culture' => $culture,
            ]));
        }
    }

    /**
     * Get i18n data for an entity in a specific culture.
     */
    public static function get(string $table, int $id, string $culture): ?object
    {
        $row = DB::table($table)
            ->where('id', $id)
            ->where('culture', $culture)
            ->first();

        return $row ? self::decryptRow($row) : null;
    }

    /**
     * Get i18n data with culture fallback.
     * Tries the requested culture first, then falls back to another culture.
     */
    public static function getWithFallback(string $table, int $id, string $culture, string $fallback = 'en'): ?object
    {
        $row = self::get($table, $id, $culture);

        if ($row) {
            return $row;
        }

        if ($culture !== $fallback) {
            return self::get($table, $id, $fallback);
        }

        // Last resort: get any available culture
        $row = DB::table($table)
            ->where('id', $id)
            ->first();

        return $row ? self::decryptRow($row) : null;
    }

    /**
     * Delete all i18n rows for an entity.
     */
    public static function delete(string $table, int $id): void
    {
        DB::table($table)->where('id', $id)->delete();
    }

    /**
     * Get all cultures available for an entity.
     */
    public static function getCultures(string $table, int $id): array
    {
        return DB::table($table)
            ->where('id', $id)
            ->pluck('culture')
            ->all();
    }

    /**
     * Transparently decrypt any encrypted field values in a row.
     *
     * Encrypted values are prefixed with {AHG-ENC}. This method iterates
     * all string properties and decrypts any that carry the prefix.
     * Safe to call on unencrypted rows (no-op).
     */
    private static function decryptRow(object $row): object
    {
        static $hasService = null;

        if ($hasService === null) {
            $hasService = class_exists('\\AtomFramework\\Core\\Security\\EncryptableFieldService');
            if ($hasService) {
                $path = \sfConfig::get('sf_root_dir') . '/atom-framework/src/Core/Security/EncryptableFieldService.php';
                if (!class_exists('\\AtomFramework\\Core\\Security\\EncryptableFieldService', false) && file_exists($path)) {
                    require_once $path;
                    require_once dirname($path) . '/EncryptionService.php';
                    require_once dirname($path) . '/KeyManager.php';
                }
                $hasService = class_exists('\\AtomFramework\\Core\\Security\\EncryptableFieldService');
            }
        }

        if (!$hasService) {
            return $row;
        }

        $prefix = '{AHG-ENC}';
        foreach (get_object_vars($row) as $key => $value) {
            if (is_string($value) && str_starts_with($value, $prefix)) {
                try {
                    $row->$key = \AtomFramework\Core\Security\EncryptableFieldService::decryptValue($value);
                } catch (\Exception $e) {
                    // Leave encrypted value as-is if decryption fails
                }
            }
        }

        return $row;
    }
}
