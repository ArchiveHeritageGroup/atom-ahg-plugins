<?php

use AtomFramework\Http\Controllers\AhgApiController;

/**
 * API v2 - Digital Object Read Action
 *
 * Returns detailed digital object data including embedded EXIF/IPTC/XMP metadata
 * extracted from the master file via ahgUniversalMetadataExtractor.
 *
 * GET /apiv2/digital-objects/{id}
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 */
class apiv2DigitalObjectsReadAction extends AhgApiController
{
    public function GET($request)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }

        $id = $request->getParameter('id');
        if (empty($id)) {
            return $this->error(400, 'Bad Request', 'id parameter required');
        }

        $objectId = filter_var($id, FILTER_VALIDATE_INT);
        if ($objectId === false) {
            return $this->error(400, 'Bad Request', 'id must be an integer');
        }

        // Fetch digital object record
        $digitalObject = $this->repository->getDigitalObjectById($objectId);
        if (!$digitalObject) {
            return $this->error(404, 'Not Found', "Digital object '{$objectId}' not found");
        }

        // Optionally attach embedded metadata for image types
        $includeEmbedded = filter_var(
            $request->getParameter('include_embedded_metadata', false),
            FILTER_VALIDATE_BOOLEAN
        );

        if ($includeEmbedded) {
            $digitalObject['embedded_metadata'] = $this->getEmbeddedMetadata($digitalObject);
        }

        return $this->success($digitalObject);
    }

    /**
     * Extract embedded EXIF/IPTC/XMP metadata from a digital object's master file.
     *
     * @param array $digitalObject Digital object row from getDigitalObjectById
     * @return array|null Extracted metadata or null if not applicable / unavailable
     */
    protected function getEmbeddedMetadata(array $digitalObject): ?array
    {
        $mimeType = $digitalObject['mime_type'] ?? '';

        // Only attempt extraction for image types
        if (empty($mimeType) || strpos($mimeType, 'image/') !== 0) {
            return null;
        }

        $masterUrl = $digitalObject['master_url'] ?? null;
        if (empty($masterUrl)) {
            return null;
        }

        // Build absolute filesystem path from master URL
        $webDir = defined('sfConfig::get(\'sf_web_dir\')')
            ? sfConfig::get('sf_web_dir')
            : (\defined('SF_WEB_DIR') ? SF_WEB_DIR : dirname(__DIR__, 5) . '/web');

        // Strip leading slash from URL path and prepend web dir
        $relativePath = ltrim(parse_url($masterUrl, PHP_URL_PATH), '/');
        $filePath = $webDir . '/' . $relativePath;

        if (!file_exists($filePath)) {
            return [
                'available' => false,
                'reason' => 'File not found on filesystem',
            ];
        }

        // Dynamically load the metadata extractor
        $extractorClass = '\ahgMetadataExtractionPlugin\lib\Services\ahgUniversalMetadataExtractor';
        $extractorPath = $webDir . '/../plugins/ahgMetadataExtractionPlugin/lib/Services/ahgUniversalMetadataExtractor.php';

        if (!class_exists($extractorClass) && file_exists($extractorPath)) {
            require_once $extractorPath;
        }

        if (!class_exists($extractorClass)) {
            return [
                'available' => false,
                'reason' => 'ahgUniversalMetadataExtractor class not found',
            ];
        }

        try {
            $extractor = new $extractorClass($filePath, $mimeType);
            $metadata = $extractor->extractAll();

            if (empty($metadata) || (empty($metadata['exif']) && empty($metadata['iptc']) && empty($metadata['xmp']))) {
                return [
                    'available' => false,
                    'reason' => 'No EXIF, IPTC, or XMP metadata found in file',
                ];
            }

            // Return structured payload
            return [
                'available' => true,
                'source' => 'ahgUniversalMetadataExtractor',
                'consolidated' => $metadata['consolidated'] ?? null,
                'exif' => $this->sanitizeExifForApi($metadata['exif'] ?? null),
                'iptc' => $metadata['iptc'] ?? null,
                'xmp' => $metadata['xmp'] ?? null,
                'gps' => $metadata['gps'] ?? null,
                'file_info' => $metadata['file'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'available' => false,
                'reason' => 'Extraction error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Strip binary/unstable EXIF fields for API responses.
     */
    protected function sanitizeExifForApi(?array $exif): ?array
    {
        if ($exif === null) {
            return null;
        }

        $allowedKeys = [
            'Make', 'Model', 'Software', 'DateTime', 'DateTimeOriginal',
            'DateTimeDigitized', 'ExposureTime', 'FNumber', 'ISOSpeedRatings',
            'FocalLength', 'FocalLengthIn35mmFilm', 'Flash', 'MeteringMode',
            'ExposureProgram', 'ExposureBiasValue', 'WhiteBalance', 'PhotographicSensitivity',
            'GPSLatitude', 'GPSLatitudeRef', 'GPSLongitude', 'GPSLongitudeRef',
            'GPSAltitude', 'GPSAltitudeRef', 'ImageWidth', 'ImageHeight',
            'Orientation', 'XResolution', 'YResolution', 'ResolutionUnit',
            'ColorSpace', 'Artist', 'Copyright', 'ImageDescription',
            'ExposureMode', 'SceneCaptureType', 'SubjectDistanceRange',
        ];

        return array_intersect_key($exif, array_flip($allowedKeys));
    }
}
