<?php

class dataMigrationUploadAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }
        
        if (!$request->isMethod('post')) {
            $this->redirect(['module' => 'dataMigration', 'action' => 'index']);
        }
        
        $file = $request->getFiles('import_file');
        
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->getUser()->setFlash('error', 'Please select a file to upload');
            $this->redirect(['module' => 'dataMigration', 'action' => 'index']);
        }
        
        // Get options
        $sheetIndex = (int)$request->getParameter('sheet_index', 0);
        $firstRowHeader = (int)$request->getParameter('first_row_header', 1);
        $delimiter = $request->getParameter('delimiter', 'auto');
        $encoding = $request->getParameter('encoding', 'auto');
        $targetType = $request->getParameter('target_type', 'archives');
        $savedMapping = $request->getParameter('saved_mapping', '');
        
        // Save file to temp location
        $uploadDir = sfConfig::get('sf_upload_dir') . '/migration';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filename = $file['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $tempFile = $uploadDir . '/' . uniqid('import_') . '.' . $ext;
        
        if (!move_uploaded_file($file['tmp_name'], $tempFile)) {
            $this->getUser()->setFlash('error', 'Failed to save uploaded file');
            $this->redirect(['module' => 'dataMigration', 'action' => 'index']);
        }
        
        // Parse file and detect headers/rows
        try {
            $detection = $this->parseFile($tempFile, $ext, $sheetIndex, $firstRowHeader, $delimiter, $encoding);
        } catch (\Exception $e) {
            unlink($tempFile);
            $this->getUser()->setFlash('error', 'Error parsing file: ' . $e->getMessage());
            $this->redirect(['module' => 'dataMigration', 'action' => 'index']);
        }
        
        // Store in session
        $this->getUser()->setAttribute('migration_file', $tempFile);
        $this->getUser()->setAttribute('migration_filename', $filename);
        $this->getUser()->setAttribute('migration_detection', $detection);
        $this->getUser()->setAttribute('migration_target_type', $targetType);
        $this->getUser()->setAttribute('migration_saved_mapping', $savedMapping);
        $this->getUser()->setAttribute('migration_options', [
            'sheet_index' => $sheetIndex,
            'first_row_header' => $firstRowHeader,
            'delimiter' => $delimiter,
            'encoding' => $encoding
        ]);
        
        // Redirect to mapping page
        $this->redirect(['module' => 'dataMigration', 'action' => 'map', 'mapping_id' => $savedMapping]);
    }
    
    protected function parseFile($filepath, $ext, $sheetIndex, $firstRowHeader, $delimiter, $encoding)
    {
        $headers = [];
        $rows = [];
        $rowCount = 0;
        
        if (in_array($ext, ['xls', 'xlsx'])) {
            // Excel file
            require_once sfConfig::get('sf_root_dir') . '/vendor/autoload.php';
            
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
            $sheet = $spreadsheet->getSheet($sheetIndex);
            $data = $sheet->toArray();
            $rowCount = count($data);
            
            if ($firstRowHeader && $rowCount > 0) {
                $headers = array_map('trim', array_filter($data[0], function($v) { return $v !== null && $v !== ''; }));
                // Re-index headers to handle gaps
                $headers = array_values($headers);
                $rows = array_slice($data, 1);
            } else {
                $colCount = count($data[0] ?? []);
                for ($i = 0; $i < $colCount; $i++) {
                    $headers[] = $this->getColumnLetter($i);
                }
                $rows = $data;
            }
            
        } elseif (in_array($ext, ['csv', 'txt'])) {
            // CSV file
            $content = file_get_contents($filepath);
            
            // Handle encoding
            if ($encoding === 'auto') {
                $detected = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
                if ($detected && $detected !== 'UTF-8') {
                    $content = mb_convert_encoding($content, 'UTF-8', $detected);
                }
            } elseif ($encoding !== 'UTF-8') {
                $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            }
            
            // Detect delimiter
            if ($delimiter === 'auto') {
                $delimiter = $this->detectDelimiter($content);
            } elseif ($delimiter === '\t') {
                $delimiter = "\t";
            }
            
            // Parse CSV
            $handle = fopen('php://temp', 'r+');
            fwrite($handle, $content);
            rewind($handle);
            
            $allRows = [];
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $allRows[] = $row;
            }
            fclose($handle);
            
            $rowCount = count($allRows);
            
            if ($firstRowHeader && $rowCount > 0) {
                $headers = array_map('trim', $allRows[0]);
                $rows = array_slice($allRows, 1);
            } else {
                $colCount = count($allRows[0] ?? []);
                for ($i = 0; $i < $colCount; $i++) {
                    $headers[] = $this->getColumnLetter($i);
                }
                $rows = $allRows;
            }
        }
        
        return [
            'headers' => $headers,
            'rows' => $rows,
            'row_count' => count($rows),
            'delimiter' => $delimiter ?? null
        ];
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
