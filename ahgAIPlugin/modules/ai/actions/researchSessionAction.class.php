<?php

use AtomFramework\Http\Controllers\AhgController;

require_once dirname(__FILE__).'/../../../lib/Services/ResearchCopilotService.php';

/**
 * /ai/research/session/:id
 *   GET                 — load the session's messages (JSON).
 *   GET  ?op=export     — download the session as a Markdown transcript.
 *   POST ?op=delete     — delete the session.
 *   POST ?op=rename     — rename the session (title=...).
 */
class aiResearchSessionAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'not_authenticated']));
        }
        $userId = (int) $this->getUser()->getAttribute('user_id');
        $id = (int) $request->getParameter('id');
        $op = (string) $request->getParameter('op', '');
        $svc = new ResearchCopilotService();

        if ($request->isMethod('post')) {
            if ($op === 'delete') {
                return $this->renderText(json_encode(['success' => $svc->deleteSession($id, $userId)]));
            }
            if ($op === 'rename') {
                $ok = $svc->rename($id, $userId, (string) $request->getParameter('title', ''));
                return $this->renderText(json_encode(['success' => $ok]));
            }
            return $this->renderText(json_encode(['error' => 'unknown_op']));
        }

        if ($op === 'export') {
            $md = $svc->exportMarkdown($id, $userId);
            if ($md === null) {
                return $this->renderText(json_encode(['error' => 'not_found']));
            }
            $resp = $this->getResponse();
            $resp->setContentType('text/markdown; charset=utf-8');
            $resp->setHttpHeader('Content-Disposition', 'attachment; filename="research-session-'.$id.'.md"');
            return $this->renderText($md);
        }

        return $this->renderText(json_encode([
            'success' => true,
            'messages' => $svc->getMessages($id, $userId),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
