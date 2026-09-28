<?php

namespace AhgStorageManage\Services;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Hierarchical storage locations: building, floor, room, shelf, and so on down.
 *
 * An adjacency list (parent_id), not a nested set. A storage tree is small and
 * changes rarely, so the whole table is read once and the tree assembled in
 * memory. That is one query per page instead of one per node, and it avoids
 * carrying lft/rgt bookkeeping for a few hundred rows.
 *
 * `level` is denormalised depth, kept for ordering. It is recomputed for a
 * location AND its descendants whenever a parent changes, because a stale level
 * silently reorders the tree and nothing would report it.
 *
 * Reads do not swallow database errors. A missing table or column must surface as
 * a failure, not as an empty screen that looks like an empty archive.
 */
class StorageLocationService
{
    /** Depth ceiling, so a cycle in the data cannot spin a request forever. */
    public const MAX_DEPTH = 100;

    /** The values location_type accepts; mirrored in the column COMMENT. */
    public const TYPES = ['building', 'floor', 'room', 'aisle', 'bay', 'rack', 'shelf', 'container', 'storage_unit'];

    protected string $culture;

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
    }

    /**
     * Create a location beneath an optional parent.
     *
     * @return null|array the created location
     */
    public function createLocation(array $data): ?array
    {
        $validated = $this->validateLocationData($data);

        $validated['level'] = $this->levelFor($validated['parent_id'] ?? null);
        $validated['created_at'] = date('Y-m-d H:i:s');
        $validated['updated_at'] = date('Y-m-d H:i:s');

        return DB::connection()->transaction(function () use ($validated) {
            $id = DB::table('ahg_storage_location')->insertGetId($validated);

            return $this->getLocationById((int) $id);
        });
    }

    /**
     * Update a location. Fields left out of $data are left alone.
     *
     * @return null|array the updated location
     */
    public function updateLocation(int $id, array $data): ?array
    {
        if (null === $this->getLocationById($id)) {
            throw new Exception('Storage location not found');
        }

        // The id has to reach validation, or the slug uniqueness check matches the
        // row being edited and every update fails as a duplicate.
        $validated = $this->validateLocationData($data, $id);

        $reparented = array_key_exists('parent_id', $validated);

        if ($reparented) {
            if (null !== $validated['parent_id'] && $validated['parent_id'] === $id) {
                throw new Exception('A location cannot be its own parent');
            }

            $this->validateNoCircularReference($id, $validated['parent_id']);
            $validated['level'] = $this->levelFor($validated['parent_id']);
        }

        $validated['updated_at'] = date('Y-m-d H:i:s');

        return DB::connection()->transaction(function () use ($id, $validated, $reparented) {
            DB::table('ahg_storage_location')->where('id', $id)->update($validated);

            if ($reparented) {
                $this->recomputeDescendantLevels($id, (int) $validated['level']);
            }

            return $this->getLocationById($id);
        });
    }

    /**
     * Delete a location. A location with children is refused rather than having
     * its children silently promoted to the root by the foreign key.
     */
    public function deleteLocation(int $id): bool
    {
        $hasChildren = DB::table('ahg_storage_location')->where('parent_id', $id)->exists();

        if ($hasChildren) {
            throw new Exception('Cannot delete a location that has children. Delete or move the children first.');
        }

        return (bool) DB::table('ahg_storage_location')->where('id', $id)->delete();
    }

    /** One location, or null when there is no such row. */
    public function getLocationById(int $id): ?array
    {
        $location = DB::table('ahg_storage_location')->where('id', $id)->first();

        return $location ? (array) $location : null;
    }

    /** A flat list, filtered by search text, type or parent. */
    public function getLocations(array $params = []): array
    {
        $query = DB::table('ahg_storage_location')
            ->orderBy('level', 'asc')
            ->orderBy('name', 'asc');

        if (!empty($params['search'])) {
            $search = $this->likeTerm($params['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', $search)->orWhere('description', 'LIKE', $search);
            });
        }

        if (!empty($params['type'])) {
            $query->where('location_type', $params['type']);
        }

        if (array_key_exists('parent_id', $params)) {
            null === $params['parent_id']
                ? $query->whereNull('parent_id')
                : $query->where('parent_id', (int) $params['parent_id']);
        }

        return array_map(static function ($row) { return (array) $row; }, $query->get()->all());
    }

    /**
     * The tree below $parentId, or the whole tree from the roots.
     *
     * One query for the table, assembled in memory. The previous version issued a
     * query per node.
     */
    public function getLocationTree(?int $parentId = null): array
    {
        $byParent = [];

        foreach ($this->allOrdered() as $row) {
            $byParent[null === $row['parent_id'] ? 0 : (int) $row['parent_id']][] = $row;
        }

        return $this->buildBranch($byParent, null === $parentId ? 0 : $parentId, 0);
    }

    /** Root to leaf, the ancestors of $id and $id itself. */
    public function getLocationPath(int $id): array
    {
        $path = [];
        $seen = [];
        $currentId = $id;

        while (null !== $currentId) {
            if (isset($seen[$currentId]) || count($path) >= self::MAX_DEPTH) {
                break;   // the data has a cycle; return what is certain rather than spin
            }
            $seen[$currentId] = true;

            $location = $this->getLocationById((int) $currentId);

            if (null === $location) {
                break;
            }

            $path[] = $location;
            $currentId = $location['parent_id'];
        }

        return array_reverse($path);
    }

    /** Every location below $id, at any depth. */
    public function getDescendants(int $id): array
    {
        $byParent = [];

        foreach ($this->allOrdered() as $row) {
            $byParent[null === $row['parent_id'] ? 0 : (int) $row['parent_id']][] = $row;
        }

        $out = [];
        $queue = [$id];
        $seen = [];

        while ($queue) {
            $parent = array_shift($queue);

            if (isset($seen[$parent])) {
                continue;
            }
            $seen[$parent] = true;

            foreach ($byParent[$parent] ?? [] as $child) {
                $out[] = $child;
                $queue[] = (int) $child['id'];
            }
        }

        return $out;
    }

    /** The children of $parentId, or the root locations. */
    public function getChildren(?int $parentId = null): array
    {
        return $this->getLocations(['parent_id' => $parentId]);
    }

    /** The whole tree, roots first, each node carrying its children. */
    public function getAllLocationsWithHierarchy(): array
    {
        return $this->getLocationTree(null);
    }

    /** Locations matching text, flat, for a search box. */
    public function searchLocations(string $query): array
    {
        $search = trim($query);

        if ('' === $search) {
            return [];
        }

        return $this->getLocations(['search' => $search]);
    }

    /**
     * Validate input. On an update ($id given) only the fields present are
     * validated, so renaming a location does not demand its type again.
     */
    protected function validateLocationData(array $data, ?int $id = null): array
    {
        $creating = null === $id;
        $validated = [];

        if ($creating || array_key_exists('name', $data)) {
            if (empty($data['name'])) {
                throw new Exception('Location name is required');
            }

            $name = trim($data['name']);

            if (mb_strlen($name) > 255) {
                throw new Exception('Location name is longer than 255 characters');
            }

            $validated['name'] = $name;
        }

        if ($creating || array_key_exists('location_type', $data)) {
            if (empty($data['location_type'])) {
                throw new Exception('Location type is required');
            }

            if (!in_array($data['location_type'], self::TYPES, true)) {
                throw new Exception('Invalid location type');
            }

            $validated['location_type'] = $data['location_type'];
        }

        // A slug the caller supplied must be honoured or refused; one we generate
        // may be adjusted, because two rooms called "Room 1" in different buildings
        // is ordinary and must not be an error.
        if (!empty($data['slug'])) {
            $validated['slug'] = $this->uniqueSlug($this->slugify($data['slug']), $id, false);
        } elseif (isset($validated['name'])) {
            $validated['slug'] = $this->uniqueSlug($this->slugify($validated['name']), $id, true);
        }

        // An explicit empty parent means "move to the root". Only a missing key
        // means "leave the parent alone".
        if (array_key_exists('parent_id', $data)) {
            $validated['parent_id'] = empty($data['parent_id']) ? null : (int) $data['parent_id'];
        }

        foreach (['description', 'notes', 'capacity_unit'] as $field) {
            if (array_key_exists($field, $data)) {
                $validated[$field] = null === $data[$field] ? null : trim((string) $data[$field]);
            }
        }

        if (array_key_exists('capacity_value', $data)) {
            $value = $data['capacity_value'];

            if (null === $value || '' === $value) {
                $validated['capacity_value'] = null;
            } elseif (!is_numeric($value)) {
                throw new Exception('Capacity must be a number');
            } else {
                $validated['capacity_value'] = (float) $value;
            }
        }

        return $validated;
    }

    /** A slug from a name: accents folded, punctuation collapsed, hyphens tidied. */
    protected function slugify(string $name): string
    {
        $slug = $name;

        if (function_exists('transliterator_transliterate')) {
            $slug = transliterator_transliterate('Any-Latin; Latin-ASCII', $slug) ?: $slug;
        } elseif (function_exists('iconv')) {
            $slug = @iconv('UTF-8', 'ASCII//TRANSLIT', $slug) ?: $slug;
        }

        $slug = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', $slug));
        $slug = trim($slug, '-');
        $slug = substr($slug, 0, 240);

        // A name of only non-Latin characters folds away to nothing, and an empty
        // slug would collide with the next such name.
        return '' === $slug ? 'location-'.substr(md5($name.microtime()), 0, 8) : $slug;
    }

    /**
     * @param bool $adjust append a discriminator on collision instead of refusing
     */
    protected function uniqueSlug(string $slug, ?int $id, bool $adjust): string
    {
        $exists = function (string $candidate) use ($id) {
            $query = DB::table('ahg_storage_location')->where('slug', $candidate);

            if (null !== $id) {
                $query->where('id', '!=', $id);
            }

            return $query->exists();
        };

        if (!$exists($slug)) {
            return $slug;
        }

        if (!$adjust) {
            throw new Exception('A location with this slug already exists');
        }

        for ($n = 2; $n < 1000; ++$n) {
            $candidate = $slug.'-'.$n;

            if (!$exists($candidate)) {
                return $candidate;
            }
        }

        return $slug.'-'.substr(md5($slug.microtime()), 0, 8);
    }

    /** Depth of a location under $parentId: zero at the root. */
    protected function levelFor(?int $parentId): int
    {
        if (null === $parentId) {
            return 0;
        }

        $parent = $this->getLocationById($parentId);

        if (null === $parent) {
            throw new Exception('Parent location not found');
        }

        return (int) $parent['level'] + 1;
    }

    /** After a move, the whole subtree sits at a new depth. */
    protected function recomputeDescendantLevels(int $id, int $level): void
    {
        $children = DB::table('ahg_storage_location')->where('parent_id', $id)->pluck('id');

        foreach ($children as $childId) {
            DB::table('ahg_storage_location')->where('id', $childId)->update(['level' => $level + 1]);
            $this->recomputeDescendantLevels((int) $childId, $level + 1);
        }
    }

    /**
     * Refuse a move that would put a location inside its own subtree.
     *
     * Tracks where it has been: without that, a cycle already in the data hangs
     * the very function whose job is to prevent cycles.
     */
    protected function validateNoCircularReference(int $id, ?int $parentId): bool
    {
        $currentId = $parentId;
        $seen = [];

        while (null !== $currentId) {
            if ((int) $currentId === $id) {
                throw new Exception('Cannot set a location as its own ancestor');
            }

            if (isset($seen[$currentId]) || count($seen) >= self::MAX_DEPTH) {
                break;
            }
            $seen[$currentId] = true;

            $parent = DB::table('ahg_storage_location')->where('id', $currentId)->first();

            if (!$parent) {
                break;
            }

            $currentId = $parent->parent_id;
        }

        return true;
    }

    /** The table once, ordered, as plain arrays. */
    protected function allOrdered(): array
    {
        return array_map(
            static function ($row) { return (array) $row; },
            DB::table('ahg_storage_location')->orderBy('level', 'asc')->orderBy('name', 'asc')->get()->all()
        );
    }

    /** Assemble one branch from rows already grouped by parent. */
    protected function buildBranch(array $byParent, int $parentKey, int $depth): array
    {
        if ($depth >= self::MAX_DEPTH) {
            return [];   // cyclic data: stop rather than recurse forever
        }

        $branch = [];

        foreach ($byParent[$parentKey] ?? [] as $node) {
            $children = $this->buildBranch($byParent, (int) $node['id'], $depth + 1);

            if ($children) {
                $node['children'] = $children;
            }

            $branch[] = $node;
        }

        return $branch;
    }

    /** Escape the wildcards, so a search for "50%" does not match everything. */
    protected function likeTerm(string $search): string
    {
        return '%'.addcslashes(trim($search), '%_\\').'%';
    }
}
