<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * DB helper using Laravel Query Builder.
 */
class AhgTranslationDb
{
    public static function conn()
    {
        // Return PDO connection from Laravel's DB
        return DB::connection()->getPdo();
    }

    public static function fetchOne(string $sql, array $params = [])
    {
        $results = DB::select($sql, $params);

        return $results[0] ?? null;
    }

    public static function fetchAll(string $sql, array $params = [])
    {
        $results = DB::select($sql, $params);

        return array_map(function ($row) {
            return (array) $row;
        }, $results);
    }

    public static function exec(string $sql, array $params = []): int
    {
        return DB::affectingStatement($sql, $params);
    }

    public static function lastInsertId(): string
    {
        return (string) DB::connection()->getPdo()->lastInsertId();
    }
}
