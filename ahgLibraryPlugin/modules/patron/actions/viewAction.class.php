<?php

use AtomFramework\Http\Controllers\AhgController;

class patronViewAction extends AhgController
{
    public function execute($request)
    {
        // Load framework
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';

        // Load PatronService
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/PatronService.php';

        $id = (int) $request->getParameter('id');
        if (!$id) {
            $this->forward404();
        }

        $service = PatronService::getInstance();

        $this->patron = $service->find($id);
        if (!$this->patron) {
            $this->forward404();
        }

        // Current checkouts
        $this->checkouts = $service->getCheckouts($id);

        // Active holds
        $this->holds = $service->getHolds($id);

        // Outstanding fines
        $this->fines = $service->getFines($id);

        // Checkout history
        $this->history = $service->getHistory($id, 50);
    }
}
