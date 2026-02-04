<?php

declare(strict_types=1);

namespace ahgLibraryPlugin\Service;

use ahgLibraryPlugin\Repository\SubjectAuthorityRepository;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * AI-powered subject suggestion service.
 *
 * Provides intelligent subject heading suggestions based on:
 * - Text matching via FULLTEXT search (40% weight)
 * - NER entity cross-reference from entity_subject_map (30% weight)
 * - Usage frequency bonus (30% weight)
 */
class SubjectSuggestionService
{
    protected SubjectAuthorityRepository $repository;

    /**
     * Scoring weights for suggestion ranking.
     */
    protected const WEIGHT_TEXT_MATCH = 0.40;
    protected const WEIGHT_NER_MATCH = 0.30;
    protected const WEIGHT_USAGE = 0.30;

    /**
     * Maximum suggestions to return.
     */
    protected const MAX_SUGGESTIONS = 15;

    public function __construct(?SubjectAuthorityRepository $repository = null)
    {
        $this->repository = $repository ?? new SubjectAuthorityRepository();
    }

    /**
     * Generate subject suggestions based on input data.
     *
     * @param array $input Input data with keys:
     *   - title: (string) Item title
     *   - description: (string) Item description/summary
     *   - ner_entities: (array) NER entities [{type, value}, ...]
     *   - existing_subjects: (array) Already assigned subjects to exclude
     * @return array Ranked suggestions with confidence scores
     */
    public function suggest(array $input): array
    {
        $title = $input['title'] ?? '';
        $description = $input['description'] ?? '';
        $nerEntities = $input['ner_entities'] ?? [];
        $existingSubjects = array_map(
            fn($s) => $this->normalizeHeading(is_array($s) ? ($s['heading'] ?? '') : $s),
            $input['existing_subjects'] ?? []
        );

        // Combine title and description for text matching
        $searchText = trim($title . ' ' . $description);

        // Score accumulators keyed by authority ID
        $scores = [];
        $authorities = [];

        // 1. Text-based matching (40% weight)
        if (!empty($searchText)) {
            $textMatches = $this->getTextMatches($searchText);
            foreach ($textMatches as $match) {
                $id = $match['id'];
                $scores[$id] = ($scores[$id] ?? 0) + ($match['relevance'] * self::WEIGHT_TEXT_MATCH);
                $authorities[$id] = $match;
            }
        }

        // 2. NER entity cross-reference (30% weight)
        if (!empty($nerEntities)) {
            $nerMatches = $this->getNerMatches($nerEntities);
            foreach ($nerMatches as $match) {
                $id = $match['id'];
                // NER relevance = confidence * normalized co-occurrence
                $nerScore = $match['confidence'] * min(1, $match['co_occurrence_count'] / 10);
                $scores[$id] = ($scores[$id] ?? 0) + ($nerScore * self::WEIGHT_NER_MATCH);
                if (!isset($authorities[$id])) {
                    $authorities[$id] = $match;
                }
                $authorities[$id]['ner_source'] = true;
            }
        }

        // 3. Usage frequency bonus (30% weight)
        if (!empty($authorities)) {
            $maxUsage = max(array_column($authorities, 'usage_count'));
            if ($maxUsage > 0) {
                foreach ($authorities as $id => $auth) {
                    $usageScore = ($auth['usage_count'] ?? 0) / $maxUsage;
                    $scores[$id] = ($scores[$id] ?? 0) + ($usageScore * self::WEIGHT_USAGE);
                }
            }
        }

        // Filter out existing subjects
        foreach ($existingSubjects as $existing) {
            foreach ($authorities as $id => $auth) {
                if ($this->normalizeHeading($auth['heading']) === $existing) {
                    unset($scores[$id]);
                    unset($authorities[$id]);
                }
            }
        }

        // Sort by score descending
        arsort($scores);

        // Build result with top suggestions
        $suggestions = [];
        $count = 0;
        foreach ($scores as $id => $score) {
            if ($count >= self::MAX_SUGGESTIONS) {
                break;
            }

            $auth = $authorities[$id];
            $suggestions[] = [
                'id' => $id,
                'heading' => $auth['heading'],
                'heading_type' => $auth['heading_type'],
                'source' => $auth['source'],
                'lcsh_id' => $auth['lcsh_id'] ?? null,
                'lcsh_uri' => $auth['lcsh_uri'] ?? null,
                'usage_count' => $auth['usage_count'] ?? 0,
                'score' => round($score, 4),
                'ner_source' => $auth['ner_source'] ?? false,
            ];
            $count++;
        }

        return $suggestions;
    }

    /**
     * Get text-based matches using FULLTEXT search.
     *
     * @param string $text Text to search
     * @return array Matching authorities with relevance scores
     */
    protected function getTextMatches(string $text): array
    {
        // Extract key terms (remove common words, limit to significant terms)
        $terms = $this->extractKeyTerms($text);
        if (empty($terms)) {
            return [];
        }

        $searchQuery = implode(' ', $terms);

        // Use FULLTEXT search with relevance scoring
        $results = DB::table('library_subject_authority')
            ->selectRaw('*, MATCH(heading) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance', [$searchQuery])
            ->whereRaw('MATCH(heading) AGAINST(? IN NATURAL LANGUAGE MODE)', [$searchQuery])
            ->orderBy('relevance', 'desc')
            ->limit(50)
            ->get();

        // Normalize relevance scores
        $maxRelevance = $results->max('relevance') ?: 1;

        return $results->map(function ($row) use ($maxRelevance) {
            return [
                'id' => (int) $row->id,
                'heading' => $row->heading,
                'heading_type' => $row->heading_type,
                'source' => $row->source,
                'lcsh_id' => $row->lcsh_id,
                'lcsh_uri' => $row->lcsh_uri,
                'usage_count' => (int) $row->usage_count,
                'relevance' => $row->relevance / $maxRelevance,
            ];
        })->toArray();
    }

    /**
     * Get matches from NER entity cross-reference.
     *
     * @param array $entities NER entities [{type, value}, ...]
     * @return array Matching authorities with confidence scores
     */
    protected function getNerMatches(array $entities): array
    {
        $matches = [];

        foreach ($entities as $entity) {
            $type = $entity['type'] ?? '';
            $value = $entity['value'] ?? '';

            if (empty($type) || empty($value)) {
                continue;
            }

            $entityMatches = $this->repository->getSubjectsForEntity($type, $value);
            foreach ($entityMatches as $match) {
                $id = $match['id'];
                // If already seen, keep highest confidence
                if (!isset($matches[$id]) || $match['confidence'] > $matches[$id]['confidence']) {
                    $matches[$id] = $match;
                }
            }
        }

        return array_values($matches);
    }

    /**
     * Extract key terms from text for search.
     *
     * @param string $text Input text
     * @return array Key terms
     */
    protected function extractKeyTerms(string $text): array
    {
        // Common stopwords to filter
        $stopwords = [
            'a', 'an', 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
            'of', 'with', 'by', 'from', 'as', 'is', 'was', 'are', 'were', 'been',
            'be', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would',
            'could', 'should', 'may', 'might', 'must', 'shall', 'can', 'need',
            'this', 'that', 'these', 'those', 'it', 'its', 'they', 'their',
            'which', 'who', 'whom', 'what', 'where', 'when', 'why', 'how',
            'all', 'each', 'every', 'both', 'few', 'more', 'most', 'other',
            'some', 'such', 'no', 'not', 'only', 'own', 'same', 'so', 'than',
            'too', 'very', 'just', 'about', 'also', 'into', 'over', 'after',
        ];

        // Lowercase and extract words
        $text = mb_strtolower($text, 'UTF-8');
        preg_match_all('/\b[a-z]{3,}\b/', $text, $matches);

        $words = array_unique($matches[0]);

        // Filter stopwords
        $words = array_filter($words, fn($w) => !in_array($w, $stopwords));

        // Limit to most frequent/significant terms
        $wordCounts = array_count_values($matches[0]);
        arsort($wordCounts);

        $significant = [];
        foreach ($wordCounts as $word => $count) {
            if (in_array($word, $stopwords)) {
                continue;
            }
            $significant[] = $word;
            if (count($significant) >= 10) {
                break;
            }
        }

        return $significant;
    }

    /**
     * Normalize a heading for comparison.
     *
     * @param string $heading Heading to normalize
     * @return string Normalized heading
     */
    protected function normalizeHeading(string $heading): string
    {
        $normalized = mb_strtolower($heading, 'UTF-8');
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', '', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        return trim($normalized);
    }

    /**
     * Get suggestions for a specific information object.
     *
     * This is a convenience method that fetches NER entities from the database.
     *
     * @param int $objectId Information object ID
     * @param string $title Item title
     * @param string $description Item description
     * @param array $existingSubjects Already assigned subjects
     * @return array Suggestions
     */
    public function suggestForObject(
        int $objectId,
        string $title,
        string $description = '',
        array $existingSubjects = []
    ): array {
        // Fetch NER entities from ahg_ner_entity table
        $nerEntities = $this->fetchNerEntities($objectId);

        return $this->suggest([
            'title' => $title,
            'description' => $description,
            'ner_entities' => $nerEntities,
            'existing_subjects' => $existingSubjects,
        ]);
    }

    /**
     * Fetch NER entities for an information object.
     *
     * @param int $objectId Information object ID
     * @return array NER entities
     */
    protected function fetchNerEntities(int $objectId): array
    {
        // Check if table exists
        try {
            $exists = DB::table('information_schema.tables')
                ->where('table_schema', DB::raw('DATABASE()'))
                ->where('table_name', 'ahg_ner_entity')
                ->exists();

            if (!$exists) {
                return [];
            }

            return DB::table('ahg_ner_entity')
                ->where('information_object_id', $objectId)
                ->select('entity_type as type', 'entity_value as value')
                ->get()
                ->toArray();

        } catch (\Exception $e) {
            // Table doesn't exist or query failed
            return [];
        }
    }
}
