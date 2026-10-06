<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Collection chatbot — RAG-grounded natural-language Q&A over the archival
 * catalogue (#121).
 *
 * Retrieve: MySQL FULLTEXT over published information_object titles + scope.
 * Augment: build a context block from the top matches.
 * Generate: ahgAIPlugin's \LlmService (same provider the help chatbot uses).
 *
 * Only PUBLISHED descriptions (status type 158 / status 160) are retrieved, so
 * the assistant never surfaces drafts.
 */
class CollectionChatbotService
{
    private const PUBLICATION_STATUS_TYPE_ID = 158;
    private const PUBLICATION_STATUS_PUBLISHED_ID = 160;
    private const MAX_RECORDS = 6;
    // Fetched from each retrieval path before the visibility filter, so that
    // dropping restricted hits still leaves enough to answer from.
    private const CANDIDATES = 18;
    private const MAX_CONTEXT_CHARS = 5000;

    /**
     * @param array<int,array{role:string,content:string}> $history
     * @return array{answer:string,sources:array,mode:string,error?:string,tokens_used?:int}
     */
    public static function chat(string $message, array $history = [], string $culture = 'en', ?int $userId = null): array
    {
        $message = trim($message);
        if ('' === $message) {
            return ['answer' => 'Please ask a question about the collection.', 'sources' => [], 'mode' => 'empty'];
        }

        $records = self::retrieve($message, $culture, $userId);
        $context = self::buildContext($records);

        $systemPrompt = "You are a research assistant for an archival catalogue. "
            . "Answer the user's question using ONLY the catalogue records provided below. "
            . "Cite the records you used by their title. If the records do not contain the answer, "
            . "say so plainly and suggest how the user might refine their search. Be concise and use "
            . "markdown.\n\nCatalogue records:\n" . $context;

        $userPrompt = '';
        foreach (array_slice($history, -4) as $msg) {
            $role = ('user' === ($msg['role'] ?? '')) ? 'User' : 'Assistant';
            $userPrompt .= $role . ': ' . ($msg['content'] ?? '') . "\n\n";
        }
        $userPrompt .= 'User: ' . $message;

        $sources = array_map(static fn ($r) => ['slug' => $r->slug, 'title' => $r->title ?: $r->slug], $records);

        try {
            $result = self::provider()->complete($systemPrompt, $userPrompt, ['max_tokens' => 800, 'temperature' => 0.2]);

            if (!empty($result['success']) && !empty($result['text'])) {
                return [
                    'answer' => $result['text'],
                    'sources' => $sources,
                    'mode' => 'ai',
                    'tokens_used' => $result['tokens_used'] ?? 0,
                ];
            }

            return self::fallback($records, $sources, $result['error'] ?? 'The assistant could not generate a response.');
        } catch (\Throwable $e) {
            return self::fallback($records, $sources, 'AI service unavailable: ' . $e->getMessage());
        }
    }

    /** True when the LLM provider is configured + reachable. */
    public static function isAvailable(): bool
    {
        try {
            return self::provider()->isAvailable();
        } catch (\Throwable $e) {
            \class_exists('AhgCore\\Core\\AhgLog') && \AhgCore\Core\AhgLog::swallowed($e, basename(__FILE__).':'.__LINE__);
            return false;
        }
    }

    /**
     * @return array<int,object> top published descriptions for the query.
     * Hybrid: gateway-fed semantic search (when configured) merged with MySQL
     * FULLTEXT, deduped by object id. Falls back to pure FULLTEXT whenever the
     * semantic index is unavailable, so behaviour is unchanged before the
     * gateway key + index exist.
     */
    public static function retrieve(string $message, string $culture = 'en', ?int $userId = null): array
    {
        $fulltext = self::retrieveFulltext($message, $culture);
        $semantic = self::retrieveSemantic($message, $culture);

        // Merge, semantic-first, dedupe by id.
        $merged = [];
        foreach (array_merge($semantic, $fulltext) as $row) {
            $id = (int) ($row->id ?? 0);
            if ($id > 0 && !isset($merged[$id])) {
                $merged[$id] = $row;
            }
        }

        // Visibility is decided here, on every hit, at the moment it is used -
        // not trusted from either index. The vector index can hold records
        // unpublished or restricted since it was built, and neither path
        // checked embargo or security classification.
        return array_slice(self::visibleOnly(array_values($merged), $userId), 0, self::MAX_RECORDS);
    }

    /**
     * Keep only what this user may see: published, and not restricted by
     * security classification, donor agreement or a full embargo
     * (SearchAccessFilterService). FAILS CLOSED: if visibility cannot be
     * established, nothing is returned - the alternative is a public
     * assistant quoting a record it should never have seen.
     *
     * @param array<int,object> $rows
     *
     * @return array<int,object>
     */
    public static function visibleOnly(array $rows, ?int $userId = null): array
    {
        if ($rows === []) {
            return [];
        }

        try {
            $ids = array_map(static fn ($r) => (int) $r->id, $rows);

            $published = array_flip(array_map('intval', DB::table('status')
                ->whereIn('object_id', $ids)
                ->where('type_id', self::PUBLICATION_STATUS_TYPE_ID)
                ->where('status_id', self::PUBLICATION_STATUS_PUBLISHED_ID)
                ->pluck('object_id')
                ->all()));

            $restricted = array_flip(array_map('intval',
                \AtomExtensions\Services\Search\SearchAccessFilterService::getInstance()->getRestrictedObjectIds($userId)
            ));

            return array_values(array_filter($rows, static function ($r) use ($published, $restricted) {
                $id = (int) $r->id;

                return isset($published[$id]) && !isset($restricted[$id]);
            }));
        } catch (\Throwable $e) {
            error_log('chatbot.visibility_check_failed: ' . $e->getMessage());

            return [];
        }
    }

    /** Longest question accepted; longer ones are refused, not truncated. */
    public const MAX_MESSAGE_CHARS = 1000;

    /**
     * Refuse a question before it costs a model call. Returns the reason to
     * show the user, or null to proceed.
     *
     * A chat answer costs far more than a page view, and PSIS was flooded at
     * 37 requests a second in August (#264). Three limits, cheapest first:
     *   - per IP: 10 a minute and 100 a day (APCu, shared by the fpm pool);
     *   - per installation: ai_chatbot_daily_cap questions a day (default
     *     1000), counted from the stored turns, so it holds across restarts.
     *
     * ponytail: without APCu the per-IP limits are skipped and only the daily
     * cap applies; nginx limit_req in front of /ai/assistantAsk is the
     * upgrade for that case and for anything beyond one host.
     */
    public static function throttle(string $ip, string $message): ?string
    {
        if (mb_strlen($message) > self::MAX_MESSAGE_CHARS) {
            return sprintf('Please keep your question under %d characters.', self::MAX_MESSAGE_CHARS);
        }

        if ('' !== $ip && \function_exists('apcu_add')) {
            foreach (['m' => [60, 10], 'd' => [86400, 100]] as $window => [$ttl, $max]) {
                $key = 'ahg_chat_'.$window.'_'.$ip;
                apcu_add($key, 0, $ttl);
                if (apcu_inc($key) > $max) {
                    return 'm' === $window
                        ? 'You are asking faster than the assistant can answer. Please wait a minute and try again.'
                        : 'You have reached today\'s limit for questions. Please come back tomorrow.';
                }
            }
        }

        try {
            $cap = (int) (DB::table('ahg_settings')->where('setting_key', 'ai_chatbot_daily_cap')->value('setting_value') ?: 1000);
            $today = DB::table('ahg_ai_chatbot_message')
                ->where('role', 'user')
                ->where('created_at', '>=', date('Y-m-d 00:00:00'))
                ->count();
            if ($today >= $cap) {
                return 'The assistant is very busy today. Please try again tomorrow, or search the catalogue directly.';
            }
        } catch (\Throwable $e) {
            // No table or setting yet: the per-IP limits above still apply.
            \class_exists('AhgCore\\Core\\AhgLog') && \AhgCore\Core\AhgLog::swallowed($e, basename(__FILE__).':'.__LINE__);
        }

        return null;
    }

    /**
     * The gateway, and only the gateway (catalog #242). LlmService can be set
     * to Anthropic, OpenAI or a direct Ollama port, each of which bypasses the
     * keyed, metered and audited AHG AI gateway - which a public assistant
     * must never do. The model comes from the gateway's own settings.
     */
    private static function provider(): \LlmProviderInterface
    {
        $dir = \sfConfig::get('sf_plugins_dir') . '/ahgAIPlugin/lib/Services';
        require_once $dir . '/LlmProviderInterface.php';
        require_once $dir . '/providers/GatewayProvider.php';

        return new \GatewayProvider(['max_tokens' => 800, 'temperature' => 0.2]);
    }

    /**
     * Semantic hits hydrated into the same row shape FULLTEXT returns.
     *
     * @return array<int,object>
     */
    private static function retrieveSemantic(string $message, string $culture): array
    {
        try {
            $svcFile = \sfConfig::get('sf_plugins_dir') . '/ahgAIPlugin/lib/Services/CatalogueVectorService.php';
            if (!is_file($svcFile)) {
                return [];
            }
            require_once $svcFile;

            $hits = (new \CatalogueVectorService())->search($message, self::CANDIDATES);
            if (empty($hits)) {
                return [];
            }

            $ids = array_values(array_filter(array_map(static fn ($h) => (int) $h['object_id'], $hits)));
            if (empty($ids)) {
                return [];
            }

            $rows = DB::table('information_object_i18n as ioi')
                ->join('information_object as io', 'io.id', '=', 'ioi.id')
                ->join('slug as s', 's.object_id', '=', 'io.id')
                ->where('ioi.culture', $culture)
                ->whereIn('io.id', $ids)
                ->get(['io.id', 'io.identifier', 'ioi.title', 'ioi.scope_and_content', 's.slug'])
                ->keyBy('id');

            // preserve semantic score order
            $ordered = [];
            foreach ($ids as $id) {
                if (isset($rows[$id])) {
                    $ordered[] = $rows[$id];
                }
            }

            return $ordered;
        } catch (\Throwable $e) {
            error_log('chatbot.retrieve_semantic_failed: ' . $e->getMessage());

            return [];
        }
    }

    /** @return array<int,object> FULLTEXT matches (the original retrieval). */
    private static function retrieveFulltext(string $message, string $culture = 'en'): array
    {
        try {
            return DB::table('information_object_i18n as ioi')
                ->join('information_object as io', 'io.id', '=', 'ioi.id')
                ->join('slug as s', 's.object_id', '=', 'io.id')
                ->join('status as st', static function ($j) {
                    $j->on('st.object_id', '=', 'io.id')
                        ->where('st.type_id', self::PUBLICATION_STATUS_TYPE_ID)
                        ->where('st.status_id', self::PUBLICATION_STATUS_PUBLISHED_ID);
                })
                ->where('ioi.culture', $culture)
                ->whereRaw(
                    '(MATCH(ioi.title) AGAINST(? IN NATURAL LANGUAGE MODE) '
                    . 'OR MATCH(ioi.scope_and_content) AGAINST(? IN NATURAL LANGUAGE MODE))',
                    [$message, $message]
                )
                ->orderByRaw(
                    '(MATCH(ioi.title) AGAINST(?) * 2 + MATCH(ioi.scope_and_content) AGAINST(?)) DESC',
                    [$message, $message]
                )
                ->limit(self::CANDIDATES)
                ->get(['io.id', 'io.identifier', 'ioi.title', 'ioi.scope_and_content', 's.slug'])
                ->all();
        } catch (\Throwable $e) {
            error_log('chatbot.retrieve_failed: ' . $e->getMessage());

            return [];
        }
    }

    /** @param array<int,object> $records */
    private static function buildContext(array $records): string
    {
        if (empty($records)) {
            return 'No matching catalogue records were found.';
        }
        $context = '';
        $chars = 0;
        foreach ($records as $r) {
            $ref = $r->identifier ? '[' . $r->identifier . '] ' : '';
            $scope = trim((string) $r->scope_and_content);
            $scope = '' !== $scope ? mb_substr(strip_tags($scope), 0, 1200) : '(no scope and content recorded)';
            $block = '--- ' . $ref . ($r->title ?: $r->slug) . " ---\n" . $scope . "\n\n";
            if ($chars + strlen($block) > self::MAX_CONTEXT_CHARS) {
                break;
            }
            $context .= $block;
            $chars += strlen($block);
        }

        return $context;
    }

    /** When the LLM is unavailable, still return the retrieved records. */
    private static function fallback(array $records, array $sources, string $error): array
    {
        $answer = empty($records)
            ? "I couldn't reach the AI service and found no matching records for that query."
            : "I couldn't reach the AI service, but here are the catalogue records most relevant to your question.";

        return ['answer' => $answer, 'sources' => $sources, 'mode' => 'fallback', 'error' => $error];
    }

    // ─── Conversation persistence (build-order #4) ──────────────────────────

    /** A fresh chatbot session id (caller threads it across turns). */
    public static function newSessionId(): string
    {
        try {
            return 'chat_' . bin2hex(random_bytes(12));
        } catch (\Throwable $e) {
            return 'chat_' . substr(md5(uniqid('', true)), 0, 24);
        }
    }

    /**
     * Persist one conversation turn to ahg_ai_chatbot_message. Best-effort:
     * a missing table never breaks the chat endpoint.
     *
     * @param array{sources?:array,grounding_score?:float,model?:string,tokens_in?:int,tokens_out?:int} $meta
     */
    public static function persistTurn(string $sessionId, string $role, string $content, array $meta = []): void
    {
        $sessionId = trim($sessionId);
        $content = trim($content);
        if ('' === $sessionId || '' === $content) {
            return;
        }

        try {
            DB::table('ahg_ai_chatbot_message')->insert([
                'session_id' => mb_substr($sessionId, 0, 64),
                'role' => in_array($role, ['user', 'assistant', 'system'], true) ? $role : 'user',
                'content' => $content,
                'sources' => isset($meta['sources']) ? json_encode($meta['sources']) : null,
                'grounding_score' => $meta['grounding_score'] ?? null,
                'model' => isset($meta['model']) ? mb_substr((string) $meta['model'], 0, 100) : null,
                'tokens_in' => $meta['tokens_in'] ?? null,
                'tokens_out' => $meta['tokens_out'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            \class_exists('AhgCore\\Core\\AhgLog') && \AhgCore\Core\AhgLog::swallowed($e, basename(__FILE__).':'.__LINE__);
            // persistence is non-essential; never surface to the user.
        }
    }

    /** Read back a session's turns oldest-first. @return array<int,object> */
    public static function history(string $sessionId, int $limit = 100): array
    {
        try {
            return DB::table('ahg_ai_chatbot_message')
                ->where('session_id', $sessionId)
                ->orderBy('created_at')->orderBy('id')
                ->limit(max(1, $limit))
                ->get(['role', 'content', 'sources', 'model', 'created_at'])
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
