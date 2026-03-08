<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Claim a missing serial issue (POST).
 *
 * Expects: issue_id.
 * Redirects to serial/view for the parent subscription.
 */
class serialClaimAction extends AhgController
{
    public function execute($request)
    {
        
        // POST only
        if ('POST' !== $request->getMethod()) {
            $this->redirect(['module' => 'serial', 'action' => 'index']);
        }

        // Load framework
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';

        // Load SerialService
        $servicePath = \sfConfig::get('sf_plugins_dir')
            . '/ahgLibraryPlugin/lib/Service/SerialService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $issueId = (int) $request->getParameter('issue_id');
        $subscriptionId = (int) $request->getParameter('subscription_id');

        if (!$issueId) {
            $this->getUser()->setFlash('error', __('Issue ID is required.'));
            $this->redirect(['module' => 'serial', 'action' => 'index']);
        }

        try {
            if (!class_exists('SerialService')) {
                throw new \RuntimeException('SerialService not available.');
            }

            $service = SerialService::getInstance();
            $result = $service->claimIssue($issueId);

            if ($result) {
                $this->getUser()->setFlash('notice', __('Issue claimed successfully.'));
            } else {
                $this->getUser()->setFlash('error', __('Could not claim issue.'));
            }
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', __('Claim error: %1%', ['%1%' => $e->getMessage()]));
        }

        if ($subscriptionId) {
            $this->redirect(['module' => 'serial', 'action' => 'view', 'id' => $subscriptionId]);
        }

        $this->redirect(['module' => 'serial', 'action' => 'index']);
    }
}
