<?php

namespace AhgStorageManage\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * The physical storage form as the way into the location tree.
 *
 * The box form keeps its seven location fields, Building down to Shelf. On save
 * each filled level is found beneath the one above it (name compared without
 * regard to case) or, for editors and administrators, created there. The box is
 * then placed in the innermost one through StorageMovementService, so the move
 * is logged like any other. Empty levels are skipped: Building + Room puts the
 * room straight under the building.
 *
 * The flat text columns in physical_object_extended are still saved by the form
 * as before, so reports that read them keep working.
 */
class StoragePlacementService
{
    /** The form's location fields, outermost first. Each is also a location type. */
    public const LEVELS = ['building', 'floor', 'room', 'aisle', 'bay', 'rack', 'shelf'];

    /** saveFromForm results */
    public const UNCHANGED = 'unchanged';
    public const MOVED = 'moved';
    public const NOT_ALLOWED = 'not_allowed';

    protected string $culture;

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
    }

    /** Whether the tree is installed on this instance. */
    public static function available(): bool
    {
        try {
            return DB::schema()->hasTable('ahg_storage_location')
                && DB::schema()->hasTable('ahg_physical_object_location');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * The box's current place split into the form's levels, or null when the
     * box is not placed. Locations of other types on the path (a container, a
     * storage unit) have no field and are left out.
     *
     * @return null|array<string, string> level => name
     */
    public function levelsFor(int $objectId): ?array
    {
        $locationId = (new StorageMovementService($this->culture))->currentLocationOf($objectId);
        if (null === $locationId) {
            return null;
        }

        $levels = [];
        foreach ((new StorageLocationService($this->culture))->getLocationPath($locationId) as $row) {
            if (in_array($row['location_type'], self::LEVELS, true)) {
                $levels[$row['location_type']] = (string) $row['name'];
            }
        }

        return $levels;
    }

    /**
     * The innermost location the levels describe.
     *
     * @param array<string, mixed> $levels    level => name, as posted
     * @param bool                 $mayCreate create missing levels, or give up
     *
     * @return null|false|int null when no level is filled; false when a level is
     *                        missing and may not be created
     */
    public function resolve(array $levels, bool $mayCreate)
    {
        $locations = new StorageLocationService($this->culture);
        $parentId = null;

        foreach (self::LEVELS as $type) {
            $name = trim((string) ($levels[$type] ?? ''));
            if ('' === $name) {
                continue;
            }

            $query = DB::table('ahg_storage_location')
                ->where('location_type', $type)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)]);
            null === $parentId ? $query->whereNull('parent_id') : $query->where('parent_id', $parentId);
            $id = $query->orderBy('id')->value('id');

            if (null === $id) {
                if (!$mayCreate) {
                    return false;
                }
                $id = $locations->createLocation(['name' => $name, 'location_type' => $type, 'parent_id' => $parentId])['id'];
            }

            $parentId = (int) $id;
        }

        return $parentId;
    }

    /**
     * Place a box from the form's posted levels.
     *
     * Nothing happens when the posted levels match where the box already is,
     * which also keeps a box that sits in a container below its shelf from
     * being pulled up to the shelf on an unrelated save.
     *
     * @param array $context user_id, username for the movement log
     *
     * @return string one of UNCHANGED, MOVED, NOT_ALLOWED
     */
    public function saveFromForm(int $objectId, array $levels, bool $mayCreate, array $context = []): string
    {
        $posted = self::normalise($levels);
        if ($posted === self::normalise($this->levelsFor($objectId) ?? [])) {
            return self::UNCHANGED;
        }

        return DB::connection()->transaction(function () use ($objectId, $levels, $mayCreate, $context) {
            $target = $this->resolve($levels, $mayCreate);
            if (false === $target) {
                return self::NOT_ALLOWED;
            }

            $movements = new StorageMovementService($this->culture);
            if ($movements->currentLocationOf($objectId) === $target) {
                return self::UNCHANGED;
            }

            $movements->moveObject($objectId, $target, $context + ['note' => 'Physical storage form']);

            return self::MOVED;
        });
    }

    /**
     * For the physical storage form's save, which exists in two copies (the
     * theme's and ahgDisplayPlugin's physicalobject actions): place the box and
     * say what to tell the user, or null for nothing. Never throws: the box has
     * already been saved, and a placement failure must not lose that.
     */
    public static function fromEditForm(int $objectId, array $posted, $user): ?array
    {
        if (!self::available()) {
            return null;
        }

        try {
            $result = (new self())->saveFromForm(
                $objectId,
                array_intersect_key($posted, array_flip(self::LEVELS)),
                $user->hasCredential(['administrator', 'editor'], false)
            );
        } catch (\Throwable $e) {
            error_log('storage.placement_failed: '.$e->getMessage());

            return ['error', 'Saved, but the box could not be placed in the storage location tree: %1%', $e->getMessage()];
        }

        if (self::NOT_ALLOWED === $result) {
            return ['error', 'Saved, but the location was not changed: only editors and administrators can add new places. Choose existing places from the suggestions.', ''];
        }

        return null;
    }

    /** The form's prefill from the tree, or null to use the flat fields. Never throws. */
    public static function prefill(int $objectId): ?array
    {
        try {
            return self::available() ? (new self())->levelsFor($objectId) : null;
        } catch (\Throwable $e) {
            error_log('storage.placement_prefill_failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * For the box's own page: its place as a path, outermost first, and its
     * latest moves. Null when the tree is not installed. Never throws.
     *
     * @return null|array{path: array, moves: array}
     */
    public static function placementView(int $objectId, int $moves = 5): ?array
    {
        try {
            if (!self::available()) {
                return null;
            }
            $movement = new StorageMovementService();
            $locationId = $movement->currentLocationOf($objectId);

            return [
                'path' => null === $locationId ? [] : (new StorageLocationService())->getLocationPath($locationId),
                'moves' => array_slice($movement->historyFor(StorageMovementService::SUBJECT_OBJECT, $objectId), 0, $moves),
            ];
        } catch (\Throwable $e) {
            error_log('storage.placement_view_failed: '.$e->getMessage());

            return null;
        }
    }

    /** level => lower-cased trimmed name, empty levels dropped, in level order. */
    protected static function normalise(array $levels): array
    {
        $out = [];
        foreach (self::LEVELS as $type) {
            $name = mb_strtolower(trim((string) ($levels[$type] ?? '')));
            if ('' !== $name) {
                $out[$type] = $name;
            }
        }

        return $out;
    }
}
