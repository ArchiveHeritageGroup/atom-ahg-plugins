<?php
use AtomExtensions\Services\AclService;

use Illuminate\Database\Capsule\Manager as DB;

class AhgSettingsEmailTestAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAdministrator()) {
            AclService::forwardUnauthorized();
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/EmailService.php';

        $testEmail = $request->getParameter('email');
        
        if (empty($testEmail)) {
            $this->getUser()->setFlash('error', 'Please enter a test email address');
            $this->redirect('settings/email');
        }

        $result = EmailService::testConnection($testEmail);

        if ($result['success']) {
            $this->getUser()->setFlash('success', $result['message']);
        } else {
            $this->getUser()->setFlash('error', $result['message']);
        }

        $this->redirect('settings/email');
    }
}
