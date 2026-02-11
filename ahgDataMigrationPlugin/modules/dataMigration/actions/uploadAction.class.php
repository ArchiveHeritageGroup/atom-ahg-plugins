<?php

use AtomFramework\Http\Controllers\AhgController;
class dataMigrationUploadAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        if (!$request->isMethod('post')) {
            $this->redirect(['module' => 'dataMigration', 'action' => 'index']);
        }

        // Get source format and folder options
        $sourceFormat = $request->getParameter('source_format', 'csv');
        $sourceFolder = $request->getParameter('source_folder', '');

        // Check if this is a folder-based import (Preservica server folder)
        if ($this->isPreservicaFormat($sourceFormat) && !empty($sourceFolder)) {
            return $this->handleFolderImport($request, $sourceFormat, $sourceFolder);
        }

        // Otherwise handle file upload
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
        $digitalObjectPath = ($digitalObjectFolder === 'custom') ? $customDigitalPath : $digitalObjectFolder;
        $savedMapping = $request->getParameter('saved_mapping', '');

        // Save file to temp location
        $uploadDir = $this->config('sf_upload_dir') . '/migration';
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

        // Handle ZIP files for Preservica formats
        $digitalObjectMappings = [];
        $extractedFolder = null;

        if (in_array($ext, ['zip', 'pax']) && $this->isPreservicaFormat($sourceFormat)) {
            $result = $this->extractAndScanPreservicaPackage($tempFile, $sourceFormat);
            if ($result['success']) {
                $extractedFolder = $result['extracted_folder'];
                $digitalObjectMappings = $result['digital_object_mappings'];
                // Use the combined OPEX data file
                $tempFile = $result['combined_file'];
                $ext = 'opex';
            } else {
                unlink($tempFile);
                $this->getUser()->setFlash('error', $result['error']);
                $this->redirect(['module' => 'dataMigration', 'action' => 'index']);
            }
        }

        // Parse file and detect headers/rows
        try {
            $detection = $this->parseFile($tempFile, $ext, $sheetIndex, $firstRowHeader, $delimiter, $encoding);
        } catch (\Exception $e) {
            unlink($tempFile);
            if ($extractedFolder && is_dir($extractedFolder)) {
                $this->removeDirectory($extractedFolder);
            }
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
            'digital_object_path' => $digitalObjectPath,
            'source_format' => $sourceFormat,
            'extracted_folder' => $extractedFolder,
            'digital_object_mappings' => $digitalObjectMappings
        ]);

        // Redirect to mapping page
        $this->redirect(['module' => 'dataMigration', 'action' => 'map', 'mapping_id' => $savedMapping]);
    }

    /**
     * Check if source format is a Preservica format.
     */
    protected function isPreservicaFormat($format)
    {
        return in_array($format, ['preservica_opex', 'preservica_xip', 'opex', 'xip']);
    }

    /**
     * Handle import from server folder (Preservica export folder).
     */
    protected function handleFolderImport($request, $sourceFormat, $sourceFolder)
    {
        // Validate folder exists
        if (!is_dir($sourceFolder)) {
            $this->getUser()->setFlash('error', 'Folder not found: ' . $sourceFolder);
            $this->redirect(['module' => 'dataMigration', 'action' => 'index']);
        }

        // Get other options
        $targetType = $request->getParameter('target_type', 'archives');
        $savedMapping = $request->getParameter('saved_mapping', '');

        // Scan folder for OPEX files
        $result = $this->scanPreservicaFolder($sourceFolder);

        if (!$result['success']) {
            $this->getUser()->setFlash('error', $result['error']);
            $this->redirect(['module' => 'dataMigration', 'action' => 'index']);
        }

        if (empty($result['opex_files'])) {
            $this->getUser()->setFlash('error', 'No OPEX files found in folder: ' . $sourceFolder);
            $this->redirect(['module' => 'dataMigration', 'action' => 'index']);
        }

        // Create combined OPEX file for mapping
        $uploadDir = $this->config('sf_upload_dir') . '/migration';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $combinedFile = $this->createCombinedOpexFile($result['opex_files'], $result['digital_object_mappings'], $uploadDir);

        // Parse the combined file
        try {
            $detection = $this->parseFile($combinedFile, 'opex', 0, 1, 'auto', 'auto');
        } catch (\Exception $e) {
            unlink($combinedFile);
            $this->getUser()->setFlash('error', 'Error parsing OPEX files: ' . $e->getMessage());
            $this->redirect(['module' => 'dataMigration', 'action' => 'index']);
        }

        // Store in session
        $this->getUser()->setAttribute('migration_file', $combinedFile);
        $this->getUser()->setAttribute('migration_filename', basename($sourceFolder) . ' (folder)');
        $this->getUser()->setAttribute('migration_detection', $detection);
        $this->getUser()->setAttribute('migration_target_type', $targetType);
        $this->getUser()->setAttribute('migration_saved_mapping', $savedMapping);
        $this->getUser()->setAttribute('migration_options', [
            'sheet_index' => 0,
            'first_row_header' => 1,
            'delimiter' => 'auto',
            'encoding' => 'auto',
            'digital_object_path' => $sourceFolder,
            'source_format' => $sourceFormat,
            'source_folder' => $sourceFolder,
            'extracted_folder' => null,
            'digital_object_mappings' => $result['digital_object_mappings']
        ]);

        // Redirect to mapping page
        $this->redirect(['module' => 'dataMigration', 'action' => 'map', 'mapping_id' => $savedMapping]);
    }

    /**
     * Extract ZIP/PAX and scan for OPEX files with associated digital objects.
     */
    protected function extractAndScanPreservicaPackage($zipPath, $sourceFormat)
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['success' => false, 'error' => 'Cannot open ZIP file'];
        }

        // Create extraction directory
        $extractDir = $this->config('sf_upload_dir') . '/migration/extracted_' . uniqid();
        if (!mkdir($extractDir, 0755, true)) {
            $zip->close();
            return ['success' => false, 'error' => 'Cannot create extraction directory'];
        }

        // Extract all files
        $zip->extractTo($extractDir);
        $zip->close();

        // Scan for OPEX files
        $result = $this->scanPreservicaFolder($extractDir);
        $result['extracted_folder'] = $extractDir;

        if ($result['success'] && !empty($result['opex_files'])) {
            // Create combined file
            $uploadDir = $this->config('sf_upload_dir') . '/migration';
            $combinedFile = $this->createCombinedOpexFile($result['opex_files'], $result['digital_object_mappings'], $uploadDir);
            $result['combined_file'] = $combinedFile;
        }

        return $result;
    }

    /**
     * Scan folder recursively for OPEX files and their associated digital objects.
     */
    protected function scanPreservicaFolder($folder)
    {
        $opexFiles = [];
        $digitalObjectMappings = [];

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($folder, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $ext = strtolower($file->getExtension());

                    if ($ext === 'opex') {
                        $opexPath = $file->getPathname();
                        $opexDir = $file->getPath();
                        $opexBasename = $file->getBasename('.opex');

                        $opexFiles[] = $opexPath;

                        // Find associated digital objects in the same directory
                        $siblingFiles = $this->findSiblingDigitalObjects($opexDir, $opexBasename);

                        if (!empty($siblingFiles)) {
                            // Use relative path from source folder as key
                            $relativePath = str_replace($folder . '/', '', $opexPath);
                            $digitalObjectMappings[$relativePath] = $siblingFiles;

                            // Also map by basename for easier lookup
                            $digitalObjectMappings[$opexBasename] = $siblingFiles;
                        }
                    }
                }
            }

            return [
                'success' => true,
                'opex_files' => $opexFiles,
                'digital_object_mappings' => $digitalObjectMappings,
                'total_opex' => count($opexFiles),
                'total_digital_objects' => array_sum(array_map('count', $digitalObjectMappings))
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error scanning folder: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Find digital object files that are siblings of an OPEX file.
     */
    protected function findSiblingDigitalObjects($directory, $opexBasename)
    {
        $digitalObjects = [];

        // Common digital object extensions
        $mediaExtensions = [
            // Images
            'jpg', 'jpeg', 'png', 'gif', 'tif', 'tiff', 'bmp', 'webp', 'svg',
            // Documents
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp',
            // Audio
            'mp3', 'wav', 'flac', 'aac', 'ogg', 'm4a',
            // Video
            'mp4', 'avi', 'mov', 'mkv', 'wmv', 'flv', 'webm',
            // 3D
            'obj', 'stl', 'glb', 'gltf', 'fbx', 'dae',
            // Archives (content files, not packages)
            'txt', 'rtf', 'html', 'htm', 'xml', 'json'
        ];

        $files = scandir($directory);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $directory . '/' . $file;

            if (!is_file($filePath)) {
                continue;
            }

            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            // Skip OPEX files
            if ($ext === 'opex') {
                continue;
            }

            // Check if it's a media file
            if (in_array($ext, $mediaExtensions)) {
                $fileBasename = pathinfo($file, PATHINFO_FILENAME);

                // Prioritize files with matching basename
                if (strcasecmp($fileBasename, $opexBasename) === 0) {
                    // Exact match - put at front
                    array_unshift($digitalObjects, $filePath);
                } else {
                    // Other files in directory
                    $digitalObjects[] = $filePath;
                }
            }
        }

        return $digitalObjects;
    }

    /**
     * Create a combined OPEX file from multiple OPEX files.
     * This allows mapping UI to work with all records at once.
     */
    protected function createCombinedOpexFile($opexFiles, $digitalObjectMappings, $outputDir)
    {
        $allRecords = [];
        $allHeaders = [];
        
        // ================================================================
        // STEP 1: Build folder hierarchy map from OPEX file paths
        // Maps folder path => legacyId (from Identifier or folder name)
        // ================================================================
        $folderToLegacyId = [];
        $opexByFolder = [];
        
        // First pass: collect all OPEX files and their folder paths
        foreach ($opexFiles as $opexFile) {
            $folderPath = dirname($opexFile);
            $opexByFolder[$folderPath] = $opexFile;
        }
        
        // Sort by path depth (parents before children)
        uksort($opexByFolder, function($a, $b) {
            return substr_count($a, '/') - substr_count($b, '/');
        });
        
        foreach ($opexByFolder as $folderPath => $opexFile) {
            // Parse each OPEX file
            $fileContent = file_get_contents($opexFile);
            $fileContent = preg_replace('/xmlns="[^"]+"/', '', $fileContent);
            
            try {
                $xml = new \SimpleXMLElement($fileContent);
                $xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
                $xml->registerXPathNamespace('dcterms', 'http://purl.org/dc/terms/');
                
                // Get the OPEX basename for digital object lookup
                $opexBasename = pathinfo($opexFile, PATHINFO_FILENAME);
                $opexRelPath = basename(dirname($opexFile)) . '/' . basename($opexFile);
                
                // Parse the OPEX file
                $records = $this->parseOpexXml($xml);
                
                // ================================================================
                // STEP 2: Derive legacyId and parentId from folder structure
                // ================================================================
                foreach ($records as &$record) {
                    // Derive legacyId: Use Identifier_Reference > Identifier > SourceID > folder name
                    $legacyId = $record['Identifier_Reference'] 
                        ?? $record['Identifier'] 
                        ?? $record['SourceID']
                        ?? $record['Transfer_SourceID']
                        ?? $opexBasename;
                    
                    // Clean up legacyId - take first value if pipe-separated
                    if (strpos($legacyId, '|') !== false) {
                        $legacyId = trim(explode('|', $legacyId)[0]);
                    }
                    
                    $record['legacyId'] = $legacyId;
                    $folderToLegacyId[$folderPath] = $legacyId;
                    
                    // Derive parentId from parent folder's legacyId
                    $parentFolderPath = dirname($folderPath);
                    if (isset($folderToLegacyId[$parentFolderPath])) {
                        $record['parentId'] = $folderToLegacyId[$parentFolderPath];
                    } else {
                        $record['parentId'] = ''; // Top-level record
                    }
                    
                    // Try to find digital objects for this record
                    $doFiles = $digitalObjectMappings[$opexBasename] ?? $digitalObjectMappings[$opexRelPath] ?? [];
                    if (!empty($doFiles)) {
                        $record['_digitalObjectPath'] = $doFiles[0];
                        $record['_digitalObjectFilename'] = basename($doFiles[0]);
                        if (count($doFiles) > 1) {
                            $record['_digitalObjectPaths'] = implode('|', $doFiles);
                        }
                    }
                    
                    // Add source OPEX file path for reference
                    $record['_sourceOpexFile'] = $opexFile;
                    
                    // Collect headers
                    foreach (array_keys($record) as $key) {
                        if (!in_array($key, $allHeaders)) {
                            $allHeaders[] = $key;
                        }
                    }
                }
                unset($record); // Break the reference to prevent PHP foreach bug
                $allRecords = array_merge($allRecords, $records);

            } catch (\Exception $e) {
                // Log error but continue with other files
                error_log("Error parsing OPEX file $opexFile: " . $e->getMessage());
            }
        }

        // Sort headers
        $allHeaders = $this->sortOpexHeaders($allHeaders);

        // Create combined XML file
        $combinedFile = $outputDir . '/combined_' . uniqid() . '.opex';

        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xmlContent .= '<OPEXMetadata xmlns="http://www.openpreservationexchange.org/opex/v1.2"' . "\n";
        $xmlContent .= '              xmlns:dc="http://purl.org/dc/elements/1.1/"' . "\n";
        $xmlContent .= '              xmlns:dcterms="http://purl.org/dc/terms/">' . "\n";
        $xmlContent .= '  <DescriptiveMetadata>' . "\n";

        foreach ($allRecords as $record) {
            $xmlContent .= '    <Record>' . "\n";
            foreach ($record as $key => $value) {
                if ($value !== '' && $value !== null) {
                    $safeKey = preg_replace('/[^a-zA-Z0-9_:]/', '_', $key);
                    $safeValue = htmlspecialchars($value, ENT_XML1, 'UTF-8');
                    $xmlContent .= "      <{$safeKey}>{$safeValue}</{$safeKey}>\n";
                }
            }
            $xmlContent .= '    </Record>' . "\n";
        }

        $xmlContent .= '  </DescriptiveMetadata>' . "\n";
        $xmlContent .= '</OPEXMetadata>' . "\n";

        file_put_contents($combinedFile, $xmlContent);

        return $combinedFile;
    }

    /**
     * Parse OPEX XML and return array of records.
     */
    protected function parseOpexXml($xml)
    {
        $records = [];

        // Check for multiple Record elements
        $recordNodes = $xml->xpath('//DescriptiveMetadata/Record');

        if (!empty($recordNodes)) {
            foreach ($recordNodes as $recordNode) {
                $record = $this->extractRecordFromNode($recordNode, $xml);
                if (!empty($record)) {
                    $records[] = $record;
                }
            }
        } else {
            // Single record file
            $record = $this->extractSingleRecord($xml);
            if (!empty($record)) {
                $records[] = $record;
            }
        }

        return $records;
    }

    /**
     * Extract record data from a Record XML node.
     */
    protected function extractRecordFromNode($node, $xml)
    {
        $record = [];

        // Get all child elements
        foreach ($node->children() as $child) {
            $name = $child->getName();
            $value = trim((string)$child);

            if (!empty($value)) {
                if (isset($record[$name])) {
                    $record[$name] .= ' | ' . $value;
                } else {
                    $record[$name] = $value;
                }
            }

            // Check for type attribute (e.g., Identifier type="Reference")
            $type = (string)($child['type'] ?? '');
            if (!empty($type) && !empty($value)) {
                $record[$name . '_' . $type] = $value;
            }
        }

        // Get Dublin Core elements
        $dcNs = 'http://purl.org/dc/elements/1.1/';
        foreach ($node->children($dcNs) as $child) {
            $name = 'dc:' . $child->getName();
            $value = trim((string)$child);
            if (!empty($value)) {
                if (isset($record[$name])) {
                    $record[$name] .= ' | ' . $value;
                } else {
                    $record[$name] = $value;
                }
            }
        }

        // Get DC Terms elements
        $dctermsNs = 'http://purl.org/dc/terms/';
        foreach ($node->children($dctermsNs) as $child) {
            $name = 'dcterms:' . $child->getName();
            $value = trim((string)$child);
            if (!empty($value)) {
                if (isset($record[$name])) {
                    $record[$name] .= ' | ' . $value;
                } else {
                    $record[$name] = $value;
                }
            }
        }

        return $record;
    }

    /**
     * Extract single record from OPEX XML.
     */
    protected function extractSingleRecord($xml)
    {
        $record = [];

        // Properties
        $props = $xml->xpath('//Properties');
        if (!empty($props)) {
            foreach ($props[0]->children() as $child) {
                $name = $child->getName();
                $value = trim((string)$child);
                if (!empty($value)) {
                    $record[$name] = $value;
                }
            }
        }

        // Transfer info
        $transfer = $xml->xpath('//Transfer');
        if (!empty($transfer)) {
            foreach ($transfer[0]->children() as $child) {
                $name = 'Transfer_' . $child->getName();
                $value = trim((string)$child);
                if (!empty($value)) {
                    $record[$name] = $value;
                }
            }
        }

        // DescriptiveMetadata - get all elements
        $dm = $xml->xpath('//DescriptiveMetadata');
        if (!empty($dm)) {
            $this->extractAllFromNode($dm[0], $record, '');
        }

        return $record;
    }

    /**
     * Recursively extract all elements from XML node.
     */
    protected function extractAllFromNode($node, &$record, $prefix)
    {
        foreach ($node->children() as $child) {
            $name = $prefix . $child->getName();
            $value = trim((string)$child);

            $childCount = count($child->children());

            if ($childCount > 0) {
                $this->extractAllFromNode($child, $record, $name . '_');
            } elseif (!empty($value)) {
                if (isset($record[$name])) {
                    $record[$name] .= ' | ' . $value;
                } else {
                    $record[$name] = $value;
                }
            }

            // Handle type attribute
            $type = (string)($child['type'] ?? '');
            if (!empty($type) && !empty($value)) {
                $record[$name . '_' . $type] = $value;
            }
        }

        // DC namespace
        $dcNs = 'http://purl.org/dc/elements/1.1/';
        foreach ($node->children($dcNs) as $child) {
            $name = 'dc:' . $child->getName();
            $value = trim((string)$child);
            if (!empty($value)) {
                $record[$name] = $value;
            }
        }

        // DCTerms namespace
        $dctermsNs = 'http://purl.org/dc/terms/';
        foreach ($node->children($dctermsNs) as $child) {
            $name = 'dcterms:' . $child->getName();
            $value = trim((string)$child);
            if (!empty($value)) {
                $record[$name] = $value;
            }
        }
    }

    /**
     * Remove directory recursively.
     */
    protected function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
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
            $bootstrap = $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
            if (file_exists($bootstrap)) {
                require_once $bootstrap;
            }

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
        $headers = [];
        $records = [];
        $xml->registerXPathNamespace('dcterms', 'http://purl.org/dc/terms/');
        $xml->registerXPathNamespace('opex', 'http://www.openpreservationexchange.org/opex/v1.2');

        // Check for multiple Record elements in DescriptiveMetadata
        $recordNodes = $xml->xpath('//DescriptiveMetadata/Record');

        // Also check for Folder elements (AHG extended OPEX format)
        $folderNodes = $xml->xpath('//Folder');

        if (!empty($recordNodes) && count($recordNodes) > 0) {
            // Multiple records - parse each one
            foreach ($recordNodes as $recordNode) {
                $record = $this->parseOpexRecord($recordNode, $xml);
                if (!empty($record)) {
                    $records[] = $record;
                }
            }
        } elseif (!empty($folderNodes) && count($folderNodes) > 0) {
            // Folder-based structure - parse each folder as a record
            foreach ($folderNodes as $folderNode) {
                $record = $this->parseOpexFolder($folderNode, $xml);
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

        // Remove auto-generated AHG provenance fields from headers (they are internal)
        $headers = array_filter($headers, function($h) {
            return strpos($h, "ahgProvenance") !== 0 && strpos($h, "ahgCondition") !== 0 && strpos($h, "ahgRights") !== 0 && strpos($h, "Rights_") !== 0;
        });
        $headers = array_values($headers); // Re-index

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

        // Extract ALL other child elements (including _digitalObjectPath, etc.)
        foreach ($recordNode->children() as $child) {
            $name = $child->getName();
            $value = trim((string)$child);
            if (!empty($value) && !isset($record[$name])) {
                $record[$name] = $value;
            }
        }

        // Override legacyId with LegacyId element if present (takes priority over Identifier)
        if (!empty($record['LegacyId'])) {
            $record['legacyId'] = $record['LegacyId'];
        }
        // Set parentId from ParentId element
        if (!empty($record['ParentId'])) {
            $record['parentId'] = $record['ParentId'];
        }
        $this->extractCommonOpexFields($record, $recordNode, $xml);

        return $record;
    }

    /**
     * Parse a Folder element from OPEX file.
     */
    protected function parseOpexFolder($folderNode, $xml)
    {
        $record = [];

        // Get folder ID attribute
        $folderId = (string)($folderNode['id'] ?? '');
        if ($folderId) {
            $record['legacyId'] = $folderId;
        }

        // Extract ALL fields from Properties
        if (isset($folderNode->Properties)) {
            foreach ($folderNode->Properties->children() as $child) {
                $name = $child->getName();
                $value = trim((string)$child);
                if (!empty($value)) {
                    $record[$name] = $value;
                }
            }
        }

        // Extract ALL fields directly from the Folder node
        $this->extractAllElements($folderNode, $record, '');

        // Copy LegacyId to legacyId if found (extractAllElements uses exact element name)
        if (!empty($record['LegacyId']) && empty($record['legacyId'])) {
            $record['legacyId'] = $record['LegacyId'];
        }
        // Copy ParentId to parentId if found
        if (!empty($record['ParentId']) && empty($record['parentId'])) {
            $record['parentId'] = $record['ParentId'];
        }

        // Extract Identifier elements with type attribute
        $identifiers = $folderNode->xpath('.//Identifier');
        foreach ($identifiers as $id) {
            $type = (string)($id['type'] ?? 'identifier');
            $value = trim((string)$id);
            if (!empty($value)) {
                $fieldName = 'Identifier_' . preg_replace('/[^a-zA-Z0-9]/', '', $type);
                $record[$fieldName] = $value;
            }
        }

        // Extract History events
        $historyEvents = $folderNode->xpath('.//History/Event');
        if (!empty($historyEvents)) {
            $dates = [];
            $types = [];
            $descriptions = [];
            $agents = [];

            foreach ($historyEvents as $event) {
                $date = (string)($event->EventDate ?? $event->Date ?? '');
                $type = (string)($event->EventType ?? $event->Type ?? '');
                $agent = (string)($event->EventAgent ?? $event->Agent ?? '');
                $desc = (string)($event->EventDescription ?? $event->Description ?? '');

                if (!empty($date) || !empty($type) || !empty($agent) || !empty($desc)) {
                    $dates[] = $date;
                    $types[] = $type;
                    $descriptions[] = $desc;
                    $agents[] = $agent;
                }
            }

            if (!empty($dates)) {
                array_multisort($dates, SORT_ASC, $types, $descriptions, $agents);

                $record['ahgProvenanceEventDates'] = implode('|', $dates);
                $record['ahgProvenanceEventTypes'] = implode('|', $types);
                $record['ahgProvenanceEventDescriptions'] = implode('|', $descriptions);
                $record['ahgProvenanceEventAgents'] = implode('|', $agents);
                $record['ahgProvenanceEventCount'] = (string)count($dates);

                $filteredDates = array_filter($dates);
                if (!empty($filteredDates)) {
                    $record['ahgProvenanceFirstDate'] = reset($filteredDates);
                    $record['ahgProvenanceLastDate'] = end($filteredDates);
                }
            }
        }

        // Extract Rights elements
        $rights = $folderNode->xpath('.//Rights/RightsStatement');
        foreach ($rights as $i => $rs) {
            $prefix = count($rights) > 1 ? "Rights{$i}_" : "Rights_";
            foreach ($rs->children() as $child) {
                $name = $child->getName();
                $value = trim((string)$child);
                if (!empty($value)) {
                    $record[$prefix . $name] = $value;
                }
            }
        }

        return $record;
    }

    /**
     * Recursively extract all elements from an XML node
     */
    protected function extractAllElements($node, &$record, $prefix)
    {
        foreach ($node->children() as $child) {
            $name = $child->getName();
            $value = trim((string)$child);

            $childCount = count($child->children());
            if ($childCount > 0 && empty($value)) {
                $this->extractAllElements($child, $record, $name . '_');
            } elseif (!empty($value)) {
                $fieldName = $prefix . $name;
                if (isset($record[$fieldName]) && $record[$fieldName] !== $value) {
                    $record[$fieldName] .= ' | ' . $value;
                } else {
                    $record[$fieldName] = $value;
                }
            }
        }

        // Dublin Core namespace
        $dcNs = 'http://purl.org/dc/elements/1.1/';
        foreach ($node->children($dcNs) as $child) {
            $name = 'dc:' . $child->getName();
            $value = trim((string)$child);
            if (!empty($value)) {
                $record[$name] = $value;
            }
        }

        // DC Terms namespace
        $dctermsNs = 'http://purl.org/dc/terms/';
        foreach ($node->children($dctermsNs) as $child) {
            $name = 'dcterms:' . $child->getName();
            $value = trim((string)$child);
            if (!empty($value)) {
                $record[$name] = $value;
            }
        }
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

        // Extract LegacyId and ParentId from Record element
        $legacyIdNodes = $xml->xpath('//LegacyId');
        if (!empty($legacyIdNodes)) {
            $record['LegacyId'] = (string)$legacyIdNodes[0];
            if (empty($record['legacyId'])) {
                $record['legacyId'] = (string)$legacyIdNodes[0];
            }
        }
        $parentIdNodes = $xml->xpath('//ParentId');
        if (!empty($parentIdNodes)) {
            $record['ParentId'] = (string)$parentIdNodes[0];
            $record['parentId'] = (string)$parentIdNodes[0];
        }
        $this->extractCommonOpexFields($record, null, $xml);

        return $record;
    }

    /**
     * Extract common OPEX fields including AHG extended fields.
     */
    protected function extractCommonOpexFields(&$record, $recordNode, $xml)
    {
        $context = $recordNode ?? $xml;

        // Digital Object fields
        $filenames = $context->xpath('.//DigitalObject/Filename | .//Filename | .//File | .//Bitstream');
        if (!empty($filenames)) {
            $record['Filename'] = (string)$filenames[0];
            $record['digitalObjectPath'] = (string)$filenames[0];
        }

        $fixity = $context->xpath('.//DigitalObject/Fixity | .//Fixity | .//Checksum');
        if (!empty($fixity)) {
            $algorithm = (string)($fixity[0]['algorithm'] ?? 'SHA-256');
            $record['digitalObjectChecksum'] = $algorithm . ':' . (string)$fixity[0];
        }

        $format = $context->xpath('.//DigitalObject/FormatName | .//FormatName');
        if (!empty($format)) {
            $record['digitalObjectMimeType'] = (string)$format[0];
        }

        $size = $context->xpath('.//DigitalObject/FileSize | .//FileSize');
        if (!empty($size)) {
            $record['digitalObjectSize'] = (string)$size[0];
        }

        // Security fields
        $security = $xml->xpath('//Properties/SecurityDescriptor | //SecurityDescriptor');
        if (!empty($security)) {
            $record['ahgSecurityClassification'] = (string)$security[0];
            if (empty($record['accessConditions'])) {
                $record['accessConditions'] = ucfirst((string)$security[0]);
            }
        }

        // Rights fields
        $rightsText = [];
        if (!empty($record['dc:rights'])) {
            $rightsText[] = "Rights: " . $record['dc:rights'];
        }
        if (!empty($record['dcterms:license'])) {
            $rightsText[] = "License: " . $record['dcterms:license'];
        }
        if (!empty($record['dcterms:rightsHolder'])) {
            $rightsText[] = "Rights Holder: " . $record['dcterms:rightsHolder'];
        }
        if (!empty($record['dcterms:accessRights'])) {
            $rightsText[] = "Access: " . $record['dcterms:accessRights'];
        }
        if (!empty($rightsText)) {
            $record['ahgRightsStatement'] = implode(' | ', $rightsText);
        }

        // Provenance/History fields
        $historyEvents = $xml->xpath('//History/Event | //opex:History/opex:Event');
        if (!empty($historyEvents)) {
            $dates = [];
            $types = [];
            $descriptions = [];
            $agents = [];

            foreach ($historyEvents as $event) {
                $date = (string)($event->EventDate ?? $event->Date ?? '');
                $type = (string)($event->EventType ?? $event->Type ?? '');
                $agent = (string)($event->EventAgent ?? $event->Agent ?? '');
                $desc = (string)($event->EventDescription ?? $event->Description ?? '');

                $dates[] = $date;
                $types[] = $type;
                $descriptions[] = $desc;
                $agents[] = $agent;
            }

            array_multisort($dates, SORT_ASC, $types, $descriptions, $agents);

            $record['ahgProvenanceEventDates'] = implode('|', $dates);
            $record['ahgProvenanceEventTypes'] = implode('|', $types);
            $record['ahgProvenanceEventDescriptions'] = implode('|', $descriptions);
            $record['ahgProvenanceEventAgents'] = implode('|', $agents);
            $record['ahgProvenanceEventCount'] = (string)count($dates);

            $filteredDates = array_filter($dates);
            if (!empty($filteredDates)) {
                $record['ahgProvenanceFirstDate'] = reset($filteredDates);
                $record['ahgProvenanceLastDate'] = end($filteredDates);
            }

            $record['ahgProvenanceHistory'] = '';
        }

        if (!empty($record['dcterms:provenance']) && empty($record['ahgProvenanceHistory'])) {
            $record['ahgProvenanceHistory'] = $record['dcterms:provenance'];
        }

        // Relationships
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
            'legacyId', 'LegacyId', 'ParentId', 'LevelOfDescription', 'Identifier_Reference', 'Identifier_AtoM_ID', 'title', 'dc:title',
            'scopeAndContent', 'dc:description', 'Description',
            'dc:date', 'dcterms:created', 'dcterms:modified',
            'dc:creator', 'dc:contributor', 'dc:publisher',
            'dc:subject', 'dc:coverage', 'dcterms:spatial',
            'dc:format', 'dcterms:extent', 'dc:language', 'dc:type',
            '_digitalObjectPath', '_digitalObjectFilename', '_digitalObjectPaths',
            'Filename', 'digitalObjectPath', 'digitalObjectChecksum', 'digitalObjectMimeType', 'digitalObjectSize',
            'accessConditions', 'reproductionConditions', 'dc:rights', 'dcterms:accessRights', 'dcterms:license',
            'ahgSecurityClassification', 'SecurityDescriptor',
            'ahgRightsStatement', 'ahgRightsStructured', 'dcterms:rightsHolder',
            'ahgProvenanceHistory', 'ahgProvenanceEventCount', 'ahgProvenanceFirstDate', 'ahgProvenanceLastDate',
            'dcterms:provenance',
            'ahgRelationships',
            'Transfer_SourceID', 'Transfer_Manifest',
            '_sourceOpexFile'
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

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('/metadata\.xml$/i', $name) || preg_match('/\.xip$/i', $name)) {
                $content = $zip->getFromIndex($i);
                $records = array_merge($records, $this->parseXipContent($content));
            }
        }

        $zip->close();

        foreach ($records as $record) {
            foreach (array_keys($record) as $key) {
                if (!in_array($key, $headers)) {
                    $headers[] = $key;
                }
            }
        }

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

    protected function parseXipContent($content)
    {
        $records = [];

        try {
            $content = preg_replace('/xmlns="[^"]+"/', '', $content);

            $xml = new \SimpleXMLElement($content);
            $xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');

            $objects = $xml->xpath('//StructuralObject');

            foreach ($objects as $obj) {
                $record = [];

                foreach ($obj->children() as $child) {
                    $name = $child->getName();
                    $value = (string)$child;
                    $record[$name] = $value;
                }

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
            return $this->parseGenericXmlContent($content);
        }

        return $records;
    }

    protected function parseGenericXml($filepath)
    {
        $content = file_get_contents($filepath);
        return $this->parseGenericXmlFromContent($content);
    }

    protected function parseGenericXmlFromContent($content)
    {
        $records = [];

        try {
            $xml = new \SimpleXMLElement($content);
            $xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
            $xml->registerXPathNamespace('dcterms', 'http://purl.org/dc/terms/');

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

        $headers = [];
        foreach ($records as $record) {
            foreach (array_keys($record) as $key) {
                if (!in_array($key, $headers)) {
                    $headers[] = $key;
                }
            }
        }

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

    protected function parseGenericXmlContent($content)
    {
        return $this->parseGenericXmlFromContent($content);
    }

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
