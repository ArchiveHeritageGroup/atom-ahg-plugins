<?php

use AtomFramework\Http\Controllers\AhgController;

class authorityControlViewAction extends AhgController
{
    public function execute($request)
    {
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/AuthorityControlService.php';

        $id = (int) $request->getParameter('id');
        if (!$id) {
            $this->forward404();
        }

        $svc = new AuthorityControlService();

        $this->authority = $svc->find($id);
        if (!$this->authority) {
            $this->forward404();
        }

        $culture = \sfContext::getInstance()->getUser()->getCulture() ?: 'en';
        $this->linkedItems = $svc->linkedItems($id, $culture);
    }
}
