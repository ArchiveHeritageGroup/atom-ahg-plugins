<?php

use AtomFramework\Http\Controllers\AhgController;
require_once dirname(__FILE__) . '/../../../lib/DonutActionSupport.php';

/**
 * GET /ai/donut - DONUT dashboard: gateway health + training status.
 */
class aiDonutDashboardAction extends AhgController
{
    public function execute($request)
    {
        $service = DonutActionSupport::service();

        if ($request->isXmlHttpRequest() || $request->getParameter('format') === 'json') {
            return $this->renderText(json_encode([
                'success'  => true,
                'health'   => $service->health(),
                'training' => $service->trainingStatus(),
            ]));
        }

        $this->health = $service->health();
        $this->training = $service->trainingStatus();
        $this->available = $this->health !== null;

        return sfView::SUCCESS;
    }
}
