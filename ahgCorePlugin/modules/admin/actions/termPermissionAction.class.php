<?php

/**
 * AHG stub for admin/termPermission action.
 * Replaces apps/qubit/modules/admin/actions/termPermissionAction.class.php.
 */
class AdminTermPermissionAction extends sfAction
{
    public function execute($request)
    {
        $this->use = null;
        if (isset($request->use)) {
            $this->use = $request->use;
        }
    }
}
