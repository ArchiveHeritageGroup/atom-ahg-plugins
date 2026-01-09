<?php
namespace AhgMigration\Mappers;

use Illuminate\Database\Capsule\Manager as DB;

class TransformationEngine
{
    protected array $customTransformations = [];
    protected array $taxonomyCache = [];
    
    /**
     * Load custom transformations from template
     */
    public function loadCustomTransformations(array $transformations): void
    {
        $this->customTransformations = $transformations;
    }
    
    /**
     * Apply a transformation to a value
     */
    public function apply(string $transform, $value, array $context = [], array $options = [])
    {
        // Check for custom transformation first
        if (isset($this->customTransformations[$transform])) {
            return $this->applyCustom($this->customTransformations[$transform], $value, $context);
        }
        
        // Built-in transformations
        return match($transform) {
            // Date transformations
            'parseDate' => $this->parseDate($value),
            'parseDateRange' => $this->parseDateRange($value),
            'formatDate' => $this->formatDate($value, $options['format'] ?? 'Y-m-d'),
            
            // Taxonomy/lookup transformations
            'mapLevel' => $this->mapLevelOfDescription($value),
            'mapTaxonomy' => $this->mapTaxonomy($value, $options['taxonomy'] ?? null),
            'mapBoolean' => $this->mapBoolean($value),
            'mapEntityType' => $this->mapEntityType($value),
            
            // Multi-value transformations
            'splitMultiValue' => $this->splitMultiValue($value, $options['delimiter'] ?? null),
            'joinValues' => $this->joinValues($value, $options['delimiter'] ?? '; '),
            'firstValue' => $this->firstValue($value),
            
            // Actor/relation transformations
            'createActor' => $this->prepareActor($value, $options['type'] ?? 'person'),
            'createCorporateBody' => $this->prepareActor($value, 'corporate_body'),
            'createFamily' => $this->prepareActor($value, 'family'),
            
            // Text transformations
            'trim' => trim($value),
            'uppercase' => mb_strtoupper($value),
            'lowercase' => mb_strtolower($value),
            'titlecase' => mb_convert_case($value, MB_CASE_TITLE),
            'stripHtml' => strip_tags($value),
            'normalizeWhitespace' => $this->normalizeWhitespace($value),
            'truncate' => $this->truncate($value, $options['length'] ?? 255),
            'append' => $value . ($options['suffix'] ?? ''),
            'prepend' => ($options['prefix'] ?? '') . $value,
            
            // Conditional transformations
            'defaultIfEmpty' => $value ?: ($options['default'] ?? ''),
            'nullIfEmpty' => $value ?: null,
            'replaceEmpty' => $value ?: ($options['replacement'] ?? 'Unknown'),
            
            // Format-specific
            'parseVernonDate' => $this->parseVernonDate($value),
            'parseArchivesSpaceDate' => $this->parseArchivesSpaceDate($value),
            
            // Pass through if unknown
            default => $value
        };
    }
    
    /**
     * Apply custom transformation configuration
     */
    protected function applyCustom(array $config, $value, array $context)
    {
        $type = $config['type'] ?? 'replace';
        
        return match($type) {
            'replace' => $this->applyReplacements($value, $config['mappings'] ?? []),
            'regex' => preg_replace($config['pattern'] ?? '//', $config['replacement'] ?? '', $value),
            'split' => explode($config['delimiter'] ?? '|', $value),
            'join' => is_array($value) ? implode($config['delimiter'] ?? '; ', $value) : $value,
            'template' => $this->applyTemplate($config['template'] ?? '{value}', $value, $context),
            'lookup' => $this->lookupValue($value, $config),
            'concat' => $this->concatFields($context, $config['fields'] ?? [], $config['delimiter'] ?? ' '),
            'conditional' => $this->applyConditional($value, $config, $context),
            default => $value
        };
    }
    
    // =========================================================================
    // DATE TRANSFORMATIONS
    // =========================================================================
    
    protected function parseDate($value): array
    {
        if (empty($value)) {
            return [];
        }
        
        // If already an array with date info, return it
        if (is_array($value)) {
            return $value;
        }
        
        $result = ['description' => $value];
        
        // Try to extract date range first (e.g., "1900-1950", "1900 - 1950", "1900/1950")
        if (preg_match('/(\d{4})\s*[-–—\/]\s*(\d{4})/', $value, $matches)) {
            $result['startDate'] = $matches[1] . '-01-01';
            $result['endDate'] = $matches[2] . '-12-31';
            return $result;
        }
        
        // Try various date formats
        $formats = [
            'Y-m-d', 'Y/m/d', 'd-m-Y', 'd/m/Y', 'm/d/Y',
            'Y-m', 'Y/m', 'm-Y', 'm/Y',
            'Y',
            'F j, Y', 'j F Y', 'F Y', 'M Y', 'M j, Y', 'j M Y',
            'd M Y', 'd F Y'
        ];
        
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, trim($value));
            if ($date && $date->format($format) === trim($value)) {
                $result['startDate'] = $date->format('Y-m-d');
                
                // For year-only dates, set end date to end of year
                if ($format === 'Y') {
                    $result['endDate'] = $date->format('Y') . '-12-31';
                }
                
                return $result;
            }
        }
        
        // Try to extract just a year
        if (preg_match('/\b(\d{4})\b/', $value, $matches)) {
            $result['startDate'] = $matches[1] . '-01-01';
        }
        
        return $result;
    }
    
    protected function parseDateRange($value): array
    {
        return $this->parseDate($value);
    }
    
    protected function formatDate($value, string $format): string
    {
        if (empty($value)) return '';
        
        $timestamp = is_numeric($value) ? $value : strtotime($value);
        return $timestamp ? date($format, $timestamp) : $value;
    }
    
    protected function parseVernonDate($value): array
    {
        // Vernon often uses formats like "c.1900" or "1900s" or "early 20th century"
        $result = ['description' => $value];
        
        // Handle circa dates
        if (preg_match('/c\.?\s*(\d{4})/', $value, $matches)) {
            $result['startDate'] = ($matches[1] - 5) . '-01-01';
            $result['endDate'] = ($matches[1] + 5) . '-12-31';
            return $result;
        }
        
        // Handle decade dates (1900s)
        if (preg_match('/(\d{4})s/', $value, $matches)) {
            $decade = $matches[1];
            $result['startDate'] = $decade . '-01-01';
            $result['endDate'] = ($decade + 9) . '-12-31';
            return $result;
        }
        
        return $this->parseDate($value);
    }
    
    protected function parseArchivesSpaceDate($value): array
    {
        // ArchivesSpace uses ISO format in @normal attribute
        return $this->parseDate($value);
    }
    
    // =========================================================================
    // TAXONOMY/LOOKUP TRANSFORMATIONS
    // =========================================================================
    
    protected function mapLevelOfDescription($value): string
    {
        $mappings = [
            // Standard archival levels
            'collection' => 'Collection',
            'fonds' => 'Fonds',
            'subfonds' => 'Sub-fonds',
            'sub-fonds' => 'Sub-fonds',
            'recordgrp' => 'Record group',
            'record group' => 'Record group',
            'subgrp' => 'Sub-group',
            'sub-group' => 'Sub-group',
            'series' => 'Series',
            'subseries' => 'Sub-series',
            'sub-series' => 'Sub-series',
            'file' => 'File',
            'item' => 'Item',
            'piece' => 'Item',
            'otherlevel' => 'Part',
            'part' => 'Part',
            'class' => 'Class',
            
            // Museum/object terms
            'object' => 'Item',
            'work' => 'Item',
            'artwork' => 'Item',
            'artifact' => 'Item',
            'specimen' => 'Item',
            
            // Library terms
            'volume' => 'Item',
            'issue' => 'Item'
        ];
        
        $normalized = strtolower(trim($value));
        return $mappings[$normalized] ?? ucfirst($value);
    }
    
    protected function mapTaxonomy($value, ?string $taxonomy): string
    {
        if (!$taxonomy || empty($value)) {
            return $value;
        }
        
        $cacheKey = "{$taxonomy}:{$value}";
        if (isset($this->taxonomyCache[$cacheKey])) {
            return $this->taxonomyCache[$cacheKey];
        }
        
        // Try to find existing term
        $term = DB::table('term_i18n as ti')
            ->join('term as t', 't.id', '=', 'ti.id')
            ->join('taxonomy as tax', 'tax.id', '=', 't.taxonomy_id')
            ->where('tax.name', $taxonomy)
            ->where(function($q) use ($value) {
                $q->where('ti.name', $value)
                  ->orWhere('ti.name', 'LIKE', $value . '%');
            })
            ->where('ti.culture', 'en')
            ->value('ti.name');
        
        $result = $term ?: $value;
        $this->taxonomyCache[$cacheKey] = $result;
        
        return $result;
    }
    
    protected function mapBoolean($value): bool
    {
        if (is_bool($value)) return $value;
        
        $trueValues = ['yes', 'true', '1', 'y', 'on', 'published', 'public', 'active', 'enabled'];
        return in_array(strtolower(trim($value)), $trueValues, true);
    }
    
    protected function mapEntityType($value): string
    {
        $mappings = [
            'person' => 'person',
            'individual' => 'person',
            'human' => 'person',
            'organisation' => 'corporate_body',
            'organization' => 'corporate_body',
            'corporate' => 'corporate_body',
            'company' => 'corporate_body',
            'institution' => 'corporate_body',
            'corp' => 'corporate_body',
            'family' => 'family',
            'group' => 'family'
        ];
        
        return $mappings[strtolower(trim($value))] ?? 'person';
    }
    
    // =========================================================================
    // MULTI-VALUE TRANSFORMATIONS
    // =========================================================================
    
    protected function splitMultiValue($value, ?string $delimiter = null): array
    {
        if (is_array($value)) {
            return $value;
        }
        
        if (empty($value)) {
            return [];
        }
        
        // Auto-detect delimiter if not specified
        if (!$delimiter) {
            $delimiters = ['|', ';', "\n", ','];
            foreach ($delimiters as $d) {
                if (strpos($value, $d) !== false) {
                    $delimiter = $d;
                    break;
                }
            }
        }
        
        if ($delimiter) {
            return array_map('trim', array_filter(explode($delimiter, $value)));
        }
        
        return [$value];
    }
    
    protected function joinValues($value, string $delimiter = '; '): string
    {
        if (!is_array($value)) {
            return $value;
        }
        return implode($delimiter, array_filter($value));
    }
    
    protected function firstValue($value)
    {
        if (is_array($value)) {
            return $value[0] ?? null;
        }
        return $value;
    }
    
    // =========================================================================
    // ACTOR TRANSFORMATIONS
    // =========================================================================
    
    protected function prepareActor($value, string $type = 'person'): array
    {
        if (is_array($value) && isset($value['authorizedFormOfName'])) {
            return $value;
        }
        
        if (is_array($value)) {
            // Array of names
            return array_map(fn($v) => [
                'authorizedFormOfName' => trim(is_array($v) ? ($v['name'] ?? $v['authorizedFormOfName'] ?? '') : $v),
                'entityType' => $type
            ], $value);
        }
        
        return [
            'authorizedFormOfName' => trim($value),
            'entityType' => $type
        ];
    }
    
    // =========================================================================
    // TEXT TRANSFORMATIONS
    // =========================================================================
    
    protected function normalizeWhitespace($value): string
    {
        if (!is_string($value)) return $value;
        return preg_replace('/\s+/', ' ', trim($value));
    }
    
    protected function truncate($value, int $length): string
    {
        if (!is_string($value) || mb_strlen($value) <= $length) {
            return $value;
        }
        return mb_substr($value, 0, $length - 3) . '...';
    }
    
    // =========================================================================
    // CUSTOM TRANSFORMATION HELPERS
    // =========================================================================
    
    protected function applyReplacements($value, array $mappings): string
    {
        $key = strtolower(trim($value));
        return $mappings[$key] ?? $value;
    }
    
    protected function applyTemplate(string $template, $value, array $context): string
    {
        $replacements = array_merge(['value' => $value], $context);
        
        foreach ($replacements as $key => $val) {
            if (is_scalar($val)) {
                $template = str_replace('{' . $key . '}', $val, $template);
            }
        }
        
        return $template;
    }
    
    protected function lookupValue($value, array $config): ?string
    {
        $table = $config['table'] ?? null;
        $lookupField = $config['lookupField'] ?? 'name';
        $returnField = $config['returnField'] ?? 'id';
        
        if (!$table) {
            return $value;
        }
        
        $result = DB::table($table)
            ->where($lookupField, $value)
            ->value($returnField);
        
        return $result ?? ($config['default'] ?? $value);
    }
    
    protected function concatFields(array $context, array $fields, string $delimiter = ' '): string
    {
        $values = [];
        foreach ($fields as $field) {
            if (isset($context[$field]) && $context[$field] !== '') {
                $values[] = $context[$field];
            }
        }
        return implode($delimiter, $values);
    }
    
    protected function applyConditional($value, array $config, array $context)
    {
        $field = $config['field'] ?? null;
        $operator = $config['operator'] ?? 'equals';
        $compareValue = $config['value'] ?? null;
        $thenValue = $config['then'] ?? $value;
        $elseValue = $config['else'] ?? $value;
        
        $testValue = $field ? ($context[$field] ?? null) : $value;
        
        $matches = match($operator) {
            'equals', '==' => $testValue == $compareValue,
            'not_equals', '!=' => $testValue != $compareValue,
            'contains' => strpos($testValue, $compareValue) !== false,
            'starts_with' => strpos($testValue, $compareValue) === 0,
            'ends_with' => substr($testValue, -strlen($compareValue)) === $compareValue,
            'empty' => empty($testValue),
            'not_empty' => !empty($testValue),
            'regex' => preg_match($compareValue, $testValue),
            default => false
        };
        
        return $matches ? $thenValue : $elseValue;
    }
    
    /**
     * Get list of available transformations
     */
    public static function getAvailableTransformations(): array
    {
        return [
            'Date' => [
                'parseDate' => 'Parse date (auto-detect format)',
                'parseDateRange' => 'Parse date range',
                'parseVernonDate' => 'Parse Vernon CMS date',
                'parseArchivesSpaceDate' => 'Parse ArchivesSpace date'
            ],
            'Mapping' => [
                'mapLevel' => 'Map to Level of Description',
                'mapBoolean' => 'Map to boolean (yes/no)',
                'mapEntityType' => 'Map to entity type (person/corporate)'
            ],
            'Multi-value' => [
                'splitMultiValue' => 'Split into multiple values',
                'joinValues' => 'Join array into string',
                'firstValue' => 'Take first value only'
            ],
            'Actor' => [
                'createActor' => 'Create actor (person)',
                'createCorporateBody' => 'Create corporate body',
                'createFamily' => 'Create family'
            ],
            'Text' => [
                'trim' => 'Trim whitespace',
                'uppercase' => 'Convert to uppercase',
                'lowercase' => 'Convert to lowercase',
                'titlecase' => 'Convert to title case',
                'stripHtml' => 'Remove HTML tags',
                'normalizeWhitespace' => 'Normalize whitespace'
            ]
        ];
    }
}
