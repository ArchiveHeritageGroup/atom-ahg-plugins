<?php

namespace AhgChatbotPlugin\Services;

use AtomExtensions\Services\AhgSettingsService;
use AtomFramework\Services\AI\AiGatewayClient;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Ask the Archive: answers from retrieved catalogue records, through the AHG
 * AI gateway and nothing else (no provider setting can route it elsewhere).
 */
class ChatbotService
{
    /** Longest question accepted; longer ones are refused, not truncated. */
    public const MAX_MESSAGE_CHARS = 1000;

    private const MAX_CONTEXT_CHARS = 5000;
    private const HISTORY_TURNS = 4;

    /**
     * @param array<int,array{role:string,content:string}> $history
     *
     * @return array{answer:string,sources:array,mode:string,model?:string,error?:string}
     */
    public static function answer(string $question, array $history, string $culture, ?int $userId, ?string $pageSlug = null): array
    {
        $question = trim($question);
        if ('' === $question) {
            return ['answer' => 'Please ask a question about the collection.', 'sources' => [], 'mode' => 'empty'];
        }

        $records = ChatbotRetriever::retrieve($question, $culture, $userId, $pageSlug);
        $sources = array_map(static fn ($r) => [
            'slug' => $r->slug,
            'title' => $r->title ?: $r->slug,
            'identifier' => $r->identifier,
            'thumbnail' => $r->thumbnail,
        ], $records);

        // Visiting, opening times, contacts, services: answered from the
        // institution's own public information, not from the records.
        $info = ['text' => '', 'sources' => []];
        if (ChatbotSiteInfo::isAbout($question)) {
            $pageId = null !== $pageSlug ? (int) DB::table('slug')->where('slug', $pageSlug)->value('object_id') : 0;
            $info = ChatbotSiteInfo::forQuestion($culture, $pageId ?: null);
            if ('' !== $info['text']) {
                // Records matched on words like "open" or "copy" are noise
                // here; keep only the record being viewed.
                $records = array_values(array_filter($records, static fn ($r) => $r->slug === $pageSlug));
                $sources = array_merge($info['sources'], array_values(array_filter($sources, static fn ($s) => $s['slug'] === $pageSlug)));
            }
        }

        $system = 'You are "Ask the archive", an assistant for a public archival catalogue. '
            .'Answer ONLY from the information below; never use outside knowledge. '
            .'Name the records you used by their title. If the information does not answer the question, say so plainly '
            .'and suggest a better search. Keep answers short. Reply in the language of the question.'
            .('' !== $info['text']
                ? "\n\nFor questions about visiting, opening times, contacting the institution or its services, answer from the institution information. "
                    .'Give only contact details that appear there, exactly as written. If opening times are not listed, say so and give the contact details instead.'
                    ."\n\nInstitution information:\n".$info['text']
                : '')
            // The retriever puts the page's record first when it is visible.
            .(isset($records[0]) && null !== $pageSlug && $records[0]->slug === $pageSlug
                ? "\n\nThe visitor is viewing the record \"".($records[0]->title ?: $pageSlug).'". Words like "this", "it" and "here" refer to that record.'
                : '')
            ."\n\nCatalogue records:\n".self::context($records);

        $messages = [['role' => 'system', 'content' => $system]];
        foreach (array_slice($history, -self::HISTORY_TURNS) as $turn) {
            $role = 'user' === ($turn['role'] ?? '') ? 'user' : 'assistant';
            $content = mb_substr(trim((string) ($turn['content'] ?? '')), 0, self::MAX_MESSAGE_CHARS);
            if ('' !== $content) {
                $messages[] = ['role' => $role, 'content' => $content];
            }
        }
        $messages[] = ['role' => 'user', 'content' => $question];

        try {
            $client = AiGatewayClient::fromSettings();
            $model = (string) AhgSettingsService::get('chatbot_model', '') ?: $client->getChatModel();
            // think=false: qwen3 otherwise spends the token budget on a hidden
            // reasoning pass and can return an empty answer (needs framework
            // v2.18.47+; older frameworks ignore the option).
            $result = $client->chat($messages, ['model' => $model, 'temperature' => 0.2, 'max_tokens' => 800, 'timeout' => 90, 'think' => false]);
            if (!empty($result['success'])) {
                return ['answer' => self::stripThinking($result['text']), 'sources' => $sources, 'mode' => 'ai', 'model' => $result['model']];
            }
            $error = (string) ($result['error'] ?? 'no answer');
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }
        error_log('chatbot.generation_failed: '.$error);

        return [
            'answer' => [] === $records
                ? 'The assistant is unavailable right now, and no matching records were found. Please try the catalogue search.'
                : 'The assistant is unavailable right now, but these are the records most relevant to your question.',
            'sources' => $sources,
            'mode' => 'fallback',
        ];
    }

    /**
     * Refuse a question before it costs anything. Returns the reason to show,
     * or null to proceed. Cheapest first: length, then per IP (10 a minute,
     * 100 a day, APCu), then the installation's daily cap from the log.
     *
     * ponytail: without APCu only the daily cap applies; nginx limit_req on
     * /chatbot/ask is the upgrade, and the answer for more than one host.
     */
    public static function throttle(string $ip, string $question): ?string
    {
        if (mb_strlen($question) > self::MAX_MESSAGE_CHARS) {
            return sprintf('Please keep your question under %d characters.', self::MAX_MESSAGE_CHARS);
        }

        if ('' !== $ip && \function_exists('apcu_add')) {
            foreach (['m' => [60, 10], 'd' => [86400, 100]] as $window => [$ttl, $max]) {
                $key = 'ahg_chatbot_'.$window.'_'.$ip;
                \apcu_add($key, 0, $ttl);
                if (\apcu_inc($key) > $max) {
                    return 'm' === $window
                        ? 'You are asking faster than the assistant can answer. Please wait a minute and try again.'
                        : 'You have reached today\'s limit for questions. Please come back tomorrow.';
                }
            }
        }

        try {
            $cap = (int) AhgSettingsService::get('chatbot_daily_cap', 1000);
            $today = DB::table('ahg_chatbot_message')->where('role', 'user')->where('created_at', '>=', date('Y-m-d 00:00:00'))->count();
            if ($cap > 0 && $today >= $cap) {
                return 'The assistant is very busy today. Please try again tomorrow, or use the catalogue search.';
            }
        } catch (\Throwable $e) {
            \class_exists('AhgCore\\Core\\AhgLog') && \AhgCore\Core\AhgLog::swallowed($e, basename(__FILE__).':'.__LINE__);
        }

        return null;
    }

    public static function newSessionId(): string
    {
        return 'chat_'.bin2hex(random_bytes(12));
    }

    /**
     * Log one turn. Personal details are masked BEFORE storage and no IP is
     * kept (POPIA); chatbot:purge-logs deletes turns past the retention period.
     * Best effort: a logging failure never breaks the conversation.
     *
     * @return int|null the new row id (assistant turns are rated by it)
     */
    public static function log(string $sessionId, string $role, string $content, array $meta = []): ?int
    {
        try {
            return (int) DB::table('ahg_chatbot_message')->insertGetId([
                'session_id' => mb_substr($sessionId, 0, 64),
                'role' => 'assistant' === $role ? 'assistant' : 'user',
                'content' => self::mask($content),
                'sources' => isset($meta['sources']) ? json_encode(array_column($meta['sources'], 'slug')) : null,
                'mode' => $meta['mode'] ?? null,
                'answered' => isset($meta['sources']) ? (int) ([] !== $meta['sources']) : null,
                'model' => isset($meta['model']) ? mb_substr((string) $meta['model'], 0, 100) : null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            \class_exists('AhgCore\\Core\\AhgLog') && \AhgCore\Core\AhgLog::swallowed($e, basename(__FILE__).':'.__LINE__);

            return null;
        }
    }

    /** Record a visitor's thumbs up (1) or down (-1) on an answer in their session. */
    public static function rate(string $sessionId, int $messageId, int $rating): bool
    {
        try {
            return DB::table('ahg_chatbot_message')
                ->where('id', $messageId)->where('session_id', $sessionId)->where('role', 'assistant')
                ->update(['rating' => $rating > 0 ? 1 : -1]) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Mask what visitors most often type about themselves: e-mail addresses,
     * South African ID numbers (13 digits) and phone numbers.
     *
     * ponytail: pattern masking, not entity recognition - a name typed in
     * free text is kept. The 30-day retention is the backstop.
     */
    public static function mask(string $text): string
    {
        $text = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[email]', $text);
        $text = preg_replace('/\b\d{13}\b/', '[id number]', $text);

        return preg_replace('/(?<!\w)\+?\d[\d ()-]{8,}\d(?!\w)/', '[phone]', $text);
    }

    /** Delete logged turns older than chatbot_retention_days. */
    public static function purge(?int $days = null): int
    {
        $days ??= max(1, (int) AhgSettingsService::get('chatbot_retention_days', 30));

        return DB::table('ahg_chatbot_message')->where('created_at', '<', date('Y-m-d H:i:s', strtotime("-{$days} days")))->delete();
    }

    /** qwen3 and similar emit <think>...</think> before the answer. */
    private static function stripThinking(string $text): string
    {
        return trim(preg_replace('#<think>.*?</think>#s', '', $text));
    }

    /** @param array<int,object> $records */
    private static function context(array $records): string
    {
        if ([] === $records) {
            return 'No matching catalogue records were found.';
        }
        $out = '';
        foreach ($records as $r) {
            $scope = trim(strip_tags((string) $r->scope_and_content));
            $block = '--- '.($r->identifier ? '['.$r->identifier.'] ' : '').($r->title ?: $r->slug)." ---\n"
                .('' !== $scope ? mb_substr($scope, 0, 1200) : '(no scope and content recorded)')."\n\n";
            if (strlen($out) + strlen($block) > self::MAX_CONTEXT_CHARS) {
                break;
            }
            $out .= $block;
        }

        return $out;
    }
}
