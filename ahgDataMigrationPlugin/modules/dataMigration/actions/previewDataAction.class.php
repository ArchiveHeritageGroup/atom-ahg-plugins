<?php

use AtomFramework\Http\Controllers\AhgController;
class dataMigrationPreviewDataAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }
        
        $this->filepath = $this->getUser()->getAttribute('migration_file');
        $this->filename = $this->getUser()->getAttribute('migration_filename');
        $this->detection = $this->getUser()->getAttribute('migration_detection');
        $this->mapping = $this->getUser()->getAttribute('migration_mapping');
        
        // Debug
        error_log("PreviewData: filepath=" . $this->filepath);
        error_log("PreviewData: mapping count=" . (is_array($this->mapping) ? count($this->mapping) : 'not array'));
        
        if (!$this->filepath || !file_exists($this->filepath)) {
            $this->getUser()->setFlash('error', 'Session expired. Please upload file again.');
            $this->redirect(['module' => 'dataMigration', 'action' => 'index']);
        }
        
        if (empty($this->mapping)) {
            $this->getUser()->setFlash('error', 'No mapping data. Please configure field mappings.');
            $this->redirect(['module' => 'dataMigration', 'action' => 'map']);
        }
        
        // Transform data according to mapping
        $this->transformedData = $this->transformData();
    }
    
    protected function transformData()
    {
        $rows = $this->detection['rows'] ?? [];
        $headers = $this->detection['headers'] ?? [];
        $mapping = $this->mapping ?? [];
        
        $transformed = [];
        
        foreach ($rows as $rowIndex => $row) {
            $record = [];
            
            foreach ($mapping as $fieldConfig) {
                if (empty($fieldConfig['include'])) continue;
                
                $sourceField = $fieldConfig['source_field'] ?? '';
                $atomField = $fieldConfig['atom_field'] ?? '';
                $constantValue = $fieldConfig['constant_value'] ?? '';
                $concatenate = !empty($fieldConfig['concatenate']);
                $concatConstant = !empty($fieldConfig['concat_constant']);
                $concatSymbol = $fieldConfig['concat_symbol'] ?? '|';
                
                if (empty($atomField)) continue;
                
                // Get source value by header name
                $sourceIndex = array_search($sourceField, $headers);
                $value = '';
                if ($sourceIndex !== false && isset($row[$sourceIndex])) {
                    $value = $row[$sourceIndex];
                }
                
                // Handle constant - prepend if concat_constant, otherwise use as default
                if ($concatConstant && $constantValue && $value) {
                    $value = $constantValue . $value;
                } elseif ($constantValue && empty($value)) {
                    $value = $constantValue;
                }
                
                // Skip empty values
                if ($value === '' || $value === null) continue;
                
                // Handle concatenation to same target field
                if ($concatenate && isset($record[$atomField]) && $record[$atomField] !== '') {
                    $symbol = ($concatSymbol === '\n' || $concatSymbol === "\\n") ? "\n" : $concatSymbol;
                    $record[$atomField] .= $symbol . $value;
                } else {
                    if (!isset($record[$atomField]) || $record[$atomField] === '') {
                        $record[$atomField] = $value;
                    } elseif ($concatenate) {
                        $symbol = ($concatSymbol === '\n' || $concatSymbol === "\\n") ? "\n" : $concatSymbol;
                        $record[$atomField] .= $symbol . $value;
                    }
                }
            }
            
            if (!empty($record)) {
                $transformed[] = $record;
            }
            
            // Limit preview to 50 rows
            if ($rowIndex >= 49) break;
        }
        
        return $transformed;
    }
}
