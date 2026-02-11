<?php

use AtomFramework\Http\Controllers\AhgApiController;
/**
 * Create a new webhook
 *
 * POST /api/v2/webhooks
 *
 * Request body:
 * {
 *   "name": "My Webhook",
 *   "url": "https://example.com/webhook",
 *   "events": ["item.created", "item.updated"],
 *   "entity_types": ["informationobject", "actor"]
 * }
 */
class apiv2WebhooksCreateAction extends AhgApiController
{
    public function POST($request)
    {
        // Require authentication
        if (!$this->user) {
            return $this->unauthorized('Authentication required');
        }

        // Check scope
        if (!$this->hasScope('write')) {
            return $this->forbidden('Insufficient scope: write required');
        }

        $data = $this->getJsonBody();

        $result = \AhgAPI\Services\WebhookService::create($this->user->id, $data);

        if (!$result['success']) {
            return $this->error($result['error'], 400);
        }

        return $this->success($result['data'], 201);
    }
}
