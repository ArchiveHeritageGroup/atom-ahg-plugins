<?php

/**
 * ExhibitionSpaceService - PSIS Symfony port of heratio#146 — front-of-house space allocation.
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * Licensed under the GNU Affero General Public License v3.0 or later.
 */

use Illuminate\Database\Capsule\Manager as DB;

class ExhibitionSpaceService
{
    public const SPACE_TYPES = [
        'gallery' => 'Gallery', 'hall' => 'Hall', 'display_case' => 'Display case',
        'plinth' => 'Plinth', 'vitrine' => 'Vitrine',
    ];
    public const CAPACITY_UNITS = [
        'linear_wall_meters' => 'Linear wall metres', 'display_cases' => 'Display cases',
        'plinths' => 'Plinths', 'square_meters' => 'Square metres',
    ];

    public function getBySlug(string $slug)
    {
        return DB::table('ahg_exhibition_space')->where('slug', $slug)->first();
    }

    public function getById(int $id)
    {
        return DB::table('ahg_exhibition_space')->where('id', $id)->first();
    }

    public function browse(string $search = '', int $perPage = 25)
    {
        $today = date('Y-m-d');
        $q = DB::table('ahg_exhibition_space as sp')
            ->leftJoin('ahg_exhibition_placement as ep', function ($j) use ($today) {
                $j->on('ep.exhibition_space_id', '=', 'sp.id')
                    ->where(function ($x) use ($today) { $x->whereNull('ep.starts_at')->orWhere('ep.starts_at', '<=', $today); })
                    ->where(function ($x) use ($today) { $x->whereNull('ep.ends_at')->orWhere('ep.ends_at', '>=', $today); });
            })
            ->select('sp.id', 'sp.slug', 'sp.name', 'sp.space_type', 'sp.building', 'sp.floor',
                'sp.capacity_value', 'sp.capacity_unit',
                DB::raw('COALESCE(SUM(ep.size_units_used), 0) AS used_units_today'),
                DB::raw('COUNT(DISTINCT ep.information_object_id) AS current_placements'))
            ->groupBy('sp.id', 'sp.slug', 'sp.name', 'sp.space_type', 'sp.building', 'sp.floor', 'sp.capacity_value', 'sp.capacity_unit');
        if ($search !== '') {
            $q->where(function ($x) use ($search) {
                $x->where('sp.name', 'like', '%'.$search.'%')->orWhere('sp.building', 'like', '%'.$search.'%');
            });
        }
        return $q->orderBy('sp.name')->get();
    }

    public function getPlacements(int $spaceId)
    {
        return DB::table('ahg_exhibition_placement as ep')
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('ioi.id', '=', 'ep.information_object_id')->where('ioi.culture', '=', 'en');
            })
            ->where('ep.exhibition_space_id', $spaceId)
            ->orderBy('ep.starts_at')
            ->select('ep.id', 'ep.information_object_id', 'ep.exhibition_id',
                'ep.size_units_used', 'ep.starts_at', 'ep.ends_at', 'ep.notes',
                'ioi.title as information_object_title')
            ->get();
    }

    public function capacityOverflow(int $spaceId, float $size, string $startsAt, string $endsAt, ?int $excludePid = null): ?float
    {
        $space = $this->getById($spaceId);
        if (!$space || $space->capacity_value === null) return null;
        $q = DB::table('ahg_exhibition_placement')->where('exhibition_space_id', $spaceId);
        if ($excludePid !== null) $q->where('id', '!=', $excludePid);
        $q->where(function ($x) use ($endsAt) { $x->whereNull('starts_at')->orWhere('starts_at', '<=', $endsAt); })
          ->where(function ($x) use ($startsAt) { $x->whereNull('ends_at')->orWhere('ends_at', '>=', $startsAt); });
        $used = (float) ($q->sum('size_units_used') ?? 0);
        $overflow = ($used + $size) - (float) $space->capacity_value;
        return $overflow > 0 ? $overflow : 0.0;
    }

    public function create(array $data): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') throw new InvalidArgumentException('Exhibition space name is required.');
        $now = date('Y-m-d H:i:s');
        return (int) DB::table('ahg_exhibition_space')->insertGetId([
            'slug' => $this->generateUniqueSlug($name),
            'name' => $name,
            'space_type' => $this->normalizeType($data['space_type'] ?? null, self::SPACE_TYPES, 'gallery'),
            'building' => $data['building'] ?? null,
            'floor' => $data['floor'] ?? null,
            'capacity_value' => isset($data['capacity_value']) && $data['capacity_value'] !== '' ? (float) $data['capacity_value'] : null,
            'capacity_unit' => $this->normalizeType($data['capacity_unit'] ?? null, self::CAPACITY_UNITS, 'linear_wall_meters'),
            'lighting_lux_target' => isset($data['lighting_lux_target']) && $data['lighting_lux_target'] !== '' ? (float) $data['lighting_lux_target'] : null,
            'notes' => $data['notes'] ?? null,
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    public function update(int $id, array $data): void
    {
        $payload = [
            'name' => $data['name'] ?? null,
            'space_type' => isset($data['space_type']) ? $this->normalizeType($data['space_type'], self::SPACE_TYPES, 'gallery') : null,
            'building' => $data['building'] ?? null, 'floor' => $data['floor'] ?? null,
            'capacity_value' => isset($data['capacity_value']) && $data['capacity_value'] !== '' ? (float) $data['capacity_value'] : null,
            'capacity_unit' => isset($data['capacity_unit']) ? $this->normalizeType($data['capacity_unit'], self::CAPACITY_UNITS, 'linear_wall_meters') : null,
            'lighting_lux_target' => isset($data['lighting_lux_target']) && $data['lighting_lux_target'] !== '' ? (float) $data['lighting_lux_target'] : null,
            'notes' => $data['notes'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        DB::table('ahg_exhibition_space')->where('id', $id)->update(array_filter($payload, fn ($v) => $v !== null));
    }

    public function delete(int $id): void
    {
        $count = DB::table('ahg_exhibition_placement')->where('exhibition_space_id', $id)->count();
        if ($count > 0) throw new RuntimeException("Cannot delete: {$count} placement(s) still reference this exhibition space.");
        DB::table('ahg_exhibition_space')->where('id', $id)->delete();
    }

    public function placePlacement(array $data): int
    {
        $pid = (int) ($data['id'] ?? 0);
        $spaceId = (int) ($data['exhibition_space_id'] ?? 0);
        $ioId = (int) ($data['information_object_id'] ?? 0);
        $size = (float) ($data['size_units_used'] ?? 0);
        $startsAt = $data['starts_at'] ?? null;
        $endsAt = $data['ends_at'] ?? null;
        if ($spaceId <= 0 || $ioId <= 0) throw new InvalidArgumentException('exhibition_space_id and information_object_id are required.');
        if ($startsAt && $endsAt && $startsAt > $endsAt) throw new InvalidArgumentException('starts_at must be on or before ends_at.');
        if ($startsAt && $endsAt) {
            $overflow = $this->capacityOverflow($spaceId, $size, $startsAt, $endsAt, $pid > 0 ? $pid : null);
            if ($overflow !== null && $overflow > 0) {
                throw new RuntimeException(sprintf('Placement would exceed capacity by %s units between %s and %s.',
                    number_format($overflow, 2), $startsAt, $endsAt));
            }
        }
        $payload = [
            'information_object_id' => $ioId, 'exhibition_space_id' => $spaceId,
            'exhibition_id' => isset($data['exhibition_id']) && $data['exhibition_id'] !== '' ? (int) $data['exhibition_id'] : null,
            'size_units_used' => $size, 'starts_at' => $startsAt, 'ends_at' => $endsAt,
            'notes' => $data['notes'] ?? null, 'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($pid > 0) { DB::table('ahg_exhibition_placement')->where('id', $pid)->update($payload); return $pid; }
        $payload['created_at'] = date('Y-m-d H:i:s');
        return (int) DB::table('ahg_exhibition_placement')->insertGetId($payload);
    }

    public function removePlacement(int $pid): bool
    {
        return DB::table('ahg_exhibition_placement')->where('id', $pid)->delete() > 0;
    }

    // ── Builder (#136): layout coords + room canvas ─────────────────────────

    /**
     * Placements for the builder / walkthrough: layout coords + object title +
     * best-available thumbnail URL (usage_id 141).
     */
    public function getBuilderPlacements(int $spaceId)
    {
        return DB::table('ahg_exhibition_placement as ep')
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('ioi.id', '=', 'ep.information_object_id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('digital_object as do', function ($j) {
                $j->on('do.object_id', '=', 'ep.information_object_id')->where('do.usage_id', '=', 141);
            })
            ->where('ep.exhibition_space_id', $spaceId)
            ->orderBy('ep.z_order')
            ->select(
                'ep.id', 'ep.information_object_id', 'ep.pos_x', 'ep.pos_y',
                'ep.item_w', 'ep.item_h', 'ep.wall', 'ep.z_order', 'ep.rotation', 'ep.tour_order',
                'ep.size_units_used', 'ep.notes',
                'ioi.title as title',
                DB::raw("CASE WHEN do.path IS NOT NULL THEN CONCAT('/', do.path, do.name) ELSE NULL END as thumb")
            )
            ->get();
    }

    /**
     * Bulk-save builder layout. Each item = {id, pos_x, pos_y, item_w, item_h,
     * wall, z_order, rotation, tour_order}. Only updates placements that belong
     * to this space. Returns number of rows updated.
     */
    public function saveLayout(int $spaceId, array $items): int
    {
        $saved = 0;
        $now = date('Y-m-d H:i:s');
        $walls = ['north', 'east', 'south', 'west', 'floor'];
        foreach ($items as $it) {
            $pid = (int) ($it['id'] ?? 0);
            if ($pid <= 0) {
                continue;
            }
            $tour = (isset($it['tour_order']) && '' !== $it['tour_order'] && null !== $it['tour_order'])
                ? (int) $it['tour_order'] : null;
            $saved += DB::table('ahg_exhibition_placement')
                ->where('id', $pid)->where('exhibition_space_id', $spaceId)
                ->update([
                    'pos_x' => isset($it['pos_x']) ? (float) $it['pos_x'] : null,
                    'pos_y' => isset($it['pos_y']) ? (float) $it['pos_y'] : null,
                    'item_w' => max(20, (float) ($it['item_w'] ?? 120)),
                    'item_h' => max(20, (float) ($it['item_h'] ?? 120)),
                    'wall' => in_array(($it['wall'] ?? 'north'), $walls, true) ? $it['wall'] : 'north',
                    'z_order' => (int) ($it['z_order'] ?? 0),
                    'rotation' => (float) ($it['rotation'] ?? 0),
                    'tour_order' => $tour,
                    'updated_at' => $now,
                ]);
        }
        return $saved;
    }

    /** Update room canvas dimensions + colours. */
    public function updateRoom(int $spaceId, array $data): void
    {
        DB::table('ahg_exhibition_space')->where('id', $spaceId)->update([
            'room_width' => max(200, (float) ($data['room_width'] ?? 1200)),
            'room_height' => max(200, (float) ($data['room_height'] ?? 700)),
            'wall_color' => substr((string) ($data['wall_color'] ?? '#f3f0ea'), 0, 20),
            'floor_color' => substr((string) ($data['floor_color'] ?? '#d8c9ac'), 0, 20),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function generateUniqueSlug(string $name): string
    {
        $base = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($name)));
        $base = trim($base, '-');
        if ($base === '') $base = 'exhibition-space';
        $slug = $base; $i = 2;
        while (DB::table('ahg_exhibition_space')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }
        return $slug;
    }

    private function normalizeType(?string $value, array $allowed, string $default): string
    {
        if ($value === null || trim((string) $value) === '') return $default;
        $value = trim((string) $value);
        return isset($allowed[$value]) ? $value : $default;
    }
}
