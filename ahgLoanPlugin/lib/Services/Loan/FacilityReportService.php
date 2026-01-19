<?php

declare(strict_types=1);

namespace AhgLoan\Services\Loan;

use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Facility Report Service.
 *
 * Manages pre-loan facility assessments for borrower venues.
 * Based on Spectrum 5.0 standards for venue suitability assessment.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class FacilityReportService
{
    /** Facility ratings */
    public const RATINGS = [
        'excellent' => 'Excellent - Exceeds all requirements',
        'good' => 'Good - Meets all requirements',
        'acceptable' => 'Acceptable - Meets minimum requirements',
        'marginal' => 'Marginal - Requires improvements',
        'unacceptable' => 'Unacceptable - Cannot proceed',
    ];

    /** Fire suppression types */
    public const FIRE_SUPPRESSION_TYPES = [
        'sprinkler' => 'Sprinkler System',
        'gas' => 'Gas Suppression (FM-200/Novec)',
        'foam' => 'Foam System',
        'dry_chemical' => 'Dry Chemical',
        'water_mist' => 'Water Mist',
        'none' => 'None/Extinguishers Only',
    ];

    /** Image types */
    public const IMAGE_TYPES = [
        'exterior' => 'Building Exterior',
        'interior' => 'Interior/Gallery Space',
        'display_area' => 'Display Area',
        'storage' => 'Storage Area',
        'security' => 'Security Systems',
        'climate_control' => 'Climate Control Equipment',
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
     * Create a new facility report.
     *
     * @param int   $loanId Loan ID
     * @param array $data   Report data
     * @param int   $userId Creating user ID
     *
     * @return int Report ID
     */
    public function create(int $loanId, array $data, int $userId): int
    {
        $reportId = $this->db->table('loan_facility_report')->insertGetId([
            'loan_id' => $loanId,
            'venue_name' => $data['venue_name'],
            'venue_address' => $data['venue_address'] ?? null,
            'venue_contact_name' => $data['venue_contact_name'] ?? null,
            'venue_contact_email' => $data['venue_contact_email'] ?? null,
            'venue_contact_phone' => $data['venue_contact_phone'] ?? null,
            'assessment_date' => $data['assessment_date'] ?? date('Y-m-d'),
            'assessed_by' => $userId,

            // Environmental
            'has_climate_control' => $data['has_climate_control'] ?? false,
            'temperature_min' => $data['temperature_min'] ?? null,
            'temperature_max' => $data['temperature_max'] ?? null,
            'humidity_min' => $data['humidity_min'] ?? null,
            'humidity_max' => $data['humidity_max'] ?? null,
            'has_uv_filtering' => $data['has_uv_filtering'] ?? false,
            'light_levels_lux' => $data['light_levels_lux'] ?? null,

            // Security
            'has_24hr_security' => $data['has_24hr_security'] ?? false,
            'has_cctv' => $data['has_cctv'] ?? false,
            'has_alarm_system' => $data['has_alarm_system'] ?? false,
            'has_fire_suppression' => $data['has_fire_suppression'] ?? false,
            'fire_suppression_type' => $data['fire_suppression_type'] ?? null,
            'security_notes' => $data['security_notes'] ?? null,

            // Display/Storage
            'display_case_type' => $data['display_case_type'] ?? null,
            'mounting_method' => $data['mounting_method'] ?? null,
            'barrier_distance' => $data['barrier_distance'] ?? null,
            'storage_type' => $data['storage_type'] ?? null,

            // Access
            'public_access_hours' => $data['public_access_hours'] ?? null,
            'staff_supervision' => $data['staff_supervision'] ?? true,
            'photography_allowed' => $data['photography_allowed'] ?? true,

            // Assessment
            'overall_rating' => $data['overall_rating'] ?? 'acceptable',
            'recommendations' => $data['recommendations'] ?? null,
            'conditions_required' => $data['conditions_required'] ?? null,

            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->logger->info('Facility report created', [
            'report_id' => $reportId,
            'loan_id' => $loanId,
            'venue' => $data['venue_name'],
        ]);

        return $reportId;
    }

    /**
     * Get facility report by ID.
     */
    public function get(int $reportId): ?array
    {
        $report = $this->db->table('loan_facility_report')
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
     * Get facility reports for a loan.
     */
    public function getForLoan(int $loanId): array
    {
        return $this->db->table('loan_facility_report')
            ->where('loan_id', $loanId)
            ->orderByDesc('assessment_date')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Update facility report.
     */
    public function update(int $reportId, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        unset($data['id'], $data['loan_id'], $data['created_at']);

        return $this->db->table('loan_facility_report')
            ->where('id', $reportId)
            ->update($data) > 0;
    }

    /**
     * Approve facility report.
     */
    public function approve(int $reportId, int $userId): bool
    {
        return $this->db->table('loan_facility_report')
            ->where('id', $reportId)
            ->update([
                'approved' => true,
                'approved_by' => $userId,
                'approved_date' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Add image to facility report.
     */
    public function addImage(int $reportId, string $filePath, array $metadata = []): int
    {
        return $this->db->table('loan_facility_image')->insertGetId([
            'facility_report_id' => $reportId,
            'file_path' => $filePath,
            'file_name' => $metadata['file_name'] ?? basename($filePath),
            'mime_type' => $metadata['mime_type'] ?? null,
            'caption' => $metadata['caption'] ?? null,
            'image_type' => $metadata['image_type'] ?? 'other',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get images for a facility report.
     */
    public function getImages(int $reportId): array
    {
        return $this->db->table('loan_facility_image')
            ->where('facility_report_id', $reportId)
            ->orderBy('created_at')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Delete image.
     */
    public function deleteImage(int $imageId): bool
    {
        $image = $this->db->table('loan_facility_image')
            ->where('id', $imageId)
            ->first();

        if ($image && file_exists($image->file_path)) {
            unlink($image->file_path);
        }

        return $this->db->table('loan_facility_image')
            ->where('id', $imageId)
            ->delete() > 0;
    }

    /**
     * Delete facility report.
     */
    public function delete(int $reportId): bool
    {
        // Delete images first
        $images = $this->getImages($reportId);
        foreach ($images as $image) {
            if (file_exists($image['file_path'])) {
                unlink($image['file_path']);
            }
        }

        return $this->db->table('loan_facility_report')
            ->where('id', $reportId)
            ->delete() > 0;
    }

    /**
     * Calculate facility score based on criteria.
     *
     * @return array Score breakdown and total
     */
    public function calculateScore(int $reportId): array
    {
        $report = $this->get($reportId);
        if (!$report) {
            return ['total' => 0, 'max' => 100, 'breakdown' => []];
        }

        $breakdown = [];
        $total = 0;

        // Environmental (30 points max)
        $envScore = 0;
        if ($report['has_climate_control']) {
            $envScore += 15;
        }
        if ($report['has_uv_filtering']) {
            $envScore += 10;
        }
        if ($report['light_levels_lux'] && $report['light_levels_lux'] <= 150) {
            $envScore += 5;
        }
        $breakdown['environmental'] = ['score' => $envScore, 'max' => 30];
        $total += $envScore;

        // Security (30 points max)
        $secScore = 0;
        if ($report['has_24hr_security']) {
            $secScore += 10;
        }
        if ($report['has_cctv']) {
            $secScore += 8;
        }
        if ($report['has_alarm_system']) {
            $secScore += 7;
        }
        if ($report['has_fire_suppression']) {
            $secScore += 5;
        }
        $breakdown['security'] = ['score' => $secScore, 'max' => 30];
        $total += $secScore;

        // Display (20 points max)
        $displayScore = 0;
        if ($report['display_case_type']) {
            $displayScore += 10;
        }
        if ($report['barrier_distance'] && $report['barrier_distance'] >= 1.0) {
            $displayScore += 5;
        }
        if ($report['staff_supervision']) {
            $displayScore += 5;
        }
        $breakdown['display'] = ['score' => $displayScore, 'max' => 20];
        $total += $displayScore;

        // Documentation (20 points max)
        $docScore = 0;
        $images = $this->getImages($reportId);
        $docScore += min(10, count($images) * 2); // 2 points per image, max 10
        if ($report['recommendations']) {
            $docScore += 5;
        }
        if ($report['conditions_required']) {
            $docScore += 5;
        }
        $breakdown['documentation'] = ['score' => $docScore, 'max' => 20];
        $total += $docScore;

        return [
            'total' => $total,
            'max' => 100,
            'percentage' => round(($total / 100) * 100),
            'breakdown' => $breakdown,
            'recommended_rating' => $this->scoreToRating($total),
        ];
    }

    /**
     * Convert score to rating recommendation.
     */
    private function scoreToRating(int $score): string
    {
        if ($score >= 85) {
            return 'excellent';
        }
        if ($score >= 70) {
            return 'good';
        }
        if ($score >= 55) {
            return 'acceptable';
        }
        if ($score >= 40) {
            return 'marginal';
        }

        return 'unacceptable';
    }

    /**
     * Get ratings for dropdown.
     */
    public function getRatings(): array
    {
        return self::RATINGS;
    }

    /**
     * Get fire suppression types for dropdown.
     */
    public function getFireSuppressionTypes(): array
    {
        return self::FIRE_SUPPRESSION_TYPES;
    }

    /**
     * Get image types for dropdown.
     */
    public function getImageTypes(): array
    {
        return self::IMAGE_TYPES;
    }
}
