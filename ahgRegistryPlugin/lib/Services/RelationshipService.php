<?php

namespace AhgRegistry\Services;

use Illuminate\Database\Capsule\Manager as DB;

class RelationshipService
{
    protected string $culture;
    protected string $vendorInstTable = 'registry_vendor_institution';
    protected string $instSoftTable = 'registry_institution_software';

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
    }

    // =========================================================================
    // Vendor-Institution Relationships
    // =========================================================================

    /**
     * Get client institutions for a vendor.
     */
    public function getVendorClients(int $vendorId): array
    {
        return DB::table("{$this->vendorInstTable} as rvi")
            ->leftJoin('registry_institution as ri', 'ri.id', '=', 'rvi.institution_id')
            ->where('rvi.vendor_id', $vendorId)
            ->where('rvi.is_active', 1)
            ->select(
                'rvi.id as relationship_id',
                'ri.id as institution_id',
                'ri.name as institution_name',
                'ri.slug as institution_slug',
                'ri.name',
                'ri.slug',
                'ri.institution_type',
                'ri.city',
                'ri.country',
                'ri.logo_path',
                'ri.is_verified',
                'rvi.relationship_type',
                'rvi.service_description',
                'rvi.start_date',
                'rvi.end_date',
                'rvi.is_active',
                'rvi.is_public'
            )
            ->orderBy('ri.name', 'asc')
            ->get()
            ->all();
    }

    /**
     * Get vendors for an institution.
     */
    public function getInstitutionVendors(int $institutionId): array
    {
        return DB::table("{$this->vendorInstTable} as rvi")
            ->leftJoin('registry_vendor as rv', 'rv.id', '=', 'rvi.vendor_id')
            ->where('rvi.institution_id', $institutionId)
            ->where('rvi.is_active', 1)
            ->select(
                'rvi.id as relationship_id',
                'rv.id as vendor_id',
                'rv.name as vendor_name',
                'rv.slug as vendor_slug',
                'rv.name',
                'rv.slug',
                'rv.vendor_type',
                'rv.city',
                'rv.country',
                'rv.logo_path',
                'rv.is_verified',
                'rvi.relationship_type',
                'rvi.service_description',
                'rvi.start_date',
                'rvi.end_date',
                'rvi.is_active',
                'rvi.is_public'
            )
            ->orderBy('rv.name', 'asc')
            ->get()
            ->all();
    }

    /**
     * Create a vendor-institution relationship.
     */
    public function createVendorRelationship(array $data): array
    {
        if (empty($data['vendor_id']) || empty($data['institution_id']) || empty($data['relationship_type'])) {
            return ['success' => false, 'error' => 'Vendor ID, institution ID, and relationship type are required'];
        }

        // Check for duplicate
        $existing = DB::table($this->vendorInstTable)
            ->where('vendor_id', $data['vendor_id'])
            ->where('institution_id', $data['institution_id'])
            ->where('relationship_type', $data['relationship_type'])
            ->first();

        if ($existing) {
            return ['success' => false, 'error' => 'This vendor-institution relationship already exists'];
        }

        $data['is_active'] = $data['is_active'] ?? 1;
        $data['is_public'] = $data['is_public'] ?? 1;
        $data['created_at'] = date('Y-m-d H:i:s');

        $id = DB::table($this->vendorInstTable)->insertGetId($data);

        // Recalculate vendor client count
        $this->updateClientCount($data['vendor_id']);

        return ['success' => true, 'id' => $id];
    }

    /**
     * Remove a vendor-institution relationship.
     */
    public function removeVendorRelationship(int $id): array
    {
        $rel = DB::table($this->vendorInstTable)->where('id', $id)->first();
        if (!$rel) {
            return ['success' => false, 'error' => 'Relationship not found'];
        }

        DB::table($this->vendorInstTable)->where('id', $id)->delete();

        // Recalculate vendor client count
        $this->updateClientCount($rel->vendor_id);

        return ['success' => true];
    }

    // =========================================================================
    // Institution-Software Relationships
    // =========================================================================

    /**
     * Get software used by an institution.
     */
    public function getInstitutionSoftware(int $institutionId): array
    {
        return DB::table("{$this->instSoftTable} as ris")
            ->leftJoin('registry_software as rs', 'rs.id', '=', 'ris.software_id')
            ->where('ris.institution_id', $institutionId)
            ->select(
                'ris.id as assignment_id',
                'rs.id as software_id',
                'rs.name',
                'rs.slug',
                'rs.category',
                'rs.logo_path',
                'rs.latest_version',
                'rs.pricing_model',
                'ris.version_in_use',
                'ris.deployment_date',
                'ris.notes',
                'ris.instance_id'
            )
            ->orderBy('rs.name', 'asc')
            ->get()
            ->all();
    }

    /**
     * Get institutions using a particular software.
     */
    public function getSoftwareInstitutions(int $softwareId): array
    {
        return DB::table("{$this->instSoftTable} as ris")
            ->leftJoin('registry_institution as ri', 'ri.id', '=', 'ris.institution_id')
            ->where('ris.software_id', $softwareId)
            ->where('ri.is_active', 1)
            ->select(
                'ris.id as assignment_id',
                'ri.id as institution_id',
                'ri.name',
                'ri.slug',
                'ri.institution_type',
                'ri.city',
                'ri.country',
                'ri.logo_path',
                'ri.is_verified',
                'ris.version_in_use',
                'ris.deployment_date',
                'ris.notes'
            )
            ->orderBy('ri.name', 'asc')
            ->get()
            ->all();
    }

    /**
     * Assign software to an institution.
     */
    public function assignSoftware(array $data): array
    {
        if (empty($data['institution_id']) || empty($data['software_id'])) {
            return ['success' => false, 'error' => 'Institution ID and software ID are required'];
        }

        // Check for duplicate
        $query = DB::table($this->instSoftTable)
            ->where('institution_id', $data['institution_id'])
            ->where('software_id', $data['software_id']);

        if (!empty($data['instance_id'])) {
            $query->where('instance_id', $data['instance_id']);
        } else {
            $query->whereNull('instance_id');
        }

        if ($query->exists()) {
            return ['success' => false, 'error' => 'This software assignment already exists'];
        }

        $data['created_at'] = date('Y-m-d H:i:s');

        $id = DB::table($this->instSoftTable)->insertGetId($data);

        // Recalculate software institution count
        $this->updateInstitutionCount($data['software_id']);

        return ['success' => true, 'id' => $id];
    }

    /**
     * Remove a software assignment.
     */
    public function removeSoftwareAssignment(int $id): array
    {
        $assignment = DB::table($this->instSoftTable)->where('id', $id)->first();
        if (!$assignment) {
            return ['success' => false, 'error' => 'Assignment not found'];
        }

        DB::table($this->instSoftTable)->where('id', $id)->delete();

        // Recalculate software institution count
        $this->updateInstitutionCount($assignment->software_id);

        return ['success' => true];
    }

    // =========================================================================
    // Count Recalculation
    // =========================================================================

    /**
     * Recalculate vendor.client_count from active relationships.
     */
    public function updateClientCount(int $vendorId): void
    {
        $count = DB::table($this->vendorInstTable)
            ->where('vendor_id', $vendorId)
            ->where('is_active', 1)
            ->distinct()
            ->count('institution_id');

        DB::table('registry_vendor')->where('id', $vendorId)->update([
            'client_count' => $count,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Recalculate software.institution_count from assignments.
     */
    public function updateInstitutionCount(int $softwareId): void
    {
        $count = DB::table($this->instSoftTable)
            ->where('software_id', $softwareId)
            ->distinct()
            ->count('institution_id');

        DB::table('registry_software')->where('id', $softwareId)->update([
            'institution_count' => $count,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
