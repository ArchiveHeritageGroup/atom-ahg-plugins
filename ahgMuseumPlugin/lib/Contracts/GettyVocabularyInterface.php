<?php

declare(strict_types=1);

namespace arMuseumMetadataPlugin\Contracts;

/**
 * Getty Vocabulary Interface.
 *
 * Defines contract for interacting with Getty Vocabularies (AAT, TGN, ULAN).
 * Implementations can use SPARQL, REST API, or local cache.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 *
 * @see https://www.getty.edu/research/tools/vocabularies/
 */
interface GettyVocabularyInterface
{
    /**
     * Search for terms in a vocabulary.
     *
     * @param string $query      Search query
     * @param string $vocabulary Vocabulary identifier (aat, tgn, ulan)
     * @param int    $limit      Maximum results to return
     *
     * @return array Array of matching terms with URIs
     */
    public function search(string $query, string $vocabulary, int $limit = 20): array;

    /**
     * Get term details by URI or ID.
     *
     * @param string $identifier Getty URI or numeric ID
     * @param string $vocabulary Vocabulary identifier
     *
     * @return array|null Term details or null if not found
     */
    public function getTerm(string $identifier, string $vocabulary): ?array;

    /**
     * Get broader (parent) terms for a concept.
     *
     * @param string $uri        Getty concept URI
     * @param string $vocabulary Vocabulary identifier
     *
     * @return array Array of broader terms
     */
    public function getBroaderTerms(string $uri, string $vocabulary): array;

    /**
     * Get narrower (child) terms for a concept.
     *
     * @param string $uri        Getty concept URI
     * @param string $vocabulary Vocabulary identifier
     *
     * @return array Array of narrower terms
     */
    public function getNarrowerTerms(string $uri, string $vocabulary): array;

    /**
     * Get related terms for a concept.
     *
     * @param string $uri        Getty concept URI
     * @param string $vocabulary Vocabulary identifier
     *
     * @return array Array of related terms
     */
    public function getRelatedTerms(string $uri, string $vocabulary): array;

    /**
     * Validate that a URI exists in the vocabulary.
     *
     * @param string $uri        Getty concept URI
     * @param string $vocabulary Vocabulary identifier
     *
     * @return bool True if URI is valid
     */
    public function validateUri(string $uri, string $vocabulary): bool;

    /**
     * Get preferred label for a term in specified language.
     *
     * @param string $uri      Getty concept URI
     * @param string $language ISO 639-1 language code (default: en)
     *
     * @return string|null Preferred label or null
     */
    public function getPreferredLabel(string $uri, string $language = 'en'): ?string;

    /**
     * Get all labels (preferred + alternate) for a term.
     *
     * @param string $uri      Getty concept URI
     * @param string $language ISO 639-1 language code (optional, all if null)
     *
     * @return array Array of labels with types
     */
    public function getAllLabels(string $uri, ?string $language = null): array;

    /**
     * Get scope note (definition) for a term.
     *
     * @param string $uri      Getty concept URI
     * @param string $language ISO 639-1 language code
     *
     * @return string|null Scope note or null
     */
    public function getScopeNote(string $uri, string $language = 'en'): ?string;
}
