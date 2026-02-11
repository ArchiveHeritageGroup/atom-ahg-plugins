<?php

use AtomFramework\Http\Controllers\AhgApiController;
/**
 * Get a single webhook by ID
 *
 * GET /api/v2/webhooks/:id
 */
class apiv2WebhooksReadAction extends AhgApiController
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

        $webhook = \AhgAPI\Services\WebhookService::getById($webhookId);

        if (!$webhook) {
            return $this->notFound('Webhook not found');
        }

        // Check ownership
        if ($webhook->user_id !== $this->user->id && !$this->isAdmin()) {
            return $this->forbidden('Access denied');
        }

        // Include delivery stats
        $stats = \AhgAPI\Services\WebhookService::getDeliveryStats($webhookId);

        $webhook->delivery_stats = $stats;

        return $this->success($webhook);
    }
}
