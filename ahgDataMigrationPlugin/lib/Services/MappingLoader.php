<?php
namespace ahgDataMigrationPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

class MappingLoader
{
    protected static $defaultMappingsPath;
    
    public static function init()
    {
        self::$defaultMappingsPath = \sfConfig::get('sf_root_dir') 
            . '/atom-ahg-plugins/ahgDataMigrationPlugin/data/mappings/defaults';
    }
    
    /**
     * Load default mapping for a target type
     */
    public static function loadDefault(string $targetType): ?array
    {
        self::init();
        $file = self::$defaultMappingsPath . '/' . $targetType . '.json';
        
        if (file_exists($file)) {
            $content = file_get_contents($file);
            return json_decode($content, true);
        }
        
        return null;
    }
    
    /**
     * Load saved mapping from database
     */
    public static function loadSaved(int $mappingId): ?array
    {
        try {
            $mapping = DB::table('atom_data_mapping')->find($mappingId);
            if ($mapping) {
                return [
                    'name' => $mapping->name,
                    'target_type' => $mapping->target_type,
                    'fields' => json_decode($mapping->field_mappings, true)
                ];
            }
        } catch (\Exception $e) {
            // ignore
        }
        return null;
    }
    
    /**
     * Get target fields for a specific record type
     */
    public static function getTargetFields(string $targetType): array
    {
        $fields = [
            'information_object' => [
                'identifier' => 'Identifier',
                'legacyId' => 'Legacy ID',
                'parentId' => 'Parent ID',
                'qubitParentSlug' => 'Parent Slug',
                'title' => 'Title',
                'levelOfDescription' => 'Level of Description',
                'extentAndMedium' => 'Extent and Medium',
                'repository' => 'Repository',
                'archivalHistory' => 'Archival History',
                'acquisition' => 'Immediate Source of Acquisition',
                'scopeAndContent' => 'Scope and Content',
                'appraisal' => 'Appraisal',
                'accruals' => 'Accruals',
                'arrangement' => 'Arrangement',
                'accessConditions' => 'Access Conditions',
                'reproductionConditions' => 'Reproduction Conditions',
                'language' => 'Language',
                'script' => 'Script',
                'languageNote' => 'Language Note',
                'physicalCharacteristics' => 'Physical Characteristics',
                'findingAids' => 'Finding Aids',
                'locationOfOriginals' => 'Location of Originals',
                'locationOfCopies' => 'Location of Copies',
                'relatedUnitsOfDescription' => 'Related Units',
                'publicationNote' => 'Publication Note',
                'generalNote' => 'General Note',
                'digitalObjectPath' => 'Digital Object Path',
                'digitalObjectURI' => 'Digital Object URI',
                'subjectAccessPoints' => 'Subject Access Points',
                'placeAccessPoints' => 'Place Access Points',
                'nameAccessPoints' => 'Name Access Points',
                'genreAccessPoints' => 'Genre Access Points',
                'eventDates' => 'Event Dates',
                'eventTypes' => 'Event Types',
                'eventActors' => 'Event Actors',
                'publicationStatus' => 'Publication Status',
                'descriptionIdentifier' => 'Description Identifier',
                'rules' => 'Rules/Conventions',
                'sources' => 'Sources',
                'culture' => 'Culture/Language Code'
            ],
            'repository' => [
                'identifier' => 'Identifier',
                'authorizedFormOfName' => 'Authorized Form of Name',
                'parallelFormsOfName' => 'Parallel Forms of Name',
                'otherFormsOfName' => 'Other Forms of Name',
                'types' => 'Repository Type',
                'history' => 'History',
                'geoculturalContext' => 'Geocultural Context',
                'mandates' => 'Mandates',
                'internalStructures' => 'Internal Structures',
                'collectingPolicies' => 'Collecting Policies',
                'buildings' => 'Buildings',
                'holdings' => 'Holdings',
                'findingAids' => 'Finding Aids',
                'openingTimes' => 'Opening Times',
                'accessConditions' => 'Access Conditions',
                'disabledAccess' => 'Disabled Access',
                'researchServices' => 'Research Services',
                'reproductionServices' => 'Reproduction Services',
                'publicFacilities' => 'Public Facilities',
                'descriptionIdentifier' => 'Description Identifier',
                'institutionIdentifier' => 'Institution Identifier',
                'levelOfDetail' => 'Level of Detail',
                'maintenanceNote' => 'Maintenance Notes',
                'contactPerson' => 'Contact Person',
                'streetAddress' => 'Street Address',
                'city' => 'City',
                'region' => 'Region/Province',
                'country' => 'Country',
                'postalCode' => 'Postal Code',
                'telephone' => 'Telephone',
                'fax' => 'Fax',
                'email' => 'Email',
                'website' => 'Website',
                'culture' => 'Culture/Language Code'
            ],
            'accession' => [
                'identifier' => 'Accession Number',
                'title' => 'Title',
                'accessionDate' => 'Accession Date',
                'extentAndMedium' => 'Extent and Medium',
                'repository' => 'Repository',
                'acquisitionType' => 'Acquisition Type',
                'processingStatus' => 'Processing Status',
                'processingPriority' => 'Processing Priority',
                'processingDate' => 'Processing Date',
                'accessionNotes' => 'Accession Notes',
                'archivalHistory' => 'Archival History',
                'scopeAndContent' => 'Scope and Content',
                'appraisal' => 'Appraisal/Selection',
                'physicalCharacteristics' => 'Physical Condition',
                'locationOfOriginals' => 'Location of Materials',
                'rules' => 'Rules/Conventions',
                'sources' => 'Sources'
            ],
            'actor' => [
                'authorizedFormOfName' => 'Authorized Form of Name',
                'parallelFormsOfName' => 'Parallel Forms of Name',
                'standardizedNameIdentifier' => 'Standardized Identifiers',
                'otherFormsOfName' => 'Other Forms of Name',
                'entityType' => 'Entity Type',
                'datesOfExistence' => 'Dates of Existence',
                'history' => 'History/Biography',
                'places' => 'Places',
                'legalStatus' => 'Legal Status',
                'functions' => 'Functions',
                'mandates' => 'Mandates',
                'internalStructures' => 'Internal Structures',
                'generalContext' => 'General Context',
                'occupations' => 'Occupations',
                'descriptionIdentifier' => 'Record Identifier',
                'institutionIdentifier' => 'Maintaining Agency',
                'rules' => 'Rules/Conventions',
                'sources' => 'Sources',
                'maintenanceNotes' => 'Maintenance Notes',
                'culture' => 'Culture/Language Code'
            ],
            'subject' => [
                'name' => 'Term',
                'taxonomy' => 'Taxonomy',
                'scopeNote' => 'Scope Note',
                'sourceNote' => 'Source Note',
                'culture' => 'Culture/Language Code'
            ],
            'place' => [
                'name' => 'Place Name',
                'taxonomy' => 'Taxonomy',
                'scopeNote' => 'Scope Note',
                'sourceNote' => 'Source Note',
                'culture' => 'Culture/Language Code'
            ],
            'event' => [
                'eventType' => 'Event Type',
                'eventDate' => 'Event Date',
                'eventDescription' => 'Event Description',
                'eventLocation' => 'Event Location',
                'eventActors' => 'Related Actors',
                'legacyId' => 'Legacy ID',
                'culture' => 'Culture/Language Code'
            ]
        ];
        
        return $fields[$targetType] ?? [];
    }
    
    /**
     * Get list of available default mappings
     */
    public static function getAvailableDefaults(): array
    {
        self::init();
        $defaults = [];
        
        foreach (glob(self::$defaultMappingsPath . '/*.json') as $file) {
            $content = json_decode(file_get_contents($file), true);
            if ($content) {
                $defaults[] = [
                    'target_type' => $content['target_type'],
                    'name' => $content['name'],
                    'description' => $content['description'] ?? ''
                ];
            }
        }
        
        return $defaults;
    }
}
