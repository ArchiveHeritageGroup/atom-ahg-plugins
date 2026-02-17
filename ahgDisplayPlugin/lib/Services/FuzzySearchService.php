<?php

namespace AhgDisplay\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * FuzzySearchService - Typo-tolerant search correction for GLAM Browse.
 *
 * Combines Levenshtein distance, SOUNDEX, and Metaphone to suggest corrections
 * for misspelled search queries. Vocabulary is loaded from facet cache, thesaurus
 * terms, taxonomy terms, and creator names.
 */
class FuzzySearchService
{
    private array $vocabulary = [];
    private array $soundexIndex = [];
    private array $metaphoneIndex = [];
    private bool $loaded = false;

    public function __construct()
    {
        $this->loadVocabulary();
    }

    /**
     * Load vocabulary from multiple sources for fuzzy matching.
     */
    public function loadVocabulary(): void
    {
        if ($this->loaded) {
            return;
        }

        // Source 1: display_facet_cache (616+ terms)
        try {
            $facets = DB::table('display_facet_cache')
                ->whereNotNull('term_name')
                ->where('term_name', '!=', '')
                ->pluck('term_name')
                ->toArray();
            foreach ($facets as $term) {
                $this->addToVocabulary($term);
            }
        } catch (\Exception $e) {
            // table may not exist
        }

        // Source 2: ahg_thesaurus_term (2,946 terms, try/catch for missing plugin)
        try {
            $thesaurus = DB::table('ahg_thesaurus_term')
                ->whereNotNull('term')
                ->where('term', '!=', '')
                ->pluck('term')
                ->toArray();
            foreach ($thesaurus as $term) {
                $this->addToVocabulary($term);
            }
        } catch (\Exception $e) {
            // ahgSemanticSearchPlugin not installed
        }

        // Source 3: term_i18n for taxonomy 35 (subjects), 42 (places), 78 (genres)
        try {
            $terms = DB::table('term_i18n as ti')
                ->join('term as t', 'ti.id', '=', 't.id')
                ->where('ti.culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
                ->whereIn('t.taxonomy_id', [35, 42, 78])
                ->whereNotNull('ti.name')
                ->where('ti.name', '!=', '')
                ->pluck('ti.name')
                ->toArray();
            foreach ($terms as $term) {
                $this->addToVocabulary($term);
            }
        } catch (\Exception $e) {
            // skip
        }

        // Source 4: actor_i18n for creators
        try {
            $actors = DB::table('actor_i18n')
                ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
                ->whereNotNull('authorized_form_of_name')
                ->where('authorized_form_of_name', '!=', '')
                ->pluck('authorized_form_of_name')
                ->toArray();
            foreach ($actors as $name) {
                $this->addToVocabulary($name);
            }
        } catch (\Exception $e) {
            // skip
        }

        // Source 5: information_object_i18n titles (top 2000 by id desc for performance)
        try {
            $titles = DB::table('information_object_i18n')
                ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
                ->whereNotNull('title')
                ->where('title', '!=', '')
                ->orderBy('id', 'desc')
                ->limit(2000)
                ->pluck('title')
                ->toArray();
            foreach ($titles as $title) {
                // Extract individual words from titles for matching
                $words = preg_split('/\s+/', $title, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($words as $word) {
                    $clean = preg_replace('/[^a-zA-Z0-9]/', '', $word);
                    if (strlen($clean) >= 3) {
                        $this->addToVocabulary($clean);
                    }
                }
            }
        } catch (\Exception $e) {
            // skip
        }

        $this->buildPhoneticIndexes();
        $this->loaded = true;
    }

    /**
     * Add a term to the vocabulary.
     */
    private function addToVocabulary(string $term): void
    {
        // Add the full term
        $normalized = mb_strtolower(trim($term));
        if ($normalized !== '' && !isset($this->vocabulary[$normalized])) {
            $this->vocabulary[$normalized] = $term;
        }

        // Also add individual words from multi-word terms
        $words = preg_split('/\s+/', $term, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($words as $word) {
            $clean = preg_replace('/[^a-zA-Z0-9\'-]/', '', $word);
            $normWord = mb_strtolower($clean);
            if (strlen($normWord) >= 3 && !isset($this->vocabulary[$normWord])) {
                $this->vocabulary[$normWord] = $clean;
            }
        }
    }

    /**
     * Build SOUNDEX and Metaphone indexes from vocabulary.
     */
    private function buildPhoneticIndexes(): void
    {
        foreach ($this->vocabulary as $normalized => $original) {
            // Only index single words for phonetic matching
            if (strpos($normalized, ' ') !== false) {
                continue;
            }

            $sx = @soundex($normalized);
            if ($sx && $sx !== '0000') {
                $this->soundexIndex[$sx][] = $normalized;
            }

            $mp = @metaphone($normalized);
            if ($mp) {
                $this->metaphoneIndex[$mp][] = $normalized;
            }
        }
    }

    /**
     * Correct a search query using Levenshtein distance and phonetic matching.
     *
     * @return array{original: string, corrected: ?string, suggestion: ?string, confidence: float, corrections: array, method: ?string}
     */
    public function correctQuery(string $query): array
    {
        $result = [
            'original' => $query,
            'corrected' => null,
            'suggestion' => null,
            'confidence' => 0.0,
            'corrections' => [],
            'method' => null,
        ];

        if (empty($this->vocabulary)) {
            return $result;
        }

        $words = preg_split('/\s+/', trim($query), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($words)) {
            return $result;
        }

        $correctedWords = [];
        $anyCorrected = false;
        $totalConfidence = 0.0;
        $correctionCount = 0;
        $methods = [];

        foreach ($words as $word) {
            $normalized = mb_strtolower($word);

            // Skip short words (1-2 chars) and numbers
            if (strlen($normalized) <= 2 || is_numeric($normalized)) {
                $correctedWords[] = $word;
                continue;
            }

            // If the word exists in vocabulary exactly, no correction needed
            if (isset($this->vocabulary[$normalized])) {
                $correctedWords[] = $word;
                $totalConfidence += 1.0;
                $correctionCount++;
                continue;
            }

            // Try Levenshtein first
            $match = $this->findLevenshteinMatch($normalized);
            if ($match) {
                $correctedWords[] = $match['suggestion'];
                $anyCorrected = true;
                $totalConfidence += $match['confidence'];
                $correctionCount++;
                $methods[] = 'levenshtein';
                $result['corrections'][] = [
                    'original' => $word,
                    'suggestion' => $match['suggestion'],
                    'confidence' => $match['confidence'],
                    'method' => 'levenshtein',
                    'distance' => $match['distance'],
                ];
                continue;
            }

            // Try phonetic match (SOUNDEX then Metaphone)
            $match = $this->findPhoneticMatch($normalized);
            if ($match) {
                $correctedWords[] = $match['suggestion'];
                $anyCorrected = true;
                $totalConfidence += $match['confidence'];
                $correctionCount++;
                $methods[] = $match['method'];
                $result['corrections'][] = [
                    'original' => $word,
                    'suggestion' => $match['suggestion'],
                    'confidence' => $match['confidence'],
                    'method' => $match['method'],
                ];
                continue;
            }

            // No correction found, keep original
            $correctedWords[] = $word;
            $correctionCount++;
        }

        if ($anyCorrected) {
            $corrected = implode(' ', $correctedWords);
            $result['corrected'] = $corrected;
            $result['suggestion'] = $corrected;
            $result['confidence'] = $correctionCount > 0
                ? round($totalConfidence / $correctionCount, 2)
                : 0.0;
            // Use most common method
            if (!empty($methods)) {
                $result['method'] = array_count_values($methods);
                arsort($result['method']);
                $result['method'] = array_key_first($result['method']);
            }
        }

        return $result;
    }

    /**
     * Find best Levenshtein match for a word.
     *
     * @return array{suggestion: string, confidence: float, distance: int}|null
     */
    private function findLevenshteinMatch(string $word): ?array
    {
        $wordLen = strlen($word);
        $maxDistance = $wordLen <= 5 ? 2 : 3;
        $bestMatch = null;
        $bestDistance = $maxDistance + 1;

        foreach ($this->vocabulary as $normalized => $original) {
            // Skip multi-word entries for per-word matching
            if (strpos($normalized, ' ') !== false) {
                continue;
            }

            // Quick length check to skip impossible matches
            $vocabLen = strlen($normalized);
            if (abs($vocabLen - $wordLen) > $maxDistance) {
                continue;
            }

            $distance = levenshtein($word, $normalized);
            if ($distance > 0 && $distance < $bestDistance) {
                $bestDistance = $distance;
                $bestMatch = $original;
            }
        }

        if ($bestMatch === null || $bestDistance > $maxDistance) {
            return null;
        }

        // Confidence: inversely proportional to distance relative to word length
        $confidence = round(1.0 - ($bestDistance / max($wordLen, 1)), 2);

        return [
            'suggestion' => $bestMatch,
            'confidence' => max(0.1, $confidence),
            'distance' => $bestDistance,
        ];
    }

    /**
     * Find phonetic match using SOUNDEX first, then Metaphone fallback.
     *
     * @return array{suggestion: string, confidence: float, method: string}|null
     */
    private function findPhoneticMatch(string $word): ?array
    {
        // Try SOUNDEX
        $sx = @soundex($word);
        if ($sx && $sx !== '0000' && isset($this->soundexIndex[$sx])) {
            $candidates = $this->soundexIndex[$sx];
            $best = $this->pickBestCandidate($word, $candidates);
            if ($best && $best !== $word) {
                return [
                    'suggestion' => $this->vocabulary[$best] ?? $best,
                    'confidence' => 0.6,
                    'method' => 'soundex',
                ];
            }
        }

        // Try Metaphone
        $mp = @metaphone($word);
        if ($mp && isset($this->metaphoneIndex[$mp])) {
            $candidates = $this->metaphoneIndex[$mp];
            $best = $this->pickBestCandidate($word, $candidates);
            if ($best && $best !== $word) {
                return [
                    'suggestion' => $this->vocabulary[$best] ?? $best,
                    'confidence' => 0.5,
                    'method' => 'metaphone',
                ];
            }
        }

        return null;
    }

    /**
     * Pick the best candidate from a list based on Levenshtein distance.
     */
    private function pickBestCandidate(string $word, array $candidates): ?string
    {
        $best = null;
        $bestDist = PHP_INT_MAX;

        foreach ($candidates as $candidate) {
            if ($candidate === $word) {
                continue;
            }
            $dist = levenshtein($word, $candidate);
            if ($dist < $bestDist) {
                $bestDist = $dist;
                $best = $candidate;
            }
        }

        return $best;
    }

    /**
     * Get vocabulary size for diagnostics.
     */
    public function getVocabularySize(): int
    {
        return count($this->vocabulary);
    }
}
