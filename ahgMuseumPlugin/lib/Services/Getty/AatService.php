<?php

declare(strict_types=1);

namespace arMuseumMetadataPlugin\Services\Getty;

use arMuseumMetadataPlugin\Contracts\GettyVocabularyInterface;
use Psr\Log\LoggerInterface;

/**
 * AAT (Art & Architecture Thesaurus) Service.
 *
 * Specialized service for searching and retrieving terms from the
 * Getty Art & Architecture Thesaurus. Provides convenience methods
 * for common AAT categories used in museum cataloguing.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 *
 * @see http://vocab.getty.edu/aat/
 */
class AatService
{
    /** AAT Facet/Hierarchy IDs for common categories */
    public const FACETS = [
        // Objects
        'objects' => '300264092',
        'furnishings' => '300037335',
        'tools_equipment' => '300022238',
        'containers' => '300045611',
        'visual_works' => '300191086',
        'information_forms' => '300026059',

        // Materials
        'materials' => '300010358',
        'inorganic_materials' => '300010359',
        'organic_materials' => '300010360',
        'additive_materials' => '300014695',

        // Activities/Processes
        'processes_techniques' => '300053001',
        'forming_processes' => '300053946',
        'additive_processes' => '300053634',
        'subtractive_processes' => '300053645',
        'surface_processes' => '300053653',
        'image_making_processes' => '300053669',

        // Styles and Periods
        'styles_periods' => '300264088',
        'western_styles' => '300108957',
        'african_styles' => '300015940',
        'asian_styles' => '300018493',

        // Agents
        'people' => '300024978',
        'organizations' => '300025948',

        // Physical Attributes
        'physical_attributes' => '300055126',
        'conditions_states' => '300048715',
        'colors' => '300080438',
        'shapes' => '300056273',
        'sizes' => '300055119',
    ];

    /** Common material terms with AAT IDs */
    public const COMMON_MATERIALS = [
        'oil_paint' => '300015050',
        'watercolor' => '300015045',
        'acrylic_paint' => '300015058',
        'tempera' => '300015062',
        'bronze' => '300010957',
        'marble' => '300011443',
        'wood' => '300011914',
        'canvas' => '300014078',
        'paper' => '300014109',
        'ceramic' => '300010669',
        'glass' => '300010797',
        'gold' => '300011021',
        'silver' => '300011029',
        'iron' => '300011002',
        'steel' => '300133751',
        'textile' => '300231565',
        'leather' => '300011845',
        'ivory' => '300011857',
        'bone' => '300011798',
        'stone' => '300011176',
    ];

    /** Common technique terms with AAT IDs */
    public const COMMON_TECHNIQUES = [
        'painting' => '300054216',
        'drawing' => '300054196',
        'sculpture' => '300047090',
        'carving' => '300053149',
        'casting' => '300053104',
        'engraving' => '300053225',
        'etching' => '300053241',
        'lithography' => '300053279',
        'photography' => '300054225',
        'weaving' => '300053642',
        'embroidery' => '300264024',
        'printing' => '300054198',
        'gilding' => '300053789',
        'enameling' => '300053765',
        'glazing' => '300053832',
        'forging' => '300053816',
        'welding' => '300053869',
        'molding' => '300264179',
        'assemblage' => '300047158',
        'collage' => '300033963',
    ];

    /** Common object types with AAT IDs */
    public const COMMON_OBJECT_TYPES = [
        'painting' => '300033618',
        'drawing' => '300033973',
        'print' => '300041273',
        'photograph' => '300046300',
        'sculpture' => '300047090',
        'relief' => '300047230',
        'statue' => '300047600',
        'bust' => '300047457',
        'vase' => '300132254',
        'bowl' => '300045614',
        'vessel' => '300193015',
        'textile' => '300231565',
        'tapestry' => '300205002',
        'carpet' => '300185512',
        'furniture' => '300037680',
        'chair' => '300037772',
        'table' => '300039548',
        'jewelry' => '300209286',
        'coin' => '300037222',
        'medal' => '300046025',
        'book' => '300028051',
        'manuscript' => '300028569',
        'map' => '300028094',
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
     * Search AAT for terms.
     *
     * @param string   $query Search query
     * @param int      $limit Maximum results
     * @param string[] $facets Limit to specific facets (optional)
     *
     * @return array Search results
     */
    public function search(string $query, int $limit = 20, array $facets = []): array
    {
        return $this->sparql->search($query, 'aat', $limit);
    }

    /**
     * Search for materials in AAT.
     */
    public function searchMaterials(string $query, int $limit = 20): array
    {
        $results = $this->sparql->search($query, 'aat', $limit * 2);

        // Filter to materials facet
        return array_filter($results, function ($term) {
            $hierarchy = $term['hierarchy'] ?? '';

            return str_contains($hierarchy, 'Materials')
                || str_contains($hierarchy, 'materials');
        });
    }

    /**
     * Search for techniques in AAT.
     */
    public function searchTechniques(string $query, int $limit = 20): array
    {
        $results = $this->sparql->search($query, 'aat', $limit * 2);

        // Filter to processes/techniques
        return array_filter($results, function ($term) {
            $hierarchy = $term['hierarchy'] ?? '';

            return str_contains($hierarchy, 'Processes')
                || str_contains($hierarchy, 'Techniques')
                || str_contains($hierarchy, 'processes');
        });
    }

    /**
     * Search for object types in AAT.
     */
    public function searchObjectTypes(string $query, int $limit = 20): array
    {
        $results = $this->sparql->search($query, 'aat', $limit * 2);

        // Filter to objects facet
        return array_filter($results, function ($term) {
            $hierarchy = $term['hierarchy'] ?? '';

            return str_contains($hierarchy, 'Objects')
                || str_contains($hierarchy, 'Visual Works')
                || str_contains($hierarchy, 'Furnishings');
        });
    }

    /**
     * Search for styles and periods in AAT.
     */
    public function searchStylesPeriods(string $query, int $limit = 20): array
    {
        $results = $this->sparql->search($query, 'aat', $limit * 2);

        // Filter to styles/periods
        return array_filter($results, function ($term) {
            $hierarchy = $term['hierarchy'] ?? '';

            return str_contains($hierarchy, 'Styles')
                || str_contains($hierarchy, 'Periods')
                || str_contains($hierarchy, 'styles');
        });
    }

    /**
     * Get term by AAT ID.
     *
     * @param string $id AAT numeric ID (e.g., '300015050')
     *
     * @return array|null Term details
     */
    public function getTerm(string $id): ?array
    {
        return $this->sparql->getTerm($id, 'aat');
    }

    /**
     * Get full URI for AAT ID.
     *
     * @param string $id AAT numeric ID
     *
     * @return string Full URI
     */
    public function getUri(string $id): string
    {
        return 'http://vocab.getty.edu/aat/'.$id;
    }

    /**
     * Get common material terms with AAT URIs.
     *
     * @return array Array of material terms with URIs
     */
    public function getCommonMaterials(): array
    {
        $materials = [];

        foreach (self::COMMON_MATERIALS as $name => $id) {
            $materials[] = [
                'id' => $id,
                'uri' => $this->getUri($id),
                'label' => ucwords(str_replace('_', ' ', $name)),
                'key' => $name,
            ];
        }

        return $materials;
    }

    /**
     * Get common technique terms with AAT URIs.
     *
     * @return array Array of technique terms with URIs
     */
    public function getCommonTechniques(): array
    {
        $techniques = [];

        foreach (self::COMMON_TECHNIQUES as $name => $id) {
            $techniques[] = [
                'id' => $id,
                'uri' => $this->getUri($id),
                'label' => ucwords(str_replace('_', ' ', $name)),
                'key' => $name,
            ];
        }

        return $techniques;
    }

    /**
     * Get common object types with AAT URIs.
     *
     * @return array Array of object type terms with URIs
     */
    public function getCommonObjectTypes(): array
    {
        $types = [];

        foreach (self::COMMON_OBJECT_TYPES as $name => $id) {
            $types[] = [
                'id' => $id,
                'uri' => $this->getUri($id),
                'label' => ucwords(str_replace('_', ' ', $name)),
                'key' => $name,
            ];
        }

        return $types;
    }

    /**
     * Validate that an ID exists in AAT.
     */
    public function validateId(string $id): bool
    {
        return $this->sparql->validateUri($id, 'aat');
    }

    /**
     * Get children of a facet/hierarchy node.
     *
     * @param string $facetId Facet ID from FACETS constant
     *
     * @return array Child terms
     */
    public function getFacetChildren(string $facetId): array
    {
        $id = self::FACETS[$facetId] ?? $facetId;

        return $this->sparql->getNarrowerTerms($this->getUri($id), 'aat');
    }

    /**
     * Get hierarchy path for a term.
     */
    public function getHierarchy(string $id): array
    {
        return $this->sparql->getBroaderTerms($this->getUri($id), 'aat');
    }

    /**
     * Find best match for a term string.
     *
     * Searches AAT and returns the best matching term based on
     * label similarity and hierarchy relevance.
     *
     * @param string      $term   Term to match
     * @param string|null $facet  Preferred facet (materials, techniques, etc.)
     *
     * @return array|null Best matching term or null
     */
    public function findBestMatch(string $term, ?string $facet = null): ?array
    {
        // Search for the term
        $results = $this->search($term, 10);

        if (empty($results)) {
            return null;
        }

        // If facet specified, prefer results from that facet
        if ($facet && isset(self::FACETS[$facet])) {
            foreach ($results as $result) {
                $hierarchy = $result['hierarchy'] ?? '';
                if (str_contains(strtolower($hierarchy), $facet)) {
                    return $result;
                }
            }
        }

        // Return first result (best match by relevance)
        return $results[0];
    }

    /**
     * Link local term to AAT.
     *
     * Attempts to find an AAT match for a local term and returns
     * the linking data.
     *
     * @param string      $localTerm   Local term text
     * @param string|null $category    Category hint (materials, techniques, etc.)
     * @param float       $threshold   Minimum confidence for auto-linking (0-1)
     *
     * @return array Linking result with match confidence
     */
    public function linkToAat(string $localTerm, ?string $category = null, float $threshold = 0.8): array
    {
        $result = [
            'localTerm' => $localTerm,
            'category' => $category,
            'matched' => false,
            'confidence' => 0.0,
            'aatTerm' => null,
            'candidates' => [],
        ];

        // Search based on category
        $searchMethod = match ($category) {
            'materials' => 'searchMaterials',
            'techniques' => 'searchTechniques',
            'object_types' => 'searchObjectTypes',
            'styles_periods' => 'searchStylesPeriods',
            default => 'search',
        };

        $candidates = $this->$searchMethod($localTerm, 5);
        $result['candidates'] = array_slice($candidates, 0, 5);

        if (empty($candidates)) {
            return $result;
        }

        // Calculate confidence based on label similarity
        $bestMatch = null;
        $bestScore = 0;

        foreach ($candidates as $candidate) {
            $label = strtolower($candidate['prefLabel'] ?? '');
            $search = strtolower($localTerm);

            // Exact match
            if ($label === $search) {
                $score = 1.0;
            }
            // Contains exact term
            elseif (str_contains($label, $search) || str_contains($search, $label)) {
                $score = 0.9;
            }
            // Levenshtein similarity
            else {
                $distance = levenshtein($label, $search);
                $maxLen = max(strlen($label), strlen($search));
                $score = $maxLen > 0 ? 1 - ($distance / $maxLen) : 0;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $candidate;
            }
        }

        $result['confidence'] = round($bestScore, 2);

        if ($bestMatch && $bestScore >= $threshold) {
            $result['matched'] = true;
            $result['aatTerm'] = $bestMatch;
        }

        return $result;
    }
}
