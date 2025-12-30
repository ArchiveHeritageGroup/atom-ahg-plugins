#!/usr/bin/env php
<?php
/**
 * Background metadata processor - called async after upload
 * Fills all metadata fields from extracted EXIF/IPTC/XMP data
 * Supports ISAD (Archives), Museum (Spectrum), and DAM templates
 */
if ($argc < 2) {
    exit(1);
}

$ioId = (int) $argv[1];
if ($ioId < 1) {
    exit(1);
}

define('SF_ROOT_DIR', dirname(dirname(dirname(__DIR__))));
require_once SF_ROOT_DIR.'/config/ProjectConfiguration.class.php';
$configuration = ProjectConfiguration::getApplicationConfiguration('qubit', 'prod', false);
sfContext::createInstance($configuration);

require_once SF_ROOT_DIR.'/plugins/ahgThemeB5Plugin/lib/ahgUniversalMetadataExtractor.php';

// Term/Taxonomy IDs
define('TERM_CREATION_ID', 111);
define('TERM_NAME_ACCESS_POINT_ID', 161);
define('TAXONOMY_SUBJECT_ID', 35);
define('TAXONOMY_PLACE_ID', 42);

try {
    $conn = Propel::getConnection();

    // Get digital object path
    $stmt = $conn->prepare("SELECT path, name FROM digital_object WHERE object_id = ?");
    $stmt->execute([$ioId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        error_log("METADATA: No digital object for IO $ioId");
        exit(0);
    }

    $filePath = SF_ROOT_DIR . $row['path'] . $row['name'];

    if (!file_exists($filePath)) {
        error_log("METADATA: File not found: $filePath");
        exit(0);
    }

    error_log("METADATA: Processing $filePath for IO $ioId");

    // Detect template type (dam, museum, isad)
    $templateType = detectTemplateType($conn, $ioId);
    error_log("METADATA: Template type: $templateType");

    // Get field mappings from settings
    $mappings = getFieldMappings($conn, $templateType);
    error_log("METADATA: Using mappings for $templateType");
    
    // Check if we should overwrite existing values
    $stmt = $conn->prepare("SELECT setting_value FROM ahg_settings WHERE setting_key = ?");
    $stmt->execute(["meta_overwrite_existing"]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $overwriteExisting = ($row && $row["setting_value"] === "true");
    
    // Check if we should replace placeholder titles
    $stmt->execute(["meta_replace_placeholders"]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $replacePlaceholders = !$row || $row["setting_value"] !== "false"; // Default true
    
    error_log("METADATA: Overwrite existing: " . ($overwriteExisting ? "yes" : "no") . ", Replace placeholders: " . ($replacePlaceholders ? "yes" : "no"));

    // Extract metadata
    $extractor = new ahgUniversalMetadataExtractor($filePath);
    $metadata = $extractor->extractAll();

    if (empty($metadata)) {
        error_log("METADATA: No metadata extracted");
        exit(0);
    }

    $summary = $extractor->formatSummary();
    $keyFields = $metadata['_extractor']['key_fields'] ?? [];

    // 1. PHYSICAL CHARACTERISTICS / TECHNICAL INFO (technical summary)
    $technicalMapping = $mappings['technical'] ?? 'physicalCharacteristics';
    if ($technicalMapping !== 'none' && !empty($summary)) {
        if (strlen($summary) > 10000) {
            $summary = substr($summary, 0, 10000) . "\n...";
        }
        $dbColumn = mapToDbColumn($technicalMapping);
        if ($dbColumn) {
            $stmt = $conn->prepare("UPDATE information_object_i18n SET $dbColumn = ? WHERE id = ? AND culture = ?");
            $stmt->execute([$summary, $ioId, 'en']);
            error_log("METADATA: Updated $dbColumn with technical summary");
        }
    }

    // 2. TITLE - check XMP, IPTC, then filename
    $titleMapping = $mappings['title'] ?? 'title';
    $titleValue = $metadata['xmp']['title'] ?? $metadata['iptc']['headline'] ?? $keyFields['title'] ?? null;
    if ($titleMapping !== 'none' && !empty($titleValue)) {
        $stmt = $conn->prepare("SELECT title FROM information_object_i18n WHERE id = ? AND culture = ?");
        $stmt->execute([$ioId, 'en']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $currentTitle = $row['title'] ?? '';
        
        // Update if empty OR if overwrite enabled OR if placeholder replacement enabled and current looks like placeholder
        $isPlaceholder = preg_match('/^(Photo|Image|File|Untitled|Document|Item|Record)\s*\d*$/i', trim($currentTitle));
        
        if (empty($currentTitle) || $overwriteExisting || ($replacePlaceholders && $isPlaceholder)) {
            $stmt = $conn->prepare("UPDATE information_object_i18n SET title = ? WHERE id = ? AND culture = ?");
            $stmt->execute([$titleValue, $ioId, 'en']);
            error_log("METADATA: Set title: " . $titleValue . ($isPlaceholder ? " (replaced placeholder: $currentTitle)" : ""));
        } else {
            error_log("METADATA: Title not updated - existing: " . $currentTitle);
        }
    }

    // 3. DESCRIPTION / CAPTION / SCOPE AND CONTENT
    $descMapping = $mappings['description'] ?? 'scopeAndContent';
    $descValue = $metadata['xmp']['description'] ?? $metadata['iptc']['caption'] ?? $keyFields['description'] ?? null;
    if ($descMapping !== 'none' && !empty($descValue)) {
        $dbColumn = mapToDbColumn($descMapping);
        if ($dbColumn) {
            $stmt = $conn->prepare("SELECT $dbColumn FROM information_object_i18n WHERE id = ? AND culture = ?");
            $stmt->execute([$ioId, 'en']);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (empty($row[$dbColumn]) || $overwriteExisting) {
                $stmt = $conn->prepare("UPDATE information_object_i18n SET $dbColumn = ? WHERE id = ? AND culture = ?");
                $stmt->execute([$descValue, $ioId, 'en']);
                error_log("METADATA: Set $dbColumn (description)" . ($overwriteExisting ? " (overwrite)" : ""));
            }
        }
    }

    // 4. COPYRIGHT / ACCESS CONDITIONS
    $copyrightMapping = $mappings['copyright'] ?? 'accessConditions';
    $copyrightValue = $metadata['xmp']['rights'] ?? $metadata['iptc']['copyright'] ?? $keyFields['copyright'] ?? null;
    if ($copyrightMapping !== 'none' && !empty($copyrightValue)) {
        $dbColumn = mapToDbColumn($copyrightMapping);
        if ($dbColumn) {
            $stmt = $conn->prepare("SELECT $dbColumn FROM information_object_i18n WHERE id = ? AND culture = ?");
            $stmt->execute([$ioId, 'en']);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $current = $row[$dbColumn] ?? '';
            if (strpos($current, $copyrightValue) === false) {
                $newVal = trim($current);
                if (!empty($newVal)) $newVal .= "\n\n";
                $newVal .= "Copyright: " . $copyrightValue;
                $stmt = $conn->prepare("UPDATE information_object_i18n SET $dbColumn = ? WHERE id = ? AND culture = ?");
                $stmt->execute([$newVal, $ioId, 'en']);
                error_log("METADATA: Added copyright to $dbColumn");
            }
        }
    }

    // 5. DATE / CREATION EVENT
    $dateMapping = $mappings['date'] ?? 'creationEvent';
    $dateValue = $metadata['exif']['DateTimeOriginal'] ?? $metadata['iptc']['date_created'] ?? $keyFields['date'] ?? null;
    if ($dateMapping !== 'none' && !empty($dateValue)) {
        $dateStr = $dateValue;
        // Parse date - handle formats like "2021:02:05 15:04:54"
        $dateOnly = preg_replace('/^(\d{4}):(\d{2}):(\d{2}).*/', '$1-$2-$3', $dateStr);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOnly)) {
            // Check if creation event exists
            $stmt = $conn->prepare("SELECT id FROM event WHERE object_id = ? AND type_id = ?");
            $stmt->execute([$ioId, TERM_CREATION_ID]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existing) {
                // Create object entry for event
                $stmt = $conn->prepare("INSERT INTO object (class_name, created_at, updated_at) VALUES (?, NOW(), NOW())");
                $stmt->execute(['QubitEvent']);
                $eventId = $conn->lastInsertId();

                // Create event
                $stmt = $conn->prepare("INSERT INTO event (id, object_id, type_id, start_date, end_date, source_culture) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$eventId, $ioId, TERM_CREATION_ID, $dateOnly, $dateOnly, 'en']);

                // Create event i18n
                $stmt = $conn->prepare("INSERT INTO event_i18n (id, culture, date) VALUES (?, ?, ?)");
                $stmt->execute([$eventId, 'en', $dateOnly]);

                error_log("METADATA: Created creation event with date $dateOnly");
            }
        }
    }

    // 6. CREATOR / NAME ACCESS POINTS
    $creatorMapping = $mappings['creator'] ?? 'nameAccessPoints';
    $creatorValue = $metadata['xmp']['creator'] ?? $metadata['iptc']['byline'] ?? $keyFields['creator'] ?? null;
    if ($creatorMapping !== 'none' && !empty($creatorValue)) {
        $creators = is_array($creatorValue) ? $creatorValue : [$creatorValue];

        foreach ($creators as $name) {
            $name = trim($name);
            if (empty($name)) continue;

            // Find existing actor
            $stmt = $conn->prepare("SELECT id FROM actor_i18n WHERE authorized_form_of_name = ? AND culture = ?");
            $stmt->execute([$name, 'en']);
            $actor = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$actor) {
                error_log("METADATA: Creator not found (skipping): $name");
                continue;
            }

            $actorId = $actor['id'];

            // Check if relation exists
            $stmt = $conn->prepare("SELECT id FROM relation WHERE subject_id = ? AND object_id = ? AND type_id = ?");
            $stmt->execute([$ioId, $actorId, TERM_NAME_ACCESS_POINT_ID]);
            if ($stmt->fetch()) {
                continue; // Already linked
            }

            // Create object entry for relation
            $stmt = $conn->prepare("INSERT INTO object (class_name, created_at, updated_at) VALUES (?, NOW(), NOW())");
            $stmt->execute(['QubitRelation']);
            $relationId = $conn->lastInsertId();

            // Create relation
            $stmt = $conn->prepare("INSERT INTO relation (id, subject_id, object_id, type_id, source_culture) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$relationId, $ioId, $actorId, TERM_NAME_ACCESS_POINT_ID, 'en']);

            error_log("METADATA: Linked creator: $name");
        }
    }

    // 7. KEYWORDS / SUBJECT ACCESS POINTS
    $keywordsMapping = $mappings['keywords'] ?? 'subjectAccessPoints';
    $keywordsValue = $metadata['xmp']['keywords'] ?? $metadata['iptc']['keywords'] ?? $keyFields['keywords'] ?? null;
    if ($keywordsMapping !== 'none' && !empty($keywordsValue)) {
        $keywords = $keywordsValue;
        if (!is_array($keywords)) {
            $keywords = array_map('trim', explode(',', $keywords));
        }

        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            if (empty($keyword) || strlen($keyword) < 2) continue;

            // Find existing subject term
            $stmt = $conn->prepare("
                SELECT t.id FROM term t
                JOIN term_i18n ti ON t.id = ti.id
                WHERE ti.name = ? AND ti.culture = ? AND t.taxonomy_id = ?
            ");
            $stmt->execute([$keyword, 'en', TAXONOMY_SUBJECT_ID]);
            $term = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$term) {
                // Skip if not found
                continue;
            }

            $termId = $term['id'];

            // Check if relation exists
            $stmt = $conn->prepare("SELECT id FROM object_term_relation WHERE object_id = ? AND term_id = ?");
            $stmt->execute([$ioId, $termId]);
            if ($stmt->fetch()) {
                continue;
            }

            // Create object entry
            $stmt = $conn->prepare("INSERT INTO object (class_name, created_at, updated_at) VALUES (?, NOW(), NOW())");
            $stmt->execute(['QubitObjectTermRelation']);
            $relationId = $conn->lastInsertId();

            // Create term relation
            $stmt = $conn->prepare("INSERT INTO object_term_relation (id, object_id, term_id) VALUES (?, ?, ?)");
            $stmt->execute([$relationId, $ioId, $termId]);

            error_log("METADATA: Linked subject: $keyword");
        }
    }

    // 8. GPS / PLACE ACCESS POINTS
    $gpsMapping = $mappings['gps'] ?? 'placeAccessPoints';
    $gpsValue = $metadata['gps'] ?? $keyFields['gps'] ?? null;
    if ($gpsMapping !== 'none' && !empty($gpsValue)) {
        $gps = $gpsValue;
        $gpsText = '';

        if (is_array($gps)) {
            $gpsText = sprintf("GPS: %.6f, %.6f", $gps['latitude'] ?? 0, $gps['longitude'] ?? 0);
            if (!empty($gps['altitude'])) {
                $gpsText .= sprintf(" (Alt: %.1fm)", $gps['altitude']);
            }
        } else {
            $gpsText = "GPS: " . $gps;
        }

        // Add to scope_and_content if not already there
        $stmt = $conn->prepare("SELECT scope_and_content FROM information_object_i18n WHERE id = ? AND culture = ?");
        $stmt->execute([$ioId, 'en']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $current = $row['scope_and_content'] ?? '';

        if (strpos($current, 'GPS:') === false) {
            $newVal = trim($current);
            if (!empty($newVal)) $newVal .= "\n\n";
            $newVal .= $gpsText;
            $stmt = $conn->prepare("UPDATE information_object_i18n SET scope_and_content = ? WHERE id = ? AND culture = ?");
            $stmt->execute([$newVal, $ioId, 'en']);
            error_log("METADATA: Added GPS to scope_and_content");
        }
    }

    error_log("METADATA: Complete for IO $ioId");

} catch (Exception $e) {
    error_log("METADATA ERROR: " . $e->getMessage());
    error_log("METADATA TRACE: " . $e->getTraceAsString());
}

/**
 * Detect template type: dam, museum, or isad
 */
function detectTemplateType($conn, $ioId) {
    // Check level of description sector
    $stmt = $conn->prepare("
        SELECT los.sector 
        FROM information_object io
        JOIN level_of_description_sector los ON io.level_of_description_id = los.term_id
        WHERE io.id = ?
    ");
    $stmt->execute([$ioId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row && !empty($row['sector'])) {
        if ($row['sector'] === 'dam') return 'dam';
        if ($row['sector'] === 'museum') return 'museum';
        if ($row['sector'] === 'archive') return 'isad';
    }
    
    // Check display standard
    $stmt = $conn->prepare("
        SELECT ti.name 
        FROM information_object io
        JOIN term_i18n ti ON io.display_standard_id = ti.id AND ti.culture = 'en'
        WHERE io.id = ?
    ");
    $stmt->execute([$ioId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row && !empty($row['name'])) {
        $name = strtolower($row['name']);
        if (strpos($name, 'dam') !== false || strpos($name, 'digital asset') !== false) {
            return 'dam';
        }
        if (strpos($name, 'museum') !== false || strpos($name, 'spectrum') !== false) {
            return 'museum';
        }
    }
    
    return 'isad';
}

/**
 * Get field mappings from ahg_settings
 */
function getFieldMappings($conn, $templateType) {
    $suffix = $templateType === 'isad' ? 'isad' : ($templateType === 'museum' ? 'museum' : 'dam');
    
    $mappings = [];
    $fields = ['title', 'creator', 'keywords', 'description', 'date', 'copyright', 'technical', 'gps'];
    
    foreach ($fields as $field) {
        $settingKey = 'map_' . $field . '_' . $suffix;
        $stmt = $conn->prepare("SELECT setting_value FROM ahg_settings WHERE setting_key = ?");
        $stmt->execute([$settingKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $mappings[$field] = $row ? $row['setting_value'] : getDefaultMapping($field, $suffix);
    }
    
    return $mappings;
}

/**
 * Get default field mapping
 */
function getDefaultMapping($field, $suffix) {
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
 * Map target field to database column
 */
function mapToDbColumn($targetField) {
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
        'physicalDescription' => 'physical_characteristics',
        // DAM fields - map to closest AtoM field
        'caption' => 'scope_and_content',
        'technicalInfo' => 'physical_characteristics',
        'copyrightNotice' => 'access_conditions',
        // Access point types (handled separately)
        'nameAccessPoints' => null,
        'subjectAccessPoints' => null,
        'placeAccessPoints' => null,
        'productionPerson' => null,
        'objectCategory' => null,
        'fieldCollectionPlace' => null,
        'creator' => null,
        'keywords' => null,
        'gpsLocation' => null,
        'creationEvent' => null,
        'productionDate' => null,
        'dateCreated' => null,
        'usageRights' => 'access_conditions',
        'creditLine' => 'scope_and_content',
        'category' => null,
        'instructions' => 'scope_and_content',
        'dateModified' => null,
        'cameraInfo' => 'physical_characteristics',
        'location' => 'scope_and_content',
    ];
    
    return $columnMap[$targetField] ?? null;
}
