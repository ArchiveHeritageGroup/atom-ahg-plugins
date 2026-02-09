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
        return DB::table($table)
            ->where('id', $id)
            ->where('culture', $culture)
            ->first();
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
        return DB::table($table)
            ->where('id', $id)
            ->first();
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
}
