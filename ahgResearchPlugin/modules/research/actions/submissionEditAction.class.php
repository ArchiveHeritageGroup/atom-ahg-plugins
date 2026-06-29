<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Researcher Self-Description Portal — create / edit a submission.
 *
 * GET  ?new=1            → choose a submission type
 * GET  ?id=N             → ISAD(G) form for an editable (draft/returned) submission
 * POST do=create         → create a draft of the chosen type
 * POST do=save|submit    → persist ISAD(G) fields, optionally submit for review
 *
 * @package ahgResearchPlugin
 */
class researchSubmissionEditAction extends AhgController
{
    private const ISAD_FIELDS = [
        'title', 'identifier', 'level_of_description', 'scope_and_content',
        'extent_and_medium', 'date_display', 'creators', 'subjects', 'places',
        'access_conditions', 'reproduction_conditions', 'notes',
    ];

    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/SubmissionService.php';
        $service = new SubmissionService();
        $userId = (int) $this->getUser()->getAttribute('user_id');

        $this->types = SubmissionService::getTypes();
        $this->sidebarActive = 'submissions';
        $this->unreadNotifications = $this->unreadNotifications ?? 0;

        $do = (string) $request->getParameter('do');
        $id = (int) $request->getParameter('id');

        if ($request->isMethod('post')) {
            if ('create' === $do) {
                $newId = $service->createDraft(
                    $userId,
                    $this->researcherId($userId),
                    (string) $request->getParameter('type'),
                    trim((string) $request->getParameter('title'))
                );
                $this->redirect('research/submissionEdit?id=' . $newId);
            }

            $sub = $service->get($id);
            if (!$sub || (int) $sub['user_id'] !== $userId) {
                $this->forward404('Submission not found');
            }
            if (!in_array($sub['status'], ['draft', 'returned'], true)) {
                $this->getUser()->setFlash('error', 'This submission can no longer be edited.');
                $this->redirect('research/submissions');
            }

            $data = [];
            foreach (self::ISAD_FIELDS as $f) {
                $data[$f] = trim((string) $request->getParameter($f));
            }
            $service->saveItem($id, $data);

            if ('submit' === $do) {
                $service->submit($id, $userId);
                $this->getUser()->setFlash('notice', 'Submission sent for archival review.');
                $this->redirect('research/submissions');
            }

            $this->getUser()->setFlash('notice', 'Draft saved.');
            $this->redirect('research/submissionEdit?id=' . $id);
        }

        // GET
        if ($id) {
            $sub = $service->get($id);
            if (!$sub || (int) $sub['user_id'] !== $userId) {
                $this->forward404('Submission not found');
            }
            $this->submission = $sub;
            $this->item = $sub['item'];
            $this->editable = in_array($sub['status'], ['draft', 'returned'], true);
        } else {
            // New submission: show the type picker.
            $this->submission = null;
            $this->item = null;
            $this->editable = true;
        }
    }

    private function researcherId(int $userId): ?int
    {
        $rid = DB::table('research_researcher')->where('user_id', $userId)->value('id');

        return $rid ? (int) $rid : null;
    }
}
