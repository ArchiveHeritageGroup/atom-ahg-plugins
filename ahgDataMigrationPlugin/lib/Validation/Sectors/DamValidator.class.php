<?php

namespace ahgDataMigrationPlugin\Validation\Sectors;

use ahgDataMigrationPlugin\Validation\AhgBaseValidator;
use ahgDataMigrationPlugin\Validation\AhgValidationReport;

/**
 * DAM (Digital Asset Management) sector validator implementing Dublin Core and IPTC validation rules.
 *
 * Validates:
 * - Dublin Core element compliance
 * - IPTC metadata standards
 * - File format validation
 * - MIME type consistency
 * - Resolution and dimension formats
 * - Rights information
 */
class DamValidator extends AhgBaseValidator
{
    /** Dublin Core type vocabulary */
    public const DC_TYPES = [
        'Collection',
        'Dataset',
        'Event',
        'Image',
        'InteractiveResource',
        'MovingImage',
        'PhysicalObject',
        'Service',
        'Software',
        'Sound',
        'StillImage',
        'Text',
    ];

    /** Common file extensions and their MIME types */
    public const FILE_TYPES = [
        // Images
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'bmp' => 'image/bmp',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        // Documents
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        // Audio
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'flac' => 'audio/flac',
        'ogg' => 'audio/ogg',
        // Video
        'mp4' => 'video/mp4',
        'avi' => 'video/x-msvideo',
        'mov' => 'video/quicktime',
        'mkv' => 'video/x-matroska',
        'webm' => 'video/webm',
        // 3D
        'obj' => 'model/obj',
        'stl' => 'model/stl',
        'gltf' => 'model/gltf+json',
        'glb' => 'model/gltf-binary',
    ];

    /** Common rights statements */
    public const RIGHTS_STATEMENTS = [
        'Public Domain',
        'Creative Commons',
        'CC BY',
        'CC BY-SA',
        'CC BY-NC',
        'CC BY-NC-SA',
        'CC BY-ND',
        'CC BY-NC-ND',
        'CC0',
        'All Rights Reserved',
        'Copyright',
        'In Copyright',
        'No Known Copyright',
        'Orphan Work',
    ];

    public function __construct(?array $options = null)
    {
        parent::__construct($options);
        $this->title = 'DAM (Dublin Core/IPTC) Validation';
        $this->sectorCode = 'dam';
    }

    /**
     * Validate a row against DC/IPTC rules.
     *
     * @param array<string, mixed> $row
     */
    public function validateRow(array $row, int $rowNumber): bool
    {
        $isValid = true;

        // Validate Dublin Core type
        if (!$this->validateDcType($row, $rowNumber)) {
            $isValid = false;
        }

        // Validate file/format information
        if (!$this->validateFileFormat($row, $rowNumber)) {
            $isValid = false;
        }

        // Validate MIME type consistency
        if (!$this->validateMimeType($row, $rowNumber)) {
            $isValid = false;
        }

        // Validate dimensions/resolution
        if (!$this->validateDimensions($row, $rowNumber)) {
            $isValid = false;
        }

        // Validate file size
        if (!$this->validateFileSize($row, $rowNumber)) {
            $isValid = false;
        }

        // Validate rights information
        if (!$this->validateRights($row, $rowNumber)) {
            $isValid = false;
        }

        // Validate GPS coordinates
        if (!$this->validateGpsCoordinates($row, $rowNumber)) {
            $isValid = false;
        }

        // Validate date
        if (!$this->validateDateCreated($row, $rowNumber)) {
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * Validate Dublin Core type.
     *
     * @param array<string, mixed> $row
     */
    protected function validateDcType(array $row, int $rowNumber): bool
    {
        $type = $row['type'] ?? $row['dc_type'] ?? $row['dcType'] ?? null;

        if (null === $type || '' === trim((string) $type)) {
            $this->addRowError(
                $rowNumber,
                'type',
                'Dublin Core type is recommended for digital assets',
                AhgValidationReport::SEVERITY_INFO,
                'dc_type_recommended'
            );

            return true;
        }

        $type = trim((string) $type);

        // Check against DC Type Vocabulary
        $found = false;
        foreach (self::DC_TYPES as $dcType) {
            if (0 === strcasecmp($type, $dcType)) {
                $found = true;

                break;
            }
        }

        if (!$found) {
            $this->addRowError(
                $rowNumber,
                'type',
                sprintf(
                    "Type '%s' is not a standard Dublin Core type. Valid types: %s",
                    $type,
                    implode(', ', self::DC_TYPES)
                ),
                AhgValidationReport::SEVERITY_WARNING,
                'dc_type_nonstandard'
            );
        }

        return true;
    }

    /**
     * Validate file format.
     *
     * @param array<string, mixed> $row
     */
    protected function validateFileFormat(array $row, int $rowNumber): bool
    {
        $format = $row['format'] ?? $row['dc_format'] ?? $row['fileFormat'] ?? null;
        $filename = $row['filename'] ?? $row['file_name'] ?? null;

        if (null === $format && null === $filename) {
            $this->addRowError(
                $rowNumber,
                'format',
                'File format or filename is recommended for digital assets',
                AhgValidationReport::SEVERITY_INFO,
                'dc_format_recommended'
            );

            return true;
        }

        // Extract extension from filename if present
        if (null !== $filename && '' !== trim((string) $filename)) {
            $filename = trim((string) $filename);
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if ('' === $ext) {
                $this->addRowError(
                    $rowNumber,
                    'filename',
                    'Filename should include a file extension',
                    AhgValidationReport::SEVERITY_WARNING,
                    'dam_filename_extension'
                );
            } elseif (!isset(self::FILE_TYPES[$ext])) {
                $this->addRowError(
                    $rowNumber,
                    'filename',
                    sprintf("File extension '%s' is not a recognized format", $ext),
                    AhgValidationReport::SEVERITY_INFO,
                    'dam_extension_unknown'
                );
            }
        }

        return true;
    }

    /**
     * Validate MIME type consistency.
     *
     * @param array<string, mixed> $row
     */
    protected function validateMimeType(array $row, int $rowNumber): bool
    {
        $mimeType = $row['formatMimeType'] ?? $row['mimeType'] ?? $row['mime_type'] ?? null;
        $filename = $row['filename'] ?? $row['file_name'] ?? null;

        if (null === $mimeType || '' === trim((string) $mimeType)) {
            return true;
        }

        $mimeType = strtolower(trim((string) $mimeType));

        // Basic MIME type format validation
        if (!preg_match('/^[a-z]+\/[a-z0-9.+-]+$/i', $mimeType)) {
            $this->addRowError(
                $rowNumber,
                'formatMimeType',
                sprintf("Invalid MIME type format: '%s'", $mimeType),
                AhgValidationReport::SEVERITY_ERROR,
                'dam_mimetype_format'
            );

            return false;
        }

        // Check consistency with filename extension
        if (null !== $filename && '' !== trim((string) $filename)) {
            $ext = strtolower(pathinfo(trim((string) $filename), PATHINFO_EXTENSION));

            if ('' !== $ext && isset(self::FILE_TYPES[$ext])) {
                $expectedMime = self::FILE_TYPES[$ext];

                if ($mimeType !== $expectedMime) {
                    $this->addRowError(
                        $rowNumber,
                        'formatMimeType',
                        sprintf(
                            "MIME type '%s' does not match file extension '%s' (expected '%s')",
                            $mimeType,
                            $ext,
                            $expectedMime
                        ),
                        AhgValidationReport::SEVERITY_WARNING,
                        'dam_mimetype_mismatch'
                    );
                }
            }
        }

        return true;
    }

    /**
     * Validate dimensions and resolution.
     *
     * @param array<string, mixed> $row
     */
    protected function validateDimensions(array $row, int $rowNumber): bool
    {
        $dimensions = $row['dimensions'] ?? $row['imageDimensions'] ?? null;
        $resolution = $row['resolution'] ?? $row['dpi'] ?? null;

        // Validate dimensions format
        if (null !== $dimensions && '' !== trim((string) $dimensions)) {
            $dimensions = trim((string) $dimensions);

            // Expected format: WxH or W x H
            if (!preg_match('/^\d+\s*[x×]\s*\d+$/i', $dimensions)) {
                $this->addRowError(
                    $rowNumber,
                    'dimensions',
                    sprintf("Dimensions '%s' should be in format 'Width x Height' (e.g., '1920x1080')", $dimensions),
                    AhgValidationReport::SEVERITY_WARNING,
                    'dam_dimensions_format'
                );
            } else {
                // Extract and validate values
                preg_match('/(\d+)\s*[x×]\s*(\d+)/i', $dimensions, $matches);
                $width = (int) $matches[1];
                $height = (int) $matches[2];

                if ($width > 50000 || $height > 50000) {
                    $this->addRowError(
                        $rowNumber,
                        'dimensions',
                        sprintf('Image dimensions (%dx%d) are unusually large - please verify', $width, $height),
                        AhgValidationReport::SEVERITY_WARNING,
                        'dam_dimensions_large'
                    );
                }
            }
        }

        // Validate resolution
        if (null !== $resolution && '' !== trim((string) $resolution)) {
            $resolution = trim((string) $resolution);

            // Extract numeric value
            if (preg_match('/(\d+)/', $resolution, $matches)) {
                $dpi = (int) $matches[1];

                if ($dpi < 72) {
                    $this->addRowError(
                        $rowNumber,
                        'resolution',
                        sprintf('Resolution (%d dpi) is below web standard (72 dpi)', $dpi),
                        AhgValidationReport::SEVERITY_INFO,
                        'dam_resolution_low'
                    );
                }

                if ($dpi > 2400) {
                    $this->addRowError(
                        $rowNumber,
                        'resolution',
                        sprintf('Resolution (%d dpi) is unusually high - please verify', $dpi),
                        AhgValidationReport::SEVERITY_INFO,
                        'dam_resolution_high'
                    );
                }
            }
        }

        return true;
    }

    /**
     * Validate file size.
     *
     * @param array<string, mixed> $row
     */
    protected function validateFileSize(array $row, int $rowNumber): bool
    {
        $fileSize = $row['fileSize'] ?? $row['file_size'] ?? null;

        if (null === $fileSize || '' === (string) $fileSize) {
            return true;
        }

        // Handle numeric or string with units
        $bytes = $this->parseSizeToBytes((string) $fileSize);

        if (null === $bytes) {
            $this->addRowError(
                $rowNumber,
                'fileSize',
                sprintf("Could not parse file size: '%s'", $fileSize),
                AhgValidationReport::SEVERITY_WARNING,
                'dam_filesize_format'
            );

            return true;
        }

        // Check for suspiciously small files
        if ($bytes < 100) {
            $this->addRowError(
                $rowNumber,
                'fileSize',
                sprintf('File size (%d bytes) is suspiciously small', $bytes),
                AhgValidationReport::SEVERITY_WARNING,
                'dam_filesize_small'
            );
        }

        // Check for very large files (> 10GB)
        if ($bytes > 10 * 1024 * 1024 * 1024) {
            $this->addRowError(
                $rowNumber,
                'fileSize',
                sprintf('File size (%.2f GB) is very large', $bytes / (1024 * 1024 * 1024)),
                AhgValidationReport::SEVERITY_INFO,
                'dam_filesize_large'
            );
        }

        return true;
    }

    /**
     * Parse size string to bytes.
     */
    protected function parseSizeToBytes(string $size): ?int
    {
        $size = trim($size);

        // If purely numeric, assume bytes
        if (is_numeric($size)) {
            return (int) $size;
        }

        // Parse with units
        if (preg_match('/^(\d+(?:\.\d+)?)\s*(B|KB|MB|GB|TB)?$/i', $size, $matches)) {
            $value = (float) $matches[1];
            $unit = strtoupper($matches[2] ?? 'B');

            return (int) match ($unit) {
                'B' => $value,
                'KB' => $value * 1024,
                'MB' => $value * 1024 * 1024,
                'GB' => $value * 1024 * 1024 * 1024,
                'TB' => $value * 1024 * 1024 * 1024 * 1024,
                default => $value
            };
        }

        return null;
    }

    /**
     * Validate rights information.
     *
     * @param array<string, mixed> $row
     */
    protected function validateRights(array $row, int $rowNumber): bool
    {
        $rights = $row['rights'] ?? $row['dc_rights'] ?? $row['dcRights'] ?? null;

        if (null === $rights || '' === trim((string) $rights)) {
            $this->addRowError(
                $rowNumber,
                'rights',
                'Rights information is important for digital assets',
                AhgValidationReport::SEVERITY_WARNING,
                'dc_rights_recommended'
            );

            return true;
        }

        $rights = trim((string) $rights);

        // Check if it matches a known rights statement
        $found = false;
        foreach (self::RIGHTS_STATEMENTS as $statement) {
            if (false !== stripos($rights, $statement)) {
                $found = true;

                break;
            }
        }

        if (!$found) {
            $this->addRowError(
                $rowNumber,
                'rights',
                'Consider using a standard rights statement (e.g., Creative Commons, Public Domain)',
                AhgValidationReport::SEVERITY_INFO,
                'dc_rights_nonstandard'
            );
        }

        return true;
    }

    /**
     * Validate GPS coordinates.
     *
     * @param array<string, mixed> $row
     */
    protected function validateGpsCoordinates(array $row, int $rowNumber): bool
    {
        $lat = $row['gpsLatitude'] ?? $row['latitude'] ?? $row['lat'] ?? null;
        $lon = $row['gpsLongitude'] ?? $row['longitude'] ?? $row['lon'] ?? $row['lng'] ?? null;

        // Skip if neither present
        if ((null === $lat || '' === (string) $lat) && (null === $lon || '' === (string) $lon)) {
            return true;
        }

        // Validate latitude
        if (null !== $lat && '' !== (string) $lat) {
            $latValue = (float) $lat;

            if ($latValue < -90 || $latValue > 90) {
                $this->addRowError(
                    $rowNumber,
                    'gpsLatitude',
                    sprintf('Latitude %f is out of valid range (-90 to 90)', $latValue),
                    AhgValidationReport::SEVERITY_ERROR,
                    'dam_latitude_range'
                );

                return false;
            }
        }

        // Validate longitude
        if (null !== $lon && '' !== (string) $lon) {
            $lonValue = (float) $lon;

            if ($lonValue < -180 || $lonValue > 180) {
                $this->addRowError(
                    $rowNumber,
                    'gpsLongitude',
                    sprintf('Longitude %f is out of valid range (-180 to 180)', $lonValue),
                    AhgValidationReport::SEVERITY_ERROR,
                    'dam_longitude_range'
                );

                return false;
            }
        }

        // Check if only one coordinate is provided
        $hasLat = null !== $lat && '' !== (string) $lat;
        $hasLon = null !== $lon && '' !== (string) $lon;

        if ($hasLat !== $hasLon) {
            $this->addRowError(
                $rowNumber,
                'gpsLatitude',
                'Both latitude and longitude should be provided together',
                AhgValidationReport::SEVERITY_WARNING,
                'dam_gps_incomplete'
            );
        }

        return true;
    }

    /**
     * Validate date created.
     *
     * @param array<string, mixed> $row
     */
    protected function validateDateCreated(array $row, int $rowNumber): bool
    {
        $dateCreated = $row['dateCreated'] ?? $row['date_created'] ?? $row['dc_date'] ?? null;

        if (null === $dateCreated || '' === trim((string) $dateCreated)) {
            $this->addRowError(
                $rowNumber,
                'dateCreated',
                'Date created is recommended for digital assets',
                AhgValidationReport::SEVERITY_INFO,
                'dc_date_recommended'
            );

            return true;
        }

        $dateCreated = trim((string) $dateCreated);

        // Try to parse as ISO date
        $parsed = \DateTime::createFromFormat('Y-m-d', $dateCreated)
            ?: \DateTime::createFromFormat('Y-m-d H:i:s', $dateCreated)
            ?: \DateTime::createFromFormat(\DateTime::ISO8601, $dateCreated);

        if (!$parsed) {
            $this->addRowError(
                $rowNumber,
                'dateCreated',
                sprintf("Date '%s' should preferably be in ISO format (YYYY-MM-DD)", $dateCreated),
                AhgValidationReport::SEVERITY_INFO,
                'dc_date_format'
            );
        } else {
            // Check for future dates
            if ($parsed > new \DateTime()) {
                $this->addRowError(
                    $rowNumber,
                    'dateCreated',
                    'Date created is in the future',
                    AhgValidationReport::SEVERITY_WARNING,
                    'dc_date_future'
                );
            }
        }

        return true;
    }

    /**
     * Validate entire file.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    public function validateFile(array $rows): AhgValidationReport
    {
        $this->report->setTotalRows(count($rows));

        foreach ($rows as $rowNumber => $row) {
            $this->validateRow($row, $rowNumber);
        }

        return $this->report->finish();
    }
}
