<?php

/**
 * AHG stub for admin/secure action.
 * Replaces apps/qubit/modules/admin/actions/secureAction.class.php.
 */
class AdminSecureAction extends sfAction
{
    public function execute($request)
    {
        $this->getResponse()->setStatusCode(403);
    }
}
