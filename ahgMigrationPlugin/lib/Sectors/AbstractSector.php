<?php
namespace AhgMigration\Sectors;

abstract class AbstractSector implements SectorInterface
{
    protected array $fields = [];
    protected array $fieldGroups = [];
    protected array $levels = [];
    
    public function getRequiredFields(): array
    {
        return array_filter($this->fields, fn($f) => $f['required'] ?? false);
    }
    
    public function validate(array $data): array
    {
        $errors = [];
        $warnings = [];
        
        // Check required fields
        foreach ($this->getRequiredFields() as $field => $config) {
            if (empty($data[$field])) {
                $errors[] = "Required field '{$config['label']}' is missing";
            }
        }
        
        // Validate field types
        foreach ($data as $field => $value) {
            if (!isset($this->fields[$field])) {
                continue;
            }
            
            $config = $this->fields[$field];
            $type = $config['type'] ?? 'string';
            
            $fieldError = $this->validateFieldType($field, $value, $type, $config);
            if ($fieldError) {
                $warnings[] = $fieldError;
            }
        }
        
        return ['errors' => $errors, 'warnings' => $warnings];
    }
    
    protected function validateFieldType(string $field, $value, string $type, array $config): ?string
    {
        if (empty($value)) return null;
        
        switch ($type) {
            case 'date':
                if (!strtotime($value)) {
                    return "Invalid date format for '{$config['label']}'";
                }
                break;
            case 'integer':
                if (!is_numeric($value)) {
                    return "'{$config['label']}' should be a number";
                }
                break;
            case 'taxonomy':
                // Could validate against actual taxonomy terms
                break;
        }
        
        return null;
    }
}
