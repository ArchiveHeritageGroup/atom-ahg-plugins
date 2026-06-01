<?php

use AtomFramework\Http\Controllers\AhgController;
require_once dirname(__FILE__) . '/../../../lib/DonutActionSupport.php';

/**
 * GET /ai/donut/results/:id - return a stored extraction as JSON.
 */
class aiDonutResultAction extends AhgController
{
    public function execute($request)
    {
        return $this->renderText(json_encode(DonutActionSupport::result($request)));
    }
}
