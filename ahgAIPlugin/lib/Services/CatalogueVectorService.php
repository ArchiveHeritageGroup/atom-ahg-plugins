<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Catalogue Vector Service — gateway-fed semantic search over the catalogue.
 *
 * Embeds PUBLISHED information objects via the AHG AI gateway
 * (\AtomFramework\Services\AI\AiGatewayClient → nomic-embed-text) and stores
 * the vectors in a dedicated Qdrant collection ("{db}_io_nomic"). This is kept
 * SEPARATE from ahgDiscoveryPlugin's MiniLM "{db}_records" collection (384-dim,
 * locally embedded) — the two use different models / dimensions and must not
 * collide.
 *
 * Qdrant itself is a vector store, not a GPU inference node, so the direct
 * localhost:6333 REST calls are infrastructure access, not an AI-gateway bypass.
 * Only the embeddings go through the gateway.
 *
 * Everything degrades gracefully: when the gateway key is unset or Qdrant is
 * down, isEnabled() is false and search() returns [] so the chatbot falls back
 * to FULLTEXT exactly as before.
 */
class CatalogueVectorService
{
    private const PUBLICATION_STATUS_TYPE_ID = 158;
    private const PUBLICATION_STATUS_PUBLISHED_ID = 160;

    private string $qdrantUrl;
    private string $collection;
    private float $minScore = 0.45;

    public function __construct(?string $qdrantUrl = null, ?string $collection = null)
    {
        $settings = self::loadSettings();
        $this->qdrantUrl = rtrim($qdrantUrl ?? ($settings['qdrant_url'] ?? 'http://localhost:6333'), '/');
        $this->collection = $collection ?? ($settings['vector_collection'] ?? '') ?: self::detectCollection();

        $min = $settings['vector_min_score'] ?? '';
        if (is_numeric($min)) {
            $this->minScore = (float) $min;
        }
    }

    /** Settings live in ahg_ai_settings (feature='gateway'). */
    private static function loadSettings(): array
    {
        try {
            $rows = DB::table('ahg_ai_settings')
                ->where('feature', 'gateway')
                ->whereIn('setting_key', ['qdrant_url', 'vector_collection', 'vector_min_score'])
                ->get();
            $out = [];
            foreach ($rows as $row) {
                $out[$row->setting_key] = $row->setting_value;
            }

            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function detectCollection(): string
    {
        $db = '';
        if (class_exists('sfConfig')) {
            $db = (string) \sfConfig::get('app_database_name', '');
        }
        if ('' === $db) {
            try {
                $db = DB::connection()->getDatabaseName();
            } catch (\Throwable $e) {
                $db = 'archive';
            }
        }

        return $db . '_io_nomic';
    }

    public function getCollection(): string
    {
        return $this->collection;
    }

    /** Gateway key configured AND Qdrant reachable. */
    public function isEnabled(): bool
    {
        if (!class_exists('\AtomFramework\Services\AI\AiGatewayClient')) {
            return false;
        }
        if (!\AtomFramework\Services\AI\AiGatewayClient::fromSettings()->isConfigured()) {
            return false;
        }

        return $this->qdrantReachable();
    }

    private function qdrantReachable(): bool
    {
        $res = $this->qdrant('GET', '/collections', null, 4);

        return ($res['status'] ?? 0) === 200;
    }

    private function gateway(): \AtomFramework\Services\AI\AiGatewayClient
    {
        return \AtomFramework\Services\AI\AiGatewayClient::fromSettings();
    }

    // =========================================================================
    // Indexing
    // =========================================================================

    /**
     * Embed + upsert a batch of published descriptions.
     *
     * @return array{indexed:int,skipped:int,failed:int,done:bool,next_offset:int}
     */
    public function indexBatch(int $limit = 200, int $offset = 0, string $culture = 'en', bool $dryRun = false): array
    {
        $stats = ['indexed' => 0, 'skipped' => 0, 'failed' => 0, 'done' => false, 'next_offset' => $offset];

        $rows = $this->publishedQuery($culture)
            ->orderBy('io.id')
            ->offset($offset)
            ->limit($limit)
            ->get(['io.id', 'io.identifier', 'ioi.title', 'ioi.scope_and_content', 's.slug'])
            ->all();

        if (empty($rows)) {
            $stats['done'] = true;

            return $stats;
        }

        $gw = $this->gateway();
        $points = [];
        foreach ($rows as $r) {
            $text = $this->recordText($r);
            if ('' === $text) {
                $stats['skipped']++;
                continue;
            }
            if ($dryRun) {
                $stats['indexed']++;
                continue;
            }

            $vec = $gw->embed($text);
            if (!is_array($vec) || $vec === []) {
                $stats['failed']++;
                continue;
            }

            // Create the collection lazily once we know the real dimension.
            $this->ensureCollection(count($vec));

            $points[] = [
                'id' => (int) $r->id,
                'vector' => $vec,
                'payload' => [
                    'object_id' => (int) $r->id,
                    'slug' => (string) $r->slug,
                    'title' => (string) ($r->title ?: $r->slug),
                    'identifier' => (string) ($r->identifier ?? ''),
                ],
            ];
            $stats['indexed']++;
        }

        if (!$dryRun && $points !== []) {
            $ok = $this->upsert($points);
            if (!$ok) {
                // roll the indexed count back into failed: the batch didn't land.
                $stats['failed'] += count($points);
                $stats['indexed'] -= count($points);
            }
        }

        $stats['next_offset'] = $offset + count($rows);
        $stats['done'] = count($rows) < $limit;

        return $stats;
    }

    /** Count of published descriptions eligible for indexing. */
    public function publishedCount(string $culture = 'en'): int
    {
        try {
            return (int) $this->publishedQuery($culture)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function publishedQuery(string $culture)
    {
        return DB::table('information_object_i18n as ioi')
            ->join('information_object as io', 'io.id', '=', 'ioi.id')
            ->join('slug as s', 's.object_id', '=', 'io.id')
            ->join('status as st', static function ($j) {
                $j->on('st.object_id', '=', 'io.id')
                    ->where('st.type_id', self::PUBLICATION_STATUS_TYPE_ID)
                    ->where('st.status_id', self::PUBLICATION_STATUS_PUBLISHED_ID);
            })
            ->where('ioi.culture', $culture)
            ->where('io.id', '>', 1); // skip the root information object
    }

    private function recordText(object $r): string
    {
        $title = trim((string) ($r->title ?? ''));
        $scope = trim(strip_tags((string) ($r->scope_and_content ?? '')));
        $text = trim($title . "\n" . mb_substr($scope, 0, 4000));

        return $text;
    }

    // =========================================================================
    // Search
    // =========================================================================

    /**
     * Semantic search. Returns [{object_id, score, slug, title, identifier}, ...]
     * ordered by score desc. Empty array when disabled or on any failure.
     */
    public function search(string $query, int $limit = 6): array
    {
        $query = trim($query);
        if ('' === $query || !$this->isEnabled()) {
            return [];
        }

        $vec = $this->gateway()->embed($query);
        if (!is_array($vec) || $vec === []) {
            return [];
        }

        $body = json_encode([
            'query' => $vec,
            'limit' => $limit,
            'with_payload' => true,
            'score_threshold' => $this->minScore,
        ]);

        $res = $this->qdrant('POST', '/collections/' . rawurlencode($this->collection) . '/points/query', $body);
        if (($res['status'] ?? 0) !== 200) {
            return [];
        }

        $data = json_decode($res['body'] ?? '', true);
        $points = $data['result']['points'] ?? ($data['result'] ?? []);
        if (!is_array($points)) {
            return [];
        }

        $out = [];
        foreach ($points as $p) {
            $payload = $p['payload'] ?? [];
            $out[] = [
                'object_id' => (int) ($payload['object_id'] ?? $p['id'] ?? 0),
                'score' => (float) ($p['score'] ?? 0),
                'slug' => (string) ($payload['slug'] ?? ''),
                'title' => (string) ($payload['title'] ?? ''),
                'identifier' => (string) ($payload['identifier'] ?? ''),
            ];
        }

        return $out;
    }

    // =========================================================================
    // Qdrant REST
    // =========================================================================

    private function ensureCollection(int $dim): void
    {
        static $ensured = [];
        $key = $this->collection . ':' . $dim;
        if (isset($ensured[$key])) {
            return;
        }

        $res = $this->qdrant('GET', '/collections/' . rawurlencode($this->collection));
        if (($res['status'] ?? 0) === 200) {
            $ensured[$key] = true;

            return;
        }

        $body = json_encode([
            'vectors' => ['size' => $dim, 'distance' => 'Cosine'],
        ]);
        $this->qdrant('PUT', '/collections/' . rawurlencode($this->collection), $body);
        $ensured[$key] = true;
    }

    /** @param array<int,array> $points */
    private function upsert(array $points): bool
    {
        $body = json_encode(['points' => $points]);
        $res = $this->qdrant('PUT', '/collections/' . rawurlencode($this->collection) . '/points?wait=true', $body);

        return ($res['status'] ?? 0) === 200;
    }

    /**
     * Minimal Qdrant HTTP. Direct localhost call by design (Qdrant is a vector
     * store, not a GPU node — not subject to the AI-gateway routing rule).
     *
     * @return array{status:int,body:string}
     */
    private function qdrant(string $method, string $path, ?string $body = null, int $timeout = 30): array
    {
        $ch = curl_init();
        $opts = [
            CURLOPT_URL => $this->qdrantUrl . $path,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ];
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($ch, $opts);

        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err !== '') {
            return ['status' => 0, 'body' => ''];
        }

        return ['status' => $code, 'body' => is_string($resp) ? $resp : ''];
    }
}
