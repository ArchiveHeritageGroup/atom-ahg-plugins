<?php

use AtomFramework\Http\Controllers\AhgController;
class extendedRightsLiftEmbargoAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        // Load service
        require_once $this->config('sf_root_dir') . '/atom-ahg-plugins/ahgExtendedRightsPlugin/lib/Services/ExtendedRightsService.php';

        $service = new \ahgExtendedRightsPlugin\Services\ExtendedRightsService('en');

        $embargoId = (int) $request->getParameter('id');
        $service->liftEmbargo($embargoId);

        $this->getUser()->setFlash('notice', 'Embargo lifted.');
        $this->redirect(['module' => 'extendedRights', 'action' => 'embargoes']);
    }
}
