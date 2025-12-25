<?php

class donorAgreementRemindersAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';

        $service = new \AtomFramework\Services\DonorAgreement\DonorAgreementService();
        $this->reminders = $service->getPendingReminders();
    }
}
