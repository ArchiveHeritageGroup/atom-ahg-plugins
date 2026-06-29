<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Researcher Self-Description Portal — "My submissions" list.
 *
 * @package ahgResearchPlugin
 */
class researchSubmissionsAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/SubmissionService.php';
        $service = new SubmissionService();

        $userId = (int) $this->getUser()->getAttribute('user_id');
        $this->submissions = $service->listForUser($userId);
        $this->types = SubmissionService::getTypes();
        $this->isArchivist = SubmissionService::isArchivist($userId);

        $this->sidebarActive = 'submissions';
        $this->unreadNotifications = $this->unreadNotifications ?? 0;
    }
}
