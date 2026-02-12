<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Webhook Management Action
 *
 * Provides UI for managing API webhooks - Issue #82
 */
class settingsWebhooksAction extends AhgController
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

        // Load WebhookService
        $servicePath = sfConfig::get('sf_plugins_dir') . '/ahgAPIPlugin/lib/Services/WebhookService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $this->form = new sfForm();

        // Handle form submission
        if ($request->isMethod('post')) {
            $action = $request->getParameter('action_type');

            if ($action === 'create') {
                $this->createWebhook($request);
            } elseif ($action === 'delete') {
                $this->deleteWebhook($request);
            } elseif ($action === 'toggle') {
                $this->toggleWebhook($request);
            } elseif ($action === 'regenerate') {
                $this->regenerateSecret($request);
            }

            $this->redirect(['module' => 'ahgSettings', 'action' => 'webhooks']);
        }

        // Load existing webhooks (all users for admin view)
        $this->webhooks = $this->getAllWebhooks();

        // Get users for dropdown
        $this->users = $this->getUsers();

        // Get supported events and entity types
        $this->supportedEvents = \AhgAPI\Services\WebhookService::getSupportedEvents();
        $this->supportedEntityTypes = \AhgAPI\Services\WebhookService::getSupportedEntityTypes();

        // Event labels for display
        $this->eventLabels = [
            'item.created' => 'Record Created',
            'item.updated' => 'Record Updated',
            'item.deleted' => 'Record Deleted',
            'item.published' => 'Record Published',
            'item.unpublished' => 'Record Unpublished',
        ];

        // Entity type labels for display
        $this->entityTypeLabels = [
            'informationobject' => 'Descriptions',
            'actor' => 'Authority Records',
            'repository' => 'Repositories',
            'accession' => 'Accessions',
            'term' => 'Terms',
        ];
    }

    protected function getAllWebhooks()
    {
        return \Illuminate\Database\Capsule\Manager::table('ahg_webhook as w')
            ->leftJoin('user as u', 'w.user_id', '=', 'u.id')
            ->select([
                'w.id',
                'w.user_id',
                'w.name',
                'w.url',
                'w.events',
                'w.entity_types',
                'w.is_active',
                'w.failure_count',
                'w.last_triggered_at',
                'w.created_at',
                'u.username'
            ])
            ->orderBy('w.created_at', 'desc')
            ->get()
            ->map(function ($webhook) {
                $webhook->events = json_decode($webhook->events, true) ?? [];
                $webhook->entity_types = json_decode($webhook->entity_types, true) ?? [];
                $webhook->stats = $this->getWebhookStats($webhook->id);
                return $webhook;
            })
            ->all();
    }

    protected function getWebhookStats($webhookId)
    {
        $stats = \Illuminate\Database\Capsule\Manager::table('ahg_webhook_delivery')
            ->where('webhook_id', $webhookId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status IN ("pending", "retrying") THEN 1 ELSE 0 END) as pending
            ')
            ->first();

        return [
            'total' => (int) ($stats->total ?? 0),
            'success' => (int) ($stats->success ?? 0),
            'failed' => (int) ($stats->failed ?? 0),
            'pending' => (int) ($stats->pending ?? 0),
        ];
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

    protected function createWebhook($request)
    {
        $name = trim($request->getParameter('webhook_name'));
        $url = trim($request->getParameter('webhook_url'));
        $userId = (int) $request->getParameter('user_id');
        $events = $request->getParameter('events', []);
        $entityTypes = $request->getParameter('entity_types', []);

        if (empty($name) || empty($url) || empty($userId)) {
            $this->getUser()->setFlash('error', 'Name, URL, and user are required.');
            return;
        }

        $result = \AhgAPI\Services\WebhookService::create($userId, [
            'name' => $name,
            'url' => $url,
            'events' => $events,
            'entity_types' => $entityTypes,
        ]);

        if ($result['success']) {
            $this->getUser()->setFlash('new_webhook_secret', $result['data']['secret']);
            $this->getUser()->setFlash('success', "Webhook '{$name}' created. Copy the secret now - it won't be shown again!");
        } else {
            $this->getUser()->setFlash('error', $result['error']);
        }
    }

    protected function deleteWebhook($request)
    {
        $webhookId = (int) $request->getParameter('webhook_id');

        // Get webhook to find owner
        $webhook = \Illuminate\Database\Capsule\Manager::table('ahg_webhook')
            ->where('id', $webhookId)
            ->first();

        if ($webhook) {
            $result = \AhgAPI\Services\WebhookService::delete($webhookId, $webhook->user_id);

            if ($result['success']) {
                $this->getUser()->setFlash('success', 'Webhook deleted.');
            } else {
                $this->getUser()->setFlash('error', $result['error']);
            }
        }
    }

    protected function toggleWebhook($request)
    {
        $webhookId = (int) $request->getParameter('webhook_id');

        $webhook = \Illuminate\Database\Capsule\Manager::table('ahg_webhook')
            ->where('id', $webhookId)
            ->first();

        if ($webhook) {
            \Illuminate\Database\Capsule\Manager::table('ahg_webhook')
                ->where('id', $webhookId)
                ->update([
                    'is_active' => $webhook->is_active ? 0 : 1,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            $status = $webhook->is_active ? 'deactivated' : 'activated';
            $this->getUser()->setFlash('success', "Webhook {$status}.");
        }
    }

    protected function regenerateSecret($request)
    {
        $webhookId = (int) $request->getParameter('webhook_id');

        $webhook = \Illuminate\Database\Capsule\Manager::table('ahg_webhook')
            ->where('id', $webhookId)
            ->first();

        if ($webhook) {
            $result = \AhgAPI\Services\WebhookService::regenerateSecret($webhookId, $webhook->user_id);

            if ($result['success']) {
                $this->getUser()->setFlash('new_webhook_secret', $result['secret']);
                $this->getUser()->setFlash('success', 'Secret regenerated. Copy it now - it will not be shown again!');
            } else {
                $this->getUser()->setFlash('error', $result['error']);
            }
        }
    }
}
