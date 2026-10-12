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
        // The directory is shared and centrally curated: changing it is an administrator's
        // job, as targetJournalDelete already required. Researchers browse and get suggestions.
        if (!$this->getUser()->isAdministrator()) {
            $this->getUser()->setFlash('error', 'Administrator access required');
            $this->redirect('research/targetJournals');
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
