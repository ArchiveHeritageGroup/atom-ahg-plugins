<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * View a single ILL request.
 */
class illViewAction extends AhgController
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

        $id = (int) $request->getParameter('id');

        if (!$id) {
            $this->forward404(__('ILL request not found.'));
        }

        try {
            if (!class_exists('ILLService')) {
                throw new \RuntimeException('ILLService not available.');
            }

            $service = ILLService::getInstance();
            $this->illRequest = $service->find($id);

            if (!$this->illRequest) {
                $this->forward404(__('ILL request not found.'));
            }
        } catch (\Exception $e) {
            $this->illRequest = null;
            $this->error = $e->getMessage();
        }
    }
}
