<?php

namespace AhgRegistry\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

class RelationshipRepository
{
    protected string $vendorInstitutionTable = 'registry_vendor_institution';
    protected string $institutionSoftwareTable = 'registry_institution_software';

    // -------------------------------------------------------
    // Vendor-Institution relationships
    // -------------------------------------------------------

    public function findByVendor(int $vendorId, array $params = []): array
    {
        $query = DB::table($this->vendorInstitutionTable . ' as vi')
            ->leftJoin('registry_institution as i', 'vi.institution_id', '=', 'i.id')
            ->where('vi.vendor_id', $vendorId)
            ->select('vi.*', 'i.name as institution_name', 'i.slug as institution_slug', 'i.institution_type', 'i.country as institution_country');

        if (isset($params['is_active'])) {
            $query->where('vi.is_active', (int) $params['is_active']);
        }
        if (!empty($params['relationship_type'])) {
            $query->where('vi.relationship_type', $params['relationship_type']);
        }

        $total = $query->count();

        $limit = $params['limit'] ?? 20;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $items = $query->orderBy('i.name', 'asc')
                       ->limit($limit)
                       ->offset($offset)
                       ->get();

        return ['items' => $items, 'total' => $total, 'page' => (int) $page];
    }

    public function findByInstitution(int $institutionId, array $params = []): array
    {
        $query = DB::table($this->vendorInstitutionTable . ' as vi')
            ->leftJoin('registry_vendor as v', 'vi.vendor_id', '=', 'v.id')
            ->where('vi.institution_id', $institutionId)
            ->select('vi.*', 'v.name as vendor_name', 'v.slug as vendor_slug', 'v.vendor_type', 'v.country as vendor_country');

        if (isset($params['is_active'])) {
            $query->where('vi.is_active', (int) $params['is_active']);
        }
        if (!empty($params['relationship_type'])) {
            $query->where('vi.relationship_type', $params['relationship_type']);
        }

        $total = $query->count();

        $limit = $params['limit'] ?? 20;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $items = $query->orderBy('v.name', 'asc')
                       ->limit($limit)
                       ->offset($offset)
                       ->get();

        return ['items' => $items, 'total' => $total, 'page' => (int) $page];
    }

    public function createVendorInstitution(array $data): int
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');

        return DB::table($this->vendorInstitutionTable)->insertGetId($data);
    }

    public function updateVendorInstitution(int $id, array $data): bool
    {
        return DB::table($this->vendorInstitutionTable)->where('id', $id)->update($data) >= 0;
    }

    public function deleteVendorInstitution(int $id): bool
    {
        return DB::table($this->vendorInstitutionTable)->where('id', $id)->delete() > 0;
    }

    public function vendorInstitutionExists(int $vendorId, int $institutionId, string $relationshipType): bool
    {
        return DB::table($this->vendorInstitutionTable)
            ->where('vendor_id', $vendorId)
            ->where('institution_id', $institutionId)
            ->where('relationship_type', $relationshipType)
            ->exists();
    }

    // -------------------------------------------------------
    // Institution-Software relationships
    // -------------------------------------------------------

    public function findSoftwareByInstitution(int $institutionId, array $params = []): array
    {
        $query = DB::table($this->institutionSoftwareTable . ' as isw')
            ->leftJoin('registry_software as s', 'isw.software_id', '=', 's.id')
            ->where('isw.institution_id', $institutionId)
            ->select('isw.*', 's.name as software_name', 's.slug as software_slug', 's.category', 's.latest_version');

        $total = $query->count();

        $limit = $params['limit'] ?? 20;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $items = $query->orderBy('s.name', 'asc')
                       ->limit($limit)
                       ->offset($offset)
                       ->get();

        return ['items' => $items, 'total' => $total, 'page' => (int) $page];
    }

    public function findInstitutionsBySoftware(int $softwareId, array $params = []): array
    {
        $query = DB::table($this->institutionSoftwareTable . ' as isw')
            ->leftJoin('registry_institution as i', 'isw.institution_id', '=', 'i.id')
            ->where('isw.software_id', $softwareId)
            ->select('isw.*', 'i.name as institution_name', 'i.slug as institution_slug', 'i.institution_type', 'i.country as institution_country');

        $total = $query->count();

        $limit = $params['limit'] ?? 20;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $items = $query->orderBy('i.name', 'asc')
                       ->limit($limit)
                       ->offset($offset)
                       ->get();

        return ['items' => $items, 'total' => $total, 'page' => (int) $page];
    }

    public function createInstitutionSoftware(array $data): int
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');

        return DB::table($this->institutionSoftwareTable)->insertGetId($data);
    }

    public function updateInstitutionSoftware(int $id, array $data): bool
    {
        return DB::table($this->institutionSoftwareTable)->where('id', $id)->update($data) >= 0;
    }

    public function deleteInstitutionSoftware(int $id): bool
    {
        return DB::table($this->institutionSoftwareTable)->where('id', $id)->delete() > 0;
    }
}
