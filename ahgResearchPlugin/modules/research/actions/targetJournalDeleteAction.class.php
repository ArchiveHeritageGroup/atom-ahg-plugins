<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Target-journal directory - delete a journal (#114 / Heratio #1107).
 *
 * @package ahgResearchPlugin
 */
class researchTargetJournalDeleteAction extends AhgController
{
    public function execute($request)
    {
        // Admin-only: the target-journal directory is a shared, centrally-curated
        // list (no per-user ownership column), so deletes must be admin-gated.
        // Was previously gated only by isAuthenticated(), letting any logged-in
        // user delete shared directory entries (security audit 2026-06-15).
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->isAdministrator()) {
            $this->getUser()->setFlash('error', 'Administrator access required');
            $this->redirect('research/targetJournals');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/TargetJournalService.php';
        $service = new TargetJournalService();

        $id = (int) $request->getParameter('id');
        if ($id) {
            $service->delete($id);
            $this->getUser()->setFlash('success', 'Journal removed from the directory.');
        }

        $this->redirect('research/targetJournals');
    }
}
