<?php

declare(strict_types=1);

namespace arMuseumMetadataPlugin\Services\Getty;

use arMuseumMetadataPlugin\Models\GettyVocabularyLink;
use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Getty Linking Service.
 *
 * Provides batch linking capabilities for connecting AtoM taxonomy terms
 * to Getty Vocabulary URIs. Supports auto-linking with confidence scoring
 * and manual review workflows.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class GettyLinkingService
{
    /** Taxonomy IDs for museum-related vocabularies */
    public const TAXONOMY_MATERIALS = 'MUSEUM_MATERIALS';
    public const TAXONOMY_TECHNIQUES = 'MUSEUM_TECHNIQUES';
    public const TAXONOMY_OBJECT_TYPES = 'MUSEUM_OBJECT_TYPES';
    public const TAXONOMY_STYLES_PERIODS = 'MUSEUM_STYLES_PERIODS';
    public const TAXONOMY_PLACES = 'PLACES';

    /** Mapping of taxonomy types to Getty vocabularies */
    private const TAXONOMY_VOCABULARY_MAP = [
        self::TAXONOMY_MATERIALS => 'aat',
        self::TAXONOMY_TECHNIQUES => 'aat',
        self::TAXONOMY_OBJECT_TYPES => 'aat',
        self::TAXONOMY_STYLES_PERIODS => 'aat',
        self::TAXONOMY_PLACES => 'tgn',
    ];

    private ConnectionInterface $db;
    private GettyLinkRepository $repository;
    private AatService $aatService;
    private TgnService $tgnService;
    private UlanService $ulanService;
    private LoggerInterface $logger;

    public function __construct(
        ConnectionInterface $db,
        GettyLinkRepository $repository,
        AatService $aatService,
        TgnService $tgnService,
        UlanService $ulanService,
        ?LoggerInterface $logger = null
    ) {
        $this->db = $db;
        $this->repository = $repository;
        $this->aatService = $aatService;
        $this->tgnService = $tgnService;
        $this->ulanService = $ulanService;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Auto-link a single term to Getty vocabulary.
     *
     * @param int         $termId     AtoM term ID
     * @param string      $termName   Term display name
     * @param string      $vocabulary Target vocabulary (aat, tgn, ulan)
     * @param string|null $category   Category hint for AAT (materials, techniques, etc.)
     * @param float       $threshold  Minimum confidence for auto-confirmation
     *
     * @return GettyVocabularyLink|null Created/updated link or null if no match
     */
    public function linkTerm(
        int $termId,
        string $termName,
        string $vocabulary,
        ?string $category = null,
        float $threshold = 0.9
    ): ?GettyVocabularyLink {
        $this->logger->info('Attempting to link term to Getty', [
            'term_id' => $termId,
            'term_name' => $termName,
            'vocabulary' => $vocabulary,
            'category' => $category,
        ]);

        // Check if already linked
        $existingLink = $this->repository->findByTermId($termId);
        if ($existingLink && $existingLink->isConfirmed()) {
            $this->logger->debug('Term already has confirmed Getty link', [
                'term_id' => $termId,
                'getty_uri' => $existingLink->getGettyUri(),
            ]);

            return $existingLink;
        }

        // Attempt linking based on vocabulary
        $linkResult = match ($vocabulary) {
            'aat' => $this->aatService->linkToAat($termName, $category, 0.0),
            'tgn' => $this->tgnService->linkToTgn($termName),
            'ulan' => $this->ulanService->linkToUlan($termName),
            default => throw new \InvalidArgumentException("Unknown vocabulary: {$vocabulary}"),
        };

        if (empty($linkResult['candidates'])) {
            $this->logger->info('No Getty matches found for term', [
                'term_id' => $termId,
                'term_name' => $termName,
            ]);

            return null;
        }

        // Create link from best match
        $bestMatch = $linkResult['matched'] ? $linkResult[$vocabulary.'Term'] ?? $linkResult['aatTerm'] ?? $linkResult['tgnPlace'] ?? $linkResult['ulanAgent'] : $linkResult['candidates'][0];

        if (!$bestMatch) {
            $bestMatch = $linkResult['candidates'][0];
        }

        $link = new GettyVocabularyLink();
        $link->setTermId($termId)
            ->setVocabulary($vocabulary)
            ->setGettyUri($bestMatch['uri'])
            ->setGettyPrefLabel($bestMatch['prefLabel'] ?? null)
            ->setGettyScopeNote($bestMatch['scopeNote'] ?? null)
            ->setConfidence($linkResult['confidence']);

        // Auto-confirm if above threshold
        if ($linkResult['confidence'] >= $threshold) {
            $link->setStatus(GettyVocabularyLink::STATUS_CONFIRMED);
            $this->logger->info('Auto-confirmed Getty link (high confidence)', [
                'term_id' => $termId,
                'confidence' => $linkResult['confidence'],
            ]);
        } else {
            $link->setStatus(GettyVocabularyLink::STATUS_SUGGESTED);
            $this->logger->info('Getty link suggested (requires review)', [
                'term_id' => $termId,
                'confidence' => $linkResult['confidence'],
            ]);
        }

        return $this->repository->save($link);
    }

    /**
     * Batch link all terms in a taxonomy.
     *
     * @param int         $taxonomyId       AtoM taxonomy ID
     * @param string      $vocabulary       Target vocabulary
     * @param string|null $category         Category hint for AAT
     * @param float       $threshold        Auto-confirm threshold
     * @param int         $batchSize        Process in batches
     * @param callable|null $progressCallback Progress callback(int $processed, int $total)
     *
     * @return array Summary of linking results
     */
    public function linkTaxonomy(
        int $taxonomyId,
        string $vocabulary,
        ?string $category = null,
        float $threshold = 0.9,
        int $batchSize = 50,
        ?callable $progressCallback = null
    ): array {
        $summary = [
            'taxonomy_id' => $taxonomyId,
            'vocabulary' => $vocabulary,
            'total_terms' => 0,
            'processed' => 0,
            'linked' => 0,
            'confirmed' => 0,
            'suggested' => 0,
            'no_match' => 0,
            'errors' => 0,
            'skipped_existing' => 0,
        ];

        // Get all terms in taxonomy
        $terms = $this->db->table('term')
            ->join('term_i18n', 'term_i18n.id', '=', 'term.id')
            ->where('term.taxonomy_id', $taxonomyId)
            ->where('term_i18n.culture', 'en')
            ->select('term.id', 'term_i18n.name')
            ->orderBy('term_i18n.name')
            ->get();

        $summary['total_terms'] = count($terms);

        $this->logger->info('Starting batch Getty linking', [
            'taxonomy_id' => $taxonomyId,
            'total_terms' => $summary['total_terms'],
            'vocabulary' => $vocabulary,
        ]);

        foreach ($terms as $index => $term) {
            try {
                // Check if already linked
                $existingLink = $this->repository->findByTermId($term->id);
                if ($existingLink && $existingLink->isConfirmed()) {
                    ++$summary['skipped_existing'];
                    ++$summary['processed'];
                    continue;
                }

                $link = $this->linkTerm(
                    $term->id,
                    $term->name,
                    $vocabulary,
                    $category,
                    $threshold
                );

                if ($link) {
                    ++$summary['linked'];
                    if ($link->isConfirmed()) {
                        ++$summary['confirmed'];
                    } else {
                        ++$summary['suggested'];
                    }
                } else {
                    ++$summary['no_match'];
                }
            } catch (\Exception $e) {
                ++$summary['errors'];
                $this->logger->error('Error linking term', [
                    'term_id' => $term->id,
                    'error' => $e->getMessage(),
                ]);
            }

            ++$summary['processed'];

            // Progress callback
            if ($progressCallback && 0 === $summary['processed'] % $batchSize) {
                $progressCallback($summary['processed'], $summary['total_terms']);
            }

            // Rate limiting - be nice to Getty's servers
            if (0 === $summary['processed'] % 10) {
                usleep(100000); // 100ms pause every 10 requests
            }
        }

        $this->logger->info('Batch Getty linking complete', $summary);

        return $summary;
    }

    /**
     * Link all unlinked terms across museum taxonomies.
     *
     * @return array Summary by taxonomy
     */
    public function linkAllMuseumTerms(float $threshold = 0.9): array
    {
        $results = [];

        // Get taxonomy IDs from database
        $taxonomies = $this->db->table('taxonomy')
            ->join('taxonomy_i18n', 'taxonomy_i18n.id', '=', 'taxonomy.id')
            ->where('taxonomy_i18n.culture', 'en')
            ->whereIn('taxonomy_i18n.name', [
                'Museum Materials',
                'Museum Techniques',
                'Museum Object Types',
                'Styles and Periods',
            ])
            ->select('taxonomy.id', 'taxonomy_i18n.name')
            ->get();

        foreach ($taxonomies as $taxonomy) {
            $category = match ($taxonomy->name) {
                'Museum Materials' => 'materials',
                'Museum Techniques' => 'techniques',
                'Museum Object Types' => 'object_types',
                'Styles and Periods' => 'styles_periods',
                default => null,
            };

            $results[$taxonomy->name] = $this->linkTaxonomy(
                $taxonomy->id,
                'aat',
                $category,
                $threshold
            );
        }

        return $results;
    }

    /**
     * Confirm a suggested link.
     *
     * @param int $linkId Link ID
     * @param int $userId User confirming the link
     */
    public function confirmLink(int $linkId, int $userId): bool
    {
        $link = $this->repository->find($linkId);
        if (!$link) {
            return false;
        }

        $link->confirm($userId);
        $this->repository->save($link);

        $this->logger->info('Getty link confirmed', [
            'link_id' => $linkId,
            'user_id' => $userId,
            'term_id' => $link->getTermId(),
        ]);

        return true;
    }

    /**
     * Reject a suggested link.
     */
    public function rejectLink(int $linkId): bool
    {
        $link = $this->repository->find($linkId);
        if (!$link) {
            return false;
        }

        $link->reject();
        $this->repository->save($link);

        $this->logger->info('Getty link rejected', [
            'link_id' => $linkId,
            'term_id' => $link->getTermId(),
        ]);

        return true;
    }

    /**
     * Replace a link with a different Getty term.
     *
     * @param int    $linkId   Existing link ID
     * @param string $gettyUri New Getty URI
     * @param int    $userId   User making the change
     */
    public function replaceLink(int $linkId, string $gettyUri, int $userId): ?GettyVocabularyLink
    {
        $link = $this->repository->find($linkId);
        if (!$link) {
            return null;
        }

        // Get new term details
        $vocabulary = $link->getVocabulary();
        $service = match ($vocabulary) {
            'aat' => $this->aatService,
            'tgn' => $this->tgnService,
            'ulan' => $this->ulanService,
            default => null,
        };

        if (!$service) {
            return null;
        }

        // Extract ID from URI and get term
        if (preg_match('/\/(\d+)$/', $gettyUri, $matches)) {
            $gettyId = $matches[1];
            $method = match ($vocabulary) {
                'aat' => 'getTerm',
                'tgn' => 'getPlace',
                'ulan' => 'getAgent',
                default => 'getTerm',
            };

            $term = $service->$method($gettyId);

            if ($term) {
                $link->setGettyUri($gettyUri)
                    ->setGettyPrefLabel($term['prefLabel'] ?? null)
                    ->setGettyScopeNote($term['scopeNote'] ?? null)
                    ->setConfidence(1.0)
                    ->confirm($userId);

                $this->repository->save($link);

                $this->logger->info('Getty link replaced', [
                    'link_id' => $linkId,
                    'new_uri' => $gettyUri,
                    'user_id' => $userId,
                ]);

                return $link;
            }
        }

        return null;
    }

    /**
     * Get linking statistics.
     */
    public function getStatistics(): array
    {
        return [
            'by_status' => $this->repository->countByStatus(),
            'by_vocabulary' => $this->repository->countByVocabulary(),
        ];
    }

    /**
     * Get suggested links pending review.
     *
     * @return GettyVocabularyLink[]
     */
    public function getPendingReview(int $limit = 50): array
    {
        return $this->repository->findSuggested($limit);
    }

    /**
     * Export links as CSV.
     *
     * @param string|null $vocabulary Filter by vocabulary
     *
     * @return string CSV content
     */
    public function exportToCsv(?string $vocabulary = null): string
    {
        $query = $this->db->table('getty_vocabulary_link')
            ->join('term', 'term.id', '=', 'getty_vocabulary_link.term_id')
            ->join('term_i18n', 'term_i18n.id', '=', 'term.id')
            ->where('term_i18n.culture', 'en')
            ->where('getty_vocabulary_link.status', '!=', GettyVocabularyLink::STATUS_REJECTED);

        if ($vocabulary) {
            $query->where('getty_vocabulary_link.vocabulary', $vocabulary);
        }

        $links = $query->select(
            'term.id as term_id',
            'term_i18n.name as term_name',
            'getty_vocabulary_link.vocabulary',
            'getty_vocabulary_link.getty_uri',
            'getty_vocabulary_link.getty_id',
            'getty_vocabulary_link.getty_pref_label',
            'getty_vocabulary_link.status',
            'getty_vocabulary_link.confidence'
        )->get();

        $csv = "term_id,term_name,vocabulary,getty_uri,getty_id,getty_pref_label,status,confidence\n";

        foreach ($links as $link) {
            $csv .= sprintf(
                "%d,\"%s\",%s,%s,%s,\"%s\",%s,%.2f\n",
                $link->term_id,
                str_replace('"', '""', $link->term_name),
                $link->vocabulary,
                $link->getty_uri,
                $link->getty_id,
                str_replace('"', '""', $link->getty_pref_label ?? ''),
                $link->status,
                $link->confidence
            );
        }

        return $csv;
    }
}
