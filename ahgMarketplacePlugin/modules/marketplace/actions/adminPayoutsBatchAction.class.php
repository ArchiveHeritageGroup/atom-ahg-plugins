<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Services/PayoutService.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\PayoutService;

class marketplaceAdminPayoutsBatchAction extends AhgController
{
    public function execute($request)
    {
        // Admin check
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }
        if (!$this->context->user->hasCredential('administrator')) {
            $this->getUser()->setFlash('error', 'Admin access required.');
            $this->redirect(['module' => 'marketplace', 'action' => 'browse']);
        }

        // POST only
        if (!$request->isMethod('post')) {
            $this->redirect(['module' => 'marketplace', 'action' => 'adminPayouts']);
        }

        $selectedIds = $request->getParameter('payout_ids', []);
        if (!is_array($selectedIds) || empty($selectedIds)) {
            $this->getUser()->setFlash('error', 'No payouts selected.');
            $this->redirect(['module' => 'marketplace', 'action' => 'adminPayouts']);
        }

        // Sanitize IDs
        $ids = array_map('intval', $selectedIds);
        $ids = array_filter($ids, function ($id) {
            return $id > 0;
        });

        if (empty($ids)) {
            $this->getUser()->setFlash('error', 'No valid payouts selected.');
            $this->redirect(['module' => 'marketplace', 'action' => 'adminPayouts']);
        }

        $adminUserId = (int) $this->context->user->getAttribute('user_id');

        $payoutService = new PayoutService();
        $result = $payoutService->batchProcess($ids, $adminUserId);

        $message = sprintf(
            'Batch processing complete: %d processed, %d skipped.',
            $result['processed'],
            $result['skipped']
        );

        if (!empty($result['errors'])) {
            $errorMessages = [];
            foreach ($result['errors'] as $err) {
                $errorMessages[] = 'Payout #' . $err['payout_id'] . ': ' . $err['error'];
            }
            $message .= ' Errors: ' . implode('; ', $errorMessages);
        }

        if ($result['processed'] > 0) {
            $this->getUser()->setFlash('notice', $message);
        } else {
            $this->getUser()->setFlash('error', $message);
        }

        $this->redirect(['module' => 'marketplace', 'action' => 'adminPayouts']);
    }
}
