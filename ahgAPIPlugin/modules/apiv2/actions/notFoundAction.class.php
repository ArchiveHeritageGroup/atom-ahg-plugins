<?php

use AtomFramework\Http\Controllers\AhgApiController;
class apiv2NotFoundAction extends AhgApiController
{
    protected function authenticate(): bool
    {
        return true; // Allow access to return 404
    }

    public function execute($request)
    {
        $this->response->setHttpHeader('Content-Type', 'application/json; charset=utf-8');
        $this->response->setStatusCode(404);
        
        return $this->renderText(json_encode([
            'success' => false,
            'error' => 'Not Found',
            'message' => 'API endpoint not found: ' . $request->getPathInfo()
        ], JSON_PRETTY_PRINT));
    }
}
