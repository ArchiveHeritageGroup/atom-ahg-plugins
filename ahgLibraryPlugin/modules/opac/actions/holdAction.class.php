<?php

declare(strict_types=1);

use AtomFramework\Http\Controllers\AhgController;

/**
 * OPAC hold — place a hold on a library item (POST only).
 *
 * @package    ahgLibraryPlugin
 * @subpackage opac
 */
class opacHoldAction extends AhgController
{
    public function execute($request)
    {
        // Must be authenticated
        if (!$this->getUser()->isAuthenticated()) {
            $this->forward('admin', 'secure');
        }

        // POST only
        if ('POST' !== $request->getMethod()) {
            $this->redirect(['module' => 'opac', 'action' => 'index']);
        }

        // Initialize database
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';

        // Load services
        require_once sfConfig::get('sf_root_dir')
            . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/PatronService.php';
        require_once sfConfig::get('sf_root_dir')
            . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/HoldService.php';

        $libraryItemId = (int) $request->getParameter('library_item_id', 0);

        if ($libraryItemId <= 0) {
            $this->getUser()->setFlash('error', __('Invalid library item.'));
            $this->redirect(['module' => 'opac', 'action' => 'index']);
        }

        // Find patron record for current user
        $userId = (int) $this->getUser()->getAttribute('user_id');
        $patronService = PatronService::getInstance();
        $patron = $patronService->findByUserId($userId);

        if (!$patron) {
            $this->getUser()->setFlash('error', __('You do not have a library patron account. Please contact library staff to register.'));
            $this->redirect(['module' => 'opac', 'action' => 'view', 'id' => $libraryItemId]);
        }

        // Place the hold
        $holdService = HoldService::getInstance();
        $notes = trim($request->getParameter('hold_notes', ''));
        $result = $holdService->placeHold($libraryItemId, $patron->id, $notes ?: null);

        if ($result['success']) {
            $this->getUser()->setFlash('notice', $result['message'] ?? __('Hold placed successfully.'));
        } else {
            $this->getUser()->setFlash('error', $result['error'] ?? __('Unable to place hold.'));
        }

        $this->redirect(['module' => 'opac', 'action' => 'view', 'id' => $libraryItemId]);
    }
}
