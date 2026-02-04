<?php

/**
 * DAM (Digital Asset Management) CSV import task.
 *
 * Imports CSV files following Dublin Core/IPTC standards for digital assets.
 */
class damCsvImportTask extends sectorImportTask
{
    protected $name = 'dam-csv-import';
    protected $briefDescription = 'Import DAM CSV data with Dublin Core/IPTC validation';

    protected function getSectorCode(): string
    {
        return 'dam';
    }

    protected function getRequiredColumns(): array
    {
        return ['identifier', 'title'];
    }

    protected function getColumnMap(): array
    {
        return [
            'legacy_id' => 'legacyId',
            'parent_id' => 'parentId',
            'asset_id' => 'identifier',
            'file_name' => 'filename',
            'name' => 'filename',
            'alternative_title' => 'alternativeTitle',
            'alt_title' => 'alternativeTitle',
            'author' => 'creator',
            'photographer' => 'creator',
            'artist' => 'creator',
            'date_created' => 'dateCreated',
            'creation_date' => 'dateCreated',
            'date' => 'dateCreated',
            'date_captured' => 'dateCaptured',
            'capture_date' => 'dateCaptured',
            'date_taken' => 'dateCaptured',
            'date_modified' => 'dateModified',
            'modified_date' => 'dateModified',
            'scope_and_content' => 'description',
            'summary' => 'description',
            'file_format' => 'format',
            'format_mime_type' => 'formatMimeType',
            'mime_type' => 'formatMimeType',
            'file_size' => 'fileSize',
            'size' => 'fileSize',
            'image_size' => 'dimensions',
            'length' => 'duration',
            'media_type' => 'type',
            'copyright' => 'rights',
            'rights_holder' => 'rightsHolder',
            'copyright_holder' => 'rightsHolder',
            'usage_terms' => 'usageTerms',
            'credit_line' => 'credit',
            'tags' => 'keywords',
            'gps_latitude' => 'gpsLatitude',
            'latitude' => 'gpsLatitude',
            'gps_longitude' => 'gpsLongitude',
            'longitude' => 'gpsLongitude',
            'gps_altitude' => 'gpsAltitude',
            'altitude' => 'gpsAltitude',
            'location_created' => 'locationCreated',
            'location' => 'locationCreated',
            'location_shown' => 'locationShown',
            'camera_model' => 'cameraModel',
            'model' => 'cameraModel',
            'camera_make' => 'cameraMake',
            'make' => 'cameraMake',
            'focal_length' => 'focalLength',
            'exposure_time' => 'exposureTime',
            'shutter_speed' => 'exposureTime',
            'f_stop' => 'aperture',
            'iso_speed' => 'iso',
            'color_space' => 'colorSpace',
            'dpi' => 'resolution',
            'subjects' => 'subjectAccessPoints',
            'places' => 'placeAccessPoints',
            'names' => 'nameAccessPoints',
            'digital_object_path' => 'digitalObjectPath',
            'file_path' => 'digitalObjectPath',
            'path' => 'digitalObjectPath',
            'digital_object_uri' => 'digitalObjectURI',
            'url' => 'digitalObjectURI',
        ];
    }

    /**
     * Override to map DAM fields to AtoM fields.
     */
    protected function setI18nFields(\QubitInformationObject $io, array $data): void
    {
        $io->title = $data['title'] ?? $data['filename'] ?? null;
        $io->identifier = $data['identifier'] ?? null;
        $io->extentAndMedium = $this->formatExtent($data);
        $io->scopeAndContent = $data['description'] ?? $data['caption'] ?? null;
        $io->accessConditions = $data['rights'] ?? $data['license'] ?? null;
    }

    /**
     * Format extent field from DAM data.
     */
    protected function formatExtent(array $data): ?string
    {
        $parts = [];

        if (!empty($data['format'])) {
            $parts[] = $data['format'];
        }

        if (!empty($data['formatMimeType'])) {
            $parts[] = '('.$data['formatMimeType'].')';
        }

        if (!empty($data['fileSize'])) {
            $parts[] = $this->formatFileSize($data['fileSize']);
        }

        if (!empty($data['dimensions'])) {
            $parts[] = $data['dimensions'];
        }

        if (!empty($data['resolution'])) {
            $parts[] = $data['resolution'];
        }

        if (!empty($data['duration'])) {
            $parts[] = 'Duration: '.$data['duration'];
        }

        return !empty($parts) ? implode('; ', $parts) : null;
    }

    /**
     * Format file size for display.
     */
    protected function formatFileSize($bytes): string
    {
        $bytes = (int) $bytes;
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2).' GB';
        }
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }

    /**
     * Override to create events with capture date.
     */
    protected function createEvents(int $objectId, array $data): void
    {
        // Create creation event
        $creator = $data['creator'] ?? null;
        $dateCreated = $data['dateCreated'] ?? $data['dateCaptured'] ?? null;

        if ($creator || $dateCreated) {
            $event = new \QubitEvent();
            $event->objectId = $objectId;
            $event->typeId = \QubitTerm::CREATION_ID;
            $event->date = $dateCreated;
            $event->culture = $this->culture;

            if ($creator) {
                $actor = $this->findOrCreateActor($creator);
                if ($actor) {
                    $event->actorId = $actor->id;
                }
            }

            $event->save();
        }

        // Create place access points from GPS coordinates
        $this->createLocationAccessPoint($objectId, $data);
    }

    /**
     * Create location access point from GPS coordinates.
     */
    protected function createLocationAccessPoint(int $objectId, array $data): void
    {
        $location = $data['locationCreated'] ?? $data['locationShown'] ?? null;
        $lat = $data['gpsLatitude'] ?? null;
        $lon = $data['gpsLongitude'] ?? null;

        if ($location) {
            // Add as place access point
            $this->createTermRelations($objectId, $location, \QubitTaxonomy::PLACE_ID);
        } elseif ($lat && $lon) {
            // Create a location note if we have GPS but no place name
            $note = new \QubitNote();
            $note->objectId = $objectId;
            $note->typeId = \QubitTerm::GENERAL_NOTE_ID;
            $note->content = sprintf('GPS Coordinates: %s, %s', $lat, $lon);
            $note->culture = $this->culture;
            $note->save();
        }
    }

    protected function saveSectorMetadata(int $objectId, array $row): void
    {
        // Save DAM-specific metadata
        try {
            $exists = Illuminate\Database\Capsule\Manager::table('dam_metadata')
                ->where('information_object_id', $objectId)
                ->exists()
            ;

            $metadata = [
                'information_object_id' => $objectId,
                'filename' => $row['filename'] ?? null,
                'format' => $row['format'] ?? null,
                'mime_type' => $row['formatMimeType'] ?? null,
                'file_size' => $row['fileSize'] ?? null,
                'dimensions' => $row['dimensions'] ?? null,
                'resolution' => $row['resolution'] ?? null,
                'color_space' => $row['colorSpace'] ?? null,
                'rights' => $row['rights'] ?? null,
                'license' => $row['license'] ?? null,
                'gps_latitude' => $row['gpsLatitude'] ?? null,
                'gps_longitude' => $row['gpsLongitude'] ?? null,
                'camera_model' => $row['cameraModel'] ?? null,
                'camera_make' => $row['cameraMake'] ?? null,
                'caption' => $row['caption'] ?? null,
            ];

            if ($exists) {
                Illuminate\Database\Capsule\Manager::table('dam_metadata')
                    ->where('information_object_id', $objectId)
                    ->update($metadata)
                ;
            } else {
                Illuminate\Database\Capsule\Manager::table('dam_metadata')
                    ->insert($metadata)
                ;
            }
        } catch (\Exception $e) {
            // Table doesn't exist, skip sector metadata
        }
    }
}
