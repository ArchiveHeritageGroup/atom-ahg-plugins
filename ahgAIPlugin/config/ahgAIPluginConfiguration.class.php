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
            ['module' => 'ai', 'action' => 'review']
        ));

        $routing->prependRoute('ahg_ai_ner_bulk_save', new sfRoute(
            '/ai/ner/bulk-save',
            ['module' => 'ai', 'action' => 'bulkSave']
        ));

        $routing->prependRoute('ahg_ai_create_date', new sfRoute(
            '/ai/ner/create-date',
            ['module' => 'ai', 'action' => 'createDate']
        ));

        $routing->prependRoute('ahg_ai_preview_date_split', new sfRoute(
            '/ai/ner/preview-date-split',
            ['module' => 'ai', 'action' => 'previewDateSplit']
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

        // LLM Description Suggestion routes
        $routing->prependRoute('ahg_ai_suggest', new sfRoute(
            '/ai/suggest/:id',
            ['module' => 'ai', 'action' => 'suggest']
        ));

        $routing->prependRoute('ahg_ai_suggest_preview', new sfRoute(
            '/ai/suggest/:id/preview',
            ['module' => 'ai', 'action' => 'suggestPreview']
        ));

        $routing->prependRoute('ahg_ai_suggest_review', new sfRoute(
            '/ai/suggest/review',
            ['module' => 'ai', 'action' => 'suggestReview']
        ));

        $routing->prependRoute('ahg_ai_suggest_decision', new sfRoute(
            '/ai/suggest/:id/decision',
            ['module' => 'ai', 'action' => 'suggestDecision']
        ));

        $routing->prependRoute('ahg_ai_llm_configs', new sfRoute(
            '/ai/llm/configs',
            ['module' => 'ai', 'action' => 'llmConfigs']
        ));

        $routing->prependRoute('ahg_ai_llm_health', new sfRoute(
            '/ai/llm/health',
            ['module' => 'ai', 'action' => 'llmHealth']
        ));

        $routing->prependRoute('ahg_ai_templates', new sfRoute(
            '/ai/templates',
            ['module' => 'ai', 'action' => 'templates']
        ));

        // NER PDF Overlay Display routes (Issue #20)
        $routing->prependRoute('ahg_ai_ner_pdf_overlay', new sfRoute(
            '/ai/ner/pdf-overlay/:id',
            ['module' => 'ai', 'action' => 'pdfOverlay'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('ahg_ai_ner_approved_entities', new sfRoute(
            '/ai/ner/approved-entities/:id',
            ['module' => 'ai', 'action' => 'getApprovedEntities'],
            ['id' => '\d+']
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
