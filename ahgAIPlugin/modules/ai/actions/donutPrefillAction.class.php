<?php

use AtomFramework\Http\Controllers\AhgController;
require_once dirname(__FILE__) . '/../../../lib/DonutActionSupport.php';

/**
 * POST /ai/donut/prefill - extract structured fields from a document image
 * by path; returns JSON for the edit-form overlay.
 */
class aiDonutPrefillAction extends AhgController
{
    public function execute($request)
    {
        $userId = DonutActionSupport::currentUserId($this);
        $result = DonutActionSupport::prefill($request, $userId);

        return $this->renderText(json_encode($result));
    }
}
