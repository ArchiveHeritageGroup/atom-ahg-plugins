<?php

declare(strict_types=1);

namespace arMuseumMetadataPlugin\Services\Getty;

use arMuseumMetadataPlugin\Contracts\GettyVocabularyInterface;
use Psr\Log\LoggerInterface;

/**
 * TGN (Getty Thesaurus of Geographic Names) Service.
 *
 * Specialized service for searching and retrieving place names from the
 * Getty Thesaurus of Geographic Names. Used for recording creation places,
 * discovery locations, and other geographic metadata in museum cataloguing.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 *
 * @see http://vocab.getty.edu/tgn/
 */
class TgnService
{
    /** TGN Place Types */
    public const PLACE_TYPES = [
        'inhabited_place' => '83002',      // cities, towns, villages
        'nation' => '81010',               // countries
        'first_level_subdivision' => '83011', // states, provinces
        'second_level_subdivision' => '83012', // counties, districts
        'historical_region' => '83015',
        'continent' => '81011',
        'general_region' => '83013',
        'deserted_settlement' => '83003',
        'island' => '83014',
        'mountain' => '83026',
        'river' => '83029',
        'sea' => '83039',
        'lake' => '83037',
        'archaeological_site' => '83016',
        'historical_settlement' => '83004',
    ];

    /** Major world regions for browsing */
    public const WORLD_REGIONS = [
        'africa' => '7001242',
        'americas' => '1000001',
        'north_america' => '1000001',
        'south_america' => '1000003',
        'asia' => '1000004',
        'europe' => '1000003',
        'oceania' => '1000005',
        'middle_east' => '7001204',
    ];

    /** South African provinces (for AHG context) */
    public const SOUTH_AFRICA_PROVINCES = [
        'eastern_cape' => '7001623',
        'free_state' => '1105578',
        'gauteng' => '7001624',
        'kwazulu_natal' => '7001625',
        'limpopo' => '7001626',
        'mpumalanga' => '7001627',
        'northern_cape' => '7001628',
        'north_west' => '7001629',
        'western_cape' => '7001630',
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
     * Search TGN for places.
     *
     * @param string $query Search query
     * @param int    $limit Maximum results
     *
     * @return array Search results
     */
    public function search(string $query, int $limit = 20): array
    {
        return $this->sparql->search($query, 'tgn', $limit);
    }

    /**
     * Search for places within a specific country.
     *
     * @param string $query   Place name to search
     * @param string $country Country TGN ID or name
     * @param int    $limit   Maximum results
     *
     * @return array Filtered results
     */
    public function searchInCountry(string $query, string $country, int $limit = 20): array
    {
        $results = $this->search("{$query} {$country}", $limit * 2);

        // Filter by country in hierarchy
        return array_values(array_filter($results, function ($place) use ($country) {
            $hierarchy = strtolower($place['hierarchy'] ?? '');

            return str_contains($hierarchy, strtolower($country));
        }));
    }

    /**
     * Search for places in South Africa.
     */
    public function searchSouthAfrica(string $query, int $limit = 20): array
    {
        return $this->searchInCountry($query, 'South Africa', $limit);
    }

    /**
     * Get place by TGN ID.
     *
     * @param string $id TGN numeric ID
     *
     * @return array|null Place details
     */
    public function getPlace(string $id): ?array
    {
        $place = $this->sparql->getTerm($id, 'tgn');

        if ($place) {
            // Enhance with coordinates if available
            $coords = $this->getCoordinates($id);
            if ($coords) {
                $place['latitude'] = $coords['latitude'];
                $place['longitude'] = $coords['longitude'];
            }
        }

        return $place;
    }

    /**
     * Get coordinates for a TGN place.
     *
     * @param string $id TGN ID
     *
     * @return array|null Coordinates with latitude/longitude
     */
    public function getCoordinates(string $id): ?array
    {
        $uri = $this->getUri($id);

        // Query for coordinates using TGN-specific properties
        $sparql = <<<SPARQL
PREFIX schema: <http://schema.org/>
PREFIX wgs84: <http://www.w3.org/2003/01/geo/wgs84_pos#>
PREFIX gvp: <http://vocab.getty.edu/ontology#>

SELECT ?lat ?long
FROM <http://vocab.getty.edu/dataset/tgn>
WHERE {
    <{$uri}-place> wgs84:lat ?lat ;
                   wgs84:long ?long .
}
LIMIT 1
SPARQL;

        // Execute via raw SPARQL (would need direct access to sparql service)
        // For now, return null - coordinates would be fetched from -place URI
        return null;
    }

    /**
     * Get full URI for TGN ID.
     */
    public function getUri(string $id): string
    {
        return 'http://vocab.getty.edu/tgn/'.$id;
    }

    /**
     * Get places within a region (children).
     *
     * @param string $regionId TGN ID of parent region
     *
     * @return array Child places
     */
    public function getPlacesInRegion(string $regionId): array
    {
        return $this->sparql->getNarrowerTerms($this->getUri($regionId), 'tgn');
    }

    /**
     * Get parent regions for a place.
     */
    public function getParentRegions(string $placeId): array
    {
        return $this->sparql->getBroaderTerms($this->getUri($placeId), 'tgn');
    }

    /**
     * Get South African provinces as structured data.
     */
    public function getSouthAfricanProvinces(): array
    {
        $provinces = [];

        foreach (self::SOUTH_AFRICA_PROVINCES as $name => $id) {
            $provinces[] = [
                'id' => $id,
                'uri' => $this->getUri($id),
                'label' => ucwords(str_replace('_', ' ', $name)),
                'key' => $name,
            ];
        }

        return $provinces;
    }

    /**
     * Validate that a TGN ID exists.
     */
    public function validateId(string $id): bool
    {
        return $this->sparql->validateUri($id, 'tgn');
    }

    /**
     * Find best match for a place name.
     *
     * @param string      $placeName Place name to match
     * @param string|null $country   Country context for disambiguation
     *
     * @return array|null Best matching place
     */
    public function findBestMatch(string $placeName, ?string $country = null): ?array
    {
        if ($country) {
            $results = $this->searchInCountry($placeName, $country, 5);
        } else {
            $results = $this->search($placeName, 5);
        }

        if (empty($results)) {
            return null;
        }

        // Prefer exact matches
        foreach ($results as $result) {
            $label = strtolower($result['prefLabel'] ?? '');
            if ($label === strtolower($placeName)) {
                return $result;
            }
        }

        return $results[0];
    }

    /**
     * Link local place to TGN.
     *
     * @param string      $localPlace Local place name
     * @param string|null $country    Country context
     * @param string|null $province   Province/state context
     *
     * @return array Linking result
     */
    public function linkToTgn(string $localPlace, ?string $country = null, ?string $province = null): array
    {
        $result = [
            'localPlace' => $localPlace,
            'country' => $country,
            'province' => $province,
            'matched' => false,
            'confidence' => 0.0,
            'tgnPlace' => null,
            'candidates' => [],
        ];

        // Build search query with context
        $searchQuery = $localPlace;
        if ($province) {
            $searchQuery .= ' '.$province;
        }
        if ($country) {
            $searchQuery .= ' '.$country;
        }

        $candidates = $this->search($searchQuery, 10);
        $result['candidates'] = array_slice($candidates, 0, 5);

        if (empty($candidates)) {
            return $result;
        }

        // Find best match considering hierarchy
        $bestMatch = null;
        $bestScore = 0;

        foreach ($candidates as $candidate) {
            $label = strtolower($candidate['prefLabel'] ?? '');
            $search = strtolower($localPlace);
            $hierarchy = strtolower($candidate['hierarchy'] ?? '');

            // Base score on label match
            if ($label === $search) {
                $score = 0.8;
            } elseif (str_contains($label, $search)) {
                $score = 0.6;
            } else {
                $distance = levenshtein($label, $search);
                $maxLen = max(strlen($label), strlen($search));
                $score = $maxLen > 0 ? (1 - ($distance / $maxLen)) * 0.5 : 0;
            }

            // Boost if country matches
            if ($country && str_contains($hierarchy, strtolower($country))) {
                $score += 0.15;
            }

            // Boost if province matches
            if ($province && str_contains($hierarchy, strtolower($province))) {
                $score += 0.1;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $candidate;
            }
        }

        $result['confidence'] = round(min($bestScore, 1.0), 2);

        if ($bestMatch && $bestScore >= 0.7) {
            $result['matched'] = true;
            $result['tgnPlace'] = $bestMatch;
        }

        return $result;
    }
}
