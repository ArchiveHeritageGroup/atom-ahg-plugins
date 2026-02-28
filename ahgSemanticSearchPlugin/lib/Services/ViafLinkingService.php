<?php

declare(strict_types=1);

namespace AtomFramework\Services\SemanticSearch;

use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

/**
 * VIAF Linking Service
 *
 * Links AtoM actors (persons/organizations) to VIAF authority records
 * via the VIAF AutoSuggest API. Stores matched VIAF IDs in the
 * heritage_entity_graph_node table.
 *
 * @package AtomFramework\Services\SemanticSearch
 * @author Johan Pieterse - The Archive and Heritage Group
 * @version 1.0.0
 */
class ViafLinkingService
{
    private Logger $logger;
    private array $config;

    private const AUTOSUGGEST_ENDPOINT = 'https://viaf.org/viaf/AutoSuggest';
    private const DEFAULT_RATE_LIMIT_MS = 500;
    private const DEFAULT_THRESHOLD = 0.85;

    public function __construct(array $config = [])
    {
        $logDir = class_exists('\sfConfig')
            ? \sfConfig::get('sf_log_dir', '/var/log/atom')
            : '/var/log/atom';

        $this->config = array_merge([
            'log_path' => $logDir . '/viaf_linking.log',
            'rate_limit_ms' => self::DEFAULT_RATE_LIMIT_MS,
            'timeout' => 15,
            'threshold' => self::DEFAULT_THRESHOLD,
        ], $config);

        $this->logger = new Logger('viaf_linking');
        if (is_writable(dirname($this->config['log_path']))) {
            $this->logger->pushHandler(new RotatingFileHandler($this->config['log_path'], 30, Logger::INFO));
        }
    }

    /**
     * Link a single entity to VIAF by name.
     *
     * @param int    $nodeId      heritage_entity_graph_node.id
     * @param string $name        Entity canonical name
     * @param string $entityType  person|organization
     * @param bool   $dryRun      If true, do not write to DB
     * @return array{matched: bool, viaf_id: ?string, confidence: float, candidates: array}
     */
    public function linkEntity(int $nodeId, string $name, string $entityType = 'person', bool $dryRun = false): array
    {
        $result = [
            'matched' => false,
            'viaf_id' => null,
            'confidence' => 0.0,
            'candidates' => [],
        ];

        // Check if already linked
        $existing = DB::table('heritage_entity_graph_node')
            ->where('id', $nodeId)
            ->value('viaf_id');

        if (!empty($existing)) {
            $this->logger->debug('Node already has VIAF ID', ['node_id' => $nodeId, 'viaf_id' => $existing]);
            $result['matched'] = true;
            $result['viaf_id'] = $existing;
            $result['confidence'] = 1.0;
            return $result;
        }

        // Query VIAF AutoSuggest
        $candidates = $this->queryViaf($name);

        if (empty($candidates)) {
            $this->logger->info('No VIAF candidates found', ['name' => $name]);
            return $result;
        }

        $result['candidates'] = $candidates;

        // Score candidates
        $bestMatch = null;
        $bestScore = 0.0;

        foreach ($candidates as $candidate) {
            $score = $this->scoreCandidateMatch($name, $entityType, $candidate);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $candidate;
            }
        }

        if ($bestMatch && $bestScore >= $this->config['threshold']) {
            $result['matched'] = true;
            $result['viaf_id'] = $bestMatch['viaf_id'];
            $result['confidence'] = $bestScore;

            if (!$dryRun) {
                DB::table('heritage_entity_graph_node')
                    ->where('id', $nodeId)
                    ->update([
                        'viaf_id' => $bestMatch['viaf_id'],
                        'last_seen_at' => date('Y-m-d H:i:s'),
                    ]);

                $this->logger->info('VIAF match stored', [
                    'node_id' => $nodeId,
                    'name' => $name,
                    'viaf_id' => $bestMatch['viaf_id'],
                    'confidence' => $bestScore,
                ]);
            }
        } else {
            $this->logger->info('No VIAF match above threshold', [
                'name' => $name,
                'best_score' => $bestScore,
                'threshold' => $this->config['threshold'],
            ]);
        }

        return $result;
    }

    /**
     * Batch link entities from heritage_entity_graph_node.
     *
     * @param string $entityType  person|organization|all
     * @param int    $limit       Maximum entities to process
     * @param bool   $dryRun      If true, do not write to DB
     * @return array Summary statistics
     */
    public function batchLink(string $entityType = 'all', int $limit = 100, bool $dryRun = false): array
    {
        $stats = [
            'processed' => 0,
            'matched' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $query = DB::table('heritage_entity_graph_node')
            ->whereNull('viaf_id')
            ->orderBy('occurrence_count', 'desc')
            ->limit($limit);

        if ($entityType !== 'all') {
            $query->where('entity_type', $entityType);
        } else {
            $query->whereIn('entity_type', ['person', 'organization']);
        }

        $nodes = $query->get();

        $this->logger->info('Starting VIAF batch linking', [
            'entity_type' => $entityType,
            'limit' => $limit,
            'total_nodes' => count($nodes),
            'dry_run' => $dryRun,
        ]);

        foreach ($nodes as $node) {
            try {
                $result = $this->linkEntity(
                    $node->id,
                    $node->canonical_value,
                    $node->entity_type,
                    $dryRun
                );

                $stats['processed']++;

                if ($result['matched']) {
                    $stats['matched']++;
                }

                // Rate limiting
                usleep($this->config['rate_limit_ms'] * 1000);

            } catch (\Exception $e) {
                $stats['failed']++;
                $stats['errors'][] = "{$node->canonical_value}: " . $e->getMessage();
                $this->logger->error('VIAF linking failed', [
                    'node_id' => $node->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger->info('VIAF batch linking complete', $stats);

        return $stats;
    }

    /**
     * Query VIAF AutoSuggest API.
     *
     * @return array Array of candidates with viaf_id, name, name_type, source
     */
    private function queryViaf(string $name): array
    {
        $url = self::AUTOSUGGEST_ENDPOINT . '?' . http_build_query([
            'query' => $name,
        ]);

        $context = stream_context_create([
            'http' => [
                'timeout' => $this->config['timeout'],
                'header' => [
                    'User-Agent: AtoM-Framework/1.0 (https://theahg.co.za; johan@theahg.co.za)',
                    'Accept: application/json',
                ],
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            $error = error_get_last();
            $this->logger->error('VIAF request failed', ['error' => $error['message'] ?? 'Unknown']);
            return [];
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['result'])) {
            $this->logger->error('Invalid VIAF JSON response');
            return [];
        }

        $candidates = [];
        foreach ($data['result'] as $item) {
            $candidates[] = [
                'viaf_id' => $item['viafid'] ?? null,
                'name' => $item['displayForm'] ?? $item['term'] ?? '',
                'name_type' => $item['nametype'] ?? '',
                'source' => $item['source'] ?? '',
                'score' => $item['score'] ?? 0,
            ];
        }

        return $candidates;
    }

    /**
     * Score a VIAF candidate against our entity.
     *
     * Uses Levenshtein distance + entity type matching for scoring.
     */
    private function scoreCandidateMatch(string $entityName, string $entityType, array $candidate): float
    {
        $candidateName = $candidate['name'] ?? '';
        if (empty($candidateName)) {
            return 0.0;
        }

        // Normalize for comparison
        $normalizedEntity = mb_strtolower(trim($entityName));
        $normalizedCandidate = mb_strtolower(trim($candidateName));

        // Exact match
        if ($normalizedEntity === $normalizedCandidate) {
            return 1.0;
        }

        // Levenshtein similarity (0.0-1.0)
        $maxLen = max(mb_strlen($normalizedEntity), mb_strlen($normalizedCandidate));
        if ($maxLen === 0) {
            return 0.0;
        }

        $distance = levenshtein($normalizedEntity, $normalizedCandidate);
        $similarity = 1.0 - ($distance / $maxLen);

        // Entity type matching boost
        $nameType = $candidate['name_type'] ?? '';
        $typeMatch = false;
        if ($entityType === 'person' && $nameType === 'personal') {
            $typeMatch = true;
        } elseif ($entityType === 'organization' && $nameType === 'corporate') {
            $typeMatch = true;
        }

        if ($typeMatch) {
            $similarity = min(1.0, $similarity + 0.10);
        }

        return round($similarity, 4);
    }

    /**
     * Get linking statistics.
     */
    public function getStats(): array
    {
        $total = DB::table('heritage_entity_graph_node')
            ->whereIn('entity_type', ['person', 'organization'])
            ->count();

        $linked = DB::table('heritage_entity_graph_node')
            ->whereIn('entity_type', ['person', 'organization'])
            ->whereNotNull('viaf_id')
            ->count();

        return [
            'total_linkable' => $total,
            'linked' => $linked,
            'unlinked' => $total - $linked,
            'coverage_pct' => $total > 0 ? round(($linked / $total) * 100, 1) : 0,
        ];
    }
}
