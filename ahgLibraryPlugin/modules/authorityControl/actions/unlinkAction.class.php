<?php

use AtomFramework\Http\Controllers\AhgController;

class authorityControlUnlinkAction extends AhgController
{
    public function execute($request)
    {
        if (!$request->isMethod('post')) {
            $this->forward404();
        }

        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once dirname(__FILE__, 4).'/lib/Service/AuthorityControlService.php';

        $linkId = (int) $request->getParameter('linkId');
        if (!$linkId) {
            $this->forward404();
        }

        $link = \Illuminate\Database\Capsule\Manager::table('library_item_authority_link')
            ->where('id', $linkId)
            ->first();

        $svc = new AuthorityControlService();
        $svc->unlinkFromItem($linkId);

        $this->getUser()->setFlash('notice', __('Link removed.'));

        if ($link && !empty($link->authority_id)) {
            $this->redirect(['module' => 'authorityControl', 'action' => 'view', 'id' => $link->authority_id]);
        }
        $this->redirect(['module' => 'authorityControl', 'action' => 'index']);
    }
}
