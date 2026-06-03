<?php

/**
 * recordsManage module — file plan / classification scheme admin (#118).
 */
class recordsManageActions extends sfActions
{
    public function preExecute()
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }
        if (!$this->getUser()->hasCredential('administrator')) {
            $this->forward('admin', 'secure');
        }
    }

    protected function filePlanService()
    {
        require_once dirname(__DIR__, 3) . '/lib/Services/FilePlanService.php';

        return new \AhgRecordsManage\Services\FilePlanService();
    }

    protected function emailService()
    {
        require_once dirname(__DIR__, 3) . '/lib/Services/EmailCaptureService.php';

        return new \AhgRecordsManage\Services\EmailCaptureService();
    }

    public function executeEmailCapture($request)
    {
        $svc = $this->emailService();
        $userId = (int) $this->getUser()->getAttribute('user_id');

        if ($request->isMethod('post')) {
            $do = $request->getParameter('do');
            try {
                if ($do === 'upload') {
                    $file = $request->getFiles('eml');
                    if (!empty($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
                        $res = $svc->captureFromEml($file['tmp_name'], $userId);
                        $this->getUser()->setFlash('success', $res['duplicate']
                            ? 'Email already captured (duplicate Message-ID)'
                            : 'Email captured');
                    } else {
                        $this->getUser()->setFlash('error', 'No .eml file uploaded');
                    }
                } elseif ($do === 'classify') {
                    $svc->classify((int) $request->getParameter('id'),
                        (int) $request->getParameter('fileplan_node_id'),
                        $request->getParameter('disposal_class_id') ? (int) $request->getParameter('disposal_class_id') : null,
                        $userId);
                    $this->getUser()->setFlash('success', 'Email classified');
                } elseif ($do === 'declare') {
                    $ioId = $svc->declareAsRecord((int) $request->getParameter('id'), $userId);
                    $this->getUser()->setFlash($ioId ? 'success' : 'error',
                        $ioId ? 'Declared as record (information object #' . $ioId . ')' : 'Could not declare as record');
                }
            } catch (\Throwable $e) {
                $this->getUser()->setFlash('error', 'Capture failed: ' . $e->getMessage());
            }
            $this->redirect(['module' => 'recordsManage', 'action' => 'emailCapture']);
        }

        $queue = $svc->listQueue(['status' => $request->getParameter('status') ?: null]);
        $this->rows = $queue['rows'];
        $this->counts = $svc->counts();
        $this->nodes = $this->filePlanService()->getNodesForDropdown();
    }

    public function executeFilePlan($request)
    {
        $svc = $this->filePlanService();

        if ($request->isMethod('post')) {
            $do = $request->getParameter('do');
            if ($do === 'add') {
                $svc->createNode(array_merge(
                    $request->getPostParameters(),
                    ['created_by' => $this->getUser()->getAttribute('user_id')]
                ));
                $this->getUser()->setFlash('success', 'File plan node added');
            } elseif ($do === 'edit') {
                $svc->updateNode((int) $request->getParameter('id'), $request->getPostParameters());
                $this->getUser()->setFlash('success', 'Node updated');
            } elseif ($do === 'delete') {
                $ok = $svc->deleteNode((int) $request->getParameter('id'));
                $this->getUser()->setFlash($ok ? 'success' : 'error',
                    $ok ? 'Node deleted' : 'Cannot delete: node has children or linked records');
            } elseif ($do === 'move') {
                $svc->moveNode((int) $request->getParameter('id'), (int) $request->getParameter('new_parent_id'));
                $this->getUser()->setFlash('success', 'Node moved');
            }
            $this->redirect(['module' => 'recordsManage', 'action' => 'filePlan']);
        }

        $this->nodes = $svc->getTreeFlat();
        $this->dropdown = $svc->getNodesForDropdown();
        $this->stats = $svc->getStats();
        $editId = (int) $request->getParameter('edit', 0);
        $this->editNode = $editId ? $svc->getNode($editId) : null;
    }
}
