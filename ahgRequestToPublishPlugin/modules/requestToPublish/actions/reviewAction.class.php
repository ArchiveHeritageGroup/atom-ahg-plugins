<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Peer-review workspace for a single publication request (admin/curator).
 *
 * Route: /requesttopublish/review/:id
 */
class requestToPublishReviewAction extends AhgController
{
    public function execute($request)
    {
        $user = $this->getUser();
        if (!$user->isAuthenticated()
            || !$user->hasGroup(\AtomExtensions\Constants\AclConstants::ADMINISTRATOR_ID)
        ) {
            \AtomExtensions\Services\AclService::forwardUnauthorized();
        }

        $pluginDir = $this->config('sf_plugins_dir') . '/ahgRequestToPublishPlugin/lib/Services';
        require_once $pluginDir . '/WorkflowService.php';
        require_once $pluginDir . '/RequestToPublishService.php';
        $svc = new \ahgRequestToPublishPlugin\Services\WorkflowService();

        $rid = (int) $request->getParameter('id');
        if (!$rid) {
            $this->forward404();
        }

        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action');
            if ('add_review' === $action) {
                $svc->addReview($rid, [
                    'reviewer_id' => (int) $user->getAttribute('user_id'),
                    'reviewer_name' => (string) $request->getParameter('reviewer_name'),
                    'verdict' => (string) $request->getParameter('verdict'),
                    'comments' => (string) $request->getParameter('comments'),
                ]);
            } elseif ('notes' === $action) {
                $svc->setNotes($rid, (string) $request->getParameter('internal_notes'));
            }
            $this->redirect(['module' => 'requestToPublish', 'action' => 'review', 'id' => $rid]);

            return;
        }

        $rtp = new \ahgRequestToPublishPlugin\Services\RequestToPublishService();
        $this->requestData = $rtp->getRequestWithObject($rid);
        $this->workflow = $svc->getByRequest($rid);
        $this->reviews = $svc->getReviews($rid);
        $this->requestId = $rid;
        $this->svc = $svc;
    }
}
