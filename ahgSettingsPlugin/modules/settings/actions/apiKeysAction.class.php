<?php

class settingsApiKeysAction extends sfAction
{
    public function execute($request)
    {
        // Check admin permission
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        // Load framework
        $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($frameworkPath)) {
            require_once $frameworkPath;
        }

        $this->form = new sfForm();

        // Handle form submission
        if ($request->isMethod('post')) {
            $action = $request->getParameter('action_type');

            if ($action === 'create') {
                $this->createApiKey($request);
            } elseif ($action === 'delete') {
                $this->deleteApiKey($request);
            } elseif ($action === 'toggle') {
                $this->toggleApiKey($request);
            }

            $this->redirect(['module' => 'settings', 'action' => 'apiKeys']);
        }

        // Load existing API keys
        $this->apiKeys = $this->getApiKeys();

        // Get users for dropdown
        $this->users = $this->getUsers();
    }

    protected function getApiKeys()
    {
        return \Illuminate\Database\Capsule\Manager::table('ahg_api_key as k')
            ->leftJoin('user as u', 'k.user_id', '=', 'u.id')
            ->select([
                'k.id',
                'k.user_id',
                'k.name',
                'k.api_key_prefix',
                'k.scopes',
                'k.rate_limit',
                'k.expires_at',
                'k.last_used_at',
                'k.is_active',
                'k.created_at',
                'u.username'
            ])
            ->orderBy('k.created_at', 'desc')
            ->get()
            ->all();
    }

    protected function getUsers()
    {
        return \Illuminate\Database\Capsule\Manager::table('user')
            ->whereNotNull('username')
            ->where('active', 1)
            ->orderBy('username')
            ->select(['id', 'username', 'email'])
            ->get()
            ->all();
    }

    protected function createApiKey($request)
    {
        $name = trim($request->getParameter('key_name'));
        $userId = (int) $request->getParameter('user_id');
        $scopes = $request->getParameter('scopes', ['read']);
        $rateLimit = (int) $request->getParameter('rate_limit', 1000);
        $expiresAt = $request->getParameter('expires_at');

        if (empty($name) || empty($userId)) {
            $this->getUser()->setFlash('error', 'Name and user are required.');
            return;
        }

        // Generate secure API key
        $apiKey = bin2hex(random_bytes(32));
        $apiKeyPrefix = substr($apiKey, 0, 8);

        // Hash the key for storage (store hash, display once)
        $hashedKey = hash('sha256', $apiKey);

        \Illuminate\Database\Capsule\Manager::table('ahg_api_key')->insert([
            'user_id' => $userId,
            'name' => $name,
            'api_key' => $hashedKey,
            'api_key_prefix' => $apiKeyPrefix,
            'scopes' => json_encode($scopes),
            'rate_limit' => $rateLimit,
            'expires_at' => !empty($expiresAt) ? $expiresAt : null,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Store the plain key in flash to show once
        $this->getUser()->setFlash('new_api_key', $apiKey);
        $this->getUser()->setFlash('success', "API key '{$name}' created. Copy it now - it won't be shown again!");
    }

    protected function deleteApiKey($request)
    {
        $keyId = (int) $request->getParameter('key_id');
        
        \Illuminate\Database\Capsule\Manager::table('ahg_api_key')
            ->where('id', $keyId)
            ->delete();

        $this->getUser()->setFlash('success', 'API key deleted.');
    }

    protected function toggleApiKey($request)
    {
        $keyId = (int) $request->getParameter('key_id');
        
        $key = \Illuminate\Database\Capsule\Manager::table('ahg_api_key')
            ->where('id', $keyId)
            ->first();

        if ($key) {
            \Illuminate\Database\Capsule\Manager::table('ahg_api_key')
                ->where('id', $keyId)
                ->update(['is_active' => $key->is_active ? 0 : 1]);

            $status = $key->is_active ? 'deactivated' : 'activated';
            $this->getUser()->setFlash('success', "API key {$status}.");
        }
    }
}
