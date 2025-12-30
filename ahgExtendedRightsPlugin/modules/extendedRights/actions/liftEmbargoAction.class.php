<?php

class extendedRightsLiftEmbargoAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        // Load service
        require_once sfConfig::get('sf_root_dir').'/atom-framework/app/Services/Rights/ExtendedRightsService.php';

        $service = new \App\Services\Rights\ExtendedRightsService('en');

        $embargoId = (int) $request->getParameter('id');
        $service->liftEmbargo($embargoId);

        $this->getUser()->setFlash('notice', 'Embargo lifted.');
        $this->redirect(['module' => 'extendedRights', 'action' => 'embargoes']);
    }
}
