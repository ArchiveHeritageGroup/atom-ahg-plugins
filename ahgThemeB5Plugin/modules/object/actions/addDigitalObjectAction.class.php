<?php
use AtomExtensions\Services\AclService;
use Illuminate\Database\Capsule\Manager as DB;

/*
 * This file is part of the Access to Memory (AtoM) software.
 * Modified by The AHG to include Universal Metadata Extraction
 */

// Include the metadata extraction trait
require_once sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/lib/arMetadataExtractionTrait.php';

/**
 * Digital Object add component with metadata extraction.
 */
class ObjectAddDigitalObjectAction extends sfAction
{
    use arMetadataExtractionTrait;

    protected $uploadedFilePath = null;

    public function execute($request)
    {
        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        $this->resource = $this->getRoute()->resource;

        // Get repository to test upload limits
        if ($this->resource instanceof QubitInformationObject) {
            $this->repository = $this->resource->getRepository(['inherit' => true]);
        } elseif ($this->resource instanceof QubitActor) {
            $this->repository = $this->resource->getMaintainingRepository();
        }

        // Check that object exists and that it is not the root
        if (!isset($this->resource) || !isset($this->resource->parent)) {
            $this->forward404();
        }

        // Assemble resource description
        sfContext::getInstance()->getConfiguration()->loadHelpers(['Qubit']);

        if ($this->resource instanceof QubitActor) {
            $this->resourceDescription = render_title($this->resource);
        } elseif ($this->resource instanceof QubitInformationObject) {
            $this->resourceDescription = '';
            if (isset($this->resource->identifier)) {
                $this->resourceDescription .= $this->resource->identifier.' - ';
            }
            $this->resourceDescription .= render_title(new sfIsadPlugin($this->resource));
        }

        // Check if already exists a digital object
        if (null !== $digitalObject = $this->resource->getDigitalObject()) {
            $this->redirect([$digitalObject, 'module' => 'digitalobject', 'action' => 'edit']);
        }

        // Check user authorization
        if (!AclService::check($this->resource, 'update')) {
            AclService::forwardUnauthorized();
        }

        // Check if uploads are allowed
        if (!QubitDigitalObject::isUploadAllowed()) {
            AclService::forwardToSecureAction();
        }

        // Add form fields
        $this->addFields($request);

        // Process form
        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters(), $request->getFiles());
            if ($this->form->isValid()) {
                $this->processForm();

                $this->resource->save();

                // Extract and apply metadata AFTER digital object is created
                // Wrapped in try-catch to ensure upload succeeds even if metadata fails
                // DISABLED - debugging hang issue
                // // Re-enabled with logging only
                // Run metadata extraction in background (async)
                $this->runMetadataBackground();

                if ($this->resource instanceof QubitInformationObject) {
                    $this->resource->updateXmlExports();
                }
                $this->redirect([$this->resource, 'module' => 'object']);
            }
        }
    }

    /**
     * Upload the asset and create digital object with derivatives.
     */
    public function processForm()
    {
        $digitalObject = new QubitDigitalObject();

        if (null !== $this->form->getValue('file')) {
            $name = $this->form->getValue('file')->getOriginalName();
            $content = file_get_contents($this->form->getValue('file')->getTempName());
            $digitalObject->assets[] = new QubitAsset($name, $content);
            $digitalObject->usageId = QubitTerm::MASTER_ID;
            
            // Store temp path for metadata extraction
            $this->uploadedFilePath = $this->form->getValue('file')->getTempName();
        } elseif (null !== $this->form->getValue('url')) {
            try {
                $digitalObject->importFromURI($this->form->getValue('url'));
            } catch (sfException $e) {
                $this->logMessage($e->getMessage(), 'err');
            }
        }

        $this->resource->digitalObjectsRelatedByobjectId[] = $digitalObject;
    }

    /**
     * Safe wrapper for metadata extraction - never throws, never breaks upload
     */
    protected function safeExtractAndApplyMetadata()
    {
        // Check if metadata extraction is enabled in AHG settings
        $extractEnabled = $this->getAhgSetting('meta_extract_on_upload', 'true') === 'true';
        $autoPopulate = $this->getAhgSetting('meta_auto_populate', 'true') === 'true';
        
        if (!$extractEnabled) {
            error_log('Metadata extraction disabled in AHG settings');
            return;
        }

        try {
            $this->extractAndApplyMetadata($autoPopulate);
        } catch (\Throwable $e) {
            // Log error but don't throw - upload must succeed
            error_log('Metadata extraction failed (non-fatal): ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
        }
    }
    
    protected function getAhgSetting($key, $default = null)
    {
        $value = DB::table('ahg_settings')
            ->where('setting_key', $key)
            ->value('setting_value');
        return $value !== null ? $value : $default;
    }

    /**
     * Extract and apply metadata after upload
     */
    protected function extractAndApplyMetadata($autoPopulate = true)
    {
        // Only for information objects
        if (!($this->resource instanceof QubitInformationObject)) {
            return;
        }

        // Get the digital object
        $digitalObject = $this->resource->getDigitalObject();
        if (!$digitalObject) {
            error_log('No digital object found for metadata extraction');
            return;
        }

        // Get file path - try the saved digital object path first (more reliable)
        $filePath = null;
        
        // Get from saved digital object
        $savedPath = $digitalObject->getAbsolutePath();
        if ($savedPath && file_exists($savedPath)) {
            $filePath = $savedPath;
        }
        // Fallback to temp path
        elseif (!empty($this->uploadedFilePath) && file_exists($this->uploadedFilePath)) {
            $filePath = $this->uploadedFilePath;
        }

        if (!$filePath || !file_exists($filePath)) {
            error_log('File not found for metadata extraction: ' . ($filePath ?? 'null'));
            return;
        }

        error_log('=== Starting metadata extraction for: ' . basename($filePath));

        // Extract metadata using trait method
        $metadata = $this->extractAllMetadata($filePath);

        if (empty($metadata)) {
            error_log('No metadata extracted from: ' . basename($filePath));
            return;
        }

        error_log('Metadata extracted successfully, applying to information object...');

        // Apply metadata to information object - simple fields only (no actors/terms)
        if ($autoPopulate) {
            // Apply only physical characteristics - minimal and safe
            $this->applyPhysicalCharacteristicsOnly($this->resource->id, $metadata);
        } else {
            error_log('Auto-populate disabled - metadata extracted but not applied');
        }

        error_log('Metadata extraction and application complete');
    }

    protected function addFields($request)
    {
        // Single upload
        if (0 < count($request->getFiles())) {
            $this->form->setValidator('file', new sfValidatorFile());
        }
        $this->form->setWidget('file', new sfWidgetFormInputFile());

        // URL
        if (isset($request->url) && 'http://' != $request->url) {
            $this->form->setValidator('url', new QubitValidatorUrl());
        }
        $this->form->setDefault('url', 'http://');
        $this->form->setWidget('url', new sfWidgetFormInput());
    }

    protected function applyPhysicalCharacteristicsOnly($ioId, $metadata)
    {
        error_log("=== APPLYING PHYSICAL CHARACTERISTICS ONLY ===");
        
        $extractorInfo = $metadata['_extractor'] ?? [];
        $summary = $extractorInfo['summary'] ?? null;
        
        if (empty($summary)) {
            error_log("No summary to apply");
            return;
        }
        
        // Limit summary size
        if (strlen($summary) > 5000) {
            $summary = substr($summary, 0, 5000) . "\n... (truncated)";
        }
        
        try {
            $exists = DB::table('information_object_i18n')
                ->where('id', $ioId)
                ->where('culture', 'en')
                ->exists();
            
            if ($exists) {
                DB::table('information_object_i18n')
                    ->where('id', $ioId)
                    ->where('culture', 'en')
                    ->update(['physical_characteristics' => $summary]);
                error_log("Updated physical_characteristics");
            } else {
                error_log("No i18n record found for IO " . $ioId);
            }
        } catch (\Exception $e) {
            error_log("Error updating physical_characteristics: " . $e->getMessage());
        }
    }


    protected function testMetadataLogging()
    {
        error_log("=== TEST METADATA LOGGING START ===");
        
        try {
            $digitalObject = $this->resource->getDigitalObject();
            if (!$digitalObject) {
                error_log("No digital object");
                return;
            }
            
            error_log("Digital object ID: " . $digitalObject->id);
            
            $filePath = $digitalObject->getAbsolutePath();
            error_log("File path: " . $filePath);
            
            if (!$filePath || !file_exists($filePath)) {
                error_log("File not found");
                return;
            }
            
            error_log("File exists, size: " . filesize($filePath));
            error_log("=== TEST METADATA LOGGING END ===");
            
        } catch (\Throwable $e) {
            error_log("Test error: " . $e->getMessage());
        }
    }


    protected function applyMetadataLaravel()
    {
        error_log("=== APPLY METADATA LARAVEL START ===");
        try {
            require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
            
            if (!($this->resource instanceof QubitInformationObject)) {
                return;
            }
            
            $digitalObject = $this->resource->getDigitalObject();
            if (!$digitalObject) {
                error_log("No digital object");
                return;
            }
            
            $filePath = $digitalObject->getAbsolutePath();
            if (!$filePath || !file_exists($filePath)) {
                error_log("File not found: " . $filePath);
                return;
            }
            
            error_log("Extracting from: " . basename($filePath));
            
            // Extract metadata
            $extractor = new ahgUniversalMetadataExtractor($filePath);
            $metadata = $extractor->extractAll();
            if (empty($metadata)) {
                error_log("No metadata extracted");
                return;
            }
            
            // Detect template type (dam, museum, isad)
            $templateType = $this->detectTemplateType();
            error_log("Template type: " . $templateType);
            
            // Get field mappings from settings
            $mappings = $this->getFieldMappings($templateType);
            
            // Apply mapped metadata
            $this->applyMappedMetadata($metadata, $mappings, $extractor->formatSummary());
            
        } catch (\Throwable $e) {
            error_log("Metadata error: " . $e->getMessage());
        }
        error_log("=== APPLY METADATA LARAVEL END ===");
    }
    
    /**
     * Detect template type: dam, museum, or isad
     */
    protected function detectTemplateType()
    {
        // Check level of description sector
        $levelId = $this->resource->levelOfDescriptionId;
        if ($levelId) {
            $sector = \Illuminate\Database\Capsule\Manager::table('level_of_description_sector')
                ->where('term_id', $levelId)
                ->value('sector');
            if ($sector === 'dam') return 'dam';
            if ($sector === 'museum') return 'museum';
        }
        
        // Check display standard
        $displayStandardId = $this->resource->displayStandardId ?? null;
        if ($displayStandardId) {
            $termName = \Illuminate\Database\Capsule\Manager::table('term_i18n')
                ->where('id', $displayStandardId)
                ->where('culture', 'en')
                ->value('name');
            $termName = strtolower($termName ?? '');
            if (strpos($termName, 'dam') !== false || strpos($termName, 'digital asset') !== false) {
                return 'dam';
            }
            if (strpos($termName, 'museum') !== false || strpos($termName, 'spectrum') !== false) {
                return 'museum';
            }
        }
        
        return 'isad';
    }
    
    /**
     * Get field mappings for template type
     */
    protected function getFieldMappings($templateType)
    {
        $suffix = $templateType === 'isad' ? 'isad' : ($templateType === 'museum' ? 'museum' : 'dam');
        
        $mappings = [];
        $fields = ['title', 'creator', 'keywords', 'description', 'date', 'copyright', 'technical', 'gps'];
        
        foreach ($fields as $field) {
            $settingKey = 'map_' . $field . '_' . $suffix;
            $value = \Illuminate\Database\Capsule\Manager::table('ahg_settings')
                ->where('setting_key', $settingKey)
                ->value('setting_value');
            $mappings[$field] = $value ?: $this->getDefaultMapping($field, $suffix);
        }
        
        return $mappings;
    }
    
    /**
     * Get default field mapping
     */
    protected function getDefaultMapping($field, $suffix)
    {
        $defaults = [
            'isad' => [
                'title' => 'title',
                'creator' => 'nameAccessPoints',
                'keywords' => 'subjectAccessPoints',
                'description' => 'scopeAndContent',
                'date' => 'creationEvent',
                'copyright' => 'accessConditions',
                'technical' => 'physicalCharacteristics',
                'gps' => 'placeAccessPoints'
            ],
            'museum' => [
                'title' => 'objectName',
                'creator' => 'productionPerson',
                'keywords' => 'objectCategory',
                'description' => 'briefDescription',
                'date' => 'productionDate',
                'copyright' => 'rightsNotes',
                'technical' => 'technicalDescription',
                'gps' => 'fieldCollectionPlace'
            ],
            'dam' => [
                'title' => 'title',
                'creator' => 'creator',
                'keywords' => 'keywords',
                'description' => 'caption',
                'date' => 'dateCreated',
                'copyright' => 'copyrightNotice',
                'technical' => 'technicalInfo',
                'gps' => 'gpsLocation'
            ]
        ];
        
        return $defaults[$suffix][$field] ?? 'none';
    }
    
    /**
     * Apply mapped metadata to information object
     */
    protected function applyMappedMetadata($metadata, $mappings, $summary)
    {
        $ioId = $this->resource->id;
        $culture = sfContext::getInstance()->user->getCulture() ?? 'en';
        $updates = [];
        $keyFields = $metadata['_extractor']['key_fields'] ?? [];
        
        // Map extracted metadata to AtoM fields
        foreach ($mappings as $sourceField => $targetField) {
            if ($targetField === 'none') continue;
            
            $value = $this->getExtractedValue($metadata, $keyFields, $sourceField);
            if (empty($value)) continue;
            
            // Map to database column
            $dbColumn = $this->mapToDbColumn($targetField);
            if ($dbColumn) {
                $updates[$dbColumn] = $value;
                error_log("Mapping $sourceField -> $targetField ($dbColumn): " . substr($value, 0, 50));
            }
        }
        
        // Always add technical summary to physical_characteristics if not already set
        if (empty($updates['physical_characteristics']) && !empty($summary)) {
            if (strlen($summary) > 10000) {
                $summary = substr($summary, 0, 10000) . "\n... (truncated)";
            }
            $updates['physical_characteristics'] = $summary;
        }
        
        if (empty($updates)) {
            error_log("No fields to update");
            return;
        }
        
        // Update database
        $exists = \Illuminate\Database\Capsule\Manager::table('information_object_i18n')
            ->where('id', $ioId)
            ->where('culture', $culture)
            ->exists();
            
        if ($exists) {
            \Illuminate\Database\Capsule\Manager::table('information_object_i18n')
                ->where('id', $ioId)
                ->where('culture', $culture)
                ->update($updates);
            error_log("SUCCESS: Updated " . count($updates) . " fields");
        } else {
            error_log("No i18n record for IO $ioId");
        }
    }
    
    /**
     * Get extracted value from metadata
     */
    protected function getExtractedValue($metadata, $keyFields, $sourceField)
    {
        switch ($sourceField) {
            case 'title':
                return $keyFields['title'] ?? $metadata['iptc']['ObjectName'] ?? $metadata['xmp']['title'] ?? null;
            case 'creator':
                return $keyFields['creator'] ?? $metadata['iptc']['By-line'] ?? $metadata['xmp']['creator'] ?? null;
            case 'keywords':
                $kw = $keyFields['keywords'] ?? $metadata['iptc']['Keywords'] ?? $metadata['xmp']['subject'] ?? null;
                return is_array($kw) ? implode(', ', $kw) : $kw;
            case 'description':
                return $keyFields['description'] ?? $metadata['iptc']['Caption-Abstract'] ?? $metadata['xmp']['description'] ?? null;
            case 'date':
                return $keyFields['date_created'] ?? $metadata['exif']['DateTimeOriginal'] ?? $metadata['iptc']['DateCreated'] ?? null;
            case 'copyright':
                return $keyFields['copyright'] ?? $metadata['iptc']['CopyrightNotice'] ?? $metadata['xmp']['rights'] ?? null;
            case 'technical':
                $tech = [];
                if (!empty($metadata['exif']['Make'])) $tech[] = 'Camera: ' . $metadata['exif']['Make'] . ' ' . ($metadata['exif']['Model'] ?? '');
                if (!empty($metadata['basic']['width'])) $tech[] = 'Dimensions: ' . $metadata['basic']['width'] . 'x' . $metadata['basic']['height'];
                if (!empty($metadata['basic']['mime_type'])) $tech[] = 'Format: ' . $metadata['basic']['mime_type'];
                return implode("\n", $tech);
            case 'gps':
                if (!empty($keyFields['gps_latitude']) && !empty($keyFields['gps_longitude'])) {
                    return $keyFields['gps_latitude'] . ', ' . $keyFields['gps_longitude'];
                }
                return null;
            default:
                return null;
        }
    }
    
    /**
     * Map target field to database column
     */
    protected function mapToDbColumn($targetField)
    {
        $columnMap = [
            // ISAD fields
            'title' => 'title',
            'scopeAndContent' => 'scope_and_content',
            'physicalCharacteristics' => 'physical_characteristics',
            'accessConditions' => 'access_conditions',
            'reproductionConditions' => 'reproduction_conditions',
            'archivalHistory' => 'archival_history',
            'extentAndMedium' => 'extent_and_medium',
            // Museum fields - map to closest AtoM field
            'objectName' => 'title',
            'briefDescription' => 'scope_and_content',
            'technicalDescription' => 'physical_characteristics',
            'rightsNotes' => 'access_conditions',
            // DAM fields - map to closest AtoM field
            'caption' => 'scope_and_content',
            'technicalInfo' => 'physical_characteristics',
            'copyrightNotice' => 'access_conditions',
            'creator' => null, // Handled separately as access point
            'keywords' => null, // Handled separately as access point
            'gpsLocation' => null, // Could add to scope_and_content or place access point
            'dateCreated' => null, // Handled via event
        ];
        
        return $columnMap[$targetField] ?? null;
    }


    protected function scheduleMetadataExtraction()
    {
        try {
            if (!($this->resource instanceof QubitInformationObject)) {
                return;
            }
            
            $digitalObject = $this->resource->getDigitalObject();
            if (!$digitalObject) {
                return;
            }
            
            $filePath = $digitalObject->getAbsolutePath();
            if (!$filePath || !file_exists($filePath)) {
                return;
            }
            
            // Extract metadata now (fast)
            $extractor = new ahgUniversalMetadataExtractor($filePath);
            $metadata = $extractor->extractAll();
            
            if (empty($metadata)) {
                return;
            }
            
            $summary = $extractor->formatSummary();
            $keyFields = $metadata['_extractor']['key_fields'] ?? [];
            
            // Store in session for post-transaction processing
            $data = [
                'io_id' => $this->resource->id,
                'summary' => $summary,
                'key_fields' => $keyFields,
            ];
            
            // Use a file-based queue (simple, no transaction lock)
            $queueFile = sfConfig::get('sf_cache_dir') . '/metadata_queue_' . $this->resource->id . '.json';
            file_put_contents($queueFile, json_encode($data));
            
            // Register shutdown function to process after response
            register_shutdown_function([$this, 'processMetadataQueue'], $queueFile);
            
            error_log("Metadata queued for IO " . $this->resource->id);
            
        } catch (\Throwable $e) {
            error_log("Queue error: " . $e->getMessage());
        }
    }
    
    public function processMetadataQueue($queueFile)
    {
        if (!file_exists($queueFile)) {
            return;
        }
        
        try {
            $data = json_decode(file_get_contents($queueFile), true);
            unlink($queueFile);
            
            if (empty($data)) {
                return;
            }
            
            $ioId = $data['io_id'];
            $summary = $data['summary'];
            $keyFields = $data['key_fields'] ?? [];
            
            // Now safe to use PDO - transaction is complete
            $conn = Propel::getConnection();
            
            // Update physical_characteristics
            if (!empty($summary)) {
                $stmt = $conn->prepare("UPDATE information_object_i18n SET physical_characteristics = ? WHERE id = ? AND culture = ?");
                $stmt->execute([$summary, $ioId, 'en']);
                error_log("Updated physical_characteristics for IO $ioId");
            }
            
            // Update title if empty
            if (!empty($keyFields['title'])) {
                $stmt = $conn->prepare("SELECT title FROM information_object_i18n WHERE id = ? AND culture = ?");
                $stmt->execute([$ioId, 'en']);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (empty($row['title'])) {
                    $stmt = $conn->prepare("UPDATE information_object_i18n SET title = ? WHERE id = ? AND culture = ?");
                    $stmt->execute([$keyFields['title'], $ioId, 'en']);
                }
            }
            
            // Update scope_and_content if empty
            if (!empty($keyFields['description'])) {
                $stmt = $conn->prepare("SELECT scope_and_content FROM information_object_i18n WHERE id = ? AND culture = ?");
                $stmt->execute([$ioId, 'en']);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (empty($row['scope_and_content'])) {
                    $stmt = $conn->prepare("UPDATE information_object_i18n SET scope_and_content = ? WHERE id = ? AND culture = ?");
                    $stmt->execute([$keyFields['description'], $ioId, 'en']);
                }
            }
            
            error_log("Metadata applied for IO $ioId");
            
        } catch (\Throwable $e) {
            error_log("Process queue error: " . $e->getMessage());
        }
    }

    protected function runMetadataBackground()
    {
        if (!($this->resource instanceof QubitInformationObject)) {
            return;
        }
        
        $script = sfConfig::get('sf_plugins_dir') . '/ahgThemeB5Plugin/lib/process_metadata.php';
        $ioId = $this->resource->id;
        
        // Run in background (non-blocking)
        $cmd = "php $script $ioId >> /var/log/atom_metadata.log 2>&1 &";
        exec($cmd);
        
        error_log("METADATA: Queued background processing for IO $ioId");
    }

}
