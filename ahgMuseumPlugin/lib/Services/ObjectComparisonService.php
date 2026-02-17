<?php

declare(strict_types=1);

namespace arMuseumMetadataPlugin\Services;

use Illuminate\Database\ConnectionInterface;

/**
 * Object Comparison Service.
 *
 * Enables side-by-side comparison of museum objects for research,
 * attribution studies, and cataloguing assistance.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ObjectComparisonService
{
    /** Comparable field groups */
    public const FIELD_GROUPS = [
        'identification' => [
            'label' => 'Identification',
            'fields' => ['identifier', 'title', 'level_of_description'],
        ],
        'creation' => [
            'label' => 'Creation',
            'fields' => ['creator', 'creation_date', 'creation_place'],
        ],
        'physical' => [
            'label' => 'Physical Description',
            'fields' => ['object_type', 'materials', 'techniques', 'dimensions', 'inscriptions'],
        ],
        'style' => [
            'label' => 'Style & Classification',
            'fields' => ['style_period', 'school', 'culture', 'subject'],
        ],
        'provenance' => [
            'label' => 'Provenance',
            'fields' => ['provenance', 'acquisition_source', 'acquisition_date'],
        ],
        'condition' => [
            'label' => 'Condition',
            'fields' => ['condition_rating', 'condition_notes'],
        ],
        'valuation' => [
            'label' => 'Valuation',
            'fields' => ['insurance_value', 'valuation_date'],
        ],
    ];

    private ConnectionInterface $db;

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Compare two objects.
     *
     * @param int      $objectId1   First object ID
     * @param int      $objectId2   Second object ID
     * @param string[] $fieldGroups Groups to compare (null = all)
     *
     * @return array Comparison data
     */
    public function compare(int $objectId1, int $objectId2, ?array $fieldGroups = null): array
    {
        $object1 = $this->getObjectData($objectId1);
        $object2 = $this->getObjectData($objectId2);

        if (!$object1 || !$object2) {
            return ['error' => 'One or both objects not found'];
        }

        $groups = $fieldGroups ?? array_keys(self::FIELD_GROUPS);
        $comparison = [
            'object1' => [
                'id' => $objectId1,
                'identifier' => $object1['identifier'],
                'title' => $object1['title'],
                'thumbnail' => $object1['thumbnail'] ?? null,
            ],
            'object2' => [
                'id' => $objectId2,
                'identifier' => $object2['identifier'],
                'title' => $object2['title'],
                'thumbnail' => $object2['thumbnail'] ?? null,
            ],
            'fields' => [],
            'similarities' => [],
            'differences' => [],
            'similarity_score' => 0,
        ];

        $matchCount = 0;
        $totalFields = 0;

        foreach ($groups as $groupKey) {
            if (!isset(self::FIELD_GROUPS[$groupKey])) {
                continue;
            }

            $group = self::FIELD_GROUPS[$groupKey];
            $comparison['fields'][$groupKey] = [
                'label' => $group['label'],
                'comparisons' => [],
            ];

            foreach ($group['fields'] as $field) {
                $value1 = $object1[$field] ?? null;
                $value2 = $object2[$field] ?? null;

                $fieldComparison = [
                    'field' => $field,
                    'label' => $this->getFieldLabel($field),
                    'value1' => $value1,
                    'value2' => $value2,
                    'match' => $this->valuesMatch($value1, $value2),
                    'similarity' => $this->calculateSimilarity($value1, $value2),
                ];

                $comparison['fields'][$groupKey]['comparisons'][] = $fieldComparison;

                if ($value1 || $value2) {
                    ++$totalFields;
                    if ($fieldComparison['match']) {
                        ++$matchCount;
                        $comparison['similarities'][] = $field;
                    } else {
                        $comparison['differences'][] = $field;
                    }
                }
            }
        }

        // Calculate overall similarity
        $comparison['similarity_score'] = $totalFields > 0
            ? round(($matchCount / $totalFields) * 100, 1)
            : 0;

        return $comparison;
    }

    /**
     * Compare multiple objects.
     *
     * @param int[] $objectIds Object IDs to compare
     *
     * @return array Multi-object comparison
     */
    public function compareMultiple(array $objectIds): array
    {
        if (count($objectIds) < 2) {
            return ['error' => 'Need at least 2 objects to compare'];
        }

        $objects = [];
        foreach ($objectIds as $id) {
            $data = $this->getObjectData($id);
            if ($data) {
                $objects[$id] = $data;
            }
        }

        if (count($objects) < 2) {
            return ['error' => 'Could not load enough objects'];
        }

        $comparison = [
            'objects' => [],
            'field_groups' => [],
        ];

        // Build object headers
        foreach ($objects as $id => $obj) {
            $comparison['objects'][] = [
                'id' => $id,
                'identifier' => $obj['identifier'],
                'title' => $obj['title'],
                'thumbnail' => $obj['thumbnail'] ?? null,
            ];
        }

        // Build field comparison matrix
        foreach (self::FIELD_GROUPS as $groupKey => $group) {
            $comparison['field_groups'][$groupKey] = [
                'label' => $group['label'],
                'fields' => [],
            ];

            foreach ($group['fields'] as $field) {
                $fieldData = [
                    'field' => $field,
                    'label' => $this->getFieldLabel($field),
                    'values' => [],
                    'all_match' => true,
                ];

                $firstValue = null;
                foreach ($objects as $id => $obj) {
                    $value = $obj[$field] ?? null;
                    $fieldData['values'][$id] = $value;

                    if (null === $firstValue) {
                        $firstValue = $value;
                    } elseif (!$this->valuesMatch($firstValue, $value)) {
                        $fieldData['all_match'] = false;
                    }
                }

                $comparison['field_groups'][$groupKey]['fields'][] = $fieldData;
            }
        }

        return $comparison;
    }

    /**
     * Find similar objects.
     *
     * @param int   $objectId   Reference object ID
     * @param array $criteria   Fields to match on
     * @param int   $limit      Maximum results
     *
     * @return array Similar objects
     */
    public function findSimilar(int $objectId, array $criteria = [], int $limit = 10): array
    {
        $object = $this->getObjectData($objectId);

        if (!$object) {
            return [];
        }

        // Default criteria if none specified
        if (empty($criteria)) {
            $criteria = ['creator', 'object_type', 'materials', 'style_period'];
        }

        // Build query conditions
        $query = $this->db->table('information_object as io')
            ->join('information_object_i18n as ioi', function ($join) {
                $join->on('ioi.id', '=', 'io.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('io.id', '!=', $objectId)
            ->select('io.id', 'io.identifier', 'ioi.title');

        // Add conditions for each criterion
        foreach ($criteria as $field) {
            $value = $object[$field] ?? null;
            if ($value) {
                // This is simplified - real implementation would need
                // proper joins to the relevant tables
                switch ($field) {
                    case 'creator':
                        // Would join to actor relation
                        break;
                    case 'object_type':
                    case 'materials':
                    case 'techniques':
                        // Would join to term relations
                        break;
                }
            }
        }

        $results = $query->limit($limit)->get()->all();

        // Calculate similarity scores
        $scored = [];
        foreach ($results as $row) {
            $comparison = $this->compare($objectId, $row->id, ['creation', 'physical', 'style']);
            $scored[] = [
                'id' => $row->id,
                'identifier' => $row->identifier,
                'title' => $row->title,
                'similarity_score' => $comparison['similarity_score'] ?? 0,
                'matching_fields' => $comparison['similarities'] ?? [],
            ];
        }

        // Sort by similarity
        usort($scored, fn ($a, $b) => $b['similarity_score'] <=> $a['similarity_score']);

        return array_slice($scored, 0, $limit);
    }

    /**
     * Get comparison view data for rendering.
     *
     * @param int[] $objectIds Objects to compare
     *
     * @return array View-ready comparison data
     */
    public function getComparisonView(array $objectIds): array
    {
        $comparison = count($objectIds) === 2
            ? $this->compare($objectIds[0], $objectIds[1])
            : $this->compareMultiple($objectIds);

        // Add images for each object
        foreach ($objectIds as $i => $id) {
            $images = $this->getObjectImages($id);
            if (isset($comparison['objects'][$i])) {
                $comparison['objects'][$i]['images'] = $images;
            } elseif (isset($comparison['object'.($i + 1)])) {
                $comparison['object'.($i + 1)]['images'] = $images;
            }
        }

        return $comparison;
    }

    /**
     * Get object data for comparison.
     */
    private function getObjectData(int $objectId): ?array
    {
        $object = $this->db->table('information_object as io')
            ->join('information_object_i18n as ioi', function ($join) {
                $join->on('ioi.id', '=', 'io.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('io.id', $objectId)
            ->select(
                'io.id',
                'io.identifier',
                'io.level_of_description_id',
                'ioi.title',
                'ioi.scope_and_content',
                'ioi.physical_characteristics',
                'ioi.extent_and_medium'
            )
            ->first();

        if (!$object) {
            return null;
        }

        $data = (array) $object;

        // Get museum metadata if available
        $museumData = $this->db->table('museum_object')
            ->where('information_object_id', $objectId)
            ->first();

        if ($museumData) {
            $data = array_merge($data, (array) $museumData);
        }

        // Get creator(s)
        $creators = $this->db->table('event as e')
            ->join('actor as a', 'a.id', '=', 'e.actor_id')
            ->join('actor_i18n as ai', function ($join) {
                $join->on('ai.id', '=', 'a.id')
                    ->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('e.information_object_id', $objectId)
            ->where('e.type_id', 111) // Creation event type
            ->select('ai.authorized_form_of_name')
            ->get()
            ->pluck('authorized_form_of_name')
            ->all();

        $data['creator'] = implode('; ', $creators);

        // Get thumbnail
        $thumbnail = $this->db->table('digital_object')
            ->where('information_object_id', $objectId)
            ->orderBy('sequence')
            ->first();

        if ($thumbnail) {
            $data['thumbnail'] = '/uploads/'.$thumbnail->path;
        }

        return $data;
    }

    /**
     * Get all images for an object.
     */
    private function getObjectImages(int $objectId): array
    {
        return $this->db->table('digital_object')
            ->where('information_object_id', $objectId)
            ->orderBy('sequence')
            ->select('id', 'name', 'path', 'mime_type')
            ->get()
            ->map(fn ($img) => [
                'id' => $img->id,
                'name' => $img->name,
                'url' => '/uploads/'.$img->path,
                'mime_type' => $img->mime_type,
            ])
            ->all();
    }

    /**
     * Check if two values match.
     */
    private function valuesMatch($value1, $value2): bool
    {
        if (null === $value1 && null === $value2) {
            return true;
        }

        if (null === $value1 || null === $value2) {
            return false;
        }

        // Normalize strings for comparison
        $v1 = is_string($value1) ? strtolower(trim($value1)) : $value1;
        $v2 = is_string($value2) ? strtolower(trim($value2)) : $value2;

        return $v1 === $v2;
    }

    /**
     * Calculate similarity between two values (0-100).
     */
    private function calculateSimilarity($value1, $value2): float
    {
        if ($this->valuesMatch($value1, $value2)) {
            return 100.0;
        }

        if (null === $value1 || null === $value2) {
            return 0.0;
        }

        if (!is_string($value1) || !is_string($value2)) {
            return 0.0;
        }

        // Use Levenshtein for string similarity
        $v1 = strtolower(trim($value1));
        $v2 = strtolower(trim($value2));

        $maxLen = max(strlen($v1), strlen($v2));
        if (0 === $maxLen) {
            return 100.0;
        }

        $distance = levenshtein($v1, $v2);
        $similarity = (1 - ($distance / $maxLen)) * 100;

        return round(max(0, $similarity), 1);
    }

    /**
     * Get human-readable field label.
     */
    private function getFieldLabel(string $field): string
    {
        $labels = [
            'identifier' => 'Accession Number',
            'title' => 'Title',
            'level_of_description' => 'Level',
            'creator' => 'Creator/Artist',
            'creation_date' => 'Date Created',
            'creation_place' => 'Place Created',
            'object_type' => 'Object Type',
            'materials' => 'Materials',
            'techniques' => 'Techniques',
            'dimensions' => 'Dimensions',
            'inscriptions' => 'Inscriptions',
            'style_period' => 'Style/Period',
            'school' => 'School',
            'culture' => 'Culture',
            'subject' => 'Subject',
            'provenance' => 'Provenance',
            'acquisition_source' => 'Acquisition Source',
            'acquisition_date' => 'Acquisition Date',
            'condition_rating' => 'Condition',
            'condition_notes' => 'Condition Notes',
            'insurance_value' => 'Insurance Value',
            'valuation_date' => 'Valuation Date',
        ];

        return $labels[$field] ?? ucwords(str_replace('_', ' ', $field));
    }

    /**
     * Get field groups for UI.
     */
    public function getFieldGroups(): array
    {
        return self::FIELD_GROUPS;
    }
}
