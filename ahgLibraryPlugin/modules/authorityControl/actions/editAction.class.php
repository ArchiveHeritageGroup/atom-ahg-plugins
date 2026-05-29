<?php

use AtomFramework\Http\Controllers\AhgController;

class authorityControlEditAction extends AhgController
{
    public function execute($request)
    {
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/AuthorityControlService.php';

        $svc = new AuthorityControlService();
        $id  = (int) $request->getParameter('id');

        $this->authority = null;
        if ($id) {
            $this->authority = $svc->find($id);
            if (!$this->authority) {
                $this->forward404();
            }
        }

        if ($request->isMethod('post')) {
            $heading = trim((string) $request->getParameter('heading', ''));

            if ($heading === '') {
                $this->getUser()->setFlash('error', __('Subject heading is required.'));

                return sfView::SUCCESS;
            }

            $data = [
                'heading'      => $heading,
                'subject_type' => $request->getParameter('subject_type', 'topic'),
                'source'       => $request->getParameter('source', 'local'),
                'uri'          => $request->getParameter('uri') ?: null,
            ];

            if ($this->authority) {
                $svc->update($id, $data);
                $this->getUser()->setFlash('notice', __('Authority record updated.'));
                $this->redirect(['module' => 'authorityControl', 'action' => 'view', 'id' => $id]);
            } else {
                $newId = $svc->create($data);
                $this->getUser()->setFlash('notice', __('Authority record created.'));
                $this->redirect(['module' => 'authorityControl', 'action' => 'view', 'id' => $newId]);
            }
        }
    }
}
