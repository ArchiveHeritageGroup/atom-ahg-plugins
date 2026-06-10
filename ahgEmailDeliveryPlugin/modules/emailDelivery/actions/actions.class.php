<?php

/**
 * emailDelivery actions (#145) — bounce webhook + suppression admin.
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * Licensed under the GNU Affero General Public License v3.0 or later.
 */

use AtomFramework\Http\Controllers\AhgController;

class emailDeliveryActions extends AhgController
{
    protected ?EmailSuppressionService $service = null;

    protected function getService(): EmailSuppressionService
    {
        if ($this->service === null) {
            require_once $this->config('sf_root_dir').'/plugins/ahgEmailDeliveryPlugin/lib/Services/EmailSuppressionService.php';
            $this->service = new EmailSuppressionService();
        }
        return $this->service;
    }

    protected function requireAdmin(): void
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
            return;
        }
        if (!$this->getUser()->hasCredential('administrator')) {
            $this->forward404('Administrator access required');
        }
    }

    /**
     * Provider bounce/complaint webhook. Server-to-server POST with a JSON body.
     * No CSRF (no session); accepts an optional shared-secret token check.
     */
    public function executeBounce($request)
    {
        if (!$request->isMethod('post')) {
            return $this->renderJsonError('POST required', 405);
        }

        // Optional shared secret: set app_email_webhook_secret in config.php to enforce.
        $secret = \sfConfig::get('app_email_webhook_secret', '');
        if ($secret !== '') {
            $given = $request->getHttpHeader('X-Webhook-Secret') ?: $request->getParameter('secret', '');
            if (!hash_equals($secret, (string) $given)) {
                return $this->renderJsonError('Unauthorized', 401);
            }
        }

        $raw = $request->getContent();
        $payload = $raw ? json_decode($raw, true) : null;
        if (!is_array($payload)) {
            // Fall back to form-encoded params.
            $payload = $request->getPostParameters() ?: [];
        }

        // SNS subscription confirmation handshake (SES via SNS).
        if (isset($payload['Type']) && $payload['Type'] === 'SubscriptionConfirmation') {
            return $this->renderJson(['ok' => true, 'note' => 'SubscriptionConfirmation received; confirm SubscribeURL manually']);
        }

        $n = $this->getService()->ingestWebhook($payload);
        return $this->renderJson(['ok' => true, 'suppressed' => $n]);
    }

    public function executeSuppressions($request)
    {
        $this->requireAdmin();
        $this->search = trim((string) $request->getParameter('q', ''));
        $this->reasonFilter = trim((string) $request->getParameter('reason', ''));
        $this->rows = $this->getService()->listAll($this->search, $this->reasonFilter);
        $this->stats = $this->getService()->stats();
        $this->reasons = EmailSuppressionService::REASONS;
    }

    public function executeAdd($request)
    {
        $this->requireAdmin();
        if ($request->isMethod('post')) {
            $email = (string) $request->getParameter('email', '');
            $reason = (string) $request->getParameter('reason', 'manual');
            $ok = $this->getService()->suppress($email, $reason, [
                'source' => 'manual',
                'detail' => $request->getParameter('detail') ?: null,
                'created_by' => $this->getUser()->getAttribute('user_id'),
            ]);
            $this->getUser()->setFlash($ok ? 'notice' : 'error',
                $ok ? 'Address suppressed.' : 'Invalid email address.');
        }
        $this->redirect('emailDelivery/suppressions');
    }

    public function executeRemove($request)
    {
        $this->requireAdmin();
        if ($request->isMethod('post')) {
            $email = (string) $request->getParameter('email', '');
            $ok = $this->getService()->unsuppress($email);
            $this->getUser()->setFlash($ok ? 'notice' : 'error',
                $ok ? 'Suppression removed.' : 'Address not found.');
        }
        $this->redirect('emailDelivery/suppressions');
    }
}
