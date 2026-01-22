<?php

namespace ahgPrivacyPlugin\Service;

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
        $query = DB::table('privacy_visual_redaction')
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
        return DB::table('privacy_visual_redaction')
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
            'linked_entity_id' => $data['linked_entity_id'] ?? null,
            'label' => $data['label'] ?? null,
            'color' => $data['color'] ?? '#000000',
            'status' => $data['status'] ?? 'pending',
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $id = DB::table('privacy_visual_redaction')->insertGetId($record);

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

        $result = DB::table('privacy_visual_redaction')
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
        $result = DB::table('privacy_visual_redaction')
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
        DB::table('privacy_visual_redaction')
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

    /**
     * Get NER-detected entities for an object (to show as overlays)
     */
    public function getNerEntitiesForPage(int $objectId, int $pageNumber): Collection
    {
        // Get entities that have bounding box info from PDF extraction
        return DB::table('ahg_ner_entity as e')
            ->join('ahg_ner_extraction as ex', 'ex.id', '=', 'e.extraction_id')
            ->where('e.object_id', $objectId)
            ->where('ex.backend_used', 'pii_detector')
            ->whereIn('e.status', ['pending', 'flagged', 'redacted'])
            ->whereNotNull('e.bounding_box')
            ->select([
                'e.id',
                'e.entity_text',
                'e.entity_type',
                'e.confidence',
                'e.status',
                'e.bounding_box',
            ])
            ->get()
            ->map(function ($entity) use ($pageNumber) {
                // Parse bounding box JSON
                $bbox = json_decode($entity->bounding_box, true);
                if (!$bbox || ($bbox['page'] ?? 1) !== $pageNumber) {
                    return null;
                }
                return (object)[
                    'id' => $entity->id,
                    'entity_id' => $entity->id,
                    'text' => $entity->entity_text,
                    'type' => $entity->entity_type,
                    'confidence' => $entity->confidence,
                    'status' => $entity->status,
                    'x' => $bbox['x'] ?? 0,
                    'y' => $bbox['y'] ?? 0,
                    'width' => $bbox['width'] ?? 0,
                    'height' => $bbox['height'] ?? 0,
                    'page' => $bbox['page'] ?? 1,
                ];
            })
            ->filter();
    }

    /**
     * Convert NER entity to visual redaction region
     */
    public function createRegionFromNerEntity(int $entityId, ?int $userId = null): ?int
    {
        $entity = DB::table('ahg_ner_entity')->where('id', $entityId)->first();
        if (!$entity || !$entity->bounding_box) {
            return null;
        }

        $bbox = json_decode($entity->bounding_box, true);
        if (!$bbox) {
            return null;
        }

        $data = [
            'object_id' => $entity->object_id,
            'page_number' => $bbox['page'] ?? 1,
            'region_type' => 'rectangle',
            'coordinates' => [
                'x' => $bbox['x'] ?? 0,
                'y' => $bbox['y'] ?? 0,
                'width' => $bbox['width'] ?? 0,
                'height' => $bbox['height'] ?? 0,
            ],
            'normalized' => 1,
            'source' => 'auto_ner',
            'linked_entity_id' => $entityId,
            'label' => $entity->entity_type . ': ' . substr($entity->entity_text, 0, 30),
            'status' => 'approved',
        ];

        return $this->saveRegion($data, $userId);
    }

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
        $script = \sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/python/get_pdf_info.py';

        // If script doesn't exist, try pymupdf directly
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
        $regions = DB::table('privacy_visual_redaction')
            ->where('object_id', $objectId)
            ->whereIn('status', ['pending', 'approved'])
            ->get();

        if ($regions->isEmpty()) {
            return ['success' => true, 'message' => 'No regions to apply', 'redacted_path' => null];
        }

        // Check cache
        $regionsHash = hash('sha256', $regions->pluck('id')->sort()->implode(','));
        $cached = DB::table('privacy_redaction_cache')
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
            DB::table('privacy_redaction_cache')->insert([
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
            DB::table('privacy_visual_redaction')
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
        $script = \sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/python/pdf_redactor.py';

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
    protected function applyImageRedactions(string $inputPath, string $outputPath, Collection $regions): array
    {
        $script = \sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/python/image_redactor.py';

        // Prepare regions JSON
        $regionsData = [];
        foreach ($regions as $region) {
            $coords = json_decode($region->coordinates, true);
            $regionsData[] = [
                'x' => (float)($coords['x'] ?? 0),
                'y' => (float)($coords['y'] ?? 0),
                'width' => (float)($coords['width'] ?? 0),
                'height' => (float)($coords['height'] ?? 0),
                'color' => $region->color ?? '#000000',
                'normalized' => (bool)$region->normalized,
            ];
        }

        $regionsJson = escapeshellarg(json_encode($regionsData));
        $escapedInput = escapeshellarg($inputPath);
        $escapedOutput = escapeshellarg($outputPath);

        $cmd = "{$this->pythonPath} {$script} {$escapedInput} {$escapedOutput} {$regionsJson} 2>&1";
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

    // =====================
    // Cache Management
    // =====================

    /**
     * Invalidate cache for an object
     */
    public function invalidateCache(int $objectId): void
    {
        $cached = DB::table('privacy_redaction_cache')
            ->where('object_id', $objectId)
            ->get();

        foreach ($cached as $cache) {
            if (file_exists($cache->redacted_path)) {
                @unlink($cache->redacted_path);
            }
        }

        DB::table('privacy_redaction_cache')
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
        $cached = DB::table('privacy_redaction_cache')
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
        $cached = DB::table('privacy_redaction_cache')->get();

        foreach ($cached as $cache) {
            if (file_exists($cache->redacted_path)) {
                @unlink($cache->redacted_path);
                $count++;
            }
        }

        DB::table('privacy_redaction_cache')->truncate();

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
            'total_regions' => DB::table('privacy_visual_redaction')->count(),
            'pending' => DB::table('privacy_visual_redaction')->where('status', 'pending')->count(),
            'approved' => DB::table('privacy_visual_redaction')->where('status', 'approved')->count(),
            'applied' => DB::table('privacy_visual_redaction')->where('status', 'applied')->count(),
            'rejected' => DB::table('privacy_visual_redaction')->where('status', 'rejected')->count(),
            'by_source' => [
                'manual' => DB::table('privacy_visual_redaction')->where('source', 'manual')->count(),
                'auto_ner' => DB::table('privacy_visual_redaction')->where('source', 'auto_ner')->count(),
                'auto_pii' => DB::table('privacy_visual_redaction')->where('source', 'auto_pii')->count(),
            ],
            'objects_with_redactions' => DB::table('privacy_visual_redaction')
                ->distinct('object_id')
                ->count('object_id'),
            'cache_count' => DB::table('privacy_redaction_cache')->count(),
        ];
    }
}
