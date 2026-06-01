<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * scanManage module actions — ahgScanPlugin.
 *
 * Admin UI for configuring watched folders, reviewing scan history, and
 * launching an on-demand scan pass. The streaming pipeline runs in the
 * scan:watch CLI; this module manages its configuration.
 */
class scanManageActions extends AhgController
{
    private function services(): \AhgScanPlugin\Services\WatchedFolderService
    {
        $base = $this->config('sf_root_dir') . '/plugins/ahgScanPlugin/lib/Services';
        require_once $base . '/WatchedFolderService.php';
        require_once $base . '/ScannerService.php';

        return new \AhgScanPlugin\Services\WatchedFolderService();
    }

    public function executeIndex($request)
    {
        $this->requireAdmin();
        $this->getResponse()->setTitle('Watched Folders - ' . $this->getResponse()->getTitle());

        $svc = $this->services();
        $this->folders = $svc->listAll();
    }

    public function executeEdit($request)
    {
        $this->requireAdmin();

        $svc = $this->services();
        $id = (int) $request->getParameter('id');
        $this->folder = $id ? $svc->find($id) : null;
        $this->session = ($this->folder && $this->folder->ingest_session_id)
            ? \Illuminate\Database\Capsule\Manager::table('ingest_session')
                ->where('id', $this->folder->ingest_session_id)->first()
            : null;
        $this->isNew = !$this->folder;
        $this->getResponse()->setTitle(($this->isNew ? 'New watched folder' : 'Edit watched folder') . ' - ' . $this->getResponse()->getTitle());
    }

    public function executeCreate($request)
    {
        $this->requireAdmin();
        if ($request->getMethod() !== 'POST') {
            $this->redirect(['module' => 'scanManage', 'action' => 'index']);
        }

        $svc = $this->services();
        try {
            $id = $svc->create($this->collectInput($request), $this->userId() ?? 1);
            $this->getUser()->setFlash('notice', 'Watched folder created.');
            $this->redirect(['module' => 'scanManage', 'action' => 'edit', 'id' => $id]);
        } catch (\Throwable $e) {
            $this->getUser()->setFlash('error', 'Could not create folder: ' . $e->getMessage());
            $this->redirect(['module' => 'scanManage', 'action' => 'new']);
        }
    }

    public function executeUpdate($request)
    {
        $this->requireAdmin();
        $id = (int) $request->getParameter('id');
        if ($request->getMethod() !== 'POST') {
            $this->redirect(['module' => 'scanManage', 'action' => 'edit', 'id' => $id]);
        }

        $svc = $this->services();
        try {
            $svc->update($id, $this->collectInput($request));
            $this->getUser()->setFlash('notice', 'Watched folder updated.');
        } catch (\Throwable $e) {
            $this->getUser()->setFlash('error', 'Update failed: ' . $e->getMessage());
        }
        $this->redirect(['module' => 'scanManage', 'action' => 'edit', 'id' => $id]);
    }

    public function executeDelete($request)
    {
        $this->requireAdmin();
        $id = (int) $request->getParameter('id');
        $svc = $this->services();
        $svc->delete($id);
        $this->getUser()->setFlash('notice', 'Watched folder removed (history retained).');
        $this->redirect(['module' => 'scanManage', 'action' => 'index']);
    }

    public function executeToggle($request)
    {
        $this->requireAdmin();
        $id = (int) $request->getParameter('id');
        $svc = $this->services();
        $folder = $svc->find($id);
        if ($folder) {
            $svc->update($id, ['enabled' => $folder->enabled ? 0 : 1]);
        }
        $this->redirect(['module' => 'scanManage', 'action' => 'index']);
    }

    /**
     * On-demand single scan pass for one folder (admin "Scan now" button).
     */
    public function executeRun($request)
    {
        $this->requireAdmin();
        $id = (int) $request->getParameter('id');
        $svc = $this->services();
        $folder = $svc->find($id);
        if (!$folder) {
            $this->forward404('Watched folder not found.');
        }

        $scanner = new \AhgScanPlugin\Services\ScannerService($svc);
        $counts = $scanner->scanFolder($folder);

        if ($request->isXmlHttpRequest()) {
            return $this->renderJson($counts);
        }

        $this->getUser()->setFlash('notice', sprintf(
            'Scan complete: %d detected, %d enqueued, %d duplicate, %d failed.',
            $counts['detected'],
            $counts['enqueued'],
            $counts['skipped_duplicate'],
            $counts['failed']
        ));
        $this->redirect(['module' => 'scanManage', 'action' => 'history', 'id' => $id]);
    }

    public function executeHistory($request)
    {
        $this->requireAdmin();
        $id = (int) $request->getParameter('id');
        $svc = $this->services();
        $this->folder = $svc->find($id);
        if (!$this->folder) {
            $this->forward404('Watched folder not found.');
        }
        $this->events = $svc->recentEvents($id, 50);
        $this->getResponse()->setTitle('Scan history - ' . $this->getResponse()->getTitle());
    }

    /**
     * Collect + sanitise the folder form fields shared by create/update.
     */
    private function collectInput($request): array
    {
        return [
            'code' => trim((string) $request->getParameter('code')),
            'label' => trim((string) $request->getParameter('label')),
            'path' => trim((string) $request->getParameter('path')),
            'layout' => $request->getParameter('layout', 'flat'),
            'parent_id' => $request->getParameter('parent_id') ? (int) $request->getParameter('parent_id') : null,
            'repository_id' => $request->getParameter('repository_id') ? (int) $request->getParameter('repository_id') : null,
            'sector' => $request->getParameter('sector', 'archive'),
            'standard' => $request->getParameter('standard', 'isadg'),
            'disposition_success' => $request->getParameter('disposition_success', 'move'),
            'disposition_failure' => $request->getParameter('disposition_failure', 'quarantine'),
            'processed_path' => trim((string) $request->getParameter('processed_path')) ?: null,
            'failed_path' => trim((string) $request->getParameter('failed_path')) ?: null,
            'min_quiet_seconds' => (int) $request->getParameter('min_quiet_seconds', 10),
            'auto_commit' => $request->getParameter('auto_commit') ? 1 : 0,
            'enabled' => $request->getParameter('enabled') ? 1 : 0,
            'derivative_thumbnails' => $request->getParameter('derivative_thumbnails') ? 1 : 0,
            'derivative_reference' => $request->getParameter('derivative_reference') ? 1 : 0,
            'process_virus_scan' => $request->getParameter('process_virus_scan') ? 1 : 0,
            'process_ocr' => $request->getParameter('process_ocr') ? 1 : 0,
            'process_ner' => $request->getParameter('process_ner') ? 1 : 0,
        ];
    }
}
