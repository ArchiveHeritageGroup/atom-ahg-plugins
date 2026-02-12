<?php

namespace AtomFramework\Console\Commands\Ai;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * AI Suggest Description Command.
 *
 * Batch generate description suggestions for archival records using LLM.
 */
class SuggestDescriptionCommand extends BaseCommand
{
    protected string $name = 'ai:suggest-description';
    protected string $description = 'Generate AI description suggestions for archival records';
    protected string $detailedDescription = <<<'EOF'
    Generates scope_and_content suggestions using an LLM (Ollama, OpenAI, or Anthropic).
    Suggestions are saved for custodian review before being applied to records.

    Examples:
      php bin/atom ai:suggest-description --object=12345
      php bin/atom ai:suggest-description --repository=5 --empty-only --limit=100
      php bin/atom ai:suggest-description --with-ocr --level=item --dry-run
      php bin/atom ai:suggest-description --llm-config=2 --template=3
    EOF;

    protected function configure(): void
    {
        $this->addOption('object', null, 'Process specific object ID');
        $this->addOption('repository', null, 'Filter by repository ID');
        $this->addOption('level', null, 'Filter by level of description (fonds, series, file, item)');
        $this->addOption('empty-only', null, 'Only process records with empty scope_and_content');
        $this->addOption('with-ocr', null, 'Only process records that have OCR text');
        $this->addOption('limit', null, 'Maximum number to process', '50');
        $this->addOption('template', null, 'Prompt template ID');
        $this->addOption('llm-config', null, 'LLM config ID');
        $this->addOption('dry-run', null, 'Preview without generating');
        $this->addOption('delay', null, 'Delay between requests in seconds', '2');
    }

    protected function handle(): int
    {
        $this->info('AI Description Suggestion Task');
        $this->line(str_repeat('-', 50));

        // Check if feature is enabled
        $enabled = $this->getSetting('suggest', 'enabled', '1');
        if ($enabled !== '1') {
            $this->error('Suggestion feature is disabled in settings');
            return 1;
        }

        // Load services
        require_once dirname(__DIR__) . '/Services/DescriptionService.php';
        require_once dirname(__DIR__) . '/Services/LlmService.php';
        require_once dirname(__DIR__) . '/Services/PromptService.php';

        $descriptionService = new \DescriptionService();
        $llmService = new \LlmService();

        // Check LLM availability
        $llmConfigId = $this->option('llm-config') ? (int) $this->option('llm-config') : null;
        try {
            $provider = $llmService->getProvider($llmConfigId);
            if (!$provider->isAvailable()) {
                $this->error('LLM provider is not available');
                return 1;
            }
            $this->info('Using LLM provider: ' . $provider->getName());
        } catch (\Exception $e) {
            $this->error('LLM error: ' . $e->getMessage());
            return 1;
        }

        // Get objects to process
        $objectIds = $this->getObjectsToProcess();
        $count = count($objectIds);

        $this->info("Found {$count} records to process");

        if ($count === 0) {
            $this->info('No records match the criteria');
            return 0;
        }

        // Dry run
        if ($this->hasOption('dry-run')) {
            $this->bold('=== DRY RUN - No suggestions will be generated ===');

            $shown = 0;
            foreach ($objectIds as $id) {
                $info = $this->getObjectInfo($id);
                $this->line(sprintf(
                    '  [%d] %s (%s)',
                    $id,
                    $info['title'] ?? 'Untitled',
                    $info['identifier'] ?? '-'
                ));
                $shown++;
                if ($shown >= 20 && $count > 20) {
                    $this->line(sprintf('  ... and %d more', $count - 20));
                    break;
                }
            }

            return 0;
        }

        // Process objects
        $templateId = $this->option('template') ? (int) $this->option('template') : null;
        $delay = (int) ($this->option('delay') ?? 2);

        $processed = 0;
        $success = 0;
        $errors = 0;
        $startTime = time();

        foreach ($objectIds as $objectId) {
            $processed++;
            $info = $this->getObjectInfo($objectId);

            $this->line(sprintf(
                '  [%d/%d] Processing: %s (ID: %d)',
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
                    null
                );

                if ($result['success']) {
                    $success++;
                    $this->info(sprintf(
                        '    -> Suggestion created (ID: %d, %d tokens, %dms)',
                        $result['suggestion_id'],
                        $result['tokens_used'] ?? 0,
                        $result['generation_time_ms'] ?? 0
                    ));
                } else {
                    $errors++;
                    $this->error('    -> Error: ' . ($result['error'] ?? 'Unknown'));
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error('    -> Exception: ' . $e->getMessage());
            }

            // Rate limiting delay
            if ($processed < $count && $delay > 0) {
                sleep($delay);
            }
        }

        // Summary
        $elapsed = time() - $startTime;
        $this->line(str_repeat('-', 50));
        $this->info(sprintf('Completed in %d seconds', $elapsed));
        $this->info(sprintf('Processed: %d | Success: %d | Errors: %d', $processed, $success, $errors));

        if ($success > 0) {
            $this->info('Review suggestions at: /ai/suggest/review');
        }

        return $errors > 0 ? 1 : 0;
    }

    private function getObjectsToProcess(): array
    {
        if ($this->option('object')) {
            return [(int) $this->option('object')];
        }

        $query = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', 'en');
            })
            ->where('io.id', '>', 1);

        // Repository filter
        if ($this->option('repository')) {
            $query->where('io.repository_id', (int) $this->option('repository'));
        }

        // Level filter
        if ($this->option('level')) {
            $level = strtolower($this->option('level'));
            $levelId = $this->getLevelId($level);
            if ($levelId) {
                $query->where('io.level_of_description_id', $levelId);
            }
        }

        // Empty scope_and_content only
        if ($this->hasOption('empty-only')) {
            $query->where(function ($q) {
                $q->whereNull('ioi.scope_and_content')
                  ->orWhere('ioi.scope_and_content', '=', '');
            });
        }

        // With OCR only
        if ($this->hasOption('with-ocr')) {
            $query->whereExists(function ($subquery) {
                $subquery->select(DB::raw(1))
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
            $subquery->select(DB::raw(1))
                ->from('ahg_description_suggestion')
                ->whereColumn('object_id', 'io.id')
                ->where('status', 'pending')
                ->groupBy('object_id')
                ->havingRaw('COUNT(*) >= ?', [$maxPending]);
        });

        $limit = (int) ($this->option('limit') ?? 50);
        $query->limit($limit);
        $query->orderBy('io.id');

        return $query->pluck('io.id')->toArray();
    }

    private function getObjectInfo(int $objectId): array
    {
        $object = DB::table('information_object as io')
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

    private function getLevelId(string $levelName): ?int
    {
        $term = DB::table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', 34)
            ->whereRaw('LOWER(term_i18n.name) = ?', [strtolower($levelName)])
            ->first();

        return $term ? $term->id : null;
    }

    private function getSetting(string $feature, string $key, $default = null)
    {
        $setting = DB::table('ahg_ai_settings')
            ->where('feature', $feature)
            ->where('setting_key', $key)
            ->first();

        return $setting ? $setting->setting_value : $default;
    }
}
