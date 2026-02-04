<?php

/**
 * Regenerate webhook secret
 *
 * POST /api/v2/webhooks/:id/regenerate-secret
 *
 * Returns new secret (only time it's shown after initial creation)
 */
class apiv2WebhookRegenerateSecretAction extends AhgApiAction
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

        $webhookId = (int) $request->getAttribute('sf_route')->getVariable('id');

        $result = \AhgAPI\Services\WebhookService::regenerateSecret($webhookId, $this->user->id);

        if (!$result['success']) {
            $code = $result['error'] === 'Webhook not found' ? 404 : 400;
            return $this->error($result['error'], $code);
        }

        return $this->success([
            'webhook_id' => $webhookId,
            'secret' => $result['secret'],
            'message' => 'Secret regenerated. Store this securely - it will not be shown again.',
        ]);
    }
}
