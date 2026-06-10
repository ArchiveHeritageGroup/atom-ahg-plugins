<?php

use AtomFramework\Http\Controllers\AhgController;

require_once dirname(__FILE__).'/../../../lib/Services/CollectionChatbotService.php';

/**
 * GET /ai/research — Researcher Copilot workspace (sessions sidebar + chat).
 */
class aiResearchAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
            return sfView::NONE;
        }
        $this->aiAvailable = \CollectionChatbotService::isAvailable();
        return sfView::SUCCESS;
    }
}
