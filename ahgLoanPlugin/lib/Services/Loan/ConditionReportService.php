<?php

declare(strict_types=1);

namespace AhgLoan\Services\Loan;

use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Condition Report Service.
 *
 * Manages condition documentation for loan objects.
 * Supports pre-loan and post-loan condition assessments with image documentation.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ConditionReportService
{
    /** Report types */
    public const REPORT_TYPES = [
        'pre_loan' => 'Pre-Loan Examination',
        'post_loan' => 'Post-Loan Examination',
        'in_transit' => 'Transit Inspection',
        'periodic' => 'Periodic Check',
    ];

    /** Condition ratings */
    public const CONDITIONS = [
        'excellent' => 'Excellent - No visible issues',
        'good' => 'Good - Minor wear consistent with age',
        'fair' => 'Fair - Some visible issues, stable',
        'poor' => 'Poor - Significant issues, requires attention',
        'critical' => 'Critical - Immediate treatment required',
    ];

    /** Image types */
    public const IMAGE_TYPES = [
        'overall' => 'Overall View',
        'detail' => 'Detail Shot',
        'damage' => 'Damage Documentation',
        'measurement' => 'Measurement Reference',
        'comparison' => 'Before/After Comparison',
        'other' => 'Other',
    ];

    /** View positions */
    public const VIEW_POSITIONS = [
        'front' => 'Front',
        'back' => 'Back/Verso',
        'top' => 'Top',
        'bottom' => 'Bottom',
        'left' => 'Left Side',
        'right' => 'Right Side',
        'detail' => 'Detail',
        'other' => 'Other',
    ];

    private ConnectionInterface $db;
    private LoggerInterface $logger;

    public function __construct(ConnectionInterface $db, ?LoggerInterface $logger = null)
    {
        $this->db = $db;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Create a new condition report.
     *
     * @param int   $loanId       Loan ID
     * @param array $data         Report data
     * @param int   $examinerId   Examiner user ID
     * @param int   $loanObjectId Optional specific loan object
     *
     * @return int Report ID
     */
    public function create(int $loanId, array $data, int $examinerId, ?int $loanObjectId = null): int
    {
        $reportId = $this->db->table('loan_condition_report')->insertGetId([
            'loan_id' => $loanId,
            'loan_object_id' => $loanObjectId,
            'information_object_id' => $data['information_object_id'] ?? null,
            'report_type' => $data['report_type'] ?? 'pre_loan',
            'examination_date' => $data['examination_date'] ?? date('Y-m-d H:i:s'),
            'examiner_id' => $examinerId,
            'examiner_name' => $data['examiner_name'] ?? null,
            'location' => $data['location'] ?? null,

            // Condition
            'overall_condition' => $data['overall_condition'] ?? 'good',
            'condition_stable' => $data['condition_stable'] ?? true,
            'structural_condition' => $data['structural_condition'] ?? null,
            'surface_condition' => $data['surface_condition'] ?? null,

            // Issues
            'has_damage' => $data['has_damage'] ?? false,
            'damage_description' => $data['damage_description'] ?? null,
            'has_previous_repairs' => $data['has_previous_repairs'] ?? false,
            'repair_description' => $data['repair_description'] ?? null,
            'has_active_deterioration' => $data['has_active_deterioration'] ?? false,
            'deterioration_description' => $data['deterioration_description'] ?? null,

            // Measurements
            'height_cm' => $data['height_cm'] ?? null,
            'width_cm' => $data['width_cm'] ?? null,
            'depth_cm' => $data['depth_cm'] ?? null,
            'weight_kg' => $data['weight_kg'] ?? null,

            // Requirements
            'handling_requirements' => $data['handling_requirements'] ?? null,
            'mounting_requirements' => $data['mounting_requirements'] ?? null,
            'environmental_requirements' => $data['environmental_requirements'] ?? null,

            // Recommendations
            'treatment_recommendations' => $data['treatment_recommendations'] ?? null,
            'display_recommendations' => $data['display_recommendations'] ?? null,

            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->logger->info('Condition report created', [
            'report_id' => $reportId,
            'loan_id' => $loanId,
            'type' => $data['report_type'] ?? 'pre_loan',
        ]);

        return $reportId;
    }

    /**
     * Get condition report by ID.
     */
    public function get(int $reportId): ?array
    {
        $report = $this->db->table('loan_condition_report')
            ->where('id', $reportId)
            ->first();

        if (!$report) {
            return null;
        }

        $data = (array) $report;
        $data['images'] = $this->getImages($reportId);

        return $data;
    }

    /**
     * Get condition reports for a loan.
     */
    public function getForLoan(int $loanId): array
    {
        return $this->db->table('loan_condition_report')
            ->where('loan_id', $loanId)
            ->orderByDesc('examination_date')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Get condition reports for a specific loan object.
     */
    public function getForObject(int $loanObjectId): array
    {
        return $this->db->table('loan_condition_report')
            ->where('loan_object_id', $loanObjectId)
            ->orderByDesc('examination_date')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Update condition report.
     */
    public function update(int $reportId, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        unset($data['id'], $data['loan_id'], $data['created_at']);

        return $this->db->table('loan_condition_report')
            ->where('id', $reportId)
            ->update($data) > 0;
    }

    /**
     * Sign report as lender.
     */
    public function signAsLender(int $reportId, int $userId): bool
    {
        return $this->db->table('loan_condition_report')
            ->where('id', $reportId)
            ->update([
                'signed_by_lender' => $userId,
                'lender_signature_date' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Sign report as borrower.
     */
    public function signAsBorrower(int $reportId, int $userId): bool
    {
        return $this->db->table('loan_condition_report')
            ->where('id', $reportId)
            ->update([
                'signed_by_borrower' => $userId,
                'borrower_signature_date' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Add image to condition report.
     */
    public function addImage(int $reportId, string $filePath, array $metadata = []): int
    {
        $maxOrder = $this->db->table('loan_condition_image')
            ->where('condition_report_id', $reportId)
            ->max('sort_order') ?? 0;

        return $this->db->table('loan_condition_image')->insertGetId([
            'condition_report_id' => $reportId,
            'file_path' => $filePath,
            'file_name' => $metadata['file_name'] ?? basename($filePath),
            'mime_type' => $metadata['mime_type'] ?? null,
            'image_type' => $metadata['image_type'] ?? 'overall',
            'caption' => $metadata['caption'] ?? null,
            'annotation_data' => isset($metadata['annotation_data']) ? json_encode($metadata['annotation_data']) : null,
            'view_position' => $metadata['view_position'] ?? 'front',
            'sort_order' => $maxOrder + 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get images for a condition report.
     */
    public function getImages(int $reportId): array
    {
        return $this->db->table('loan_condition_image')
            ->where('condition_report_id', $reportId)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($r) {
                $data = (array) $r;
                if ($data['annotation_data']) {
                    $data['annotation_data'] = json_decode($data['annotation_data'], true);
                }

                return $data;
            })
            ->all();
    }

    /**
     * Update image metadata.
     */
    public function updateImage(int $imageId, array $data): bool
    {
        if (isset($data['annotation_data']) && is_array($data['annotation_data'])) {
            $data['annotation_data'] = json_encode($data['annotation_data']);
        }

        return $this->db->table('loan_condition_image')
            ->where('id', $imageId)
            ->update($data) > 0;
    }

    /**
     * Reorder images.
     */
    public function reorderImages(int $reportId, array $imageIds): bool
    {
        foreach ($imageIds as $order => $imageId) {
            $this->db->table('loan_condition_image')
                ->where('id', $imageId)
                ->where('condition_report_id', $reportId)
                ->update(['sort_order' => $order + 1]);
        }

        return true;
    }

    /**
     * Delete image.
     */
    public function deleteImage(int $imageId): bool
    {
        $image = $this->db->table('loan_condition_image')
            ->where('id', $imageId)
            ->first();

        if ($image && file_exists($image->file_path)) {
            unlink($image->file_path);
        }

        return $this->db->table('loan_condition_image')
            ->where('id', $imageId)
            ->delete() > 0;
    }

    /**
     * Delete condition report.
     */
    public function delete(int $reportId): bool
    {
        $images = $this->getImages($reportId);
        foreach ($images as $image) {
            if (file_exists($image['file_path'])) {
                unlink($image['file_path']);
            }
        }

        return $this->db->table('loan_condition_report')
            ->where('id', $reportId)
            ->delete() > 0;
    }

    /**
     * Compare two condition reports.
     *
     * @return array Comparison results with changes highlighted
     */
    public function compare(int $reportId1, int $reportId2): array
    {
        $report1 = $this->get($reportId1);
        $report2 = $this->get($reportId2);

        if (!$report1 || !$report2) {
            return ['error' => 'One or both reports not found'];
        }

        $changes = [];
        $fieldsToCompare = [
            'overall_condition',
            'condition_stable',
            'structural_condition',
            'surface_condition',
            'has_damage',
            'damage_description',
            'has_active_deterioration',
            'height_cm',
            'width_cm',
            'depth_cm',
            'weight_kg',
        ];

        foreach ($fieldsToCompare as $field) {
            if ($report1[$field] != $report2[$field]) {
                $changes[$field] = [
                    'before' => $report1[$field],
                    'after' => $report2[$field],
                ];
            }
        }

        return [
            'report1' => [
                'id' => $reportId1,
                'type' => $report1['report_type'],
                'date' => $report1['examination_date'],
            ],
            'report2' => [
                'id' => $reportId2,
                'type' => $report2['report_type'],
                'date' => $report2['examination_date'],
            ],
            'changes' => $changes,
            'has_changes' => !empty($changes),
            'condition_changed' => isset($changes['overall_condition']),
            'new_damage' => isset($changes['has_damage']) && $changes['has_damage']['after'],
        ];
    }

    /**
     * Generate PDF export of condition report.
     *
     * @return string PDF file path
     */
    public function generatePdf(int $reportId, string $outputDir): string
    {
        $report = $this->get($reportId);
        if (!$report) {
            throw new \RuntimeException('Report not found');
        }

        // For now, generate HTML that can be converted to PDF
        $html = $this->generateHtml($report);

        $fileName = 'condition_report_'.$report['id'].'_'.date('Ymd').'.html';
        $filePath = $outputDir.'/'.$fileName;

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        file_put_contents($filePath, $html);

        // Update report with PDF path
        $this->update($reportId, [
            'pdf_generated' => true,
            'pdf_path' => $filePath,
        ]);

        return $filePath;
    }

    /**
     * Generate HTML version of condition report.
     */
    private function generateHtml(array $report): string
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Condition Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; border-bottom: 2px solid #333; }
        .section { margin: 20px 0; }
        .label { font-weight: bold; color: #666; }
        .value { margin-left: 10px; }
        .condition-badge { padding: 5px 10px; border-radius: 4px; color: white; }
        .condition-excellent { background: #28a745; }
        .condition-good { background: #17a2b8; }
        .condition-fair { background: #ffc107; color: #333; }
        .condition-poor { background: #fd7e14; }
        .condition-critical { background: #dc3545; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
        .images { display: flex; flex-wrap: wrap; gap: 10px; }
        .image { max-width: 200px; }
        .signature-line { border-top: 1px solid #333; margin-top: 50px; padding-top: 5px; }
    </style>
</head>
<body>
    <h1>Condition Report</h1>

    <div class="section">
        <p><span class="label">Report Type:</span> <span class="value">'.htmlspecialchars(self::REPORT_TYPES[$report['report_type']] ?? $report['report_type']).'</span></p>
        <p><span class="label">Examination Date:</span> <span class="value">'.htmlspecialchars($report['examination_date']).'</span></p>
        <p><span class="label">Examiner:</span> <span class="value">'.htmlspecialchars($report['examiner_name'] ?? 'Unknown').'</span></p>
        <p><span class="label">Location:</span> <span class="value">'.htmlspecialchars($report['location'] ?? 'Not specified').'</span></p>
    </div>

    <div class="section">
        <h2>Condition Assessment</h2>
        <p><span class="label">Overall Condition:</span> <span class="condition-badge condition-'.htmlspecialchars($report['overall_condition']).'">'.ucfirst(htmlspecialchars($report['overall_condition'])).'</span></p>
        <p><span class="label">Condition Stable:</span> <span class="value">'.($report['condition_stable'] ? 'Yes' : 'No').'</span></p>
    </div>';

        if ($report['structural_condition'] || $report['surface_condition']) {
            $html .= '
    <div class="section">
        <h2>Detailed Condition</h2>';
            if ($report['structural_condition']) {
                $html .= '<p><span class="label">Structural:</span></p><p>'.nl2br(htmlspecialchars($report['structural_condition'])).'</p>';
            }
            if ($report['surface_condition']) {
                $html .= '<p><span class="label">Surface:</span></p><p>'.nl2br(htmlspecialchars($report['surface_condition'])).'</p>';
            }
            $html .= '</div>';
        }

        if ($report['has_damage'] || $report['has_previous_repairs'] || $report['has_active_deterioration']) {
            $html .= '
    <div class="section">
        <h2>Issues</h2>';
            if ($report['has_damage']) {
                $html .= '<p><span class="label">Damage:</span></p><p>'.nl2br(htmlspecialchars($report['damage_description'] ?? 'Yes')).'</p>';
            }
            if ($report['has_previous_repairs']) {
                $html .= '<p><span class="label">Previous Repairs:</span></p><p>'.nl2br(htmlspecialchars($report['repair_description'] ?? 'Yes')).'</p>';
            }
            if ($report['has_active_deterioration']) {
                $html .= '<p><span class="label">Active Deterioration:</span></p><p>'.nl2br(htmlspecialchars($report['deterioration_description'] ?? 'Yes')).'</p>';
            }
            $html .= '</div>';
        }

        if ($report['height_cm'] || $report['width_cm'] || $report['depth_cm'] || $report['weight_kg']) {
            $html .= '
    <div class="section">
        <h2>Measurements</h2>
        <table>
            <tr><th>Dimension</th><th>Value</th></tr>';
            if ($report['height_cm']) {
                $html .= '<tr><td>Height</td><td>'.$report['height_cm'].' cm</td></tr>';
            }
            if ($report['width_cm']) {
                $html .= '<tr><td>Width</td><td>'.$report['width_cm'].' cm</td></tr>';
            }
            if ($report['depth_cm']) {
                $html .= '<tr><td>Depth</td><td>'.$report['depth_cm'].' cm</td></tr>';
            }
            if ($report['weight_kg']) {
                $html .= '<tr><td>Weight</td><td>'.$report['weight_kg'].' kg</td></tr>';
            }
            $html .= '</table></div>';
        }

        $html .= '
    <div class="section">
        <h2>Signatures</h2>
        <table>
            <tr>
                <td width="50%">
                    <p class="signature-line">Lender Representative</p>
                    <p>Date: '.($report['lender_signature_date'] ?? '_____________').'</p>
                </td>
                <td width="50%">
                    <p class="signature-line">Borrower Representative</p>
                    <p>Date: '.($report['borrower_signature_date'] ?? '_____________').'</p>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>';

        return $html;
    }

    /**
     * Get report types for dropdown.
     */
    public function getReportTypes(): array
    {
        return self::REPORT_TYPES;
    }

    /**
     * Get conditions for dropdown.
     */
    public function getConditions(): array
    {
        return self::CONDITIONS;
    }

    /**
     * Get image types for dropdown.
     */
    public function getImageTypes(): array
    {
        return self::IMAGE_TYPES;
    }

    /**
     * Get view positions for dropdown.
     */
    public function getViewPositions(): array
    {
        return self::VIEW_POSITIONS;
    }
}
