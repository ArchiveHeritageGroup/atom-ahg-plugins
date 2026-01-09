<?php

class dataMigrationExportCsvAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }
        
        $filepath = $this->getUser()->getAttribute('migration_file');
        $filename = $this->getUser()->getAttribute('migration_filename');
        $detection = $this->getUser()->getAttribute('migration_detection');
        $mapping = $this->getUser()->getAttribute('migration_mapping');
        
        if (!$filepath || !file_exists($filepath)) {
            $this->getUser()->setFlash('error', 'Session expired. Please upload file again.');
            $this->redirect(['module' => 'dataMigration', 'action' => 'index']);
        }
        
        if (empty($mapping)) {
            $this->getUser()->setFlash('error', 'No mapping data. Please configure field mappings.');
            $this->redirect(['module' => 'dataMigration', 'action' => 'map']);
        }
        
        // Include path transformer
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgDataMigrationPlugin/lib/Services/PathTransformer.php';
        
        // Transform data
        $rows = $detection['rows'] ?? [];
        $headers = $detection['headers'] ?? [];
        $transformed = $this->transformData($rows, $headers, $mapping);
        
        if (empty($transformed)) {
            $this->getUser()->setFlash('error', 'No data to export. Check your field mappings.');
            $this->redirect(['module' => 'dataMigration', 'action' => 'map']);
        }
        
        // Get all unique fields from transformed data
        $allFields = [];
        foreach ($transformed as $row) {
            $allFields = array_merge($allFields, array_keys($row));
        }
        $allFields = array_unique($allFields);
        
        // Generate CSV
        $output = fopen('php://temp', 'r+');
        
        // Write header
        fputcsv($output, $allFields);
        
        // Write data
        foreach ($transformed as $row) {
            $csvRow = [];
            foreach ($allFields as $field) {
                $csvRow[] = $row[$field] ?? '';
            }
            fputcsv($output, $csvRow);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        // Send response
        $exportFilename = pathinfo($filename, PATHINFO_FILENAME) . '_atom_import.csv';
        
        $this->getResponse()->setContentType('text/csv');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="' . $exportFilename . '"');
        $this->getResponse()->setContent($csv);
        
        return sfView::NONE;
    }
    
    protected function transformData($rows, $headers, $mapping)
    {
        $transformed = [];
        
        foreach ($rows as $row) {
            $record = [];
            
            foreach ($mapping as $fieldConfig) {
                if (empty($fieldConfig['include'])) continue;
                
                $sourceField = $fieldConfig['source_field'] ?? '';
                $atomField = $fieldConfig['atom_field'] ?? '';
                $constantValue = $fieldConfig['constant_value'] ?? '';
                $concatenate = !empty($fieldConfig['concatenate']);
                $concatConstant = !empty($fieldConfig['concat_constant']);
                $concatSymbol = $fieldConfig['concat_symbol'] ?? '|';
                $transform = $fieldConfig['transform'] ?? '';
                $transformOptions = $fieldConfig['transform_options'] ?? [];
                
                if (empty($atomField)) continue;
                
                $sourceIndex = array_search($sourceField, $headers);
                $value = '';
                if ($sourceIndex !== false && isset($row[$sourceIndex])) {
                    $value = $row[$sourceIndex];
                }
                
                // Apply path transformation
                if ($transform && $value) {
                    $value = \ahgDataMigrationPlugin\Services\PathTransformer::transform($value, $transform, $transformOptions);
                }
                
                // Handle constant
                if ($concatConstant && $constantValue && $value) {
                    $value = $constantValue . $value;
                } elseif ($constantValue && empty($value)) {
                    $value = $constantValue;
                }
                
                if ($value === '' || $value === null) continue;
                
                // Handle concatenation
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
        }
        
        return $transformed;
    }
}
