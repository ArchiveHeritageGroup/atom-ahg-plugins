<?php

namespace AhgStorageManage\Services;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Hierarchical storage locations: building, floor, room, shelf, and so on down.
 *
 * parent_id is the source of truth. ahg_storage_location_closure is a maintained
 * index of every (ancestor, descendant, depth) pair, the same shape as Heratio's
 * closure tables (heratio#1333, atom-ahg-plugins#193), so a subtree, a path or a
 * cycle check is one indexed query instead of a parent walk. Every write that
 * changes the tree updates the closure inside the same transaction as the row.
 * rebuildClosure() re-derives it from parent_id if it is ever in doubt.
 *
 * Trees are still assembled in memory from one query: the whole table for the
 * full tree, or just the subtree rows from the closure for a branch.
 *
 * `level` is denormalised depth, kept for ordering. After a move the subtree's
 * levels are reset in one UPDATE joined through the closure.
 *
 * Reads do not swallow database errors. A missing table or column must surface as
 * a failure, not as an empty screen that looks like an empty archive.
 */
class StorageLocationService
{
    /** Depth ceiling for tree assembly and the closure rebuild. */
    public const MAX_DEPTH = 100;

    /**
     * The location types this plugin ships with. The list an archive actually
     * uses is the Dropdown Manager taxonomy below, where it can be added to and
     * reordered; this is what types() falls back to when that list is not there.
     */
    public const TYPES = ['building', 'floor', 'room', 'aisle', 'bay', 'rack', 'shelf', 'container', 'storage_unit'];

    /** The ahg_dropdown taxonomy that holds the location types. */
    public const TYPE_TAXONOMY = 'storage_location_type';

    protected string $culture;

    /** @var null|array<string, string> code => label, read once per instance */
    protected ?array $types = null;

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
            $id = (int) DB::table('ahg_storage_location')->insertGetId($validated);
            $this->closureAddNode($id, $validated['parent_id'] ?? null);

            return $this->getLocationById($id);
        });
    }

    /**
     * Update a location. Fields left out of $data are left alone.
     *
     * @return null|array the updated location
     */
    public function updateLocation(int $id, array $data): ?array
    {
        $current = $this->getLocationById($id);

        if (null === $current) {
            throw new Exception('Storage location not found');
        }

        // The id has to reach validation, or the slug uniqueness check matches the
        // row being edited and every update fails as a duplicate.
        $validated = $this->validateLocationData($data, $id);

        $currentParent = null === $current['parent_id'] ? null : (int) $current['parent_id'];
        $reparented = array_key_exists('parent_id', $validated) && $validated['parent_id'] !== $currentParent;

        if (array_key_exists('parent_id', $validated) && !$reparented) {
            unset($validated['parent_id']);   // unchanged: no closure work, no level reset
        }

        if ($reparented) {
            if (null !== $validated['parent_id'] && $validated['parent_id'] === $id) {
                throw new Exception('A location cannot be its own parent');
            }

            $this->validateNoCircularReference($id, $validated['parent_id']);
            $validated['level'] = $this->levelFor($validated['parent_id']);
        }

        $validated['updated_at'] = date('Y-m-d H:i:s');

        return DB::connection()->transaction(function () use ($id, $validated, $reparented, $currentParent) {
            DB::table('ahg_storage_location')->where('id', $id)->update($validated);

            if ($reparented) {
                $this->closureMoveNode($id, $validated['parent_id']);
                $this->recomputeDescendantLevels($id, (int) $validated['level']);

                // One event for the thing that moved. What sat under it is a
                // closure query, so fanning this out over every object beneath
                // would duplicate the hierarchy and go stale as soon as it changed.
                (new StorageMovementService($this->culture))
                    ->recordLocationMove($id, $currentParent, $validated['parent_id']);
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

        $holdsObjects = DB::table('ahg_physical_object_location')->where('location_id', $id)->exists();

        if ($holdsObjects) {
            throw new Exception('Cannot delete a location that still holds physical objects. Move them out first.');
        }

        // The movement log references locations with RESTRICT, so that deleting a
        // location cannot erase the record of what passed through it. Without this
        // check the operator would see a driver error instead of the reason.
        $hasHistory = DB::table('ahg_storage_movement')
            ->where(function ($q) use ($id) {
                $q->where('from_location_id', $id)->orWhere('to_location_id', $id);
            })
            ->exists();

        if ($hasHistory) {
            throw new Exception('Cannot delete a location that appears in the movement log. Its history would go with it.');
        }

        return DB::connection()->transaction(function () use ($id) {
            // The foreign keys cascade these away too; clearing them first keeps
            // the closure right even where FK checks are off (bulk loads).
            DB::table('ahg_storage_location_closure')->where('descendant', $id)->delete();

            return (bool) DB::table('ahg_storage_location')->where('id', $id)->delete();
        });
    }

    /**
     * The location types, code => label, in the order the archive set.
     *
     * Read from the Dropdown Manager (ahg_dropdown, taxonomy
     * storage_location_type), so a type can be added, renamed or retired without
     * a release. Falls back to the shipped list when the table is not there or
     * the taxonomy is empty: a location form with no types in it cannot save
     * anything, and that is worse than an out-of-date list.
     *
     * A type switched off in the Dropdown Manager is no longer offered, and
     * locations that already carry it keep it.
     */
    public function types(): array
    {
        if (null !== $this->types) {
            return $this->types;
        }

        $types = [];

        if (DB::schema()->hasTable('ahg_dropdown')) {
            $rows = DB::table('ahg_dropdown')
                ->where('taxonomy', self::TYPE_TAXONOMY)
                ->where('is_active', 1)
                ->orderBy('sort_order')
                ->orderBy('label')
                ->get(['code', 'label'])->all();

            foreach ($rows as $row) {
                $types[(string) $row->code] = (string) $row->label;
            }
        }

        if (!$types) {
            foreach (self::TYPES as $code) {
                $types[$code] = ucfirst(str_replace('_', ' ', $code));
            }
        }

        return $this->types = $types;
    }

    /** The label for a type code, or the code itself for one no longer listed. */
    public function typeLabel(?string $code): string
    {
        if (null === $code || '' === $code) {
            return '';
        }

        return $this->types()[$code] ?? ucfirst(str_replace('_', ' ', $code));
    }

    /**
     * What a location can hold, and what it does hold, counted down the tree.
     *
     * Capacity is whatever was declared on each location, in whatever unit, so
     * it is summed per unit and never across units: forty boxes and twelve
     * linear metres are not fifty-two of anything. A location's own figure and
     * the sum of what is declared beneath it are reported apart, because an
     * archive may declare capacity on the room, on its shelves, or on both, and
     * adding the two would count the same space twice.
     *
     * Occupancy is a count of physical objects. That is the one thing the
     * movement log knows for certain; it does not know how many linear metres a
     * box takes up.
     *
     * @return array own, beneath (unit => total), declared_beneath,
     *               objects_here, objects_beneath, objects_total
     */
    public function capacityRollup(int $id): array
    {
        $own = $this->getLocationById($id);

        if (null === $own) {
            throw new Exception('Storage location not found');
        }

        $beneath = [];
        $declared = 0;

        $rows = DB::table('ahg_storage_location_closure as c')
            ->join('ahg_storage_location as l', 'l.id', '=', 'c.descendant')
            ->where('c.ancestor', $id)
            ->where('c.depth', '>', 0)
            ->whereNotNull('l.capacity_value')
            ->groupBy('l.capacity_unit')
            ->orderBy('l.capacity_unit')
            ->selectRaw('l.capacity_unit as unit, SUM(l.capacity_value) as total, COUNT(*) as locations')
            ->get()->all();

        foreach ($rows as $row) {
            $beneath[(string) ($row->unit ?? '')] = (float) $row->total;
            $declared += (int) $row->locations;
        }

        $here = (int) DB::table('ahg_physical_object_location')->where('location_id', $id)->count();

        $total = (int) DB::table('ahg_storage_location_closure as c')
            ->join('ahg_physical_object_location as pol', 'pol.location_id', '=', 'c.descendant')
            ->where('c.ancestor', $id)
            ->count();

        return [
            'own' => null === $own['capacity_value'] ? null : [
                'value' => (float) $own['capacity_value'],
                'unit' => (string) ($own['capacity_unit'] ?? ''),
            ],
            'beneath' => $beneath,
            'declared_beneath' => $declared,
            'objects_here' => $here,
            'objects_beneath' => $total - $here,
            'objects_total' => $total,
        ];
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
        $rows = null === $parentId ? $this->allOrdered() : $this->subtreeOrdered($parentId, false);

        foreach ($rows as $row) {
            $byParent[null === $row['parent_id'] ? 0 : (int) $row['parent_id']][] = $row;
        }

        return $this->buildBranch($byParent, null === $parentId ? 0 : $parentId, 0);
    }

    /** Root to leaf, the ancestors of $id and $id itself. One query. */
    public function getLocationPath(int $id): array
    {
        return array_map(
            static function ($row) { return (array) $row; },
            DB::table('ahg_storage_location_closure as c')
                ->join('ahg_storage_location as l', 'l.id', '=', 'c.ancestor')
                ->where('c.descendant', $id)
                ->orderBy('c.depth', 'desc')
                ->select('l.*')
                ->get()->all()
        );
    }

    /** Every location below $id, at any depth, shallowest first. */
    public function getDescendants(int $id): array
    {
        return $this->subtreeOrdered($id, false);
    }

    /**
     * Re-derive the closure from parent_id, depth by depth. Use after a bulk
     * load or a hand edit; normal writes keep it in step on their own.
     *
     * @return int closure rows written
     */
    public function rebuildClosure(): int
    {
        return DB::connection()->transaction(function () {
            DB::table('ahg_storage_location_closure')->delete();
            DB::statement('INSERT INTO ahg_storage_location_closure (ancestor, descendant, depth)
                SELECT id, id, 0 FROM ahg_storage_location');

            for ($depth = 0; $depth < self::MAX_DEPTH; ++$depth) {
                $added = DB::affectingStatement(
                    'INSERT IGNORE INTO ahg_storage_location_closure (ancestor, descendant, depth)
                     SELECT c.ancestor, l.id, c.depth + 1
                     FROM ahg_storage_location_closure c
                     JOIN ahg_storage_location l ON l.parent_id = c.descendant
                     WHERE c.depth = ?',
                    [$depth]
                );

                if (0 === $added) {
                    break;
                }
            }

            return (int) DB::table('ahg_storage_location_closure')->count();
        });
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

            // A location being edited may keep a type that has since been
            // retired from the list; it may not be given one.
            $keeping = !$creating && ($this->getLocationById($id)['location_type'] ?? null) === $data['location_type'];

            if (!$keeping && !array_key_exists((string) $data['location_type'], $this->types())) {
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

    /** After a move, the whole subtree sits at a new depth: one UPDATE. */
    protected function recomputeDescendantLevels(int $id, int $level): void
    {
        DB::update(
            'UPDATE ahg_storage_location l
             JOIN ahg_storage_location_closure c ON c.descendant = l.id AND c.ancestor = ?
             SET l.level = ? + c.depth',
            [$id, $level]
        );
    }

    /** A new, childless node: its self row plus one row per ancestor of the parent. */
    protected function closureAddNode(int $id, ?int $parentId): void
    {
        DB::table('ahg_storage_location_closure')->where('descendant', $id)->delete();
        DB::table('ahg_storage_location_closure')->insert(['ancestor' => $id, 'descendant' => $id, 'depth' => 0]);

        if (null !== $parentId) {
            DB::insert(
                'INSERT INTO ahg_storage_location_closure (ancestor, descendant, depth)
                 SELECT ancestor, ?, depth + 1 FROM ahg_storage_location_closure WHERE descendant = ?',
                [$id, $parentId]
            );
        }
    }

    /**
     * Move a node and its whole subtree under $newParentId (null: to the root).
     * Detach the subtree from the node's old ancestors, then attach it to every
     * ancestor of the new parent. The subtree's internal rows are untouched.
     */
    protected function closureMoveNode(int $id, ?int $newParentId): void
    {
        DB::delete(
            'DELETE FROM ahg_storage_location_closure
             WHERE descendant IN (SELECT d FROM (SELECT descendant AS d FROM ahg_storage_location_closure WHERE ancestor = ?) AS sub)
               AND ancestor   IN (SELECT a FROM (SELECT ancestor AS a FROM ahg_storage_location_closure WHERE descendant = ? AND ancestor <> ?) AS sup)',
            [$id, $id, $id]
        );

        if (null !== $newParentId) {
            DB::insert(
                'INSERT INTO ahg_storage_location_closure (ancestor, descendant, depth)
                 SELECT super.ancestor, sub.descendant, super.depth + sub.depth + 1
                 FROM ahg_storage_location_closure super
                 JOIN ahg_storage_location_closure sub ON sub.ancestor = ?
                 WHERE super.descendant = ?',
                [$id, $newParentId]
            );
        }
    }

    /**
     * Refuse a move that would put a location inside its own subtree: the new
     * parent must not be $id or any descendant of it. One lookup in the closure.
     */
    protected function validateNoCircularReference(int $id, ?int $parentId): bool
    {
        if (null === $parentId) {
            return true;
        }

        $insideOwnSubtree = DB::table('ahg_storage_location_closure')
            ->where('ancestor', $id)
            ->where('descendant', $parentId)
            ->exists();

        if ($insideOwnSubtree) {
            throw new Exception('Cannot set a location as its own ancestor');
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

    /** The subtree under $id from the closure, ordered like allOrdered(). */
    protected function subtreeOrdered(int $id, bool $includeSelf): array
    {
        $query = DB::table('ahg_storage_location_closure as c')
            ->join('ahg_storage_location as l', 'l.id', '=', 'c.descendant')
            ->where('c.ancestor', $id)
            ->orderBy('l.level', 'asc')
            ->orderBy('l.name', 'asc')
            ->select('l.*');

        if (!$includeSelf) {
            $query->where('c.depth', '>', 0);
        }

        return array_map(static function ($row) { return (array) $row; }, $query->get()->all());
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
