<?php

namespace AhgRegistry\Services;

use Illuminate\Database\Capsule\Manager as DB;

class SoftwareService
{
    protected string $culture;
    protected string $table = 'registry_software';
    protected string $releaseTable = 'registry_software_release';

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
        $query = DB::table($this->table)->where('is_active', 1);

        if (!empty($params['category'])) {
            $query->where('category', $params['category']);
        }
        if (!empty($params['vendor'])) {
            $query->where('vendor_id', (int) $params['vendor']);
        }
        if (!empty($params['license'])) {
            $query->where('license', $params['license']);
        }
        if (!empty($params['pricing'])) {
            $query->where('pricing_model', $params['pricing']);
        }
        if (!empty($params['sector'])) {
            $query->whereRaw("JSON_CONTAINS(glam_sectors, ?)", ['"' . $params['sector'] . '"']);
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
            if (!empty($params['vendor'])) {
                $query->where('vendor_id', (int) $params['vendor']);
            }
            if (!empty($params['license'])) {
                $query->where('license', $params['license']);
            }
            if (!empty($params['pricing'])) {
                $query->where('pricing_model', $params['pricing']);
            }
            if (!empty($params['sector'])) {
                $query->whereRaw("JSON_CONTAINS(glam_sectors, ?)", ['"' . $params['sector'] . '"']);
            }
            if (isset($params['is_featured']) && $params['is_featured'] !== '') {
                $query->where('is_featured', (int) $params['is_featured']);
            }

            $query->where(function ($q) use ($likeTerm) {
                $q->where('name', 'LIKE', $likeTerm)
                  ->orWhere('description', 'LIKE', $likeTerm)
                  ->orWhere('category', 'LIKE', $likeTerm);
            });

            $total = $query->count();
            $usedLikeFallback = true;
        }

        $sort = $params['sort'] ?? 'name';
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

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Get software by slug with all related data.
     */
    public function view(string $slug): ?array
    {
        $software = DB::table($this->table)->where('slug', $slug)->first();
        if (!$software) {
            return null;
        }

        $id = $software->id;

        // Releases
        $releases = DB::table($this->releaseTable)
            ->where('software_id', $id)
            ->orderBy('released_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();

        // Vendor
        $vendor = null;
        if ($software->vendor_id) {
            $vendor = DB::table('registry_vendor')
                ->where('id', $software->vendor_id)
                ->first();
        }

        // Institutions using this software
        $institutions = DB::table('registry_institution_software as ris')
            ->leftJoin('registry_institution as ri', 'ri.id', '=', 'ris.institution_id')
            ->where('ris.software_id', $id)
            ->where('ri.is_active', 1)
            ->select('ri.*', 'ris.version_in_use', 'ris.deployment_date')
            ->orderBy('ri.name', 'asc')
            ->get()
            ->all();

        // Reviews
        $reviews = DB::table('registry_review')
            ->where('entity_type', 'software')
            ->where('entity_id', $id)
            ->where('is_visible', 1)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();

        // Tags
        $tags = DB::table('registry_tag')
            ->where('entity_type', 'software')
            ->where('entity_id', $id)
            ->pluck('tag')
            ->all();

        return [
            'software' => $software,
            'releases' => $releases,
            'vendor' => $vendor,
            'institutions' => $institutions,
            'reviews' => $reviews,
            'tags' => $tags,
        ];
    }

    // =========================================================================
    // CRUD
    // =========================================================================

    /**
     * Create a new software entry.
     */
    public function create(array $data): array
    {
        if (empty($data['name'])) {
            return ['success' => false, 'error' => 'Software name is required'];
        }
        if (empty($data['category'])) {
            return ['success' => false, 'error' => 'Category is required'];
        }

        $data['slug'] = $this->generateSlug($data['name']);
        $data['is_active'] = $data['is_active'] ?? 1;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Handle JSON fields
        foreach (['supported_platforms', 'glam_sectors', 'standards_supported', 'languages'] as $jsonField) {
            if (isset($data[$jsonField]) && is_array($data[$jsonField])) {
                $data[$jsonField] = json_encode($data[$jsonField]);
            }
        }

        $id = DB::table($this->table)->insertGetId($data);

        return ['success' => true, 'id' => $id, 'slug' => $data['slug']];
    }

    /**
     * Update an existing software entry.
     */
    public function update(int $id, array $data): array
    {
        $software = DB::table($this->table)->where('id', $id)->first();
        if (!$software) {
            return ['success' => false, 'error' => 'Software not found'];
        }

        if (isset($data['name']) && $data['name'] !== $software->name) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        foreach (['supported_platforms', 'glam_sectors', 'standards_supported', 'languages'] as $jsonField) {
            if (isset($data[$jsonField]) && is_array($data[$jsonField])) {
                $data[$jsonField] = json_encode($data[$jsonField]);
            }
        }

        $data['updated_at'] = date('Y-m-d H:i:s');

        DB::table($this->table)->where('id', $id)->update($data);

        return ['success' => true];
    }

    /**
     * Delete software and cascade to related records.
     */
    public function delete(int $id): array
    {
        $software = DB::table($this->table)->where('id', $id)->first();
        if (!$software) {
            return ['success' => false, 'error' => 'Software not found'];
        }

        // Remove releases
        DB::table($this->releaseTable)->where('software_id', $id)->delete();

        // Remove institution assignments
        DB::table('registry_institution_software')->where('software_id', $id)->delete();

        // Remove reviews
        DB::table('registry_review')
            ->where('entity_type', 'software')
            ->where('entity_id', $id)
            ->delete();

        // Remove tags
        DB::table('registry_tag')
            ->where('entity_type', 'software')
            ->where('entity_id', $id)
            ->delete();

        // Remove attachments
        DB::table('registry_attachment')
            ->where('entity_type', 'software')
            ->where('entity_id', $id)
            ->delete();

        DB::table($this->table)->where('id', $id)->delete();

        return ['success' => true];
    }

    // =========================================================================
    // Featured
    // =========================================================================

    /**
     * Toggle featured status.
     */
    public function toggleFeatured(int $id): array
    {
        $software = DB::table($this->table)->where('id', $id)->first();
        if (!$software) {
            return ['success' => false, 'error' => 'Software not found'];
        }

        $newStatus = $software->is_featured ? 0 : 1;
        DB::table($this->table)->where('id', $id)->update([
            'is_featured' => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true, 'is_featured' => $newStatus];
    }

    // =========================================================================
    // Releases
    // =========================================================================

    /**
     * Get the latest stable release for a software.
     */
    public function getLatestVersion(int $softwareId): ?object
    {
        return DB::table($this->releaseTable)
            ->where('software_id', $softwareId)
            ->where('is_stable', 1)
            ->where('is_latest', 1)
            ->first();
    }

    /**
     * Get all releases for a software ordered by date.
     */
    public function getReleases(int $softwareId): array
    {
        return DB::table($this->releaseTable)
            ->where('software_id', $softwareId)
            ->orderBy('released_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    /**
     * Create a new release, set is_latest, and update software latest_version.
     */
    public function createRelease(int $softwareId, array $data): array
    {
        $software = DB::table($this->table)->where('id', $softwareId)->first();
        if (!$software) {
            return ['success' => false, 'error' => 'Software not found'];
        }

        if (empty($data['version'])) {
            return ['success' => false, 'error' => 'Version is required'];
        }

        // Check for duplicate version
        $existing = DB::table($this->releaseTable)
            ->where('software_id', $softwareId)
            ->where('version', $data['version'])
            ->first();
        if ($existing) {
            return ['success' => false, 'error' => 'Version ' . $data['version'] . ' already exists'];
        }

        // Unset previous is_latest if this is stable
        $isStable = $data['is_stable'] ?? 1;
        if ($isStable) {
            DB::table($this->releaseTable)
                ->where('software_id', $softwareId)
                ->where('is_latest', 1)
                ->update(['is_latest' => 0]);
        }

        $data['software_id'] = $softwareId;
        $data['is_latest'] = $isStable ? 1 : 0;
        $data['released_at'] = $data['released_at'] ?? date('Y-m-d H:i:s');
        $data['created_at'] = date('Y-m-d H:i:s');

        $releaseId = DB::table($this->releaseTable)->insertGetId($data);

        // Update software latest_version
        if ($isStable) {
            DB::table($this->table)->where('id', $softwareId)->update([
                'latest_version' => $data['version'],
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return ['success' => true, 'id' => $releaseId];
    }

    /**
     * Handle uploaded package for software: store file, compute checksum.
     */
    public function handleUpload(int $softwareId, array $fileData): array
    {
        $software = DB::table($this->table)->where('id', $softwareId)->first();
        if (!$software) {
            return ['success' => false, 'error' => 'Software not found'];
        }

        if (empty($fileData['tmp_name']) || empty($fileData['name'])) {
            return ['success' => false, 'error' => 'No file uploaded'];
        }

        $uploadDir = \sfConfig::get('sf_upload_dir', '/usr/share/nginx/archive/uploads')
            . '/registry/software/' . $softwareId;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileData['name']);
        $destPath = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($fileData['tmp_name'], $destPath)) {
            return ['success' => false, 'error' => 'Failed to move uploaded file'];
        }

        $checksum = hash_file('sha256', $destPath);
        $fileSize = filesize($destPath);

        DB::table($this->table)->where('id', $softwareId)->update([
            'upload_path' => $destPath,
            'upload_filename' => $filename,
            'upload_size_bytes' => $fileSize,
            'upload_checksum' => $checksum,
            'is_internal' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'success' => true,
            'path' => $destPath,
            'filename' => $filename,
            'size' => $fileSize,
            'checksum' => $checksum,
        ];
    }

    /**
     * Increment download count for a release.
     */
    public function incrementDownloadCount(int $releaseId): void
    {
        DB::table($this->releaseTable)
            ->where('id', $releaseId)
            ->increment('download_count');

        // Also increment total on software record
        $release = DB::table($this->releaseTable)->where('id', $releaseId)->first();
        if ($release) {
            DB::table($this->table)
                ->where('id', $release->software_id)
                ->increment('download_count');
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Generate a unique URL-safe slug.
     */
    private function generateSlug(string $name): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        $slug = preg_replace('/-+/', '-', $slug);

        $baseSlug = $slug;
        $counter = 1;
        while (DB::table($this->table)->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
