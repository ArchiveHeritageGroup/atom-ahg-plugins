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
        $digitalObjectFolder = $request->getParameter('digital_object_folder', '');
        $customDigitalPath = $request->getParameter('custom_digital_path', '');
        // Resolve digital object path
        $digitalObjectPath = ($digitalObjectFolder === 'custom') ? $customDigitalPath : $digitalObjectFolder;
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
            'encoding' => $encoding,
            'digital_object_path' => $digitalObjectPath
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
        $xml->registerXPathNamespace('opex', 'http://www.openpreservationexchange.org/opex/v1.2');

        $headers = [];
        $records = [];

        // Check for multiple Record elements in DescriptiveMetadata
        $recordNodes = $xml->xpath('//DescriptiveMetadata/Record');

        if (!empty($recordNodes) && count($recordNodes) > 0) {
            // Multiple records - parse each one
            foreach ($recordNodes as $recordNode) {
                $record = $this->parseOpexRecord($recordNode, $xml);
                if (!empty($record)) {
                    $records[] = $record;
                }
            }
        } else {
            // Single record file - extract from Properties and DescriptiveMetadata
            $record = $this->parseOpexSingleRecord($xml);
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

        // Sort headers to group related fields
        $headers = $this->sortOpexHeaders($headers);

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
     * Parse a single OPEX Record element.
     */
    protected function parseOpexRecord($recordNode, $xml)
    {
        $record = [];

        // Get Identifiers with their type attribute
        foreach ($recordNode->xpath('Identifier') as $id) {
            $type = (string)($id['type'] ?? 'ID');
            $value = (string)$id;
            $key = 'Identifier_' . $type;
            $record[$key] = $value;
            // Also set legacyId from first identifier
            if (empty($record['legacyId'])) {
                $record['legacyId'] = $value;
            }
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
        $dcTerms = ['extent', 'provenance', 'accessRights', 'created', 'modified', 
                    'license', 'rightsHolder', 'spatial', 'temporal'];
        foreach ($dcTerms as $term) {
            $nodes = $recordNode->xpath("dcterms:{$term}");
            if (!empty($nodes)) {
                $values = [];
                foreach ($nodes as $node) {
                    $values[] = (string)$node;
                }
                $record['dcterms:' . $term] = implode(' | ', $values);
            }
        }

        // Extract common fields to standard names
        $this->extractCommonOpexFields($record, $recordNode, $xml);

        return $record;
    }

    /**
     * Parse single record OPEX file.
     */
    protected function parseOpexSingleRecord($xml)
    {
        $record = [];

        // Properties
        $props = $xml->xpath('//Properties');
        if (!empty($props)) {
            foreach ($props[0]->children() as $child) {
                $name = $child->getName();
                $record[$name] = (string)$child;
                
                // Map to standard fields
                if ($name === 'Title') $record['title'] = (string)$child;
                if ($name === 'Description') $record['scopeAndContent'] = (string)$child;
                if ($name === 'SecurityDescriptor') {
                    $record['ahgSecurityClassification'] = (string)$child;
                    $record['accessConditions'] = ucfirst((string)$child);
                }
            }
        }

        // Transfer info
        $transfer = $xml->xpath('//Transfer');
        if (!empty($transfer)) {
            foreach ($transfer[0]->children() as $child) {
                $name = $child->getName();
                if ($name === 'SourceID') {
                    $record['Transfer_SourceID'] = (string)$child;
                    $record['legacyId'] = (string)$child;
                } elseif ($name === 'Manifest') {
                    $files = $child->xpath('Files');
                    $record['Transfer_Manifest'] = !empty($files) ? (string)$files[0] . ' files' : '';
                } else {
                    $record['Transfer_' . $name] = (string)$child;
                }
            }
        }

        // Identifiers
        $identifiers = $xml->xpath('//Properties/Identifiers/Identifier | //Identifier');
        foreach ($identifiers as $id) {
            $type = (string)($id['type'] ?? 'ID');
            $record['Identifier_' . $type] = (string)$id;
            if (empty($record['legacyId'])) {
                $record['legacyId'] = (string)$id;
            }
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

        // DC Terms
        $dcTerms = ['extent', 'provenance', 'accessRights', 'created', 'modified', 
                    'license', 'rightsHolder', 'spatial', 'temporal'];
        foreach ($dcTerms as $term) {
            $nodes = $xml->xpath("//dcterms:{$term}");
            if (!empty($nodes)) {
                $values = [];
                foreach ($nodes as $node) {
                    $values[] = (string)$node;
                }
                $record['dcterms:' . $term] = implode(' | ', $values);
            }
        }

        // Extract extended fields
        $this->extractCommonOpexFields($record, null, $xml);

        return $record;
    }

    /**
     * Extract common OPEX fields including AHG extended fields.
     */
    protected function extractCommonOpexFields(&$record, $recordNode, $xml)
    {
        // =====================================================
        // DIGITAL OBJECT FIELDS
        // =====================================================
        
        // Look for DigitalObject/Filename elements
        $context = $recordNode ?? $xml;
        $filenames = $context->xpath('.//DigitalObject/Filename | .//Filename | .//File | .//Bitstream');
        if (!empty($filenames)) {
            $record['Filename'] = (string)$filenames[0];
            $record['digitalObjectPath'] = (string)$filenames[0];
        }

        // Fixity/Checksum
        $fixity = $context->xpath('.//DigitalObject/Fixity | .//Fixity | .//Checksum');
        if (!empty($fixity)) {
            $algorithm = (string)($fixity[0]['algorithm'] ?? 'SHA-256');
            $record['digitalObjectChecksum'] = $algorithm . ':' . (string)$fixity[0];
        }

        // File format
        $format = $context->xpath('.//DigitalObject/FormatName | .//FormatName');
        if (!empty($format)) {
            $record['digitalObjectMimeType'] = (string)$format[0];
        }

        // File size
        $size = $context->xpath('.//DigitalObject/FileSize | .//FileSize');
        if (!empty($size)) {
            $record['digitalObjectSize'] = (string)$size[0];
        }

        // =====================================================
        // SECURITY & ACCESS FIELDS (AHG)
        // =====================================================
        
        $security = $xml->xpath('//Properties/SecurityDescriptor | //SecurityDescriptor');
        if (!empty($security)) {
            $record['ahgSecurityClassification'] = (string)$security[0];
            if (empty($record['accessConditions'])) {
                $record['accessConditions'] = ucfirst((string)$security[0]);
            }
        }

        // =====================================================
        // RIGHTS FIELDS (AHG Extended)
        // =====================================================
        
        $rightsText = [];
        
        // DC Rights
        if (!empty($record['dc:rights'])) {
            $rightsText[] = "Rights: " . $record['dc:rights'];
        }
        
        // License
        if (!empty($record['dcterms:license'])) {
            $rightsText[] = "License: " . $record['dcterms:license'];
        }
        
        // Rights Holder
        if (!empty($record['dcterms:rightsHolder'])) {
            $rightsText[] = "Rights Holder: " . $record['dcterms:rightsHolder'];
        }
        
        // Access Rights
        if (!empty($record['dcterms:accessRights'])) {
            $rightsText[] = "Access: " . $record['dcterms:accessRights'];
        }
        
        if (!empty($rightsText)) {
            $record['ahgRightsStatement'] = implode(' | ', $rightsText);
        }

        // Parse Rights elements (structured)
        $rightsElements = $xml->xpath('//Rights/RightsStatement');
        if (!empty($rightsElements)) {
            $structuredRights = [];
            foreach ($rightsElements as $rs) {
                $basis = (string)($rs->RightsBasis ?? '');
                $status = (string)($rs->CopyrightStatus ?? '');
                $note = (string)($rs->CopyrightNote ?? $rs->LicenseNote ?? $rs->PolicyNote ?? $rs->StatuteNote ?? '');
                
                $rStr = $basis;
                if ($status) $rStr .= " ($status)";
                if ($note) $rStr .= ": $note";
                $structuredRights[] = $rStr;
            }
            $record['ahgRightsStructured'] = implode(' || ', $structuredRights);
        }

        // =====================================================
        // PROVENANCE / HISTORY FIELDS (AHG)
        // =====================================================
        
        $historyEvents = $xml->xpath('//History/Event | //opex:History/opex:Event');
        if (!empty($historyEvents)) {
            $provenanceText = [];
            $eventCount = 0;
            $firstDate = null;
            $lastDate = null;
            
            foreach ($historyEvents as $event) {
                $eventCount++;
                $date = (string)($event->EventDate ?? $event->Date ?? '');
                $type = (string)($event->EventType ?? $event->Type ?? '');
                $agent = (string)($event->EventAgent ?? $event->Agent ?? '');
                $desc = (string)($event->EventDescription ?? $event->Description ?? '');
                
                // Track first/last dates
                if ($date) {
                    if (!$firstDate || strtotime($date) < strtotime($firstDate)) {
                        $firstDate = $date;
                    }
                    if (!$lastDate || strtotime($date) > strtotime($lastDate)) {
                        $lastDate = $date;
                    }
                }
                
                $eventStr = $date ?: 'Unknown date';
                $eventStr .= ': ' . ($type ?: 'Event');
                if ($desc) $eventStr .= ' - ' . $desc;
                if ($agent) $eventStr .= ' (by ' . $agent . ')';
                $provenanceText[] = $eventStr;
            }
            
            $record['ahgProvenanceHistory'] = implode(' || ', $provenanceText);
            $record['ahgProvenanceEventCount'] = (string)$eventCount;
            if ($firstDate) $record['ahgProvenanceFirstDate'] = $firstDate;
            if ($lastDate) $record['ahgProvenanceLastDate'] = $lastDate;
        }

        // Also check dcterms:provenance
        if (!empty($record['dcterms:provenance']) && empty($record['ahgProvenanceHistory'])) {
            $record['ahgProvenanceHistory'] = $record['dcterms:provenance'];
        }

        // =====================================================
        // RELATIONSHIPS
        // =====================================================
        
        $relationships = $xml->xpath('//Relationships/Relationship');
        if (!empty($relationships)) {
            $relText = [];
            foreach ($relationships as $rel) {
                $type = (string)($rel['type'] ?? $rel->Type ?? 'related');
                $target = (string)($rel['target'] ?? $rel->Target ?? $rel);
                $relText[] = "$type: $target";
            }
            $record['ahgRelationships'] = implode(' | ', $relText);
        }
    }

    /**
     * Sort OPEX headers to group related fields.
     */
    protected function sortOpexHeaders($headers)
    {
        $priority = [
            // Core identification
            'legacyId', 'Identifier_Reference', 'Identifier_AtoM_ID', 'title', 'dc:title',
            // Description
            'scopeAndContent', 'dc:description', 'Description',
            // Dates
            'dc:date', 'dcterms:created', 'dcterms:modified',
            // Creators/Contributors
            'dc:creator', 'dc:contributor', 'dc:publisher',
            // Access points
            'dc:subject', 'dc:coverage', 'dcterms:spatial',
            // Physical description
            'dc:format', 'dcterms:extent', 'dc:language', 'dc:type',
            // Digital objects
            'Filename', 'digitalObjectPath', 'digitalObjectChecksum', 'digitalObjectMimeType', 'digitalObjectSize',
            // Rights (standard AtoM)
            'accessConditions', 'reproductionConditions', 'dc:rights', 'dcterms:accessRights', 'dcterms:license',
            // Security (AHG)
            'ahgSecurityClassification', 'SecurityDescriptor',
            // Rights (AHG Extended)
            'ahgRightsStatement', 'ahgRightsStructured', 'dcterms:rightsHolder',
            // Provenance (AHG)
            'ahgProvenanceHistory', 'ahgProvenanceEventCount', 'ahgProvenanceFirstDate', 'ahgProvenanceLastDate',
            'dcterms:provenance',
            // Relationships
            'ahgRelationships',
            // Transfer metadata
            'Transfer_SourceID', 'Transfer_Manifest',
        ];

        usort($headers, function($a, $b) use ($priority) {
            $posA = array_search($a, $priority);
            $posB = array_search($b, $priority);
            
            if ($posA === false) $posA = 999;
            if ($posB === false) $posB = 999;
            
            if ($posA === $posB) {
                return strcmp($a, $b);
            }
            return $posA - $posB;
        });

        return $headers;
    }
     
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
