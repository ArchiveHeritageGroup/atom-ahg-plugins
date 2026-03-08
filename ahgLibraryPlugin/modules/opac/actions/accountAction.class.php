<?php

declare(strict_types=1);

use AtomFramework\Http\Controllers\AhgController;

/**
 * OPAC account — "My Account" for logged-in patrons.
 *
 * Shows current loans, holds, fines.
 *
 * @package    ahgLibraryPlugin
 * @subpackage opac
 */
class opacAccountAction extends AhgController
{
    public function execute($request)
    {
        // Must be authenticated
        if (!$this->getUser()->isAuthenticated()) {
            $this->forward('admin', 'secure');
        }

        // Initialize database
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';

        // Load services
        require_once sfConfig::get('sf_root_dir')
            . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/OpacService.php';
        require_once sfConfig::get('sf_root_dir')
            . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/PatronService.php';
        require_once sfConfig::get('sf_root_dir')
            . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/FineService.php';

        // Find patron record for current user
        $userId = (int) $this->getUser()->getAttribute('user_id');
        $patronService = PatronService::getInstance();
        $patron = $patronService->findByUserId($userId);

        if (!$patron) {
            $this->account = null;
            return;
        }

        $service = OpacService::getInstance();
        $this->account = $service->getPatronAccount($patron->id);
    }
}
