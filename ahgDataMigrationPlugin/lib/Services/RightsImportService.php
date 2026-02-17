<?php

namespace ahgDataMigrationPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Service to import rights metadata into AtoM's rights tables.
 * 
 * Handles mapping from various source formats (Preservica OPEX, Dublin Core, etc.)
 * to AtoM's PREMIS-based rights structure.
 */
class RightsImportService
{
    // Rights basis taxonomy (id=68)
    const BASIS_COPYRIGHT = 170;
    const BASIS_LICENSE = 171;
    const BASIS_STATUTE = 172;
    const BASIS_POLICY = 173;
    const BASIS_DONOR = 218;
    
    // Copyright status taxonomy (id=69)
    const STATUS_UNDER_COPYRIGHT = 350;
    const STATUS_PUBLIC_DOMAIN = 351;
    const STATUS_UNKNOWN = 352;
    
    // Rights act taxonomy (id=67)
    const ACT_DELETE = 343;
    const ACT_DISCOVER = 344;
    const ACT_DISPLAY = 345;
    const ACT_DISSEMINATE = 346;
    const ACT_MIGRATE = 347;
    const ACT_MODIFY = 348;
    const ACT_REPLICATE = 349;
    
    // Relation type for rights
    const RELATION_TYPE_RIGHT = 'Right';
    
    /**
     * Import rights from Preservica OPEX metadata.
     * 
     * @param int $informationObjectId The target information object
     * @param array $metadata Parsed OPEX metadata
     * @return int|null The created rights record ID or null
     */
    public function importFromOpex(int $informationObjectId, array $metadata): ?int
    {
        $rightsData = $this->parseOpexRights($metadata);
        
        if (empty($rightsData)) {
            return null;
        }
        
        return $this->createRightsRecord($informationObjectId, $rightsData);
    }
    
    /**
     * Import rights from Dublin Core metadata.
     * 
     * @param int $informationObjectId The target information object
     * @param array $metadata DC metadata array
     * @return int|null The created rights record ID or null
     */
    public function importFromDublinCore(int $informationObjectId, array $metadata): ?int
    {
        $rightsData = $this->parseDublinCoreRights($metadata);
        
        if (empty($rightsData)) {
            return null;
        }
        
        return $this->createRightsRecord($informationObjectId, $rightsData);
    }
    
    /**
     * Parse Preservica OPEX rights metadata.
     */
    protected function parseOpexRights(array $metadata): array
    {
        $rights = [];
        
        // SecurityDescriptor determines access level
        $securityDescriptor = $metadata['SecurityDescriptor'] ?? null;
        if ($securityDescriptor) {
            $rights['access_level'] = $this->mapSecurityDescriptor($securityDescriptor);
        }
        
        // dc:rights contains copyright/license text
        $dcRights = $metadata['dc:rights'] ?? $metadata['rights'] ?? null;
        if ($dcRights) {
            $rights = array_merge($rights, $this->parseRightsStatement($dcRights));
        }
        
        // dcterms:accessRights
        $accessRights = $metadata['dcterms:accessRights'] ?? $metadata['accessRights'] ?? null;
        if ($accessRights) {
            $rights['rights_note'] = $accessRights;
        }
        
        // dcterms:license
        $license = $metadata['dcterms:license'] ?? $metadata['license'] ?? null;
        if ($license) {
            $rights['license_terms'] = $license;
            $rights['basis_id'] = self::BASIS_LICENSE;
        }
        
        return $rights;
    }
    
    /**
     * Parse Dublin Core rights metadata.
     */
    protected function parseDublinCoreRights(array $metadata): array
    {
        $rights = [];
        
        // dc:rights
        $dcRights = $metadata['dc:rights'] ?? $metadata['rights'] ?? null;
        if ($dcRights) {
            $rights = array_merge($rights, $this->parseRightsStatement($dcRights));
        }
        
        // dcterms:accessRights
        if (!empty($metadata['dcterms:accessRights'])) {
            $rights['rights_note'] = $metadata['dcterms:accessRights'];
        }
        
        // dcterms:license
        if (!empty($metadata['dcterms:license'])) {
            $rights['license_terms'] = $metadata['dcterms:license'];
            $rights['basis_id'] = self::BASIS_LICENSE;
        }
        
        return $rights;
    }
    
    /**
     * Parse a rights statement string to determine basis and status.
     */
    protected function parseRightsStatement(string $statement): array
    {
        $rights = [];
        $statement = trim($statement);
        $statementLower = strtolower($statement);
        
        // Check for Public Domain
        if (strpos($statementLower, 'public domain') !== false ||
            strpos($statementLower, 'pd') !== false ||
            strpos($statementLower, 'cc0') !== false) {
            $rights['basis_id'] = self::BASIS_COPYRIGHT;
            $rights['copyright_status_id'] = self::STATUS_PUBLIC_DOMAIN;
            $rights['copyright_note'] = $statement;
            return $rights;
        }
        
        // Check for Creative Commons licenses
        if (preg_match('/cc[- ]?(by|nc|nd|sa|0)/i', $statementLower) ||
            strpos($statementLower, 'creative commons') !== false) {
            $rights['basis_id'] = self::BASIS_LICENSE;
            $rights['license_terms'] = $statement;
            return $rights;
        }
        
        // Check for copyright statements
        if (strpos($statementLower, 'copyright') !== false ||
            strpos($statementLower, '©') !== false ||
            preg_match('/\(c\)\s*\d{4}/i', $statement)) {
            $rights['basis_id'] = self::BASIS_COPYRIGHT;
            $rights['copyright_status_id'] = self::STATUS_UNDER_COPYRIGHT;
            $rights['copyright_note'] = $statement;
            return $rights;
        }
        
        // Check for "All rights reserved"
        if (strpos($statementLower, 'all rights reserved') !== false) {
            $rights['basis_id'] = self::BASIS_COPYRIGHT;
            $rights['copyright_status_id'] = self::STATUS_UNDER_COPYRIGHT;
            $rights['copyright_note'] = $statement;
            return $rights;
        }
        
        // Check for license keywords
        if (strpos($statementLower, 'license') !== false ||
            strpos($statementLower, 'licence') !== false) {
            $rights['basis_id'] = self::BASIS_LICENSE;
            $rights['license_terms'] = $statement;
            return $rights;
        }
        
        // Default: treat as copyright with unknown status
        $rights['basis_id'] = self::BASIS_COPYRIGHT;
        $rights['copyright_status_id'] = self::STATUS_UNKNOWN;
        $rights['rights_note'] = $statement;
        
        return $rights;
    }
    
    /**
     * Map Preservica SecurityDescriptor to access restrictions.
     */
    protected function mapSecurityDescriptor(string $descriptor): array
    {
        $descriptorLower = strtolower(trim($descriptor));
        
        $mapping = [
            'open' => ['restriction' => 0, 'acts' => [self::ACT_DISPLAY, self::ACT_DISSEMINATE, self::ACT_DISCOVER]],
            'public' => ['restriction' => 0, 'acts' => [self::ACT_DISPLAY, self::ACT_DISSEMINATE, self::ACT_DISCOVER]],
            'closed' => ['restriction' => 1, 'acts' => []],
            'private' => ['restriction' => 1, 'acts' => []],
            'restricted' => ['restriction' => 1, 'acts' => [self::ACT_DISCOVER]],
            'internal' => ['restriction' => 1, 'acts' => [self::ACT_DISCOVER]],
        ];
        
        return $mapping[$descriptorLower] ?? ['restriction' => 0, 'acts' => [self::ACT_DISPLAY]];
    }
    
    /**
     * Create a rights record and link it to an information object.
     */
    public function createRightsRecord(int $informationObjectId, array $rightsData): ?int
    {
        try {
            DB::beginTransaction();
            
            // Create object entry first (AtoM pattern)
            $objectId = DB::table('object')->insertGetId([
                'class_name' => 'QubitRights',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            
            // Create rights record
            DB::table('rights')->insert([
                'id' => $objectId,
                'start_date' => $rightsData['start_date'] ?? date('Y-m-d'),
                'end_date' => $rightsData['end_date'] ?? null,
                'basis_id' => $rightsData['basis_id'] ?? self::BASIS_COPYRIGHT,
                'rights_holder_id' => $rightsData['rights_holder_id'] ?? null,
                'copyright_status_id' => $rightsData['copyright_status_id'] ?? null,
                'copyright_status_date' => $rightsData['copyright_status_date'] ?? null,
                'copyright_jurisdiction' => $rightsData['copyright_jurisdiction'] ?? null,
                'statute_determination_date' => $rightsData['statute_determination_date'] ?? null,
                'statute_citation_id' => $rightsData['statute_citation_id'] ?? null,
                'source_culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
            ]);
            
            // Create i18n record
            DB::table('rights_i18n')->insert([
                'id' => $objectId,
                'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
                'rights_note' => $rightsData['rights_note'] ?? null,
                'copyright_note' => $rightsData['copyright_note'] ?? null,
                'identifier_value' => $rightsData['identifier_value'] ?? null,
                'license_terms' => $rightsData['license_terms'] ?? null,
            ]);
            
            // Get the "Right" relation type ID
            $rightRelationTypeId = DB::table('term_i18n')
                ->where('name', self::RELATION_TYPE_RIGHT)
                ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
                ->value('id');
            
            if ($rightRelationTypeId) {
                // Create relation object
                $relationObjectId = DB::table('object')->insertGetId([
                    'class_name' => 'QubitRelation',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                
                // Link rights to information object via relation
                DB::table('relation')->insert([
                    'id' => $relationObjectId,
                    'subject_id' => $informationObjectId,
                    'object_id' => $objectId,
                    'type_id' => $rightRelationTypeId,
                    'source_culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
                ]);
            }
            
            // Create granted rights (acts) if access_level provided
            if (!empty($rightsData['access_level'])) {
                $this->createGrantedRights($objectId, $rightsData['access_level']);
            }
            
            DB::commit();
            return $objectId;
            
        } catch (\Exception $e) {
            DB::rollBack();
            error_log("RightsImportService error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create granted_right records for the allowed/restricted acts.
     */
    protected function createGrantedRights(int $rightsId, array $accessLevel): void
    {
        $restriction = $accessLevel['restriction'] ?? 0;
        $allowedActs = $accessLevel['acts'] ?? [];
        
        // All possible acts
        $allActs = [
            self::ACT_DELETE,
            self::ACT_DISCOVER,
            self::ACT_DISPLAY,
            self::ACT_DISSEMINATE,
            self::ACT_MIGRATE,
            self::ACT_MODIFY,
            self::ACT_REPLICATE,
        ];
        
        $serialNumber = 0;
        
        foreach ($allActs as $actId) {
            // Determine if this act is allowed or restricted
            $isAllowed = in_array($actId, $allowedActs);
            $actRestriction = $isAllowed ? 0 : 1;
            
            // Only create grants for acts that differ from default or are explicitly set
            if ($isAllowed || $restriction === 1) {
                DB::table('granted_right')->insert([
                    'rights_id' => $rightsId,
                    'act_id' => $actId,
                    'restriction' => $actRestriction,
                    'start_date' => date('Y-m-d'),
                    'end_date' => null,
                    'notes' => null,
                    'serial_number' => $serialNumber++,
                ]);
            }
        }
    }
    
    /**
     * Get or create a rights holder by name.
     */
    public function getOrCreateRightsHolder(string $name): int
    {
        // Check if exists
        $existing = DB::table('actor_i18n')
            ->where('authorized_form_of_name', $name)
            ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
            ->value('id');
        
        if ($existing) {
            // Check if it's a rights holder
            $isRightsHolder = DB::table('rights_holder')->where('id', $existing)->exists();
            if ($isRightsHolder) {
                return $existing;
            }
        }
        
        // Create new actor/rights holder
        $objectId = DB::table('object')->insertGetId([
            'class_name' => 'QubitRightsHolder',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        DB::table('actor')->insert([
            'id' => $objectId,
            'source_culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
        ]);
        
        DB::table('actor_i18n')->insert([
            'id' => $objectId,
            'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
            'authorized_form_of_name' => $name,
        ]);
        
        DB::table('rights_holder')->insert([
            'id' => $objectId,
        ]);
        
        return $objectId;
    }
}
