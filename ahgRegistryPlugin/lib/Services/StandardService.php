<?php

namespace AhgRegistry\Services;

use Illuminate\Database\Capsule\Manager as DB;

class StandardService
{
    protected string $culture;
    protected string $table = 'registry_standard';
    protected string $extensionTable = 'registry_standard_extension';
    protected string $conformanceTable = 'registry_software_standard';
    protected string $guideTable = 'registry_setup_guide';

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
    }

    // =========================================================================
    // Browse & View
    // =========================================================================

    /**
     * Paginated browse with filters.
     */
    public function browse(array $params = []): array
    {
        $query = DB::table($this->table)
            ->selectRaw('registry_standard.*, (SELECT COUNT(*) FROM registry_standard_extension WHERE registry_standard_extension.standard_id = registry_standard.id AND registry_standard_extension.is_active = 1) as extension_count')
            ->where('is_active', 1);

        if (!empty($params['category'])) {
            $query->where('category', $params['category']);
        }
        if (!empty($params['sector'])) {
            $query->whereRaw("JSON_CONTAINS(sector_applicability, ?)", ['"' . $params['sector'] . '"']);
        }
        if (isset($params['is_featured']) && $params['is_featured'] !== '') {
            $query->where('is_featured', (int) $params['is_featured']);
        }

        $searchTerm = $params['search'] ?? ($params['query'] ?? '');
        $usedLikeFallback = false;
        if (!empty($searchTerm)) {
            $query->whereRaw("MATCH(name, description) AGAINST(? IN BOOLEAN MODE)", [$searchTerm]);
        }

        $total = $query->count();

        // If FULLTEXT returned 0, fall back to LIKE search
        if ($total === 0 && !empty($searchTerm)) {
            $likeTerm = '%' . $searchTerm . '%';
            $query = DB::table($this->table)->where('is_active', 1);

            if (!empty($params['category'])) {
                $query->where('category', $params['category']);
            }
            if (!empty($params['sector'])) {
                $query->whereRaw("JSON_CONTAINS(sector_applicability, ?)", ['"' . $params['sector'] . '"']);
            }
            if (isset($params['is_featured']) && $params['is_featured'] !== '') {
                $query->where('is_featured', (int) $params['is_featured']);
            }

            $query->where(function ($q) use ($likeTerm) {
                $q->where('name', 'LIKE', $likeTerm)
                  ->orWhere('description', 'LIKE', $likeTerm)
                  ->orWhere('acronym', 'LIKE', $likeTerm)
                  ->orWhere('issuing_body', 'LIKE', $likeTerm);
            });

            $total = $query->count();
            $usedLikeFallback = true;
        }

        $sort = $params['sort'] ?? 'sort_order';
        $direction = $params['direction'] ?? 'asc';
        $limit = $params['limit'] ?? 20;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        if (!empty($searchTerm) && $sort === 'relevance' && !$usedLikeFallback) {
            $query->orderByRaw("MATCH(name, description) AGAINST(? IN BOOLEAN MODE) DESC", [$searchTerm]);
        } else {
            $query->orderBy($sort, $direction);
        }

        $items = $query->limit($limit)->offset($offset)->get();

        return ['items' => $items, 'total' => $total, 'page' => (int) $page];
    }

    /**
     * Get standard by slug with all related data.
     */
    public function getStandard(string $slug): ?array
    {
        $standard = DB::table($this->table)->where('slug', $slug)->first();
        if (!$standard) {
            return null;
        }

        $id = $standard->id;

        // Extensions
        $extensions = DB::table($this->extensionTable)
            ->where('standard_id', $id)
            ->where('is_active', 1)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->all();

        // Software conformance
        $conformance = DB::table($this->conformanceTable . ' as ss')
            ->leftJoin('registry_software as rs', 'rs.id', '=', 'ss.software_id')
            ->where('ss.standard_id', $id)
            ->select('ss.*', 'rs.name as software_name', 'rs.slug as software_slug')
            ->orderBy('rs.name', 'asc')
            ->get()
            ->all();

        // Tags
        $tags = DB::table('registry_tag')
            ->where('entity_type', 'standard')
            ->where('entity_id', $id)
            ->pluck('tag')
            ->all();

        return [
            'standard' => $standard,
            'extensions' => $extensions,
            'conformance' => $conformance,
            'tags' => $tags,
        ];
    }

    // =========================================================================
    // Guides
    // =========================================================================

    /**
     * Paginated browse of setup guides for a software.
     */
    public function browseGuides(int $softwareId, array $params = []): array
    {
        $query = DB::table($this->guideTable)
            ->where('software_id', $softwareId)
            ->where('is_active', 1);

        if (!empty($params['category'])) {
            $query->where('category', $params['category']);
        }

        $total = $query->count();

        $sort = $params['sort'] ?? 'sort_order';
        $direction = $params['direction'] ?? 'asc';
        $limit = $params['limit'] ?? 20;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $items = $query->orderBy($sort, $direction)
                       ->limit($limit)
                       ->offset($offset)
                       ->get();

        return ['items' => $items, 'total' => $total, 'page' => (int) $page];
    }

    /**
     * Get a single guide by software ID and slug.
     */
    public function getGuide(int $softwareId, string $slug): ?object
    {
        $guide = DB::table($this->guideTable)
            ->where('software_id', $softwareId)
            ->where('slug', $slug)
            ->first();

        if ($guide) {
            DB::table($this->guideTable)->where('id', $guide->id)->increment('view_count');
        }

        return $guide;
    }

    // =========================================================================
    // CRUD — Standards
    // =========================================================================

    /**
     * Create or update a standard.
     */
    public function saveStandard(array $data): int
    {
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Standard name is required');
        }

        // Handle JSON fields
        if (isset($data['sector_applicability']) && is_array($data['sector_applicability'])) {
            $data['sector_applicability'] = json_encode($data['sector_applicability']);
        }

        $now = date('Y-m-d H:i:s');

        if (!empty($data['id'])) {
            $id = (int) $data['id'];
            $existing = DB::table($this->table)->where('id', $id)->first();
            if (!$existing) {
                throw new \RuntimeException('Standard not found');
            }

            if (isset($data['name']) && $data['name'] !== $existing->name) {
                $data['slug'] = $this->generateSlug($data['name']);
            }

            unset($data['id']);
            $data['updated_at'] = $now;
            DB::table($this->table)->where('id', $id)->update($data);

            return $id;
        }

        $data['slug'] = $data['slug'] ?? $this->generateSlug($data['name']);
        $data['is_active'] = $data['is_active'] ?? 1;
        $data['created_at'] = $data['created_at'] ?? $now;
        $data['updated_at'] = $data['updated_at'] ?? $now;

        return DB::table($this->table)->insertGetId($data);
    }

    /**
     * Delete a standard and cascade to related records.
     */
    public function deleteStandard(int $id): bool
    {
        $standard = DB::table($this->table)->where('id', $id)->first();
        if (!$standard) {
            return false;
        }

        // Remove extensions (FK CASCADE handles this, but be explicit)
        DB::table($this->extensionTable)->where('standard_id', $id)->delete();

        // Remove conformance records
        DB::table($this->conformanceTable)->where('standard_id', $id)->delete();

        // Remove tags
        DB::table('registry_tag')
            ->where('entity_type', 'standard')
            ->where('entity_id', $id)
            ->delete();

        return DB::table($this->table)->where('id', $id)->delete() > 0;
    }

    // =========================================================================
    // CRUD — Extensions
    // =========================================================================

    /**
     * Create or update an extension.
     */
    public function saveExtension(array $data): int
    {
        if (empty($data['standard_id'])) {
            throw new \InvalidArgumentException('Standard ID is required');
        }
        if (empty($data['title'])) {
            throw new \InvalidArgumentException('Extension title is required');
        }

        $now = date('Y-m-d H:i:s');

        if (!empty($data['id'])) {
            $id = (int) $data['id'];
            unset($data['id']);
            $data['updated_at'] = $now;
            DB::table($this->extensionTable)->where('id', $id)->update($data);

            return $id;
        }

        $data['created_at'] = $data['created_at'] ?? $now;
        $data['updated_at'] = $data['updated_at'] ?? $now;

        return DB::table($this->extensionTable)->insertGetId($data);
    }

    /**
     * Delete an extension.
     */
    public function deleteExtension(int $id): bool
    {
        return DB::table($this->extensionTable)->where('id', $id)->delete() > 0;
    }

    // =========================================================================
    // CRUD — Guides
    // =========================================================================

    /**
     * Create or update a setup guide.
     */
    public function saveGuide(array $data): int
    {
        if (empty($data['software_id'])) {
            throw new \InvalidArgumentException('Software ID is required');
        }
        if (empty($data['title'])) {
            throw new \InvalidArgumentException('Guide title is required');
        }

        $now = date('Y-m-d H:i:s');

        if (!empty($data['id'])) {
            $id = (int) $data['id'];
            $existing = DB::table($this->guideTable)->where('id', $id)->first();
            if (!$existing) {
                throw new \RuntimeException('Guide not found');
            }

            if (isset($data['title']) && $data['title'] !== $existing->title) {
                $data['slug'] = $this->generateSlug($data['title'], $this->guideTable);
            }

            unset($data['id']);
            $data['updated_at'] = $now;
            DB::table($this->guideTable)->where('id', $id)->update($data);

            return $id;
        }

        $data['slug'] = $data['slug'] ?? $this->generateSlug($data['title'], $this->guideTable);
        $data['is_active'] = $data['is_active'] ?? 1;
        $data['created_at'] = $data['created_at'] ?? $now;
        $data['updated_at'] = $data['updated_at'] ?? $now;

        return DB::table($this->guideTable)->insertGetId($data);
    }

    /**
     * Delete a setup guide.
     */
    public function deleteGuide(int $id): bool
    {
        return DB::table($this->guideTable)->where('id', $id)->delete() > 0;
    }

    // =========================================================================
    // CRUD — Conformance
    // =========================================================================

    /**
     * Upsert a software-standard conformance record.
     */
    public function saveConformance(array $data): int
    {
        if (empty($data['software_id']) || empty($data['standard_id'])) {
            throw new \InvalidArgumentException('Software ID and Standard ID are required');
        }

        $now = date('Y-m-d H:i:s');

        // Upsert: check for existing record by unique key
        $existing = DB::table($this->conformanceTable)
            ->where('software_id', $data['software_id'])
            ->where('standard_id', $data['standard_id'])
            ->first();

        if ($existing) {
            $updateData = $data;
            unset($updateData['software_id'], $updateData['standard_id']);
            $updateData['updated_at'] = $now;
            DB::table($this->conformanceTable)->where('id', $existing->id)->update($updateData);

            return $existing->id;
        }

        $data['created_at'] = $data['created_at'] ?? $now;
        $data['updated_at'] = $data['updated_at'] ?? $now;

        return DB::table($this->conformanceTable)->insertGetId($data);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Generate a unique URL-safe slug.
     */
    public function generateSlug(string $name, string $table = 'registry_standard'): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        $slug = preg_replace('/-+/', '-', $slug);

        $baseSlug = $slug;
        $counter = 1;
        while (DB::table($table)->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
