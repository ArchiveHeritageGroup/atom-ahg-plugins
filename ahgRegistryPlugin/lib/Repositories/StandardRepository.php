<?php

namespace AhgRegistry\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

class StandardRepository
{
    protected string $table = 'registry_standard';
    protected string $extensionTable = 'registry_standard_extension';
    protected string $conformanceTable = 'registry_software_standard';
    protected string $guideTable = 'registry_setup_guide';

    // -------------------------------------------------------
    // Standards
    // -------------------------------------------------------

    public function findById(int $id): ?object
    {
        return DB::table($this->table)->where('id', $id)->first();
    }

    public function findBySlug(string $slug): ?object
    {
        return DB::table($this->table)->where('slug', $slug)->first();
    }

    public function findAll(array $params = []): array
    {
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

    public function saveStandard(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        // Handle JSON fields
        if (isset($data['sector_applicability']) && is_array($data['sector_applicability'])) {
            $data['sector_applicability'] = json_encode($data['sector_applicability']);
        }

        if (!empty($data['id'])) {
            $id = (int) $data['id'];
            unset($data['id']);
            $data['updated_at'] = $now;
            DB::table($this->table)->where('id', $id)->update($data);

            return $id;
        }

        $data['created_at'] = $data['created_at'] ?? $now;
        $data['updated_at'] = $data['updated_at'] ?? $now;

        return DB::table($this->table)->insertGetId($data);
    }

    public function deleteStandard(int $id): bool
    {
        return DB::table($this->table)->where('id', $id)->delete() > 0;
    }

    public function getCategories(): array
    {
        return DB::table($this->table)
            ->where('is_active', 1)
            ->distinct()
            ->pluck('category')
            ->all();
    }

    public function countByCategory(): array
    {
        return DB::table($this->table)
            ->where('is_active', 1)
            ->selectRaw('category, COUNT(*) as cnt')
            ->groupBy('category')
            ->orderBy('category', 'asc')
            ->get()
            ->all();
    }

    // -------------------------------------------------------
    // Extensions
    // -------------------------------------------------------

    public function getExtensions(int $standardId): array
    {
        return DB::table($this->extensionTable)
            ->where('standard_id', $standardId)
            ->where('is_active', 1)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->all();
    }

    public function saveExtension(array $data): int
    {
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

    public function deleteExtension(int $id): bool
    {
        return DB::table($this->extensionTable)->where('id', $id)->delete() > 0;
    }

    // -------------------------------------------------------
    // Software–Standard Conformance
    // -------------------------------------------------------

    public function getConformance(int $standardId): array
    {
        return DB::table($this->conformanceTable . ' as ss')
            ->leftJoin('registry_software as rs', 'rs.id', '=', 'ss.software_id')
            ->where('ss.standard_id', $standardId)
            ->select('ss.*', 'rs.name as software_name', 'rs.slug as software_slug')
            ->orderBy('rs.name', 'asc')
            ->get()
            ->all();
    }

    public function getSoftwareConformance(int $softwareId): array
    {
        return DB::table($this->conformanceTable . ' as ss')
            ->leftJoin($this->table . ' as st', 'st.id', '=', 'ss.standard_id')
            ->where('ss.software_id', $softwareId)
            ->select('ss.*', 'st.name as standard_name', 'st.acronym as standard_acronym', 'st.slug as standard_slug')
            ->orderBy('st.name', 'asc')
            ->get()
            ->all();
    }

    public function saveConformance(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        // Upsert: check for existing record
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

    public function deleteConformance(int $softwareId, int $standardId): bool
    {
        return DB::table($this->conformanceTable)
            ->where('software_id', $softwareId)
            ->where('standard_id', $standardId)
            ->delete() > 0;
    }

    // -------------------------------------------------------
    // Setup Guides
    // -------------------------------------------------------

    public function findGuideById(int $id): ?object
    {
        return DB::table($this->guideTable)->where('id', $id)->first();
    }

    public function findGuideBySlug(int $softwareId, string $slug): ?object
    {
        return DB::table($this->guideTable)
            ->where('software_id', $softwareId)
            ->where('slug', $slug)
            ->first();
    }

    public function findGuides(int $softwareId, array $params = []): array
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

    public function saveGuide(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        if (!empty($data['id'])) {
            $id = (int) $data['id'];
            unset($data['id']);
            $data['updated_at'] = $now;
            DB::table($this->guideTable)->where('id', $id)->update($data);

            return $id;
        }

        $data['created_at'] = $data['created_at'] ?? $now;
        $data['updated_at'] = $data['updated_at'] ?? $now;

        return DB::table($this->guideTable)->insertGetId($data);
    }

    public function deleteGuide(int $id): bool
    {
        return DB::table($this->guideTable)->where('id', $id)->delete() > 0;
    }

    public function getGuideCategories(): array
    {
        return DB::table($this->guideTable)
            ->where('is_active', 1)
            ->distinct()
            ->pluck('category')
            ->all();
    }

    public function incrementGuideViews(int $guideId): void
    {
        DB::table($this->guideTable)->where('id', $guideId)->increment('view_count');
    }
}
