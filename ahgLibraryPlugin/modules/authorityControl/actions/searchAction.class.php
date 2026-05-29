<?php

use AtomFramework\Http\Controllers\AhgController;

class authorityControlSearchAction extends AhgController
{
    public function execute($request)
    {
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/AuthorityControlService.php';

        $term = (string) ($request->getParameter('q') ?? $request->getParameter('term') ?? '');
        $max  = max(1, min(50, (int) $request->getParameter('max', 20)));

        $svc = new AuthorityControlService();
        $results = $svc->search($term, $max);

        return $this->renderJson(['results' => $results]);
    }
}
