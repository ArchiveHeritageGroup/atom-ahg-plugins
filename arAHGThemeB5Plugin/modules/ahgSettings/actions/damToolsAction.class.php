<?php

/**
 * DAM Tools Action
 */
class damToolsAction extends sfAction
{
    public function execute($request)
    {
        // Check authentication
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('user/login');
        }

        // Check admin access
        if (!$this->context->user->hasCredential('administrator')) {
            $this->forward('admin', 'secure');
        }
    }
}
