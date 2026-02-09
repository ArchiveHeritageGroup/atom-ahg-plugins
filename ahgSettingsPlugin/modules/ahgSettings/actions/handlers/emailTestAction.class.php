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

        \AhgCore\Core\AhgDb::init();

        $testEmail = $request->getParameter('email');

        if (empty($testEmail)) {
            $this->getUser()->setFlash('error', 'Please enter a test email address');
            $this->redirect(['module' => 'ahgSettings', 'action' => 'email']);
        }

        $result = \AhgCore\Services\EmailService::testConnection($testEmail);

        if ($result['success']) {
            $this->getUser()->setFlash('success', $result['message']);
        } else {
            $this->getUser()->setFlash('error', $result['message']);
        }

        $this->redirect(['module' => 'ahgSettings', 'action' => 'email']);
    }
}
