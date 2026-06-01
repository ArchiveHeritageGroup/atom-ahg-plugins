<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Target-journal directory - seed/refresh the DHET-accredited starter set
 * (South-African accreditation module) (#114 / Heratio #1107).
 *
 * Idempotent upsert by title. Mirrors Heratio ::seedDhet().
 *
 * @package ahgResearchPlugin
 */
class researchTargetJournalSeedDhetAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/TargetJournalService.php';
        $service = new TargetJournalService();

        $n = $service->seedDhetStarter();
        $this->getUser()->setFlash(
            'success',
            sprintf('%d DHET-accredited journals seeded/updated (South-African accreditation module).', $n)
        );

        $this->redirect('research/targetJournals');
    }
}
