<?php

use AtomFramework\Http\Controllers\AhgController;

class authorityControlLinkAction extends AhgController
{
    public function execute($request)
    {
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/AuthorityControlService.php';

        $svc = new AuthorityControlService();

        if ($request->isMethod('post')) {
            $authorityId   = (int) $request->getParameter('authority_id');
            $libraryItemId = (int) $request->getParameter('library_item_id');
            $tag           = trim((string) $request->getParameter('source_tag', '650')) ?: '650';

            if (!$authorityId || !$libraryItemId) {
                $this->getUser()->setFlash('error', __('Authority record and library item are both required.'));
                $this->redirect(['module' => 'authorityControl', 'action' => 'link', 'id' => $authorityId]);
            }

            $svc->linkToItem($authorityId, $libraryItemId, $tag);
            $this->getUser()->setFlash('notice', __('Subject heading linked to library item.'));
            $this->redirect(['module' => 'authorityControl', 'action' => 'view', 'id' => $authorityId]);
        }

        $id = (int) $request->getParameter('id');
        $this->authority = $svc->find($id);
        if (!$this->authority) {
            $this->forward404();
        }
    }
}
