<?php

/**
 * ahgRdmPlugin — RDM dataset module (Phase 1, atom-ahg-plugins#168).
 *
 * Scaffold + Dataset model + deposit. Later phases add the POPIA scan, human
 * gate, DOI/landing, compliance scoreboard and dashboard.
 */
class rdmActions extends sfActions
{
    protected function getDatasetService(): \AhgRdm\Services\DatasetService
    {
        // The plugin config registers an AhgRdm\ autoloader; this is a safety net.
        if (!class_exists('\AhgRdm\Services\DatasetService')) {
            require_once sfConfig::get('sf_plugins_dir') . '/ahgRdmPlugin/lib/Services/DatasetService.php';
        }

        return new \AhgRdm\Services\DatasetService();
    }

    protected function getGateService(): \AhgRdm\Services\PopiaGateService
    {
        if (!class_exists('\AhgRdm\Services\PopiaGateService')) {
            require_once sfConfig::get('sf_plugins_dir') . '/ahgRdmPlugin/lib/Services/PopiaGateService.php';
        }

        return new \AhgRdm\Services\PopiaGateService();
    }

    protected function getComplianceService(): \AhgRdm\Services\ComplianceReportService
    {
        if (!class_exists('\AhgRdm\Services\ComplianceReportService')) {
            require_once sfConfig::get('sf_plugins_dir') . '/ahgRdmPlugin/lib/Services/ComplianceReportService.php';
        }

        return new \AhgRdm\Services\ComplianceReportService();
    }

    protected function getDmpLinkService(): \AhgRdm\Services\DmpLinkService
    {
        if (!class_exists('\AhgRdm\Services\DmpLinkService')) {
            require_once sfConfig::get('sf_plugins_dir') . '/ahgRdmPlugin/lib/Services/DmpLinkService.php';
        }

        return new \AhgRdm\Services\DmpLinkService();
    }

    protected function requireAuth(): void
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }
    }

    protected function currentUserId(): ?int
    {
        $id = $this->getUser()->getAttribute('user_id');

        return $id ? (int) $id : null;
    }

    // ─── Index: list datasets ───────────────────────────────────────────

    public function executeIndex(sfWebRequest $request)
    {
        $this->requireAuth();
        $this->datasets = $this->getDatasetService()->list();
    }

    // ─── Create: GET shows the form, POST creates + redirects to show ───

    public function executeCreate(sfWebRequest $request)
    {
        $this->requireAuth();

        if ($request->isMethod('post')) {
            $title = trim((string) $request->getParameter('title'));
            if ($title === '') {
                $this->getUser()->setFlash('error', 'A dataset title is required.');

                return sfView::SUCCESS;
            }

            $description = trim((string) $request->getParameter('description'));
            $projectId = $request->getParameter('project_id');
            $projectId = ($projectId !== null && $projectId !== '') ? (int) $projectId : null;

            $datasetId = $this->getDatasetService()->create(
                $title,
                $description !== '' ? $description : null,
                $projectId,
                $this->currentUserId()
            );

            $this->getUser()->setFlash('notice', 'Dataset created. Deposit files below.');
            $this->redirect('@rdm_datasets_show?id=' . $datasetId);
        }
    }

    // ─── Show: dataset detail + deposited files + upload form ───────────

    public function executeShow(sfWebRequest $request)
    {
        $this->requireAuth();
        $svc = $this->getDatasetService();

        $this->dataset = $svc->get((int) $request->getParameter('id'));
        $this->forward404Unless($this->dataset, 'Dataset not found.');

        $this->files = $svc->files((int) $this->dataset->id);
        $this->findings = \Illuminate\Database\Capsule\Manager::table('rdm_scan_finding')
            ->where('dataset_id', (int) $this->dataset->id)
            ->orderByRaw("FIELD(category,'special_category','personal')")
            ->orderBy('type')
            ->get()
            ->all();

        $this->gate = $this->getGateService()->gateStatus((int) $this->dataset->id);
        $this->dmp = $this->getDmpLinkService()->context($this->dataset);
    }

    // ─── Feature 1: link / unlink a Data Management Plan ─────────────────

    public function executeLinkDmp(sfWebRequest $request)
    {
        $this->requireAuth();
        $datasetId = (int) $request->getParameter('id');

        if (!$request->isMethod('post')) {
            $this->redirect('@rdm_datasets_show?id=' . $datasetId);
        }

        $svc = $this->getDmpLinkService();
        try {
            if ($request->getParameter('mode') === 'create') {
                $title = trim((string) $request->getParameter('title')) ?: 'Data Management Plan';
                $dmpId = $svc->createAndLink($datasetId, [
                    'title'  => $title,
                    'funder' => trim((string) $request->getParameter('funder')) ?: null,
                ], $this->currentUserId());
                $msg = $dmpId
                    ? "Created and linked DMP #{$dmpId}."
                    : 'Could not create a DMP — the dataset needs a project with an owner.';
                $this->getUser()->setFlash($dmpId ? 'notice' : 'error', $msg);
            } else {
                $dmpId = (int) $request->getParameter('dmp_id');
                $ok = $dmpId > 0 && $svc->link($datasetId, $dmpId, $this->currentUserId());
                $this->getUser()->setFlash($ok ? 'notice' : 'error', $ok
                    ? "Linked DMP #{$dmpId}."
                    : 'Could not link that plan — it must belong to the dataset\'s project.');
            }
        } catch (\Throwable $e) {
            $this->getUser()->setFlash('error', $e->getMessage());
        }

        $this->redirect('@rdm_datasets_show?id=' . $datasetId);
    }

    public function executeUnlinkDmp(sfWebRequest $request)
    {
        $this->requireAuth();
        $datasetId = (int) $request->getParameter('id');

        if ($request->isMethod('post')) {
            $this->getDmpLinkService()->unlink($datasetId);
            $this->getUser()->setFlash('notice', 'DMP unlinked.');
        }

        $this->redirect('@rdm_datasets_show?id=' . $datasetId);
    }

    // ─── Compliance scoreboard ──────────────────────────────────────────

    public function executeCompliance(sfWebRequest $request)
    {
        $this->requireAuth();
        $svc = $this->getComplianceService();

        $this->filters = array_filter([
            'institution' => trim((string) $request->getParameter('institution')),
            'verdict'     => trim((string) $request->getParameter('verdict')),
            'disposition' => trim((string) $request->getParameter('disposition')),
        ], fn ($v) => $v !== '');

        $this->rows = $svc->rows($this->filters);
        $this->institutions = $svc->institutions();
        $this->summary = $svc->summary($this->filters);
    }

    // ─── Public landing (no auth): citable metadata + access badge ──────

    public function executeLanding(sfWebRequest $request)
    {
        $svc = $this->getDatasetService();
        $this->dataset = $svc->get((int) $request->getParameter('id'));
        $this->forward404Unless($this->dataset, 'Dataset not found.');

        $this->year = !empty($this->dataset->created_at) ? substr((string) $this->dataset->created_at, 0, 4) : date('Y');
        $this->doiUrl = !empty($this->dataset->doi) ? 'https://doi.org/' . $this->dataset->doi : null;
        $this->fileCount = count($svc->files((int) $this->dataset->id));
        $this->dmp = $this->getDmpLinkService()->context($this->dataset);
    }

    // ─── Human gate: confirm/dismiss a finding ──────────────────────────

    public function executeResolveFinding(sfWebRequest $request)
    {
        $this->requireAuth();
        $datasetId = (int) $request->getParameter('id');

        if (!$request->isMethod('post')) {
            $this->redirect('@rdm_datasets_show?id=' . $datasetId);
        }

        $decision = (string) $request->getParameter('decision');
        if (!in_array($decision, ['confirm', 'dismiss'], true)) {
            $this->getUser()->setFlash('error', 'Invalid decision.');
            $this->redirect('@rdm_datasets_show?id=' . $datasetId);
        }

        try {
            $this->getGateService()->resolveFinding(
                (int) $request->getParameter('fid'),
                $decision,
                trim((string) $request->getParameter('note')) ?: null,
                $this->currentUserId()
            );
            $this->getUser()->setFlash('notice', 'Finding ' . ($decision === 'dismiss' ? 'dismissed' : 'confirmed') . '.');
        } catch (\Throwable $e) {
            $this->getUser()->setFlash('error', $e->getMessage());
        }

        $this->redirect('@rdm_datasets_show?id=' . $datasetId);
    }

    // ─── Human gate: apply a disposition (release gated) ────────────────

    public function executeDisposition(sfWebRequest $request)
    {
        $this->requireAuth();
        $datasetId = (int) $request->getParameter('id');

        if (!$request->isMethod('post')) {
            $this->redirect('@rdm_datasets_show?id=' . $datasetId);
        }

        try {
            $result = $this->getGateService()->setDisposition(
                $datasetId,
                (string) $request->getParameter('disposition'),
                $this->currentUserId(),
                trim((string) $request->getParameter('embargo_until')) ?: null
            );
            $this->getUser()->setFlash('notice', 'Disposition set: ' . $result['disposition'] . ' (status ' . $result['status'] . ').');
        } catch (\Throwable $e) {
            $this->getUser()->setFlash('error', $e->getMessage());
        }

        $this->redirect('@rdm_datasets_show?id=' . $datasetId);
    }

    // ─── Deposit: POST file uploads into the dataset ────────────────────

    public function executeDeposit(sfWebRequest $request)
    {
        $this->requireAuth();

        if (!$request->isMethod('post')) {
            $this->redirect('@rdm_datasets_show?id=' . (int) $request->getParameter('id'));
        }

        $datasetId = (int) $request->getParameter('id');
        $dataset = $this->getDatasetService()->get($datasetId);
        $this->forward404Unless($dataset, 'Dataset not found.');

        $files = $this->normalizeUploads($request->getFiles('files'));
        if (empty($files)) {
            $this->getUser()->setFlash('error', 'No files were uploaded.');
            $this->redirect('@rdm_datasets_show?id=' . $datasetId);
        }

        $result = $this->getDatasetService()->deposit($datasetId, $files, $this->currentUserId());

        $msg = sprintf('Deposited %d file(s).', $result['stored']);
        if ($result['skipped'] > 0) {
            $msg .= sprintf(' %d skipped (invalid upload).', $result['skipped']);
        }
        $this->getUser()->setFlash('notice', $msg);
        $this->redirect('@rdm_datasets_show?id=' . $datasetId);
    }

    // ─── Scan: kick the POPIA scan in the background ────────────────────

    public function executeScan(sfWebRequest $request)
    {
        $this->requireAuth();

        $datasetId = (int) $request->getParameter('id');
        $dataset = $this->getDatasetService()->get($datasetId);
        $this->forward404Unless($dataset, 'Dataset not found.');

        if (!$request->isMethod('post')) {
            $this->redirect('@rdm_datasets_show?id=' . $datasetId);
        }

        $fileCount = (int) \Illuminate\Database\Capsule\Manager::table('rdm_dataset_file')
            ->where('dataset_id', $datasetId)->count();
        if ($fileCount === 0) {
            $this->getUser()->setFlash('error', 'Deposit at least one file before scanning.');
            $this->redirect('@rdm_datasets_show?id=' . $datasetId);
        }

        // Mark scanning now so the UI reflects it immediately, then launch the
        // task off-thread (NER can exceed request limits) — mirrors ingest:commit.
        \Illuminate\Database\Capsule\Manager::table('rdm_dataset')
            ->where('id', $datasetId)
            ->update(['status' => 'scanning', 'updated_at' => date('Y-m-d H:i:s')]);

        $atomRoot = sfConfig::get('sf_root_dir');
        $logDir = sfConfig::get('sf_log_dir');
        $cmd = sprintf(
            'nohup php %s/symfony rdm:scan --dataset-id=%d > %s/rdm-scan-%d.log 2>&1 &',
            escapeshellarg($atomRoot),
            $datasetId,
            escapeshellarg($logDir),
            $datasetId
        );
        @exec($cmd);

        $this->getUser()->setFlash('notice', 'POPIA scan started. Findings will appear when it completes.');
        $this->redirect('@rdm_datasets_show?id=' . $datasetId);
    }

    /**
     * Normalise PHP's $_FILES shape (from a multi-file `files[]` input) into a
     * flat list of {tmp_path, original_name}, skipping upload errors.
     *
     * @return array<int,array{tmp_path:string,original_name:string}>
     */
    protected function normalizeUploads($raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        // Multi-file input (name="files[]"): each key is an array of values.
        if (isset($raw['tmp_name']) && is_array($raw['tmp_name'])) {
            $out = [];
            foreach ($raw['tmp_name'] as $i => $tmp) {
                if (($raw['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) {
                    continue;
                }
                $out[] = ['tmp_path' => $tmp, 'original_name' => (string) ($raw['name'][$i] ?? basename($tmp))];
            }

            return $out;
        }

        // Single-file input (name="files").
        if (isset($raw['tmp_name']) && is_string($raw['tmp_name'])) {
            if (($raw['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && is_uploaded_file($raw['tmp_name'])) {
                return [['tmp_path' => $raw['tmp_name'], 'original_name' => (string) ($raw['name'] ?? basename($raw['tmp_name']))]];
            }
        }

        return [];
    }
}
