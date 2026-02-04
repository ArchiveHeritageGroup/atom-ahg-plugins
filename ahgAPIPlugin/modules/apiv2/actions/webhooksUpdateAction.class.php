<?php

/**
 * Update a webhook
 *
 * PUT /api/v2/webhooks/:id
 *
 * Request body (all fields optional):
 * {
 *   "name": "Updated Name",
 *   "url": "https://example.com/new-webhook",
 *   "events": ["item.created"],
 *   "entity_types": ["informationobject"],
 *   "is_active": false
 * }
 */
class apiv2WebhooksUpdateAction extends AhgApiAction
{
    public function PUT($request)
    {
        // Require authentication
        if (!$this->user) {
            return $this->unauthorized('Authentication required');
        }

        // Check scope
        if (!$this->hasScope('write')) {
            return $this->forbidden('Insufficient scope: write required');
        }

        $webhookId = (int) $request->getAttribute('sf_route')->getVariable('id');
        $data = $this->getJsonBody();

        $result = \AhgAPI\Services\WebhookService::update($webhookId, $this->user->id, $data);

        if (!$result['success']) {
            $code = $result['error'] === 'Webhook not found' ? 404 : 400;
            return $this->error($result['error'], $code);
        }

        return $this->success($result['data']);
    }
}
