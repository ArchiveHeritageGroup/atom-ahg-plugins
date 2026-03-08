<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Interlibrary Loan — browse and search requests.
 *
 * Displays ILL request list with direction tabs, search, and status filter.
 */
class illIndexAction extends AhgController
{
    public function execute($request)
    {
        
        // Load framework
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';

        // Load ILLService
        $servicePath = \sfConfig::get('sf_plugins_dir')
            . '/ahgLibraryPlugin/lib/Service/ILLService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        // Flash messages
        $this->notice = $this->getUser()->getFlash('notice');
        $this->error = $this->getUser()->getFlash('error');

        // Search/filter params
        $params = [
            'q'          => $request->getParameter('q', ''),
            'direction'  => $request->getParameter('direction', ''),
            'ill_status' => $request->getParameter('ill_status', ''),
            'page'       => $request->getParameter('page', 1),
        ];

        $this->q         = $params['q'];
        $this->direction = $params['direction'];
        $this->illStatus = $params['ill_status'];

        try {
            if (!class_exists('ILLService')) {
                throw new \RuntimeException('ILLService not available.');
            }

            $service = ILLService::getInstance();
            $result = $service->search($params);

            $this->results    = $result['items'];
            $this->total      = $result['total'];
            $this->page       = $result['page'];
            $this->totalPages = $result['pages'];
        } catch (\Exception $e) {
            $this->results    = [];
            $this->total      = 0;
            $this->page       = 1;
            $this->totalPages = 0;
            $this->error = $e->getMessage();
        }
    }
}
