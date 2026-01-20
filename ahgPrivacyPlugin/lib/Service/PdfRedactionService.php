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

        // Get redacted PII terms for this object
        $terms = $this->getRedactedTerms($objectId);

        if (empty($terms)) {
            // No terms to redact, return original
            return [
                'success' => true,
                'path' => $originalPath,
                'redacted' => false,
                'message' => 'No PII to redact'
            ];
        }

        // Check cache first
        $cacheKey = $this->getCacheKey($objectId, $originalPath, $terms);
        $cachedPath = $this->cacheDir . '/' . $cacheKey . '.pdf';

        if (file_exists($cachedPath) && $this->isCacheValid($cachedPath)) {
            return [
                'success' => true,
                'path' => $cachedPath,
                'redacted' => true,
                'cached' => true,
                'terms_count' => count($terms)
            ];
        }

        // Generate redacted PDF
        $result = $this->redactPdf($originalPath, $cachedPath, $terms);

        if ($result['success']) {
            return [
                'success' => true,
                'path' => $cachedPath,
                'redacted' => true,
                'cached' => false,
                'terms_count' => count($terms),
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
     * Get list of redacted PII terms for an object
     *
     * @param int $objectId
     * @return array
     */
    public function getRedactedTerms(int $objectId): array
    {
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
     * Call Python script to redact PDF
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
     * Generate cache key for a redacted PDF
     *
     * @param int $objectId
     * @param string $originalPath
     * @param array $terms
     * @return string
     */
    protected function getCacheKey(int $objectId, string $originalPath, array $terms): string
    {
        $data = [
            'object_id' => $objectId,
            'path' => $originalPath,
            'mtime' => filemtime($originalPath),
            'terms' => $terms
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
