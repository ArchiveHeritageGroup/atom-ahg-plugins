<?php

use AtomFramework\Http\Controllers\AhgApiController;
/**
 * List webhooks for the authenticated user
 *
 * GET /api/v2/webhooks
 */
class apiv2WebhooksBrowseAction extends AhgApiController
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

        $webhooks = \AhgAPI\Services\WebhookService::getByUser($this->user->id);

        return $this->success([
            'total' => count($webhooks),
            'webhooks' => $webhooks,
        ]);
    }
}
