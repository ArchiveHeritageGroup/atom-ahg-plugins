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

    public function initialize()
    {
        // Add CSS
        $this->dispatcher->connect('response.filter_content', [$this, 'filterContent']);
    }

    public function filterContent(sfEvent $event, $content)
    {
        // Add any dynamic content injection here
        return $content;
    }

    /**
     * Install plugin tables
     */
    public static function install()
    {
        $sqlFile = dirname(__FILE__) . '/../data/install.sql';
        
        if (!file_exists($sqlFile)) {
            throw new sfException('Install SQL file not found: ' . $sqlFile);
        }

        $sql = file_get_contents($sqlFile);
        $conn = Propel::getConnection();
        
        // Split by semicolon and execute each statement
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && strpos($stmt, '--') !== 0;
            }
        );

        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    $conn->exec($statement);
                } catch (Exception $e) {
                    // Log but continue - table may already exist
                    error_log('NER Plugin install: ' . $e->getMessage());
                }
            }
        }

        return true;
    }

    /**
     * Uninstall plugin tables
     */
    public static function uninstall($keepData = true)
    {
        if ($keepData) {
            return true;
        }

        $tables = [
            'ahg_ner_usage',
            'ahg_ner_entity',
            'ahg_ner_extraction',
            'ahg_ner_settings'
        ];

        $conn = Propel::getConnection();
        
        foreach ($tables as $table) {
            try {
                $conn->exec("DROP TABLE IF EXISTS {$table}");
            } catch (Exception $e) {
                error_log('NER Plugin uninstall: ' . $e->getMessage());
            }
        }

        return true;
    }
}
