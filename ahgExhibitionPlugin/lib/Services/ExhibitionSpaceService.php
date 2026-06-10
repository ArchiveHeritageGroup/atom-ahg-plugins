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
     * Legacy bulk-save builder layout (old PSIS column scheme). Each item =
     * {id, pos_x, pos_y, item_w, item_h, wall, z_order, rotation, tour_order}.
     * Renamed from saveLayout() when the Heratio digital-twin saveLayout() was
     * ported in (see below). Only updates placements that belong to this space.
     * Returns number of rows updated.
     */
    public function saveLayout_legacy(int $spaceId, array $items): int
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

    // ════════════════════════════════════════════════════════════════════════
    //  Digital twin / 2.5D builder + walkthrough (ported from heratio#1138+).
    //  PSIS schema already carries the Heratio columns, so these port verbatim,
    //  with defensive guards for columns/tables that do not exist on PSIS yet
    //  (guided_tour_json, walkthrough_path_json, ahg_exhibition_reading,
    //  ahg_exhibition_furniture).
    // ════════════════════════════════════════════════════════════════════════

    private function nowTs(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * True when $table physically exists in the current database. Uses
     * INFORMATION_SCHEMA (placeholders cannot bind to SHOW TABLES LIKE).
     */
    private function tableExists(string $table): bool
    {
        return count(DB::select(
            'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1',
            [$table]
        )) > 0;
    }

    /** True when $column exists on $table. */
    private function columnExists(string $table, string $column): bool
    {
        return count(DB::select(
            'SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1',
            [$table, $column]
        )) > 0;
    }

    /**
     * Placements for the drag-and-drop builder canvas: spatial coordinates plus a
     * best-effort thumbnail URL for each information object.
     */
    public function getPlacementsForBuilder(int $exhibitionSpaceId): array
    {
        $rows = DB::table('ahg_exhibition_placement as ep')
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('ioi.id', '=', 'ep.information_object_id')->where('ioi.culture', '=', 'en');
            })
            ->where('ep.exhibition_space_id', $exhibitionSpaceId)
            ->where(function ($q) {   // corridor objects are building-level, not per-room
                $q->whereNull('ep.wall_or_zone')->orWhere('ep.wall_or_zone', '!=', 'corridor');
            })
            ->select(
                'ep.id', 'ep.information_object_id',
                'ep.pos_x', 'ep.pos_y', 'ep.rotation_deg', 'ep.scale', 'ep.z_order',
                'ep.wall_or_zone', 'ep.label_visible', 'ep.size_units_used',
                'ep.model_tilt_x', 'ep.model_tilt_z', 'ep.wall_u', 'ep.wall_v', 'ep.spotlight', 'ep.display_case', 'ep.on_floor',
                'ep.view_x', 'ep.view_y',
                'ioi.title as information_object_title'
            )
            ->orderBy('ep.z_order')
            ->get();

        return $rows->map(function ($r) {
            $media = $this->getObjectMedia((int) $r->information_object_id);

            return [
                'id' => (int) $r->id,
                'information_object_id' => (int) $r->information_object_id,
                'title' => $r->information_object_title ?: ('#'.$r->information_object_id),
                'pos_x' => $r->pos_x !== null ? (float) $r->pos_x : null,
                'pos_y' => $r->pos_y !== null ? (float) $r->pos_y : null,
                'rotation_deg' => (float) ($r->rotation_deg ?? 0),
                'scale' => (float) ($r->scale ?? 1),
                'z_order' => (int) ($r->z_order ?? 0),
                'wall_or_zone' => $r->wall_or_zone,
                'label_visible' => (int) ($r->label_visible ?? 1),
                'size_units_used' => (float) ($r->size_units_used ?? 0),
                'view_x' => $r->view_x !== null ? (float) $r->view_x : null,
                'view_y' => $r->view_y !== null ? (float) $r->view_y : null,
                'kind' => $media['kind'],
                'tilt_x' => $r->model_tilt_x !== null ? (float) $r->model_tilt_x : null,
                'tilt_z' => $r->model_tilt_z !== null ? (float) $r->model_tilt_z : null,
                'wall_u' => $r->wall_u !== null ? (float) $r->wall_u : null,
                'wall_v' => $r->wall_v !== null ? (float) $r->wall_v : null,
                'spotlight' => (int) ($r->spotlight ?? 0),
                'display_case' => (int) ($r->display_case ?? 0), 'on_floor' => (int) ($r->on_floor ?? 0),
                'thumb_url' => $media['image_url'] ?? $this->thumbnailUrl((int) $r->information_object_id),
            ];
        })->all();
    }

    /** Best-effort thumbnail URL for an information object. */
    public function thumbnailUrl(int $informationObjectId): ?string
    {
        return $this->bestImageUrl($informationObjectId);
    }

    /**
     * Best browser-renderable image URL for an object. Searches the object's own
     * digital objects AND their child derivatives, preferring reference > thumb >
     * master. Returns null when only non-browser formats exist.
     */
    private function bestImageUrl(int $informationObjectId): ?string
    {
        $imgExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
        $direct = DB::table('digital_object')
            ->where('object_id', $informationObjectId)
            ->select('id', 'usage_id', 'path', 'name')->get();
        $ids = $direct->pluck('id')->all();
        $children = empty($ids) ? collect() : DB::table('digital_object')
            ->whereIn('parent_id', $ids)
            ->select('id', 'usage_id', 'path', 'name')->get();

        $candidates = $direct->concat($children)->filter(function ($r) use ($imgExts) {
            if (empty($r->path)) {
                return false;
            }
            $ext = strtolower(pathinfo((string) ($r->name ?: $r->path), PATHINFO_EXTENSION));

            return in_array($ext, $imgExts, true);
        });
        if ($candidates->isEmpty()) {
            return null;
        }
        $rank = [141 => 0, 142 => 1, 140 => 2];
        $best = $candidates->sortBy(fn ($r) => $rank[$r->usage_id] ?? 9)->first();

        return $this->buildDoUrl($best->path, $best->name);
    }

    /**
     * Build a public URL from a digital_object row. AtoM stores `path` as the
     * directory and `name` as the filename, so when `path` has no file extension
     * the filename is appended.
     */
    private function buildDoUrl(string $path, ?string $name): ?string
    {
        $path = trim($path);
        if ($path === '') {
            return null;
        }
        $hasExt = pathinfo(parse_url($path, PHP_URL_PATH) ?: $path, PATHINFO_EXTENSION) !== '';
        if (! $hasExt && ! empty($name)) {
            $path = rtrim($path, '/').'/'.ltrim($name, '/');
        }
        if (! str_starts_with($path, '/') && ! str_starts_with($path, 'http') && ! str_starts_with($path, 'uploads')) {
            $path = '/uploads/r/'.$path;
        }

        return $this->normalizeUploadPath($path);
    }

    /** Models above this size freeze the browser when parsed on the main thread. */
    private const MAX_MODEL_BYTES = 20 * 1024 * 1024;   // 20 MB

    private function modelTooBig(?string $webPath): bool
    {
        if (! $webPath) {
            return false;
        }
        $root = defined('ATOM_ROOT') ? ATOM_ROOT : (\sfConfig::get('sf_root_dir') ?: '/usr/share/nginx/archive');
        $f = rtrim((string) $root, '/').'/'.ltrim($webPath, '/');

        return is_file($f) && filesize($f) > self::MAX_MODEL_BYTES;
    }

    /**
     * Resolve the display media for an information object so the 3D walkthrough can
     * pick the right renderer.
     *
     * @return array{kind:string,model_url:?string,image_url:?string,format:?string}
     */
    public function getObjectMedia(int $informationObjectId): array
    {
        // 1) Dedicated 3D model row wins.
        if ($this->tableExists('object_3d_model')) {
            $model = DB::table('object_3d_model')
                ->where('object_id', $informationObjectId)
                ->orderByDesc('is_primary')
                ->first();
            if ($model && ! empty($model->file_path)) {
                $murl = $this->normalizeUploadPath($model->file_path);

                return [
                    'kind' => '3d',
                    'model_url' => $murl,
                    'model_oversize' => $this->modelTooBig($murl),
                    'image_url' => $this->normalizeUploadPath($model->poster_image ?: $model->thumbnail) ?: $this->thumbnailUrl($informationObjectId),
                    'doc_url' => null,
                    'format' => $model->format ?: 'glb',
                ];
            }
        }

        // 2) Inspect the primary digital object to detect 3D / PDF masters.
        $do = DB::table('digital_object')
            ->where('object_id', $informationObjectId)
            ->whereIn('usage_id', [141, 142, 140])
            ->orderByRaw('FIELD(usage_id, 141, 142, 140)')
            ->select('path', 'name')
            ->first();
        if ($do && ! empty($do->path)) {
            $ext = strtolower(pathinfo((string) ($do->name ?: $do->path), PATHINFO_EXTENSION));
            $url = $this->buildDoUrl($do->path, $do->name);
            $threeD = ['glb', 'gltf', 'obj', 'stl', 'usdz', 'ply'];
            if (in_array($ext, $threeD, true)) {
                return ['kind' => '3d', 'model_url' => $url, 'model_oversize' => $this->modelTooBig($url), 'image_url' => $this->bestImageUrl($informationObjectId), 'doc_url' => null, 'format' => $ext];
            }
            if ($ext === 'pdf') {
                return ['kind' => 'pdf', 'model_url' => null, 'image_url' => $this->bestImageUrl($informationObjectId), 'doc_url' => $url, 'format' => 'pdf'];
            }
        }

        // 3) Otherwise a flat image.
        $img = $this->bestImageUrl($informationObjectId);
        if ($img) {
            return ['kind' => 'image', 'model_url' => null, 'image_url' => $img, 'doc_url' => null, 'format' => 'image'];
        }

        return ['kind' => 'other', 'model_url' => null, 'image_url' => null, 'doc_url' => null, 'format' => null];
    }

    private function normalizeUploadPath(?string $p): ?string
    {
        if ($p === null || $p === '') {
            return null;
        }
        if (str_starts_with($p, 'http') || str_starts_with($p, '/')) {
            return $p;
        }
        if (str_starts_with($p, 'uploads')) {
            return '/'.$p;
        }

        return '/uploads/'.$p;
    }

    /**
     * Persist canvas positions (digital-twin scheme). Only placements that belong
     * to the given space are updated.
     *
     * @param  array<int,array<string,mixed>>  $positions  each: id,pos_x,pos_y,rotation_deg,scale,z_order
     */
    public function saveLayout(int $exhibitionSpaceId, array $positions): int
    {
        $valid = DB::table('ahg_exhibition_placement')
            ->where('exhibition_space_id', $exhibitionSpaceId)
            ->pluck('id')->map(fn ($v) => (int) $v)->all();
        $valid = array_flip($valid);

        $saved = 0;
        foreach ($positions as $p) {
            $id = (int) ($p['id'] ?? 0);
            if ($id <= 0 || ! isset($valid[$id])) {
                continue;
            }
            DB::table('ahg_exhibition_placement')->where('id', $id)->update([
                'pos_x' => isset($p['pos_x']) ? max(0, min(1, (float) $p['pos_x'])) : null,
                'pos_y' => isset($p['pos_y']) ? max(0, min(1, (float) $p['pos_y'])) : null,
                'rotation_deg' => (float) ($p['rotation_deg'] ?? 0),
                'scale' => (float) ($p['scale'] ?? 1),
                'z_order' => (int) ($p['z_order'] ?? 0),
                'updated_at' => $this->nowTs(),
            ]);
            $saved++;
        }

        return $saved;
    }

    /**
     * Create a placement dropped onto the canvas (no date range, so no capacity
     * gate) and return its full builder row for immediate rendering.
     *
     * @return array<string,mixed>
     */
    public function createPlacementAt(int $exhibitionSpaceId, int $informationObjectId, float $posX, float $posY, float $sizeUnits = 0): array
    {
        if ($exhibitionSpaceId <= 0 || $informationObjectId <= 0) {
            throw new InvalidArgumentException('exhibition_space_id and information_object_id are required.');
        }
        $sizeUnits = max(0, $sizeUnits);
        $now = $this->nowTs();
        $id = (int) DB::table('ahg_exhibition_placement')->insertGetId([
            'information_object_id' => $informationObjectId,
            'exhibition_space_id' => $exhibitionSpaceId,
            'size_units_used' => $sizeUnits,
            'pos_x' => max(0, min(1, $posX)),
            'pos_y' => max(0, min(1, $posY)),
            'rotation_deg' => 0,
            'scale' => 1,
            'z_order' => 0,
            'label_visible' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $title = DB::table('information_object_i18n')
            ->where('id', $informationObjectId)->where('culture', 'en')->value('title');
        $media = $this->getObjectMedia($informationObjectId);

        return [
            'id' => $id,
            'information_object_id' => $informationObjectId,
            'title' => $title ?: ('#'.$informationObjectId),
            'pos_x' => max(0, min(1, $posX)),
            'pos_y' => max(0, min(1, $posY)),
            'rotation_deg' => 0.0,
            'scale' => 1.0,
            'z_order' => 0,
            'wall_or_zone' => null,
            'label_visible' => 1,
            'size_units_used' => $sizeUnits,
            'kind' => $media['kind'],
            'thumb_url' => $media['image_url'] ?? $this->thumbnailUrl($informationObjectId),
        ];
    }

    /** Update just the capacity size of a placement (builder size editor). */
    public function updatePlacementSize(int $exhibitionSpaceId, int $placementId, float $sizeUnits): bool
    {
        return DB::table('ahg_exhibition_placement')
            ->where('id', $placementId)
            ->where('exhibition_space_id', $exhibitionSpaceId)
            ->update(['size_units_used' => max(0, $sizeUnits), 'updated_at' => $this->nowTs()]) > 0;
    }

    /** Per-object 3D orientation. Pass null for an axis to fall back to the auto guess. */
    public function updatePlacementTilt(int $exhibitionSpaceId, int $placementId, ?float $tiltX, ?float $tiltZ): bool
    {
        return DB::table('ahg_exhibition_placement')
            ->where('id', $placementId)
            ->where('exhibition_space_id', $exhibitionSpaceId)
            ->update(['model_tilt_x' => $tiltX, 'model_tilt_z' => $tiltZ, 'updated_at' => $this->nowTs()]) > 0;
    }

    /** Spotlight mode. 0 = off, 1 = light on approach, 2 = always-on. */
    public function updatePlacementSpotlight(int $exhibitionSpaceId, int $placementId, int $mode): bool
    {
        $mode = max(0, min(2, $mode));

        return DB::table('ahg_exhibition_placement')
            ->where('id', $placementId)->where('exhibition_space_id', $exhibitionSpaceId)
            ->update(['spotlight' => $mode, 'updated_at' => $this->nowTs()]) > 0;
    }

    /** Toggle whether this item is shown inside a glass display case on a plinth. */
    public function updatePlacementDisplayCase(int $exhibitionSpaceId, int $placementId, bool $on): bool
    {
        return DB::table('ahg_exhibition_placement')
            ->where('id', $placementId)->where('exhibition_space_id', $exhibitionSpaceId)
            ->update(['display_case' => $on ? 1 : 0, 'updated_at' => $this->nowTs()]) > 0;
    }

    /** Toggle whether a 3D model stands directly on the floor (no pedestal). */
    public function updatePlacementOnFloor(int $exhibitionSpaceId, int $placementId, bool $on): bool
    {
        return DB::table('ahg_exhibition_placement')
            ->where('id', $placementId)->where('exhibition_space_id', $exhibitionSpaceId)
            ->update(['on_floor' => $on ? 1 : 0, 'updated_at' => $this->nowTs()]) > 0;
    }

    /** Set (or clear with null) the curator-chosen viewing spot (room-local 0-1 fraction). */
    public function updatePlacementView(int $exhibitionSpaceId, int $placementId, ?float $vx, ?float $vy): bool
    {
        $clamp = fn ($v) => $v === null ? null : max(0, min(1, (float) $v));

        return DB::table('ahg_exhibition_placement')
            ->where('id', $placementId)->where('exhibition_space_id', $exhibitionSpaceId)
            ->update(['view_x' => $clamp($vx), 'view_y' => $clamp($vy), 'updated_at' => $this->nowTs()]) > 0;
    }

    /** Bring-to-front / send-to-back: set a placement's z-order. */
    public function updatePlacementZOrder(int $exhibitionSpaceId, int $placementId, int $z): bool
    {
        return DB::table('ahg_exhibition_placement')
            ->where('id', $placementId)->where('exhibition_space_id', $exhibitionSpaceId)
            ->update(['z_order' => $z, 'updated_at' => $this->nowTs()]) > 0;
    }

    /** Assign a placement to a specific wall (null/'' = auto nearest). */
    public function updatePlacementWall(int $exhibitionSpaceId, int $placementId, ?string $wall): bool
    {
        return DB::table('ahg_exhibition_placement')
            ->where('id', $placementId)
            ->where('exhibition_space_id', $exhibitionSpaceId)
            ->update(['wall_or_zone' => ($wall !== null && $wall !== '') ? $wall : null, 'updated_at' => $this->nowTs()]) > 0;
    }

    // -------- Interior walls (normalized 0-1 floorplan coords) --------

    /** @return array<int,array<string,mixed>> */
    public function getWalls(int $exhibitionSpaceId): array
    {
        $space = $this->getById($exhibitionSpaceId);
        if (! $space || empty($space->walls_json)) {
            return [];
        }
        $walls = json_decode((string) $space->walls_json, true);

        return is_array($walls) ? array_values($walls) : [];
    }

    /** @param array<int,array<string,mixed>> $walls */
    public function saveWalls(int $exhibitionSpaceId, array $walls): void
    {
        $clean = [];
        foreach ($walls as $i => $w) {
            $x1 = isset($w['x1']) ? max(0, min(1, (float) $w['x1'])) : null;
            $z1 = isset($w['z1']) ? max(0, min(1, (float) $w['z1'])) : null;
            $x2 = isset($w['x2']) ? max(0, min(1, (float) $w['x2'])) : null;
            $z2 = isset($w['z2']) ? max(0, min(1, (float) $w['z2'])) : null;
            if ($x1 === null || $z1 === null || $x2 === null || $z2 === null) {
                continue;
            }
            $clean[] = [
                'id' => isset($w['id']) && $w['id'] !== '' ? (string) $w['id'] : ('wall-'.$i),
                'x1' => $x1, 'z1' => $z1, 'x2' => $x2, 'z2' => $z2,
            ];
        }
        DB::table('ahg_exhibition_space')->where('id', $exhibitionSpaceId)
            ->update(['walls_json' => json_encode($clean), 'updated_at' => $this->nowTs()]);
    }

    // -------- Doorways --------

    /** @return array<int,array<string,mixed>> */
    public function getDoors(int $exhibitionSpaceId): array
    {
        $space = $this->getById($exhibitionSpaceId);
        if (! $space || empty($space->doors_json)) {
            return [];
        }
        $doors = json_decode((string) $space->doors_json, true);

        return is_array($doors) ? array_values($this->sanitizeDoors($doors)) : [];
    }

    /** Persist a room's doors (sanitised). */
    public function saveDoors(int $exhibitionSpaceId, array $doors): void
    {
        DB::table('ahg_exhibition_space')->where('id', $exhibitionSpaceId)
            ->update(['doors_json' => json_encode(array_values($this->sanitizeDoors($doors))), 'updated_at' => $this->nowTs()]);
    }

    /** @param array<int,mixed> $doors @return array<int,array<string,mixed>> */
    private function sanitizeDoors(array $doors): array
    {
        $walls = ['north', 'south', 'east', 'west'];
        $types = ['open', 'single', 'double', 'glass', 'sliding', 'ornate'];
        $clean = [];
        foreach ($doors as $d) {
            if (! is_array($d)) {
                continue;
            }
            $pos = max(0.0, min(1.0, (float) ($d['pos'] ?? 0.5)));
            $width = max(0.5, min(6.0, (float) ($d['width'] ?? 1.6)));
            $type = isset($d['type']) && in_array((string) $d['type'], $types, true) ? (string) $d['type'] : 'open';
            if (isset($d['edge']) && is_numeric($d['edge'])) {
                $clean[] = ['edge' => max(0, (int) $d['edge']), 'pos' => $pos, 'width' => $width, 'type' => $type];

                continue;
            }
            $wall = isset($d['wall']) ? strtolower((string) $d['wall']) : '';
            if (! in_array($wall, $walls, true)) {
                continue;
            }
            $clean[] = ['wall' => $wall, 'pos' => $pos, 'width' => $width, 'type' => $type];
        }

        return $clean;
    }

    // -------- Windows --------

    /** @return array<int,array<string,mixed>> */
    public function getWindows(int $exhibitionSpaceId): array
    {
        $space = $this->getById($exhibitionSpaceId);
        if (! $space || empty($space->windows_json)) {
            return [];
        }
        $w = json_decode((string) $space->windows_json, true);
        if (! is_array($w)) {
            return [];
        }
        $out = [];
        foreach ($w as $x) {
            $hasWall = ! empty($x['wall']);
            $hasEdge = isset($x['edge']) && is_numeric($x['edge']);
            if (! $hasWall && ! $hasEdge) {
                continue;
            }
            $row = [
                'pos' => isset($x['pos']) ? max(0.0, min(1.0, (float) $x['pos'])) : 0.5,
                'width' => isset($x['width']) ? max(0.4, min(6.0, (float) $x['width'])) : 1.4,
                'sill' => isset($x['sill']) ? max(0.2, min(2.0, (float) $x['sill'])) : 0.9,
                'height' => isset($x['height']) ? max(0.4, min(3.0, (float) $x['height'])) : 1.3,
            ];
            if ($hasEdge) {
                $row['edge'] = (int) $x['edge'];
            } else {
                $row['wall'] = (string) $x['wall'];
            }
            $out[] = $row;
        }

        return $out;
    }

    /** Persist a room's windows. */
    public function saveWindows(int $exhibitionSpaceId, array $windows): void
    {
        DB::table('ahg_exhibition_space')->where('id', $exhibitionSpaceId)
            ->update(['windows_json' => json_encode($windows), 'updated_at' => $this->nowTs()]);
    }

    // -------- Custom room footprint (polygon shape) --------

    /** @return array<int,array{x:float,z:float}>|null */
    public function getShape(int $exhibitionSpaceId): ?array
    {
        $space = $this->getById($exhibitionSpaceId);
        if (! $space || empty($space->shape_json)) {
            return null;
        }
        $pts = json_decode((string) $space->shape_json, true);

        return $this->sanitizeShape(is_array($pts) ? $pts : []);
    }

    /** Persist a room footprint polygon (normalized 0-1). Null/<3 points clears it. */
    public function saveShape(int $exhibitionSpaceId, ?array $points): void
    {
        $clean = $points === null ? null : $this->sanitizeShape($points);
        DB::table('ahg_exhibition_space')->where('id', $exhibitionSpaceId)
            ->update(['shape_json' => $clean ? json_encode($clean) : null, 'updated_at' => $this->nowTs()]);
    }

    /** @param array<int,mixed> $points @return array<int,array{x:float,z:float}>|null */
    private function sanitizeShape(array $points): ?array
    {
        $clean = [];
        foreach ($points as $p) {
            $x = null;
            $z = null;
            if (is_array($p)) {
                $x = $p['x'] ?? ($p[0] ?? null);
                $z = $p['z'] ?? ($p[1] ?? null);
            }
            if ($x === null || $z === null) {
                continue;
            }
            $clean[] = ['x' => max(0.0, min(1.0, (float) $x)), 'z' => max(0.0, min(1.0, (float) $z))];
        }

        return count($clean) >= 3 ? $clean : null;
    }

    // -------- Room dimensions --------

    /** Set room dimensions (metres): width, depth, wall height. Nulls are skipped. */
    public function updateRoomDims(int $exhibitionSpaceId, ?float $w, ?float $d, ?float $h): void
    {
        $p = [];
        if ($w !== null) {
            $p['room_w'] = max(1, min(200, $w));
        }
        if ($d !== null) {
            $p['room_d'] = max(1, min(200, $d));
        }
        if ($h !== null) {
            $p['room_h'] = max(1, min(30, $h));
        }
        if ($p) {
            $p['updated_at'] = $this->nowTs();
            DB::table('ahg_exhibition_space')->where('id', $exhibitionSpaceId)->update($p);
        }
    }

    /** Effective room dimensions (metres), defaulting where unset. */
    public function roomDims(object $space): array
    {
        return [
            'w' => $space->room_w !== null ? (float) $space->room_w : 18.0,
            'd' => $space->room_d !== null ? (float) $space->room_d : 14.0,
            'h' => $space->room_h !== null ? (float) $space->room_h : 4.0,
        ];
    }

    // -------- Furniture (table may not exist on PSIS yet) --------

    /** @return array<int,array<string,mixed>> */
    public function getFurniture(int $exhibitionSpaceId): array
    {
        if (! $this->tableExists('ahg_exhibition_furniture')) {
            return [];
        }

        return DB::table('ahg_exhibition_furniture')->where('exhibition_space_id', $exhibitionSpaceId)
            ->orderBy('id')->get()->map(function ($r) {
                $poles = (! empty($r->pole_json) && is_array($pj = json_decode((string) $r->pole_json, true))) ? $pj : null;
                $label = null;
                if (! empty($r->asset_path)) {
                    $base = pathinfo((string) $r->asset_path, PATHINFO_FILENAME);
                    $base = preg_replace('/-[0-9a-f]{8}$/i', '', $base);
                    $label = ucfirst(trim(str_replace(['-', '_'], ' ', $base)));
                }

                return ['id' => (int) $r->id, 'kind' => $r->kind, 'pos_x' => (float) $r->pos_x, 'pos_y' => (float) $r->pos_y, 'rotation_deg' => (float) $r->rotation_deg, 'scale' => (float) $r->scale, 'segments' => (int) ($r->segments ?? 2), 'poles' => $poles, 'asset_path' => $r->asset_path ?? null, 'asset_ext' => $r->asset_ext ?? null, 'label' => $label];
            })->all();
    }

    // -------- Guided tour (guided_tour_json column may not exist on PSIS yet) --------

    /** @return array<int,array<string,mixed>> */
    public function getGuidedTour(object $space): array
    {
        if (! $this->columnExists('ahg_exhibition_space', 'guided_tour_json')) {
            return [];
        }
        $raw = [];
        foreach ($this->buildingRoomsOrdered($space) as $rm) {
            $j = $rm->guided_tour_json ?? null;
            if (is_string($j)) {
                $j = json_decode($j, true);
            }
            if (! is_array($j) || empty($j)) {
                continue;
            }
            if (isset($j[0]['io_id'])) {   // legacy flat stop array
                $j = [['name' => 'Tour', 'stops' => $j]];
            }
            foreach ($j as $t) {
                if (is_array($t)) {
                    $raw[] = $t;
                }
            }
        }
        if (empty($raw)) {
            return [];
        }
        $seen = [];
        $deduped = [];
        foreach ($raw as $t) {
            $key = mb_strtolower(trim((string) ($t['name'] ?? 'Tour')));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $deduped[] = $t;
        }
        $raw = $deduped;
        $ids = [];
        foreach ($raw as $t) {
            foreach (($t['stops'] ?? []) as $s) {
                if (! empty($s['io_id'])) {
                    $ids[] = (int) $s['io_id'];
                }
            }
        }
        $titles = $ids ? DB::table('information_object_i18n')->whereIn('id', $ids)->where('culture', 'en')
            ->pluck('title', 'id')->all() : [];
        $out = [];
        foreach ($raw as $t) {
            $stops = [];
            foreach (($t['stops'] ?? []) as $s) {
                if (empty($s['io_id'])) {
                    continue;
                }
                $id = (int) $s['io_id'];
                $stops[] = ['io_id' => $id, 'title' => $titles[$id] ?? ('#'.$id), 'narration' => (string) ($s['narration'] ?? ''), 'dwell' => (int) ($s['dwell'] ?? 6), 'audio' => (string) ($s['audio'] ?? '')];
            }
            $out[] = ['name' => (string) ($t['name'] ?? 'Tour'), 'stops' => $stops];
        }

        return $out;
    }

    /** Save the authored guided tours (list of {name, stops}); validates + clamps. */
    public function saveGuidedTour(object $space, array $tours): void
    {
        if (! $this->columnExists('ahg_exhibition_space', 'guided_tour_json')) {
            return;
        }
        $clean = [];
        foreach ($tours as $t) {
            $stops = [];
            foreach (($t['stops'] ?? []) as $s) {
                if (empty($s['io_id'])) {
                    continue;
                }
                $stops[] = ['io_id' => (int) $s['io_id'], 'narration' => mb_substr(trim((string) ($s['narration'] ?? '')), 0, 1200), 'dwell' => max(2, min(60, (int) ($s['dwell'] ?? 6))), 'audio' => mb_substr(trim((string) ($s['audio'] ?? '')), 0, 500)];
            }
            if ($stops) {
                $clean[] = ['name' => (mb_substr(trim((string) ($t['name'] ?? 'Tour')), 0, 80) ?: 'Tour'), 'stops' => $stops];
            }
        }
        $rooms = $this->buildingRoomsOrdered($space);
        $host = $rooms[0] ?? $space;
        DB::table('ahg_exhibition_space')->where('id', $host->id)->update(['guided_tour_json' => json_encode($clean)]);
        if (! empty($space->building_id)) {
            DB::table('ahg_exhibition_space')->where('building_id', $space->building_id)
                ->where('id', '!=', $host->id)->update(['guided_tour_json' => null]);
        }
    }

    // -------- Multi-room building assembly (walkthrough) --------

    /** Rooms of a space's building in display order (or just the space when ungrouped). */
    public function buildingRoomsOrdered(object $space): array
    {
        if (empty($space->building_id)) {
            return [$space];
        }

        return DB::table('ahg_exhibition_space')
            ->where('building_id', $space->building_id)
            ->orderBy('floor_level')->orderBy('building_seq')->orderBy('id')
            ->get()->all();
    }

    /** Space ids belonging to a space's building (or just the space when ungrouped). */
    private function buildingSpaceIds(object $space): array
    {
        if (! empty($space->building_id)) {
            return DB::table('ahg_exhibition_space')->where('building_id', $space->building_id)->pluck('id')->map(fn ($v) => (int) $v)->all();
        }

        return [(int) $space->id];
    }

    /**
     * Corridor objects for a building (placements flagged wall_or_zone='corridor').
     *
     * @return array<int,array<string,mixed>>
     */
    public function getBuildingCorridorObjects(object $space): array
    {
        $ids = $this->buildingSpaceIds($space);
        if (empty($ids)) {
            return [];
        }
        $rows = DB::table('ahg_exhibition_placement as ep')
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('ioi.id', '=', 'ep.information_object_id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug as sl', 'sl.object_id', '=', 'ep.information_object_id')
            ->whereIn('ep.exhibition_space_id', $ids)
            ->where('ep.wall_or_zone', 'corridor')
            ->select('ep.id', 'ep.information_object_id', 'ep.pos_x', 'ep.pos_y', 'ep.rotation_deg', 'ep.scale',
                'ep.model_tilt_x', 'ep.model_tilt_z', 'ioi.title as title', 'ioi.scope_and_content as description', 'sl.slug as slug')
            ->get();

        return $rows->map(function ($r) {
            $desc = trim(strip_tags((string) ($r->description ?? '')));
            $media = $this->getObjectMedia((int) $r->information_object_id);

            return [
                'id' => (int) $r->id,
                'information_object_id' => (int) $r->information_object_id,
                'title' => $r->title ?: ('#'.$r->information_object_id),
                'description' => mb_strlen($desc) > 400 ? mb_substr($desc, 0, 400).'...' : $desc,
                'pos_x' => $r->pos_x !== null ? (float) $r->pos_x : 0.5,
                'pos_y' => $r->pos_y !== null ? (float) $r->pos_y : 0.5,
                'rotation_deg' => (float) ($r->rotation_deg ?? 0),
                'scale' => (float) ($r->scale ?? 1),
                'kind' => $media['kind'],
                'model_url' => $media['model_url'],
                'model_oversize' => ! empty($media['model_oversize']),
                'model_format' => $media['format'],
                'tilt_x' => $r->model_tilt_x !== null ? (float) $r->model_tilt_x : null,
                'tilt_z' => $r->model_tilt_z !== null ? (float) $r->model_tilt_z : null,
                'image_url' => $media['image_url'],
                'doc_url' => $media['doc_url'] ?? null,
                'record_url' => $r->slug ? '/'.$r->slug : null,
            ];
        })->all();
    }

    /**
     * Ordered walkthrough stops for one room (reading order, or saved route when
     * walkthrough_path_json is present on PSIS).
     *
     * @return array<int,array<string,mixed>>
     */
    public function getWalkthroughStops(int $exhibitionSpaceId): array
    {
        $space = $this->getById($exhibitionSpaceId);

        $rows = DB::table('ahg_exhibition_placement as ep')
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('ioi.id', '=', 'ep.information_object_id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug as sl', 'sl.object_id', '=', 'ep.information_object_id')
            ->where('ep.exhibition_space_id', $exhibitionSpaceId)
            ->where(function ($q) {
                $q->whereNull('ep.wall_or_zone')->orWhere('ep.wall_or_zone', '!=', 'corridor');
            })
            ->select(
                'ep.id', 'ep.information_object_id', 'ep.pos_x', 'ep.pos_y',
                'ep.rotation_deg', 'ep.scale', 'ep.size_units_used', 'ep.wall_or_zone',
                'ep.model_tilt_x', 'ep.model_tilt_z', 'ep.wall_u', 'ep.wall_v', 'ep.spotlight', 'ep.display_case', 'ep.on_floor',
                'ep.view_x', 'ep.view_y',
                'ioi.title as title', 'ioi.scope_and_content as description', 'sl.slug as slug'
            )
            ->get();

        $byId = [];
        $stops = [];
        foreach ($rows as $r) {
            $desc = trim(strip_tags((string) ($r->description ?? '')));
            $media = $this->getObjectMedia((int) $r->information_object_id);
            $stop = [
                'id' => (int) $r->id,
                'information_object_id' => (int) $r->information_object_id,
                'title' => $r->title ?: ('#'.$r->information_object_id),
                'description' => mb_strlen($desc) > 400 ? mb_substr($desc, 0, 400).'...' : $desc,
                'pos_x' => $r->pos_x !== null ? (float) $r->pos_x : 0.5,
                'pos_y' => $r->pos_y !== null ? (float) $r->pos_y : 0.5,
                'rotation_deg' => (float) ($r->rotation_deg ?? 0),
                'scale' => (float) ($r->scale ?? 1),
                'size_units_used' => (float) ($r->size_units_used ?? 0),
                'view_x' => $r->view_x !== null ? (float) $r->view_x : null,
                'view_y' => $r->view_y !== null ? (float) $r->view_y : null,
                'wall_or_zone' => $r->wall_or_zone,
                'kind' => $media['kind'],
                'model_url' => $media['model_url'],
                'model_oversize' => ! empty($media['model_oversize']),
                'model_format' => $media['format'],
                'tilt_x' => $r->model_tilt_x !== null ? (float) $r->model_tilt_x : null,
                'tilt_z' => $r->model_tilt_z !== null ? (float) $r->model_tilt_z : null,
                'wall_u' => $r->wall_u !== null ? (float) $r->wall_u : null,
                'wall_v' => $r->wall_v !== null ? (float) $r->wall_v : null,
                'spotlight' => (int) ($r->spotlight ?? 0),
                'display_case' => (int) ($r->display_case ?? 0), 'on_floor' => (int) ($r->on_floor ?? 0),
                'image_url' => $media['image_url'],
                'doc_url' => $media['doc_url'] ?? null,
                'thumb_url' => $media['image_url'] ?? $this->thumbnailUrl((int) $r->information_object_id),
                'record_url' => $r->slug ? '/'.$r->slug : null,
            ];
            $byId[$stop['id']] = $stop;
            $stops[] = $stop;
        }

        $path = [];
        if ($space && isset($space->walkthrough_path_json) && ! empty($space->walkthrough_path_json)) {
            $decoded = json_decode((string) $space->walkthrough_path_json, true);
            if (is_array($decoded)) {
                $path = $decoded;
            }
        }
        if (! empty($path)) {
            $ordered = [];
            foreach ($path as $pid) {
                if (isset($byId[(int) $pid])) {
                    $ordered[] = $byId[(int) $pid];
                    unset($byId[(int) $pid]);
                }
            }
            foreach ($byId as $remaining) {
                $ordered[] = $remaining;
            }

            return $ordered;
        }

        usort($stops, function ($a, $b) {
            if (abs($a['pos_y'] - $b['pos_y']) > 0.08) {
                return $a['pos_y'] <=> $b['pos_y'];
            }

            return $a['pos_x'] <=> $b['pos_x'];
        });

        return $stops;
    }

    // -------- Live conservation state (ahg_exhibition_reading may not exist on PSIS) --------

    /** Latest reading per metric for a space. */
    public function latestReadings(int $spaceId): array
    {
        if (! $this->tableExists('ahg_exhibition_reading')) {
            return [];
        }
        $rows = DB::table('ahg_exhibition_reading')
            ->where('exhibition_space_id', $spaceId)
            ->whereIn('id', function ($q) use ($spaceId) {
                $q->from('ahg_exhibition_reading')->selectRaw('MAX(id)')
                    ->where('exhibition_space_id', $spaceId)->groupBy('metric');
            })->get();
        $out = [];
        foreach ($rows as $r) {
            $out[$r->metric] = ['value' => (float) $r->value, 'at' => (string) $r->recorded_at];
        }

        return $out;
    }

    /**
     * Conservation status from readings vs targets (museum norms). Worst metric wins.
     *
     * @return array{status:string,reasons:array<int,string>}
     */
    public function conservationStatus(object $space, array $readings): array
    {
        $level = 0;
        $reasons = [];
        $bump = function (int $l, string $msg) use (&$level, &$reasons) { $level = max($level, $l); $reasons[] = $msg; };
        if (isset($readings['lux'])) {
            $lux = $readings['lux']['value'];
            $target = $space->lighting_lux_target !== null ? (float) $space->lighting_lux_target : 200.0;
            if ($lux > $target * 1.5) {
                $bump(2, 'Light '.round($lux).' lux well above target '.round($target));
            } elseif ($lux > $target) {
                $bump(1, 'Light '.round($lux).' lux above target '.round($target));
            }
        }
        if (isset($readings['temp_c'])) {
            $t = $readings['temp_c']['value'];
            if ($t < 14 || $t > 26) {
                $bump(2, 'Temperature '.$t.'C out of safe range');
            } elseif ($t < 16 || $t > 24) {
                $bump(1, 'Temperature '.$t.'C outside ideal 16-24C');
            }
        }
        if (isset($readings['humidity'])) {
            $h = $readings['humidity']['value'];
            if ($h < 35 || $h > 65) {
                $bump(2, 'Humidity '.$h.'% out of safe range');
            } elseif ($h < 40 || $h > 60) {
                $bump(1, 'Humidity '.$h.'% outside ideal 40-60%');
            }
        }

        return ['status' => $level === 2 ? 'alert' : ($level === 1 ? 'warn' : 'ok'), 'reasons' => $reasons];
    }

    /** Combined live state for one room (readings + conservation status). */
    public function liveState(object $space): array
    {
        $readings = $this->latestReadings((int) $space->id);
        $cs = $this->conservationStatus($space, $readings);

        return [
            'readings' => $readings,
            'status' => empty($readings) ? 'none' : $cs['status'],
            'reasons' => $cs['reasons'],
            'lux_target' => $space->lighting_lux_target !== null ? (float) $space->lighting_lux_target : null,
        ];
    }

    /**
     * Assemble the building for the walkthrough: all rooms sharing this space's
     * building_id (or just this space when ungrouped), laid out either by explicit
     * plan coordinates or auto-rowed along X. Each room carries its own stops,
     * interior walls, doors, windows, footprint and decor.
     *
     * @return array<string,mixed>
     */
    public function getWalkthroughBuilding(object $space): array
    {
        $rooms = (! empty($space->building_id))
            ? DB::table('ahg_exhibition_space')->where('building_id', $space->building_id)
                ->orderBy('building_seq')->orderBy('id')->get()->all()
            : [$space];

        $planMode = false;
        foreach ($rooms as $r) {
            if ($r->bld_x !== null && $r->bld_y !== null) {
                $planMode = true;
                break;
            }
        }

        $out = [];
        $xCursor = 0.0;
        $maxH = 0.0;
        $minX = null;
        $maxX = null;
        $minZ = null;
        $maxZ = null;
        foreach ($rooms as $r) {
            $dim = $this->roomDims($r);
            if ($planMode && $r->bld_x !== null && $r->bld_y !== null) {
                $x = (float) $r->bld_x;
                $z = (float) $r->bld_y;
            } else {
                $x = $xCursor;
                $z = -$dim['d'] / 2;
                $xCursor += $dim['w'];
            }
            $out[] = [
                'id' => (int) $r->id,
                'name' => $r->name,
                'slug' => $r->slug,
                'w' => $dim['w'], 'd' => $dim['d'], 'h' => $dim['h'],
                'x_offset' => $x, 'z_offset' => $z,
                'rot' => ($planMode && $r->bld_rot !== null) ? (float) $r->bld_rot : 0.0,
                'is_current' => (int) $r->id === (int) $space->id,
                'floorplan' => $r->floorplan_image_path ?? null,
                'ceiling' => $r->ceiling_image_path ?? null,
                'wall_image' => $r->wall_image_path ?? null,
                'floor_image' => $r->floor_image_path ?? null,
                'floor_grout' => (int) ($r->floor_grout ?? 0),
                'floor_tile_m' => (float) ($r->floor_tile_m ?? 2),
                'floor_grout_mm' => (float) ($r->floor_grout_mm ?? 8),
                'wall_color' => $r->wall_color ?? null,
                'wall_colors' => (! empty($r->wall_colors_json) && is_array($wc = json_decode((string) $r->wall_colors_json, true))) ? $wc : new \stdClass,
                'wall_images' => (! empty($r->wall_images_json) && is_array($wi = json_decode((string) $r->wall_images_json, true))) ? $wi : new \stdClass,
                'furniture' => $this->getFurniture((int) $r->id),
                'stops' => $this->getWalkthroughStops((int) $r->id),
                'walls' => $this->getWalls((int) $r->id),
                'doors' => $this->getDoors((int) $r->id),
                'windows' => $this->getWindows((int) $r->id),
                'shape' => $this->getShape((int) $r->id),
                'live' => $this->liveState($r),
                'floor' => (int) ($r->floor_level ?? 0),
                'is_outdoor' => (int) ($r->is_outdoor ?? 0) === 1,
                'scan_shell' => $r->scan_shell_path ?? null,
                'scan_shell_scale' => (float) ($r->scan_shell_scale ?? 1),
                'scan_embed' => $r->scan_embed_url ?? null,
            ];
            $minX = $minX === null ? $x : min($minX, $x);
            $maxX = $maxX === null ? $x + $dim['w'] : max($maxX, $x + $dim['w']);
            $minZ = $minZ === null ? $z : min($minZ, $z);
            $maxZ = $maxZ === null ? $z + $dim['d'] : max($maxZ, $z + $dim['d']);
            $maxH = max($maxH, $dim['h']);
        }

        $hasOutdoor = false;
        foreach ($out as $rm) {
            if (! empty($rm['is_outdoor'])) { $hasOutdoor = true; break; }
        }
        $stairs = $space->stairs_json ?? null;
        if (is_string($stairs)) { $stairs = json_decode($stairs, true); }

        return [
            'rooms' => $out, 'plan_mode' => $planMode,
            'corridor' => $this->getBuildingCorridorObjects($space),
            'min_x' => $minX ?? 0, 'max_x' => $maxX ?? 0, 'min_z' => $minZ ?? 0, 'max_z' => $maxZ ?? 0,
            'total_w' => ($maxX ?? 0) - ($minX ?? 0), 'max_d' => ($maxZ ?? 0) - ($minZ ?? 0), 'max_h' => $maxH,
            'floor_height' => max(4.5, $maxH + 0.5),
            'has_outdoor' => $hasOutdoor,
            'stairs' => is_array($stairs) ? $stairs : [],
        ];
    }
}
