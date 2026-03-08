<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * View a serial subscription with issues and gap analysis.
 */
class serialViewAction extends AhgController
{
    public function execute($request)
    {
        
        // Load framework
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';

        // Load SerialService
        $servicePath = \sfConfig::get('sf_plugins_dir')
            . '/ahgLibraryPlugin/lib/Service/SerialService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        // Flash messages
        $this->notice = $this->getUser()->getFlash('notice');
        $this->error = $this->getUser()->getFlash('error');

        $id = (int) $request->getParameter('id');

        if (!$id) {
            $this->forward404(__('Subscription not found.'));
        }

        try {
            if (!class_exists('SerialService')) {
                throw new \RuntimeException('SerialService not available.');
            }

            $service = SerialService::getInstance();
            $data = $service->getSubscription($id);

            if (!$data) {
                $this->forward404(__('Subscription not found.'));
            }

            $this->subscription = $data['subscription'];
            $this->issues       = $data['issues'];
            $this->gaps          = $service->getGaps($id);
        } catch (\Exception $e) {
            $this->subscription = null;
            $this->issues       = [];
            $this->gaps          = [];
            $this->error = $e->getMessage();
        }
    }
}
