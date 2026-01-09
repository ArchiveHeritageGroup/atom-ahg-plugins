<?php

class dataMigrationGetPreviewAction extends sfAction
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');
        
        $filepath = $this->getUser()->getAttribute('migration_file');
        $detection = $this->getUser()->getAttribute('migration_detection');
        
        if (!$filepath || !file_exists($filepath)) {
            return $this->renderText(json_encode(['error' => 'No file']));
        }
        
        $delimiter = $detection['delimiter'] ?? ',';
        $rows = [];
        
        $handle = fopen($filepath, 'r');
        $headers = fgetcsv($handle, 0, $delimiter);
        
        $count = 0;
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false && $count < 10) {
            $data = [];
            foreach ($headers as $i => $h) {
                $data[$h] = isset($row[$i]) ? $row[$i] : '';
            }
            $rows[] = $data;
            $count++;
        }
        fclose($handle);
        
        return $this->renderText(json_encode(['rows' => $rows]));
    }
}
