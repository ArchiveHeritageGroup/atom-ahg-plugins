<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Public receipt lookup for a publication request. Anonymous submitters track
 * their request status with the receipt token issued at submission.
 *
 * Routes: /requesttopublish/receipt and /requesttopublish/receipt/:token
 */
class requestToPublishReceiptAction extends AhgController
{
    public function execute($request)
    {
        require_once $this->config('sf_plugins_dir') . '/ahgRequestToPublishPlugin/lib/Services/WorkflowService.php';
        $svc = new \ahgRequestToPublishPlugin\Services\WorkflowService();

        // POST = a token entered into the lookup form → canonical GET URL.
        if ($request->isMethod('post')) {
            $token = trim((string) $request->getParameter('token'));
            $this->redirect(['module' => 'requestToPublish', 'action' => 'receipt', 'token' => $token]);

            return;
        }

        $token = trim((string) $request->getParameter('token'));
        $this->token = $token;
        $this->workflow = $token !== '' ? $svc->getByToken($token) : null;
        $this->svc = $svc;
    }
}
