<?php

/**
 * ahgAIPlugin Configuration
 *
 * Consolidated AI tools plugin for AtoM: NER, Translation, Summarization, Spellcheck
 */
class ahgAIPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'AI Tools - NER, Translation, Summarization, Spellcheck for archival records';
    public static $version = '2.0.0';
    public static $category = 'ai';

    public function routingLoadConfiguration(sfEvent $event)
    {
        $routing = $event->getSubject();

        // NER routes
        $routing->prependRoute('ahg_ai_ner_extract', new sfRoute(
            '/ai/ner/extract/:id',
            ['module' => 'ai', 'action' => 'nerExtract']
        ));

        $routing->prependRoute('ahg_ai_ner_review', new sfRoute(
            '/ai/ner/review',
            ['module' => 'ai', 'action' => 'nerReview']
        ));

        $routing->prependRoute('ahg_ai_ner_bulk_save', new sfRoute(
            '/ai/ner/bulk-save',
            ['module' => 'ai', 'action' => 'nerBulkSave']
        ));

        // Summarization routes
        $routing->prependRoute('ahg_ai_summarize', new sfRoute(
            '/ai/summarize/:id',
            ['module' => 'ai', 'action' => 'summarize']
        ));

        // Translation routes
        $routing->prependRoute('ahg_ai_translate', new sfRoute(
            '/ai/translate/:id',
            ['module' => 'ai', 'action' => 'translate']
        ));

        $routing->prependRoute('ahg_ai_translate_batch', new sfRoute(
            '/ai/translate/batch',
            ['module' => 'ai', 'action' => 'translateBatch']
        ));

        // Spellcheck routes
        $routing->prependRoute('ahg_ai_spellcheck', new sfRoute(
            '/ai/spellcheck/:id',
            ['module' => 'ai', 'action' => 'spellcheck']
        ));

        // Handwriting Text Recognition (HTR) routes
        $routing->prependRoute('ahg_ai_htr', new sfRoute(
            '/ai/htr/:id',
            ['module' => 'ai', 'action' => 'htr']
        ));

        // Settings & Health
        $routing->prependRoute('ahg_ai_settings', new sfRoute(
            '/ai/settings',
            ['module' => 'ai', 'action' => 'settings']
        ));

        $routing->prependRoute('ahg_ai_health', new sfRoute(
            '/ai/health',
            ['module' => 'ai', 'action' => 'health']
        ));
    }

    public function initialize()
    {
        // Enable the ai module
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'ai';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        $this->dispatcher->connect('routing.load_configuration', [$this, 'routingLoadConfiguration']);
        $this->dispatcher->connect('response.filter_content', [$this, 'filterContent']);
    }

    public function filterContent(sfEvent $event, $content)
    {
        return $content;
    }

    public static function install()
    {
        $sqlFile = dirname(__FILE__) . '/../data/install.sql';
        if (!file_exists($sqlFile)) {
            throw new sfException('Install SQL file not found: ' . $sqlFile);
        }
        $sql = file_get_contents($sqlFile);
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function ($stmt) { return !empty($stmt) && strpos($stmt, '--') !== 0; }
        );
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    \Illuminate\Database\Capsule\Manager::statement($statement);
                } catch (Exception $e) {
                    error_log('AI Plugin install: ' . $e->getMessage());
                }
            }
        }
        return true;
    }

    public static function uninstall($keepData = true)
    {
        if ($keepData) {
            return true;
        }
        $tables = [
            'ahg_ai_usage',
            'ahg_ai_settings',
            'ahg_ner_entity',
            'ahg_ner_extraction',
            'ahg_translation_queue',
            'ahg_translation_log'
        ];
        foreach ($tables as $table) {
            try {
                \Illuminate\Database\Capsule\Manager::statement("DROP TABLE IF EXISTS `{$table}`");
            } catch (Exception $e) {
                error_log('AI Plugin uninstall: ' . $e->getMessage());
            }
        }
        return true;
    }
}
