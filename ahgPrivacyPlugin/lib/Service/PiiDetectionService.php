<?php

namespace ahgPrivacyPlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * PII Detection Service
 *
 * Extends NER with regex-based PII detection for:
 * - ID Numbers (SA ID, Nigerian NIN, Passport)
 * - Email addresses
 * - Phone numbers
 * - Financial data (bank accounts, tax numbers)
 *
 * Integrates with:
 * - ahgAIPlugin (entity extraction, translation, summarization)
 * - ahgPrivacyPlugin (data inventory)
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 */
class PiiDetectionService
{
    /**
     * PII patterns for regex detection
     */
    private static array $patterns = [
        // South African ID: 13 digits, YYMMDD + gender + citizenship + checksum
        'SA_ID' => '/\b(\d{2})(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])(\d{4})(\d)(\d)(\d)\b/',

        // Nigerian NIN: 11 digits
        'NG_NIN' => '/\b\d{11}\b/',

        // Passport: Various formats (2 letters + 6-9 digits or similar)
        'PASSPORT' => '/\b[A-Z]{1,2}\d{6,9}\b/i',

        // Email addresses
        'EMAIL' => '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/',

        // Phone numbers (SA format: 0XX XXX XXXX or +27 XX XXX XXXX)
        'PHONE_SA' => '/\b(?:\+27|0)[1-9]\d{1,2}[\s-]?\d{3}[\s-]?\d{4}\b/',

        // International phone (generic)
        'PHONE_INTL' => '/\b\+\d{1,3}[\s-]?\d{2,4}[\s-]?\d{3,4}[\s-]?\d{3,4}\b/',

        // Bank account (SA: typically 10-11 digits)
        'BANK_ACCOUNT' => '/\b\d{10,11}\b/',

        // Tax number (SA: 10 digits starting with specific prefixes)
        'TAX_NUMBER' => '/\b[0-9]{10}\b/',

        // Credit card (basic Luhn-eligible patterns)
        'CREDIT_CARD' => '/\b(?:\d{4}[\s-]?){3}\d{4}\b/',
    ];

    /**
     * PII category mappings (to privacy_data_inventory.data_type)
     */
    private static array $categoryMap = [
        'PERSON' => 'personal',
        'SA_ID' => 'personal',
        'NG_NIN' => 'personal',
        'PASSPORT' => 'personal',
        'EMAIL' => 'personal',
        'PHONE_SA' => 'personal',
        'PHONE_INTL' => 'personal',
        'BANK_ACCOUNT' => 'financial',
        'TAX_NUMBER' => 'financial',
        'CREDIT_CARD' => 'financial',
        'DATE' => 'personal',
        'ORG' => 'personal',
        'GPE' => 'personal',
        // ISAD Access Point types
        'ISAD_SUBJECT' => 'personal',
        'ISAD_PLACE' => 'personal',
        'ISAD_NAME' => 'personal',
        'ISAD_DATE' => 'personal',
    ];

    /**
     * Risk levels for PII types
     */
    private static array $riskLevels = [
        'SA_ID' => 'high',
        'NG_NIN' => 'high',
        'PASSPORT' => 'high',
        'CREDIT_CARD' => 'critical',
        'BANK_ACCOUNT' => 'high',
        'TAX_NUMBER' => 'high',
        'EMAIL' => 'medium',
        'PHONE_SA' => 'medium',
        'PHONE_INTL' => 'medium',
        'PERSON' => 'medium',
        'DATE' => 'low',
        'ORG' => 'low',
        'GPE' => 'low',
        // ISAD Access Point types
        'ISAD_SUBJECT' => 'low',
        'ISAD_PLACE' => 'low',
        'ISAD_NAME' => 'medium',
        'ISAD_DATE' => 'low',
    ];

    /**
     * Get taxonomy ID for subjects (uses AhgTaxonomy for dynamic lookup)
     */
    private static function getSubjectsTaxonomyId(): int
    {
        return \AhgCore\Taxonomy\AhgTaxonomy::getId('subjects') ?? 35;
    }

    /**
     * Get taxonomy ID for places (uses AhgTaxonomy for dynamic lookup)
     */
    private static function getPlacesTaxonomyId(): int
    {
        return \AhgCore\Taxonomy\AhgTaxonomy::getId('places') ?? 42;
    }

    /**
     * Scan text for PII using regex patterns
     */
    public function detectPii(string $text): array
    {
        $results = [
            'entities' => [],
            'summary' => [
                'total' => 0,
                'high_risk' => 0,
                'medium_risk' => 0,
                'low_risk' => 0,
            ],
            'categories' => [],
        ];

        foreach (self::$patterns as $type => $pattern) {
            if (preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $value = $match[0];
                    $position = $match[1];

                    // Validate specific patterns
                    if ($type === 'SA_ID' && !$this->validateSaId($value)) {
                        continue;
                    }

                    // Skip if looks like a generic number (not PII)
                    if (in_array($type, ['BANK_ACCOUNT', 'TAX_NUMBER']) && !$this->looksLikeFinancial($text, $position, $value)) {
                        continue;
                    }

                    $risk = self::$riskLevels[$type] ?? 'medium';
                    $category = self::$categoryMap[$type] ?? 'personal';

                    $results['entities'][] = [
                        'type' => $type,
                        'value' => $this->maskPii($value, $type),
                        'raw_value' => $value,
                        'position' => $position,
                        'risk_level' => $risk,
                        'category' => $category,
                        'confidence' => $this->calculateConfidence($type, $value, $text),
                    ];

                    $results['summary']['total']++;
                    $results['summary'][$risk . '_risk']++;

                    if (!isset($results['categories'][$category])) {
                        $results['categories'][$category] = 0;
                    }
                    $results['categories'][$category]++;
                }
            }
        }

        return $results;
    }

    /**
     * Full PII scan combining NER + regex detection
     */
    public function fullScan(string $text): array
    {
        // Get NER entities (PERSON, ORG, GPE, DATE)
        $nerEntities = $this->callNerApi($text);

        // Get regex-based PII
        $piiEntities = $this->detectPii($text);

        // Merge results
        $allEntities = $piiEntities['entities'];

        // Add NER entities
        foreach ($nerEntities as $type => $values) {
            foreach ($values as $value) {
                $risk = self::$riskLevels[$type] ?? 'low';
                $category = self::$categoryMap[$type] ?? 'personal';

                $allEntities[] = [
                    'type' => $type,
                    'value' => $value,
                    'raw_value' => $value,
                    'position' => strpos($text, $value) ?: 0,
                    'risk_level' => $risk,
                    'category' => $category,
                    'confidence' => 0.85, // NER confidence
                    'source' => 'ner',
                ];

                $piiEntities['summary']['total']++;
                $piiEntities['summary'][$risk . '_risk']++;

                if (!isset($piiEntities['categories'][$category])) {
                    $piiEntities['categories'][$category] = 0;
                }
                $piiEntities['categories'][$category]++;
            }
        }

        // Sort by position
        usort($allEntities, fn($a, $b) => $a['position'] <=> $b['position']);

        return [
            'entities' => $allEntities,
            'summary' => $piiEntities['summary'],
            'categories' => $piiEntities['categories'],
            'has_high_risk' => $piiEntities['summary']['high_risk'] > 0 ||
                              ($piiEntities['summary']['critical_risk'] ?? 0) > 0,
        ];
    }

    /**
     * Scan an information object for PII
     */
    public function scanObject(int $objectId, bool $includeDigitalObjects = true): array
    {
        $results = [
            'object_id' => $objectId,
            'scanned_at' => date('Y-m-d H:i:s'),
            'fields_scanned' => [],
            'entities' => [],
            'summary' => ['total' => 0, 'high_risk' => 0, 'medium_risk' => 0, 'low_risk' => 0],
            'categories' => [],
            'risk_score' => 0,
        ];

        // Get object metadata
        $object = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function($j) {
                $j->on('i18n.id', '=', 'io.id')->where('i18n.culture', '=', 'en');
            })
            ->where('io.id', $objectId)
            ->select(['io.id', 'i18n.title', 'i18n.scope_and_content', 'i18n.arrangement',
                      'i18n.appraisal', 'i18n.archival_history', 'i18n.physical_characteristics'])
            ->first();

        if (!$object) {
            return $results;
        }

        // Scan each text field
        $fieldsToScan = [
            'title' => $object->title,
            'scope_and_content' => $object->scope_and_content,
            'arrangement' => $object->arrangement,
            'appraisal' => $object->appraisal,
            'archival_history' => $object->archival_history,
            'physical_characteristics' => $object->physical_characteristics,
        ];

        foreach ($fieldsToScan as $fieldName => $fieldValue) {
            if (empty($fieldValue)) {
                continue;
            }

            $results['fields_scanned'][] = $fieldName;
            $scan = $this->fullScan($fieldValue);

            foreach ($scan['entities'] as $entity) {
                $entity['field'] = $fieldName;
                $results['entities'][] = $entity;
            }

            // Aggregate summaries
            foreach (['total', 'high_risk', 'medium_risk', 'low_risk'] as $key) {
                $results['summary'][$key] += $scan['summary'][$key] ?? 0;
            }

            foreach ($scan['categories'] as $cat => $count) {
                if (!isset($results['categories'][$cat])) {
                    $results['categories'][$cat] = 0;
                }
                $results['categories'][$cat] += $count;
            }
        }

        // Scan digital object (PDF) if requested
        if ($includeDigitalObjects) {
            $pdfScan = $this->scanDigitalObject($objectId);
            if ($pdfScan) {
                $results['fields_scanned'][] = 'digital_object';
                foreach ($pdfScan['entities'] as $entity) {
                    $entity['field'] = 'digital_object';
                    $results['entities'][] = $entity;
                }
                foreach (['total', 'high_risk', 'medium_risk', 'low_risk'] as $key) {
                    $results['summary'][$key] += $pdfScan['summary'][$key] ?? 0;
                }
            }
        }

        // Include ISAD access points (Subject, Places, Names, Dates)
        $isadEntities = $this->convertAccessPointsToEntities($objectId);
        if (!empty($isadEntities)) {
            $results['fields_scanned'][] = 'isad_access_points';
            foreach ($isadEntities as $entity) {
                $results['entities'][] = $entity;
                $results['summary']['total']++;
                $riskKey = $entity['risk_level'] . '_risk';
                if (isset($results['summary'][$riskKey])) {
                    $results['summary'][$riskKey]++;
                }
            }
        }

        // Calculate risk score (0-100)
        $results['risk_score'] = $this->calculateRiskScore($results['summary']);

        return $results;
    }

    /**
     * Scan digital object (PDF) for PII
     */
    public function scanDigitalObject(int $objectId): ?array
    {
        $digitalObject = DB::table('digital_object')
            ->where('object_id', $objectId)
            ->first();

        if (!$digitalObject || $digitalObject->mime_type !== 'application/pdf') {
            return null;
        }

        // Use NER PDF extraction endpoint
        $filePath = $this->getDigitalObjectPath($digitalObject);
        if (!$filePath || !file_exists($filePath)) {
            return null;
        }

        try {
            $nerService = new \ahgNerService();
            $pdfResult = $nerService->extractFromPdf($filePath);

            if (!$pdfResult || !($pdfResult['success'] ?? false)) {
                return null;
            }

            // Get extracted text and scan for PII
            $text = $pdfResult['text'] ?? '';
            if (empty($text)) {
                return null;
            }

            return $this->fullScan($text);
        } catch (\Exception $e) {
            error_log('PII PDF scan error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get ISAD access points for an information object
     *
     * Returns subjects, places, names, and dates from ISAD access point fields
     *
     * @param int $objectId
     * @return array
     */
    public function getIsadAccessPoints(int $objectId): array
    {
        $accessPoints = [
            'subjects' => [],
            'places' => [],
            'names' => [],
            'dates' => [],
        ];

        // Get subject access points (taxonomy 35)
        $subjects = DB::table('object_term_relation as otr')
            ->join('term as t', 't.id', '=', 'otr.term_id')
            ->join('term_i18n as ti', function ($j) {
                $j->on('ti.id', '=', 't.id')->where('ti.culture', '=', 'en');
            })
            ->where('otr.object_id', $objectId)
            ->where('t.taxonomy_id', self::getSubjectsTaxonomyId())
            ->pluck('ti.name')
            ->toArray();
        $accessPoints['subjects'] = array_unique(array_filter($subjects));

        // Get place access points (taxonomy 42)
        $places = DB::table('object_term_relation as otr')
            ->join('term as t', 't.id', '=', 'otr.term_id')
            ->join('term_i18n as ti', function ($j) {
                $j->on('ti.id', '=', 't.id')->where('ti.culture', '=', 'en');
            })
            ->where('otr.object_id', $objectId)
            ->where('t.taxonomy_id', self::getPlacesTaxonomyId())
            ->pluck('ti.name')
            ->toArray();
        $accessPoints['places'] = array_unique(array_filter($places));

        // Get name access points (via events - creators, subjects of, etc.)
        $names = DB::table('event as ev')
            ->join('actor as a', 'a.id', '=', 'ev.actor_id')
            ->join('actor_i18n as ai', function ($j) {
                $j->on('ai.id', '=', 'a.id')->where('ai.culture', '=', 'en');
            })
            ->where('ev.object_id', $objectId)
            ->whereNotNull('ev.actor_id')
            ->pluck('ai.authorized_form_of_name')
            ->toArray();
        $accessPoints['names'] = array_unique(array_filter($names));

        // Get date access points (from events)
        $dates = DB::table('event')
            ->where('object_id', $objectId)
            ->whereNotNull('start_date')
            ->select(['start_date', 'end_date'])
            ->get();

        foreach ($dates as $date) {
            if ($date->start_date) {
                $accessPoints['dates'][] = $date->start_date;
            }
            if ($date->end_date && $date->end_date !== $date->start_date) {
                $accessPoints['dates'][] = $date->end_date;
            }
        }
        $accessPoints['dates'] = array_unique(array_filter($accessPoints['dates']));

        return $accessPoints;
    }

    /**
     * Convert ISAD access points to PII entity format
     *
     * @param int $objectId
     * @return array
     */
    public function convertAccessPointsToEntities(int $objectId): array
    {
        $accessPoints = $this->getIsadAccessPoints($objectId);
        $entities = [];

        // Convert subjects
        foreach ($accessPoints['subjects'] as $subject) {
            $entities[] = [
                'type' => 'ISAD_SUBJECT',
                'value' => $subject,
                'raw_value' => $subject,
                'position' => 0,
                'risk_level' => self::$riskLevels['ISAD_SUBJECT'],
                'category' => 'personal',
                'confidence' => 1.0, // Access points are definitive
                'source' => 'isad_access_point',
                'field' => 'subject_access_points',
            ];
        }

        // Convert places
        foreach ($accessPoints['places'] as $place) {
            $entities[] = [
                'type' => 'ISAD_PLACE',
                'value' => $place,
                'raw_value' => $place,
                'position' => 0,
                'risk_level' => self::$riskLevels['ISAD_PLACE'],
                'category' => 'personal',
                'confidence' => 1.0,
                'source' => 'isad_access_point',
                'field' => 'place_access_points',
            ];
        }

        // Convert names
        foreach ($accessPoints['names'] as $name) {
            $entities[] = [
                'type' => 'ISAD_NAME',
                'value' => $name,
                'raw_value' => $name,
                'position' => 0,
                'risk_level' => self::$riskLevels['ISAD_NAME'],
                'category' => 'personal',
                'confidence' => 1.0,
                'source' => 'isad_access_point',
                'field' => 'name_access_points',
            ];
        }

        // Convert dates
        foreach ($accessPoints['dates'] as $date) {
            $entities[] = [
                'type' => 'ISAD_DATE',
                'value' => $date,
                'raw_value' => $date,
                'position' => 0,
                'risk_level' => self::$riskLevels['ISAD_DATE'],
                'category' => 'personal',
                'confidence' => 1.0,
                'source' => 'isad_access_point',
                'field' => 'date_access_points',
            ];
        }

        return $entities;
    }

    /**
     * Save PII scan results to database
     */
    public function saveScanResults(int $objectId, array $results, ?int $userId = null): int
    {
        // Create extraction record
        $extractionId = DB::table('ahg_ner_extraction')->insertGetId([
            'object_id' => $objectId,
            'backend_used' => 'pii_detector',
            'status' => 'completed',
            'entity_count' => $results['summary']['total'],
            'extracted_at' => date('Y-m-d H:i:s'),
        ]);

        // Save each entity
        foreach ($results['entities'] as $entity) {
            DB::table('ahg_ner_entity')->insert([
                'extraction_id' => $extractionId,
                'object_id' => $objectId,
                'entity_type' => $entity['type'],
                'entity_value' => $entity['value'],
                'original_value' => $entity['raw_value'],
                'confidence' => $entity['confidence'],
                'status' => $entity['risk_level'] === 'high' || $entity['risk_level'] === 'critical'
                    ? 'flagged' : 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // Update or create data inventory entry if high-risk PII found
        if ($results['summary']['high_risk'] > 0) {
            $this->createDataInventoryEntry($objectId, $results);
        }

        return $extractionId;
    }

    /**
     * Create privacy data inventory entry for high-risk PII
     */
    protected function createDataInventoryEntry(int $objectId, array $results): void
    {
        // Check if entry already exists
        $existing = DB::table('privacy_data_inventory')
            ->where('name', 'LIKE', "PII-{$objectId}-%")
            ->first();

        if ($existing) {
            // Update existing entry
            DB::table('privacy_data_inventory')
                ->where('id', $existing->id)
                ->update([
                    'description' => $this->buildInventoryDescription($results),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            return;
        }

        // Get object title for reference
        $object = DB::table('information_object_i18n')
            ->where('id', $objectId)
            ->where('culture', 'en')
            ->first();

        $title = $object->title ?? "Object #{$objectId}";
        $dataType = $this->determineDataType($results['categories']);

        DB::table('privacy_data_inventory')->insert([
            'name' => "PII-{$objectId}-" . date('Ymd'),
            'description' => $this->buildInventoryDescription($results),
            'data_type' => $dataType,
            'storage_location' => "information_object:{$objectId}",
            'storage_format' => 'electronic',
            'encryption' => 0,
            'access_controls' => json_encode(['requires_review' => true]),
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Batch scan multiple objects
     */
    public function batchScan(array $filters = [], int $limit = 100): array
    {
        $query = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function($j) {
                $j->on('i18n.id', '=', 'io.id')->where('i18n.culture', '=', 'en');
            })
            ->whereNotNull('i18n.scope_and_content')
            ->where('i18n.scope_and_content', '!=', '');

        if (!empty($filters['repository_id'])) {
            $query->where('io.repository_id', $filters['repository_id']);
        }

        if (!empty($filters['level_of_description_id'])) {
            $query->where('io.level_of_description_id', $filters['level_of_description_id']);
        }

        // Skip already scanned objects
        if (empty($filters['rescan'])) {
            $scannedIds = DB::table('ahg_ner_extraction')
                ->where('backend_used', 'pii_detector')
                ->pluck('object_id')
                ->toArray();

            if (!empty($scannedIds)) {
                $query->whereNotIn('io.id', $scannedIds);
            }
        }

        $objects = $query->select(['io.id', 'i18n.title'])
            ->limit($limit)
            ->get();

        $results = [
            'scanned' => 0,
            'with_pii' => 0,
            'high_risk' => 0,
            'objects' => [],
        ];

        foreach ($objects as $obj) {
            $scan = $this->scanObject($obj->id);
            $results['scanned']++;

            if ($scan['summary']['total'] > 0) {
                $results['with_pii']++;
                $results['objects'][] = [
                    'id' => $obj->id,
                    'title' => $obj->title,
                    'pii_count' => $scan['summary']['total'],
                    'risk_score' => $scan['risk_score'],
                ];
            }

            if ($scan['summary']['high_risk'] > 0) {
                $results['high_risk']++;
            }

            // Save results
            $this->saveScanResults($obj->id, $scan);
        }

        // Sort by risk score
        usort($results['objects'], fn($a, $b) => $b['risk_score'] <=> $a['risk_score']);

        return $results;
    }

    /**
     * Get PII scan statistics
     */
    public function getStatistics(): array
    {
        $totalScanned = DB::table('ahg_ner_extraction')
            ->where('backend_used', 'pii_detector')
            ->count();

        $withPii = DB::table('ahg_ner_extraction as e')
            ->where('e.backend_used', 'pii_detector')
            ->where('e.entity_count', '>', 0)
            ->count();

        $highRiskCount = DB::table('ahg_ner_entity')
            ->where('status', 'flagged')
            ->count();

        $byType = DB::table('ahg_ner_entity as e')
            ->join('ahg_ner_extraction as ex', 'ex.id', '=', 'e.extraction_id')
            ->where('ex.backend_used', 'pii_detector')
            ->select('e.entity_type', DB::raw('COUNT(*) as count'))
            ->groupBy('e.entity_type')
            ->pluck('count', 'entity_type')
            ->toArray();

        $pendingReview = DB::table('ahg_ner_entity')
            ->whereIn('status', ['pending', 'flagged'])
            ->count();

        return [
            'total_scanned' => $totalScanned,
            'with_pii' => $withPii,
            'high_risk_entities' => $highRiskCount,
            'pending_review' => $pendingReview,
            'by_type' => $byType,
            'coverage_percent' => $this->calculateCoverage(),
        ];
    }

    /**
     * Validate South African ID number using Luhn algorithm
     */
    protected function validateSaId(string $id): bool
    {
        if (strlen($id) !== 13 || !ctype_digit($id)) {
            return false;
        }

        // Check date validity
        $year = (int)substr($id, 0, 2);
        $month = (int)substr($id, 2, 2);
        $day = (int)substr($id, 4, 2);

        if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
            return false;
        }

        // Luhn checksum validation
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $digit = (int)$id[$i];
            if ($i % 2 === 0) {
                $sum += $digit;
            } else {
                $doubled = $digit * 2;
                $sum += $doubled > 9 ? $doubled - 9 : $doubled;
            }
        }

        return $sum % 10 === 0;
    }

    /**
     * Check if a number looks like financial data based on context
     */
    protected function looksLikeFinancial(string $text, int $position, string $value): bool
    {
        $context = strtolower(substr($text, max(0, $position - 50), 100));
        $financialKeywords = ['account', 'bank', 'tax', 'vat', 'payment', 'invoice', 'ref'];

        foreach ($financialKeywords as $keyword) {
            if (strpos($context, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mask PII value for display
     */
    protected function maskPii(string $value, string $type): string
    {
        switch ($type) {
            case 'SA_ID':
            case 'NG_NIN':
            case 'PASSPORT':
                return substr($value, 0, 4) . str_repeat('*', strlen($value) - 6) . substr($value, -2);
            case 'EMAIL':
                $parts = explode('@', $value);
                return substr($parts[0], 0, 2) . '***@' . $parts[1];
            case 'PHONE_SA':
            case 'PHONE_INTL':
                return substr($value, 0, 4) . '***' . substr($value, -3);
            case 'CREDIT_CARD':
                return '****-****-****-' . substr(preg_replace('/\D/', '', $value), -4);
            case 'BANK_ACCOUNT':
            case 'TAX_NUMBER':
                return substr($value, 0, 3) . str_repeat('*', strlen($value) - 5) . substr($value, -2);
            default:
                return $value;
        }
    }

    /**
     * Calculate confidence score for detection
     */
    protected function calculateConfidence(string $type, string $value, string $text): float
    {
        $baseConfidence = 0.7;

        // Higher confidence for validated patterns
        if ($type === 'SA_ID' && $this->validateSaId($value)) {
            return 0.95;
        }

        if ($type === 'EMAIL' && filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return 0.95;
        }

        // Context-based confidence boost
        $context = strtolower(substr($text, max(0, strpos($text, $value) - 30), 60));
        $contextKeywords = [
            'SA_ID' => ['id', 'identity', 'number', 'document'],
            'EMAIL' => ['email', 'contact', 'mail', '@'],
            'PHONE_SA' => ['phone', 'tel', 'call', 'contact', 'mobile', 'cell'],
        ];

        if (isset($contextKeywords[$type])) {
            foreach ($contextKeywords[$type] as $keyword) {
                if (strpos($context, $keyword) !== false) {
                    $baseConfidence += 0.1;
                }
            }
        }

        return min($baseConfidence, 0.95);
    }

    /**
     * Calculate overall risk score (0-100)
     */
    protected function calculateRiskScore(array $summary): int
    {
        $score = 0;
        $score += ($summary['critical_risk'] ?? 0) * 30;
        $score += ($summary['high_risk'] ?? 0) * 20;
        $score += ($summary['medium_risk'] ?? 0) * 5;
        $score += ($summary['low_risk'] ?? 0) * 1;

        return min($score, 100);
    }

    /**
     * Determine primary data type for inventory
     */
    protected function determineDataType(array $categories): string
    {
        if (!empty($categories['financial'])) {
            return 'financial';
        }
        if (!empty($categories['health'])) {
            return 'health';
        }
        return 'personal';
    }

    /**
     * Build description for data inventory entry
     */
    protected function buildInventoryDescription(array $results): string
    {
        $parts = ["PII scan detected {$results['summary']['total']} entities."];

        if ($results['summary']['high_risk'] > 0) {
            $parts[] = "High-risk: {$results['summary']['high_risk']}";
        }

        $types = [];
        foreach ($results['entities'] as $entity) {
            $types[$entity['type']] = ($types[$entity['type']] ?? 0) + 1;
        }

        $typeList = [];
        foreach ($types as $type => $count) {
            $typeList[] = "{$type}: {$count}";
        }

        if (!empty($typeList)) {
            $parts[] = "Types: " . implode(', ', $typeList);
        }

        return implode(' ', $parts);
    }

    /**
     * Calculate scan coverage percentage
     */
    protected function calculateCoverage(): float
    {
        $totalObjects = DB::table('information_object')
            ->where('id', '!=', 1) // Skip root
            ->count();

        if ($totalObjects === 0) {
            return 0;
        }

        $scannedObjects = DB::table('ahg_ner_extraction')
            ->where('backend_used', 'pii_detector')
            ->distinct('object_id')
            ->count('object_id');

        return round(($scannedObjects / $totalObjects) * 100, 2);
    }

    /**
     * Call NER API
     */
    protected function callNerApi(string $text): array
    {
        try {
            $nerService = new \ahgNerService();
            $result = $nerService->extract($text);

            if ($result && ($result['success'] ?? false)) {
                return $result['entities'] ?? [];
            }
        } catch (\Exception $e) {
            error_log('NER API error: ' . $e->getMessage());
        }

        return ['PERSON' => [], 'ORG' => [], 'GPE' => [], 'DATE' => []];
    }

    /**
     * Get digital object file path
     */
    protected function getDigitalObjectPath($digitalObject): ?string
    {
        $path = $digitalObject->path ?? '';
        $name = $digitalObject->name ?? '';

        if (!$path || !$name) {
            return null;
        }

        $webDir = \sfConfig::get('sf_web_dir');
        $fullPath = $webDir . '/uploads/' . trim($path, '/') . '/' . $name;

        return file_exists($fullPath) ? $fullPath : null;
    }
}
