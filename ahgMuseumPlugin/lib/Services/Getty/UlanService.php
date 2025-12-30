<?php

declare(strict_types=1);

namespace arMuseumMetadataPlugin\Services\Getty;

use arMuseumMetadataPlugin\Contracts\GettyVocabularyInterface;
use Psr\Log\LoggerInterface;

/**
 * ULAN (Union List of Artist Names) Service.
 *
 * Specialized service for searching and retrieving artist/creator names from the
 * Getty Union List of Artist Names. Used for authority control of creators,
 * manufacturers, and other agents in museum cataloguing.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 *
 * @see http://vocab.getty.edu/ulan/
 */
class UlanService
{
    /** ULAN Agent Types */
    public const AGENT_TYPES = [
        'person' => '500000001',
        'corporate_body' => '500000002',
        'family' => '500000003',
    ];

    /** Common roles */
    public const ROLES = [
        'artist' => '500025103',
        'painter' => '500025108',
        'sculptor' => '500025171',
        'architect' => '500025111',
        'photographer' => '500025686',
        'printmaker' => '500025164',
        'engraver' => '500025129',
        'ceramist' => '500025117',
        'goldsmith' => '500025138',
        'silversmith' => '500025174',
        'jeweler' => '500025143',
        'furniture_maker' => '500025136',
        'textile_designer' => '500025186',
        'graphic_designer' => '500025139',
        'illustrator' => '500025141',
        'draftsman' => '500025123',
        'glass_artist' => '500025135',
        'metalworker' => '500025148',
        'woodworker' => '500025189',
        'weaver' => '500025188',
        'potter' => '500025159',
        'calligrapher' => '500025115',
    ];

    /** Gender values (as used in ULAN) */
    public const GENDERS = [
        'male' => 'male',
        'female' => 'female',
        'unknown' => 'unknown',
        'not_applicable' => 'N/A', // For corporate bodies
    ];

    private GettyVocabularyInterface $sparql;
    private ?LoggerInterface $logger;

    public function __construct(
        GettyVocabularyInterface $sparqlService,
        ?LoggerInterface $logger = null
    ) {
        $this->sparql = $sparqlService;
        $this->logger = $logger;
    }

    /**
     * Search ULAN for artists/agents.
     *
     * @param string $query Search query (name)
     * @param int    $limit Maximum results
     *
     * @return array Search results
     */
    public function search(string $query, int $limit = 20): array
    {
        return $this->sparql->search($query, 'ulan', $limit);
    }

    /**
     * Search for artists by role.
     *
     * @param string $query   Name search
     * @param string $role    Role key from ROLES constant
     * @param int    $limit   Maximum results
     *
     * @return array Filtered results
     */
    public function searchByRole(string $query, string $role, int $limit = 20): array
    {
        $results = $this->search($query, $limit * 2);

        // Note: Role filtering would require enhanced SPARQL query
        // For now, return all results - could be filtered post-query
        return array_slice($results, 0, $limit);
    }

    /**
     * Search for artists by nationality.
     *
     * @param string $query       Name search
     * @param string $nationality Nationality (e.g., 'Dutch', 'French')
     * @param int    $limit       Maximum results
     */
    public function searchByNationality(string $query, string $nationality, int $limit = 20): array
    {
        // Combine search terms for better results
        $combinedQuery = "{$query} {$nationality}";

        return $this->search($combinedQuery, $limit);
    }

    /**
     * Get artist/agent by ULAN ID.
     *
     * @param string $id ULAN numeric ID
     *
     * @return array|null Agent details
     */
    public function getAgent(string $id): ?array
    {
        $agent = $this->sparql->getTerm($id, 'ulan');

        if ($agent) {
            // Enhance with biographical data
            $bio = $this->getBiographicalData($id);
            if ($bio) {
                $agent = array_merge($agent, $bio);
            }
        }

        return $agent;
    }

    /**
     * Get biographical data for an agent.
     *
     * @param string $id ULAN ID
     *
     * @return array|null Biographical data
     */
    public function getBiographicalData(string $id): ?array
    {
        $uri = $this->getUri($id);

        // This would require a specialized SPARQL query for ULAN bio data
        // Including: birth/death dates, places, nationalities, roles

        return [
            'birthDate' => null,
            'deathDate' => null,
            'birthPlace' => null,
            'deathPlace' => null,
            'nationality' => null,
            'roles' => [],
            'gender' => null,
        ];
    }

    /**
     * Get full URI for ULAN ID.
     */
    public function getUri(string $id): string
    {
        return 'http://vocab.getty.edu/ulan/'.$id;
    }

    /**
     * Get common roles with ULAN IDs.
     */
    public function getCommonRoles(): array
    {
        $roles = [];

        foreach (self::ROLES as $name => $id) {
            $roles[] = [
                'id' => $id,
                'uri' => $this->getUri($id),
                'label' => ucwords(str_replace('_', ' ', $name)),
                'key' => $name,
            ];
        }

        return $roles;
    }

    /**
     * Search for corporate bodies only.
     */
    public function searchCorporateBodies(string $query, int $limit = 20): array
    {
        // Would require specialized SPARQL query filtering by agent type
        return $this->search($query, $limit);
    }

    /**
     * Search for persons only.
     */
    public function searchPersons(string $query, int $limit = 20): array
    {
        // Would require specialized SPARQL query filtering by agent type
        return $this->search($query, $limit);
    }

    /**
     * Validate that a ULAN ID exists.
     */
    public function validateId(string $id): bool
    {
        return $this->sparql->validateUri($id, 'ulan');
    }

    /**
     * Get related artists (associates, teachers, students, etc.)
     *
     * @param string $id ULAN ID
     *
     * @return array Related agents with relationship types
     */
    public function getRelatedAgents(string $id): array
    {
        return $this->sparql->getRelatedTerms($this->getUri($id), 'ulan');
    }

    /**
     * Find best match for an artist name.
     *
     * @param string      $name        Artist name to match
     * @param string|null $nationality Nationality hint
     * @param string|null $birthYear   Birth year hint
     *
     * @return array|null Best matching agent
     */
    public function findBestMatch(
        string $name,
        ?string $nationality = null,
        ?string $birthYear = null
    ): ?array {
        // Build search query
        $query = $name;
        if ($nationality) {
            $query .= ' '.$nationality;
        }

        $results = $this->search($query, 10);

        if (empty($results)) {
            return null;
        }

        // For now, return first result
        // Could be enhanced to score by nationality match, date overlap, etc.
        return $results[0];
    }

    /**
     * Link local authority to ULAN.
     *
     * @param string      $localName    Local authority name
     * @param string|null $role         Role hint
     * @param string|null $nationality  Nationality hint
     * @param string|null $activeYears  Active period hint (e.g., "1850-1920")
     *
     * @return array Linking result
     */
    public function linkToUlan(
        string $localName,
        ?string $role = null,
        ?string $nationality = null,
        ?string $activeYears = null
    ): array {
        $result = [
            'localName' => $localName,
            'role' => $role,
            'nationality' => $nationality,
            'activeYears' => $activeYears,
            'matched' => false,
            'confidence' => 0.0,
            'ulanAgent' => null,
            'candidates' => [],
        ];

        // Build contextual search
        $searchQuery = $localName;
        if ($nationality) {
            $searchQuery .= ' '.$nationality;
        }
        if ($role) {
            $searchQuery .= ' '.$role;
        }

        $candidates = $this->search($searchQuery, 10);
        $result['candidates'] = array_slice($candidates, 0, 5);

        if (empty($candidates)) {
            return $result;
        }

        // Score candidates
        $bestMatch = null;
        $bestScore = 0;

        foreach ($candidates as $candidate) {
            $label = strtolower($candidate['prefLabel'] ?? '');
            $searchName = strtolower($localName);

            // Name matching
            if ($label === $searchName) {
                $score = 0.7;
            } elseif (str_contains($label, $searchName) || str_contains($searchName, $label)) {
                $score = 0.5;
            } else {
                // Check inverted name format (Last, First vs First Last)
                $nameParts = explode(' ', $searchName);
                if (count($nameParts) >= 2) {
                    $invertedName = $nameParts[count($nameParts) - 1].', '
                        .implode(' ', array_slice($nameParts, 0, -1));
                    if (str_contains($label, strtolower($invertedName))) {
                        $score = 0.6;
                    } else {
                        $distance = levenshtein($label, $searchName);
                        $maxLen = max(strlen($label), strlen($searchName));
                        $score = $maxLen > 0 ? (1 - ($distance / $maxLen)) * 0.4 : 0;
                    }
                } else {
                    $distance = levenshtein($label, $searchName);
                    $maxLen = max(strlen($label), strlen($searchName));
                    $score = $maxLen > 0 ? (1 - ($distance / $maxLen)) * 0.4 : 0;
                }
            }

            // Boost based on context
            $scopeNote = strtolower($candidate['scopeNote'] ?? '');

            if ($nationality && str_contains($scopeNote, strtolower($nationality))) {
                $score += 0.15;
            }

            if ($role && str_contains($scopeNote, strtolower($role))) {
                $score += 0.1;
            }

            if ($activeYears && preg_match('/\d{4}/', $activeYears, $matches)) {
                if (str_contains($scopeNote, $matches[0])) {
                    $score += 0.1;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $candidate;
            }
        }

        $result['confidence'] = round(min($bestScore, 1.0), 2);

        if ($bestMatch && $bestScore >= 0.6) {
            $result['matched'] = true;
            $result['ulanAgent'] = $bestMatch;
        }

        return $result;
    }

    /**
     * Suggest ULAN matches for AtoM actor record.
     *
     * @param array $actorData AtoM actor data with name, dates, etc.
     *
     * @return array Suggested ULAN matches
     */
    public function suggestForActor(array $actorData): array
    {
        $name = $actorData['authorized_form_of_name']
            ?? $actorData['name']
            ?? '';

        if (empty($name)) {
            return [];
        }

        // Extract any dates
        $dates = null;
        if (isset($actorData['dates_of_existence'])) {
            $dates = $actorData['dates_of_existence'];
        }

        // Extract function/occupation as role hint
        $role = null;
        if (isset($actorData['functions'])) {
            $role = $actorData['functions'];
        }

        $linkResult = $this->linkToUlan($name, $role, null, $dates);

        return $linkResult['candidates'];
    }
}
