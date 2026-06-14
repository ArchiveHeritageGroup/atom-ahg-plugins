<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * EU AI Act governance registers — AI system inventory, model registry,
 * Article 9 risk register, and conformity / human-oversight attestations.
 *
 * Complements the Article 12 inference receipt chain (InferenceLogger) with the
 * broader AI Act obligations. Pure CRUD + dashboard rollups over the
 * ai_act_system / ai_act_model / ai_act_risk / ai_act_attestation tables.
 */
class AiActGovernanceService
{
    // ---- controlled vocabularies (kept here, not as DB ENUMs) -------------

    public const RISK_CLASSIFICATIONS = ['prohibited', 'high', 'limited', 'minimal'];
    public const SYSTEM_ROLES = ['provider', 'deployer', 'importer', 'distributor'];
    public const LIFECYCLE_STATUSES = ['development', 'deployed', 'suspended', 'retired'];
    public const MODALITIES = ['text', 'vision', 'audio', 'multimodal', 'embedding', 'other'];
    public const RISK_CATEGORIES = ['safety', 'fundamental_rights', 'bias_discrimination', 'privacy', 'security', 'transparency', 'accuracy', 'other'];
    public const RISK_STATUSES = ['open', 'mitigating', 'accepted', 'closed'];
    public const ATTESTATION_TYPES = ['conformity_declaration', 'human_oversight', 'risk_management', 'data_governance', 'technical_documentation', 'transparency', 'other'];
    public const ATTESTATION_STATUSES = ['draft', 'attested', 'expired', 'revoked'];

    // =======================================================================
    // AI system inventory
    // =======================================================================

    public function listSystems(): array
    {
        return DB::table('ai_act_system')->orderBy('name')->get()->all();
    }

    public function getSystem(int $id): ?object
    {
        return DB::table('ai_act_system')->where('id', $id)->first();
    }

    /** @return array<int,string> id => name, for dropdowns */
    public function systemOptions(): array
    {
        $out = [];
        foreach (DB::table('ai_act_system')->orderBy('name')->get(['id', 'name']) as $r) {
            $out[(int) $r->id] = (string) $r->name;
        }

        return $out;
    }

    public function saveSystem(array $data, ?int $id = null): int
    {
        $row = [
            'name' => trim((string) ($data['name'] ?? '')),
            'description' => $this->nullable($data['description'] ?? null),
            'purpose' => $this->nullable($data['purpose'] ?? null),
            'provider' => $this->nullable($data['provider'] ?? null),
            'role' => $this->oneOf($data['role'] ?? '', self::SYSTEM_ROLES, 'deployer'),
            'risk_classification' => $this->oneOf($data['risk_classification'] ?? '', self::RISK_CLASSIFICATIONS, 'minimal'),
            'lifecycle_status' => $this->oneOf($data['lifecycle_status'] ?? '', self::LIFECYCLE_STATUSES, 'development'),
            'deployment_context' => $this->nullable($data['deployment_context'] ?? null),
            'human_oversight' => $this->nullable($data['human_oversight'] ?? null),
            'owner' => $this->nullable($data['owner'] ?? null),
            'last_review_date' => $this->nullableDate($data['last_review_date'] ?? null),
            'next_review_date' => $this->nullableDate($data['next_review_date'] ?? null),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        ];

        if ($id) {
            DB::table('ai_act_system')->where('id', $id)->update($row);

            return $id;
        }

        return (int) DB::table('ai_act_system')->insertGetId($row);
    }

    public function deleteSystem(int $id): void
    {
        DB::table('ai_act_system')->where('id', $id)->delete();
    }

    // =======================================================================
    // Model registry
    // =======================================================================

    public function listModels(): array
    {
        return DB::table('ai_act_model as m')
            ->leftJoin('ai_act_system as s', 's.id', '=', 'm.system_id')
            ->orderBy('m.model_id')
            ->get(['m.*', 's.name as system_name'])
            ->all();
    }

    public function getModel(int $id): ?object
    {
        return DB::table('ai_act_model')->where('id', $id)->first();
    }

    public function saveModel(array $data, ?int $id = null): int
    {
        $row = [
            'system_id' => $this->nullableInt($data['system_id'] ?? null),
            'model_id' => trim((string) ($data['model_id'] ?? '')),
            'version' => $this->nullable($data['version'] ?? null),
            'provider' => $this->nullable($data['provider'] ?? null),
            'modality' => $this->oneOf($data['modality'] ?? '', self::MODALITIES, 'text'),
            'intended_purpose' => $this->nullable($data['intended_purpose'] ?? null),
            'training_data_summary' => $this->nullable($data['training_data_summary'] ?? null),
            'limitations' => $this->nullable($data['limitations'] ?? null),
            'evaluation_summary' => $this->nullable($data['evaluation_summary'] ?? null),
            'license' => $this->nullable($data['license'] ?? null),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        ];

        if ($id) {
            DB::table('ai_act_model')->where('id', $id)->update($row);

            return $id;
        }

        return (int) DB::table('ai_act_model')->insertGetId($row);
    }

    public function deleteModel(int $id): void
    {
        DB::table('ai_act_model')->where('id', $id)->delete();
    }

    // =======================================================================
    // Risk register (Article 9)
    // =======================================================================

    public function listRisks(): array
    {
        return DB::table('ai_act_risk as r')
            ->leftJoin('ai_act_system as s', 's.id', '=', 'r.system_id')
            ->orderByRaw('(r.likelihood * r.severity) DESC')
            ->get(['r.*', 's.name as system_name'])
            ->all();
    }

    public function getRisk(int $id): ?object
    {
        return DB::table('ai_act_risk')->where('id', $id)->first();
    }

    public function saveRisk(array $data, ?int $id = null): int
    {
        $row = [
            'system_id' => $this->nullableInt($data['system_id'] ?? null),
            'title' => trim((string) ($data['title'] ?? '')),
            'category' => $this->oneOf($data['category'] ?? '', self::RISK_CATEGORIES, 'other'),
            'description' => $this->nullable($data['description'] ?? null),
            'likelihood' => $this->clampScore($data['likelihood'] ?? 3),
            'severity' => $this->clampScore($data['severity'] ?? 3),
            'mitigation' => $this->nullable($data['mitigation'] ?? null),
            'residual_likelihood' => isset($data['residual_likelihood']) && $data['residual_likelihood'] !== '' ? $this->clampScore($data['residual_likelihood']) : null,
            'residual_severity' => isset($data['residual_severity']) && $data['residual_severity'] !== '' ? $this->clampScore($data['residual_severity']) : null,
            'status' => $this->oneOf($data['status'] ?? '', self::RISK_STATUSES, 'open'),
            'owner' => $this->nullable($data['owner'] ?? null),
            'review_date' => $this->nullableDate($data['review_date'] ?? null),
        ];

        if ($id) {
            DB::table('ai_act_risk')->where('id', $id)->update($row);

            return $id;
        }

        return (int) DB::table('ai_act_risk')->insertGetId($row);
    }

    public function deleteRisk(int $id): void
    {
        DB::table('ai_act_risk')->where('id', $id)->delete();
    }

    /** Inherent risk score band for a likelihood*severity product (1-25). */
    public static function riskBand(int $score): string
    {
        if ($score >= 15) {
            return 'critical';
        }
        if ($score >= 8) {
            return 'high';
        }
        if ($score >= 4) {
            return 'medium';
        }

        return 'low';
    }

    // =======================================================================
    // Conformity / oversight attestations
    // =======================================================================

    public function listAttestations(): array
    {
        return DB::table('ai_act_attestation as a')
            ->leftJoin('ai_act_system as s', 's.id', '=', 'a.system_id')
            ->orderByDesc('a.updated_at')
            ->get(['a.*', 's.name as system_name'])
            ->all();
    }

    public function getAttestation(int $id): ?object
    {
        return DB::table('ai_act_attestation')->where('id', $id)->first();
    }

    public function saveAttestation(array $data, ?int $id = null): int
    {
        $status = $this->oneOf($data['status'] ?? '', self::ATTESTATION_STATUSES, 'draft');
        $row = [
            'system_id' => $this->nullableInt($data['system_id'] ?? null),
            'type' => $this->oneOf($data['type'] ?? '', self::ATTESTATION_TYPES, 'conformity_declaration'),
            'statement' => $this->nullable($data['statement'] ?? null),
            'status' => $status,
            'attested_by' => $this->nullable($data['attested_by'] ?? null),
            'evidence_url' => $this->nullable($data['evidence_url'] ?? null),
            'next_review_date' => $this->nullableDate($data['next_review_date'] ?? null),
        ];

        // Stamp attested_at when moving into the attested state (if not already set).
        if ('attested' === $status) {
            $existing = $id ? $this->getAttestation($id) : null;
            $row['attested_at'] = ($existing && $existing->attested_at) ? $existing->attested_at : date('Y-m-d H:i:s');
        }

        if ($id) {
            DB::table('ai_act_attestation')->where('id', $id)->update($row);

            return $id;
        }

        return (int) DB::table('ai_act_attestation')->insertGetId($row);
    }

    public function deleteAttestation(int $id): void
    {
        DB::table('ai_act_attestation')->where('id', $id)->delete();
    }

    // =======================================================================
    // Dashboard rollups
    // =======================================================================

    public function dashboardSummary(): array
    {
        $today = date('Y-m-d');

        $byRisk = [];
        foreach (self::RISK_CLASSIFICATIONS as $rc) {
            $byRisk[$rc] = 0;
        }
        foreach (DB::table('ai_act_system')->select('risk_classification', DB::raw('COUNT(*) as c'))->groupBy('risk_classification')->get() as $r) {
            $byRisk[(string) $r->risk_classification] = (int) $r->c;
        }

        return [
            'systems_total' => (int) DB::table('ai_act_system')->count(),
            'systems_by_risk' => $byRisk,
            'models_total' => (int) DB::table('ai_act_model')->count(),
            'risks_open' => (int) DB::table('ai_act_risk')->whereIn('status', ['open', 'mitigating'])->count(),
            'risks_high' => (int) DB::table('ai_act_risk')
                ->whereIn('status', ['open', 'mitigating'])
                ->whereRaw('(likelihood * severity) >= 8')
                ->count(),
            'attestations_total' => (int) DB::table('ai_act_attestation')->count(),
            'attestations_attested' => (int) DB::table('ai_act_attestation')->where('status', 'attested')->count(),
            'attestations_overdue' => (int) DB::table('ai_act_attestation')
                ->whereNotNull('next_review_date')
                ->whereRaw('next_review_date < ?', [$today])
                ->whereIn('status', ['draft', 'attested'])
                ->count(),
            'systems_review_overdue' => (int) DB::table('ai_act_system')
                ->whereNotNull('next_review_date')
                ->whereRaw('next_review_date < ?', [$today])
                ->where('is_active', 1)
                ->count(),
        ];
    }

    // =======================================================================
    // Helpers
    // =======================================================================

    private function nullable($v): ?string
    {
        $v = is_string($v) ? trim($v) : $v;

        return ($v === null || $v === '') ? null : (string) $v;
    }

    private function nullableInt($v): ?int
    {
        return ($v === null || $v === '' || (int) $v === 0) ? null : (int) $v;
    }

    private function nullableDate($v): ?string
    {
        $v = is_string($v) ? trim($v) : $v;

        return ($v === null || $v === '') ? null : (string) $v;
    }

    private function oneOf($v, array $allowed, string $default): string
    {
        $v = (string) $v;

        return in_array($v, $allowed, true) ? $v : $default;
    }

    private function clampScore($v): int
    {
        $n = (int) $v;
        if ($n < 1) {
            return 1;
        }
        if ($n > 5) {
            return 5;
        }

        return $n;
    }
}
