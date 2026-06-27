<?php

/**
 * @author    Johan Pieterse <johan@theahg.co.za>
 * @copyright The Archive and Heritage Group (Pty) Ltd
 * @license   AGPL-3.0-or-later
 */

namespace AhgRdm\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Enforce a dataset's human-gate disposition — reverse port of Heratio ahg-rdm
 * DatasetReleaseService (heratio#1341). Applies ODRL access/embargo policies and,
 * on a finalised disposition, mints a DataCite DOI. Thin orchestration over
 * ahgResearchPlugin OdrlService + ahgDoiPlugin DoiService — no new policy or DOI
 * machinery.
 *
 * Policies are written against the dataset's container IO AND each child file IO
 * (target_type 'archival_description'), so the same ODRL evaluation the IO show
 * page uses gates access. Re-applying a disposition first clears the dataset's
 * prior rdm policies so state always reflects the latest decision.
 */
class DatasetReleaseService
{
    /**
     * @return array{disposition:string, doi?:?string, policies?:int, embargo_until?:string}
     */
    public function apply(int $datasetId, string $disposition, ?int $userId, ?string $embargoUntil = null): array
    {
        $ds = DB::table('rdm_dataset')->where('id', $datasetId)->first();
        if (!$ds) {
            throw new \RuntimeException("Dataset {$datasetId} not found.");
        }

        $ioIds = $this->datasetIoIds($datasetId, (int) $ds->io_parent_id);
        $this->clearPolicies($ioIds);

        $out = ['disposition' => $disposition];

        // Access policies: 'release' is open (policies cleared above);
        // restrict/de-identify -> indefinite prohibition; embargo -> until a date.
        if ($disposition !== 'release') {
            $dateTo = null;
            if ($disposition === 'embargo') {
                $dateTo = $embargoUntil ?: date('Y-m-d H:i:s', strtotime('+1 year'));
                $out['embargo_until'] = $dateTo;
            }
            $this->restrict($ioIds, $dateTo, $userId);
            $out['policies'] = count($ioIds) * 2; // use + reproduce per IO
        }

        // Mint a DOI for ANY finalised disposition — a restricted/embargoed dataset
        // is still a citable record (public metadata, gated files); access is
        // controlled by the ODRL policies above, independently of the DOI.
        $out['doi'] = $this->mintDoi($datasetId, (int) $ds->io_parent_id);

        return $out;
    }

    /** Container IO + every deposited file's child IO. */
    private function datasetIoIds(int $datasetId, int $containerIo): array
    {
        $ids = DB::table('rdm_dataset_file')->where('dataset_id', $datasetId)
            ->pluck('io_id')->map(fn ($v) => (int) $v)->all();
        array_unshift($ids, $containerIo);

        return array_values(array_unique(array_filter($ids)));
    }

    private function odrl(): \OdrlService
    {
        if (!class_exists('\OdrlService')) {
            require_once \sfConfig::get('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/OdrlService.php';
        }

        return new \OdrlService();
    }

    /** Remove the dataset's existing ODRL policies (these IOs are rdm-owned). */
    private function clearPolicies(array $ioIds): void
    {
        $odrl = $this->odrl();
        foreach ($ioIds as $io) {
            foreach ($odrl->getPolicies('archival_description', $io) as $p) {
                if (isset($p->id)) {
                    $odrl->deletePolicy((int) $p->id);
                }
            }
        }
    }

    /** Prohibition on use + reproduce; optional date_to = embargo-until. */
    private function restrict(array $ioIds, ?string $dateTo, ?int $userId): void
    {
        $odrl = $this->odrl();
        $constraints = $dateTo ? ['date_to' => $dateTo] : null;
        foreach ($ioIds as $io) {
            foreach (['use', 'reproduce'] as $action) {
                $odrl->createPolicy([
                    'target_type'      => 'archival_description',
                    'target_id'        => $io,
                    'policy_type'      => 'prohibition',
                    'action_type'      => $action,
                    'constraints_json' => $constraints,
                    'created_by'       => $userId,
                ]);
            }
        }
    }

    /**
     * Mint a DataCite DOI for the dataset's container IO via ahgDoiPlugin, with a
     * draft test-prefix fallback. Idempotent (returns the existing DOI).
     *
     * Live vs dry-run is decided by the ENVIRONMENT, not the code (parity
     * heratio#1348): a real DataCite registration fires only when the active
     * ahg_doi_config targets a production environment. Every other environment
     * ('test'/dev/demo) builds the reserved test-prefix DOI string with NO
     * external call, so off-prod never registers real DOIs. Flipping
     * ahg_doi_config.environment to production (once real creds land) is an ops
     * action, not a code change.
     */
    private function mintDoi(int $datasetId, int $containerIo): ?string
    {
        $existing = DB::table('rdm_dataset')->where('id', $datasetId)->value('doi');
        if (!empty($existing)) {
            return $existing;
        }

        $doi = null;
        try {
            $config = DB::table('ahg_doi_config')->where('is_active', 1)->first();
            $live = $config && in_array(strtolower((string) ($config->environment ?? '')), ['production', 'prod', 'live'], true);

            if ($live) {
                if (!class_exists('\ahgDoiPlugin\Services\DoiService')) {
                    require_once \sfConfig::get('sf_plugins_dir') . '/ahgDoiPlugin/lib/Services/DoiService.php';
                }
                $r = (new \ahgDoiPlugin\Services\DoiService())->mintDoi($containerIo, 'findable');
                // mintDoi returns the existing DOI even when success=false (already minted).
                if (!empty($r['doi'])) {
                    $doi = $r['doi'];
                }
            }
        } catch (\Throwable $e) {
            error_log('[ahgRdm/release] DOI mint fell back to draft: ' . $e->getMessage());
        }

        if (!$doi) {
            $doi = '10.5072/heratio.dataset.' . $datasetId; // DataCite reserved test prefix
        }

        DB::table('rdm_dataset')->where('id', $datasetId)->update([
            'doi'        => $doi,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $doi;
    }
}
