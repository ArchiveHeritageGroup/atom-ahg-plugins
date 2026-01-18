<?php

/**
 * ahgNerPlugin Configuration
 *
 * Named Entity Recognition plugin for AtoM
 */
class ahgNerPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Named Entity Recognition - Extract entities from archival records using AI';
    public static $version = '1.0.0';
    public static $category = 'ai';

    public function routingLoadConfiguration(sfEvent $event)
    {
        $routing = $event->getSubject();
        
        // Register NER routes
        $routing->prependRoute('ahg_ner_summarize', new sfRoute(
            '/ner/summarize/:id',
            ['module' => 'ahgNer', 'action' => 'summarize']
        ));
        
        $routing->prependRoute('ahg_ner_extract', new sfRoute(
            '/ner/extract/:id',
            ['module' => 'ahgNer', 'action' => 'extract']
        ));
        
        $routing->prependRoute('ahg_ner_review', new sfRoute(
            '/ner/review',
            ['module' => 'ahgNer', 'action' => 'review']
        ));
        
        $routing->prependRoute('ahg_ner_health', new sfRoute(
            '/ner/health',
            ['module' => 'ahgNer', 'action' => 'health']
        ));
        
        $routing->prependRoute('ahg_ner_bulk_save', new sfRoute(
            '/ner/bulk-save',
            ['module' => 'ahgNer', 'action' => 'bulkSave']
        ));
    }

    public function initialize()
    {
        // Register route loading
        $this->dispatcher->connect('routing.load_configuration', [$this, 'routingLoadConfiguration']);
        
        // Add CSS
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
                    error_log('NER Plugin install: ' . $e->getMessage());
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
        $tables = ['ahg_ner_usage', 'ahg_ner_entity', 'ahg_ner_extraction', 'ahg_ner_settings'];
        foreach ($tables as $table) {
            try {
                \Illuminate\Database\Capsule\Manager::statement("DROP TABLE IF EXISTS `{$table}`");
            } catch (Exception $e) {
                error_log('NER Plugin uninstall: ' . $e->getMessage());
            }
        }
        return true;
    }
}
