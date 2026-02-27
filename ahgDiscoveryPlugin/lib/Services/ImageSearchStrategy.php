<?php

namespace AhgDiscovery\Services;

/**
 * Step 2E: Image Similarity Search via Qdrant (CLIP)
 *
 * Embeds an uploaded/selected image using CLIP (ViT-B/32, 512 dims)
 * then searches the Qdrant image collection for visually similar records.
 *
 * One Python call at query time for embedding (~500ms),
 * one Qdrant REST call for search (~50ms).
 */
class ImageSearchStrategy
{
    /** Qdrant REST endpoint */
    private string $qdrantUrl;

    /** Qdrant collection name for images */
    private string $collection;

    /** Path to embed_image.py script */
    private string $embedScript;

    /** Minimum similarity score to include (0-1 for cosine) */
    private const MIN_SCORE = 0.30;

    public function __construct(?string $collection = null, string $qdrantUrl = 'http://localhost:6333')
    {
        $this->qdrantUrl = rtrim($qdrantUrl, '/');
        $this->collection = $collection ?? self::detectCollection();
        $this->embedScript = \sfConfig::get('sf_plugins_dir')
            . '/ahgDiscoveryPlugin/scripts/embed_image.py';
    }

    /**
     * Detect collection name from the current AtoM database config.
     */
    private static function detectCollection(): string
    {
        $dbName = \sfConfig::get('app_database_name', '');
        if (empty($dbName)) {
            try {
                $dbName = \Illuminate\Database\Capsule\Manager::connection()->getDatabaseName();
            } catch (\Exception $e) {
                $dbName = 'archive';
            }
        }
        return $dbName . '_images';
    }

    /**
     * Search by image file path. Returns visually similar records.
     *
     * @param string $imagePath Absolute path to image file
     * @param int    $limit     Max results
     * @return array [{object_id, image_score, title, slug}, ...]
     */
    public function searchByPath(string $imagePath, int $limit = 50): array
    {
        if (!file_exists($imagePath)) {
            error_log('[ahgDiscovery] ImageSearch: file not found: ' . $imagePath);
            return [];
        }

        $vector = $this->embedImage($imagePath);
        if (empty($vector)) {
            return [];
        }

        return $this->searchQdrant($vector, $limit);
    }

    /**
     * Search by a digital object ID (find similar images to an existing record).
     *
     * @param int $digitalObjectId digital_object.id
     * @param int $limit           Max results
     * @return array [{object_id, image_score, title, slug}, ...]
     */
    public function searchByDigitalObject(int $digitalObjectId, int $limit = 50): array
    {
        $do = \Illuminate\Database\Capsule\Manager::table('digital_object')
            ->where('id', $digitalObjectId)
            ->first();

        if (!$do) {
            return [];
        }

        // Prefer reference derivative
        $ref = \Illuminate\Database\Capsule\Manager::table('digital_object')
            ->where('parent_id', $digitalObjectId)
            ->where('usage_id', 142)
            ->first();

        $root = \sfConfig::get('sf_root_dir');
        if ($ref) {
            $filepath = $root . '/' . ltrim($ref->path, '/') . $ref->name;
        } else {
            $filepath = $root . '/' . ltrim($do->path, '/') . $do->name;
        }

        return $this->searchByPath($filepath, $limit);
    }

    /**
     * Search by a pre-computed vector (e.g. from an API call that already embedded).
     *
     * @param float[] $vector CLIP embedding (512 dims)
     * @param int     $limit  Max results
     * @return array [{object_id, image_score, title, slug}, ...]
     */
    public function searchByVector(array $vector, int $limit = 50): array
    {
        return $this->searchQdrant($vector, $limit);
    }

    /**
     * Embed an image using the Python CLIP script.
     *
     * @return float[]|null Vector or null on failure
     */
    private function embedImage(string $imagePath): ?array
    {
        if (!file_exists($this->embedScript)) {
            error_log('[ahgDiscovery] embed_image.py not found: ' . $this->embedScript);
            return null;
        }

        $cmd = sprintf(
            'python3 %s %s 2>/dev/null',
            escapeshellarg($this->embedScript),
            escapeshellarg($imagePath)
        );

        $output = shell_exec($cmd);
        if (empty($output)) {
            error_log('[ahgDiscovery] embed_image.py returned empty output');
            return null;
        }

        $result = json_decode($output, true);
        if (!$result || empty($result['vector'])) {
            error_log('[ahgDiscovery] embed_image.py invalid response: ' . substr($output, 0, 200));
            return null;
        }

        return $result['vector'];
    }

    /**
     * Search Qdrant image collection via REST API.
     *
     * @param float[] $vector CLIP image embedding
     * @param int     $limit  Max results
     * @return array [{object_id, image_score, title, slug, do_id}, ...]
     */
    private function searchQdrant(array $vector, int $limit): array
    {
        $url = $this->qdrantUrl . '/collections/' . $this->collection . '/points/query';

        $payload = json_encode([
            'query'           => $vector,
            'limit'           => $limit,
            'with_payload'    => true,
            'score_threshold' => self::MIN_SCORE,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 2,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || empty($response)) {
            error_log('[ahgDiscovery] Qdrant image search failed: HTTP ' . $httpCode);
            return [];
        }

        $data = json_decode($response, true);
        if (!isset($data['result']['points'])) {
            error_log('[ahgDiscovery] Qdrant image unexpected response: ' . substr($response, 0, 200));
            return [];
        }

        $results = [];
        foreach ($data['result']['points'] as $point) {
            $results[] = [
                'object_id'   => (int) ($point['payload']['object_id'] ?? 0),
                'image_score' => (float) ($point['score'] ?? 0),
                'title'       => $point['payload']['title'] ?? '',
                'slug'        => $point['payload']['slug'] ?? '',
                'do_id'       => (int) $point['id'],
            ];
        }

        return $results;
    }

    /**
     * Check if Qdrant image collection is available.
     */
    public static function isAvailable(?string $collection = null, string $qdrantUrl = 'http://localhost:6333'): bool
    {
        $col = $collection ?? self::detectCollection();
        $url = rtrim($qdrantUrl, '/') . '/collections/' . $col;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 2,
            CURLOPT_CONNECTTIMEOUT => 1,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return false;
        }

        $data = json_decode($response, true);
        return isset($data['result']['status']) && $data['result']['status'] === 'green';
    }
}
