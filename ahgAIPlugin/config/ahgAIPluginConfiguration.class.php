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
        $router = new \AtomFramework\Routing\RouteLoader('ai');

        // NER routes
        $router->any('ahg_ai_ner_extract', '/ai/ner/extract/:id', 'nerExtract');
        $router->any('ahg_ai_ner_review', '/ai/ner/review', 'review');
        $router->any('ahg_ai_ner_bulk_save', '/ai/ner/bulk-save', 'bulkSave');
        $router->any('ahg_ai_create_date', '/ai/ner/create-date', 'createDate');
        $router->any('ahg_ai_preview_date_split', '/ai/ner/preview-date-split', 'previewDateSplit');

        // Summarization routes
        $router->any('ahg_ai_summarize', '/ai/summarize/:id', 'summarize');

        // Translation routes
        $router->any('ahg_ai_translate', '/ai/translate/:id', 'translate');
        $router->any('ahg_ai_translate_batch', '/ai/translate/batch', 'translateBatch');

        // Spellcheck routes
        $router->any('ahg_ai_spellcheck', '/ai/spellcheck/:id', 'spellcheck');

        // Handwriting Text Recognition (HTR) routes
        $router->any('ahg_ai_htr', '/ai/htr/:id', 'htr');

        // Settings & Health
        $router->any('ahg_ai_settings', '/ai/settings', 'settings');
        $router->any('ahg_ai_health', '/ai/health', 'health');

        // LLM Description Suggestion routes
        $router->any('ahg_ai_suggest', '/ai/suggest/:id', 'suggest');
        $router->any('ahg_ai_suggest_preview', '/ai/suggest/:id/preview', 'suggestPreview');
        $router->any('ahg_ai_suggest_review', '/ai/suggest/review', 'suggestReview');
        $router->any('ahg_ai_suggest_decision', '/ai/suggest/:id/decision', 'suggestDecision');
        $router->any('ahg_ai_llm_configs', '/ai/llm/configs', 'llmConfigs');
        $router->any('ahg_ai_llm_health', '/ai/llm/health', 'llmHealth');
        $router->any('ahg_ai_templates', '/ai/templates', 'templates');

        // NER PDF Overlay Display routes (Issue #20)
        $router->any('ahg_ai_ner_pdf_overlay', '/ai/ner/pdf-overlay/:id', 'pdfOverlay', ['id' => '\d+']);
        $router->any('ahg_ai_ner_approved_entities', '/ai/ner/approved-entities/:id', 'getApprovedEntities', ['id' => '\d+']);

        $router->register($event->getSubject());
    }

    public function initialize()
    {
        // Enable the ai module
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'ai';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        $this->dispatcher->connect('routing.load_configuration', [$this, 'routingLoadConfiguration']);
        $this->dispatcher->connect('response.filter_content', [$this, 'filterContent']);

        // Auto-trigger NER on digital object upload (Issue #19)
        $this->dispatcher->connect('QubitDigitalObject::insert', [$this, 'onDigitalObjectInsert']);
    }

    public function filterContent(sfEvent $event, $content)
    {
        return $content;
    }

    /**
     * Handle digital object insert event - auto-trigger NER extraction
     * Issue #19: NER on Document Upload
     */
    public function onDigitalObjectInsert(sfEvent $event)
    {
        try {
            $digitalObject = $event->getSubject();

            // Get the parent information object
            $objectId = $digitalObject->objectId;
            if (!$objectId) {
                return;
            }

            // Check if auto-trigger is enabled
            if (!$this->isAutoTriggerEnabled()) {
                return;
            }

            // Check if this is a processable document type
            $mimeType = $digitalObject->mimeType ?? '';
            if (!$this->isProcessableMimeType($mimeType)) {
                return;
            }

            // Queue NER extraction job
            $this->queueNerExtraction($objectId, $digitalObject->id);

        } catch (Exception $e) {
            error_log('AI Plugin: Auto-trigger NER failed - ' . $e->getMessage());
        }
    }

    /**
     * Check if auto-trigger NER is enabled in settings
     */
    private function isAutoTriggerEnabled(): bool
    {
        try {
            // Initialize database if needed
            if (!class_exists('\\Illuminate\\Database\\Capsule\\Manager')) {
                return false;
            }

            // Check ahg_ner_settings first (used by AI Services UI)
            $setting = \Illuminate\Database\Capsule\Manager::table('ahg_ner_settings')
                ->where('setting_key', 'auto_extract_on_upload')
                ->first();

            if ($setting) {
                return $setting->setting_value === '1';
            }

            // Fallback to ahg_ai_settings
            $setting = \Illuminate\Database\Capsule\Manager::table('ahg_ai_settings')
                ->where('feature', 'ner')
                ->where('setting_key', 'auto_trigger_on_upload')
                ->first();

            return $setting && $setting->setting_value === '1';
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if the mime type is processable for NER
     */
    private function isProcessableMimeType(string $mimeType): bool
    {
        $processable = [
            'application/pdf',
            'text/plain',
            'text/html',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/rtf',
        ];

        return in_array($mimeType, $processable, true);
    }

    /**
     * Queue NER extraction job via Gearman
     */
    private function queueNerExtraction(int $objectId, int $digitalObjectId): void
    {
        try {
            // Try Gearman first
            if (class_exists('GearmanClient')) {
                $client = new GearmanClient();
                $client->addServer();

                $params = [
                    'objectId' => $objectId,
                    'runNer' => true,
                    'runSummarize' => false,
                    'autoTriggered' => true,
                    'digitalObjectId' => $digitalObjectId,
                ];

                $client->doBackground('arNerExtractJob', json_encode($params));

                // Log the queued job
                $this->logAutoTrigger($objectId, $digitalObjectId, 'queued');
                return;
            }
        } catch (Exception $e) {
            // Gearman not available, fall through to database queue
        }

        // Fallback: store in database queue for later processing
        try {
            \Illuminate\Database\Capsule\Manager::table('ahg_ai_pending_extraction')->insert([
                'object_id' => $objectId,
                'digital_object_id' => $digitalObjectId,
                'task_type' => 'ner',
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $this->logAutoTrigger($objectId, $digitalObjectId, 'pending');
        } catch (Exception $e) {
            error_log('AI Plugin: Failed to queue NER extraction - ' . $e->getMessage());
        }
    }

    /**
     * Log auto-trigger event
     */
    private function logAutoTrigger(int $objectId, int $digitalObjectId, string $status): void
    {
        try {
            \Illuminate\Database\Capsule\Manager::table('ahg_ai_auto_trigger_log')->insert([
                'object_id' => $objectId,
                'digital_object_id' => $digitalObjectId,
                'task_type' => 'ner',
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Exception $e) {
            // Logging table might not exist yet, ignore
        }
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
