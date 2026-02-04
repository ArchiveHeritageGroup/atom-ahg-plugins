<?php

namespace ahgDataMigrationPlugin\Exporters;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * DAM (Dublin Core/IPTC) CSV exporter for AtoM import.
 */
class DamExporter extends BaseExporter
{
    public function getSectorCode(): string
    {
        return 'dam';
    }

    /**
     * Override to add DAM-specific metadata.
     */
    protected function loadRecordFromDatabase(int $id): ?array
    {
        $record = parent::loadRecordFromDatabase($id);

        if (null === $record) {
            return null;
        }

        // Map base fields to DAM fields
        $record['dateCreated'] = $record['dateRange'] ?? null;
        $record['creator'] = $record['creators'] ?? null;
        $record['description'] = $record['scope_and_content'] ?? null;
        $record['extent'] = $record['extent_and_medium'] ?? null;
        $record['keywords'] = $record['subjectAccessPoints'] ?? null;

        // Get digital object details
        $digitalObject = $this->loadDigitalObjectDetails($id);
        if ($digitalObject) {
            $record = array_merge($record, $digitalObject);
        }

        // Load DAM-specific metadata if table exists
        $damMeta = $this->loadDamMetadata($id);
        if ($damMeta) {
            $record = array_merge($record, $damMeta);
        }

        return $record;
    }

    /**
     * Load detailed digital object information.
     */
    protected function loadDigitalObjectDetails(int $id): ?array
    {
        $do = DB::table('digital_object')
            ->where('object_id', $id)
            ->first();

        if (!$do) {
            return null;
        }

        $result = [
            'filename' => $do->name ?? null,
            'formatMimeType' => $do->mime_type ?? null,
            'fileSize' => $do->byte_size ?? null,
        ];

        // Try to extract dimensions from metadata or derive from path
        if ($do->mime_type && str_starts_with($do->mime_type, 'image/')) {
            $result['type'] = 'StillImage';
        } elseif ($do->mime_type && str_starts_with($do->mime_type, 'video/')) {
            $result['type'] = 'MovingImage';
        } elseif ($do->mime_type && str_starts_with($do->mime_type, 'audio/')) {
            $result['type'] = 'Sound';
        } elseif ($do->mime_type && str_starts_with($do->mime_type, 'application/pdf')) {
            $result['type'] = 'Text';
        }

        // Try to get format from mime type
        if ($do->mime_type) {
            $parts = explode('/', $do->mime_type);
            if (count($parts) > 1) {
                $result['format'] = strtoupper($parts[1]);
            }
        }

        return $result;
    }

    /**
     * Load DAM-specific metadata from custom table.
     */
    protected function loadDamMetadata(int $id): ?array
    {
        try {
            $meta = DB::table('dam_metadata')
                ->where('information_object_id', $id)
                ->first();

            if (!$meta) {
                return null;
            }

            return [
                'dimensions' => $meta->dimensions ?? null,
                'resolution' => $meta->resolution ?? null,
                'colorSpace' => $meta->color_space ?? null,
                'rights' => $meta->rights ?? null,
                'license' => $meta->license ?? null,
                'gpsLatitude' => $meta->gps_latitude ?? null,
                'gpsLongitude' => $meta->gps_longitude ?? null,
                'cameraModel' => $meta->camera_model ?? null,
                'cameraMake' => $meta->camera_make ?? null,
                'caption' => $meta->caption ?? null,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getColumns(): array
    {
        return [
            'legacyId',
            'parentId',
            'identifier',
            'filename',
            'title',
            'alternativeTitle',
            'creator',
            'contributor',
            'publisher',
            'dateCreated',
            'dateCaptured',
            'dateModified',
            'description',
            'format',
            'formatMimeType',
            'extent',
            'fileSize',
            'dimensions',
            'duration',
            'type',
            'source',
            'language',
            'coverage',
            'rights',
            'rightsHolder',
            'license',
            'usageTerms',
            'credit',
            'keywords',
            'category',
            'headline',
            'caption',
            'gpsLatitude',
            'gpsLongitude',
            'gpsAltitude',
            'locationCreated',
            'locationShown',
            'cameraModel',
            'cameraMake',
            'focalLength',
            'exposureTime',
            'aperture',
            'iso',
            'colorSpace',
            'resolution',
            'orientation',
            'subjectAccessPoints',
            'placeAccessPoints',
            'nameAccessPoints',
            'digitalObjectPath',
            'digitalObjectURI',
        ];
    }

    public function mapRecord(array $record): array
    {
        $mapping = [
            'legacy_id' => 'legacyId',
            'legacyId' => 'legacyId',
            'parent_id' => 'parentId',
            'parentId' => 'parentId',
            'identifier' => 'identifier',
            'asset_id' => 'identifier',
            'filename' => 'filename',
            'file_name' => 'filename',
            'name' => 'filename',
            'title' => 'title',
            'alternative_title' => 'alternativeTitle',
            'alternativeTitle' => 'alternativeTitle',
            'alt_title' => 'alternativeTitle',
            'creator' => 'creator',
            'author' => 'creator',
            'photographer' => 'creator',
            'artist' => 'creator',
            'contributor' => 'contributor',
            'publisher' => 'publisher',
            'date_created' => 'dateCreated',
            'dateCreated' => 'dateCreated',
            'creation_date' => 'dateCreated',
            'date' => 'dateCreated',
            'date_captured' => 'dateCaptured',
            'dateCaptured' => 'dateCaptured',
            'capture_date' => 'dateCaptured',
            'date_taken' => 'dateCaptured',
            'date_modified' => 'dateModified',
            'dateModified' => 'dateModified',
            'modified_date' => 'dateModified',
            'description' => 'description',
            'scope_and_content' => 'description',
            'summary' => 'description',
            'format' => 'format',
            'file_format' => 'format',
            'format_mime_type' => 'formatMimeType',
            'formatMimeType' => 'formatMimeType',
            'mime_type' => 'formatMimeType',
            'extent' => 'extent',
            'file_size' => 'fileSize',
            'fileSize' => 'fileSize',
            'size' => 'fileSize',
            'dimensions' => 'dimensions',
            'image_size' => 'dimensions',
            'duration' => 'duration',
            'length' => 'duration',
            'type' => 'type',
            'media_type' => 'type',
            'source' => 'source',
            'language' => 'language',
            'coverage' => 'coverage',
            'rights' => 'rights',
            'copyright' => 'rights',
            'rights_holder' => 'rightsHolder',
            'rightsHolder' => 'rightsHolder',
            'copyright_holder' => 'rightsHolder',
            'license' => 'license',
            'usage_terms' => 'usageTerms',
            'usageTerms' => 'usageTerms',
            'credit' => 'credit',
            'credit_line' => 'credit',
            'keywords' => 'keywords',
            'tags' => 'keywords',
            'category' => 'category',
            'headline' => 'headline',
            'caption' => 'caption',
            'gps_latitude' => 'gpsLatitude',
            'gpsLatitude' => 'gpsLatitude',
            'latitude' => 'gpsLatitude',
            'gps_longitude' => 'gpsLongitude',
            'gpsLongitude' => 'gpsLongitude',
            'longitude' => 'gpsLongitude',
            'gps_altitude' => 'gpsAltitude',
            'gpsAltitude' => 'gpsAltitude',
            'altitude' => 'gpsAltitude',
            'location_created' => 'locationCreated',
            'locationCreated' => 'locationCreated',
            'location' => 'locationCreated',
            'location_shown' => 'locationShown',
            'locationShown' => 'locationShown',
            'camera_model' => 'cameraModel',
            'cameraModel' => 'cameraModel',
            'model' => 'cameraModel',
            'camera_make' => 'cameraMake',
            'cameraMake' => 'cameraMake',
            'make' => 'cameraMake',
            'focal_length' => 'focalLength',
            'focalLength' => 'focalLength',
            'exposure_time' => 'exposureTime',
            'exposureTime' => 'exposureTime',
            'shutter_speed' => 'exposureTime',
            'aperture' => 'aperture',
            'f_stop' => 'aperture',
            'iso' => 'iso',
            'iso_speed' => 'iso',
            'color_space' => 'colorSpace',
            'colorSpace' => 'colorSpace',
            'resolution' => 'resolution',
            'dpi' => 'resolution',
            'orientation' => 'orientation',
            'subjects' => 'subjectAccessPoints',
            'subjectAccessPoints' => 'subjectAccessPoints',
            'places' => 'placeAccessPoints',
            'placeAccessPoints' => 'placeAccessPoints',
            'names' => 'nameAccessPoints',
            'nameAccessPoints' => 'nameAccessPoints',
            'digital_object_path' => 'digitalObjectPath',
            'digitalObjectPath' => 'digitalObjectPath',
            'file_path' => 'digitalObjectPath',
            'path' => 'digitalObjectPath',
            'digital_object_uri' => 'digitalObjectURI',
            'digitalObjectURI' => 'digitalObjectURI',
            'url' => 'digitalObjectURI',
        ];

        $result = [];
        foreach ($record as $key => $value) {
            $targetKey = $mapping[$key] ?? $key;
            if (in_array($targetKey, $this->getColumns())) {
                $result[$targetKey] = $value;
            }
        }

        return $result;
    }
}
