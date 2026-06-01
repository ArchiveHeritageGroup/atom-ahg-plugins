<?php

use AtomFramework\Http\Controllers\AhgController;
require_once dirname(__FILE__) . '/../../../lib/DonutActionSupport.php';

/**
 * GET /ai/donut/positions - median field positions for the overlay UI.
 */
class aiDonutPositionsAction extends AhgController
{
    public function execute($request)
    {
        return $this->renderText(json_encode(DonutActionSupport::positions($request)));
    }
}
