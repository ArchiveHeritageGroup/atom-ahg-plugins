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
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
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
