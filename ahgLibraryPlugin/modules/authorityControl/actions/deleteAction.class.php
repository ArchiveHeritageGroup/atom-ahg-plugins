<?php

use AtomFramework\Http\Controllers\AhgController;

class authorityControlDeleteAction extends AhgController
{
    public function execute($request)
    {
        if (!$request->isMethod('post')) {
            $this->forward404();
        }

        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once dirname(__FILE__, 4).'/lib/Service/AuthorityControlService.php';

        $id = (int) $request->getParameter('id');
        if (!$id) {
            $this->forward404();
        }

        $svc = new AuthorityControlService();
        $svc->delete($id);

        $this->getUser()->setFlash('notice', __('Authority record deleted.'));
        $this->redirect(['module' => 'authorityControl', 'action' => 'index']);
    }
}
