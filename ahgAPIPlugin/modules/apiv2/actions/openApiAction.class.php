<?php

use AtomFramework\Http\Controllers\AhgApiController;

/**
 * GET /api/v2/openapi.json — the OpenAPI 3.1 description of the apiv2 API (#129).
 * Public (the spec is the API contract); generated from the live route table.
 */
class apiv2OpenApiAction extends AhgApiController
{
    public function GET($request)
    {
        require_once dirname(__DIR__, 3) . '/lib/Services/OpenApiGenerator.php';
        $spec = (new \AhgApi\Services\OpenApiGenerator())->generate();
        $this->getResponse()->setHttpHeader('Content-Type', 'application/json; charset=utf-8');

        return $this->renderText(json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
