<?php

namespace AhgDiscovery\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Step 1: Query Expansion
 *
 * Parses natural language queries into structured search terms:
 * - Keyword extraction (tokenize, remove stop words)
 * - Date range detection (decades, centuries, specific dates)
 * - Synonym lookup from ahg_thesaurus_synonym
 * - Entity term identification (proper nouns → likely GPE/ORG/PERSON)
 */
class QueryExpander
{
    /**
     * English stop words to filter from keyword search.
     */
    private const STOP_WORDS = [
        'a', 'an', 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
        'of', 'with', 'by', 'from', 'is', 'was', 'are', 'were', 'be', 'been',
        'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would',
        'could', 'should', 'may', 'might', 'shall', 'can', 'this', 'that',
        'these', 'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they',
        'me', 'him', 'her', 'us', 'them', 'my', 'your', 'his', 'its', 'our',
        'their', 'what', 'which', 'who', 'whom', 'when', 'where', 'why', 'how',
        'all', 'each', 'every', 'both', 'few', 'more', 'most', 'other', 'some',
        'such', 'no', 'not', 'only', 'own', 'same', 'so', 'than', 'too', 'very',
        'just', 'about', 'above', 'after', 'again', 'against', 'any', 'because',
        'before', 'below', 'between', 'during', 'into', 'through', 'under',
        'until', 'up', 'down', 'out', 'off', 'over', 'then', 'once', 'here',
        'there', 'also', 'if', 'tell', 'show', 'find', 'get', 'give',
        'know', 'look', 'make', 'want', 'let', 'like',
        // Discovery-specific filler words
        'do', 'you', 'have', 'anything', 'about', 'related', 'regarding',
        'concerning', 'records', 'documents', 'materials', 'collections',
        'information', 'details',
    ];

    /**
     * Expand a natural language query into structured search terms.
     *
     * @param string $query Raw user query
     * @return array ExpandedQuery structure
     */
    public function expand(string $query): array
    {
        $original = trim($query);
        $normalized = mb_strtolower($original);

        // Step 1: Detect date ranges
        $dateRange = $this->extractDateRange($normalized);

        // Step 2: Extract phrases (quoted or multi-word proper nouns)
        $phrases = $this->extractPhrases($original);

        // Step 3: Tokenize and remove stop words
        $keywords = $this->extractKeywords($normalized, $dateRange);

        // Step 4: Identify entity terms (capitalized multi-word strings)
        $entityTerms = $this->identifyEntityTerms($original, $phrases);

        // Step 5: Look up synonyms from thesaurus
        $synonyms = $this->lookupSynonyms(array_merge($keywords, array_map('strtolower', $phrases)));

        return [
            'original'     => $original,
            'keywords'     => $keywords,
            'phrases'      => $phrases,
            'synonyms'     => $synonyms,
            'dateRange'    => $dateRange,
            'entityTerms'  => $entityTerms,
        ];
    }

    /**
     * Detect date ranges from query text.
     *
     * Handles: "1960s", "19th century", "January 1960", "1960-1969",
     *          "1960 to 1969", "1960", "before 1900", "after 1950"
     */
    private function extractDateRange(string $text): ?array
    {
        // Decade: "1960s"
        if (preg_match('/\b(\d{3})0s\b/', $text, $m)) {
            $decade = (int)($m[1] . '0');
            return ['start' => $decade, 'end' => $decade + 9, 'label' => $m[0]];
        }

        // Century: "19th century", "twentieth century"
        $centuryWords = [
            'first' => 1, 'second' => 2, 'third' => 3, 'fourth' => 4,
            'fifth' => 5, 'sixth' => 6, 'seventh' => 7, 'eighth' => 8,
            'ninth' => 9, 'tenth' => 10, 'eleventh' => 11, 'twelfth' => 12,
            'thirteenth' => 13, 'fourteenth' => 14, 'fifteenth' => 15,
            'sixteenth' => 16, 'seventeenth' => 17, 'eighteenth' => 18,
            'nineteenth' => 19, 'twentieth' => 20, 'twenty-first' => 21,
        ];
        if (preg_match('/\b(\d{1,2})(st|nd|rd|th)\s+century\b/i', $text, $m)) {
            $c = (int)$m[1];
            return ['start' => ($c - 1) * 100, 'end' => ($c * 100) - 1, 'label' => $m[0]];
        }
        foreach ($centuryWords as $word => $c) {
            if (stripos($text, "$word century") !== false) {
                return ['start' => ($c - 1) * 100, 'end' => ($c * 100) - 1, 'label' => "$word century"];
            }
        }

        // Explicit range: "1960-1969" or "1960 to 1969"
        if (preg_match('/\b(\d{4})\s*[-–—]\s*(\d{4})\b/', $text, $m) ||
            preg_match('/\b(\d{4})\s+to\s+(\d{4})\b/', $text, $m)) {
            return ['start' => (int)$m[1], 'end' => (int)$m[2], 'label' => $m[0]];
        }

        // Before/after: "before 1900", "after 1950"
        if (preg_match('/\bbefore\s+(\d{4})\b/', $text, $m)) {
            return ['start' => null, 'end' => (int)$m[1], 'label' => $m[0]];
        }
        if (preg_match('/\bafter\s+(\d{4})\b/', $text, $m)) {
            return ['start' => (int)$m[1], 'end' => null, 'label' => $m[0]];
        }

        // Standalone year: "1960" (only if 4 digits and plausible year)
        if (preg_match('/\b(1[0-9]{3}|20[0-2]\d)\b/', $text, $m)) {
            $year = (int)$m[1];
            return ['start' => $year, 'end' => $year, 'label' => (string)$year];
        }

        return null;
    }

    /**
     * Extract quoted phrases and multi-word proper noun phrases.
     */
    private function extractPhrases(string $text): array
    {
        $phrases = [];

        // Quoted phrases: "District Six"
        if (preg_match_all('/"([^"]+)"/', $text, $matches)) {
            $phrases = array_merge($phrases, $matches[1]);
        }

        // Multi-word proper nouns (2+ consecutive capitalized words)
        // e.g., "District Six", "Group Areas Act", "Anglo Boer War"
        if (preg_match_all('/\b([A-Z][a-z]+(?:\s+(?:[A-Z][a-z]+|of|the|and|for|in|de|van|von|du))*\s+[A-Z][a-z]+)\b/', $text, $matches)) {
            foreach ($matches[1] as $phrase) {
                // Must have at least 2 capitalized words
                $capCount = preg_match_all('/[A-Z][a-z]+/', $phrase);
                if ($capCount >= 2 && !in_array($phrase, $phrases)) {
                    $phrases[] = $phrase;
                }
            }
        }

        return array_values(array_unique($phrases));
    }

    /**
     * Tokenize query, remove stop words and date tokens.
     */
    private function extractKeywords(string $normalized, ?array $dateRange): array
    {
        // Remove date text so it doesn't pollute keywords
        $text = $normalized;
        if ($dateRange) {
            $text = str_ireplace($dateRange['label'], '', $text);
        }

        // Remove punctuation except hyphens within words
        $text = preg_replace('/[^\w\s-]/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', trim($text));

        $tokens = explode(' ', $text);
        $keywords = [];

        foreach ($tokens as $token) {
            $token = trim($token, '-');
            if (strlen($token) < 2) {
                continue;
            }
            if (in_array($token, self::STOP_WORDS)) {
                continue;
            }
            // Skip pure numbers (years already captured)
            if (is_numeric($token)) {
                continue;
            }
            $keywords[] = $token;
        }

        return array_values(array_unique($keywords));
    }

    /**
     * Identify likely entity terms from capitalized words/phrases.
     */
    private function identifyEntityTerms(string $original, array $phrases): array
    {
        $terms = [];

        // All detected phrases are potential entities
        foreach ($phrases as $phrase) {
            $terms[] = ['value' => $phrase, 'type' => null]; // type will be resolved from NER table
        }

        // Single capitalized words that aren't at sentence start
        $words = preg_split('/\s+/', $original);
        for ($i = 1; $i < count($words); $i++) {
            $word = trim($words[$i], '.,;:!?"\'()[]');
            if (strlen($word) > 2 && preg_match('/^[A-Z]/', $word) && !in_array(strtolower($word), self::STOP_WORDS)) {
                // Check it's not already part of a phrase
                $inPhrase = false;
                foreach ($phrases as $phrase) {
                    if (stripos($phrase, $word) !== false) {
                        $inPhrase = true;
                        break;
                    }
                }
                if (!$inPhrase) {
                    $terms[] = ['value' => $word, 'type' => null];
                }
            }
        }

        return $terms;
    }

    /**
     * Look up synonyms from ahg_thesaurus_synonym table.
     */
    private function lookupSynonyms(array $terms): array
    {
        if (empty($terms)) {
            return [];
        }

        try {
            // Check if thesaurus tables exist
            $exists = DB::select("SHOW TABLES LIKE 'ahg_thesaurus_synonym'");
            if (empty($exists)) {
                return [];
            }

            $synonyms = [];

            foreach ($terms as $term) {
                $term = trim($term);
                if (strlen($term) < 2) {
                    continue;
                }

                // Look up term_id from ahg_thesaurus_term
                $termRow = DB::table('ahg_thesaurus_term')
                    ->where('term', $term)
                    ->first();

                if (!$termRow) {
                    continue;
                }

                // Get synonyms for this term
                $syns = DB::table('ahg_thesaurus_synonym')
                    ->where('term_id', $termRow->id)
                    ->whereIn('relationship', ['synonym', 'use_for', 'related'])
                    ->orderByDesc('weight')
                    ->limit(5)
                    ->get();

                foreach ($syns as $syn) {
                    // Get the synonym term text
                    $synTerm = DB::table('ahg_thesaurus_term')
                        ->where('id', $syn->synonym_term_id)
                        ->value('term');

                    if ($synTerm && !in_array($synTerm, $synonyms) && !in_array($synTerm, $terms)) {
                        $synonyms[] = $synTerm;
                    }
                }

                // Also check bidirectional (where this term is the synonym)
                $reverseSyns = DB::table('ahg_thesaurus_synonym')
                    ->where('synonym_term_id', $termRow->id)
                    ->where('is_bidirectional', 1)
                    ->whereIn('relationship', ['synonym', 'use_for', 'related'])
                    ->orderByDesc('weight')
                    ->limit(5)
                    ->get();

                foreach ($reverseSyns as $syn) {
                    $synTerm = DB::table('ahg_thesaurus_term')
                        ->where('id', $syn->term_id)
                        ->value('term');

                    if ($synTerm && !in_array($synTerm, $synonyms) && !in_array($synTerm, $terms)) {
                        $synonyms[] = $synTerm;
                    }
                }
            }

            return $synonyms;
        } catch (\Exception $e) {
            // Thesaurus tables may not exist — degrade gracefully
            return [];
        }
    }
}
