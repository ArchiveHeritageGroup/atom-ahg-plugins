<?php

declare(strict_types=1);

namespace AtomFramework\Services\SemanticSearch;

use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

/**
 * Wikidata Actor Linking Service
 *
 * Links AtoM actors (persons/organizations) to Wikidata entities
 * via SPARQL queries. Stores matched Q-IDs in the
 * heritage_entity_graph_node table.
 *
 * @package AtomFramework\Services\SemanticSearch
 * @author Johan Pieterse - The Archive and Heritage Group
 * @version 1.0.0
 */
class WikidataActorLinkingService
{
    private Logger $logger;
    private array $config;

    private const SPARQL_ENDPOINT = 'https://query.wikidata.org/sparql';
    private const DEFAULT_RATE_LIMIT_MS = 500;
    private const DEFAULT_THRESHOLD = 0.80;

    // Wikidata class IDs for entity type filtering
    private const PERSON_CLASSES = ['Q5']; // human
    private const ORGANIZATION_CLASSES = ['Q43229', 'Q4830453', 'Q7210356']; // organization, enterprise, political organization
    private const PLACE_CLASSES = ['Q515', 'Q486972', 'Q3957', 'Q56061']; // city, settlement, town, administrative entity

    public function __construct(array $config = [])
    {
        $logDir = class_exists('\sfConfig')
            ? \sfConfig::get('sf_log_dir', '/var/log/atom')
            : '/var/log/atom';

        $this->config = array_merge([
            'log_path' => $logDir . '/wikidata_actor_linking.log',
            'rate_limit_ms' => self::DEFAULT_RATE_LIMIT_MS,
            'timeout' => 30,
            'threshold' => self::DEFAULT_THRESHOLD,
            'max_candidates' => 10,
        ], $config);

        $this->logger = new Logger('wikidata_actor_linking');
        if (is_writable(dirname($this->config['log_path']))) {
            $this->logger->pushHandler(new RotatingFileHandler($this->config['log_path'], 30, Logger::INFO));
        }
    }

    /**
     * Link a single entity to Wikidata by name.
     *
     * @param int    $nodeId      heritage_entity_graph_node.id
     * @param string $name        Entity canonical name
     * @param string $entityType  person|organization|place
     * @param bool   $dryRun      If true, do not write to DB
     * @return array{matched: bool, wikidata_id: ?string, confidence: float, candidates: array}
     */
    public function linkEntity(int $nodeId, string $name, string $entityType = 'person', bool $dryRun = false): array
    {
        $result = [
            'matched' => false,
            'wikidata_id' => null,
            'confidence' => 0.0,
            'candidates' => [],
        ];

        // Check if already linked
        $existing = DB::table('heritage_entity_graph_node')
            ->where('id', $nodeId)
            ->value('wikidata_id');

        if (!empty($existing)) {
            $this->logger->debug('Node already has Wikidata ID', ['node_id' => $nodeId, 'wikidata_id' => $existing]);
            $result['matched'] = true;
            $result['wikidata_id'] = $existing;
            $result['confidence'] = 1.0;
            return $result;
        }

        // Build SPARQL query for entity type
        $candidates = $this->queryWikidata($name, $entityType);

        if (empty($candidates)) {
            $this->logger->info('No Wikidata candidates found', ['name' => $name, 'type' => $entityType]);
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
            $result['wikidata_id'] = $bestMatch['qid'];
            $result['confidence'] = $bestScore;

            if (!$dryRun) {
                DB::table('heritage_entity_graph_node')
                    ->where('id', $nodeId)
                    ->update([
                        'wikidata_id' => $bestMatch['qid'],
                        'last_seen_at' => date('Y-m-d H:i:s'),
                    ]);

                $this->logger->info('Wikidata match stored', [
                    'node_id' => $nodeId,
                    'name' => $name,
                    'wikidata_id' => $bestMatch['qid'],
                    'confidence' => $bestScore,
                ]);
            }
        } else {
            $this->logger->info('No Wikidata match above threshold', [
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
     * @param string $entityType  person|organization|place|all
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
            ->whereNull('wikidata_id')
            ->orderBy('occurrence_count', 'desc')
            ->limit($limit);

        if ($entityType !== 'all') {
            $query->where('entity_type', $entityType);
        } else {
            $query->whereIn('entity_type', ['person', 'organization', 'place']);
        }

        $nodes = $query->get();

        $this->logger->info('Starting Wikidata batch linking', [
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
                $this->logger->error('Wikidata linking failed', [
                    'node_id' => $node->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger->info('Wikidata batch linking complete', $stats);

        return $stats;
    }

    /**
     * Query Wikidata SPARQL endpoint for entity candidates.
     */
    private function queryWikidata(string $name, string $entityType): array
    {
        $limit = $this->config['max_candidates'];
        $escapedName = addslashes($name);

        // Build type filter
        $typeFilter = $this->buildTypeFilter($entityType);

        $query = <<<SPARQL
SELECT DISTINCT ?item ?itemLabel ?itemDescription ?itemAltLabel WHERE {
  SERVICE wikibase:mwapi {
    bd:serviceParam wikibase:endpoint "www.wikidata.org";
    bd:serviceParam wikibase:api "EntitySearch";
    bd:serviceParam mwapi:search "{$escapedName}";
    bd:serviceParam mwapi:language "en".
    ?item wikibase:apiOutputItem mwapi:item.
  }
  {$typeFilter}
  SERVICE wikibase:label {
    bd:serviceParam wikibase:language "en,af,zu,xh".
  }
}
LIMIT {$limit}
SPARQL;

        $results = $this->executeSparql($query);
        $candidates = [];

        foreach ($results as $row) {
            $qid = $this->extractQid($row['item']['value'] ?? '');
            if ($qid) {
                $candidates[] = [
                    'qid' => $qid,
                    'label' => $row['itemLabel']['value'] ?? '',
                    'description' => $row['itemDescription']['value'] ?? '',
                    'aliases' => $this->parseAliases($row['itemAltLabel']['value'] ?? ''),
                ];
            }
        }

        return $candidates;
    }

    /**
     * Build SPARQL type filter based on entity type.
     */
    private function buildTypeFilter(string $entityType): string
    {
        $classes = match ($entityType) {
            'person' => self::PERSON_CLASSES,
            'organization' => self::ORGANIZATION_CLASSES,
            'place' => self::PLACE_CLASSES,
            default => [],
        };

        if (empty($classes)) {
            return '';
        }

        $values = implode(' ', array_map(fn ($c) => "wd:{$c}", $classes));
        return "?item wdt:P31/wdt:P279* ?class . VALUES ?class { {$values} }";
    }

    /**
     * Score a Wikidata candidate against our entity.
     */
    private function scoreCandidateMatch(string $entityName, string $entityType, array $candidate): float
    {
        $candidateLabel = $candidate['label'] ?? '';
        if (empty($candidateLabel)) {
            return 0.0;
        }

        // Normalize
        $normalizedEntity = mb_strtolower(trim($entityName));
        $normalizedCandidate = mb_strtolower(trim($candidateLabel));

        // Exact match
        if ($normalizedEntity === $normalizedCandidate) {
            return 1.0;
        }

        // Levenshtein similarity
        $maxLen = max(mb_strlen($normalizedEntity), mb_strlen($normalizedCandidate));
        if ($maxLen === 0) {
            return 0.0;
        }

        $distance = levenshtein($normalizedEntity, $normalizedCandidate);
        $similarity = 1.0 - ($distance / $maxLen);

        // Check aliases for better match
        $aliases = $candidate['aliases'] ?? [];
        foreach ($aliases as $alias) {
            $normalizedAlias = mb_strtolower(trim($alias));
            if ($normalizedAlias === $normalizedEntity) {
                $similarity = max($similarity, 0.95);
                break;
            }

            $aliasMaxLen = max(mb_strlen($normalizedEntity), mb_strlen($normalizedAlias));
            if ($aliasMaxLen > 0) {
                $aliasDist = levenshtein($normalizedEntity, $normalizedAlias);
                $aliasSim = 1.0 - ($aliasDist / $aliasMaxLen);
                $similarity = max($similarity, $aliasSim);
            }
        }

        // Description context boost
        $description = mb_strtolower($candidate['description'] ?? '');
        if (!empty($description)) {
            $contextTerms = match ($entityType) {
                'person' => ['born', 'died', 'politician', 'writer', 'artist', 'activist', 'leader'],
                'organization' => ['founded', 'company', 'party', 'association', 'institution', 'organization'],
                'place' => ['city', 'town', 'province', 'region', 'district', 'municipality'],
                default => [],
            };

            foreach ($contextTerms as $term) {
                if (str_contains($description, $term)) {
                    $similarity = min(1.0, $similarity + 0.05);
                    break;
                }
            }
        }

        return round($similarity, 4);
    }

    /**
     * Execute SPARQL query and return raw results.
     */
    private function executeSparql(string $query): array
    {
        $url = self::SPARQL_ENDPOINT . '?' . http_build_query([
            'query' => $query,
            'format' => 'json',
        ]);

        $context = stream_context_create([
            'http' => [
                'timeout' => $this->config['timeout'],
                'header' => [
                    'User-Agent: AtoM-Framework/1.0 (https://theahg.co.za; johan@theahg.co.za)',
                    'Accept: application/sparql-results+json',
                ],
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            $error = error_get_last();
            $this->logger->error('Wikidata SPARQL request failed', ['error' => $error['message'] ?? 'Unknown']);
            return [];
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('Invalid Wikidata SPARQL JSON response');
            return [];
        }

        return $data['results']['bindings'] ?? [];
    }

    /**
     * Extract QID from Wikidata URI.
     */
    private function extractQid(string $uri): ?string
    {
        if (preg_match('/Q\d+$/', $uri, $matches)) {
            return $matches[0];
        }
        return null;
    }

    /**
     * Parse comma-separated aliases.
     */
    private function parseAliases(string $aliasString): array
    {
        if (empty($aliasString)) {
            return [];
        }
        return array_map('trim', explode(',', $aliasString));
    }

    /**
     * Get linking statistics.
     */
    public function getStats(): array
    {
        $total = DB::table('heritage_entity_graph_node')
            ->whereIn('entity_type', ['person', 'organization', 'place'])
            ->count();

        $linked = DB::table('heritage_entity_graph_node')
            ->whereIn('entity_type', ['person', 'organization', 'place'])
            ->whereNotNull('wikidata_id')
            ->count();

        return [
            'total_linkable' => $total,
            'linked' => $linked,
            'unlinked' => $total - $linked,
            'coverage_pct' => $total > 0 ? round(($linked / $total) * 100, 1) : 0,
        ];
    }
}
