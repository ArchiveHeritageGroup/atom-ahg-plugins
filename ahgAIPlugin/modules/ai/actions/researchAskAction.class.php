<?php

use AtomFramework\Http\Controllers\AhgController;

require_once dirname(__FILE__).'/../../../lib/Services/ResearchCopilotService.php';

/**
 * POST /ai/research/ask {session_id?, message} — ask within a session.
 * Creates a session on the fly when session_id is absent. Returns JSON.
 */
class aiResearchAskAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'not_authenticated']));
        }
        $userId = (int) $this->getUser()->getAttribute('user_id');
        $culture = $this->getUser()->getCulture() ?: 'en';

        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->getPostParameters() ?: [];
        }
        $message = trim((string) ($payload['message'] ?? ''));
        $sessionId = (int) ($payload['session_id'] ?? 0);

        if ($message === '') {
            return $this->renderText(json_encode(['error' => 'empty_message']));
        }

        $svc = new ResearchCopilotService();
        if ($sessionId <= 0 || !$svc->getSession($sessionId, $userId)) {
            $sessionId = $svc->createSession($userId, $culture);
        }

        $result = $svc->ask($sessionId, $userId, $message, $culture);
        return $this->renderText(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
