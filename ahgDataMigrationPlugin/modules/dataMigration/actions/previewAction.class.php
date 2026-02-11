<?php

use AtomFramework\Http\Controllers\AhgController;
class dataMigrationPreviewAction extends AhgController
{
    public function execute($request)
    {
        // Check if this is an AJAX preview request (POST with file) from index page
        if ($request->isMethod('post') && $request->getFiles('file')) {
            return $this->handleAjaxPreview($request);
        }
        
        // Check if this is a form submission from mapping page
        if ($request->isMethod('post') && $request->getParameter('fields')) {
            return $this->handleMappingSubmit($request);
        }
        
        // Otherwise redirect back
        $this->redirect(['module' => 'dataMigration', 'action' => 'index']);
    }
    
    protected function handleMappingSubmit($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }
        
        // Get mapping data from POST
        $fields = $request->getParameter('fields', []);
        $targetType = $request->getParameter('target_type');
        $outputMode = $request->getParameter('output_mode', 'preview');
        $targetSector = $request->getParameter('target_sector', 'archives');
        
        // Debug - log what we received
        error_log("Migration: Received " . count($fields) . " fields, output_mode: " . $outputMode);
        
        // Convert to raw array if needed
        if ($fields instanceof sfOutputEscaperArrayDecorator) {
            $fields = $fields->getRawValue();
        }
        
        // Store mapping in session
        $this->getUser()->setAttribute('migration_mapping', $fields);
        $this->getUser()->setAttribute('migration_output_mode', $outputMode);
        $this->getUser()->setAttribute('migration_target_sector', $targetSector);
        
        error_log("Migration: Saved mapping with " . count($fields) . " fields to session");
        
        // Redirect based on output mode
        switch ($outputMode) {
            case 'import':
                $this->redirect(['module' => 'dataMigration', 'action' => 'execute']);
                break;
            case 'csv':
                $this->redirect(['module' => 'dataMigration', 'action' => 'exportCsv']);
                break;
            case 'ead':
                $this->redirect(['module' => 'dataMigration', 'action' => 'exportEad']);
                break;
            case 'ahg_csv':
                $this->redirect(['module' => 'dataMigration', 'action' => 'exportAhgCsv']);
                break;
            case 'ahg_import':
                $this->redirect(['module' => 'dataMigration', 'action' => 'executeAhgImport']);
                break;
            default:
                $this->redirect(['module' => 'dataMigration', 'action' => 'previewData']);
        }
    }
    
    protected function handleAjaxPreview($request)
    {
        $this->getResponse()->setContentType('application/json');
        
        if (!$this->getUser()->isAdministrator()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Unauthorized']));
        }
        
        $file = $request->getFiles('file');
        $sheetIndex = (int)$request->getParameter('sheet_index', 0);
        $firstRowHeader = (int)$request->getParameter('first_row_header', 1);
        $delimiter = $request->getParameter('delimiter', 'auto');
        
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'No file']));
        }
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        try {
            $headers = [];
            $rows = [];
            
            if (in_array($ext, ['xls', 'xlsx'])) {
                $bootstrap = $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
                if (file_exists($bootstrap)) {
                    require_once $bootstrap;
                }

                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
                $sheet = $spreadsheet->getSheet($sheetIndex);
                $data = $sheet->toArray();
                
                if ($firstRowHeader && count($data) > 0) {
                    $headers = array_map('trim', $data[0]);
                    $dataRows = array_slice($data, 1, 10);
                } else {
                    $colCount = count($data[0] ?? []);
                    for ($i = 0; $i < $colCount; $i++) {
                        $headers[] = $this->getColumnLetter($i);
                    }
                    $dataRows = array_slice($data, 0, 10);
                }
                
                foreach ($dataRows as $row) {
                    $rowData = [];
                    foreach ($headers as $idx => $header) {
                        $rowData[$header] = $row[$idx] ?? '';
                    }
                    $rows[] = $rowData;
                }
                
            } elseif ($ext === 'csv' || $ext === 'txt') {
                $content = file_get_contents($file['tmp_name']);
                
                if ($delimiter === 'auto') {
                    $delimiter = $this->detectDelimiter($content);
                } elseif ($delimiter === '\t') {
                    $delimiter = "\t";
                }
                
                $lines = str_getcsv($content, "\n");
                
                if ($firstRowHeader && count($lines) > 0) {
                    $headers = str_getcsv($lines[0], $delimiter);
                    $headers = array_map('trim', $headers);
                    $dataLines = array_slice($lines, 1, 10);
                } else {
                    $firstLine = str_getcsv($lines[0], $delimiter);
                    for ($i = 0; $i < count($firstLine); $i++) {
                        $headers[] = $this->getColumnLetter($i);
                    }
                    $dataLines = array_slice($lines, 0, 10);
                }
                
                foreach ($dataLines as $line) {
                    $values = str_getcsv($line, $delimiter);
                    $rowData = [];
                    foreach ($headers as $idx => $header) {
                        $rowData[$header] = $values[$idx] ?? '';
                    }
                    $rows[] = $rowData;
                }
            }
            
            return $this->renderText(json_encode([
                'success' => true,
                'headers' => $headers,
                'rows' => $rows
            ]));
            
        } catch (\Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }
    
    protected function detectDelimiter($content)
    {
        $firstLine = strtok($content, "\n");
        $delimiters = [',', ';', "\t", '|'];
        $counts = [];
        
        foreach ($delimiters as $d) {
            $counts[$d] = substr_count($firstLine, $d);
        }
        
        arsort($counts);
        return key($counts);
    }
    
    protected function getColumnLetter($index)
    {
        $letter = '';
        while ($index >= 0) {
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = intval($index / 26) - 1;
        }
        return $letter;
    }
}
