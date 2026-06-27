<?php

/**
 * @author    Johan Pieterse <johan@theahg.co.za>
 * @copyright The Archive and Heritage Group (Pty) Ltd
 * @license   AGPL-3.0-or-later
 */

namespace AhgRdm\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Link an RDM dataset to a Data Management Plan — reverse port of Heratio
 * ahg-rdm DmpLinkService (heratio#1337 Feature 1).
 *
 * Pure orchestration over the EXISTING ahgResearchPlugin DMP builder (global
 * `DmpService` + research_dmp): a DMP is authored once in the research portal,
 * and a dataset just carries a reference to it (rdm_dataset.dmp_id). This package
 * never owns DMP data and never duplicates the authoring tool — it reads the plan
 * for display and writes only the single FK-by-convention column.
 *
 * A DMP is project-scoped, so linkage requires the dataset to have a project.
 */
class DmpLinkService
{
    private function dmp(): \DmpService
    {
        if (!class_exists('\DmpService')) {
            require_once \sfConfig::get('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/DmpService.php';
        }

        return new \DmpService();
    }

    private function tableExists(string $table): bool
    {
        $r = DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            [$table]
        );

        return $r && (int) $r->c > 0;
    }

    /**
     * Everything the dataset views need to render the DMP panel. Degrades to
     * 'unavailable' when the ahgResearch DMP slice is not installed.
     *
     * @return array{available:bool, project_id:?int, plans:array, linked:?object, completeness:?int, index_url:?string, show_url:?string}
     */
    public function context(object $dataset): array
    {
        $available = class_exists('\DmpService') || $this->tableExists('research_dmp');
        $available = $available && $this->tableExists('research_dmp');

        $projectId = isset($dataset->project_id) ? (int) $dataset->project_id : 0;
        $dmpId     = isset($dataset->dmp_id) ? (int) $dataset->dmp_id : 0;

        $ctx = [
            'available'    => $available,
            'project_id'   => $projectId ?: null,
            'plans'        => [],
            'linked'       => null,
            'completeness' => null,
            'index_url'    => null,
            'show_url'     => null,
        ];

        if (!$available) {
            return $ctx;
        }

        $dmp = $this->dmp();

        if ($projectId) {
            $ctx['plans']     = $dmp->listForProject($projectId);
            $ctx['index_url'] = '/research/dmps';
        }

        if ($dmpId) {
            $plan = $dmp->get($dmpId);
            if ($plan) {
                $ctx['linked']       = $plan;
                $ctx['completeness'] = $dmp->completeness($plan);
                $ctx['show_url']     = '/research/dmp/view?id=' . $dmpId;
            } else {
                // The plan was deleted out from under us; clear the dangling link.
                DB::table('rdm_dataset')->where('id', $dataset->id)->update(['dmp_id' => null]);
            }
        }

        return $ctx;
    }

    /**
     * Link an existing plan to the dataset. The plan MUST belong to the dataset's
     * project (a DMP is project-scoped), else the link is refused.
     */
    public function link(int $datasetId, int $dmpId, ?int $userId = null): bool
    {
        $ds = DB::table('rdm_dataset')->where('id', $datasetId)->first();
        if (!$ds || empty($ds->project_id) || !$this->tableExists('research_dmp')) {
            return false;
        }

        $owns = DB::table('research_dmp')
            ->where('id', $dmpId)
            ->where('project_id', $ds->project_id)
            ->exists();
        if (!$owns) {
            return false;
        }

        DB::table('rdm_dataset')->where('id', $datasetId)
            ->update(['dmp_id' => $dmpId, 'updated_at' => date('Y-m-d H:i:s')]);

        return true;
    }

    /**
     * Create a fresh DMP for the dataset's project (via the research DmpService)
     * and link it. Returns the new plan id, or null when there is no project / the
     * DMP slice is absent.
     *
     * @param  array<string,mixed>  $meta  title, funder, grant_number, status, …
     */
    public function createAndLink(int $datasetId, array $meta, ?int $userId = null): ?int
    {
        $ds = DB::table('rdm_dataset')->where('id', $datasetId)->first();
        if (!$ds || empty($ds->project_id) || !$this->tableExists('research_dmp')) {
            return null;
        }

        // research_project.owner_id is a research_researcher id — reuse it as the
        // plan owner so the DMP shows up correctly in the researcher's portal.
        $ownerId = DB::table('research_project')->where('id', $ds->project_id)->value('owner_id');
        if (!$ownerId) {
            return null;
        }

        // AtoM DmpService::create(researcherId, data) — project_id flows through
        // mapFields; never write research_dmp directly.
        $dmpId = $this->dmp()->create((int) $ownerId, array_merge($meta, [
            'project_id' => (int) $ds->project_id,
        ]));
        if (!$dmpId) {
            return null;
        }

        DB::table('rdm_dataset')->where('id', $datasetId)
            ->update(['dmp_id' => $dmpId, 'updated_at' => date('Y-m-d H:i:s')]);

        return (int) $dmpId;
    }

    /** Detach the DMP from the dataset (the plan itself is left untouched). */
    public function unlink(int $datasetId): bool
    {
        DB::table('rdm_dataset')->where('id', $datasetId)
            ->update(['dmp_id' => null, 'updated_at' => date('Y-m-d H:i:s')]);

        return true;
    }
}
