<?php

/*
 * AtoM Metadata Extraction Integration
 * 
 * This file provides integration between AtoM's upload actions and
 * the Universal Metadata Extractor. Pure Laravel Query Builder implementation.
 * 
 * @package    arMetadataExtractorPlugin
 * @author     The AHG
 * @version    3.0
 */

use Illuminate\Database\Capsule\Manager as DB;

trait arMetadataExtractionTrait
{
    // Term IDs
    protected const TERM_CREATION_ID = 111;
    protected const TERM_NAME_ACCESS_POINT_ID = 177;
    
    // Taxonomy IDs
    protected const TAXONOMY_SUBJECT_ID = 35;

    /**
     * Extract metadata from any supported file type
     */
    protected function extractAllMetadata($filePath)
    {
        error_log("=== UNIVERSAL METADATA EXTRACTION ===");
        error_log("File: " . basename($filePath));
        
        if (!file_exists($filePath)) {
            error_log("ERROR: File not found: " . $filePath);
            return null;
        }
        
        try {
            // Use the universal extractor
            $extractor = new arUniversalMetadataExtractor($filePath);
            $metadata = $extractor->extractAll();
            
            if (empty($metadata)) {
                error_log("WARNING: No metadata extracted");
                return null;
            }
            
            $fileType = $extractor->getFileType();
            error_log("File type: " . $fileType);
            
            // Get key fields for AtoM integration
            $keyFields = $extractor->getKeyFields();
            
            // Get formatted summary for Physical Characteristics
            $summary = $extractor->formatSummary();
            
            // Add extraction info to metadata
            $metadata['_extractor'] = [
                'file_type' => $fileType,
                'key_fields' => $keyFields,
                'summary' => $summary,
                'errors' => $extractor->getErrors(),
            ];
            
            error_log("Extraction complete. Type: {$fileType}");
            
            // Log any errors
            foreach ($extractor->getErrors() as $error) {
                error_log("Extractor warning: " . $error);
            }
            
            return $metadata;
            
        } catch (Exception $e) {
            error_log("ERROR: Metadata extraction failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Apply extracted metadata to information object
     */
    protected function applyMetadataToInformationObject($informationObject, $metadata, $digitalObject = null)
    {
        if (!$informationObject || empty($metadata)) {
            return false;
        }
        
        error_log("=== APPLYING METADATA TO INFORMATION OBJECT ===");
        
        // Get information object ID
        $ioId = is_object($informationObject) ? $informationObject->id : (int) $informationObject;
        
        $modified = false;
        $extractorInfo = $metadata['_extractor'] ?? [];
        $keyFields = $extractorInfo['key_fields'] ?? [];
        $fileType = $extractorInfo['file_type'] ?? 'unknown';
        
        // Get current i18n data
        $ioI18n = DB::table('information_object_i18n')
            ->where('id', $ioId)
            ->where('culture', 'en')
            ->first();
        
        $updates = [];
        
        // 1. TITLE (only if empty)
        if (empty($ioI18n->title ?? null) && !empty($keyFields['title'])) {
            $updates['title'] = $keyFields['title'];
            error_log("Set title: " . $keyFields['title']);
        }
        
        // 2. DATE CREATED
        if (!empty($keyFields['date'])) {
            $this->addCreationDateLaravel($ioId, $keyFields['date']);
            $modified = true;
        }
        
        // 3. CREATOR (as name access point)
        if (!empty($keyFields['creator'])) {
            $this->addCreatorAccessPointLaravel($ioId, $keyFields['creator']);
            $modified = true;
        }
        
        // 4. KEYWORDS (as subject access points)
        if (!empty($keyFields['keywords'])) {
            $this->addSubjectAccessPointsLaravel($ioId, $keyFields['keywords']);
            $modified = true;
        }
        
        // 5. DESCRIPTION (to scope and content)
        if (!empty($keyFields['description'])) {
            $current = $ioI18n->scope_and_content ?? '';
            if (strpos($current, $keyFields['description']) === false) {
                $newContent = trim($current);
                if (!empty($newContent)) {
                    $newContent .= "\n\n";
                }
                $newContent .= $keyFields['description'];
                $updates['scope_and_content'] = $newContent;
            }
        }
        
        // 6. PHYSICAL CHARACTERISTICS (technical metadata)
        $summary = $extractorInfo['summary'] ?? null;
        if ($summary) {
            $current = $ioI18n->physical_characteristics ?? '';
            // Remove old metadata section if present
            $current = preg_replace('/\n*=== FILE INFO ===.*$/s', '', $current);
            $current = preg_replace('/\n*=== (EXIF|IMAGE|PDF|DOCUMENT|VIDEO|AUDIO|GPS) ===.*$/s', '', $current);
            
            $newContent = trim($current);
            if (!empty($newContent)) {
                $newContent .= "\n\n";
            }
            $newContent .= $summary;
            $updates['physical_characteristics'] = $newContent;
        }
        
        // 7. GPS COORDINATES (for images)
        if ($fileType === 'image' && !empty($metadata['gps'])) {
            $this->addGpsDataLaravel($ioId, $metadata['gps'], $digitalObject);
            $modified = true;
        }
        
        // 8. COPYRIGHT NOTICE
        if (!empty($keyFields['copyright'])) {
            $current = $ioI18n->access_conditions ?? '';
            if (strpos($current, $keyFields['copyright']) === false) {
                $newContent = trim($current);
                if (!empty($newContent)) {
                    $newContent .= "\n\n";
                }
                $newContent .= "Copyright: " . $keyFields['copyright'];
                $updates['access_conditions'] = $newContent;
            }
        }
        
        // Apply i18n updates
        if (!empty($updates)) {
            if ($ioI18n) {
                DB::table('information_object_i18n')
                    ->where('id', $ioId)
                    ->where('culture', 'en')
                    ->update($updates);
            } else {
                $updates['id'] = $ioId;
                $updates['culture'] = 'en';
                DB::table('information_object_i18n')->insert($updates);
            }
            $modified = true;
            error_log("Information object i18n updated");
        }
        
        // Update timestamp
        if ($modified) {
            DB::table('object')
                ->where('id', $ioId)
                ->update(['updated_at' => date('Y-m-d H:i:s')]);
            error_log("Information object saved successfully");
        }
        
        return $modified;
    }
    
    /**
     * Add creation date event (Laravel)
     */
    protected function addCreationDateLaravel(int $ioId, string $dateString): void
    {
        // Check if creation date already exists
        $existing = DB::table('event')
            ->where('object_id', $ioId)
            ->where('type_id', self::TERM_CREATION_ID)
            ->exists();
        
        if ($existing) {
            error_log("Creation date already exists, skipping");
            return;
        }
        
        // Normalize date format
        $normalizedDate = $this->normalizeDateString($dateString);
        
        if (!$normalizedDate) {
            error_log("Could not parse date: " . $dateString);
            return;
        }
        
        try {
            $eventId = DB::table('object')->insertGetId([
                'class_name' => 'QubitEvent',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            
            DB::table('event')->insert([
                'id' => $eventId,
                'object_id' => $ioId,
                'type_id' => self::TERM_CREATION_ID,
                'date' => $normalizedDate,
            ]);
            
            error_log("Added creation date: " . $normalizedDate);
        } catch (Exception $e) {
            error_log("ERROR adding creation date: " . $e->getMessage());
        }
    }
    
    /**
     * Add creator as name access point (Laravel)
     */
    protected function addCreatorAccessPointLaravel(int $ioId, $creatorName): void
    {
        $creators = is_array($creatorName) ? $creatorName : [$creatorName];
        
        foreach ($creators as $name) {
            $name = trim($name);
            if (empty($name)) continue;
            
            try {
                // Find existing actor
                $actorId = DB::table('actor_i18n')
                    ->where('authorized_form_of_name', $name)
                    ->where('culture', 'en')
                    ->value('id');
                
                if (!$actorId) {
                    // Create new actor
                    $actorId = DB::table('object')->insertGetId([
                        'class_name' => 'QubitActor',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    
                    DB::table('actor')->insert([
                        'id' => $actorId,
                        'parent_id' => 3, // Root actor
                    ]);
                    
                    DB::table('actor_i18n')->insert([
                        'id' => $actorId,
                        'culture' => 'en',
                        'authorized_form_of_name' => $name,
                    ]);
                    
                    error_log("Created new actor: " . $name);
                }
                
                // Check if relation already exists
                $exists = DB::table('relation')
                    ->where('subject_id', $ioId)
                    ->where('object_id', $actorId)
                    ->where('type_id', self::TERM_NAME_ACCESS_POINT_ID)
                    ->exists();
                
                if ($exists) {
                    error_log("Name access point already exists: " . $name);
                    continue;
                }
                
                // Create relation
                $relationId = DB::table('object')->insertGetId([
                    'class_name' => 'QubitRelation',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                
                DB::table('relation')->insert([
                    'id' => $relationId,
                    'subject_id' => $ioId,
                    'object_id' => $actorId,
                    'type_id' => self::TERM_NAME_ACCESS_POINT_ID,
                ]);
                
                error_log("Added name access point: " . $name);
                
            } catch (Exception $e) {
                error_log("ERROR adding creator: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Add keywords as subject access points (Laravel)
     */
    protected function addSubjectAccessPointsLaravel(int $ioId, $keywords): void
    {
        if (!is_array($keywords)) {
            $keywords = array_map('trim', explode(',', $keywords));
        }
        
        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            if (empty($keyword) || strlen($keyword) < 2) continue;
            
            try {
                // Find existing term
                $termId = DB::table('term')
                    ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
                    ->where('term_i18n.name', $keyword)
                    ->where('term_i18n.culture', 'en')
                    ->where('term.taxonomy_id', self::TAXONOMY_SUBJECT_ID)
                    ->value('term.id');
                
                if (!$termId) {
                    // Create new term
                    $termId = DB::table('object')->insertGetId([
                        'class_name' => 'QubitTerm',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    
                    DB::table('term')->insert([
                        'id' => $termId,
                        'taxonomy_id' => self::TAXONOMY_SUBJECT_ID,
                        'parent_id' => 110, // Root subject term
                    ]);
                    
                    DB::table('term_i18n')->insert([
                        'id' => $termId,
                        'culture' => 'en',
                        'name' => $keyword,
                    ]);
                    
                    error_log("Created new subject term: " . $keyword);
                }
                
                // Check if relation exists
                $exists = DB::table('object_term_relation')
                    ->where('object_id', $ioId)
                    ->where('term_id', $termId)
                    ->exists();
                
                if ($exists) {
                    continue;
                }
                
                // Create relation
                $relationId = DB::table('object')->insertGetId([
                    'class_name' => 'QubitObjectTermRelation',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                
                DB::table('object_term_relation')->insert([
                    'id' => $relationId,
                    'object_id' => $ioId,
                    'term_id' => $termId,
                ]);
                
                error_log("Added subject access point: " . $keyword);
                
            } catch (Exception $e) {
                error_log("ERROR adding subject: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Add GPS data (Laravel)
     */
    protected function addGpsDataLaravel(int $ioId, array $gpsData, $digitalObject = null): void
    {
        $lat = $gpsData['latitude'] ?? null;
        $lon = $gpsData['longitude'] ?? null;
        
        if (!$lat || !$lon) {
            return;
        }
        
        $gpsText = sprintf("GPS Coordinates: %.6f, %.6f", $lat, $lon);
        
        if (isset($gpsData['altitude'])) {
            $gpsText .= sprintf(" (Altitude: %.1fm)", $gpsData['altitude']);
        }
        
        // Store in digital object properties if available
        if ($digitalObject) {
            $doId = is_object($digitalObject) ? $digitalObject->id : (int) $digitalObject;
            
            $this->savePropertyLaravel($doId, 'latitude', (string) $lat);
            $this->savePropertyLaravel($doId, 'longitude', (string) $lon);
            
            error_log("Set GPS on digital object: {$lat}, {$lon}");
        }
        
        error_log("GPS data: " . $gpsText);
    }
    
    /**
     * Save property (Laravel)
     */
    protected function savePropertyLaravel(int $objectId, string $name, ?string $value): void
    {
        $existing = DB::table('property')
            ->where('object_id', $objectId)
            ->where('name', $name)
            ->first();
        
        if ($existing) {
            if ($value !== null && $value !== '') {
                DB::table('property_i18n')
                    ->updateOrInsert(
                        ['id' => $existing->id, 'culture' => 'en'],
                        ['value' => $value]
                    );
            } else {
                DB::table('property_i18n')->where('id', $existing->id)->delete();
                DB::table('property')->where('id', $existing->id)->delete();
                DB::table('object')->where('id', $existing->id)->delete();
            }
        } elseif ($value !== null && $value !== '') {
            $propId = DB::table('object')->insertGetId([
                'class_name' => 'QubitProperty',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            
            DB::table('property')->insert([
                'id' => $propId,
                'object_id' => $objectId,
                'name' => $name,
            ]);
            
            DB::table('property_i18n')->insert([
                'id' => $propId,
                'culture' => 'en',
                'value' => $value,
            ]);
        }
    }
    
    /**
     * Normalize date string to AtoM format
     */
    protected function normalizeDateString($dateString)
    {
        if (empty($dateString)) {
            return null;
        }
        
        // EXIF format: 2021:02:05 10:30:45
        if (preg_match('/^(\d{4}):(\d{2}):(\d{2})/', $dateString, $matches)) {
            return $matches[1] . '-' . $matches[2] . '-' . $matches[3];
        }
        
        // ISO format: 2021-02-05T10:30:45
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $dateString, $matches)) {
            return $matches[1] . '-' . $matches[2] . '-' . $matches[3];
        }
        
        // PDF format: D:20210205103045
        if (preg_match('/^D:(\d{4})(\d{2})(\d{2})/', $dateString, $matches)) {
            return $matches[1] . '-' . $matches[2] . '-' . $matches[3];
        }
        
        // Year only
        if (preg_match('/^(\d{4})$/', $dateString, $matches)) {
            return $matches[1];
        }
        
        // Try PHP date parsing
        $timestamp = strtotime($dateString);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }
        
        return null;
    }
    
    /**
     * Process face detection if enabled
     */
    protected function processFaceDetection($filePath, $informationObject, $digitalObject = null)
    {
        // Check if face detection is enabled
        $enabled = $this->getSettingValue('face_detect_enabled', 'ahg_settings');
        
        if (!$enabled || $enabled === '0') {
            return;
        }
        
        error_log("=== FACE DETECTION ===");
        
        try {
            $backend = $this->getSettingValue('face_detect_backend', 'ahg_settings') ?? 'local';
            
            if (!class_exists('arFaceDetectionService')) {
                error_log("Face detection service not available");
                return;
            }
            
            $faceService = new arFaceDetectionService($backend);
            
            $faces = $faceService->detectFaces($filePath);
            
            if (empty($faces)) {
                error_log("No faces detected");
                return;
            }
            
            error_log("Detected " . count($faces) . " faces");
            
            // Match to authorities if enabled
            $autoMatch = $this->getSettingValue('face_auto_match', 'ahg_settings');
            
            if ($autoMatch && $autoMatch !== '0') {
                $faces = $faceService->matchToAuthorities($faces, $filePath);
                
                // Auto-link if enabled and confidence is high enough
                $autoLink = $this->getSettingValue('face_auto_link', 'ahg_settings');
                
                if ($autoLink && $autoLink !== '0') {
                    $ioId = is_object($informationObject) ? $informationObject->id : (int) $informationObject;
                    $linked = $faceService->linkFacesToInformationObject($faces, $ioId);
                    error_log("Auto-linked {$linked} faces to authority records");
                }
            }
            
            // Log errors
            foreach ($faceService->getErrors() as $error) {
                error_log("Face detection warning: " . $error);
            }
            
        } catch (Exception $e) {
            error_log("ERROR in face detection: " . $e->getMessage());
        }
    }
    
    /**
     * Get setting value (Laravel)
     */
    protected function getSettingValue(string $name, string $scope = null): ?string
    {
        $query = DB::table('setting')
            ->join('setting_i18n', 'setting.id', '=', 'setting_i18n.id')
            ->where('setting.name', $name)
            ->where('setting_i18n.culture', 'en');
        
        if ($scope) {
            $query->where('setting.scope', $scope);
        }
        
        return $query->value('setting_i18n.value');
    }
    
    /**
     * Legacy method for backwards compatibility
     */
    protected function extractExifMetadata($filePath)
    {
        return $this->extractAllMetadata($filePath);
    }
    
    /**
     * Legacy method for backwards compatibility
     */
    protected function applyExifToInformationObject($metadata, $informationObject, $digitalObject = null)
    {
        return $this->applyMetadataToInformationObject($informationObject, $metadata, $digitalObject);
    }
    /**
     * Apply only simple i18n fields - no actors, terms, events
     * Fast and safe - no nested set issues
     */
    protected function applySimpleMetadataFields($informationObject, $metadata)
    {
        if (!$informationObject || empty($metadata)) {
            return false;
        }

        error_log("=== APPLYING SIMPLE METADATA FIELDS ===");

        $ioId = is_object($informationObject) ? $informationObject->id : (int) $informationObject;

        $extractorInfo = $metadata['_extractor'] ?? [];
        $keyFields = $extractorInfo['key_fields'] ?? [];

        // Get current i18n data
        $ioI18n = DB::table('information_object_i18n')
            ->where('id', $ioId)
            ->where('culture', 'en')
            ->first();

        $updates = [];

        // 1. TITLE (only if empty)
        if (empty($ioI18n->title ?? null) && !empty($keyFields['title'])) {
            $updates['title'] = $keyFields['title'];
            error_log("Set title: " . $keyFields['title']);
        }

        // 2. DESCRIPTION (to scope and content, only if empty)
        if (!empty($keyFields['description']) && empty($ioI18n->scope_and_content ?? null)) {
            $updates['scope_and_content'] = $keyFields['description'];
            error_log("Set scope_and_content from description");
        }

        // 3. PHYSICAL CHARACTERISTICS (technical metadata)
        $summary = $extractorInfo['summary'] ?? null;
        if ($summary) {
            $current = $ioI18n->physical_characteristics ?? '';
            // Remove old metadata section if present
            $current = preg_replace('/\n*=== FILE INFO ===.*$/s', '', $current);
            $current = preg_replace('/\n*=== (EXIF|IMAGE|PDF|DOCUMENT|VIDEO|AUDIO|GPS) ===.*$/s', '', $current);

            $newContent = trim($current);
            if (!empty($newContent)) {
                $newContent .= "\n\n";
            }
            $newContent .= $summary;
            $updates['physical_characteristics'] = $newContent;
            error_log("Updated physical_characteristics with technical metadata");
        }

        // 4. COPYRIGHT NOTICE (to access conditions)
        if (!empty($keyFields['copyright'])) {
            $current = $ioI18n->access_conditions ?? '';
            if (empty($current) || strpos($current, $keyFields['copyright']) === false) {
                $newContent = trim($current);
                if (!empty($newContent)) {
                    $newContent .= "\n\n";
                }
                $newContent .= "Copyright: " . $keyFields['copyright'];
                $updates['access_conditions'] = $newContent;
                error_log("Added copyright to access_conditions");
            }
        }

        // Apply updates
        if (!empty($updates)) {
            if ($ioI18n) {
                DB::table('information_object_i18n')
                    ->where('id', $ioId)
                    ->where('culture', 'en')
                    ->update($updates);
                error_log("Updated " . count($updates) . " fields");
            } else {
                $updates['id'] = $ioId;
                $updates['culture'] = 'en';
                DB::table('information_object_i18n')->insert($updates);
                error_log("Created i18n record with " . count($updates) . " fields");
            }
            return true;
        }

        error_log("No fields to update");
        return false;
    }

}


/**
 * Standalone helper class for use outside of action classes
 */
class arMetadataExtractionHelper
{
    /**
     * Extract and apply metadata to information object
     */
    public static function extractAndApply($filePath, $informationObjectId, $digitalObjectId = null)
    {
        if (!class_exists('arUniversalMetadataExtractor')) {
            error_log("arUniversalMetadataExtractor not available");
            return null;
        }
        
        $extractor = new arUniversalMetadataExtractor($filePath);
        $metadata = $extractor->extractAll();
        
        if (empty($metadata)) {
            return null;
        }
        
        // Add extractor info
        $metadata['_extractor'] = [
            'file_type' => $extractor->getFileType(),
            'key_fields' => $extractor->getKeyFields(),
            'summary' => $extractor->formatSummary(),
            'errors' => $extractor->getErrors(),
        ];
        
        // Create helper instance with trait methods
        $helper = new class {
            use arMetadataExtractionTrait;
        };
        
        $helper->applyMetadataToInformationObject($informationObjectId, $metadata, $digitalObjectId);
        
        return $metadata;
    }
    
    /**
     * Extract metadata only (no application)
     */
    public static function extract($filePath)
    {
        if (!class_exists('arUniversalMetadataExtractor')) {
            return null;
        }
        
        $extractor = new arUniversalMetadataExtractor($filePath);
        return $extractor->extractAll();
    }
    
    /**
     * Get formatted summary for display
     */
    public static function getSummary($filePath)
    {
        if (!class_exists('arUniversalMetadataExtractor')) {
            return null;
        }
        
        $extractor = new arUniversalMetadataExtractor($filePath);
        $extractor->extractAll();
        return $extractor->formatSummary();
    }
}