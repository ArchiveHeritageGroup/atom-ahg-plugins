<?php

use AtomFramework\Http\Controllers\AhgController;
class donorAgreementDeleteAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $id = (int) $request->getParameter('id');
        if (!$id) {
            $this->forward404('Agreement ID required');
        }

        \AhgCore\Core\AhgDb::init();

        $service = new \AtomFramework\Services\DonorAgreement\DonorAgreementService();

        try {
            $service->delete($id);
            $this->getUser()->setFlash('notice', 'Agreement deleted successfully.');
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Error deleting agreement: ' . $e->getMessage());
        }

        $this->redirect(['module' => 'donorAgreement', 'action' => 'browse']);
    }
}
