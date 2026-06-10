<?php

use AtomFramework\Http\Controllers\AhgController;

require_once dirname(__FILE__).'/../../../lib/Services/ResearchCopilotService.php';

/**
 * GET  /ai/research/sessions — list the user's sessions (JSON).
 * POST /ai/research/sessions — create a new empty session, returns its id.
 */
class aiResearchSessionsAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'not_authenticated']));
        }
        $userId = (int) $this->getUser()->getAttribute('user_id');
        $svc = new ResearchCopilotService();

        if ($request->isMethod('post')) {
            $id = $svc->createSession($userId, $this->getUser()->getCulture() ?: 'en');
            return $this->renderText(json_encode(['success' => true, 'session_id' => $id]));
        }

        return $this->renderText(json_encode([
            'success' => true,
            'sessions' => $svc->listSessions($userId),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
