<?php

namespace AhgRedactionPlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;

/**
 * Visual Redaction Service
 *
 * Manages coordinate-based redaction regions for PDFs and images.
 * Works with the visual redaction editor to store, retrieve, and apply redactions.
 */
class VisualRedactionService
{
    protected string $cacheDir;
    protected string $pythonPath = '/usr/bin/python3';

    public function __construct()
    {
        $this->cacheDir = \sfConfig::get('sf_cache_dir') . '/redacted';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    // =====================
    // Region CRUD Operations
    // =====================

    /**
     * Get all regions for an object
     */
    public function getRegionsForObject(int $objectId, ?int $pageNumber = null): Collection
    {
        $query = DB::table('redaction_region')
            ->where('object_id', $objectId)
            ->where('status', '!=', 'rejected');

        if ($pageNumber !== null) {
            $query->where('page_number', $pageNumber);
        }

        return $query->orderBy('page_number')->orderBy('id')->get();
    }

    /**
     * Get a single region by ID
     */
    public function getRegion(int $regionId): ?object
    {
        return DB::table('redaction_region')
            ->where('id', $regionId)
            ->first();
    }

    /**
     * Save a new region
     */
    public function saveRegion(array $data, ?int $userId = null): int
    {
        $coordinates = $data['coordinates'] ?? [];
        if (is_array($coordinates)) {
            $coordinates = json_encode($coordinates);
        }

        $record = [
            'object_id' => (int)$data['object_id'],
            'digital_object_id' => $data['digital_object_id'] ?? null,
            'page_number' => (int)($data['page_number'] ?? 1),
            'region_type' => $data['region_type'] ?? 'rectangle',
            'coordinates' => $coordinates,
            'normalized' => (int)($data['normalized'] ?? 1),
            'source' => $data['source'] ?? 'manual',
            'label' => $data['label'] ?? null,
            'color' => $data['color'] ?? '#000000',
            'status' => $data['status'] ?? 'pending',
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $id = DB::table('redaction_region')->insertGetId($record);

        // Invalidate cache for this object
        $this->invalidateCache((int)$data['object_id']);

        return $id;
    }

    /**
     * Update an existing region
     */
    public function updateRegion(int $regionId, array $data, ?int $userId = null): bool
    {
        $region = $this->getRegion($regionId);
        if (!$region) {
            return false;
        }

        $updates = [
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (isset($data['coordinates'])) {
            $coordinates = $data['coordinates'];
            if (is_array($coordinates)) {
                $coordinates = json_encode($coordinates);
            }
            $updates['coordinates'] = $coordinates;
        }

        if (isset($data['page_number'])) {
            $updates['page_number'] = (int)$data['page_number'];
        }

        if (isset($data['label'])) {
            $updates['label'] = $data['label'];
        }

        if (isset($data['color'])) {
            $updates['color'] = $data['color'];
        }

        if (isset($data['status'])) {
            $updates['status'] = $data['status'];
            if ($data['status'] === 'approved' || $data['status'] === 'rejected') {
                $updates['reviewed_by'] = $userId;
                $updates['reviewed_at'] = date('Y-m-d H:i:s');
            }
        }

        $result = DB::table('redaction_region')
            ->where('id', $regionId)
            ->update($updates);

        // Invalidate cache for this object
        $this->invalidateCache($region->object_id);

        return $result >= 0;
    }

    /**
     * Delete a region
     */
    public function deleteRegion(int $regionId): bool
    {
        $region = $this->getRegion($regionId);
        if (!$region) {
            return false;
        }

        $objectId = $region->object_id;
        $result = DB::table('redaction_region')
            ->where('id', $regionId)
            ->delete();

        // Invalidate cache for this object
        $this->invalidateCache($objectId);

        return $result > 0;
    }

    /**
     * Batch save regions for an object/page
     */
    public function batchSaveRegions(int $objectId, int $pageNumber, array $regions, ?int $userId = null): array
    {
        $savedIds = [];

        // Delete existing manual regions for this page
        DB::table('redaction_region')
            ->where('object_id', $objectId)
            ->where('page_number', $pageNumber)
            ->where('source', 'manual')
            ->delete();

        // Save new regions
        foreach ($regions as $region) {
            $region['object_id'] = $objectId;
            $region['page_number'] = $pageNumber;
            $savedIds[] = $this->saveRegion($region, $userId);
        }

        return $savedIds;
    }

    // =====================
    // NER Entity Integration
    // =====================



    // =====================
    // Document Info
    // =====================

    /**
     * Get document info (page count, dimensions, type)
     */
    public function getDocumentInfo(int $objectId): ?array
    {
        $digitalObject = DB::table('digital_object')
            ->where('object_id', $objectId)
            ->first();

        if (!$digitalObject) {
            return null;
        }

        $path = \sfConfig::get('sf_web_dir') . $digitalObject->path . $digitalObject->name;
        if (!file_exists($path)) {
            return null;
        }

        $mimeType = $digitalObject->mime_type ?? mime_content_type($path);
        $isPdf = stripos($mimeType, 'pdf') !== false;
        $isImage = stripos($mimeType, 'image') !== false;

        $info = [
            'object_id' => $objectId,
            'digital_object_id' => $digitalObject->id,
            'path' => $path,
            'name' => $digitalObject->name,
            'mime_type' => $mimeType,
            'is_pdf' => $isPdf,
            'is_image' => $isImage,
            'file_size' => filesize($path),
            'page_count' => 1,
            'width' => null,
            'height' => null,
        ];

        if ($isPdf) {
            // Get PDF page count using Python
            $info['page_count'] = $this->getPdfPageCount($path);
        } elseif ($isImage) {
            // Get image dimensions
            $imageInfo = @getimagesize($path);
            if ($imageInfo) {
                $info['width'] = $imageInfo[0];
                $info['height'] = $imageInfo[1];
            }
        }

        return $info;
    }

    /**
     * Get PDF page count
     */
    protected function getPdfPageCount(string $path): int
    {
        // Page count straight from PyMuPDF. The original named a get_pdf_info.py
        // helper here and then never used it - the script does not exist in any
        // version of this code.
        $escapedPath = escapeshellarg($path);
        $cmd = "{$this->pythonPath} -c \"import fitz; doc = fitz.open({$escapedPath}); print(len(doc))\" 2>/dev/null";

        $output = shell_exec($cmd);
        $pageCount = (int)trim($output ?? '1');

        return max(1, $pageCount);
    }

    // =====================
    // Apply Redactions
    // =====================

    /**
     * Apply all approved/pending redactions to generate output
     */
    public function applyRedactions(int $objectId, ?int $userId = null): array
    {
        $docInfo = $this->getDocumentInfo($objectId);
        if (!$docInfo) {
            return ['success' => false, 'error' => 'Document not found'];
        }

        // Get all approved/pending regions
        $regions = DB::table('redaction_region')
            ->where('object_id', $objectId)
            ->whereIn('status', ['pending', 'approved'])
            ->get();

        if ($regions->isEmpty()) {
            return ['success' => true, 'message' => 'No regions to apply', 'redacted_path' => null];
        }

        // Check cache
        $regionsHash = hash('sha256', $regions->pluck('id')->sort()->implode(','));
        $cached = DB::table('redaction_cache')
            ->where('object_id', $objectId)
            ->where('regions_hash', $regionsHash)
            ->first();

        if ($cached && file_exists($cached->redacted_path)) {
            return [
                'success' => true,
                'redacted_path' => $cached->redacted_path,
                'from_cache' => true,
                'region_count' => $cached->region_count,
            ];
        }

        // Generate redacted output
        $outputPath = $this->cacheDir . '/' . $objectId . '_' . $regionsHash . '.' .
            ($docInfo['is_pdf'] ? 'pdf' : pathinfo($docInfo['name'], PATHINFO_EXTENSION));

        $result = $docInfo['is_pdf']
            ? $this->applyPdfRedactions($docInfo['path'], $outputPath, $regions)
            : $this->applyImageRedactions($docInfo['path'], $outputPath, $regions);

        if ($result['success']) {
            // Cache the result
            DB::table('redaction_cache')->insert([
                'object_id' => $objectId,
                'digital_object_id' => $docInfo['digital_object_id'],
                'original_path' => $docInfo['path'],
                'redacted_path' => $outputPath,
                'file_type' => $docInfo['is_pdf'] ? 'pdf' : 'image',
                'regions_hash' => $regionsHash,
                'region_count' => $regions->count(),
                'file_size' => file_exists($outputPath) ? filesize($outputPath) : null,
                'generated_at' => date('Y-m-d H:i:s'),
            ]);

            // Update regions as applied
            DB::table('redaction_region')
                ->whereIn('id', $regions->pluck('id')->toArray())
                ->update([
                    'status' => 'applied',
                    'applied_at' => date('Y-m-d H:i:s'),
                ]);

            $result['redacted_path'] = $outputPath;
            $result['region_count'] = $regions->count();
        }

        return $result;
    }

    /**
     * Apply PDF redactions using Python script
     */
    protected function applyPdfRedactions(string $inputPath, string $outputPath, Collection $regions): array
    {
        $script = \sfConfig::get('sf_plugins_dir') . '/ahgRedactionPlugin/lib/python/pdf_redactor.py';

        // Group regions by page
        $regionsByPage = $regions->groupBy('page_number');

        // Prepare regions JSON
        $regionsData = [];
        foreach ($regionsByPage as $page => $pageRegions) {
            foreach ($pageRegions as $region) {
                $coords = json_decode($region->coordinates, true);
                $regionsData[] = [
                    'page' => (int)$page,
                    'x' => (float)($coords['x'] ?? 0),
                    'y' => (float)($coords['y'] ?? 0),
                    'width' => (float)($coords['width'] ?? 0),
                    'height' => (float)($coords['height'] ?? 0),
                    'color' => $region->color ?? '#000000',
                    'normalized' => (bool)$region->normalized,
                ];
            }
        }

        $regionsJson = escapeshellarg(json_encode($regionsData));
        $escapedInput = escapeshellarg($inputPath);
        $escapedOutput = escapeshellarg($outputPath);

        $cmd = "{$this->pythonPath} {$script} {$escapedInput} {$escapedOutput} {$regionsJson} --regions 2>&1";
        $output = shell_exec($cmd);

        $result = @json_decode($output, true);
        if ($result && isset($result['success'])) {
            return $result;
        }

        // Fallback: check if output file was created
        if (file_exists($outputPath)) {
            return ['success' => true, 'output' => $output];
        }

        return ['success' => false, 'error' => $output ?? 'Unknown error'];
    }

    /**
     * Apply image redactions using Python script
     */
    /**
     * Burn the regions into a copy of an image, with GD.
     *
     * This shelled out to a Python helper that needs Pillow. Pillow is not part of
     * a PHP application's dependencies and was simply absent here, so applying a
     * redaction to an image failed with ModuleNotFoundError - after the regions had
     * been saved, so the archivist had every reason to think it had worked.
     *
     * GD ships with PHP on any install that can serve AtoM's own derivatives, so
     * the image path now has no dependency beyond the language. PDFs still need
     * PyMuPDF; there is no comparable way to rewrite a page in PHP.
     */
    protected function applyImageRedactions(string $inputPath, string $outputPath, Collection $regions): array
    {
        if (!extension_loaded('gd')) {
            return ['success' => false, 'error' => 'The GD extension is required to redact images.'];
        }

        $info = @getimagesize($inputPath);
        if (!$info) {
            return ['success' => false, 'error' => 'Could not read the image: '.basename($inputPath)];
        }

        [$width, $height, $type] = $info;

        switch ($type) {
            case IMAGETYPE_JPEG: $image = @imagecreatefromjpeg($inputPath); break;
            case IMAGETYPE_PNG:  $image = @imagecreatefrompng($inputPath); break;
            case IMAGETYPE_GIF:  $image = @imagecreatefromgif($inputPath); break;
            case IMAGETYPE_WEBP: $image = @imagecreatefromwebp($inputPath); break;
            default:
                return ['success' => false, 'error' => 'Unsupported image type for redaction.'];
        }

        if (!$image) {
            return ['success' => false, 'error' => 'Could not decode the image.'];
        }

        $painted = 0;

        foreach ($regions as $region) {
            $coords = json_decode($region->coordinates, true);
            if (!is_array($coords)) {
                continue;
            }

            // Coordinates are stored normalised 0-1 so they survive any resize.
            $scaleX = !empty($region->normalized) ? $width : 1;
            $scaleY = !empty($region->normalized) ? $height : 1;

            $x1 = (int) round(((float) ($coords['x'] ?? 0)) * $scaleX);
            $y1 = (int) round(((float) ($coords['y'] ?? 0)) * $scaleY);
            $x2 = $x1 + (int) round(((float) ($coords['width'] ?? 0)) * $scaleX);
            $y2 = $y1 + (int) round(((float) ($coords['height'] ?? 0)) * $scaleY);

            // Clamp, so a region drawn slightly past the edge still paints.
            $x1 = max(0, min($width - 1, $x1));
            $y1 = max(0, min($height - 1, $y1));
            $x2 = max(0, min($width - 1, $x2));
            $y2 = max(0, min($height - 1, $y2));

            if ($x2 <= $x1 || $y2 <= $y1) {
                continue;
            }

            $hex = ltrim((string) ($region->color ?? '#000000'), '#');
            if (3 === strlen($hex)) {
                $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
            }
            $rgb = array_map('hexdec', str_split(6 === strlen($hex) ? $hex : '000000', 2));
            $colour = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);

            imagefilledrectangle($image, $x1, $y1, $x2, $y2, $colour);
            ++$painted;
        }

        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Written as the type it came in as, so the redacted copy stays a drop-in
        // replacement for the original.
        switch ($type) {
            case IMAGETYPE_PNG:  $ok = imagepng($image, $outputPath); break;
            case IMAGETYPE_GIF:  $ok = imagegif($image, $outputPath); break;
            case IMAGETYPE_WEBP: $ok = imagewebp($image, $outputPath); break;
            default:             $ok = imagejpeg($image, $outputPath, 92); break;
        }

        imagedestroy($image);

        if (!$ok || !file_exists($outputPath)) {
            return ['success' => false, 'error' => 'Could not write the redacted image.'];
        }

        return ['success' => true, 'regions_applied' => $painted, 'output' => $outputPath];
    }

    // =====================
    // Cache Management
    // =====================

    /**
     * Invalidate cache for an object
     */
    public function invalidateCache(int $objectId): void
    {
        $cached = DB::table('redaction_cache')
            ->where('object_id', $objectId)
            ->get();

        foreach ($cached as $cache) {
            if (file_exists($cache->redacted_path)) {
                @unlink($cache->redacted_path);
            }
        }

        DB::table('redaction_cache')
            ->where('object_id', $objectId)
            ->delete();
    }

    /**
     * Clear cache for an object (alias for invalidateCache)
     */
    public function clearCache(int $objectId): void
    {
        $this->invalidateCache($objectId);
    }

    /**
     * Get cached redacted file if available
     */
    public function getCachedRedaction(int $objectId): ?string
    {
        $cached = DB::table('redaction_cache')
            ->where('object_id', $objectId)
            ->orderByDesc('generated_at')
            ->first();

        if ($cached && file_exists($cached->redacted_path)) {
            return $cached->redacted_path;
        }

        return null;
    }

    /**
     * Clear all cache
     */
    public function clearAllCache(): int
    {
        $count = 0;
        $cached = DB::table('redaction_cache')->get();

        foreach ($cached as $cache) {
            if (file_exists($cache->redacted_path)) {
                @unlink($cache->redacted_path);
                $count++;
            }
        }

        DB::table('redaction_cache')->truncate();

        return $count;
    }

    // =====================
    // Statistics
    // =====================

    /**
     * Get redaction statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_regions' => DB::table('redaction_region')->count(),
            'pending' => DB::table('redaction_region')->where('status', 'pending')->count(),
            'approved' => DB::table('redaction_region')->where('status', 'approved')->count(),
            'applied' => DB::table('redaction_region')->where('status', 'applied')->count(),
            'rejected' => DB::table('redaction_region')->where('status', 'rejected')->count(),
            'by_source' => [
                'manual' => DB::table('redaction_region')->where('source', 'manual')->count(),
                'auto_pii' => DB::table('redaction_region')->where('source', 'auto_pii')->count(),
            ],
            'objects_with_redactions' => DB::table('redaction_region')
                ->distinct('object_id')
                ->count('object_id'),
            'cache_count' => DB::table('redaction_cache')->count(),
        ];
    }
}
