<?php

declare(strict_types=1);

namespace AtomExtensions\Extensions\MetadataExtraction\Handlers;

use AtomExtensions\Helpers\CultureHelper;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Metadata Extraction Handler
 *
 * Handles metadata extraction from uploaded digital objects.
 * Called by DigitalObjectController or DigitalObjectService during upload.
 * Pure Laravel Query Builder implementation.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class MetadataExtractionHandler
{
    // Taxonomy IDs
    protected const TAXONOMY_SUBJECT = 35;
    
    // Term IDs
    protected const TERM_CREATION = 111;

    protected string $uploadDir;

    public function __construct()
    {
        $this->uploadDir = \sfConfig::get('sf_upload_dir');
    }

    /**
     * Check if metadata extraction is enabled.
     */
    public function isEnabled(): bool
    {
        $setting = DB::table('setting')
            ->join('setting_i18n', 'setting.id', '=', 'setting_i18n.id')
            ->where('setting.name', 'metadata_extraction_enabled')
            ->where('setting.scope', 'metadata_extraction')
            ->where('setting_i18n.culture', CultureHelper::getCulture())
            ->value('setting_i18n.value');

        if ($setting === null) {
            return true; // Default to enabled
        }

        return $setting === '1';
    }

    /**
     * Extract and apply metadata from a digital object to its information object.
     */
    public function extractAndApply(int $digitalObjectId, int $informationObjectId): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            // Check if arEmbeddedMetadataParser is available
            if (!class_exists('arEmbeddedMetadataParser', true)) {
                return false;
            }

            // Get the file path
            $do = DB::table('digital_object')->where('id', $digitalObjectId)->first();

            // Check if file type is enabled for extraction
            if ($do && !$this->isFileTypeEnabled($do->mime_type ?? '')) {
                return false;
            }
            if (!$do || !$do->path || !$do->name) {
                return false;
            }

            $path = $do->path;
            if (strpos($path, '/uploads/') === 0) {
                $path = substr($path, 9);
            }

            $absPath = $this->uploadDir . '/' . ltrim($path, '/') . $do->name;

            if (!$absPath || !is_readable($absPath)) {
                return false;
            }

            // Extract metadata
            $metadata = \arEmbeddedMetadataParser::extract($absPath);

            if (!is_array($metadata) || empty($metadata)) {
                return false;
            }

            // Apply metadata to information object
            $this->applyToInformationObject($metadata, $informationObjectId);

            // Add technical metadata
            $this->appendTechnicalMetadata($metadata, $informationObjectId);

            // #113: capture the COMPLETE ExifTool tag set (all groups) verbatim,
            // alongside the curated fields above. Best-effort; never blocks upload.
            try {
                require_once __DIR__ . '/../Services/EmbeddedMetadataService.php';
                (new \AtomExtensions\Extensions\MetadataExtraction\Services\EmbeddedMetadataService())
                    ->captureAndStore($digitalObjectId, $absPath, $informationObjectId);
            } catch (\Throwable $e) {
                // Full capture is additive; failures must not affect the upload.
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Apply extracted metadata to the information object.
     */
    protected function applyToInformationObject(array $metadata, int $informationObjectId): void
    {
        // Read from ahg_settings (meta_overwrite_existing controls both title + description)
        $overwriteAll = $this->getSetting('overwrite_existing', false);
        $overwriteTitle = $overwriteAll || $this->getSetting('overwrite_title', false);
        $overwriteDescription = $overwriteAll || $this->getSetting('overwrite_description', false);
        $autoGenerateKeywords = $this->getSetting('create_access_points', $this->getSetting('auto_generate_keywords', true));
        $extractGpsCoordinates = $this->getSetting('extract_gps', $this->getSetting('extract_gps_coordinates', true));

        // Get current i18n data
        $i18n = DB::table('information_object_i18n')
            ->where('id', $informationObjectId)
            ->where('culture', CultureHelper::getCulture())
            ->first();

        $updateData = [];

        // Apply title
        if (!empty($metadata['title']) && ($overwriteTitle || empty($i18n->title ?? null))) {
            $updateData['title'] = $metadata['title'];
        }

        // Apply description
        if (!empty($metadata['description']) && ($overwriteDescription || empty($i18n->scope_and_content ?? null))) {
            $updateData['scope_and_content'] = $metadata['description'];
        }

        // Update i18n record
        if (!empty($updateData)) {
            if ($i18n) {
                DB::table('information_object_i18n')
                    ->where('id', $informationObjectId)
                    ->where('culture', CultureHelper::getCulture())
                    ->update($updateData);
            } else {
                $updateData['id'] = $informationObjectId;
                $updateData['culture'] = CultureHelper::getCulture();
                DB::table('information_object_i18n')->insert($updateData);
            }
        }

        // Apply creator/photographer
        if (!empty($metadata['creator'])) {
            $this->addCreator($metadata['creator'], $informationObjectId);
        }

        // Apply creation date
        if (!empty($metadata['date_created'])) {
            $this->addCreationDate($metadata['date_created'], $informationObjectId);
        }

        // Apply keywords
        if ($autoGenerateKeywords && !empty($metadata['keywords'])) {
            $this->addSubjectAccessPoints($metadata['keywords'], $informationObjectId);
        }

        // Apply GPS coordinates
        if ($extractGpsCoordinates && !empty($metadata['gps'])) {
            $this->setGpsCoordinates($metadata['gps'], $informationObjectId);
        }

        // Update timestamp
        DB::table('object')
            ->where('id', $informationObjectId)
            ->update(['updated_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Append technical metadata to physical characteristics field.
     */
    protected function appendTechnicalMetadata(array $metadata, int $informationObjectId): void
    {
        if (!$this->getSetting('extract_technical', $this->getSetting('add_technical_metadata', true))) {
            return;
        }

        if (!method_exists('arEmbeddedMetadataParser', 'formatSummary')) {
            return;
        }

        $summary = \arEmbeddedMetadataParser::formatSummary($metadata);

        if (empty($summary)) {
            return;
        }

        $targetField = $this->getSetting('technical_metadata_target_field', 'physical_characteristics');

        $i18n = DB::table('information_object_i18n')
            ->where('id', $informationObjectId)
            ->where('culture', CultureHelper::getCulture())
            ->first();

        $existing = $i18n->{$targetField} ?? '';

        if (!empty($existing)) {
            $existing = preg_replace('/\n*---\s*Technical Metadata\s*---.*$/s', '', $existing);
            $existing = rtrim($existing);
        }

        $newValue = $existing ? $existing . "\n\n" . $summary : $summary;

        if ($i18n) {
            DB::table('information_object_i18n')
                ->where('id', $informationObjectId)
                ->where('culture', CultureHelper::getCulture())
                ->update([$targetField => $newValue]);
        } else {
            DB::table('information_object_i18n')->insert([
                'id' => $informationObjectId,
                'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
                $targetField => $newValue,
            ]);
        }
    }

    /**
     * Add creator actor and relation.
     */
    protected function addCreator(string $creatorName, int $informationObjectId): void
    {
        $actorId = DB::table('actor_i18n')
            ->where('authorized_form_of_name', $creatorName)
            ->where('culture', CultureHelper::getCulture())
            ->value('id');

        if (!$actorId) {
            $actorId = DB::table('object')->insertGetId([
                'class_name' => 'QubitActor',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            DB::table('actor')->insert([
                'id' => $actorId,
                'parent_id' => 3,
            ]);

            DB::table('actor_i18n')->insert([
                'id' => $actorId,
                'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
                'authorized_form_of_name' => $creatorName,
            ]);
        }

        $exists = DB::table('event')
            ->where('information_object_id', $informationObjectId)
            ->where('actor_id', $actorId)
            ->where('type_id', self::TERM_CREATION)
            ->exists();

        if (!$exists) {
            $eventId = DB::table('object')->insertGetId([
                'class_name' => 'QubitEvent',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            DB::table('event')->insert([
                'id' => $eventId,
                'information_object_id' => $informationObjectId,
                'actor_id' => $actorId,
                'type_id' => self::TERM_CREATION,
            ]);
        }
    }

    /**
     * Add creation date event.
     */
    protected function addCreationDate(string $date, int $informationObjectId): void
    {
        $event = DB::table('event')
            ->where('information_object_id', $informationObjectId)
            ->where('type_id', self::TERM_CREATION)
            ->first();

        if ($event) {
            DB::table('event')
                ->where('id', $event->id)
                ->update(['date' => $date]);
        } else {
            $eventId = DB::table('object')->insertGetId([
                'class_name' => 'QubitEvent',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            DB::table('event')->insert([
                'id' => $eventId,
                'information_object_id' => $informationObjectId,
                'type_id' => self::TERM_CREATION,
                'date' => $date,
            ]);
        }
    }

    /**
     * Add subject access points from keywords.
     */
    protected function addSubjectAccessPoints(array $keywords, int $informationObjectId): void
    {
        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            if (empty($keyword)) {
                continue;
            }

            $termId = DB::table('term')
                ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
                ->where('term_i18n.name', $keyword)
                ->where('term_i18n.culture', CultureHelper::getCulture())
                ->where('term.taxonomy_id', self::TAXONOMY_SUBJECT)
                ->value('term.id');

            if (!$termId) {
                $termId = DB::table('object')->insertGetId([
                    'class_name' => 'QubitTerm',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                DB::table('term')->insert([
                    'id' => $termId,
                    'taxonomy_id' => self::TAXONOMY_SUBJECT,
                    'parent_id' => 110,
                ]);

                DB::table('term_i18n')->insert([
                    'id' => $termId,
                    'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
                    'name' => $keyword,
                ]);
            }

            $exists = DB::table('object_term_relation')
                ->where('object_id', $informationObjectId)
                ->where('term_id', $termId)
                ->exists();

            if (!$exists) {
                $relationId = DB::table('object')->insertGetId([
                    'class_name' => 'QubitObjectTermRelation',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                DB::table('object_term_relation')->insert([
                    'id' => $relationId,
                    'object_id' => $informationObjectId,
                    'term_id' => $termId,
                ]);
            }
        }
    }

    /**
     * Set GPS coordinates.
     */
    protected function setGpsCoordinates(array $gps, int $informationObjectId): void
    {
        if (empty($gps['latitude']) || empty($gps['longitude'])) {
            return;
        }

        $note = sprintf('GPS Coordinates: %s, %s', $gps['latitude'], $gps['longitude']);

        $i18n = DB::table('information_object_i18n')
            ->where('id', $informationObjectId)
            ->where('culture', CultureHelper::getCulture())
            ->first();

        $existingNote = $i18n->scope_and_content ?? '';

        if (strpos($existingNote, 'GPS Coordinates:') === false) {
            $newValue = !empty($existingNote) ? $existingNote . "\n\n" . $note : $note;

            if ($i18n) {
                DB::table('information_object_i18n')
                    ->where('id', $informationObjectId)
                    ->where('culture', CultureHelper::getCulture())
                    ->update(['scope_and_content' => $newValue]);
            } else {
                DB::table('information_object_i18n')->insert([
                    'id' => $informationObjectId,
                    'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
                    'scope_and_content' => $newValue,
                ]);
            }
        }
    }

    /**
     * Get a metadata extraction setting.
     */
    protected function getSetting(string $name, $default = null)
    {
        // Try legacy setting table first
        $value = DB::table('setting')
            ->join('setting_i18n', 'setting.id', '=', 'setting_i18n.id')
            ->where('setting.name', $name)
            ->where('setting.scope', 'metadata_extraction')
            ->where('setting_i18n.culture', CultureHelper::getCulture())
            ->value('setting_i18n.value');

        // Fall back to ahg_settings (metadata group)
        if ($value === null) {
            $value = $this->getAhgSetting($name, null);
        }

        if ($value === null) {
            return $default;
        }

        if ($value === '1' || $value === 'true') {
            return true;
        }
        if ($value === '0' || $value === 'false') {
            return false;
        }

        return $value;
    }

    /**
     * Get a setting from ahg_settings table (metadata group).
     * Maps meta_* keys to internal names and vice versa.
     */
    protected function getAhgSetting(string $name, $default = null)
    {
        static $cache = null;
        if ($cache === null) {
            $cache = [];
            try {
                $rows = DB::table('ahg_settings')
                    ->where('setting_group', 'metadata')
                    ->get();
                foreach ($rows as $row) {
                    $cache[$row->setting_key] = $row->setting_value;
                    // Also index without prefix for handler compatibility
                    if (strpos($row->setting_key, 'meta_') === 0) {
                        $cache[substr($row->setting_key, 5)] = $row->setting_value;
                    }
                }
            } catch (\Exception $e) {
                // Table may not exist
            }
        }

        // Try exact name, then with meta_ prefix
        return $cache[$name] ?? $cache['meta_' . $name] ?? $default;
    }

    /**
     * Check if extraction is enabled for a given file type.
     */
    protected function isFileTypeEnabled(string $mimeType): bool
    {
        $typeMap = [
            'image' => 'meta_images',
            'application/pdf' => 'meta_pdf',
            'application/msword' => 'meta_office',
            'application/vnd' => 'meta_office',
            'video' => 'meta_video',
            'audio' => 'meta_audio',
        ];

        foreach ($typeMap as $mimePrefix => $settingKey) {
            if (strpos($mimeType, $mimePrefix) !== false) {
                $val = $this->getAhgSetting($settingKey, '1');
                return $val === '1' || $val === 'true';
            }
        }

        return true; // Unknown types allowed by default
    }

    /**
     * Get field mapping for a sector (isad/museum/dam).
     * Returns [metadataKey => atomField] map.
     */
    protected function getFieldMappings(string $sector = 'isad'): array
    {
        $fields = ['title', 'creator', 'keywords', 'description', 'date', 'copyright', 'technical', 'gps'];
        $defaults = [
            'title' => 'title', 'creator' => 'creator', 'keywords' => 'subject',
            'description' => 'scope_and_content', 'date' => 'date_created',
            'copyright' => 'access_conditions', 'technical' => 'physical_characteristics',
            'gps' => 'gps',
        ];

        $mappings = [];
        foreach ($fields as $field) {
            $key = "map_{$field}_{$sector}";
            $val = $this->getAhgSetting($key);
            $mappings[$field] = $val ?: ($defaults[$field] ?? $field);
        }

        return $mappings;
    }
}