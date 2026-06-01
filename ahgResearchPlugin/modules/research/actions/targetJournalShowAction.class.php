<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Target-journal directory - show one journal (#114 / Heratio #1107).
 *
 * @package ahgResearchPlugin
 */
class researchTargetJournalShowAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/TargetJournalService.php';
        $service = new TargetJournalService();

        $id = (int) $request->getParameter('id');
        $this->journal = $service->get($id);
        if (!$this->journal) {
            $this->forward404('Journal not found');
        }

        $this->sidebarActive = 'targetJournals';
        $this->unreadNotifications = $this->unreadNotifications ?? 0;
    }
}
