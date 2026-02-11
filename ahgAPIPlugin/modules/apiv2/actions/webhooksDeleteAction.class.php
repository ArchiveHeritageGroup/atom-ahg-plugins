<?php

use AtomFramework\Http\Controllers\AhgApiController;
/**
 * Delete a webhook
 *
 * DELETE /api/v2/webhooks/:id
 */
class apiv2WebhooksDeleteAction extends AhgApiController
{
    public function DELETE($request)
    {
        // Require authentication
        if (!$this->user) {
            return $this->unauthorized('Authentication required');
        }

        // Check scope
        if (!$this->hasScope('delete')) {
            return $this->forbidden('Insufficient scope: delete required');
        }

        $webhookId = (int) $request->getAttribute('sf_route')->getVariable('id');

        $result = \AhgAPI\Services\WebhookService::delete($webhookId, $this->user->id);

        if (!$result['success']) {
            $code = $result['error'] === 'Webhook not found' ? 404 : 400;
            return $this->error($result['error'], $code);
        }

        return $this->success(['deleted' => true]);
    }
}
