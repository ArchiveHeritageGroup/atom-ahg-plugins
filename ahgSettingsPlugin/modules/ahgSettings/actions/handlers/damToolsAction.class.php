<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * DAM Tools Action
 */
class damToolsAction extends AhgController
{
    public function execute($request)
    {
        // Check authentication
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        // Check admin access
        if (!$this->getUser()->hasCredential('administrator')) {
            $this->forward('admin', 'secure');
        }
    }
}
