<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Archivist reviewer dashboard — queue of researcher submissions awaiting action.
 *
 * @package ahgResearchPlugin
 */
class researchSubmissionReviewQueueAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/SubmissionService.php';
        $userId = (int) $this->getUser()->getAttribute('user_id');

        if (!SubmissionService::isArchivist($userId)) {
            $this->getUser()->setFlash('error', 'Reviewer access is restricted to archival staff.');
            $this->redirect('research/submissions');
        }

        $service = new SubmissionService();
        $this->queue = $service->listForReview();
        $this->sidebarActive = 'submissionReviewQueue';
        $this->unreadNotifications = $this->unreadNotifications ?? 0;
    }
}
