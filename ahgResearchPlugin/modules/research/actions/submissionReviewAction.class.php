<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Archivist review of a single researcher submission.
 *
 * GET  ?id=N              → view the ISAD(G) description + review history
 * POST do=start|return|approve|publish → workflow decision
 *
 * @package ahgResearchPlugin
 */
class researchSubmissionReviewAction extends AhgController
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
        $id = (int) $request->getParameter('id');
        $sub = $service->get($id);
        if (!$sub) {
            $this->forward404('Submission not found');
        }

        if ($request->isMethod('post')) {
            $do = (string) $request->getParameter('do');
            $comment = trim((string) $request->getParameter('comment'));
            try {
                switch ($do) {
                    case 'start':
                        $service->startReview($id, $userId);
                        $this->getUser()->setFlash('notice', 'Marked as under archival review.');
                        break;
                    case 'return':
                        if ('' === $comment) {
                            $this->getUser()->setFlash('error', 'A comment is required when returning for revision.');
                            break;
                        }
                        $service->returnForRevision($id, $userId, $comment);
                        $this->getUser()->setFlash('notice', 'Returned to the researcher for revision.');
                        break;
                    case 'approve':
                        $service->approve($id, $userId, $comment);
                        $this->getUser()->setFlash('notice', 'Submission approved. You can now publish it.');
                        break;
                    case 'publish':
                        $objectId = $service->publish($id, $userId);
                        $this->getUser()->setFlash('notice', 'Published as draft AtoM description #' . $objectId . ' for final check.');
                        break;
                }
            } catch (\Throwable $e) {
                $this->getUser()->setFlash('error', 'Action failed: ' . $e->getMessage());
            }
            $this->redirect('research/submissionReview?id=' . $id);
        }

        $this->submission = $sub;
        $this->item = $sub['item'];
        $this->reviews = $sub['reviews'];
        $this->sidebarActive = 'submissionReviewQueue';
        $this->unreadNotifications = $this->unreadNotifications ?? 0;
    }
}
