<?php

namespace AhgCore\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Object Service
 *
 * Manages the base `object` and `slug` tables for all AtoM entities.
 * Every entity (actor, term, accession, user, etc.) has a row in `object`
 * and a corresponding row in `slug`.
 */
class ObjectService
{
    /**
     * Create a new object record and return the auto-increment ID.
     */
    public static function create(string $className): int
    {
        $now = date('Y-m-d H:i:s');

        return DB::table('object')->insertGetId([
            'class_name' => $className,
            'created_at' => $now,
            'updated_at' => $now,
            'serial_number' => 0,
        ]);
    }

    /**
     * Generate a unique slug for an object.
     *
     * @param int         $objectId The object ID to link the slug to
     * @param string|null $basis    Optional text to slugify (e.g. name/title). If null, generates random slug.
     *
     * @return string The generated slug
     */
    public static function generateSlug(int $objectId, ?string $basis = null): string
    {
        // Remove any existing slug for this object
        DB::table('slug')->where('object_id', $objectId)->delete();

        if ($basis && trim($basis) !== '') {
            $slug = self::slugify($basis);
        } else {
            $slug = self::randomSlug();
        }

        // Ensure uniqueness
        $slug = self::ensureUnique($slug);

        DB::table('slug')->insert([
            'object_id' => $objectId,
            'slug' => $slug,
            'serial_number' => 0,
        ]);

        return $slug;
    }

    /**
     * Get the slug for an object ID.
     */
    public static function getSlug(int $objectId): ?string
    {
        return DB::table('slug')
            ->where('object_id', $objectId)
            ->value('slug');
    }

    /**
     * Resolve a slug to an object ID.
     */
    public static function resolveSlug(string $slug): ?int
    {
        return DB::table('slug')
            ->where('slug', $slug)
            ->value('object_id');
    }

    /**
     * Delete an object and its slug.
     */
    public static function deleteObject(int $id): void
    {
        DB::table('slug')->where('object_id', $id)->delete();
        DB::table('object')->where('id', $id)->delete();
    }

    /**
     * Increment the serial number (optimistic locking).
     */
    public static function incrementSerialNumber(int $id): void
    {
        DB::table('object')
            ->where('id', $id)
            ->increment('serial_number');
    }

    /**
     * Touch the updated_at timestamp.
     */
    public static function touch(int $id): void
    {
        DB::table('object')
            ->where('id', $id)
            ->update(['updated_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Get object class name.
     */
    public static function getClassName(int $id): ?string
    {
        return DB::table('object')
            ->where('id', $id)
            ->value('class_name');
    }

    /**
     * Convert text to a URL-friendly slug.
     */
    protected static function slugify(string $text): string
    {
        // Transliterate to ASCII
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        // Lowercase
        $slug = strtolower($slug);
        // Replace non-alphanumeric with hyphens
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        // Trim hyphens
        $slug = trim($slug, '-');
        // Collapse multiple hyphens
        $slug = preg_replace('/-+/', '-', $slug);
        // Limit length
        $slug = substr($slug, 0, 200);

        return $slug ?: self::randomSlug();
    }

    /**
     * Generate a random 12-character alphanumeric slug.
     */
    protected static function randomSlug(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $slug = '';
        for ($i = 0; $i < 12; $i++) {
            $slug .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $slug;
    }

    /**
     * Ensure a slug is unique by appending -2, -3, etc.
     */
    protected static function ensureUnique(string $slug): string
    {
        $original = $slug;
        $counter = 2;

        while (DB::table('slug')->where('slug', $slug)->exists()) {
            $slug = $original . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
