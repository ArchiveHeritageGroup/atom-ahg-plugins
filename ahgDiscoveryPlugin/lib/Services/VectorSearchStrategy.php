<?php

namespace AhgDiscovery\Services;

/**
 * Step 2D: Vector (Semantic) Search via Qdrant
 *
 * Embeds the user query using sentence-transformers (all-MiniLM-L6-v2)
 * then searches the Qdrant vector collection for semantically similar records.
 *
 * This enables Discovery to find records that are semantically related
 * to the query even when no exact keyword overlap exists.
 *
 * NO AI CALLS at index time (pre-built). One Python call at query time
 * for embedding (~200ms), one Qdrant REST call for search (~50ms).
 */
class VectorSearchStrategy
{
    /** Qdrant REST endpoint */
    private string $qdrantUrl;

    /** Qdrant collection name */
    private string $collection;

    /** Path to embed_query.py script */
    private string $embedScript;

    /** Minimum similarity score to include (0-1 for cosine) */
    private float $minScore = 0.25;

    /** Embedding model name (passed to embed script) */
    private string $model = 'all-MiniLM-L6-v2';

    public function __construct(?string $collection = null, ?string $qdrantUrl = null)
    {
        // Read settings from ahg_ner_settings (AI Services settings page)
        $settings = self::loadSettings();

        $this->qdrantUrl = rtrim($qdrantUrl ?? ($settings['qdrant_url'] ?? 'http://localhost:6333'), '/');
        $this->collection = $collection ?? ($settings['qdrant_collection'] ?? '') ?: self::detectCollection();
        $this->model = $settings['qdrant_model'] ?? 'all-MiniLM-L6-v2';

        $minScore = $settings['qdrant_min_score'] ?? '';
        if (is_numeric($minScore)) {
            $this->minScore = (float) $minScore;
        }

        $this->embedScript = \sfConfig::get('sf_plugins_dir')
            . '/ahgDiscoveryPlugin/scripts/embed_query.py';
    }

    /**
     * Load Qdrant settings from ahg_ner_settings table.
     */
    private static function loadSettings(): array
    {
        try {
            $rows = \Illuminate\Database\Capsule\Manager::table('ahg_ner_settings')
                ->whereIn('setting_key', ['qdrant_enabled', 'qdrant_url', 'qdrant_collection', 'qdrant_model', 'qdrant_min_score'])
                ->get();
            $settings = [];
            foreach ($rows as $row) {
                $settings[$row->setting_key] = $row->setting_value;
            }
            return $settings;
        } catch (\Exception $e) {
            return [];
        }
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
        return $dbName . '_records';
    }

    /**
     * Search Qdrant for semantically similar records.
     *
     * @param array $expanded ExpandedQuery from QueryExpander
     * @param int   $limit    Max results
     * @return array [{object_id, vector_score, title}, ...]
     */
    public function search(array $expanded, int $limit = 50): array
    {
        // Check if Qdrant is enabled in settings
        $settings = self::loadSettings();
        if (($settings['qdrant_enabled'] ?? '0') !== '1') {
            return [];
        }

        // Build query text from expanded terms
        $queryText = $this->buildQueryText($expanded);
        if (empty($queryText)) {
            return [];
        }

        // Step 1: Embed the query via Python
        $vector = $this->embedQuery($queryText);
        if (empty($vector)) {
            return [];
        }

        // Step 2: Search Qdrant
        return $this->searchQdrant($vector, $limit);
    }

    /**
     * Build a natural text query from expanded terms.
     */
    private function buildQueryText(array $expanded): string
    {
        $parts = [];

        if (!empty($expanded['phrases'])) {
            $parts = array_merge($parts, $expanded['phrases']);
        }

        if (!empty($expanded['entityTerms'])) {
            foreach ($expanded['entityTerms'] as $entity) {
                $parts[] = $entity['value'];
            }
        }

        if (!empty($expanded['keywords'])) {
            $parts = array_merge($parts, $expanded['keywords']);
        }

        return implode(' ', $parts);
    }

    /**
     * Embed query text using the Python sentence-transformers script.
     *
     * @return float[]|null Vector or null on failure
     */
    private function embedQuery(string $text): ?array
    {
        if (!file_exists($this->embedScript)) {
            error_log('[ahgDiscovery] embed_query.py not found: ' . $this->embedScript);
            return null;
        }

        $escapedText = escapeshellarg($text);
        $modelArg = escapeshellarg($this->model);
        $cmd = sprintf('python3 %s %s --model %s 2>/dev/null', escapeshellarg($this->embedScript), $escapedText, $modelArg);

        $output = shell_exec($cmd);
        if (empty($output)) {
            error_log('[ahgDiscovery] embed_query.py returned empty output');
            return null;
        }

        $result = json_decode($output, true);
        if (!$result || empty($result['vector'])) {
            error_log('[ahgDiscovery] embed_query.py invalid response: ' . substr($output, 0, 200));
            return null;
        }

        return $result['vector'];
    }

    /**
     * Search Qdrant via REST API.
     *
     * @param float[] $vector Query embedding
     * @param int     $limit  Max results
     * @return array [{object_id, vector_score, title}, ...]
     */
    private function searchQdrant(array $vector, int $limit): array
    {
        $url = $this->qdrantUrl . '/collections/' . $this->collection . '/points/query';

        $payload = json_encode([
            'query'      => $vector,
            'limit'      => $limit,
            'with_payload' => true,
            'score_threshold' => $this->minScore,
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
            error_log('[ahgDiscovery] Qdrant search failed: HTTP ' . $httpCode);
            return [];
        }

        $data = json_decode($response, true);
        if (!isset($data['result']['points'])) {
            error_log('[ahgDiscovery] Qdrant unexpected response: ' . substr($response, 0, 200));
            return [];
        }

        $results = [];
        foreach ($data['result']['points'] as $point) {
            $results[] = [
                'object_id'    => (int) $point['id'],
                'vector_score' => (float) ($point['score'] ?? 0),
                'title'        => $point['payload']['title'] ?? '',
                'slug'         => $point['payload']['slug'] ?? '',
            ];
        }

        return $results;
    }

    /**
     * Check if Qdrant is available and collection exists.
     */
    public static function isAvailable(?string $collection = null, ?string $qdrantUrl = null): bool
    {
        $settings = self::loadSettings();

        // Respect enabled toggle
        if (($settings['qdrant_enabled'] ?? '0') !== '1') {
            return false;
        }

        $url = rtrim($qdrantUrl ?? ($settings['qdrant_url'] ?? 'http://localhost:6333'), '/');
        $col = $collection ?? ($settings['qdrant_collection'] ?? '') ?: self::detectCollection();
        $url .= '/collections/' . $col;

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
