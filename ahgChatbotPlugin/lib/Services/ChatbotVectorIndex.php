<?php

namespace AhgChatbotPlugin\Services;

use AtomExtensions\Services\AhgSettingsService;
use AtomFramework\Services\AI\AiGatewayClient;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Semantic index of PUBLISHED descriptions in Qdrant, embedded through the AHG
 * AI gateway (nomic-embed-text). Point ids are information_object ids.
 *
 * The default collection is "{db}_io_nomic", the same one ahgAIPlugin's
 * ai:index-catalogue builds, so an install that already has that index needs no
 * re-embedding. Only the data is shared; this plugin carries its own code.
 *
 * Qdrant is a vector store, not a GPU node, so the direct localhost REST calls
 * are infrastructure access, not an AI-gateway bypass. Embeddings go through
 * the gateway.
 *
 * Everything degrades: no gateway key or no Qdrant means search() returns []
 * and the assistant answers from MySQL FULLTEXT alone.
 */
class ChatbotVectorIndex
{
    private const PUBLICATION_STATUS_TYPE_ID = 158;
    private const PUBLICATION_STATUS_PUBLISHED_ID = 160;

    private string $qdrantUrl;
    private string $collection;
    private float $minScore;

    public function __construct()
    {
        $this->qdrantUrl = rtrim((string) AhgSettingsService::get('chatbot_qdrant_url', 'http://localhost:6333'), '/');
        $this->collection = (string) AhgSettingsService::get('chatbot_vector_collection', '') ?: self::defaultCollection();
        $min = AhgSettingsService::get('chatbot_vector_min_score', '0.45');
        $this->minScore = is_numeric($min) ? (float) $min : 0.45;
    }

    private static function defaultCollection(): string
    {
        try {
            $db = DB::connection()->getDatabaseName();
        } catch (\Throwable $e) {
            $db = 'archive';
        }

        return $db.'_io_nomic';
    }

    public function getCollection(): string
    {
        return $this->collection;
    }

    /** Gateway key configured and Qdrant reachable. */
    public function isEnabled(): bool
    {
        if (!class_exists(AiGatewayClient::class) || !AiGatewayClient::fromSettings()->isConfigured()) {
            return false;
        }

        return 200 === ($this->qdrant('GET', '/collections', null, 4)['status'] ?? 0);
    }

    /**
     * Ids of the nearest descriptions, best first. Visibility is NOT decided
     * here - the caller re-checks every id (ChatbotRetriever::visibleOnly).
     *
     * @return int[]
     */
    public function search(string $query, int $limit): array
    {
        $query = trim($query);
        if ('' === $query || !$this->isEnabled()) {
            return [];
        }

        $vec = AiGatewayClient::fromSettings()->embed($query);
        if (!is_array($vec) || [] === $vec) {
            return [];
        }

        $res = $this->qdrant('POST', '/collections/'.rawurlencode($this->collection).'/points/query', json_encode([
            'query' => $vec,
            'limit' => $limit,
            'with_payload' => false,
            'score_threshold' => $this->minScore,
        ]));
        if (200 !== ($res['status'] ?? 0)) {
            return [];
        }

        $data = json_decode($res['body'] ?? '', true);
        $points = $data['result']['points'] ?? ($data['result'] ?? []);

        return is_array($points) ? array_values(array_filter(array_map(static fn ($p) => (int) ($p['id'] ?? 0), $points))) : [];
    }

    /**
     * Embed and upsert one batch of published descriptions.
     *
     * @return array{indexed:int,skipped:int,failed:int,done:bool,next_offset:int}
     */
    public function indexBatch(int $limit, int $offset, string $culture): array
    {
        $stats = ['indexed' => 0, 'skipped' => 0, 'failed' => 0, 'done' => false, 'next_offset' => $offset];

        $rows = $this->publishedQuery($culture)
            ->orderBy('io.id')->offset($offset)->limit($limit)
            ->get(['io.id', 'io.identifier', 'ioi.title', 'ioi.scope_and_content', 's.slug'])
            ->all();
        if ([] === $rows) {
            $stats['done'] = true;

            return $stats;
        }

        $gw = AiGatewayClient::fromSettings();
        $points = [];
        foreach ($rows as $r) {
            $text = trim(trim((string) $r->title)."\n".mb_substr(trim(strip_tags((string) $r->scope_and_content)), 0, 4000));
            if ('' === $text) {
                ++$stats['skipped'];

                continue;
            }
            $vec = $gw->embed($text);
            if (!is_array($vec) || [] === $vec) {
                ++$stats['failed'];

                continue;
            }
            $this->ensureCollection(count($vec));
            $points[] = [
                'id' => (int) $r->id,
                'vector' => $vec,
                'payload' => ['object_id' => (int) $r->id, 'slug' => (string) $r->slug, 'title' => (string) ($r->title ?: $r->slug), 'identifier' => (string) ($r->identifier ?? '')],
            ];
        }

        if ([] !== $points) {
            $ok = 200 === ($this->qdrant('PUT', '/collections/'.rawurlencode($this->collection).'/points?wait=true', json_encode(['points' => $points]))['status'] ?? 0);
            $stats[$ok ? 'indexed' : 'failed'] += count($points);
        }

        $stats['next_offset'] = $offset + count($rows);
        $stats['done'] = count($rows) < $limit;

        return $stats;
    }

    public function publishedCount(string $culture): int
    {
        try {
            return (int) $this->publishedQuery($culture)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Remove points whose description is no longer published or no longer
     * exists. Indexing only adds, so without this an unpublished record stays
     * in the index for good. (Retrieval re-checks visibility regardless; this
     * stops phantom hits crowding out real ones.)
     *
     * @return array{checked:int,removed:int,failed:bool}
     */
    public function prune(string $culture, bool $dryRun = false): array
    {
        $stats = ['checked' => 0, 'removed' => 0, 'failed' => false];
        $path = '/collections/'.rawurlencode($this->collection).'/points';
        $offset = null;

        do {
            $body = ['limit' => 1000, 'with_payload' => false, 'with_vector' => false];
            if (null !== $offset) {
                $body['offset'] = $offset;
            }
            $res = $this->qdrant('POST', $path.'/scroll', json_encode($body));
            if (200 !== ($res['status'] ?? 0)) {
                $stats['failed'] = true;

                break;
            }
            $data = json_decode($res['body'] ?? '', true);
            $ids = array_map(static fn ($p) => (int) $p['id'], $data['result']['points'] ?? []);
            $offset = $data['result']['next_page_offset'] ?? null;
            if ([] === $ids) {
                break;
            }
            $stats['checked'] += count($ids);

            $keep = array_flip(array_map('intval', $this->publishedQuery($culture)->whereIn('io.id', $ids)->pluck('io.id')->all()));
            $gone = array_values(array_filter($ids, static fn ($id) => !isset($keep[$id])));

            if ([] !== $gone && !$dryRun
                && 200 !== ($this->qdrant('POST', $path.'/delete?wait=true', json_encode(['points' => $gone]))['status'] ?? 0)) {
                $stats['failed'] = true;

                break;
            }
            $stats['removed'] += count($gone);
        } while (null !== $offset);

        return $stats;
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
            ->where('io.id', '>', 1); // the root information object
    }

    private function ensureCollection(int $dim): void
    {
        static $ensured = [];
        if (isset($ensured[$this->collection])) {
            return;
        }
        if (200 !== ($this->qdrant('GET', '/collections/'.rawurlencode($this->collection))['status'] ?? 0)) {
            $this->qdrant('PUT', '/collections/'.rawurlencode($this->collection), json_encode(['vectors' => ['size' => $dim, 'distance' => 'Cosine']]));
        }
        $ensured[$this->collection] = true;
    }

    /** @return array{status:int,body:string} */
    private function qdrant(string $method, string $path, ?string $body = null, int $timeout = 30): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->qdrantUrl.$path,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ] + (null !== $body ? [CURLOPT_POSTFIELDS => $body] : []));
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        return '' !== $err ? ['status' => 0, 'body' => ''] : ['status' => $code, 'body' => is_string($resp) ? $resp : ''];
    }
}
