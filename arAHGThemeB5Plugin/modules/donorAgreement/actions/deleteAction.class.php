<?php

class donorAgreementDeleteAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $id = (int) $request->getParameter('id');
        if (!$id) {
            $this->forward404('Agreement ID required');
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';

        $service = new \AtomFramework\Services\DonorAgreement\DonorAgreementService();

        try {
            $service->delete($id);
            $this->context->user->setFlash('notice', 'Agreement deleted successfully.');
        } catch (\Exception $e) {
            $this->context->user->setFlash('error', 'Error deleting agreement: ' . $e->getMessage());
        }

        $this->redirect(['module' => 'donorAgreement', 'action' => 'browse']);
    }
}
