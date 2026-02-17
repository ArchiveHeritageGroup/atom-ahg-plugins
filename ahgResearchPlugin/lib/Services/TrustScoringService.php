<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * TrustScoringService - Source Assessment & Composite Trust Scoring
 *
 * Manages source assessments, quality metrics, and computes weighted
 * composite trust scores for archival objects.
 *
 * @package ahgResearchPlugin
 * @version 2.1.0
 */
class TrustScoringService
{
    /**
     * Source type weights for trust score calculation.
     */
    private const SOURCE_TYPE_WEIGHTS = [
        'primary' => 40,
        'secondary' => 25,
        'tertiary' => 10,
    ];

    /**
     * Completeness weights for trust score calculation.
     */
    private const COMPLETENESS_WEIGHTS = [
        'complete' => 30,
        'partial' => 20,
        'fragment' => 10,
        'missing_pages' => 15,
        'redacted' => 15,
    ];

    /**
     * Maximum points from quality metrics (normalized).
     */
    private const QUALITY_METRIC_MAX = 30;

    /**
     * Assess a source (create or update assessment).
     *
     * @param int   $objectId     The archival object ID
     * @param int   $researcherId The researcher performing the assessment
     * @param array $data         Assessment data (source_type, source_form, completeness,
     *                            trust_score, rationale, bias_context)
     *
     * @return int The assessment ID
     */
    public function assessSource(int $objectId, int $researcherId, array $data): int
    {
        // Check for existing assessment by same researcher on same object
        $existing = DB::table('research_source_assessment')
            ->where('object_id', $objectId)
            ->where('researcher_id', $researcherId)
            ->first();

        $now = date('Y-m-d H:i:s');

        $record = [
            'object_id' => $objectId,
            'researcher_id' => $researcherId,
            'source_type' => $data['source_type'] ?? 'primary',
            'source_form' => $data['source_form'] ?? 'original',
            'completeness' => $data['completeness'] ?? 'complete',
            'trust_score' => $data['trust_score'] ?? null,
            'rationale' => $data['rationale'] ?? null,
            'bias_context' => $data['bias_context'] ?? null,
            'assessed_at' => $now,
        ];

        if ($existing) {
            DB::table('research_source_assessment')
                ->where('id', $existing->id)
                ->update($record);

            return $existing->id;
        }

        return DB::table('research_source_assessment')->insertGetId($record);
    }

    /**
     * Get assessment for an object, optionally filtered by researcher.
     *
     * @param int      $objectId     The archival object ID
     * @param int|null $researcherId Optional researcher filter
     *
     * @return object|null The assessment with assessor name, or null
     */
    public function getAssessment(int $objectId, ?int $researcherId = null): ?object
    {
        $query = DB::table('research_source_assessment as sa')
            ->join('research_researcher as r', 'sa.researcher_id', '=', 'r.id')
            ->where('sa.object_id', $objectId)
            ->select(
                'sa.*',
                'r.first_name as assessor_first_name',
                'r.last_name as assessor_last_name'
            );

        if ($researcherId !== null) {
            $query->where('sa.researcher_id', $researcherId);
        }

        return $query->orderBy('sa.assessed_at', 'desc')->first();
    }

    /**
     * Get all assessments for an object (assessment history).
     *
     * @param int $objectId The archival object ID
     *
     * @return array List of assessments with assessor names, newest first
     */
    public function getAssessmentHistory(int $objectId): array
    {
        return DB::table('research_source_assessment as sa')
            ->join('research_researcher as r', 'sa.researcher_id', '=', 'r.id')
            ->where('sa.object_id', $objectId)
            ->select(
                'sa.*',
                'r.first_name as assessor_first_name',
                'r.last_name as assessor_last_name'
            )
            ->orderBy('sa.assessed_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Add a quality metric for an object.
     *
     * @param int        $objectId The archival object ID
     * @param string     $type     Metric type (ocr_confidence, image_quality,
     *                             digitisation_completeness, fixity_status)
     * @param float      $value    Metric value (0.0 - 1.0 scale)
     * @param string     $service  Source service that produced the metric
     * @param array|null $raw      Optional raw data from the service
     *
     * @return int The quality metric ID
     */
    public function addQualityMetric(int $objectId, string $type, float $value, string $service, ?array $raw = null): int
    {
        return DB::table('research_quality_metric')->insertGetId([
            'object_id' => $objectId,
            'metric_type' => $type,
            'metric_value' => $value,
            'source_service' => $service,
            'raw_data_json' => $raw !== null ? json_encode($raw) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get all quality metrics for an object.
     *
     * @param int $objectId The archival object ID
     *
     * @return array List of quality metrics, newest first
     */
    public function getQualityMetrics(int $objectId): array
    {
        return DB::table('research_quality_metric')
            ->where('object_id', $objectId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Compute a composite trust score (0-100) for an object.
     *
     * Weighted calculation across three dimensions:
     * - Source type weight (max 40 points): primary=40, secondary=25, tertiary=10
     * - Completeness weight (max 30 points): complete=30, partial=20, fragment=10,
     *   missing_pages=15, redacted=15
     * - Quality metrics average (max 30 points): mean of all metric values,
     *   normalized to 0-30 scale (metrics are expected on a 0.0-1.0 scale)
     *
     * If no assessment exists, returns 0. If no quality metrics exist,
     * the quality dimension contributes 0 points.
     *
     * @param int $objectId The archival object ID
     *
     * @return int Composite trust score clamped to 0-100
     */
    public function computeTrustScore(int $objectId): int
    {
        // Get the latest assessment for the object
        $assessment = DB::table('research_source_assessment')
            ->where('object_id', $objectId)
            ->orderBy('assessed_at', 'desc')
            ->first();

        if (!$assessment) {
            return 0;
        }

        // Source type weight (max 40 points)
        $sourceWeight = self::SOURCE_TYPE_WEIGHTS[$assessment->source_type] ?? 0;

        // Completeness weight (max 30 points)
        $completenessWeight = self::COMPLETENESS_WEIGHTS[$assessment->completeness] ?? 0;

        // Quality metrics average, normalized to max 30 points
        $qualityScore = 0;
        $metrics = DB::table('research_quality_metric')
            ->where('object_id', $objectId)
            ->get();

        if ($metrics->count() > 0) {
            $sum = 0;
            foreach ($metrics as $metric) {
                // Metric values are on a 0.0-1.0 scale
                $sum += (float) $metric->metric_value;
            }
            $average = $sum / $metrics->count();

            // Clamp the average to 0.0-1.0 before normalizing
            $average = max(0.0, min(1.0, $average));

            // Normalize to 0-30 points
            $qualityScore = (int) round($average * self::QUALITY_METRIC_MAX);
        }

        // Composite score
        $composite = $sourceWeight + $completenessWeight + $qualityScore;

        // Clamp to 0-100
        return max(0, min(100, $composite));
    }

    /**
     * Update an existing assessment's trust score.
     *
     * @param int    $assessmentId The assessment ID
     * @param int    $score        New trust score (0-100)
     * @param string $rationale    Updated rationale for the score
     *
     * @return bool True if the assessment was updated
     */
    public function updateTrustScore(int $assessmentId, int $score, string $rationale): bool
    {
        return DB::table('research_source_assessment')
            ->where('id', $assessmentId)
            ->update([
                'trust_score' => max(0, min(100, $score)),
                'rationale' => $rationale,
                'assessed_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Delete an assessment.
     *
     * @param int $id The assessment ID
     *
     * @return bool True if the assessment was deleted
     */
    public function deleteAssessment(int $id): bool
    {
        return DB::table('research_source_assessment')
            ->where('id', $id)
            ->delete() > 0;
    }
}
