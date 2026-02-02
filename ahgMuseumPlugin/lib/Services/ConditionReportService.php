<?php

declare(strict_types=1);

namespace arMuseumMetadataPlugin\Services;

use ahgCorePlugin\Services\AhgTaxonomyService;
use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Condition Report Service.
 *
 * Manages condition assessments and reports for museum objects.
 * Supports Spectrum 5.0 condition checking procedures and
 * configurable report templates.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ConditionReportService
{
    /** Condition ratings (Spectrum 5.0 aligned) */
    public const RATINGS = [
        'excellent' => [
            'value' => 1,
            'label' => 'Excellent',
            'description' => 'No visible damage, deterioration or loss. Object is stable.',
            'color' => '#4caf50',
        ],
        'good' => [
            'value' => 2,
            'label' => 'Good',
            'description' => 'Minor wear or aging consistent with age. Object is stable.',
            'color' => '#8bc34a',
        ],
        'fair' => [
            'value' => 3,
            'label' => 'Fair',
            'description' => 'Moderate wear, minor damage or deterioration. May need attention.',
            'color' => '#ffc107',
        ],
        'poor' => [
            'value' => 4,
            'label' => 'Poor',
            'description' => 'Significant damage, deterioration or loss. Conservation needed.',
            'color' => '#ff9800',
        ],
        'unacceptable' => [
            'value' => 5,
            'label' => 'Unacceptable',
            'description' => 'Severe damage or deterioration. Immediate intervention required.',
            'color' => '#f44336',
        ],
    ];

    /** Damage types */
    public const DAMAGE_TYPES = [
        // Physical damage
        'abrasion' => ['category' => 'physical', 'label' => 'Abrasion/Scratches'],
        'crack' => ['category' => 'physical', 'label' => 'Crack'],
        'break' => ['category' => 'physical', 'label' => 'Break/Fracture'],
        'chip' => ['category' => 'physical', 'label' => 'Chip/Loss'],
        'dent' => ['category' => 'physical', 'label' => 'Dent/Deformation'],
        'tear' => ['category' => 'physical', 'label' => 'Tear'],
        'hole' => ['category' => 'physical', 'label' => 'Hole/Puncture'],
        'missing_part' => ['category' => 'physical', 'label' => 'Missing Part'],

        // Surface damage
        'stain' => ['category' => 'surface', 'label' => 'Stain'],
        'discoloration' => ['category' => 'surface', 'label' => 'Discoloration'],
        'fading' => ['category' => 'surface', 'label' => 'Fading'],
        'foxing' => ['category' => 'surface', 'label' => 'Foxing'],
        'accretion' => ['category' => 'surface', 'label' => 'Accretion/Deposit'],
        'corrosion' => ['category' => 'surface', 'label' => 'Corrosion/Rust'],
        'tarnish' => ['category' => 'surface', 'label' => 'Tarnish'],

        // Structural damage
        'delamination' => ['category' => 'structural', 'label' => 'Delamination'],
        'flaking' => ['category' => 'structural', 'label' => 'Flaking/Lifting'],
        'warping' => ['category' => 'structural', 'label' => 'Warping'],
        'cupping' => ['category' => 'structural', 'label' => 'Cupping'],
        'splitting' => ['category' => 'structural', 'label' => 'Splitting'],
        'loose_joint' => ['category' => 'structural', 'label' => 'Loose Joint'],

        // Biological damage
        'mold' => ['category' => 'biological', 'label' => 'Mold/Mildew'],
        'insect' => ['category' => 'biological', 'label' => 'Insect Damage'],
        'rodent' => ['category' => 'biological', 'label' => 'Rodent Damage'],

        // Other
        'previous_repair' => ['category' => 'other', 'label' => 'Previous Repair'],
        'inherent_vice' => ['category' => 'other', 'label' => 'Inherent Vice'],
        'other' => ['category' => 'other', 'label' => 'Other'],
    ];

    /** Report contexts (when condition checked) */
    public const CONTEXTS = [
        'acquisition' => 'Acquisition',
        'loan_out' => 'Loan Out',
        'loan_in' => 'Loan In',
        'loan_return' => 'Loan Return',
        'exhibition' => 'Exhibition',
        'storage' => 'Storage',
        'conservation' => 'Conservation',
        'routine' => 'Routine Check',
        'incident' => 'Incident/Damage Report',
        'insurance' => 'Insurance Valuation',
        'deaccession' => 'Deaccession',
    ];

    /** Location descriptors for damage mapping */
    public const LOCATIONS = [
        'overall' => 'Overall',
        'front' => 'Front/Recto',
        'back' => 'Back/Verso',
        'top' => 'Top',
        'bottom' => 'Bottom',
        'left' => 'Left Side',
        'right' => 'Right Side',
        'center' => 'Center',
        'edge' => 'Edge/Border',
        'corner_tl' => 'Top Left Corner',
        'corner_tr' => 'Top Right Corner',
        'corner_bl' => 'Bottom Left Corner',
        'corner_br' => 'Bottom Right Corner',
        'frame' => 'Frame',
        'mount' => 'Mount/Support',
        'base' => 'Base/Pedestal',
        'interior' => 'Interior',
        'exterior' => 'Exterior',
        'handle' => 'Handle',
        'lid' => 'Lid/Cover',
        'foot' => 'Foot/Feet',
    ];

    private ConnectionInterface $db;
    private LoggerInterface $logger;
    private AhgTaxonomyService $taxonomyService;

    public function __construct(
        ConnectionInterface $db,
        ?LoggerInterface $logger = null,
        ?AhgTaxonomyService $taxonomyService = null
    ) {
        $this->db = $db;
        $this->logger = $logger ?? new NullLogger();
        $this->taxonomyService = $taxonomyService ?? new AhgTaxonomyService();
    }

    /**
     * Create a new condition report.
     *
     * @param int   $objectId    Information object ID
     * @param array $reportData  Report data
     * @param int   $assessorId  User ID of assessor
     *
     * @return int Report ID
     */
    public function createReport(int $objectId, array $reportData, int $assessorId): int
    {
        $now = date('Y-m-d H:i:s');

        $data = [
            'information_object_id' => $objectId,
            'assessor_user_id' => $assessorId,
            'assessment_date' => $reportData['assessment_date'] ?? date('Y-m-d'),
            'context' => $reportData['context'] ?? 'routine',
            'overall_rating' => $reportData['overall_rating'] ?? 'good',
            'summary' => $reportData['summary'] ?? null,
            'recommendations' => $reportData['recommendations'] ?? null,
            'priority' => $reportData['priority'] ?? 'normal',
            'next_check_date' => $reportData['next_check_date'] ?? null,
            'environmental_notes' => $reportData['environmental_notes'] ?? null,
            'handling_notes' => $reportData['handling_notes'] ?? null,
            'display_notes' => $reportData['display_notes'] ?? null,
            'storage_notes' => $reportData['storage_notes'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $reportId = $this->db->table('condition_report')->insertGetId($data);

        // Insert damage observations
        if (!empty($reportData['damages'])) {
            foreach ($reportData['damages'] as $damage) {
                $this->addDamageObservation($reportId, $damage);
            }
        }

        // Insert images
        if (!empty($reportData['images'])) {
            foreach ($reportData['images'] as $image) {
                $this->addReportImage($reportId, $image);
            }
        }

        $this->logger->info('Condition report created', [
            'report_id' => $reportId,
            'object_id' => $objectId,
            'context' => $data['context'],
            'rating' => $data['overall_rating'],
        ]);

        return $reportId;
    }

    /**
     * Add damage observation to report.
     */
    public function addDamageObservation(int $reportId, array $damage): int
    {
        $data = [
            'condition_report_id' => $reportId,
            'damage_type' => $damage['type'],
            'location' => $damage['location'] ?? 'overall',
            'severity' => $damage['severity'] ?? 'minor', // minor, moderate, severe
            'description' => $damage['description'] ?? null,
            'dimensions' => $damage['dimensions'] ?? null, // e.g., "2cm x 3cm"
            'is_active' => $damage['is_active'] ?? true,
            'treatment_required' => $damage['treatment_required'] ?? false,
            'treatment_notes' => $damage['treatment_notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        return $this->db->table('condition_damage')->insertGetId($data);
    }

    /**
     * Add image to report.
     */
    public function addReportImage(int $reportId, array $image): int
    {
        $data = [
            'condition_report_id' => $reportId,
            'digital_object_id' => $image['digital_object_id'] ?? null,
            'file_path' => $image['file_path'] ?? null,
            'caption' => $image['caption'] ?? null,
            'image_type' => $image['type'] ?? 'general', // general, detail, damage, before, after
            'annotations' => isset($image['annotations']) ? json_encode($image['annotations']) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        return $this->db->table('condition_image')->insertGetId($data);
    }

    /**
     * Get report by ID.
     */
    public function getReport(int $reportId): ?array
    {
        $report = $this->db->table('condition_report')
            ->where('id', $reportId)
            ->first();

        if (!$report) {
            return null;
        }

        $report = (array) $report;

        // Get damages
        $report['damages'] = $this->db->table('condition_damage')
            ->where('condition_report_id', $reportId)
            ->get()
            ->map(fn ($d) => (array) $d)
            ->all();

        // Get images
        $report['images'] = $this->db->table('condition_image')
            ->where('condition_report_id', $reportId)
            ->get()
            ->map(fn ($i) => (array) $i)
            ->all();

        // Add rating metadata
        $report['rating_info'] = self::RATINGS[$report['overall_rating']] ?? null;

        return $report;
    }

    /**
     * Get all reports for an object.
     *
     * @param int $objectId Information object ID
     *
     * @return array Reports ordered by date descending
     */
    public function getReportsForObject(int $objectId): array
    {
        return $this->db->table('condition_report')
            ->where('information_object_id', $objectId)
            ->orderByDesc('assessment_date')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Get latest report for an object.
     */
    public function getLatestReport(int $objectId): ?array
    {
        $report = $this->db->table('condition_report')
            ->where('information_object_id', $objectId)
            ->orderByDesc('assessment_date')
            ->first();

        if (!$report) {
            return null;
        }

        return $this->getReport($report->id);
    }

    /**
     * Compare two reports (for tracking condition changes).
     *
     * @param int $reportId1 First report ID
     * @param int $reportId2 Second report ID
     *
     * @return array Comparison data
     */
    public function compareReports(int $reportId1, int $reportId2): array
    {
        $report1 = $this->getReport($reportId1);
        $report2 = $this->getReport($reportId2);

        if (!$report1 || !$report2) {
            return ['error' => 'One or both reports not found'];
        }

        // Ensure chronological order
        if ($report1['assessment_date'] > $report2['assessment_date']) {
            [$report1, $report2] = [$report2, $report1];
        }

        $comparison = [
            'earlier' => [
                'id' => $report1['id'],
                'date' => $report1['assessment_date'],
                'rating' => $report1['overall_rating'],
                'context' => $report1['context'],
            ],
            'later' => [
                'id' => $report2['id'],
                'date' => $report2['assessment_date'],
                'rating' => $report2['overall_rating'],
                'context' => $report2['context'],
            ],
            'rating_change' => $this->calculateRatingChange(
                $report1['overall_rating'],
                $report2['overall_rating']
            ),
            'new_damages' => [],
            'resolved_damages' => [],
            'unchanged_damages' => [],
        ];

        // Compare damages
        $damages1 = $this->indexDamages($report1['damages'] ?? []);
        $damages2 = $this->indexDamages($report2['damages'] ?? []);

        foreach ($damages2 as $key => $damage) {
            if (!isset($damages1[$key])) {
                $comparison['new_damages'][] = $damage;
            } else {
                $comparison['unchanged_damages'][] = $damage;
            }
        }

        foreach ($damages1 as $key => $damage) {
            if (!isset($damages2[$key])) {
                $comparison['resolved_damages'][] = $damage;
            }
        }

        return $comparison;
    }

    /**
     * Generate report from template.
     *
     * @param string $template Template name
     * @param array  $data     Object and report data
     *
     * @return array Template-populated report structure
     */
    public function generateFromTemplate(string $template, array $data): array
    {
        $templates = $this->getTemplates();

        if (!isset($templates[$template])) {
            throw new \InvalidArgumentException("Unknown template: {$template}");
        }

        $templateDef = $templates[$template];

        return [
            'context' => $templateDef['default_context'],
            'sections' => $templateDef['sections'],
            'checklist' => $templateDef['checklist'] ?? [],
            'data' => $data,
        ];
    }

    /**
     * Get available templates.
     */
    public function getTemplates(): array
    {
        return [
            'general' => [
                'name' => 'General Condition Report',
                'description' => 'Standard condition assessment for any object type',
                'default_context' => 'routine',
                'sections' => [
                    'identification' => [
                        'label' => 'Object Identification',
                        'fields' => ['accession_number', 'title', 'object_type', 'materials'],
                    ],
                    'overall_condition' => [
                        'label' => 'Overall Condition',
                        'fields' => ['overall_rating', 'summary'],
                    ],
                    'detailed_examination' => [
                        'label' => 'Detailed Examination',
                        'fields' => ['structural', 'surface', 'support'],
                    ],
                    'damages' => [
                        'label' => 'Damage Observations',
                        'fields' => ['damage_list'],
                    ],
                    'recommendations' => [
                        'label' => 'Recommendations',
                        'fields' => ['treatment', 'handling', 'storage', 'display'],
                    ],
                ],
                'checklist' => [
                    'structural_integrity' => 'Structural integrity checked',
                    'surface_examined' => 'All surfaces examined',
                    'support_checked' => 'Mount/support condition checked',
                    'frame_checked' => 'Frame condition checked (if applicable)',
                    'labels_checked' => 'Labels and markings recorded',
                    'photos_taken' => 'Condition photographs taken',
                ],
            ],

            'painting' => [
                'name' => 'Painting Condition Report',
                'description' => 'Specialized assessment for paintings',
                'default_context' => 'routine',
                'sections' => [
                    'identification' => [
                        'label' => 'Object Identification',
                        'fields' => ['accession_number', 'title', 'artist', 'date', 'medium', 'dimensions'],
                    ],
                    'support' => [
                        'label' => 'Support',
                        'fields' => ['support_type', 'support_condition', 'canvas_tension', 'panel_condition'],
                    ],
                    'ground' => [
                        'label' => 'Ground/Preparation Layer',
                        'fields' => ['ground_type', 'ground_condition', 'adhesion'],
                    ],
                    'paint_layer' => [
                        'label' => 'Paint Layer',
                        'fields' => ['paint_condition', 'flaking', 'cracking', 'losses', 'discoloration'],
                    ],
                    'surface_coating' => [
                        'label' => 'Surface Coating',
                        'fields' => ['varnish_type', 'varnish_condition', 'bloom', 'yellowing'],
                    ],
                    'frame' => [
                        'label' => 'Frame',
                        'fields' => ['frame_type', 'frame_condition', 'glazing'],
                    ],
                    'inscriptions' => [
                        'label' => 'Inscriptions & Labels',
                        'fields' => ['signature', 'labels', 'stamps'],
                    ],
                ],
                'checklist' => [
                    'raking_light' => 'Examined under raking light',
                    'uv_examination' => 'UV examination completed',
                    'transmitted_light' => 'Transmitted light examination (if applicable)',
                    'back_examined' => 'Verso examined',
                    'stretcher_examined' => 'Stretcher/strainer examined',
                    'frame_fit' => 'Frame fit checked',
                ],
            ],

            'paper' => [
                'name' => 'Works on Paper Condition Report',
                'description' => 'Assessment for drawings, prints, photographs, documents',
                'default_context' => 'routine',
                'sections' => [
                    'identification' => [
                        'label' => 'Object Identification',
                        'fields' => ['accession_number', 'title', 'artist', 'date', 'medium', 'dimensions'],
                    ],
                    'support' => [
                        'label' => 'Paper Support',
                        'fields' => ['paper_type', 'paper_weight', 'paper_color', 'watermark'],
                    ],
                    'media' => [
                        'label' => 'Media',
                        'fields' => ['media_type', 'media_condition', 'friability', 'offset'],
                    ],
                    'condition' => [
                        'label' => 'Condition',
                        'fields' => ['tears', 'losses', 'foxing', 'staining', 'folds', 'cockling'],
                    ],
                    'mount' => [
                        'label' => 'Mount/Mat',
                        'fields' => ['mount_type', 'mount_condition', 'adhesive'],
                    ],
                ],
                'checklist' => [
                    'transmitted_light' => 'Examined under transmitted light',
                    'raking_light' => 'Examined under raking light',
                    'uv_examination' => 'UV examination completed',
                    'ph_tested' => 'pH tested (if appropriate)',
                    'mount_adhesion' => 'Mount adhesion checked',
                ],
            ],

            'sculpture' => [
                'name' => 'Sculpture Condition Report',
                'description' => 'Assessment for three-dimensional objects',
                'default_context' => 'routine',
                'sections' => [
                    'identification' => [
                        'label' => 'Object Identification',
                        'fields' => ['accession_number', 'title', 'artist', 'date', 'materials', 'dimensions', 'weight'],
                    ],
                    'structural' => [
                        'label' => 'Structural Condition',
                        'fields' => ['stability', 'joins', 'cracks', 'breaks', 'repairs'],
                    ],
                    'surface' => [
                        'label' => 'Surface',
                        'fields' => ['patina', 'corrosion', 'coating', 'wear', 'accretions'],
                    ],
                    'base' => [
                        'label' => 'Base/Mount',
                        'fields' => ['base_type', 'base_condition', 'attachment'],
                    ],
                ],
                'checklist' => [
                    'all_surfaces' => 'All surfaces examined',
                    'stability_tested' => 'Structural stability verified',
                    'base_secure' => 'Base attachment secure',
                    'moving_parts' => 'Moving parts checked (if applicable)',
                    'weight_verified' => 'Weight verified',
                ],
            ],

            'loan' => [
                'name' => 'Loan Condition Report',
                'description' => 'Pre/post-loan condition assessment (Spectrum 5.0)',
                'default_context' => 'loan_out',
                'sections' => [
                    'loan_details' => [
                        'label' => 'Loan Information',
                        'fields' => ['loan_number', 'borrower', 'exhibition', 'venue', 'dates'],
                    ],
                    'identification' => [
                        'label' => 'Object Identification',
                        'fields' => ['accession_number', 'title', 'artist', 'medium', 'dimensions', 'insurance_value'],
                    ],
                    'condition_summary' => [
                        'label' => 'Condition Summary',
                        'fields' => ['overall_rating', 'summary', 'comparison_to_previous'],
                    ],
                    'detailed_condition' => [
                        'label' => 'Detailed Condition',
                        'fields' => ['damage_list', 'annotations_on_image'],
                    ],
                    'requirements' => [
                        'label' => 'Handling & Display Requirements',
                        'fields' => ['handling', 'environmental', 'lighting', 'security', 'mount'],
                    ],
                    'signatures' => [
                        'label' => 'Sign-Off',
                        'fields' => ['examined_by', 'date', 'received_by', 'witness'],
                    ],
                ],
                'checklist' => [
                    'compared_previous' => 'Compared with previous condition report',
                    'all_damage_documented' => 'All existing damage documented',
                    'photos_taken' => 'Condition photographs taken',
                    'handling_requirements' => 'Handling requirements specified',
                    'environmental_requirements' => 'Environmental requirements specified',
                    'insurance_confirmed' => 'Insurance value confirmed',
                ],
            ],
        ];
    }

    /**
     * Get condition ratings for dropdown (from database).
     */
    public function getRatings(): array
    {
        $terms = $this->taxonomyService->getConditionGradesWithColors();
        if (empty($terms)) {
            return self::RATINGS; // Fallback
        }

        $ratings = [];
        foreach ($terms as $code => $term) {
            $ratings[$code] = [
                'value' => $term->sort_order ?? 0,
                'label' => $term->name,
                'color' => $term->color ?? '#9e9e9e',
            ];
        }

        return $ratings;
    }

    /**
     * Get damage types for dropdown (from database).
     */
    public function getDamageTypes(): array
    {
        $terms = $this->taxonomyService->getDamageTypesWithAttributes();
        if (empty($terms)) {
            return self::DAMAGE_TYPES; // Fallback
        }

        $types = [];
        foreach ($terms as $code => $term) {
            $metadata = $term->metadata ? json_decode($term->metadata, true) : [];
            $types[$code] = [
                'category' => $metadata['category'] ?? 'other',
                'label' => $term->name,
            ];
        }

        return $types;
    }

    /**
     * Get damage types grouped by category (from database).
     */
    public function getDamageTypesByCategory(): array
    {
        $damageTypes = $this->getDamageTypes();
        $grouped = [];

        foreach ($damageTypes as $key => $type) {
            $category = $type['category'];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][$key] = $type['label'];
        }

        return $grouped;
    }

    /**
     * Get contexts for dropdown.
     */
    public function getContexts(): array
    {
        return self::CONTEXTS;
    }

    /**
     * Get locations for dropdown.
     */
    public function getLocations(): array
    {
        return self::LOCATIONS;
    }

    /**
     * Get objects due for condition check.
     *
     * @param int $days Check objects due within this many days
     *
     * @return array Objects needing attention
     */
    public function getObjectsDueForCheck(int $days = 30): array
    {
        $cutoffDate = date('Y-m-d', strtotime("+{$days} days"));

        return $this->db->table('condition_report as cr')
            ->join('information_object as io', 'io.id', '=', 'cr.information_object_id')
            ->whereNotNull('cr.next_check_date')
            ->where('cr.next_check_date', '<=', $cutoffDate)
            ->whereRaw('cr.id = (SELECT MAX(id) FROM condition_report WHERE information_object_id = cr.information_object_id)')
            ->select('io.id', 'io.identifier', 'cr.next_check_date', 'cr.overall_rating', 'cr.priority')
            ->orderBy('cr.next_check_date')
            ->get()
            ->all();
    }

    /**
     * Get objects by condition rating.
     *
     * @param string $rating Rating key
     *
     * @return array Objects with that rating
     */
    public function getObjectsByRating(string $rating): array
    {
        return $this->db->table('condition_report as cr')
            ->join('information_object as io', 'io.id', '=', 'cr.information_object_id')
            ->where('cr.overall_rating', $rating)
            ->whereRaw('cr.id = (SELECT MAX(id) FROM condition_report WHERE information_object_id = cr.information_object_id)')
            ->select('io.id', 'io.identifier', 'cr.assessment_date', 'cr.summary')
            ->orderByDesc('cr.assessment_date')
            ->get()
            ->all();
    }

    /**
     * Get condition statistics.
     */
    public function getStatistics(): array
    {
        $stats = [
            'by_rating' => [],
            'by_context' => [],
            'total_reports' => 0,
            'objects_assessed' => 0,
            'due_for_check' => 0,
        ];

        // Count by rating (latest report per object)
        $byRating = $this->db->table('condition_report')
            ->selectRaw('overall_rating, COUNT(DISTINCT information_object_id) as count')
            ->whereRaw('id IN (SELECT MAX(id) FROM condition_report GROUP BY information_object_id)')
            ->groupBy('overall_rating')
            ->get();

        foreach ($byRating as $row) {
            $stats['by_rating'][$row->overall_rating] = $row->count;
        }

        // Count by context
        $byContext = $this->db->table('condition_report')
            ->selectRaw('context, COUNT(*) as count')
            ->groupBy('context')
            ->get();

        foreach ($byContext as $row) {
            $stats['by_context'][$row->context] = $row->count;
        }

        // Totals
        $stats['total_reports'] = $this->db->table('condition_report')->count();
        $stats['objects_assessed'] = $this->db->table('condition_report')
            ->distinct('information_object_id')
            ->count('information_object_id');
        $stats['due_for_check'] = count($this->getObjectsDueForCheck(30));

        return $stats;
    }

    /**
     * Calculate rating change direction.
     */
    private function calculateRatingChange(string $rating1, string $rating2): array
    {
        $value1 = self::RATINGS[$rating1]['value'] ?? 0;
        $value2 = self::RATINGS[$rating2]['value'] ?? 0;

        $diff = $value2 - $value1;

        if ($diff > 0) {
            return ['direction' => 'deteriorated', 'change' => $diff];
        } elseif ($diff < 0) {
            return ['direction' => 'improved', 'change' => abs($diff)];
        }

        return ['direction' => 'unchanged', 'change' => 0];
    }

    /**
     * Index damages by type+location for comparison.
     */
    private function indexDamages(array $damages): array
    {
        $indexed = [];

        foreach ($damages as $damage) {
            $key = $damage['damage_type'].'_'.$damage['location'];
            $indexed[$key] = $damage;
        }

        return $indexed;
    }
}
