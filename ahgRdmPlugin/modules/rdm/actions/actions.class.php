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
