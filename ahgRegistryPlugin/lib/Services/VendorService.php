<?php

namespace AhgRegistry\Services;

use Illuminate\Database\Capsule\Manager as DB;

class VendorService
{
    protected string $culture;
    protected string $table = 'registry_vendor';

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

        if (!empty($params['type'])) {
            $query->whereRaw("JSON_CONTAINS(vendor_type, ?)", [json_encode($params['type'])]);
        }
        if (!empty($params['country'])) {
            $query->where('country', $params['country']);
        }
        if (!empty($params['specialization'])) {
            $query->whereRaw("JSON_CONTAINS(specializations, ?)", ['"' . $params['specialization'] . '"']);
        }
        if (isset($params['is_verified']) && $params['is_verified'] !== '') {
            $query->where('is_verified', (int) $params['is_verified']);
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

            if (!empty($params['type'])) {
                $query->whereRaw("JSON_CONTAINS(vendor_type, ?)", [json_encode($params['type'])]);
            }
            if (!empty($params['country'])) {
                $query->where('country', $params['country']);
            }
            if (!empty($params['specialization'])) {
                $query->whereRaw("JSON_CONTAINS(specializations, ?)", ['"' . $params['specialization'] . '"']);
            }
            if (isset($params['is_verified']) && $params['is_verified'] !== '') {
                $query->where('is_verified', (int) $params['is_verified']);
            }
            if (isset($params['is_featured']) && $params['is_featured'] !== '') {
                $query->where('is_featured', (int) $params['is_featured']);
            }

            $query->where(function ($q) use ($likeTerm) {
                $q->where('name', 'LIKE', $likeTerm)
                  ->orWhere('description', 'LIKE', $likeTerm)
                  ->orWhere('country', 'LIKE', $likeTerm);
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
     * Get vendor by slug with all related data.
     */
    public function view(string $slug): ?array
    {
        $vendor = DB::table($this->table)->where('slug', $slug)->first();
        if (!$vendor) {
            return null;
        }

        $id = $vendor->id;

        // Contacts
        $contacts = DB::table('registry_contact')
            ->where('entity_type', 'vendor')
            ->where('entity_id', $id)
            ->orderBy('is_primary', 'desc')
            ->orderBy('last_name', 'asc')
            ->get()
            ->all();

        // Client institutions
        $clients = DB::table('registry_vendor_institution as rvi')
            ->leftJoin('registry_institution as ri', 'ri.id', '=', 'rvi.institution_id')
            ->where('rvi.vendor_id', $id)
            ->where('rvi.is_active', 1)
            ->where('rvi.is_public', 1)
            ->select('ri.*', 'rvi.relationship_type', 'rvi.service_description', 'rvi.start_date', 'rvi.end_date')
            ->get()
            ->all();

        // Software products
        $software = DB::table('registry_software')
            ->where('vendor_id', $id)
            ->where('is_active', 1)
            ->orderBy('name', 'asc')
            ->get()
            ->all();

        // Reviews
        $reviews = DB::table('registry_review')
            ->where('entity_type', 'vendor')
            ->where('entity_id', $id)
            ->where('is_visible', 1)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();

        // Tags
        $tags = DB::table('registry_tag')
            ->where('entity_type', 'vendor')
            ->where('entity_id', $id)
            ->pluck('tag')
            ->all();

        return [
            'vendor' => $vendor,
            'contacts' => $contacts,
            'clients' => $clients,
            'software' => $software,
            'reviews' => $reviews,
            'tags' => $tags,
        ];
    }

    // =========================================================================
    // CRUD
    // =========================================================================

    /**
     * Create a new vendor.
     */
    public function create(array $data): array
    {
        if (empty($data['name'])) {
            return ['success' => false, 'error' => 'Vendor name is required'];
        }
        $vtArr = is_string($data['vendor_type'] ?? '') ? json_decode($data['vendor_type'], true) : ($data['vendor_type'] ?? []);
        if (empty($vtArr)) {
            return ['success' => false, 'error' => 'At least one vendor type is required'];
        }

        $data['slug'] = $this->generateSlug($data['name']);
        $data['is_active'] = $data['is_active'] ?? 1;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Handle JSON fields
        foreach (['specializations', 'service_regions', 'languages', 'certifications'] as $jsonField) {
            if (isset($data[$jsonField]) && is_array($data[$jsonField])) {
                $data[$jsonField] = json_encode($data[$jsonField]);
            }
        }

        $id = DB::table($this->table)->insertGetId($data);

        return ['success' => true, 'id' => $id, 'slug' => $data['slug']];
    }

    /**
     * Update an existing vendor.
     */
    public function update(int $id, array $data): array
    {
        $vendor = DB::table($this->table)->where('id', $id)->first();
        if (!$vendor) {
            return ['success' => false, 'error' => 'Vendor not found'];
        }

        if (isset($data['name']) && $data['name'] !== $vendor->name) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        foreach (['specializations', 'service_regions', 'languages', 'certifications'] as $jsonField) {
            if (isset($data[$jsonField]) && is_array($data[$jsonField])) {
                $data[$jsonField] = json_encode($data[$jsonField]);
            }
        }

        $data['updated_at'] = date('Y-m-d H:i:s');

        DB::table($this->table)->where('id', $id)->update($data);

        return ['success' => true];
    }

    /**
     * Delete vendor and cascade to related records.
     */
    public function delete(int $id): array
    {
        $vendor = DB::table($this->table)->where('id', $id)->first();
        if (!$vendor) {
            return ['success' => false, 'error' => 'Vendor not found'];
        }

        // Cascade delete contacts
        DB::table('registry_contact')
            ->where('entity_type', 'vendor')
            ->where('entity_id', $id)
            ->delete();

        // Remove institution relationships
        DB::table('registry_vendor_institution')
            ->where('vendor_id', $id)
            ->delete();

        // Unlink software (set vendor_id to null)
        DB::table('registry_software')
            ->where('vendor_id', $id)
            ->update(['vendor_id' => null]);

        // Unlink instances
        DB::table('registry_instance')
            ->where('hosting_vendor_id', $id)
            ->update(['hosting_vendor_id' => null]);
        DB::table('registry_instance')
            ->where('maintained_by_vendor_id', $id)
            ->update(['maintained_by_vendor_id' => null]);

        // Remove reviews
        DB::table('registry_review')
            ->where('entity_type', 'vendor')
            ->where('entity_id', $id)
            ->delete();

        // Remove tags
        DB::table('registry_tag')
            ->where('entity_type', 'vendor')
            ->where('entity_id', $id)
            ->delete();

        // Remove attachments
        DB::table('registry_attachment')
            ->where('entity_type', 'vendor')
            ->where('entity_id', $id)
            ->delete();

        DB::table($this->table)->where('id', $id)->delete();

        return ['success' => true];
    }

    // =========================================================================
    // Verification & Featured
    // =========================================================================

    /**
     * Mark vendor as verified.
     */
    public function verify(int $id, int $userId, ?string $notes = null): array
    {
        $vendor = DB::table($this->table)->where('id', $id)->first();
        if (!$vendor) {
            return ['success' => false, 'error' => 'Vendor not found'];
        }

        DB::table($this->table)->where('id', $id)->update([
            'is_verified' => 1,
            'verified_at' => date('Y-m-d H:i:s'),
            'verified_by' => $userId,
            'verification_notes' => $notes,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true];
    }

    /**
     * Toggle featured status.
     */
    public function toggleFeatured(int $id): array
    {
        $vendor = DB::table($this->table)->where('id', $id)->first();
        if (!$vendor) {
            return ['success' => false, 'error' => 'Vendor not found'];
        }

        $newStatus = $vendor->is_featured ? 0 : 1;
        DB::table($this->table)->where('id', $id)->update([
            'is_featured' => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true, 'is_featured' => $newStatus];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get institutions serviced by this vendor.
     */
    public function getClients(int $vendorId): array
    {
        return DB::table('registry_vendor_institution as rvi')
            ->leftJoin('registry_institution as ri', 'ri.id', '=', 'rvi.institution_id')
            ->where('rvi.vendor_id', $vendorId)
            ->where('rvi.is_active', 1)
            ->select('ri.*', 'rvi.relationship_type', 'rvi.service_description', 'rvi.start_date', 'rvi.end_date')
            ->orderBy('ri.name', 'asc')
            ->get()
            ->all();
    }

    // =========================================================================
    // Stats
    // =========================================================================

    /**
     * Get dashboard statistics for vendors.
     */
    public function getDashboardStats(): array
    {
        $total = DB::table($this->table)->where('is_active', 1)->count();
        $verified = DB::table($this->table)->where('is_active', 1)->where('is_verified', 1)->count();
        $featured = DB::table($this->table)->where('is_active', 1)->where('is_featured', 1)->count();

        // Aggregate vendor types from JSON arrays
        $byType = [];
        $vendors = DB::table($this->table)->where('is_active', 1)->select('vendor_type')->get();
        foreach ($vendors as $v) {
            $types = is_string($v->vendor_type) ? (json_decode($v->vendor_type, true) ?: []) : [];
            foreach ($types as $t) {
                $byType[$t] = ($byType[$t] ?? 0) + 1;
            }
        }
        arsort($byType);

        $byCountry = DB::table($this->table)
            ->where('is_active', 1)
            ->whereNotNull('country')
            ->selectRaw('country, COUNT(*) as cnt')
            ->groupBy('country')
            ->orderBy('cnt', 'desc')
            ->limit(20)
            ->pluck('cnt', 'country')
            ->all();

        return [
            'total' => $total,
            'verified' => $verified,
            'featured' => $featured,
            'by_type' => $byType,
            'by_country' => $byCountry,
        ];
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Generate a unique URL-safe slug.
     */
    public function generateSlug(string $name): string
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
