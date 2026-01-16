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
        if (in_array($ext, ['opex', 'xml'])) {
            return $this->parseOpexFile($filepath);
        }
        
        if (in_array($ext, ['pax', 'zip'])) {
            return $this->parsePaxFile($filepath);
        }

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
     * Parse Preservica OPEX XML file.
     */
    protected function parseOpexFile($filepath)
    {
        $content = file_get_contents($filepath);
        
        // Check if it's actually OPEX format
        if (strpos($content, 'opex:') === false && strpos($content, 'OPEXMetadata') === false) {
            // Might be generic XML, try Dublin Core
            return $this->parseDublinCoreXml($filepath, $content);
        }

        $xml = new \SimpleXMLElement($content);
        $xml->registerXPathNamespace('opex', 'http://www.openpreservationexchange.org/opex/v1.2');
        $xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
        $xml->registerXPathNamespace('dcterms', 'http://purl.org/dc/terms/');

        $headers = [];
        $rows = [];
        $record = [];

        // Extract OPEX properties
        $properties = $xml->xpath('//opex:Properties') ?: $xml->xpath('//Properties');
        if (!empty($properties)) {
            $props = $properties[0];
            foreach ($props->children() as $child) {
                $name = $child->getName();
                $value = (string)$child;
                if (!in_array($name, $headers)) {
                    $headers[] = $name;
                }
                $record[$name] = $value;
            }
        }

        // Extract Transfer info
        $transfer = $xml->xpath('//opex:Transfer') ?: $xml->xpath('//Transfer');
        if (!empty($transfer)) {
            $trans = $transfer[0];
            foreach ($trans->children() as $child) {
                $name = 'Transfer_' . $child->getName();
                $value = (string)$child;
                if (!in_array($name, $headers)) {
                    $headers[] = $name;
                }
                $record[$name] = $value;
            }
        }

        // Extract Dublin Core metadata
        $dcElements = [
            'dc:title', 'dc:creator', 'dc:subject', 'dc:description', 'dc:publisher',
            'dc:contributor', 'dc:date', 'dc:type', 'dc:format', 'dc:identifier',
            'dc:source', 'dc:language', 'dc:coverage', 'dc:rights',
            'dcterms:accessRights', 'dcterms:provenance', 'dcterms:extent'
        ];

        foreach ($dcElements as $element) {
            $parts = explode(':', $element);
            $localName = $parts[1];
            $nodes = $xml->xpath('//' . $element);
            
            if (!empty($nodes)) {
                $values = [];
                foreach ($nodes as $node) {
                    $values[] = (string)$node;
                }
                $header = $element;
                if (!in_array($header, $headers)) {
                    $headers[] = $header;
                }
                $record[$header] = implode(' | ', $values);
            }
        }

        // Extract identifiers
        $identifiers = $xml->xpath('//opex:Identifier') ?: $xml->xpath('//Identifier');
        foreach ($identifiers as $id) {
            $type = (string)($id['type'] ?? 'Identifier');
            $value = (string)$id;
            $header = 'Identifier_' . $type;
            if (!in_array($header, $headers)) {
                $headers[] = $header;
            }
            $record[$header] = $value;
        }

        // Build row from record
        $row = [];
        foreach ($headers as $h) {
            $row[] = $record[$h] ?? '';
        }
        $rows[] = $row;

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
            $xml = new \SimpleXMLElement($content);
            $xml->registerXPathNamespace('xip', 'http://preservica.com/XIP/v6.0');
            $xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');

            // Find all StructuralObjects
            $objects = $xml->xpath('//xip:StructuralObject') ?: $xml->xpath('//StructuralObject');
            
            foreach ($objects as $obj) {
                $record = [];
                
                foreach ($obj->children() as $child) {
                    $name = $child->getName();
                    $value = (string)$child;
                    $record[$name] = $value;
                }

                // Extract Dublin Core if present
                $dcRecord = $xml->xpath("//xip:Metadata[@ref='" . ($record['Ref'] ?? '') . "']//dc:*");
                if (empty($dcRecord)) {
                    $dcRecord = $xml->xpath("//Metadata//dc:*");
                }
                
                foreach ($dcRecord as $dc) {
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
                $objects = $xml->xpath('//xip:ContentObject') ?: $xml->xpath('//ContentObject');
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
            return $this->parseGenericXml($content);
        }

        return $records;
    }

    /**
     * Parse generic Dublin Core XML.
     */
    protected function parseDublinCoreXml($filepath, $content = null)
    {
        if ($content === null) {
            $content = file_get_contents($filepath);
        }
        
        $xml = new \SimpleXMLElement($content);
        $xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
        $xml->registerXPathNamespace('dcterms', 'http://purl.org/dc/terms/');

        $headers = [];
        $rows = [];
        $record = [];

        // Try to find dc:record or oai_dc:dc elements
        $dcRecords = $xml->xpath('//dc:record') ?: $xml->xpath('//oai_dc:dc') ?: [$xml];

        foreach ($dcRecords as $dcRecord) {
            $rec = [];
            foreach ($dcRecord->children('http://purl.org/dc/elements/1.1/') as $child) {
                $name = 'dc:' . $child->getName();
                $value = (string)$child;
                if (!in_array($name, $headers)) {
                    $headers[] = $name;
                }
                if (isset($rec[$name])) {
                    $rec[$name] .= ' | ' . $value;
                } else {
                    $rec[$name] = $value;
                }
            }
            foreach ($dcRecord->children('http://purl.org/dc/terms/') as $child) {
                $name = 'dcterms:' . $child->getName();
                $value = (string)$child;
                if (!in_array($name, $headers)) {
                    $headers[] = $name;
                }
                if (isset($rec[$name])) {
                    $rec[$name] .= ' | ' . $value;
                } else {
                    $rec[$name] = $value;
                }
            }
            
            $row = [];
            foreach ($headers as $h) {
                $row[] = $rec[$h] ?? '';
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
     * Parse generic XML to array.
     */
    protected function parseGenericXml($content)
    {
        $records = [];
        
        try {
            $xml = new \SimpleXMLElement($content);
            
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

        return $records;
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
