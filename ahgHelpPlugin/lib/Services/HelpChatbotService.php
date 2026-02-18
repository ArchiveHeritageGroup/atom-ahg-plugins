<?php

namespace AhgHelp\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Help Chatbot Service
 *
 * Two modes:
 * 1. Search-based (default): Matches user questions to help articles using FULLTEXT search
 * 2. AI-powered: Uses LLM (via ahgAIPlugin's LlmService) to generate contextual answers
 *
 * AI mode requires ahgAIPlugin to be installed and an LLM config to be set up.
 */
class HelpChatbotService
{
    /**
     * Process a chat message and return a response.
     *
     * @param string $message     User's question
     * @param string $mode        'search' or 'ai'
     * @param array  $history     Previous chat messages [{role, content}, ...]
     *
     * @return array {mode, answer, sources: [{slug, title, anchor?}], error?}
     */
    public static function chat(string $message, string $mode = 'search', array $history = []): array
    {
        if ($mode === 'ai' && self::isAiAvailable()) {
            return self::aiChat($message, $history);
        }

        return self::searchChat($message);
    }

    /**
     * Search-based chat: Match question to articles and return relevant snippets.
     */
    protected static function searchChat(string $message): array
    {
        $pluginDir = \sfConfig::get('sf_plugins_dir') . '/ahgHelpPlugin';
        require_once $pluginDir . '/lib/Services/HelpArticleService.php';

        // Search articles
        $articles = HelpArticleService::search($message, 5);
        $sections = HelpArticleService::searchSections($message, 5);

        if (empty($articles) && empty($sections)) {
            return [
                'mode' => 'search',
                'answer' => 'I could not find any help articles matching your question. Try rephrasing or browse the help categories.',
                'sources' => [],
            ];
        }

        // Build answer from top results
        $answer = '';
        $sources = [];

        if (!empty($articles)) {
            $top = $articles[0];
            $answer = "Based on the documentation, here's what I found:\n\n";
            $answer .= "**" . $top['title'] . "**\n";

            // Get a better snippet from the article
            $snippet = $top['snippet'] ?? '';
            if (strlen($snippet) > 50) {
                $answer .= $snippet . "...\n\n";
            }

            $sources[] = [
                'slug' => $top['slug'],
                'title' => $top['title'],
            ];

            // Add more results
            if (count($articles) > 1) {
                $answer .= "You might also find these articles helpful:\n";
                for ($i = 1; $i < min(4, count($articles)); $i++) {
                    $answer .= "- " . $articles[$i]['title'] . "\n";
                    $sources[] = [
                        'slug' => $articles[$i]['slug'],
                        'title' => $articles[$i]['title'],
                    ];
                }
            }
        }

        // Add section-level results
        if (!empty($sections)) {
            if (empty($articles)) {
                $answer = "I found relevant sections in the documentation:\n\n";
            } else {
                $answer .= "\nRelevant sections:\n";
            }

            $seenSlugs = array_column($sources, 'slug');
            foreach (array_slice($sections, 0, 3) as $sec) {
                $answer .= "- " . $sec['heading'] . " (in " . $sec['article_title'] . ")\n";
                if (!in_array($sec['slug'], $seenSlugs)) {
                    $sources[] = [
                        'slug' => $sec['slug'],
                        'title' => $sec['article_title'],
                        'anchor' => $sec['anchor'],
                    ];
                    $seenSlugs[] = $sec['slug'];
                }
            }
        }

        return [
            'mode' => 'search',
            'answer' => trim($answer),
            'sources' => $sources,
        ];
    }

    /**
     * AI-powered chat: Use LLM to generate a contextual answer from help articles.
     */
    protected static function aiChat(string $message, array $history = []): array
    {
        $pluginDir = \sfConfig::get('sf_plugins_dir') . '/ahgHelpPlugin';
        require_once $pluginDir . '/lib/Services/HelpArticleService.php';

        // Find relevant articles for context
        $articles = HelpArticleService::search($message, 3);
        $sections = HelpArticleService::searchSections($message, 5);

        // Build context from documentation
        $context = self::buildContext($articles, $sections);

        // Build system prompt
        $systemPrompt = "You are a helpful documentation assistant for AtoM Heratio, an archival management system. "
            . "Answer questions based ONLY on the provided documentation context. "
            . "If the documentation doesn't cover the question, say so clearly. "
            . "Be concise and direct. Use markdown formatting for readability. "
            . "Always reference which help article the information comes from.\n\n"
            . "Documentation context:\n" . $context;

        // Build user prompt with conversation history
        $userPrompt = '';
        foreach (array_slice($history, -4) as $msg) {
            $role = $msg['role'] === 'user' ? 'User' : 'Assistant';
            $userPrompt .= $role . ': ' . $msg['content'] . "\n\n";
        }
        $userPrompt .= 'User: ' . $message;

        try {
            // Load LLM service from ahgAIPlugin
            $aiPluginDir = \sfConfig::get('sf_plugins_dir') . '/ahgAIPlugin';
            require_once $aiPluginDir . '/lib/Services/LlmService.php';

            $llm = new \LlmService();
            $provider = $llm->getProvider();
            $result = $provider->complete($systemPrompt, $userPrompt, [
                'max_tokens' => 1000,
                'temperature' => 0.3,
            ]);

            if (!empty($result['success']) && !empty($result['text'])) {
                // Build sources list
                $sources = [];
                foreach ($articles as $a) {
                    $sources[] = ['slug' => $a['slug'], 'title' => $a['title']];
                }

                return [
                    'mode' => 'ai',
                    'answer' => $result['text'],
                    'sources' => $sources,
                    'tokens_used' => $result['tokens_used'] ?? 0,
                ];
            }

            // AI failed, fall back to search
            $searchResult = self::searchChat($message);
            $searchResult['error'] = $result['error'] ?? 'AI response failed';

            return $searchResult;
        } catch (\Exception $e) {
            // Fall back to search mode on error
            $searchResult = self::searchChat($message);
            $searchResult['error'] = 'AI unavailable: ' . $e->getMessage();

            return $searchResult;
        }
    }

    /**
     * Build context string from search results for the LLM.
     */
    protected static function buildContext(array $articles, array $sections): string
    {
        $context = '';
        $totalChars = 0;
        $maxChars = 4000;

        foreach ($articles as $a) {
            if ($totalChars > $maxChars) {
                break;
            }

            // Get full article text (truncated)
            $article = HelpArticleService::getBySlug($a['slug']);
            if ($article) {
                $text = mb_substr($article['body_text'], 0, 1500);
                $context .= "--- Article: " . $article['title'] . " ---\n" . $text . "\n\n";
                $totalChars += strlen($text);
            }
        }

        // Add section context
        foreach ($sections as $s) {
            if ($totalChars > $maxChars) {
                break;
            }

            $snippet = $s['snippet'] ?? '';
            if ($snippet) {
                $context .= "--- Section: " . $s['heading'] . " (in " . $s['article_title'] . ") ---\n" . $snippet . "\n\n";
                $totalChars += strlen($snippet);
            }
        }

        return $context ?: 'No relevant documentation found.';
    }

    /**
     * Check if AI mode is available (ahgAIPlugin installed + LLM configured).
     */
    public static function isAiAvailable(): bool
    {
        try {
            $aiPluginDir = \sfConfig::get('sf_plugins_dir') . '/ahgAIPlugin';
            if (!is_dir($aiPluginDir)) {
                return false;
            }

            // Check if plugin is enabled
            $enabled = DB::table('atom_plugin')
                ->where('name', 'ahgAIPlugin')
                ->where('is_enabled', 1)
                ->exists();

            if (!$enabled) {
                return false;
            }

            // Check if any LLM config exists
            try {
                $hasConfig = DB::table('ahg_llm_config')->where('is_active', 1)->exists();

                return $hasConfig;
            } catch (\Exception $e) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }
}
