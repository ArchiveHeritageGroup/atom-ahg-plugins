<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Target-journal directory - index/listing (#114 / Heratio #1107).
 *
 * "Where to Publish" - the directory of journals to publish TO. The core is
 * jurisdiction-neutral; the DHET list is the South-African accreditation module.
 *
 * @package ahgResearchPlugin
 */
class researchTargetJournalsAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/TargetJournalService.php';
        $service = new TargetJournalService();

        $this->q = (string) ($request->getParameter('q') ?: '');
        $this->market = (string) ($request->getParameter('market') ?: '');

        $this->journals = $service->list([
            'q'      => $this->q ?: null,
            'market' => $this->market ?: null,
        ]);

        $this->sidebarActive = 'targetJournals';
        $this->unreadNotifications = $this->unreadNotifications ?? 0;
    }
}
