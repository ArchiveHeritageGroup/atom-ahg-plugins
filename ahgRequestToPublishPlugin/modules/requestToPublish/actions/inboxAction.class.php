<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Curator inbox / triage queue for publication requests (admin).
 *
 * Route: /requesttopublish/inbox
 */
class requestToPublishInboxAction extends AhgController
{
    public function execute($request)
    {
        $user = $this->getUser();
        if (!$user->isAuthenticated()
            || !$user->hasGroup(\AtomExtensions\Constants\AclConstants::ADMINISTRATOR_ID)
        ) {
            \AtomExtensions\Services\AclService::forwardUnauthorized();
        }

        require_once $this->config('sf_plugins_dir') . '/ahgRequestToPublishPlugin/lib/Services/WorkflowService.php';
        $svc = new \ahgRequestToPublishPlugin\Services\WorkflowService();

        if ($request->isMethod('post')) {
            $rid = (int) $request->getParameter('request_id');
            switch ($request->getParameter('form_action')) {
                case 'assign':
                    $svc->assign($rid, (int) $user->getAttribute('user_id'), (string) $request->getParameter('assigned_name'));
                    break;
                case 'triage':
                    $svc->setTriage($rid, (string) $request->getParameter('triage_status'));
                    break;
                case 'priority':
                    $svc->setPriority($rid, (string) $request->getParameter('priority'));
                    break;
            }
            $this->redirect(['module' => 'requestToPublish', 'action' => 'inbox']);

            return;
        }

        $this->filters = [
            'triage_status' => $request->getParameter('triage_status'),
            'priority' => $request->getParameter('priority'),
        ];
        $this->items = $svc->inbox($this->filters);
        $this->counts = $svc->counts();
        $this->svc = $svc;
    }
}
