<?php

use AtomFramework\Http\Controllers\AhgController;
class extendedRightsEmbargoesAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $culture = $this->culture();

        // Load service
        require_once $this->config('sf_root_dir') . '/atom-ahg-plugins/ahgExtendedRightsPlugin/lib/Services/ExtendedRightsService.php';

        $service = new \ahgExtendedRightsPlugin\Services\ExtendedRightsService($culture);

        $this->embargoes = $service->getActiveEmbargoes();
    }
}
