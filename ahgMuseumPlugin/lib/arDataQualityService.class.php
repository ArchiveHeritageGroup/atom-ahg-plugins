<?php
/**
 * Data Quality Service
 * 
 * Analyzes collection records for completeness and data quality issues.
 * Uses Laravel Illuminate Database via atom-framework.
 * 
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgMuseumPlugin
 */

use Illuminate\Database\Capsule\Manager as DB;

class arDataQualityService
{
    // Quality thresholds
    const THRESHOLD_EXCELLENT = 90;
    const THRESHOLD_GOOD = 70;
    const THRESHOLD_FAIR = 50;
    const THRESHOLD_POOR = 30;

    // Field categories for analysis
    const CATEGORY_IDENTITY = 'identity';
    const CATEGORY_DESCRIPTION = 'description';
    const CATEGORY_CONTEXT = 'context';
    const CATEGORY_ACCESS = 'access';
    const CATEGORY_DIGITAL = 'digital';

    /**
     * Initialize database connection
     */
    protected static function initDatabase()
    {
        if (\AtomExtensions\Database\DatabaseBootstrap::getCapsule() === null) {
            \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
        }
    }

    /**
     * Field definitions with weights for completeness scoring
     */
    protected static $fieldDefinitions = [
        'identifier' => [
            'label' => 'Reference Code/Identifier',
            'category' => self::CATEGORY_IDENTITY,
            'weight' => 10,
            'required' => true,
            'column' => 'io.identifier'
        ],
        'title' => [
            'label' => 'Title',
            'category' => self::CATEGORY_IDENTITY,
            'weight' => 10,
            'required' => true,
            'column' => 'i18n.title'
        ],
        'levelOfDescription' => [
            'label' => 'Level of Description',
            'category' => self::CATEGORY_IDENTITY,
            'weight' => 5,
            'required' => true,
            'column' => 'io.level_of_description_id'
        ],
        'scopeAndContent' => [
            'label' => 'Scope and Content',
            'category' => self::CATEGORY_DESCRIPTION,
            'weight' => 8,
            'required' => false,
            'column' => 'i18n.scope_and_content',
            'minLength' => 50
        ],
        'extentAndMedium' => [
            'label' => 'Extent/Dimensions',
            'category' => self::CATEGORY_DESCRIPTION,
            'weight' => 6,
            'required' => false,
            'column' => 'i18n.extent_and_medium'
        ],
        'repository' => [
            'label' => 'Repository',
            'category' => self::CATEGORY_CONTEXT,
            'weight' => 5,
            'required' => true,
            'column' => 'io.repository_id'
        ],
        'accessConditions' => [
            'label' => 'Access Conditions',
            'category' => self::CATEGORY_ACCESS,
            'weight' => 4,
            'required' => false,
            'column' => 'i18n.access_conditions'
        ],
    ];

    /**
     * Analyze single record
     */
    public static function analyzeRecord($record)
    {
        self::initDatabase();

        $results = [
            'id' => $record->id,
            'slug' => $record->slug,
            'title' => $record->title ?: 'Untitled',
            'fields' => [],
            'score' => 0,
            'maxScore' => 0,
            'missingRequired' => [],
            'missingRecommended' => [],
            'issues' => [],
            'categoryScores' => []
        ];

        $categoryScores = [];

        foreach (self::$fieldDefinitions as $fieldName => $fieldDef) {
            $value = self::getFieldValueFromRecord($record, $fieldName);
            $hasValue = self::hasValue($value, $fieldDef);
            
            $fieldResult = [
                'label' => $fieldDef['label'],
                'category' => $fieldDef['category'],
                'hasValue' => $hasValue,
                'weight' => $fieldDef['weight'],
                'required' => $fieldDef['required']
            ];

            $results['maxScore'] += $fieldDef['weight'];

            if ($hasValue) {
                $results['score'] += $fieldDef['weight'];
                
                $issues = self::checkFieldQuality($fieldName, $value, $fieldDef);
                if (!empty($issues)) {
                    $results['issues'] = array_merge($results['issues'], $issues);
                }
            } else {
                if ($fieldDef['required']) {
                    $results['missingRequired'][] = $fieldDef['label'];
                } else {
                    $results['missingRecommended'][] = $fieldDef['label'];
                }
            }

            $results['fields'][$fieldName] = $fieldResult;

            if (!isset($categoryScores[$fieldDef['category']])) {
                $categoryScores[$fieldDef['category']] = ['score' => 0, 'max' => 0];
            }
            $categoryScores[$fieldDef['category']]['max'] += $fieldDef['weight'];
            if ($hasValue) {
                $categoryScores[$fieldDef['category']]['score'] += $fieldDef['weight'];
            }
        }

        // Check for creators (relation)
        $creatorCount = DB::table('event')
            ->where('object_id', $record->id)
            ->whereNotNull('actor_id')
            ->count();
        
        $hasCreator = $creatorCount > 0;
        $results['fields']['creators'] = [
            'label' => 'Creator',
            'category' => self::CATEGORY_CONTEXT,
            'hasValue' => $hasCreator,
            'weight' => 8,
            'required' => false
        ];
        $results['maxScore'] += 8;
        if ($hasCreator) {
            $results['score'] += 8;
        } else {
            $results['missingRecommended'][] = 'Creator';
        }
        if (!isset($categoryScores[self::CATEGORY_CONTEXT])) {
            $categoryScores[self::CATEGORY_CONTEXT] = ['score' => 0, 'max' => 0];
        }
        $categoryScores[self::CATEGORY_CONTEXT]['max'] += 8;
        if ($hasCreator) {
            $categoryScores[self::CATEGORY_CONTEXT]['score'] += 8;
        }

        // Check for dates (relation)
        $dateCount = DB::table('event')
            ->where('object_id', $record->id)
            ->where(function($q) {
                $q->whereNotNull('start_date')
                  ->orWhereNotNull('end_date');
            })
            ->count();
        
        $hasDate = $dateCount > 0;
        $results['fields']['eventDates'] = [
            'label' => 'Date(s)',
            'category' => self::CATEGORY_CONTEXT,
            'hasValue' => $hasDate,
            'weight' => 7,
            'required' => false
        ];
        $results['maxScore'] += 7;
        if ($hasDate) {
            $results['score'] += 7;
        } else {
            $results['missingRecommended'][] = 'Date(s)';
        }
        $categoryScores[self::CATEGORY_CONTEXT]['max'] += 7;
        if ($hasDate) {
            $categoryScores[self::CATEGORY_CONTEXT]['score'] += 7;
        }

        // Check for digital objects
        $doCount = DB::table('digital_object')
            ->where('object_id', $record->id)
            ->count();
        
        $hasDigital = $doCount > 0;
        $results['fields']['digitalObjects'] = [
            'label' => 'Digital Object(s)',
            'category' => self::CATEGORY_DIGITAL,
            'hasValue' => $hasDigital,
            'weight' => 6,
            'required' => false
        ];
        $results['maxScore'] += 6;
        if ($hasDigital) {
            $results['score'] += 6;
        } else {
            $results['missingRecommended'][] = 'Digital Object(s)';
        }
        if (!isset($categoryScores[self::CATEGORY_DIGITAL])) {
            $categoryScores[self::CATEGORY_DIGITAL] = ['score' => 0, 'max' => 0];
        }
        $categoryScores[self::CATEGORY_DIGITAL]['max'] += 6;
        if ($hasDigital) {
            $categoryScores[self::CATEGORY_DIGITAL]['score'] += 6;
        }

        // Check for subject access points
        $subjectCount = DB::table('object_term_relation')
            ->join('term', 'object_term_relation.term_id', '=', 'term.id')
            ->where('object_term_relation.object_id', $record->id)
            ->where('term.taxonomy_id', 35) // Subject taxonomy
            ->count();
        
        $hasSubjects = $subjectCount > 0;
        $results['fields']['subjectAccessPoints'] = [
            'label' => 'Subject Access Points',
            'category' => self::CATEGORY_ACCESS,
            'hasValue' => $hasSubjects,
            'weight' => 5,
            'required' => false
        ];
        $results['maxScore'] += 5;
        if ($hasSubjects) {
            $results['score'] += 5;
        } else {
            $results['missingRecommended'][] = 'Subject Access Points';
        }
        if (!isset($categoryScores[self::CATEGORY_ACCESS])) {
            $categoryScores[self::CATEGORY_ACCESS] = ['score' => 0, 'max' => 0];
        }
        $categoryScores[self::CATEGORY_ACCESS]['max'] += 5;
        if ($hasSubjects) {
            $categoryScores[self::CATEGORY_ACCESS]['score'] += 5;
        }

        // Calculate percentages
        $results['percentage'] = $results['maxScore'] > 0 
            ? round(($results['score'] / $results['maxScore']) * 100) 
            : 0;

        foreach ($categoryScores as $cat => $scores) {
            $results['categoryScores'][$cat] = [
                'score' => $scores['score'],
                'max' => $scores['max'],
                'percentage' => $scores['max'] > 0 ? round(($scores['score'] / $scores['max']) * 100) : 0
            ];
        }

        $results['grade'] = self::getGrade($results['percentage']);

        return $results;
    }

    /**
     * Get field value from record object
     */
    protected static function getFieldValueFromRecord($record, $fieldName)
    {
        switch ($fieldName) {
            case 'identifier':
                return $record->identifier;
            case 'title':
                return $record->title;
            case 'levelOfDescription':
                return $record->level_of_description_id;
            case 'scopeAndContent':
                return $record->scope_and_content;
            case 'extentAndMedium':
                return $record->extent_and_medium;
            case 'repository':
                return $record->repository_id;
            case 'accessConditions':
                return $record->access_conditions;
            default:
                return null;
        }
    }

    /**
     * Check if field has a value
     */
    protected static function hasValue($value, $fieldDef)
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            $value = trim($value);
            if (empty($value)) {
                return false;
            }
            if (isset($fieldDef['minLength']) && strlen($value) < $fieldDef['minLength']) {
                return false;
            }
            return true;
        }

        if (is_array($value) || $value instanceof Traversable) {
            return count($value) > 0;
        }

        return true;
    }

    /**
     * Check field quality issues
     */
    protected static function checkFieldQuality($fieldName, $value, $fieldDef)
    {
        $issues = [];

        if ($fieldName === 'title' && is_string($value)) {
            $titleLower = strtolower(trim($value));
            $placeholders = ['untitled', 'unknown', 'no title', 'n/a', 'tba', 'tbc'];
            if (in_array($titleLower, $placeholders)) {
                $issues[] = [
                    'field' => $fieldDef['label'],
                    'type' => 'placeholder',
                    'message' => 'Title appears to be a placeholder'
                ];
            }
        }

        if ($fieldName === 'scopeAndContent' && is_string($value)) {
            if (strlen($value) < 100) {
                $issues[] = [
                    'field' => $fieldDef['label'],
                    'type' => 'brief',
                    'message' => 'Scope and content is very brief (under 100 characters)'
                ];
            }
        }

        return $issues;
    }

    /**
     * Get grade based on percentage
     */
    protected static function getGrade($percentage)
    {
        if ($percentage >= self::THRESHOLD_EXCELLENT) {
            return ['grade' => 'A', 'label' => 'Excellent', 'color' => '#27ae60'];
        } elseif ($percentage >= self::THRESHOLD_GOOD) {
            return ['grade' => 'B', 'label' => 'Good', 'color' => '#2ecc71'];
        } elseif ($percentage >= self::THRESHOLD_FAIR) {
            return ['grade' => 'C', 'label' => 'Fair', 'color' => '#f39c12'];
        } elseif ($percentage >= self::THRESHOLD_POOR) {
            return ['grade' => 'D', 'label' => 'Poor', 'color' => '#e67e22'];
        } else {
            return ['grade' => 'F', 'label' => 'Incomplete', 'color' => '#e74c3c'];
        }
    }

    /**
     * Analyze collection/repository using Laravel Query Builder
     */
    public static function analyzeCollection($repositoryId = null, $parentId = null, $limit = 1000)
    {
        self::initDatabase();

        // Build query using Laravel
        $query = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function ($join) {
                $join->on('io.id', '=', 'i18n.id')
                    ->where('i18n.culture', '=', 'en');
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->where('io.id', '!=', 1) // Exclude root
            ->select(
                'io.id',
                'io.identifier',
                'io.level_of_description_id',
                'io.repository_id',
                'i18n.title',
                'i18n.scope_and_content',
                'i18n.extent_and_medium',
                'i18n.access_conditions',
                'slug.slug'
            );

        if ($repositoryId) {
            $query->where('io.repository_id', $repositoryId);
        }

        if ($parentId) {
            $query->where('io.parent_id', $parentId);
        }

        $objects = $query->limit($limit)->get();

        $results = [
            'totalRecords' => count($objects),
            'analyzedRecords' => 0,
            'overallScore' => 0,
            'gradeDistribution' => ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0],
            'categoryAverages' => [],
            'missingFieldCounts' => [],
            'issueTypeCounts' => [],
            'records' => [],
            'worstRecords' => [],
            'fieldCompleteness' => []
        ];

        $categoryTotals = [];
        $fieldCounts = [];

        foreach ($objects as $record) {
            $analysis = self::analyzeRecord($record);
            $results['analyzedRecords']++;
            $results['overallScore'] += $analysis['percentage'];
            $results['gradeDistribution'][$analysis['grade']['grade']]++;

            foreach ($analysis['categoryScores'] as $cat => $scores) {
                if (!isset($categoryTotals[$cat])) {
                    $categoryTotals[$cat] = ['total' => 0, 'count' => 0];
                }
                $categoryTotals[$cat]['total'] += $scores['percentage'];
                $categoryTotals[$cat]['count']++;
            }

            foreach ($analysis['missingRequired'] as $field) {
                if (!isset($results['missingFieldCounts'][$field])) {
                    $results['missingFieldCounts'][$field] = 0;
                }
                $results['missingFieldCounts'][$field]++;
            }
            foreach ($analysis['missingRecommended'] as $field) {
                if (!isset($results['missingFieldCounts'][$field])) {
                    $results['missingFieldCounts'][$field] = 0;
                }
                $results['missingFieldCounts'][$field]++;
            }

            foreach ($analysis['issues'] as $issue) {
                if (!isset($results['issueTypeCounts'][$issue['type']])) {
                    $results['issueTypeCounts'][$issue['type']] = 0;
                }
                $results['issueTypeCounts'][$issue['type']]++;
            }

            foreach ($analysis['fields'] as $fieldName => $fieldData) {
                if (!isset($fieldCounts[$fieldName])) {
                    $fieldCounts[$fieldName] = ['filled' => 0, 'total' => 0, 'label' => $fieldData['label']];
                }
                $fieldCounts[$fieldName]['total']++;
                if ($fieldData['hasValue']) {
                    $fieldCounts[$fieldName]['filled']++;
                }
            }

            $results['records'][] = [
                'id' => $analysis['id'],
                'slug' => $analysis['slug'],
                'title' => $analysis['title'],
                'percentage' => $analysis['percentage'],
                'grade' => $analysis['grade']
            ];
        }

        if ($results['analyzedRecords'] > 0) {
            $results['overallScore'] = round($results['overallScore'] / $results['analyzedRecords']);
        }

        foreach ($categoryTotals as $cat => $data) {
            $results['categoryAverages'][$cat] = round($data['total'] / $data['count']);
        }

        foreach ($fieldCounts as $fieldName => $data) {
            $results['fieldCompleteness'][$fieldName] = [
                'label' => $data['label'],
                'filled' => $data['filled'],
                'total' => $data['total'],
                'percentage' => $data['total'] > 0 ? round(($data['filled'] / $data['total']) * 100) : 0
            ];
        }

        arsort($results['missingFieldCounts']);

        usort($results['records'], function($a, $b) {
            return $a['percentage'] - $b['percentage'];
        });
        $results['worstRecords'] = array_slice($results['records'], 0, 20);

        usort($results['records'], function($a, $b) {
            return $b['percentage'] - $a['percentage'];
        });

        $results['overallGrade'] = self::getGrade($results['overallScore']);

        return $results;
    }

    /**
     * Get records missing specific field
     */
    public static function getRecordsMissingField($fieldName, $repositoryId = null, $limit = 100)
    {
        self::initDatabase();

        if (!isset(self::$fieldDefinitions[$fieldName])) {
            return [];
        }

        $fieldDef = self::$fieldDefinitions[$fieldName];
        $column = $fieldDef['column'] ?? null;

        if (!$column) {
            return [];
        }

        $query = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function ($join) {
                $join->on('io.id', '=', 'i18n.id')
                    ->where('i18n.culture', '=', 'en');
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->where('io.id', '!=', 1)
            ->whereNull($column)
            ->select('io.id', 'io.identifier', 'i18n.title', 'slug.slug');

        if ($repositoryId) {
            $query->where('io.repository_id', $repositoryId);
        }

        return $query->limit($limit)->get()->map(function($record) {
            return [
                'id' => $record->id,
                'slug' => $record->slug,
                'title' => $record->title ?: 'Untitled',
                'identifier' => $record->identifier
            ];
        })->all();
    }

    /**
     * Export quality report to CSV
     */
    public static function exportToCSV($repositoryId = null)
    {
        $analysis = self::analyzeCollection($repositoryId);
        
        $csv = "ID,Slug,Title,Score,Grade\n";
        
        foreach ($analysis['records'] as $record) {
            $csv .= sprintf(
                "%d,\"%s\",\"%s\",%d,%s\n",
                $record['id'],
                str_replace('"', '""', $record['slug'] ?? ''),
                str_replace('"', '""', $record['title'] ?? ''),
                $record['percentage'],
                $record['grade']['grade']
            );
        }

        return $csv;
    }

    /**
     * Get field definitions for UI
     */
    public static function getFieldDefinitions()
    {
        return self::$fieldDefinitions;
    }

    /**
     * Get category labels
     */
    public static function getCategoryLabels()
    {
        return [
            self::CATEGORY_IDENTITY => 'Identity',
            self::CATEGORY_DESCRIPTION => 'Description',
            self::CATEGORY_CONTEXT => 'Context',
            self::CATEGORY_ACCESS => 'Access',
            self::CATEGORY_DIGITAL => 'Digital Content'
        ];
    }
}