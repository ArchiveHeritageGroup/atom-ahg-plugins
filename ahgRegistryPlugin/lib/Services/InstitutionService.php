<?php

namespace AhgRegistry\Services;

use Illuminate\Database\Capsule\Manager as DB;

class InstitutionService
{
    protected string $culture;
    protected $institutionRepo;

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;

        $repoPath = \sfConfig::get('sf_plugins_dir') . '/ahgRegistryPlugin/lib/Repositories/';
        require_once $repoPath . 'InstitutionRepository.php';

        $this->institutionRepo = new \AhgRegistry\Repositories\InstitutionRepository();
    }

    // =========================================================================
    // Browse & View
    // =========================================================================

    /**
     * Paginated browse with filters.
     */
    public function browse(array $params = []): array
    {
        // If search query provided, use full-text search
        $searchTerm = $params['search'] ?? ($params['query'] ?? '');
        if (!empty($searchTerm)) {
            return $this->institutionRepo->search($searchTerm, $params);
        }

        return $this->institutionRepo->findAll($params);
    }

    /**
     * Get institution by slug with all related data.
     */
    public function view(string $slug): ?array
    {
        $institution = $this->institutionRepo->findBySlug($slug);
        if (!$institution) {
            return null;
        }

        $id = $institution->id;

        // Contacts
        $contacts = DB::table('registry_contact')
            ->where('entity_type', 'institution')
            ->where('entity_id', $id)
            ->orderBy('is_primary', 'desc')
            ->orderBy('last_name', 'asc')
            ->get()
            ->all();

        // Instances
        $instances = DB::table('registry_instance')
            ->where('institution_id', $id)
            ->orderBy('instance_type', 'asc')
            ->get()
            ->all();

        // Software used
        $software = DB::table('registry_institution_software as ris')
            ->leftJoin('registry_software as rs', 'rs.id', '=', 'ris.software_id')
            ->where('ris.institution_id', $id)
            ->select('rs.*', 'ris.version_in_use', 'ris.deployment_date', 'ris.notes as usage_notes')
            ->get()
            ->all();

        // Vendor relationships
        $vendors = DB::table('registry_vendor_institution as rvi')
            ->leftJoin('registry_vendor as rv', 'rv.id', '=', 'rvi.vendor_id')
            ->where('rvi.institution_id', $id)
            ->where('rvi.is_active', 1)
            ->select('rv.*', 'rv.name as vendor_name', 'rv.slug as vendor_slug', 'rvi.relationship_type', 'rvi.service_description', 'rvi.start_date', 'rvi.end_date')
            ->get()
            ->all();

        // Tags
        $tags = DB::table('registry_tag')
            ->where('entity_type', 'institution')
            ->where('entity_id', $id)
            ->pluck('tag')
            ->all();

        return [
            'institution' => $institution,
            'contacts' => $contacts,
            'instances' => $instances,
            'software' => $software,
            'vendors' => $vendors,
            'tags' => $tags,
        ];
    }

    // =========================================================================
    // CRUD
    // =========================================================================

    /**
     * Create a new institution.
     */
    public function create(array $data): array
    {
        // Validate required fields
        if (empty($data['name'])) {
            return ['success' => false, 'error' => 'Institution name is required'];
        }
        if (empty($data['institution_type'])) {
            return ['success' => false, 'error' => 'Institution type is required'];
        }

        $data['slug'] = $this->generateSlug($data['name']);
        $data['is_active'] = $data['is_active'] ?? 1;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Handle JSON fields
        foreach (['glam_sectors', 'collection_strengths', 'descriptive_standards'] as $jsonField) {
            if (isset($data[$jsonField]) && is_array($data[$jsonField])) {
                $data[$jsonField] = json_encode($data[$jsonField]);
            }
        }

        $id = $this->institutionRepo->create($data);

        return ['success' => true, 'id' => $id, 'slug' => $data['slug']];
    }

    /**
     * Update an existing institution.
     */
    public function update(int $id, array $data): array
    {
        $institution = $this->institutionRepo->findById($id);
        if (!$institution) {
            return ['success' => false, 'error' => 'Institution not found'];
        }

        // Regenerate slug if name changed
        if (isset($data['name']) && $data['name'] !== $institution->name) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        // Handle JSON fields
        foreach (['glam_sectors', 'collection_strengths', 'descriptive_standards'] as $jsonField) {
            if (isset($data[$jsonField]) && is_array($data[$jsonField])) {
                $data[$jsonField] = json_encode($data[$jsonField]);
            }
        }

        $data['updated_at'] = date('Y-m-d H:i:s');

        $this->institutionRepo->update($id, $data);

        return ['success' => true];
    }

    /**
     * Delete institution and cascade to related records.
     */
    public function delete(int $id): array
    {
        $institution = $this->institutionRepo->findById($id);
        if (!$institution) {
            return ['success' => false, 'error' => 'Institution not found'];
        }

        // Cascade delete contacts
        DB::table('registry_contact')
            ->where('entity_type', 'institution')
            ->where('entity_id', $id)
            ->delete();

        // Cascade delete instances
        $instanceIds = DB::table('registry_instance')
            ->where('institution_id', $id)
            ->pluck('id')
            ->all();

        if (!empty($instanceIds)) {
            DB::table('registry_sync_log')
                ->whereIn('instance_id', $instanceIds)
                ->delete();
            DB::table('registry_instance')
                ->where('institution_id', $id)
                ->delete();
        }

        // Remove vendor relationships
        DB::table('registry_vendor_institution')
            ->where('institution_id', $id)
            ->delete();

        // Remove software assignments
        DB::table('registry_institution_software')
            ->where('institution_id', $id)
            ->delete();

        // Remove tags
        DB::table('registry_tag')
            ->where('entity_type', 'institution')
            ->where('entity_id', $id)
            ->delete();

        // Remove reviews where this institution is the reviewer
        DB::table('registry_review')
            ->where('reviewer_institution_id', $id)
            ->delete();

        // Remove group memberships
        DB::table('registry_user_group_member')
            ->where('institution_id', $id)
            ->delete();

        // Remove attachments
        DB::table('registry_attachment')
            ->where('entity_type', 'institution')
            ->where('entity_id', $id)
            ->delete();

        // Delete institution
        $this->institutionRepo->delete($id);

        return ['success' => true];
    }

    // =========================================================================
    // Verification & Featured
    // =========================================================================

    /**
     * Mark institution as verified.
     */
    public function verify(int $id, int $userId, ?string $notes = null): array
    {
        $institution = $this->institutionRepo->findById($id);
        if (!$institution) {
            return ['success' => false, 'error' => 'Institution not found'];
        }

        $this->institutionRepo->update($id, [
            'is_verified' => 1,
            'verified_at' => date('Y-m-d H:i:s'),
            'verified_by' => $userId,
            'verification_notes' => $notes,
        ]);

        return ['success' => true];
    }

    /**
     * Toggle featured status.
     */
    public function toggleFeatured(int $id): array
    {
        $institution = $this->institutionRepo->findById($id);
        if (!$institution) {
            return ['success' => false, 'error' => 'Institution not found'];
        }

        $newStatus = $institution->is_featured ? 0 : 1;
        $this->institutionRepo->update($id, ['is_featured' => $newStatus]);

        return ['success' => true, 'is_featured' => $newStatus];
    }

    // =========================================================================
    // Map & Stats
    // =========================================================================

    /**
     * Get institutions with lat/lng for map view.
     */
    public function getForMap(array $params = []): array
    {
        return $this->institutionRepo->getForMap($params);
    }

    /**
     * Get dashboard statistics.
     */
    public function getDashboardStats(): array
    {
        $total = DB::table('registry_institution')->where('is_active', 1)->count();
        $verified = DB::table('registry_institution')->where('is_active', 1)->where('is_verified', 1)->count();
        $featured = DB::table('registry_institution')->where('is_active', 1)->where('is_featured', 1)->count();
        $usesAtom = DB::table('registry_institution')->where('is_active', 1)->where('uses_atom', 1)->count();

        $byType = DB::table('registry_institution')
            ->where('is_active', 1)
            ->selectRaw('institution_type, COUNT(*) as cnt')
            ->groupBy('institution_type')
            ->pluck('cnt', 'institution_type')
            ->all();

        $byCountry = DB::table('registry_institution')
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
            'uses_atom' => $usesAtom,
            'by_type' => $byType,
            'by_country' => $byCountry,
        ];
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Generate a unique URL-safe slug from name.
     */
    public function generateSlug(string $name): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        $slug = preg_replace('/-+/', '-', $slug);

        $baseSlug = $slug;
        $counter = 1;
        while ($this->institutionRepo->findBySlug($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
