<?php
namespace ahgPrivacyPlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * PDF Redaction Service
 *
 * Provides on-the-fly PDF redaction for documents containing PII.
 * Uses a Python backend (PyMuPDF) for actual PDF processing.
 */
class PdfRedactionService
{
    /** @var string Path to Python redactor script */
    private $pythonScript;

    /** @var string Path to Python interpreter */
    private $pythonPath = 'python3';

    /** @var string Cache directory for redacted PDFs */
    private $cacheDir;

    /** @var int Cache TTL in seconds (default 1 hour) */
    private $cacheTtl = 3600;

    public function __construct()
    {
        $this->pythonScript = \sfConfig::get('sf_plugins_dir')
            . '/ahgPrivacyPlugin/lib/python/pdf_redactor.py';
        $this->cacheDir = \sfConfig::get('sf_cache_dir') . '/pii_redacted_pdfs';

        // Ensure cache directory exists
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Get redacted PDF for a digital object
     *
     * Combines text-based PII redactions AND visual coordinate-based redactions.
     *
     * @param int $objectId The information object ID
     * @param string $originalPath Path to the original PDF
     * @return array ['success' => bool, 'path' => string|null, 'error' => string|null]
     */
    public function getRedactedPdf(int $objectId, string $originalPath): array
    {
        // Check if original exists
        if (!file_exists($originalPath)) {
            return [
                'success' => false,
                'path' => null,
                'error' => 'Original PDF not found'
            ];
        }

        // Get redacted PII terms for this object (text-based)
        $terms = $this->getRedactedTerms($objectId);

        // Get visual redaction regions (coordinate-based)
        $visualRegions = $this->getVisualRedactionRegions($objectId);

        if (empty($terms) && empty($visualRegions)) {
            // No redactions to apply, return original
            return [
                'success' => true,
                'path' => $originalPath,
                'redacted' => false,
                'message' => 'No redactions to apply'
            ];
        }

        // Check cache first (include visual regions in cache key)
        $cacheKey = $this->getCacheKey($objectId, $originalPath, $terms, $visualRegions);
        $cachedPath = $this->cacheDir . '/' . $cacheKey . '.pdf';

        if (file_exists($cachedPath) && $this->isCacheValid($cachedPath)) {
            return [
                'success' => true,
                'path' => $cachedPath,
                'redacted' => true,
                'cached' => true,
                'terms_count' => count($terms),
                'regions_count' => count($visualRegions)
            ];
        }

        // Generate redacted PDF - apply both text and visual redactions
        $result = $this->redactPdfCombined($originalPath, $cachedPath, $terms, $visualRegions);

        if ($result['success']) {
            return [
                'success' => true,
                'path' => $cachedPath,
                'redacted' => true,
                'cached' => false,
                'terms_count' => count($terms),
                'regions_count' => count($visualRegions),
                'redactions_applied' => $result['redactions_applied'] ?? 0
            ];
        }

        return [
            'success' => false,
            'path' => null,
            'error' => $result['error'] ?? 'Redaction failed'
        ];
    }

    /**
     * Get visual redaction regions for an object
     *
     * Returns regions that have been approved or applied for redaction.
     *
     * @param int $objectId
     * @return array
     */
    public function getVisualRedactionRegions(int $objectId): array
    {
        $regions = DB::table('privacy_visual_redaction')
            ->where('object_id', $objectId)
            ->whereIn('status', ['approved', 'applied', 'pending'])
            ->get();

        $result = [];
        foreach ($regions as $region) {
            $coords = json_decode($region->coordinates, true);
            if ($coords) {
                $result[] = [
                    'id' => $region->id,
                    'page' => (int)$region->page_number,
                    'x' => (float)($coords['x'] ?? 0),
                    'y' => (float)($coords['y'] ?? 0),
                    'width' => (float)($coords['width'] ?? 0),
                    'height' => (float)($coords['height'] ?? 0),
                    'color' => $region->color ?? '#000000',
                    'normalized' => (bool)$region->normalized,
                ];
            }
        }

        return $result;
    }

    /**
     * Check if object has any redactions (text or visual)
     *
     * @param int $objectId
     * @return bool
     */
    public function hasRedactions(int $objectId): bool
    {
        // Check for text-based PII redactions
        $hasTextRedactions = DB::table('ahg_ner_entity as e')
            ->join('ahg_ner_extraction as ex', 'ex.id', '=', 'e.extraction_id')
            ->where('e.object_id', $objectId)
            ->where('e.status', 'redacted')
            ->exists();

        if ($hasTextRedactions) {
            return true;
        }

        // Check for visual redactions
        return DB::table('privacy_visual_redaction')
            ->where('object_id', $objectId)
            ->whereIn('status', ['approved', 'applied', 'pending'])
            ->exists();
    }

    /**
     * Get list of redacted PII terms for an object
     *
     * Includes both NER-extracted entities AND ISAD access points marked as redacted.
     *
     * @param int $objectId
     * @return array
     */
    public function getRedactedTerms(int $objectId): array
    {
        // Get redacted entities from ahg_ner_entity (NER + regex + ISAD access points)
        $entities = DB::table('ahg_ner_entity as e')
            ->join('ahg_ner_extraction as ex', 'ex.id', '=', 'e.extraction_id')
            ->where('e.object_id', $objectId)
            ->where('e.status', 'redacted')
            ->pluck('e.entity_value')
            ->toArray();

        // Remove duplicates and empty values
        return array_values(array_unique(array_filter($entities)));
    }

    /**
     * Get list of all redactable terms for an object (for preview)
     *
     * Returns all entities that could potentially be redacted, including
     * ISAD access points regardless of status.
     *
     * @param int $objectId
     * @return array
     */
    public function getAllPotentialTerms(int $objectId): array
    {
        // Get all entities from database
        $dbEntities = DB::table('ahg_ner_entity as e')
            ->join('ahg_ner_extraction as ex', 'ex.id', '=', 'e.extraction_id')
            ->where('e.object_id', $objectId)
            ->select(['e.entity_type', 'e.entity_value', 'e.status'])
            ->get()
            ->toArray();

        // Also get fresh ISAD access points (in case they changed since last scan)
        require_once \sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PiiDetectionService.php';
        $piiService = new PiiDetectionService();
        $accessPoints = $piiService->getIsadAccessPoints($objectId);

        $terms = [];

        // Add database entities
        foreach ($dbEntities as $entity) {
            $terms[] = [
                'value' => $entity->entity_value,
                'type' => $entity->entity_type,
                'status' => $entity->status,
                'source' => strpos($entity->entity_type, 'ISAD_') === 0 ? 'isad' : 'extracted',
            ];
        }

        // Add ISAD access points not already in database
        $existingValues = array_column($terms, 'value');
        foreach ($accessPoints['subjects'] as $subject) {
            if (!in_array($subject, $existingValues)) {
                $terms[] = ['value' => $subject, 'type' => 'ISAD_SUBJECT', 'status' => 'pending', 'source' => 'isad'];
            }
        }
        foreach ($accessPoints['places'] as $place) {
            if (!in_array($place, $existingValues)) {
                $terms[] = ['value' => $place, 'type' => 'ISAD_PLACE', 'status' => 'pending', 'source' => 'isad'];
            }
        }
        foreach ($accessPoints['names'] as $name) {
            if (!in_array($name, $existingValues)) {
                $terms[] = ['value' => $name, 'type' => 'ISAD_NAME', 'status' => 'pending', 'source' => 'isad'];
            }
        }
        foreach ($accessPoints['dates'] as $date) {
            if (!in_array($date, $existingValues)) {
                $terms[] = ['value' => $date, 'type' => 'ISAD_DATE', 'status' => 'pending', 'source' => 'isad'];
            }
        }

        return $terms;
    }

    /**
     * Call Python script to redact PDF with both text terms and visual regions
     *
     * @param string $inputPath
     * @param string $outputPath
     * @param array $terms Text terms to redact
     * @param array $regions Visual regions to redact
     * @return array
     */
    protected function redactPdfCombined(string $inputPath, string $outputPath, array $terms, array $regions): array
    {
        // If we have visual regions, do a two-pass redaction
        // First pass: text-based redaction (if any terms)
        // Second pass: region-based redaction (if any regions)

        $tempPath = null;
        $currentInput = $inputPath;

        // Pass 1: Text-based redaction
        if (!empty($terms)) {
            $tempPath = $this->cacheDir . '/temp_' . uniqid() . '.pdf';
            $result = $this->redactPdf($currentInput, $tempPath, $terms);
            if (!$result['success']) {
                return $result;
            }
            $currentInput = $tempPath;
        }

        // Pass 2: Region-based redaction
        if (!empty($regions)) {
            $result = $this->redactPdfRegions($currentInput, $outputPath, $regions);

            // Clean up temp file
            if ($tempPath && file_exists($tempPath)) {
                @unlink($tempPath);
            }

            return $result;
        }

        // Only text redaction was needed, rename temp to output
        if ($tempPath && file_exists($tempPath)) {
            rename($tempPath, $outputPath);
            return ['success' => true, 'redactions_applied' => count($terms)];
        }

        // Shouldn't get here, but fallback
        return ['success' => false, 'error' => 'No redactions to apply'];
    }

    /**
     * Call Python script to redact PDF with text terms
     *
     * @param string $inputPath
     * @param string $outputPath
     * @param array $terms
     * @return array
     */
    protected function redactPdf(string $inputPath, string $outputPath, array $terms): array
    {
        // Prepare terms as JSON
        $termsJson = json_encode($terms);

        // Build command
        $cmd = sprintf(
            '%s %s %s %s %s 2>&1',
            escapeshellarg($this->pythonPath),
            escapeshellarg($this->pythonScript),
            escapeshellarg($inputPath),
            escapeshellarg($outputPath),
            escapeshellarg($termsJson)
        );

        // Execute
        $output = shell_exec($cmd);

        // Parse result
        $result = json_decode($output, true);

        if ($result === null) {
            return [
                'success' => false,
                'error' => 'Invalid response from redactor: ' . $output
            ];
        }

        return $result;
    }

    /**
     * Call Python script to redact PDF with coordinate regions
     *
     * @param string $inputPath
     * @param string $outputPath
     * @param array $regions
     * @return array
     */
    protected function redactPdfRegions(string $inputPath, string $outputPath, array $regions): array
    {
        // Prepare regions as JSON
        $regionsJson = json_encode($regions);

        // Build command with --regions flag
        $cmd = sprintf(
            '%s %s %s %s %s --regions 2>&1',
            escapeshellarg($this->pythonPath),
            escapeshellarg($this->pythonScript),
            escapeshellarg($inputPath),
            escapeshellarg($outputPath),
            escapeshellarg($regionsJson)
        );

        // Execute
        $output = shell_exec($cmd);

        // Parse result
        $result = json_decode($output, true);

        if ($result === null) {
            // Check if output file was created
            if (file_exists($outputPath)) {
                return ['success' => true, 'redactions_applied' => count($regions)];
            }
            return [
                'success' => false,
                'error' => 'Invalid response from redactor: ' . $output
            ];
        }

        return $result;
    }

    /**
     * Generate cache key for a redacted PDF
     *
     * @param int $objectId
     * @param string $originalPath
     * @param array $terms
     * @param array $regions Visual redaction regions (optional)
     * @return string
     */
    protected function getCacheKey(int $objectId, string $originalPath, array $terms, array $regions = []): string
    {
        $data = [
            'object_id' => $objectId,
            'path' => $originalPath,
            'mtime' => filemtime($originalPath),
            'terms' => $terms,
            'regions' => $regions,
        ];
        return 'redacted_' . $objectId . '_' . md5(json_encode($data));
    }

    /**
     * Check if cached file is still valid
     *
     * @param string $cachedPath
     * @return bool
     */
    protected function isCacheValid(string $cachedPath): bool
    {
        if (!file_exists($cachedPath)) {
            return false;
        }

        $age = time() - filemtime($cachedPath);
        return $age < $this->cacheTtl;
    }

    /**
     * Clear cache for a specific object
     *
     * @param int $objectId
     */
    public function clearCache(int $objectId): void
    {
        $pattern = $this->cacheDir . '/redacted_' . $objectId . '_*.pdf';
        foreach (glob($pattern) as $file) {
            @unlink($file);
        }
    }

    /**
     * Clear all cached redacted PDFs
     */
    public function clearAllCache(): void
    {
        $pattern = $this->cacheDir . '/redacted_*.pdf';
        foreach (glob($pattern) as $file) {
            @unlink($file);
        }
    }

    /**
     * Check if user can bypass PDF redaction
     *
     * @return bool
     */
    public static function canBypassRedaction(): bool
    {
        $context = \sfContext::getInstance();
        if (!$context->getUser()->isAuthenticated()) {
            return false;
        }

        // Only administrators can download unredacted PDFs
        return $context->getUser()->hasCredential('administrator');
    }

    /**
     * Get digital object path from information object ID
     *
     * @param int $objectId
     * @return string|null
     */
    public static function getDigitalObjectPath(int $objectId): ?string
    {
        $digitalObject = DB::table('digital_object')
            ->where('object_id', $objectId)
            ->first();

        if (!$digitalObject) {
            return null;
        }

        // Build the full path (matches QubitDigitalObject::getAbsolutePath())
        // Path format: sf_web_dir + path + name
        return \sfConfig::get('sf_web_dir') . $digitalObject->path . $digitalObject->name;
    }
}
