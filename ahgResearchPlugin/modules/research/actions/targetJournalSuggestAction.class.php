<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Target-journal directory - best-fit suggestions for a manuscript (JSON).
 *
 * Drives the auto-assemble flow: given manuscript subject/abstract text, score
 * directory journals by scope-term overlap and return the top matches so the
 * builder can pre-format the manuscript to the chosen journal's rules
 * (#114 / Heratio #1107).
 *
 * @package ahgResearchPlugin
 */
class researchTargetJournalSuggestAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->getResponse()->setStatusCode(401);

            return sfView::NONE;
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/TargetJournalService.php';
        $service = new TargetJournalService();

        $text = (string) ($request->getParameter('text') ?: $request->getParameter('abstract') ?: '');
        $limit = (int) ($request->getParameter('limit') ?: 5);
        $limit = $limit > 0 && $limit <= 25 ? $limit : 5;

        $suggestions = $text !== '' ? $service->suggestForScope($text, $limit) : [];

        $this->getResponse()->setContentType('application/json');
        $this->getResponse()->setContent(json_encode([
            'count'       => count($suggestions),
            'suggestions' => $suggestions,
        ]));

        return sfView::NONE;
    }
}
