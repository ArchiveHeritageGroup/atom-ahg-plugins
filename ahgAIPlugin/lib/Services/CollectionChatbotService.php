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
    private const MAX_CONTEXT_CHARS = 5000;

    /**
     * @param array<int,array{role:string,content:string}> $history
     * @return array{answer:string,sources:array,mode:string,error?:string,tokens_used?:int}
     */
    public static function chat(string $message, array $history = [], string $culture = 'en'): array
    {
        $message = trim($message);
        if ('' === $message) {
            return ['answer' => 'Please ask a question about the collection.', 'sources' => [], 'mode' => 'empty'];
        }

        $records = self::retrieve($message, $culture);
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
            $aiDir = \sfConfig::get('sf_plugins_dir') . '/ahgAIPlugin';
            require_once $aiDir . '/lib/Services/LlmService.php';

            $provider = (new \LlmService())->getProvider();
            $result = $provider->complete($systemPrompt, $userPrompt, ['max_tokens' => 800, 'temperature' => 0.2]);

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
            require_once \sfConfig::get('sf_plugins_dir') . '/ahgAIPlugin/lib/Services/LlmService.php';

            return (new \LlmService())->getProvider()->isAvailable();
        } catch (\Throwable $e) {
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
    public static function retrieve(string $message, string $culture = 'en'): array
    {
        $fulltext = self::retrieveFulltext($message, $culture);

        $semantic = self::retrieveSemantic($message, $culture);
        if (empty($semantic)) {
            return $fulltext;
        }

        // Merge, semantic-first, dedupe by id, cap at MAX_RECORDS.
        $merged = [];
        foreach (array_merge($semantic, $fulltext) as $row) {
            $id = (int) ($row->id ?? 0);
            if ($id > 0 && !isset($merged[$id])) {
                $merged[$id] = $row;
            }
            if (count($merged) >= self::MAX_RECORDS) {
                break;
            }
        }

        return array_values($merged);
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

            $hits = (new \CatalogueVectorService())->search($message, self::MAX_RECORDS);
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
                ->limit(self::MAX_RECORDS)
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
}
