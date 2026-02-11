<?php

use AtomFramework\Http\Controllers\AhgApiController;
class apiv2KeysCreateAction extends AhgApiController
{
    public function POST($request, $data = null)
    {
        if (empty($data['name'])) {
            return $this->error(400, 'Bad Request', 'name is required');
        }

        $scopes = $data['scopes'] ?? ['read'];
        $validScopes = ['read', 'write', 'delete', 'batch'];
        $scopes = array_intersect($scopes, $validScopes);

        $result = $this->apiKeyService->createApiKey(
            $this->apiKeyInfo['user_id'],
            $data['name'],
            $scopes
        );

        return $this->success([
            'id' => $result['id'],
            'api_key' => $result['api_key'],
            'prefix' => $result['prefix'],
            'name' => $result['name'],
            'scopes' => $result['scopes'],
            'message' => 'API key created. Store it securely - it will not be shown again.'
        ], 201);
    }
}
