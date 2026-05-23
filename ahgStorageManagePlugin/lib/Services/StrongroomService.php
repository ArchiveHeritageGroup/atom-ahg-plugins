<?php

/**
 * StrongroomService - heratio#145 (AtoM Heratio / PSIS port of #144).
 *
 * Copyright (C) 2026 Johan Pieterse
 * The Archive and Heritage Group (Pty) Ltd.
 * Licensed under the GNU Affero General Public License v3.0 or later.
 *
 * Mirrors `packages/ahg-storage-manage/src/Services/StrongroomService.php`
 * on the Heratio Laravel side. Schema is identical (see install.sql);
 * the only differences here are Symfony-1.4-specific bootstrap concerns
 * (no `now()` / `Str::slug()` globals — use direct PHP equivalents).
 */

namespace AhgStorageManage\Services;

use Illuminate\Database\Capsule\Manager as DB;

class StrongroomService
{
    /**
     * Allowed capacity units. Label is shown in selects + summaries.
     * Stored as VARCHAR (no ENUM, per project convention) and normalised on write.
     */
    public const CAPACITY_UNITS = [
        'linear_meters' => 'Linear meters',
        'shelves'       => 'Shelves',
        'boxes'         => 'Boxes',
        'cubic_meters'  => 'Cubic meters',
    ];

    // ---------- Read --------------------------------------------------

    public function getBySlug(string $slug): ?object
    {
        return DB::table('ahg_strongroom')->where('slug', $slug)->first();
    }

    public function getById(int $id): ?object
    {
        return DB::table('ahg_strongroom')->where('id', $id)->first();
    }

    /**
     * Browse rows with a per-room utilisation summary (used_units, occupant_count).
     */
    public function browse(string $search = '', int $perPage = 25)
    {
        $query = DB::table('ahg_strongroom as sr')
            ->leftJoin('ahg_physical_object_storage as ps', 'ps.strongroom_id', '=', 'sr.id')
            ->select(
                'sr.id', 'sr.slug', 'sr.name', 'sr.location_description',
                'sr.capacity_value', 'sr.capacity_unit',
                DB::raw('COALESCE(SUM(ps.size_units_used), 0) AS used_units'),
                DB::raw('COUNT(DISTINCT ps.physical_object_id) AS occupant_count')
            )
            ->groupBy(
                'sr.id', 'sr.slug', 'sr.name', 'sr.location_description',
                'sr.capacity_value', 'sr.capacity_unit'
            );

        if ('' !== $search) {
            $query->where(function ($q) use ($search) {
                $q->where('sr.name', 'LIKE', "%{$search}%")
                    ->orWhere('sr.location_description', 'LIKE', "%{$search}%");
            });
        }

        return $query->orderBy('sr.name')->paginate($perPage);
    }

    /**
     * Physical objects assigned to a strongroom, with their slug, name and size used.
     */
    public function getOccupants(int $strongroomId)
    {
        return DB::table('ahg_physical_object_storage as ps')
            ->join('physical_object as po', 'po.id', '=', 'ps.physical_object_id')
            ->leftJoin('physical_object_i18n as po_i18n', function ($j) {
                $j->on('po_i18n.id', '=', 'po.id')->where('po_i18n.culture', 'en');
            })
            ->leftJoin('slug', 'slug.object_id', '=', 'po.id')
            ->where('ps.strongroom_id', $strongroomId)
            ->select(
                'po.id',
                'po_i18n.name',
                'po_i18n.location',
                'slug.slug',
                'ps.size_units_used'
            )
            ->orderBy('po_i18n.name')
            ->get();
    }

    public function getUsedCapacity(int $strongroomId): float
    {
        return (float) DB::table('ahg_physical_object_storage')
            ->where('strongroom_id', $strongroomId)
            ->sum('size_units_used');
    }

    public function getRemainingCapacity(int $strongroomId): ?float
    {
        $room = $this->getById($strongroomId);
        if (null === $room || null === $room->capacity_value) {
            return null;
        }

        return (float) $room->capacity_value - $this->getUsedCapacity($strongroomId);
    }

    /**
     * Would assigning $newSize to this strongroom exceed its capacity?
     * Returns the projected over-usage (>0) or 0 when fine; NULL when there's
     * no capacity to check against.
     */
    public function capacityOverflow(int $strongroomId, float $newSize, ?int $excludePhysicalObjectId = null): ?float
    {
        $room = $this->getById($strongroomId);
        if (null === $room || null === $room->capacity_value) {
            return null;
        }

        $q = DB::table('ahg_physical_object_storage')->where('strongroom_id', $strongroomId);
        if (null !== $excludePhysicalObjectId) {
            $q->where('physical_object_id', '!=', $excludePhysicalObjectId);
        }
        $usedByOthers = (float) $q->sum('size_units_used');
        $over = ($usedByOthers + $newSize) - (float) $room->capacity_value;

        return $over > 0 ? $over : 0.0;
    }

    /** Choices for <select> menus: [id => "Name (Unit label)"]. */
    public function dropdownChoices(): array
    {
        $rows = DB::table('ahg_strongroom')
            ->orderBy('name')
            ->get(['id', 'name', 'capacity_unit']);

        $out = [];
        foreach ($rows as $row) {
            $unitLabel = self::CAPACITY_UNITS[$row->capacity_unit] ?? $row->capacity_unit;
            $out[(int) $row->id] = $row->name . ' (' . $unitLabel . ')';
        }

        return $out;
    }

    // ---------- Write -------------------------------------------------

    public function create(array $data): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ('' === $name) {
            throw new \InvalidArgumentException('Strongroom name is required');
        }
        $now = $this->nowStr();

        return DB::table('ahg_strongroom')->insertGetId([
            'slug'                 => $this->generateUniqueSlug($name),
            'name'                 => $name,
            'location_description' => $data['location_description'] ?? null,
            'capacity_value'       => $this->nullableDecimal($data['capacity_value'] ?? null),
            'capacity_unit'        => $this->normalizeCapacityUnit($data['capacity_unit'] ?? 'linear_meters'),
            'notes'                => $data['notes'] ?? null,
            'created_at'           => $now,
            'updated_at'           => $now,
        ]);
    }

    /** Slug stays sticky across renames. */
    public function update(int $id, array $data): void
    {
        $update = ['updated_at' => $this->nowStr()];

        if (isset($data['name']) && '' !== trim((string) $data['name'])) {
            $update['name'] = trim((string) $data['name']);
        }
        foreach (['location_description', 'notes'] as $key) {
            if (array_key_exists($key, $data)) {
                $update[$key] = $data[$key];
            }
        }
        if (array_key_exists('capacity_value', $data)) {
            $update['capacity_value'] = $this->nullableDecimal($data['capacity_value']);
        }
        if (array_key_exists('capacity_unit', $data)) {
            $update['capacity_unit'] = $this->normalizeCapacityUnit((string) $data['capacity_unit']);
        }

        DB::table('ahg_strongroom')->where('id', $id)->update($update);
    }

    /**
     * @throws \RuntimeException when the room still has occupants
     */
    public function delete(int $id): void
    {
        $occupants = (int) DB::table('ahg_physical_object_storage')
            ->where('strongroom_id', $id)
            ->count();

        if ($occupants > 0) {
            throw new \RuntimeException(sprintf(
                'Cannot delete strongroom: %d occupant(s) still assigned. Move them first.',
                $occupants
            ));
        }

        DB::table('ahg_strongroom')->where('id', $id)->delete();
    }

    // ---------- Physical-object assignment ----------------------------

    public function assign(int $physicalObjectId, int $strongroomId, float $sizeUnitsUsed = 0.0): void
    {
        DB::table('ahg_physical_object_storage')->updateOrInsert(
            ['physical_object_id' => $physicalObjectId],
            [
                'strongroom_id'   => $strongroomId,
                'size_units_used' => max(0.0, $sizeUnitsUsed),
                'updated_at'      => $this->nowStr(),
                'created_at'      => DB::raw('COALESCE(created_at, NOW())'),
            ]
        );
    }

    public function unassign(int $physicalObjectId): void
    {
        DB::table('ahg_physical_object_storage')
            ->where('physical_object_id', $physicalObjectId)
            ->delete();
    }

    public function getAssignment(int $physicalObjectId): ?object
    {
        return DB::table('ahg_physical_object_storage as ps')
            ->join('ahg_strongroom as sr', 'sr.id', '=', 'ps.strongroom_id')
            ->where('ps.physical_object_id', $physicalObjectId)
            ->select(
                'ps.id',
                'ps.physical_object_id',
                'ps.strongroom_id',
                'ps.size_units_used',
                'sr.slug as strongroom_slug',
                'sr.name as strongroom_name',
                'sr.capacity_unit'
            )
            ->first();
    }

    // ---------- Helpers -----------------------------------------------

    private function generateUniqueSlug(string $name): string
    {
        $base = $this->slugify($name);
        $slug = $base;
        $n = 2;
        while (DB::table('ahg_strongroom')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $n;
            ++$n;
        }

        return $slug;
    }

    private function slugify(string $s): string
    {
        $s = strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9]+/', '-', $s);
        $s = trim($s ?? '', '-');

        return '' !== $s ? $s : 'strongroom';
    }

    private function normalizeCapacityUnit(string $unit): string
    {
        return isset(self::CAPACITY_UNITS[$unit]) ? $unit : 'linear_meters';
    }

    private function nullableDecimal($value): ?float
    {
        if (null === $value || '' === $value || false === $value) {
            return null;
        }
        if (!is_numeric($value)) {
            return null;
        }
        $v = (float) $value;

        return $v < 0 ? null : $v;
    }

    private function nowStr(): string
    {
        return date('Y-m-d H:i:s');
    }
}
