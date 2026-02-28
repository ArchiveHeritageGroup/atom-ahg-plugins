<?php

namespace AhgAccessionManage\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Accession Appraisal Service
 *
 * Handles appraisal, valuation, and GRAP 103/IPSAS 45 compliance.
 */
class AccessionAppraisalService
{
    /** Appraisal types */
    const TYPE_ARCHIVAL = 'archival';
    const TYPE_MONETARY = 'monetary';
    const TYPE_INSURANCE = 'insurance';
    const TYPE_HISTORICAL = 'historical';
    const TYPE_RESEARCH = 'research';

    /** Significance levels */
    const SIGNIFICANCE_LEVELS = ['low', 'medium', 'high', 'exceptional', 'national_significance'];

    /** Recommendations */
    const RECOMMENDATIONS = ['pending', 'accept', 'reject', 'partial', 'defer'];

    /** Valuation types */
    const VALUATION_INITIAL = 'initial';
    const VALUATION_REVALUATION = 'revaluation';
    const VALUATION_IMPAIRMENT = 'impairment';
    const VALUATION_DISPOSAL = 'disposal';

    /** Valuation methods */
    const VALUATION_METHODS = ['cost', 'market', 'income', 'replacement', 'nominal'];

    protected ?int $tenantId;
    protected ?AccessionIntakeService $intakeService;

    public function __construct(?int $tenantId = null, ?AccessionIntakeService $intakeService = null)
    {
        $this->tenantId = $tenantId;
        $this->intakeService = $intakeService;
    }

    /**
     * Apply tenant_id filter to a query builder instance.
     */
    public function scopeQuery($query)
    {
        if ($this->tenantId !== null) {
            $query->where('tenant_id', $this->tenantId);
        }

        return $query;
    }

    // =========================================================================
    // APPRAISALS
    // =========================================================================

    /**
     * Create an appraisal for an accession.
     */
    public function createAppraisal(int $accessionId, array $data, int $userId): int
    {
        $now = date('Y-m-d H:i:s');

        $id = DB::table('accession_appraisal')->insertGetId([
            'accession_id' => $accessionId,
            'appraiser_id' => $data['appraiser_id'] ?? $userId,
            'appraisal_type' => $data['appraisal_type'] ?? self::TYPE_ARCHIVAL,
            'monetary_value' => $data['monetary_value'] ?? null,
            'currency' => $data['currency'] ?? 'ZAR',
            'significance' => $data['significance'] ?? null,
            'recommendation' => $data['recommendation'] ?? 'pending',
            'summary' => $data['summary'] ?? null,
            'detailed_notes' => $data['detailed_notes'] ?? null,
            'appraised_at' => $data['appraised_at'] ?? $now,
            'tenant_id' => $this->tenantId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($this->intakeService) {
            $this->intakeService->addTimelineEvent(
                $accessionId,
                AccessionIntakeService::EVENT_APPRAISED,
                $userId,
                'Appraisal created: ' . ($data['appraisal_type'] ?? self::TYPE_ARCHIVAL),
                ['appraisal_id' => $id]
            );
        }

        return $id;
    }

    /**
     * Update an appraisal.
     */
    public function updateAppraisal(int $appraisalId, array $data, int $userId): bool
    {
        $appraisal = DB::table('accession_appraisal')->where('id', $appraisalId)->first();
        if (!$appraisal) {
            return false;
        }

        $update = [];
        $fields = [
            'appraiser_id', 'appraisal_type', 'monetary_value', 'currency',
            'significance', 'recommendation', 'summary', 'detailed_notes', 'appraised_at',
        ];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $update[$f] = $data[$f];
            }
        }

        if (!empty($update)) {
            $update['updated_at'] = date('Y-m-d H:i:s');
            DB::table('accession_appraisal')->where('id', $appraisalId)->update($update);
        }

        if ($this->intakeService) {
            $this->intakeService->addTimelineEvent(
                $appraisal->accession_id,
                AccessionIntakeService::EVENT_APPRAISED,
                $userId,
                'Appraisal updated',
                ['appraisal_id' => $appraisalId]
            );
        }

        return true;
    }

    /**
     * Get a single appraisal with its criteria.
     */
    public function getAppraisal(int $appraisalId): ?array
    {
        $appraisal = DB::table('accession_appraisal')->where('id', $appraisalId)->first();
        if (!$appraisal) {
            return null;
        }

        $criteria = DB::table('accession_appraisal_criterion')
            ->where('appraisal_id', $appraisalId)
            ->orderBy('id')
            ->get()
            ->all();

        $appraiserName = null;
        if ($appraisal->appraiser_id) {
            $appraiserName = DB::table('actor_i18n')
                ->where('id', $appraisal->appraiser_id)
                ->where('culture', 'en')
                ->value('authorized_form_of_name');
        }

        return [
            'appraisal' => $appraisal,
            'criteria' => $criteria,
            'appraiser_name' => $appraiserName,
            'weighted_score' => $this->calculateWeightedScore($appraisalId),
        ];
    }

    /**
     * Get all appraisals for an accession.
     */
    public function getAppraisalsForAccession(int $accessionId): array
    {
        $query = DB::table('accession_appraisal')
            ->where('accession_id', $accessionId)
            ->orderBy('created_at', 'desc');

        $this->scopeQuery($query);

        return $query->get()->all();
    }

    // =========================================================================
    // CRITERIA
    // =========================================================================

    /**
     * Add a scoring criterion to an appraisal.
     */
    public function addCriterion(int $appraisalId, array $data): int
    {
        return DB::table('accession_appraisal_criterion')->insertGetId([
            'appraisal_id' => $appraisalId,
            'criterion_name' => $data['criterion_name'],
            'score' => $data['score'] ?? null,
            'weight' => $data['weight'] ?? 1.00,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Update a criterion.
     */
    public function updateCriterion(int $criterionId, array $data): bool
    {
        $update = [];
        foreach (['criterion_name', 'score', 'weight', 'notes'] as $f) {
            if (array_key_exists($f, $data)) {
                $update[$f] = $data[$f];
            }
        }

        if (empty($update)) {
            return false;
        }

        DB::table('accession_appraisal_criterion')
            ->where('id', $criterionId)
            ->update($update);

        return true;
    }

    /**
     * Delete a criterion.
     */
    public function deleteCriterion(int $criterionId): void
    {
        DB::table('accession_appraisal_criterion')->where('id', $criterionId)->delete();
    }

    /**
     * Calculate weighted average score for an appraisal.
     */
    public function calculateWeightedScore(int $appraisalId): ?float
    {
        $criteria = DB::table('accession_appraisal_criterion')
            ->where('appraisal_id', $appraisalId)
            ->whereNotNull('score')
            ->get();

        if ($criteria->isEmpty()) {
            return null;
        }

        $totalWeight = 0;
        $weightedSum = 0;
        foreach ($criteria as $c) {
            $weightedSum += $c->score * $c->weight;
            $totalWeight += $c->weight;
        }

        return $totalWeight > 0 ? round($weightedSum / $totalWeight, 2) : null;
    }

    /**
     * Apply an appraisal template to an appraisal (copies criteria).
     */
    public function applyTemplate(int $appraisalId, int $templateId): int
    {
        $template = DB::table('accession_appraisal_template')
            ->where('id', $templateId)
            ->first();

        if (!$template) {
            return 0;
        }

        $criteria = json_decode($template->criteria, true);
        if (!is_array($criteria)) {
            return 0;
        }

        // Remove existing criteria
        DB::table('accession_appraisal_criterion')
            ->where('appraisal_id', $appraisalId)
            ->delete();

        $count = 0;
        foreach ($criteria as $c) {
            DB::table('accession_appraisal_criterion')->insert([
                'appraisal_id' => $appraisalId,
                'criterion_name' => $c['criterion_name'] ?? '',
                'weight' => $c['weight'] ?? 1.00,
                'notes' => $c['description'] ?? null,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * List appraisal templates, optionally filtered by sector.
     */
    public function listTemplates(?string $sector = null): array
    {
        $query = DB::table('accession_appraisal_template')->orderBy('name');

        if ($sector) {
            $query->where('sector', $sector);
        }

        $this->scopeQuery($query);

        return $query->get()->all();
    }

    // =========================================================================
    // VALUATIONS (GRAP 103 / IPSAS 45)
    // =========================================================================

    /**
     * Record a valuation for an accession.
     */
    public function recordValuation(int $accessionId, array $data, int $userId): int
    {
        $id = DB::table('accession_valuation_history')->insertGetId([
            'accession_id' => $accessionId,
            'valuation_type' => $data['valuation_type'] ?? self::VALUATION_INITIAL,
            'monetary_value' => $data['monetary_value'],
            'currency' => $data['currency'] ?? 'ZAR',
            'valuation_date' => $data['valuation_date'] ?? date('Y-m-d'),
            'valuer' => $data['valuer'] ?? null,
            'method' => $data['method'] ?? null,
            'reference_document' => $data['reference_document'] ?? null,
            'notes' => $data['notes'] ?? null,
            'recorded_by' => $userId,
            'tenant_id' => $this->tenantId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($this->intakeService) {
            $this->intakeService->addTimelineEvent(
                $accessionId,
                AccessionIntakeService::EVENT_NOTE,
                $userId,
                sprintf(
                    'Valuation recorded: %s %s (%s)',
                    $data['currency'] ?? 'ZAR',
                    number_format($data['monetary_value'], 2),
                    $data['valuation_type'] ?? self::VALUATION_INITIAL
                ),
                ['valuation_id' => $id]
            );
        }

        return $id;
    }

    /**
     * Get valuation history for an accession.
     */
    public function getValuationHistory(int $accessionId): array
    {
        $query = DB::table('accession_valuation_history')
            ->where('accession_id', $accessionId)
            ->orderBy('valuation_date', 'desc');

        $this->scopeQuery($query);

        return $query->get()->all();
    }

    /**
     * Get the most recent valuation for an accession.
     */
    public function getCurrentValuation(int $accessionId): ?object
    {
        $query = DB::table('accession_valuation_history')
            ->where('accession_id', $accessionId)
            ->orderBy('valuation_date', 'desc')
            ->orderBy('created_at', 'desc');

        $this->scopeQuery($query);

        return $query->first();
    }

    /**
     * Get aggregate valuation report.
     */
    public function getValuationReport(array $filters = []): array
    {
        // Total portfolio value (latest valuation per accession)
        $latestPerAccession = DB::table('accession_valuation_history as vh1')
            ->select('vh1.*')
            ->whereRaw('vh1.id = (
                SELECT vh2.id FROM accession_valuation_history vh2
                WHERE vh2.accession_id = vh1.accession_id
                ORDER BY vh2.valuation_date DESC, vh2.created_at DESC
                LIMIT 1
            )');

        if ($this->tenantId !== null) {
            $latestPerAccession->where('vh1.tenant_id', $this->tenantId);
        }

        if (!empty($filters['repository_id'])) {
            $latestPerAccession->join('accession as a', 'vh1.accession_id', '=', 'a.id');
            // Filter by repository via relation table
        }

        $all = $latestPerAccession->get();

        $totalValue = 0;
        $byCurrency = [];
        $byType = [];
        $count = 0;

        foreach ($all as $v) {
            $totalValue += (float) $v->monetary_value;
            $cur = $v->currency ?? 'ZAR';
            $byCurrency[$cur] = ($byCurrency[$cur] ?? 0) + (float) $v->monetary_value;
            $type = $v->valuation_type ?? 'unknown';
            $byType[$type] = ($byType[$type] ?? 0) + 1;
            $count++;
        }

        return [
            'total_value' => $totalValue,
            'by_currency' => $byCurrency,
            'by_type' => $byType,
            'accession_count' => $count,
        ];
    }

    // =========================================================================
    // CLEANUP
    // =========================================================================

    /**
     * Delete all appraisal and valuation data for an accession.
     */
    public function deleteAllForAccession(int $accessionId): void
    {
        $appraisalIds = DB::table('accession_appraisal')
            ->where('accession_id', $accessionId)
            ->pluck('id')
            ->all();

        foreach ($appraisalIds as $apId) {
            DB::table('accession_appraisal_criterion')
                ->where('appraisal_id', $apId)
                ->delete();
        }

        DB::table('accession_appraisal')->where('accession_id', $accessionId)->delete();
        DB::table('accession_valuation_history')->where('accession_id', $accessionId)->delete();
    }
}
