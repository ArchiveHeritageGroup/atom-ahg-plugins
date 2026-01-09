<?php
namespace AhgMigration\Mappers;

use AhgMigration\Sectors\SectorFactory;

class FieldMapper
{
    protected array $mappings = [];
    protected array $defaults = [];
    protected string $destinationSector = 'archives';
    protected TransformationEngine $transformer;
    protected array $sectorFields = [];
    
    public function __construct()
    {
        $this->transformer = new TransformationEngine();
    }
    
    /**
     * Set destination sector and load its field definitions
     */
    public function setDestinationSector(string $sector): self
    {
        $this->destinationSector = $sector;
        $sectorDef = SectorFactory::get($sector);
        $this->sectorFields = $sectorDef->getFields();
        return $this;
    }
    
    /**
     * Set field mappings
     */
    public function setMappings(array $mappings): self
    {
        $this->mappings = $mappings;
        return $this;
    }
    
    /**
     * Set default values for unmapped fields
     */
    public function setDefaults(array $defaults): self
    {
        $this->defaults = $defaults;
        return $this;
    }
    
    /**
     * Load configuration from template object
     */
    public function loadFromTemplate(object $template): self
    {
        $this->mappings = $template->field_mappings ?? [];
        $this->defaults = $template->default_values ?? [];
        
        if (!empty($template->transformations)) {
            $this->transformer->loadCustomTransformations($template->transformations);
        }
        
        if (!empty($template->destination_sector)) {
            $this->setDestinationSector($template->destination_sector);
        }
        
        return $this;
    }
    
    /**
     * Map a source record to destination format
     */
    public function mapRecord(array $sourceData): array
    {
        $mapped = [];
        
        foreach ($this->mappings as $sourceField => $config) {
            // Handle simple string mapping or config array
            if (is_string($config)) {
                $targetField = $config;
                $transform = null;
                $options = [];
            } else {
                $targetField = $config['target'] ?? null;
                $transform = $config['transform'] ?? null;
                $options = $config['options'] ?? [];
                
                // Skip if no target specified
                if (!$targetField) continue;
            }
            
            // Get source value (supports nested keys with dot notation)
            $value = $this->getValue($sourceData, $sourceField);
            
            // Skip empty values unless explicitly allowed
            if ($value === null || $value === '') {
                continue;
            }
            
            // Apply transformation if specified
            if ($transform) {
                $value = $this->transformer->apply($transform, $value, $sourceData, $options);
            }
            
            // Set the mapped value (supports nested targets)
            $this->setValue($mapped, $targetField, $value);
        }
        
        // Apply default values for missing required fields
        foreach ($this->defaults as $field => $defaultValue) {
            if (!$this->hasValue($mapped, $field)) {
                $this->setValue($mapped, $field, $defaultValue);
            }
        }
        
        return $mapped;
    }
    
    /**
     * Auto-suggest mappings based on source field names
     */
    public function suggestMappings(array $sourceFields): array
    {
        $suggestions = [];
        $targetFields = $this->sectorFields;
        
        foreach ($sourceFields as $sourceField) {
            $normalized = $this->normalizeFieldName($sourceField);
            $suggestion = $this->findBestMatch($normalized, $sourceField, $targetFields);
            
            if ($suggestion) {
                $suggestions[$sourceField] = $suggestion;
            }
        }
        
        return $suggestions;
    }
    
    /**
     * Find best matching target field
     */
    protected function findBestMatch(string $normalized, string $original, array $targetFields): ?array
    {
        // Check common field mappings first
        $commonMappings = $this->getCommonMappings();
        if (isset($commonMappings[$normalized])) {
            $target = $commonMappings[$normalized];
            if (isset($targetFields[$target]) || $this->isNestedField($target, $targetFields)) {
                return [
                    'target' => $target,
                    'confidence' => 0.95,
                    'transform' => $this->suggestTransform($target)
                ];
            }
        }
        
        // Check for exact match
        foreach ($targetFields as $targetField => $config) {
            $targetNormalized = $this->normalizeFieldName($targetField);
            if ($targetNormalized === $normalized) {
                return [
                    'target' => $targetField,
                    'confidence' => 1.0,
                    'transform' => null
                ];
            }
        }
        
        // Check for partial/fuzzy match
        foreach ($targetFields as $targetField => $config) {
            $targetNormalized = $this->normalizeFieldName($targetField);
            $labelNormalized = $this->normalizeFieldName($config['label'] ?? '');
            
            // Check if one contains the other
            if (strlen($normalized) >= 3 && strlen($targetNormalized) >= 3) {
                if (strpos($targetNormalized, $normalized) !== false ||
                    strpos($normalized, $targetNormalized) !== false ||
                    strpos($labelNormalized, $normalized) !== false) {
                    return [
                        'target' => $targetField,
                        'confidence' => 0.7,
                        'transform' => null
                    ];
                }
            }
        }
        
        return null;
    }
    
    /**
     * Common field name mappings across systems
     */
    protected function getCommonMappings(): array
    {
        return [
            // Identifiers
            'id' => 'identifier',
            'objectid' => 'identifier',
            'objectnumber' => 'identifier',
            'accession' => 'identifier',
            'accessionnumber' => 'identifier',
            'referencecode' => 'identifier',
            'refcode' => 'identifier',
            'callnumber' => 'identifier',
            'cataloguenumber' => 'identifier',
            'unitid' => 'identifier',
            'eadid' => 'identifier',
            
            // Titles
            'name' => 'title',
            'objectname' => 'title',
            'titleproper' => 'title',
            'unittitle' => 'title',
            'itemname' => 'title',
            
            // Alternative titles
            'othertitle' => 'alternativeTitle',
            'alttitle' => 'alternativeTitle',
            'othername' => 'alternativeTitle',
            
            // Description/Scope
            'description' => 'scopeAndContent',
            'scopecontent' => 'scopeAndContent',
            'scopenote' => 'scopeAndContent',
            'abstract' => 'scopeAndContent',
            'briefdescription' => 'scopeAndContent',
            
            // Dates
            'date' => 'eventDates.description',
            'dates' => 'eventDates.description',
            'datetext' => 'eventDates.description',
            'datemade' => 'eventDates.description',
            'datecreated' => 'eventDates.description',
            'dateexpression' => 'eventDates.description',
            'unitdate' => 'eventDates.description',
            'startdate' => 'eventDates.startDate',
            'enddate' => 'eventDates.endDate',
            'datefrom' => 'eventDates.startDate',
            'dateto' => 'eventDates.endDate',
            'datebegin' => 'eventDates.startDate',
            'dateend' => 'eventDates.endDate',
            
            // Extent/Physical
            'extent' => 'extentAndMedium',
            'physdesc' => 'extentAndMedium',
            'physicaldescription' => 'extentAndMedium',
            'dimensions' => 'extentAndMedium',
            'measurements' => 'extentAndMedium',
            'size' => 'extentAndMedium',
            
            // Materials
            'materials' => 'physicalCharacteristics',
            'medium' => 'physicalCharacteristics',
            'technique' => 'physicalCharacteristics',
            'material' => 'physicalCharacteristics',
            
            // Arrangement
            'arrangement' => 'arrangement',
            'organization' => 'arrangement',
            'filing' => 'arrangement',
            
            // History
            'adminhistory' => 'archivalHistory',
            'bioghist' => 'archivalHistory',
            'administrativehistory' => 'archivalHistory',
            'custodialhistory' => 'custodialHistory',
            'custodhist' => 'custodialHistory',
            'provenance' => 'custodialHistory',
            
            // Acquisition
            'acquisition' => 'acquisition',
            'acqinfo' => 'acquisition',
            'sourceofacquisition' => 'acquisition',
            'donor' => 'acquisition',
            
            // Access conditions
            'access' => 'accessConditions',
            'accessrestrict' => 'accessConditions',
            'accessrestrictions' => 'accessConditions',
            'restrictions' => 'accessConditions',
            
            // Use/Reproduction
            'copyright' => 'reproductionConditions',
            'userestrict' => 'reproductionConditions',
            'userestrictions' => 'reproductionConditions',
            'rights' => 'reproductionConditions',
            
            // Location
            'location' => 'physicalStorage',
            'physloc' => 'physicalStorage',
            'shelf' => 'physicalStorage',
            'storage' => 'physicalStorage',
            'container' => 'physicalStorage',
            
            // Language
            'language' => 'language',
            'langmaterial' => 'language',
            
            // Creator
            'creator' => 'creators',
            'maker' => 'creators',
            'author' => 'creators',
            'artist' => 'creators',
            'primarymaker' => 'creators',
            'origination' => 'creators',
            
            // Access points
            'subject' => 'subjectAccessPoints',
            'subjects' => 'subjectAccessPoints',
            'topic' => 'subjectAccessPoints',
            'place' => 'placeAccessPoints',
            'geog' => 'placeAccessPoints',
            'geography' => 'placeAccessPoints',
            'geogname' => 'placeAccessPoints',
            'genre' => 'genreAccessPoints',
            'genreform' => 'genreAccessPoints',
            'form' => 'genreAccessPoints',
            
            // Digital objects
            'image' => 'digitalObject.path',
            'imageref' => 'digitalObject.path',
            'filename' => 'digitalObject.path',
            'digitalobject' => 'digitalObject.path',
            'filepath' => 'digitalObject.path',
            'mediafile' => 'digitalObject.path',
            
            // Hierarchy
            'parent' => '_parentIdentifier',
            'parentid' => '_parentIdentifier',
            'parentref' => '_parentIdentifier',
            'level' => 'levelOfDescription',
            'levelofdescription' => 'levelOfDescription',
            'desclevel' => 'levelOfDescription',
            
            // Notes
            'note' => 'generalNote',
            'notes' => 'generalNote',
            'generalnote' => 'generalNote',
            'odd' => 'generalNote',
            
            // Condition (Museum)
            'condition' => 'condition',
            'conditionstatus' => 'condition',
            
            // Repository
            'repository' => 'repository',
            'holdingrepository' => 'repository'
        ];
    }
    
    /**
     * Suggest transformation based on target field
     */
    protected function suggestTransform(string $target): ?string
    {
        $transforms = [
            'eventDates' => 'parseDate',
            'eventDates.startDate' => 'parseDate',
            'eventDates.endDate' => 'parseDate',
            'levelOfDescription' => 'mapLevel',
            'creators' => 'createActor',
            'subjectAccessPoints' => 'splitMultiValue',
            'placeAccessPoints' => 'splitMultiValue',
            'genreAccessPoints' => 'splitMultiValue',
            '_parentIdentifier' => null,
            'publicationStatus' => 'mapBoolean'
        ];
        
        return $transforms[$target] ?? null;
    }
    
    /**
     * Get value from nested array using dot notation
     */
    protected function getValue(array $data, string $key)
    {
        if (strpos($key, '.') === false) {
            return $data[$key] ?? null;
        }
        
        $keys = explode('.', $key);
        $value = $data;
        
        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return null;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    /**
     * Set value in nested array using dot notation
     */
    protected function setValue(array &$data, string $key, $value): void
    {
        $keys = explode('.', $key);
        $current = &$data;
        
        foreach ($keys as $i => $k) {
            // Check for append mode
            if ($k === 'append' && $i === count($keys) - 1) {
                $parentKey = $keys[$i - 1] ?? null;
                if ($parentKey && isset($current[$parentKey])) {
                    $current[$parentKey] .= "\n\n" . $value;
                }
                return;
            }
            
            if ($i === count($keys) - 1) {
                $current[$k] = $value;
            } else {
                if (!isset($current[$k]) || !is_array($current[$k])) {
                    $current[$k] = [];
                }
                $current = &$current[$k];
            }
        }
    }
    
    /**
     * Check if nested value exists
     */
    protected function hasValue(array $data, string $key): bool
    {
        $value = $this->getValue($data, $key);
        return $value !== null && $value !== '';
    }
    
    /**
     * Normalize field name for comparison
     */
    protected function normalizeFieldName(string $field): string
    {
        $normalized = strtolower($field);
        $normalized = preg_replace('/[^a-z0-9]/', '', $normalized);
        return $normalized;
    }
    
    /**
     * Check if target is a valid nested field
     */
    protected function isNestedField(string $target, array $fields): bool
    {
        $parts = explode('.', $target);
        return isset($fields[$parts[0]]);
    }
    
    /**
     * Get available target fields for current sector
     */
    public function getTargetFields(): array
    {
        return $this->sectorFields;
    }
    
    /**
     * Validate mappings configuration
     */
    public function validateMappings(): array
    {
        $errors = [];
        $warnings = [];
        
        // Check for required fields
        $requiredFields = array_filter($this->sectorFields, fn($f) => $f['required'] ?? false);
        $mappedTargets = array_map(fn($m) => is_array($m) ? ($m['target'] ?? '') : $m, $this->mappings);
        
        foreach ($requiredFields as $field => $config) {
            if (!in_array($field, $mappedTargets) && !isset($this->defaults[$field])) {
                $warnings[] = "Required field '{$config['label']}' ({$field}) is not mapped";
            }
        }
        
        // Check for invalid targets
        foreach ($this->mappings as $source => $config) {
            $target = is_array($config) ? ($config['target'] ?? '') : $config;
            $baseTarget = explode('.', $target)[0];
            
            if ($target && !isset($this->sectorFields[$baseTarget]) && $baseTarget[0] !== '_') {
                $errors[] = "Invalid target field: {$target} (source: {$source})";
            }
        }
        
        return ['errors' => $errors, 'warnings' => $warnings];
    }
}
