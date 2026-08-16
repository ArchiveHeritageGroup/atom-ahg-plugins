<?php

use AtomFramework\Http\Controllers\AhgController;

class patronSuspendAction extends AhgController
{
    public function execute($request)
    {
        // POST only
        if (!$request->isMethod('post')) {
            $this->forward404();
        }

        // Load framework
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';

        // Load PatronService
        require_once dirname(__FILE__, 4).'/lib/Service/PatronService.php';

        $id = (int) $request->getParameter('id');
        $reason = trim($request->getParameter('reason', ''));

        if (!$id) {
            $this->forward404();
        }

        $service = PatronService::getInstance();

        $patron = $service->find($id);
        if (!$patron) {
            $this->forward404();
        }

        $service->suspend($id, $reason ?: null);
        $this->getUser()->setFlash('notice', __('Patron has been suspended.'));

        $this->redirect(['module' => 'patron', 'action' => 'view', 'id' => $id]);
    }
}
