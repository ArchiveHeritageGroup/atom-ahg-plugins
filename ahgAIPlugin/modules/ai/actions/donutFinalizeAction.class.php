<?php

use AtomFramework\Http\Controllers\AhgController;
require_once dirname(__FILE__) . '/../../../lib/DonutActionSupport.php';

/**
 * POST /ai/donut/finalize - link a stored extraction to the information
 * object created from it (provenance finalisation).
 */
class aiDonutFinalizeAction extends AhgController
{
    public function execute($request)
    {
        return $this->renderText(json_encode(DonutActionSupport::finalize($request)));
    }
}
