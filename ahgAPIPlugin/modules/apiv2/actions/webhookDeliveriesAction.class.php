<?php

use AtomFramework\Http\Controllers\AhgApiController;
/**
 * Get delivery logs for a webhook
 *
 * GET /api/v2/webhooks/:id/deliveries
 *
 * Query parameters:
 * - limit: Number of records (default 50, max 100)
 * - offset: Pagination offset (default 0)
 */
class apiv2WebhookDeliveriesAction extends AhgApiController
{
    public function GET($request)
    {
        // Require authentication
        if (!$this->user) {
            return $this->unauthorized('Authentication required');
        }

        // Check scope
        if (!$this->hasScope('read')) {
            return $this->forbidden('Insufficient scope: read required');
        }

        $webhookId = (int) $request->getAttribute('sf_route')->getVariable('id');

        // Verify ownership
        $webhook = \AhgAPI\Services\WebhookService::getById($webhookId);
        if (!$webhook) {
            return $this->notFound('Webhook not found');
        }

        if ($webhook->user_id !== $this->user->id && !$this->isAdmin()) {
            return $this->forbidden('Access denied');
        }

        $limit = min(100, max(1, (int) ($request->getParameter('limit') ?? 50)));
        $offset = max(0, (int) ($request->getParameter('offset') ?? 0));

        $deliveries = \AhgAPI\Services\WebhookService::getDeliveryLogs($webhookId, $limit, $offset);
        $stats = \AhgAPI\Services\WebhookService::getDeliveryStats($webhookId);

        return $this->success([
            'webhook_id' => $webhookId,
            'stats' => $stats,
            'limit' => $limit,
            'offset' => $offset,
            'deliveries' => $deliveries,
        ]);
    }
}
