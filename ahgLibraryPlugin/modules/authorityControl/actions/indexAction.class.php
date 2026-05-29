<?php

use AtomFramework\Http\Controllers\AhgController;

class authorityControlIndexAction extends AhgController
{
    public function execute($request)
    {
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/AuthorityControlService.php';

        $this->search      = trim((string) $request->getParameter('search', ''));
        $this->subjectType = trim((string) $request->getParameter('subject_type', ''));
        $this->source      = trim((string) $request->getParameter('source', ''));
        $this->page        = max(1, (int) $request->getParameter('page', 1));

        $svc = new AuthorityControlService();
        $result = $svc->index([
            'search'       => $this->search,
            'subject_type' => $this->subjectType,
            'source'       => $this->source,
            'page'         => $this->page,
            'limit'        => 25,
        ]);

        $this->authorities = $result['hits'];
        $this->total       = $result['total'];
        $this->totalPages  = $result['pages'];
    }
}
