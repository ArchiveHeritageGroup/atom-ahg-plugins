<?php

/**
 * @author    Johan Pieterse <johan@theahg.co.za>
 * @copyright The Archive and Heritage Group (Pty) Ltd
 * @license   AGPL-3.0-or-later
 */

namespace AhgRdm\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Human gate for the POPIA scan — reverse port of Heratio ahg-rdm
 * PopiaGateService (heratio#1340). The scan only suggests; a reviewer
 * confirms/overrides every finding and applies a disposition. A dataset with
 * unresolved (or confirmed) PERSONAL/SPECIAL findings CANNOT be released open —
 * this service enforces that gate and logs every decision (durable on-row
 * provenance, plus a best-effort cross-cutting trail).
 */
class PopiaGateService
{
    private const PII_CATEGORIES = ['personal', 'special_category'];
    private const DISPOSITIONS = ['restrict', 'embargo', 'de-identify', 'release'];

    /** Confirm (real PII) or dismiss (false positive) a single finding. */
    public function resolveFinding(int $findingId, string $decision, ?string $note, ?int $userId): void
    {
        $status = $decision === 'dismiss' ? 'dismissed' : 'confirmed';
        $finding = DB::table('rdm_scan_finding')->where('id', $findingId)->first();
        if (!$finding) {
            throw new \RuntimeException("Finding {$findingId} not found.");
        }

        DB::table('rdm_scan_finding')->where('id', $findingId)->update([
            'review_status' => $status,
            'reviewed_by'   => $userId,
            'reviewed_at'   => date('Y-m-d H:i:s'),
            'decision_note' => $note ? mb_substr($note, 0, 500) : null,
        ]);

        $this->logProvenance(
            (int) $finding->dataset_id,
            "finding #{$findingId} ({$finding->type}, {$finding->method}) -> {$status}",
            (string) $finding->method,
            $userId
        );
    }

    /**
     * Apply a dataset disposition. 'release' (open) is blocked unless the gate
     * is clear (no pending and no confirmed PERSONAL/SPECIAL findings).
     *
     * @return array{disposition:string, status:string}
     */
    public function setDisposition(int $datasetId, string $disposition, ?int $userId, ?string $embargoUntil = null): array
    {
        if (!in_array($disposition, self::DISPOSITIONS, true)) {
            throw new \InvalidArgumentException("Invalid disposition '{$disposition}'.");
        }

        $gate = $this->gateStatus($datasetId);
        if ($disposition === 'release' && !$gate['can_release']) {
            throw new \RuntimeException(
                "Open release blocked: {$gate['pending']} finding(s) still unresolved and {$gate['confirmed_pii']} "
                . "confirmed PERSONAL/SPECIAL. Resolve every finding (with none confirmed as PII) before open release, "
                . "or choose restrict / embargo / de-identify."
            );
        }

        // release (gate clear) -> publishable/open; any protective disposition -> restricted.
        $status = $disposition === 'release' ? 'published' : 'restricted';

        DB::table('rdm_dataset')->where('id', $datasetId)->update([
            'disposition'    => $disposition,
            'disposition_by' => $userId,
            'disposition_at' => date('Y-m-d H:i:s'),
            'status'         => $status,
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        // Phase 4 (#171) side-effects — ODRL access/embargo on the dataset's IOs
        // and a DataCite DOI on open release. Guarded: no-op until
        // DatasetReleaseService lands, so the gate works on its own now.
        $effects = $this->applyReleaseEffects($datasetId, $disposition, $userId, $embargoUntil);

        $this->logProvenance(
            $datasetId,
            "disposition -> {$disposition} (status {$status})" . (!empty($effects['doi']) ? ", doi {$effects['doi']}" : ''),
            'human-gate',
            $userId
        );

        return array_merge(['disposition' => $disposition, 'status' => $status], $effects);
    }

    /**
     * Gate state for the UI / publish guard.
     *
     * @return array{pending:int, confirmed_pii:int, dismissed:int, can_release:bool}
     */
    public function gateStatus(int $datasetId): array
    {
        $base = DB::table('rdm_scan_finding')->where('dataset_id', $datasetId)->whereIn('category', self::PII_CATEGORIES);
        $pending = (clone $base)->where('review_status', 'pending')->count();
        $confirmed = (clone $base)->where('review_status', 'confirmed')->count();
        $dismissed = (clone $base)->where('review_status', 'dismissed')->count();

        return [
            'pending'       => (int) $pending,
            'confirmed_pii' => (int) $confirmed,
            'dismissed'     => (int) $dismissed,
            'can_release'   => $pending === 0 && $confirmed === 0,
        ];
    }

    /**
     * Phase 4 release side-effects, guarded. Returns [] until
     * DatasetReleaseService (ODRL + DOI + landing) is built, so Phase 3 enforces
     * the gate + disposition without depending on it.
     *
     * @return array<string,mixed>
     */
    private function applyReleaseEffects(int $datasetId, string $disposition, ?int $userId, ?string $embargoUntil): array
    {
        $file = \sfConfig::get('sf_plugins_dir') . '/ahgRdmPlugin/lib/Services/DatasetReleaseService.php';
        if (!is_file($file)) {
            return [];
        }
        try {
            require_once $file;
            if (class_exists('\AhgRdm\Services\DatasetReleaseService')) {
                return (new \AhgRdm\Services\DatasetReleaseService())->apply($datasetId, $disposition, $userId, $embargoUntil);
            }
        } catch (\Throwable $e) {
            // non-fatal — disposition + gate already persisted
        }

        return [];
    }

    /**
     * Provenance. The durable trail lives on the finding/dataset rows; this adds
     * a best-effort cross-cutting breadcrumb (error_log + ahgAiCompliancePlugin
     * InferenceLogger when constructible). Never blocks the gate.
     */
    private function logProvenance(int $datasetId, string $purpose, ?string $model, ?int $userId): void
    {
        $ref = "dataset:{$datasetId} user:" . ($userId ?? '?');
        try {
            error_log("[ahgRdm/popia-gate] {$purpose} ({$ref}, model=" . ($model ?? 'n/a') . ')');
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
