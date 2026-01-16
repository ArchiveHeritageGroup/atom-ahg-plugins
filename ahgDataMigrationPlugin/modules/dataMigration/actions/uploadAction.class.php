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
        // Handle Preservica formats
        if ($ext === 'opex' || ($ext === 'xml' && $this->isOpexFile($filepath))) {
            return $this->parseOpexFile($filepath);
        }
        
        if (in_array($ext, ['pax', 'zip'])) {
            return $this->parsePaxFile($filepath);
        }

        $headers = [];
        $rows = [];

        if (in_array($ext, ['xls', 'xlsx'])) {
            // Excel file
            require_once sfConfig::get('sf_root_dir') . '/vendor/autoload.php';

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
            $sheet = $spreadsheet->getSheet($sheetIndex);
            $data = $sheet->toArray();

            if ($firstRowHeader && count($data) > 0) {
                $headers = array_map('trim', array_filter($data[0], function($v) { return $v !== null && $v !== ''; }));
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

            if ($firstRowHeader && count($allRows) > 0) {
                $headers = array_map('trim', $allRows[0]);
                $rows = array_slice($allRows, 1);
            } else {
                $colCount = count($allRows[0] ?? []);
                for ($i = 0; $i < $colCount; $i++) {
                    $headers[] = $this->getColumnLetter($i);
                }
                $rows = $allRows;
            }
        } elseif ($ext === 'json') {
            // JSON file
            $content = file_get_contents($filepath);
            $data = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON file: ' . json_last_error_msg());
            }
            
            // Handle array of objects or nested structure
            if (isset($data['records'])) {
                $data = $data['records'];
            } elseif (isset($data['data'])) {
                $data = $data['data'];
            }
            
            if (!empty($data) && is_array($data)) {
                $firstRow = reset($data);
                if (is_array($firstRow)) {
                    $headers = array_keys($firstRow);
                    $rows = array_map('array_values', $data);
                }
            }
        } elseif ($ext === 'xml') {
            // Generic XML
            return $this->parseGenericXml($filepath);
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'row_count' => count($rows),
            'delimiter' => $delimiter ?? null,
            'format' => $ext
        ];
    }

    /**
     * Check if XML file is OPEX format.
     */
    protected function isOpexFile($filepath)
    {
        $content = file_get_contents($filepath, false, null, 0, 2000);
        return strpos($content, 'opex:') !== false 
            || strpos($content, 'OPEXMetadata') !== false
            || strpos($content, 'openpreservationexchange.org') !== false;
    }

    /**
     * Parse Preservica OPEX XML file - handles multiple Record elements.
     */
    protected function parseOpexFile($filepath)
    {
        $content = file_get_contents($filepath);
        
        // Remove default namespace to make XPath easier
        $content = preg_replace('/xmlns="[^"]+"/', '', $content);
        
        $xml = new \SimpleXMLElement($content);
        $xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
        $xml->registerXPathNamespace('dcterms', 'http://purl.org/dc/terms/');

        $headers = [];
        $records = [];

        // Check for multiple Record elements in DescriptiveMetadata
        $recordNodes = $xml->xpath('//DescriptiveMetadata/Record');
        
        if (!empty($recordNodes) && count($recordNodes) > 0) {
            // Multiple records - parse each one
            foreach ($recordNodes as $recordNode) {
                $record = [];
                
                // Get Identifiers with their type attribute
                foreach ($recordNode->xpath('Identifier') as $id) {
                    $type = (string)($id['type'] ?? 'ID');
                    $value = (string)$id;
                    $key = 'Identifier_' . $type;
                    $record[$key] = $value;
                }
                
                // Get Dublin Core elements
                $dcElements = ['title', 'creator', 'subject', 'description', 'publisher',
                    'contributor', 'date', 'type', 'format', 'identifier',
                    'source', 'language', 'coverage', 'rights'];
                
                foreach ($dcElements as $elem) {
                    $nodes = $recordNode->xpath("dc:{$elem}");
                    if (!empty($nodes)) {
                        $values = [];
                        foreach ($nodes as $node) {
                            $values[] = (string)$node;
                        }
                        $record['dc:' . $elem] = implode(' | ', $values);
                    }
                }
                
                // Get DC Terms elements
                $dcTerms = ['extent', 'provenance', 'accessRights', 'created', 'modified'];
                foreach ($dcTerms as $term) {
                    $nodes = $recordNode->xpath("dcterms:{$term}");
                    if (!empty($nodes)) {
                        $record['dcterms:' . $term] = (string)$nodes[0];
                    }
                }
                
                if (!empty($record)) {
                    $records[] = $record;
                }
            }
        } else {
            // Single record file - extract from Properties and DescriptiveMetadata
            $record = [];
            
            // Properties
            $props = $xml->xpath('//Properties');
            if (!empty($props)) {
                foreach ($props[0]->children() as $child) {
                    $record[$child->getName()] = (string)$child;
                }
            }
            
            // Transfer info
            $transfer = $xml->xpath('//Transfer');
            if (!empty($transfer)) {
                foreach ($transfer[0]->children() as $child) {
                    $record['Transfer_' . $child->getName()] = (string)$child;
                }
            }
            
            // Identifiers
            $identifiers = $xml->xpath('//Identifier');
            foreach ($identifiers as $id) {
                $type = (string)($id['type'] ?? 'ID');
                $record['Identifier_' . $type] = (string)$id;
            }
            
            // Dublin Core from anywhere
            $dcElements = ['title', 'creator', 'subject', 'description', 'publisher',
                'contributor', 'date', 'type', 'format', 'identifier',
                'source', 'language', 'coverage', 'rights'];
            
            foreach ($dcElements as $elem) {
                $nodes = $xml->xpath("//dc:{$elem}");
                if (!empty($nodes)) {
                    $values = [];
                    foreach ($nodes as $node) {
                        $values[] = (string)$node;
                    }
                    $record['dc:' . $elem] = implode(' | ', $values);
                }
            }
            
            if (!empty($record)) {
                $records[] = $record;
            }
        }

        // Build headers from all records
        foreach ($records as $record) {
            foreach (array_keys($record) as $key) {
                if (!in_array($key, $headers)) {
                    $headers[] = $key;
                }
            }
        }

        // Build rows
        $rows = [];
        foreach ($records as $record) {
            $row = [];
            foreach ($headers as $h) {
                $row[] = $record[$h] ?? '';
            }
            $rows[] = $row;
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'row_count' => count($rows),
            'format' => 'opex'
        ];
    }

    /**
     * Parse Preservica PAX/XIP package.
     */
    protected function parsePaxFile($filepath)
    {
        $zip = new \ZipArchive();
        if ($zip->open($filepath) !== true) {
            throw new \Exception('Cannot open PAX package');
        }

        $headers = [];
        $rows = [];
        $records = [];

        // Find metadata.xml or XIP file
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('/metadata\.xml$/i', $name) || preg_match('/\.xip$/i', $name)) {
                $content = $zip->getFromIndex($i);
                $records = array_merge($records, $this->parseXipContent($content));
            }
        }

        $zip->close();

        // Build headers from all records
        foreach ($records as $record) {
            foreach (array_keys($record) as $key) {
                if (!in_array($key, $headers)) {
                    $headers[] = $key;
                }
            }
        }

        // Build rows
        foreach ($records as $record) {
            $row = [];
            foreach ($headers as $h) {
                $row[] = $record[$h] ?? '';
            }
            $rows[] = $row;
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'row_count' => count($rows),
            'format' => 'pax'
        ];
    }

    /**
     * Parse XIP content from PAX package.
     */
    protected function parseXipContent($content)
    {
        $records = [];
        
        try {
            // Remove default namespace
            $content = preg_replace('/xmlns="[^"]+"/', '', $content);
            
            $xml = new \SimpleXMLElement($content);
            $xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');

            // Find all StructuralObjects
            $objects = $xml->xpath('//StructuralObject');
            
            foreach ($objects as $obj) {
                $record = [];
                
                foreach ($obj->children() as $child) {
                    $name = $child->getName();
                    $value = (string)$child;
                    $record[$name] = $value;
                }

                // Extract Dublin Core if present
                $dcNodes = $xml->xpath("//dc:*");
                foreach ($dcNodes as $dc) {
                    $name = 'dc:' . $dc->getName();
                    $value = (string)$dc;
                    if (isset($record[$name])) {
                        $record[$name] .= ' | ' . $value;
                    } else {
                        $record[$name] = $value;
                    }
                }

                $records[] = $record;
            }

            // If no StructuralObjects, try ContentObjects
            if (empty($records)) {
                $objects = $xml->xpath('//ContentObject');
                foreach ($objects as $obj) {
                    $record = [];
                    foreach ($obj->children() as $child) {
                        $record[$child->getName()] = (string)$child;
                    }
                    $records[] = $record;
                }
            }

        } catch (\Exception $e) {
            // If XIP parsing fails, try generic XML
            return $this->parseGenericXmlContent($content);
        }

        return $records;
    }

    /**
     * Parse generic XML file.
     */
    protected function parseGenericXml($filepath)
    {
        $content = file_get_contents($filepath);
        return $this->parseGenericXmlFromContent($content);
    }

    /**
     * Parse generic XML from content string.
     */
    protected function parseGenericXmlFromContent($content)
    {
        $records = [];
        
        try {
            $xml = new \SimpleXMLElement($content);
            $xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
            $xml->registerXPathNamespace('dcterms', 'http://purl.org/dc/terms/');

            // Try to find record-like elements
            foreach ($xml->children() as $child) {
                $record = $this->xmlToArray($child);
                if (!empty($record)) {
                    $records[] = $record;
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }

        if (empty($records)) {
            return ['headers' => [], 'rows' => [], 'row_count' => 0, 'format' => 'xml'];
        }

        // Build headers
        $headers = [];
        foreach ($records as $record) {
            foreach (array_keys($record) as $key) {
                if (!in_array($key, $headers)) {
                    $headers[] = $key;
                }
            }
        }

        // Build rows
        $rows = [];
        foreach ($records as $record) {
            $row = [];
            foreach ($headers as $h) {
                $row[] = $record[$h] ?? '';
            }
            $rows[] = $row;
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'row_count' => count($rows),
            'format' => 'xml'
        ];
    }

    /**
     * Parse Dublin Core XML.
     */
    protected function parseDublinCoreXml($filepath, $content = null)
    {
        if ($content === null) {
            $content = file_get_contents($filepath);
        }
        
        return $this->parseGenericXmlFromContent($content);
    }

    /**
     * Convert XML element to flat array.
     */
    protected function xmlToArray($element, $prefix = '')
    {
        $result = [];
        
        foreach ($element->children() as $child) {
            $name = $prefix ? $prefix . '_' . $child->getName() : $child->getName();
            
            if ($child->count() > 0) {
                $result = array_merge($result, $this->xmlToArray($child, $name));
            } else {
                $value = (string)$child;
                if (isset($result[$name])) {
                    $result[$name] .= ' | ' . $value;
                } else {
                    $result[$name] = $value;
                }
            }
        }

        // Also get attributes
        foreach ($element->attributes() as $attr => $value) {
            $name = $prefix ? $prefix . '_' . $attr : $attr;
            $result[$name] = (string)$value;
        }

        return $result;
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
