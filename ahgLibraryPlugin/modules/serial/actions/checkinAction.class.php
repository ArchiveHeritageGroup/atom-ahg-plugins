<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Check in a serial issue (POST).
 *
 * Expects: subscription_id, volume, issue_number, issue_date, received_date, supplement, notes.
 * Redirects to serial/view.
 */
class serialCheckinAction extends AhgController
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

        $subscriptionId = (int) $request->getParameter('subscription_id');

        if (!$subscriptionId) {
            $this->getUser()->setFlash('error', __('Subscription ID is required.'));
            $this->redirect(['module' => 'serial', 'action' => 'index']);
        }

        $issueData = [
            'volume'        => $request->getParameter('volume'),
            'issue_number'  => $request->getParameter('issue_number'),
            'issue_date'    => $request->getParameter('issue_date'),
            'received_date' => $request->getParameter('received_date', date('Y-m-d')),
            'supplement'    => $request->getParameter('supplement'),
            'notes'         => $request->getParameter('notes'),
        ];

        try {
            if (!class_exists('SerialService')) {
                throw new \RuntimeException('SerialService not available.');
            }

            $service = SerialService::getInstance();
            $service->checkinIssue($subscriptionId, $issueData);
            $this->getUser()->setFlash('notice', __('Issue checked in successfully.'));
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', __('Check-in error: %1%', ['%1%' => $e->getMessage()]));
        }

        $this->redirect(['module' => 'serial', 'action' => 'view', 'id' => $subscriptionId]);
    }
}
