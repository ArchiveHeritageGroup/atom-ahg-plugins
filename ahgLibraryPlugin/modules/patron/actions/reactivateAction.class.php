<?php

use AtomFramework\Http\Controllers\AhgController;

class patronReactivateAction extends AhgController
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
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/PatronService.php';

        $id = (int) $request->getParameter('id');

        if (!$id) {
            $this->forward404();
        }

        $service = PatronService::getInstance();

        $patron = $service->find($id);
        if (!$patron) {
            $this->forward404();
        }

        $service->reactivate($id);
        $this->getUser()->setFlash('notice', __('Patron has been reactivated.'));

        $this->redirect(['module' => 'patron', 'action' => 'view', 'id' => $id]);
    }
}
