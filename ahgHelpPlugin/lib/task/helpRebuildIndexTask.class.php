<?php

/**
 * Rebuild help article body_text and sections from stored markdown.
 *
 * Re-parses all help_article markdown, regenerates body_text, body_html,
 * toc_json, word_count, and rebuilds help_section rows.
 *
 * Usage:
 *   php symfony help:rebuild-index
 */
class helpRebuildIndexTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
        ]);

        $this->namespace = 'help';
        $this->name = 'rebuild-index';
        $this->briefDescription = 'Rebuild help article text index and sections from stored markdown';
        $this->detailedDescription = <<<'EOF'
The [help:rebuild-index|INFO] task re-parses all help articles from their
stored body_markdown, regenerating body_html, body_text, toc_json, word_count,
and all help_section rows.

  [php symfony help:rebuild-index|INFO]
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        \AhgCore\Core\AhgDb::init();

        $pluginDir = sfConfig::get('sf_plugins_dir') . '/ahgHelpPlugin';
        require_once $pluginDir . '/lib/Services/HelpMarkdownParser.php';
        require_once $pluginDir . '/lib/Services/HelpArticleService.php';

        $articles = \Illuminate\Database\Capsule\Manager::table('help_article')
            ->select('id', 'slug', 'body_markdown', 'category', 'subcategory', 'source_file', 'related_plugin')
            ->get();

        $count = count($articles);
        $this->logSection('help', sprintf('Rebuilding %d articles...', $count));

        $rebuilt = 0;
        $errors = 0;

        foreach ($articles as $article) {
            try {
                $parsed = \AhgHelp\Services\HelpMarkdownParser::parse($article->body_markdown);

                $data = [
                    'title' => !empty($parsed['title']) ? $parsed['title'] : ucwords(str_replace('-', ' ', $article->slug)),
                    'category' => $article->category,
                    'subcategory' => $article->subcategory,
                    'source_file' => $article->source_file,
                    'body_markdown' => $article->body_markdown,
                    'body_html' => $parsed['body_html'],
                    'body_text' => $parsed['body_text'],
                    'toc' => $parsed['toc'],
                    'sections' => $parsed['sections'],
                    'word_count' => $parsed['word_count'],
                    'related_plugin' => $article->related_plugin,
                ];

                \AhgHelp\Services\HelpArticleService::upsertFromMarkdown($article->slug, $data);
                $rebuilt++;

                $this->logSection('rebuild', sprintf('%-50s %d words, %d sections', $article->slug, $parsed['word_count'], count($parsed['sections'])));
            } catch (\Exception $e) {
                $this->logSection('error', "Error rebuilding {$article->slug}: " . $e->getMessage());
                $errors++;
            }
        }

        $this->logSection('help', '');
        $this->logSection('help', sprintf('Done: %d rebuilt, %d errors', $rebuilt, $errors));

        return $errors > 0 ? 1 : 0;
    }
}
