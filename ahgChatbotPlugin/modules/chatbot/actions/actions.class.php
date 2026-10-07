<?php

require_once __DIR__.'/../../../lib/Services/ChatbotVectorIndex.php';
require_once __DIR__.'/../../../lib/Services/ChatbotRetriever.php';
require_once __DIR__.'/../../../lib/Services/ChatbotSiteInfo.php';
require_once __DIR__.'/../../../lib/Services/ChatbotHelp.php';
require_once __DIR__.'/../../../lib/Services/ChatbotService.php';

use AhgChatbotPlugin\Services\ChatbotService;
use AtomExtensions\Services\AhgSettingsService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Ask the Archive endpoints.
 *
 *   POST /chatbot/ask       {message, history[], session_id, page_slug, mode} -> JSON answer
 *                           mode: collection (default) or help (how to use the site)
 *   POST /chatbot/feedback  {session_id, message_id, rating}            -> JSON
 *   GET  /chatbot/admin     administrators: usage, unanswered, feedback
 *
 * CSRF: the ahgCorePlugin fetch wrapper adds X-CSRF-TOKEN to same-origin POSTs.
 */
class chatbotActions extends sfActions
{
    public function executeAsk(sfWebRequest $request)
    {
        if (!$request->isMethod('post')) {
            return $this->json(['error' => 'method_not_allowed'], 405);
        }
        if (!AhgSettingsService::getBool('chatbot_enabled', true)) {
            return $this->json(['error' => 'disabled'], 404);
        }
        if (!AhgSettingsService::getBool('chatbot_public', true) && !$this->getUser()->isAuthenticated()) {
            return $this->json(['error' => 'not_authenticated'], 403);
        }

        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $message = trim((string) ($payload['message'] ?? ''));
        $history = is_array($payload['history'] ?? null) ? $payload['history'] : [];
        $sessionId = (string) ($payload['session_id'] ?? '');
        if (!preg_match('/^chat_[a-f0-9]{24}$/', $sessionId)) {
            $sessionId = ChatbotService::newSessionId();
        }
        $pageSlug = preg_match('/^[a-z0-9-]{1,255}$/', (string) ($payload['page_slug'] ?? '')) ? $payload['page_slug'] : null;

        // Refused before anything is stored or any model is called.
        $refusal = ChatbotService::throttle((string) $request->getRemoteAddress(), $message);
        if (null !== $refusal) {
            return $this->json(['answer' => $refusal, 'sources' => [], 'mode' => 'limited', 'session_id' => $sessionId], 429);
        }

        ChatbotService::log($sessionId, 'user', $message);

        // The user id drives the visibility filter: what this person may see.
        $userId = $this->getUser()->getAttribute('user_id');
        $mode = 'help' === ($payload['mode'] ?? '') ? 'help' : 'collection';
        // Staff help (cataloguing, admin) is for people who do that work, not
        // for every signed-in account - researchers sign in too.
        $staff = $this->getUser()->hasCredential(['administrator', 'editor', 'contributor'], false);
        $result = ChatbotService::answer($message, $history, $this->getUser()->getCulture(), $userId ? (int) $userId : null, $pageSlug, $mode, $request->getRelativeUrlRoot(), $staff);

        $result['message_id'] = ChatbotService::log($sessionId, 'assistant', $result['answer'], $result);
        $result['session_id'] = $sessionId;
        $result['sources'] = array_map(fn ($s) => [
            'url' => $s['url'] ?? $this->getController()->genUrl(['module' => $s['module'] ?? 'informationobject', 'slug' => $s['slug']]),
        ] + array_diff_key($s, ['module' => 1]), $result['sources']);
        unset($result['model']);

        return $this->json($result);
    }

    public function executeFeedback(sfWebRequest $request)
    {
        if (!$request->isMethod('post')) {
            return $this->json(['error' => 'method_not_allowed'], 405);
        }
        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $ok = ChatbotService::rate((string) ($payload['session_id'] ?? ''), (int) ($payload['message_id'] ?? 0), (int) ($payload['rating'] ?? 0));

        return $this->json(['ok' => $ok]);
    }

    public function executeAdmin(sfWebRequest $request)
    {
        $since = date('Y-m-d 00:00:00', strtotime('-29 days'));
        $q = static fn () => DB::table('ahg_chatbot_message')->where('created_at', '>=', $since);

        try {
            $this->perDay = $q()->where('role', 'user')
                ->selectRaw('DATE(created_at) AS day, COUNT(*) AS questions')
                ->groupBy('day')->orderBy('day', 'desc')->get()->all();
            $this->totals = [
                'questions' => $q()->where('role', 'user')->count(),
                'unanswered' => $q()->where('role', 'assistant')->where('answered', 0)->count(),
                'fallback' => $q()->where('role', 'assistant')->where('mode', 'fallback')->count(),
                'up' => $q()->where('rating', 1)->count(),
                'down' => $q()->where('rating', -1)->count(),
            ];
            // The question behind each unanswered or down-rated answer: what
            // visitors look for and the catalogue does not (yet) describe.
            $this->review = DB::table('ahg_chatbot_message as a')
                ->join('ahg_chatbot_message as u', static function ($j) {
                    $j->on('u.session_id', '=', 'a.session_id')->where('u.role', 'user')
                        ->whereRaw('u.id = (SELECT MAX(id) FROM ahg_chatbot_message WHERE session_id = a.session_id AND role = \'user\' AND id < a.id)');
                })
                ->where('a.role', 'assistant')->where('a.created_at', '>=', $since)
                ->where(static fn ($w) => $w->where('a.answered', 0)->orWhere('a.rating', -1))
                ->orderBy('a.id', 'desc')->limit(100)
                ->get(['u.content as question', 'a.content as answer', 'a.rating', 'a.answered', 'a.created_at'])->all();
            $this->error = null;
        } catch (\Throwable $e) {
            $this->perDay = $this->review = [];
            $this->totals = [];
            $this->error = 'The chatbot log table is missing - load ahgChatbotPlugin/database/install.sql.';
        }

        $this->settings = [];
        foreach (['chatbot_enabled', 'chatbot_public', 'chatbot_daily_cap', 'chatbot_retention_days', 'chatbot_model', 'chatbot_button_label', 'chatbot_info_pages', 'chatbot_help_categories_public', 'chatbot_help_categories_staff'] as $key) {
            $this->settings[$key] = AhgSettingsService::get($key, '');
        }
    }

    private function json(array $data, int $status = 200)
    {
        $this->getResponse()->setStatusCode($status);
        $this->getResponse()->setContentType('application/json; charset=utf-8');

        return $this->renderText(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
