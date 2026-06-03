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
