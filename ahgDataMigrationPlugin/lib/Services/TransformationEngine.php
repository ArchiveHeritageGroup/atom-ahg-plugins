<?php
namespace ahgDataMigrationPlugin\Services;

class TransformationEngine
{
    /**
     * Transform source data using mapping configuration
     */
    public static function transform(array $sourceData, array $mapping): array
    {
        $result = [];
        $concatGroups = [];
        
        foreach ($mapping as $field) {
            // Skip if not included
            if (empty($field['include'])) continue;
            
            $sourceField = $field['source_field'];
            $targetField = $field['atom_field'] ?? '';
            
            // Skip if no target
            if (empty($targetField)) continue;
            
            // Get value
            $value = $sourceData[$sourceField] ?? '';
            
            // Apply constant
            $constant = $field['constant_value'] ?? '';
            if (!empty($constant)) {
                if (!empty($field['concat_constant'])) {
                    // Prepend constant
                    $value = $constant . $value;
                } else {
                    // Use constant as value if source is empty
                    if (empty($value)) {
                        $value = $constant;
                    }
                }
            }
            
            // Handle concatenation
            if (!empty($field['concatenate'])) {
                if (!isset($concatGroups[$targetField])) {
                    $concatGroups[$targetField] = [
                        'values' => [],
                        'symbol' => $field['concat_symbol'] ?? '|'
                    ];
                }
                if (!empty($value)) {
                    $concatGroups[$targetField]['values'][] = $value;
                }
            } else {
                // Direct assignment (last wins)
                if (!empty($value)) {
                    $result[$targetField] = $value;
                }
            }
        }
        
        // Apply concatenated groups
        foreach ($concatGroups as $field => $data) {
            if (!empty($data['values'])) {
                $symbol = $data['symbol'];
                // Handle newline symbol
                if ($symbol === '\n' || $symbol === '\\n') {
                    $symbol = "\n";
                }
                $result[$field] = implode($symbol, $data['values']);
            }
        }
        
        return $result;
    }
}
