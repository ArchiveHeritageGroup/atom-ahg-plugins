<?php

/**
 * AI Suggest Description Task
 *
 * Batch generate description suggestions for archival records.
 * Uses LLM (Ollama/OpenAI/Anthropic) to analyze OCR text and metadata
 * and generate scope_and_content suggestions for custodian review.
 *
 * Usage:
 *   php symfony ai:suggest-description --help
 *   php symfony ai:suggest-description --repository=123 --empty-only --limit=50
 *   php symfony ai:suggest-description --object=12345
 *   php symfony ai:suggest-description --with-ocr --level=item --dry-run
 */
class aiSuggestDescriptionTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('object', null, sfCommandOption::PARAMETER_OPTIONAL, 'Process specific object ID'),
            new sfCommandOption('repository', null, sfCommandOption::PARAMETER_OPTIONAL, 'Filter by repository ID'),
            new sfCommandOption('level', null, sfCommandOption::PARAMETER_OPTIONAL, 'Filter by level of description (fonds, series, file, item)'),
            new sfCommandOption('empty-only', null, sfCommandOption::PARAMETER_NONE, 'Only process records with empty scope_and_content'),
            new sfCommandOption('with-ocr', null, sfCommandOption::PARAMETER_NONE, 'Only process records that have OCR text'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Maximum number to process', 50),
            new sfCommandOption('template', null, sfCommandOption::PARAMETER_OPTIONAL, 'Prompt template ID'),
            new sfCommandOption('llm-config', null, sfCommandOption::PARAMETER_OPTIONAL, 'LLM config ID'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Preview without generating'),
            new sfCommandOption('delay', null, sfCommandOption::PARAMETER_OPTIONAL, 'Delay between requests in seconds', 2),
        ]);

        $this->namespace = 'ai';
        $this->name = 'suggest-description';
        $this->briefDescription = 'Generate AI description suggestions for archival records';
        $this->detailedDescription = <<<EOF
The [ai:suggest-description|INFO] task generates scope_and_content suggestions
using an LLM (Ollama, OpenAI, or Anthropic).

Suggestions are saved for custodian review before being applied to records.

Examples:
  [php symfony ai:suggest-description --object=12345|INFO]
    Generate a suggestion for a specific object

  [php symfony ai:suggest-description --repository=5 --empty-only --limit=100|INFO]
    Generate suggestions for up to 100 records in repository 5 that have no description

  [php symfony ai:suggest-description --with-ocr --level=item --dry-run|INFO]
    Preview which item-level records with OCR would be processed

  [php symfony ai:suggest-description --llm-config=2 --template=3|INFO]
    Use specific LLM configuration and prompt template
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        // Initialize
        sfContext::createInstance($this->configuration);
        \AhgCore\Core\AhgDb::init();

        $this->logSection('ai', 'AI Description Suggestion Task');
        $this->logSection('ai', str_repeat('-', 50));

        // Check if feature is enabled
        $enabled = $this->getSetting('suggest', 'enabled', '1');
        if ($enabled !== '1') {
            $this->logSection('ai', 'Suggestion feature is disabled in settings', null, 'ERROR');

            return 1;
        }

        // Load services
        require_once dirname(__FILE__) . '/../Services/DescriptionService.php';
        require_once dirname(__FILE__) . '/../Services/LlmService.php';
        require_once dirname(__FILE__) . '/../Services/PromptService.php';

        $descriptionService = new DescriptionService();
        $llmService = new LlmService();

        // Check LLM availability
        $llmConfigId = $options['llm-config'] ? (int) $options['llm-config'] : null;
        try {
            $provider = $llmService->getProvider($llmConfigId);
            if (!$provider->isAvailable()) {
                $this->logSection('ai', 'LLM provider is not available', null, 'ERROR');

                return 1;
            }
            $this->logSection('ai', 'Using LLM provider: ' . $provider->getName());
        } catch (Exception $e) {
            $this->logSection('ai', 'LLM error: ' . $e->getMessage(), null, 'ERROR');

            return 1;
        }

        // Get objects to process
        $objectIds = $this->getObjectsToProcess($options);
        $count = count($objectIds);

        $this->logSection('ai', "Found {$count} records to process");

        if ($count === 0) {
            $this->logSection('ai', 'No records match the criteria');

            return 0;
        }

        // Dry run - just list what would be processed
        if ($options['dry-run']) {
            $this->logSection('ai', '=== DRY RUN - No suggestions will be generated ===');

            $shown = 0;
            foreach ($objectIds as $id) {
                $info = $this->getObjectInfo($id);
                $this->logSection('ai', sprintf(
                    '[%d] %s (%s)',
                    $id,
                    $info['title'] ?? 'Untitled',
                    $info['identifier'] ?? '-'
                ));
                $shown++;
                if ($shown >= 20 && $count > 20) {
                    $this->logSection('ai', sprintf('... and %d more', $count - 20));
                    break;
                }
            }

            return 0;
        }

        // Process objects
        $templateId = $options['template'] ? (int) $options['template'] : null;
        $delay = (int) ($options['delay'] ?? 2);

        $processed = 0;
        $success = 0;
        $errors = 0;
        $startTime = time();

        foreach ($objectIds as $objectId) {
            $processed++;
            $info = $this->getObjectInfo($objectId);

            $this->logSection('ai', sprintf(
                '[%d/%d] Processing: %s (ID: %d)',
                $processed,
                $count,
                $info['title'] ?? 'Untitled',
                $objectId
            ));

            try {
                $result = $descriptionService->generateSuggestion(
                    $objectId,
                    $templateId,
                    $llmConfigId,
                    null // CLI user
                );

                if ($result['success']) {
                    $success++;
                    $this->logSection('ai', sprintf(
                        '  -> Suggestion created (ID: %d, %d tokens, %dms)',
                        $result['suggestion_id'],
                        $result['tokens_used'] ?? 0,
                        $result['generation_time_ms'] ?? 0
                    ));
                } else {
                    $errors++;
                    $this->logSection('ai', '  -> Error: ' . ($result['error'] ?? 'Unknown'), null, 'ERROR');
                }
            } catch (Exception $e) {
                $errors++;
                $this->logSection('ai', '  -> Exception: ' . $e->getMessage(), null, 'ERROR');
            }

            // Rate limiting delay
            if ($processed < $count && $delay > 0) {
                sleep($delay);
            }
        }

        // Summary
        $elapsed = time() - $startTime;
        $this->logSection('ai', str_repeat('-', 50));
        $this->logSection('ai', sprintf('Completed in %d seconds', $elapsed));
        $this->logSection('ai', sprintf('Processed: %d | Success: %d | Errors: %d', $processed, $success, $errors));

        if ($success > 0) {
            $this->logSection('ai', 'Review suggestions at: /ai/suggest/review');
        }

        return $errors > 0 ? 1 : 0;
    }

    /**
     * Get object IDs matching the criteria
     */
    protected function getObjectsToProcess($options): array
    {
        // Single object
        if (!empty($options['object'])) {
            return [(int) $options['object']];
        }

        $query = \Illuminate\Database\Capsule\Manager::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', 'en');
            })
            ->where('io.id', '>', 1); // Exclude root

        // Repository filter
        if (!empty($options['repository'])) {
            $query->where('io.repository_id', (int) $options['repository']);
        }

        // Level filter
        if (!empty($options['level'])) {
            $level = strtolower($options['level']);
            $levelId = $this->getLevelId($level);
            if ($levelId) {
                $query->where('io.level_of_description_id', $levelId);
            }
        }

        // Empty scope_and_content only
        if ($options['empty-only']) {
            $query->where(function ($q) {
                $q->whereNull('ioi.scope_and_content')
                  ->orWhere('ioi.scope_and_content', '=', '');
            });
        }

        // With OCR only
        if ($options['with-ocr']) {
            $query->whereExists(function ($subquery) {
                $subquery->select(\Illuminate\Database\Capsule\Manager::raw(1))
                    ->from('digital_object as do')
                    ->join('iiif_ocr_text as ocr', 'do.id', '=', 'ocr.digital_object_id')
                    ->whereColumn('do.object_id', 'io.id')
                    ->whereNotNull('ocr.full_text')
                    ->where('ocr.full_text', '!=', '');
            });
        }

        // Exclude objects that already have pending suggestions
        $maxPending = (int) $this->getSetting('suggest', 'max_pending_per_object', 3);
        $query->whereNotExists(function ($subquery) use ($maxPending) {
            $subquery->select(\Illuminate\Database\Capsule\Manager::raw(1))
                ->from('ahg_description_suggestion')
                ->whereColumn('object_id', 'io.id')
                ->where('status', 'pending')
                ->groupBy('object_id')
                ->havingRaw('COUNT(*) >= ?', [$maxPending]);
        });

        // Limit
        $limit = (int) ($options['limit'] ?? 50);
        $query->limit($limit);

        // Order by oldest first (FIFO)
        $query->orderBy('io.id');

        return $query->pluck('io.id')->toArray();
    }

    /**
     * Get basic object info for logging
     */
    protected function getObjectInfo(int $objectId): array
    {
        $object = \Illuminate\Database\Capsule\Manager::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', 'en');
            })
            ->where('io.id', $objectId)
            ->select('io.identifier', 'ioi.title')
            ->first();

        if (!$object) {
            return ['title' => 'Unknown', 'identifier' => ''];
        }

        return [
            'title' => $object->title ?? 'Untitled',
            'identifier' => $object->identifier ?? '',
        ];
    }

    /**
     * Get level of description term ID
     */
    protected function getLevelId(string $levelName): ?int
    {
        $term = \Illuminate\Database\Capsule\Manager::table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', 34) // Level of description taxonomy
            ->whereRaw('LOWER(term_i18n.name) = ?', [strtolower($levelName)])
            ->first();

        return $term ? $term->id : null;
    }

    /**
     * Get a setting value
     */
    protected function getSetting(string $feature, string $key, $default = null)
    {
        $setting = \Illuminate\Database\Capsule\Manager::table('ahg_ai_settings')
            ->where('feature', $feature)
            ->where('setting_key', $key)
            ->first();

        return $setting ? $setting->setting_value : $default;
    }
}
