<?php

class dataMigrationDetectSheetsAction extends sfAction
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');
        
        if (!$this->context->user->isAdministrator()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Unauthorized']));
        }
        
        $file = $request->getFiles('file');
        
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'No file uploaded', 'code' => $file['error'] ?? 'no file']));
        }
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, ['xls', 'xlsx'])) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Not an Excel file: ' . $ext]));
        }
        
        try {
            // Load PhpSpreadsheet
            require_once sfConfig::get('sf_root_dir') . '/atom-framework/vendor/autoload.php';
            
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
            $sheetNames = $spreadsheet->getSheetNames();
            
            $sheets = [];
            foreach ($sheetNames as $idx => $name) {
                $sheet = $spreadsheet->getSheet($idx);
                $highestRow = $sheet->getHighestRow();
                $sheets[] = [
                    'index' => $idx,
                    'name' => $name,
                    'rows' => $highestRow
                ];
            }
            
            return $this->renderText(json_encode([
                'success' => true,
                'sheets' => $sheets,
                'count' => count($sheets)
            ]));
            
        } catch (\Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]));
        }
    }
}
