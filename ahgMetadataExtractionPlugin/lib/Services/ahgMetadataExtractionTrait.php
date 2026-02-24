<?php

/**
 * AtoM Metadata Extraction Integration
 *
 * This file provides integration between AtoM's upload actions and
 * the Universal Metadata Extractor. It extends the existing EXIF/IPTC/XMP
 * extraction to support all file types.
 *
 * USAGE: Include this trait in your upload action classes:
 * - addDigitalObjectAction.class.php
 * - editAction.class.php (digitalobject module)
 * - multiFileUploadAction.class.php
 *
 * @package    arMetadataExtractorPlugin
 * @author     Johan Pieterse <johan@theahg.co.za>
 * @version    2.0
 */

use Illuminate\Database\Capsule\Manager as DB;

trait arMetadataExtractionTrait
{
    // Term IDs
    const TERM_CREATION_ID = 111;
    const TERM_NAME_ACCESS_POINT_ID = 177;

    // Taxonomy IDs
    const TAXONOMY_SUBJECT_ID = 35;

    // Root IDs
    const ROOT_ACTOR_ID = 3;
    const ROOT_TERM_SUBJECT_ID = 110;

    /**
     * Extract metadata from any supported file type
     *
     * This replaces the previous extractExifMetadata() method and extends
     * support to PDFs, Office documents, videos, and audio files.
     *
     * @param string $filePath Path to the uploaded file
     * @return array|null Extracted metadata or null if extraction failed
     */
    protected function extractAllMetadata($filePath)
    {
        if (!file_exists($filePath)) {
            return null;
        }

        try {
            // Use the universal extractor
            $extractor = new ahgUniversalMetadataExtractor($filePath);
            $metadata = $extractor->extractAll();

            if (empty($metadata)) {
                return null;
            }

            $fileType = $extractor->getFileType();

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

            return $metadata;

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Apply extracted metadata to information object
     *
     * Maps metadata fields to AtoM's information object fields based on
     * file type and available data.
     *
     * @param object|int $informationObject Information object or ID
     * @param array $metadata Extracted metadata from extractAllMetadata()
     * @param object|int|null $digitalObject Optional digital object or ID
     * @return bool Success
     */
    protected function applyMetadataToInformationObject($informationObject, $metadata, $digitalObject = null)
    {
        if (!$informationObject || empty($metadata)) {
            return false;
        }

        // Get information object ID
        $informationObjectId = is_object($informationObject) ? $informationObject->id : (int) $informationObject;

        // Load information object data if ID provided
        if (!is_object($informationObject)) {
            $informationObject = $this->getInformationObjectData($informationObjectId);
            if (!$informationObject) {
                return false;
            }
        }

        $modified = false;
        $extractorInfo = $metadata['_extractor'] ?? [];
        $keyFields = $extractorInfo['key_fields'] ?? [];
        $fileType = $extractorInfo['file_type'] ?? 'unknown';

        // 1. TITLE (only if empty)
        $currentTitle = is_object($informationObject) && isset($informationObject->title)
            ? $informationObject->title
            : $this->getI18nField($informationObjectId, 'title');

        if (empty($currentTitle) && !empty($keyFields['title'])) {
            $this->setI18nField($informationObjectId, 'title', $keyFields['title']);
            $modified = true;
        }

        // 2. DATE CREATED
        if (!empty($keyFields['date'])) {
            $this->addCreationDateLaravel($informationObjectId, $keyFields['date']);
            $modified = true;
        }

        // 3. CREATOR (as name access point)
        if (!empty($keyFields['creator'])) {
            $this->addCreatorAccessPointLaravel($informationObjectId, $keyFields['creator']);
            $modified = true;
        }

        // 4. KEYWORDS (as subject access points)
        if (!empty($keyFields['keywords'])) {
            $this->addSubjectAccessPointsLaravel($informationObjectId, $keyFields['keywords']);
            $modified = true;
        }

        // 5. DESCRIPTION (to scope and content)
        if (!empty($keyFields['description'])) {
            $this->appendToScopeAndContentLaravel($informationObjectId, $keyFields['description']);
            $modified = true;
        }

        // 6. PHYSICAL CHARACTERISTICS (technical metadata)
        $summary = $extractorInfo['summary'] ?? null;
        if ($summary) {
            $this->addPhysicalCharacteristicsLaravel($informationObjectId, $summary, $fileType);
            $modified = true;
        }

        // 7. GPS COORDINATES (for images)
        if ($fileType === 'image' && !empty($metadata['gps'])) {
            $digitalObjectId = is_object($digitalObject) ? $digitalObject->id : $digitalObject;
            $this->addGpsDataLaravel($informationObjectId, $metadata['gps'], $digitalObjectId);
            $modified = true;
        }

        // 8. COPYRIGHT NOTICE
        if (!empty($keyFields['copyright'])) {
            $this->addCopyrightNoteLaravel($informationObjectId, $keyFields['copyright']);
            $modified = true;
        }

        // Update timestamp if modified
        if ($modified) {
            try {
                DB::table('object')
                    ->where('id', $informationObjectId)
                    ->update(['updated_at' => date('Y-m-d H:i:s')]);
            } catch (Exception $e) {
                return false;
            }
        }

        return $modified;
    }

    /**
     * Get information object data
     */
    protected function getInformationObjectData(int $id): ?object
    {
        return DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function ($join) {
                $join->on('io.id', '=', 'i18n.id')
                    ->where('i18n.culture', '=', 'en');
            })
            ->where('io.id', $id)
            ->select('io.*', 'i18n.title', 'i18n.scope_and_content', 'i18n.physical_characteristics', 'i18n.access_conditions')
            ->first();
    }

    /**
     * Get i18n field value
     */
    protected function getI18nField(int $id, string $field): ?string
    {
        return DB::table('information_object_i18n')
            ->where('id', $id)
            ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
            ->value($field);
    }

    /**
     * Set i18n field value
     */
    protected function setI18nField(int $id, string $field, string $value): void
    {
        DB::table('information_object_i18n')
            ->updateOrInsert(
                ['id' => $id, 'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture()],
                [$field => $value]
            );
    }

    /**
     * Add creation date event (Laravel version)
     */
    protected function addCreationDateLaravel(int $informationObjectId, string $dateString): void
    {
        // Check if creation date already exists
        $exists = DB::table('event')
            ->where('information_object_id', $informationObjectId)
            ->where('type_id', self::TERM_CREATION_ID)
            ->exists();

        if ($exists) {
            return;
        }

        // Normalize date format
        $normalizedDate = $this->normalizeDateString($dateString);

        if (!$normalizedDate) {
            return;
        }

        try {
            // Create object entry first
            $eventObjectId = DB::table('object')->insertGetId([
                'class_name' => 'QubitEvent',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            // Create event
            DB::table('event')->insert([
                'id' => $eventObjectId,
                'information_object_id' => $informationObjectId,
                'type_id' => self::TERM_CREATION_ID,
                'date' => $normalizedDate,
            ]);

        } catch (Exception $e) {
            // Date creation failed silently
        }
    }

    /**
     * Add creator as name access point (Laravel version)
     */
    protected function addCreatorAccessPointLaravel(int $informationObjectId, $creatorName): void
    {
        // Handle multiple creators
        $creators = is_array($creatorName) ? $creatorName : [$creatorName];

        foreach ($creators as $name) {
            $name = trim($name);
            if (empty($name)) {
                continue;
            }

            try {
                // Find actor by name
                $actor = DB::table('actor as a')
                    ->leftJoin('actor_i18n as ai', function ($join) {
                        $join->on('a.id', '=', 'ai.id')
                            ->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                    })
                    ->where('ai.authorized_form_of_name', $name)
                    ->select('a.id')
                    ->first();

                if (!$actor) {
                    // Create new actor
                    $actorObjectId = DB::table('object')->insertGetId([
                        'class_name' => 'QubitActor',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                    DB::table('actor')->insert([
                        'id' => $actorObjectId,
                        'parent_id' => self::ROOT_ACTOR_ID,
                    ]);

                    DB::table('actor_i18n')->insert([
                        'id' => $actorObjectId,
                        'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
                        'authorized_form_of_name' => $name,
                    ]);

                    // Generate slug
                    $slug = $this->generateSlugFromName($name);
                    DB::table('slug')->insert([
                        'object_id' => $actorObjectId,
                        'slug' => $slug,
                    ]);

                    $actorId = $actorObjectId;
                } else {
                    $actorId = $actor->id;
                }

                // Check if relation already exists
                $relationExists = DB::table('relation')
                    ->where('subject_id', $informationObjectId)
                    ->where('object_id', $actorId)
                    ->where('type_id', self::TERM_NAME_ACCESS_POINT_ID)
                    ->exists();

                if ($relationExists) {
                    continue;
                }

                // Create relation
                $relationObjectId = DB::table('object')->insertGetId([
                    'class_name' => 'QubitRelation',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                DB::table('relation')->insert([
                    'id' => $relationObjectId,
                    'subject_id' => $informationObjectId,
                    'object_id' => $actorId,
                    'type_id' => self::TERM_NAME_ACCESS_POINT_ID,
                ]);

            } catch (Exception $e) {
                // Creator access point failed silently
            }
        }
    }

    /**
     * Add keywords as subject access points (Laravel version)
     */
    protected function addSubjectAccessPointsLaravel(int $informationObjectId, $keywords): void
    {
        if (!is_array($keywords)) {
            $keywords = array_map('trim', explode(',', $keywords));
        }

        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            if (empty($keyword) || strlen($keyword) < 2) {
                continue;
            }

            try {
                // Find term by name and taxonomy
                $term = DB::table('term as t')
                    ->leftJoin('term_i18n as ti', function ($join) {
                        $join->on('t.id', '=', 'ti.id')
                            ->where('ti.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                    })
                    ->where('t.taxonomy_id', self::TAXONOMY_SUBJECT_ID)
                    ->where('ti.name', $keyword)
                    ->select('t.id')
                    ->first();

                if (!$term) {
                    // Create new term
                    $termObjectId = DB::table('object')->insertGetId([
                        'class_name' => 'QubitTerm',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                    DB::table('term')->insert([
                        'id' => $termObjectId,
                        'taxonomy_id' => self::TAXONOMY_SUBJECT_ID,
                        'parent_id' => self::ROOT_TERM_SUBJECT_ID,
                    ]);

                    DB::table('term_i18n')->insert([
                        'id' => $termObjectId,
                        'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
                        'name' => $keyword,
                    ]);

                    // Generate slug
                    $slug = $this->generateSlugFromName($keyword);
                    DB::table('slug')->insert([
                        'object_id' => $termObjectId,
                        'slug' => $slug,
                    ]);

                    $termId = $termObjectId;
                } else {
                    $termId = $term->id;
                }

                // Check if relation exists
                $exists = DB::table('object_term_relation')
                    ->where('object_id', $informationObjectId)
                    ->where('term_id', $termId)
                    ->exists();

                if ($exists) {
                    continue;
                }

                // Create relation
                $otrObjectId = DB::table('object')->insertGetId([
                    'class_name' => 'QubitObjectTermRelation',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                DB::table('object_term_relation')->insert([
                    'id' => $otrObjectId,
                    'object_id' => $informationObjectId,
                    'term_id' => $termId,
                ]);

            } catch (Exception $e) {
                // Subject access point failed silently
            }
        }
    }

    /**
     * Append to scope and content (Laravel version)
     */
    protected function appendToScopeAndContentLaravel(int $informationObjectId, string $description): void
    {
        $current = $this->getI18nField($informationObjectId, 'scope_and_content') ?? '';

        // Don't duplicate
        if (strpos($current, $description) !== false) {
            return;
        }

        $newContent = trim($current);
        if (!empty($newContent)) {
            $newContent .= "\n\n";
        }
        $newContent .= $description;

        $this->setI18nField($informationObjectId, 'scope_and_content', $newContent);
    }

    /**
     * Add physical characteristics (Laravel version)
     */
    protected function addPhysicalCharacteristicsLaravel(int $informationObjectId, string $summary, string $fileType): void
    {
        $current = $this->getI18nField($informationObjectId, 'physical_characteristics') ?? '';

        // Remove old metadata section if present
        $current = preg_replace('/\n*=== FILE INFO ===.*$/s', '', $current);
        $current = preg_replace('/\n*=== (EXIF|IMAGE|PDF|DOCUMENT|VIDEO|AUDIO|GPS) ===.*$/s', '', $current);

        $newContent = trim($current);
        if (!empty($newContent)) {
            $newContent .= "\n\n";
        }
        $newContent .= $summary;

        $this->setI18nField($informationObjectId, 'physical_characteristics', $newContent);
    }

    /**
     * Add GPS data (Laravel version)
     */
    protected function addGpsDataLaravel(int $informationObjectId, array $gpsData, ?int $digitalObjectId = null): void
    {
        $lat = $gpsData['latitude'] ?? null;
        $lon = $gpsData['longitude'] ?? null;

        if (!$lat || !$lon) {
            return;
        }

        // Add to scope and content if no specific place field
        $gpsText = sprintf("GPS Coordinates: %.6f, %.6f", $lat, $lon);

        if (isset($gpsData['altitude'])) {
            $gpsText .= sprintf(" (Altitude: %.1fm)", $gpsData['altitude']);
        }

        // Store in digital object properties if available
        if ($digitalObjectId) {
            // Store as properties
            $this->storeProperty($digitalObjectId, 'latitude', (string) $lat);
            $this->storeProperty($digitalObjectId, 'longitude', (string) $lon);
        }
    }

    /**
     * Store a property value
     */
    protected function storeProperty(int $objectId, string $name, string $value): void
    {
        // Check if property exists
        $existing = DB::table('property')
            ->where('object_id', $objectId)
            ->where('name', $name)
            ->first();

        if ($existing) {
            DB::table('property_i18n')
                ->updateOrInsert(
                    ['id' => $existing->id, 'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture()],
                    ['value' => $value]
                );
        } else {
            $propId = DB::table('property')->insertGetId([
                'object_id' => $objectId,
                'name' => $name,
            ]);

            DB::table('property_i18n')->insert([
                'id' => $propId,
                'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
                'value' => $value,
            ]);
        }
    }

    /**
     * Add copyright note (Laravel version)
     */
    protected function addCopyrightNoteLaravel(int $informationObjectId, string $copyright): void
    {
        $current = $this->getI18nField($informationObjectId, 'access_conditions') ?? '';

        if (strpos($current, $copyright) !== false) {
            return;
        }

        $newContent = trim($current);
        if (!empty($newContent)) {
            $newContent .= "\n\n";
        }
        $newContent .= "Copyright: " . $copyright;

        $this->setI18nField($informationObjectId, 'access_conditions', $newContent);
    }

    /**
     * Generate unique slug from name
     */
    protected function generateSlugFromName(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        // Ensure uniqueness
        $baseSlug = $slug;
        $counter = 1;

        while (DB::table('slug')->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
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
        $enabled = $this->getSettingValue('face_detect_enabled', false);

        if (!$enabled) {
            return;
        }

        // Get information object ID
        $informationObjectId = is_object($informationObject) ? $informationObject->id : (int) $informationObject;

        try {
            $backend = $this->getSettingValue('face_detect_backend', 'local');
            $faceService = new ahgFaceDetectionService($backend);

            $faces = $faceService->detectFaces($filePath);

            if (empty($faces)) {
                return;
            }

            // Match to authorities if enabled
            $autoMatch = $this->getSettingValue('face_auto_match', true);

            if ($autoMatch) {
                $faces = $faceService->matchToAuthorities($faces, $filePath);

                // Auto-link if enabled and confidence is high enough
                $autoLink = $this->getSettingValue('face_auto_link', false);

                if ($autoLink) {
                    $faceService->linkFacesToInformationObject($faces, $informationObjectId);
                }
            }

        } catch (Exception $e) {
            // Face detection failed silently
        }
    }

    /**
     * Get setting value
     */
    protected function getSettingValue(string $key, $default = null)
    {
        if (class_exists('ahgSettingsAction') && method_exists('ahgSettingsAction', 'getSetting')) {
            return ahgSettingsAction::getSetting($key, $default);
        }

        // Fallback to direct DB query
        $setting = DB::table('setting as s')
            ->leftJoin('setting_i18n as si', 's.id', '=', 'si.id')
            ->where('s.name', $key)
            ->select('si.value')
            ->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Legacy method for backwards compatibility
     * Maps to new extractAllMetadata() method
     */
    protected function extractExifMetadata($filePath)
    {
        return $this->extractAllMetadata($filePath);
    }

    /**
     * Legacy method for backwards compatibility
     * Maps to new applyMetadataToInformationObject() method
     */
    protected function applyExifToInformationObject($metadata, $informationObject, $digitalObject = null)
    {
        return $this->applyMetadataToInformationObject($informationObject, $metadata, $digitalObject);
    }
}


/**
 * Standalone helper class for use outside of action classes
 */
class ahgMetadataExtractionHelper
{
    /**
     * Extract and apply metadata to information object
     *
     * @param string $filePath
     * @param object|int $informationObject Information object or ID
     * @param object|int|null $digitalObject Digital object or ID
     * @return array|null Extracted metadata
     */
    public static function extractAndApply($filePath, $informationObject, $digitalObject = null)
    {
        $extractor = new ahgUniversalMetadataExtractor($filePath);
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

        $helper->applyMetadataToInformationObject($informationObject, $metadata, $digitalObject);

        return $metadata;
    }

    /**
     * Extract metadata only (no application)
     */
    public static function extract($filePath)
    {
        $extractor = new ahgUniversalMetadataExtractor($filePath);
        return $extractor->extractAll();
    }

    /**
     * Get formatted summary for display
     */
    public static function getSummary($filePath)
    {
        $extractor = new ahgUniversalMetadataExtractor($filePath);
        $extractor->extractAll();
        return $extractor->formatSummary();
    }
}