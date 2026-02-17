<?php

use Illuminate\Database\Capsule\Manager as DB;

require_once dirname(__FILE__) . '/LlmService.php';
require_once dirname(__FILE__) . '/PromptService.php';

/**
 * Description Service
 *
 * Main orchestrator for AI-powered description generation.
 * Gathers context from records, generates suggestions via LLM,
 * and manages the review workflow.
 */
class DescriptionService
{
    private LlmService $llmService;
    private PromptService $promptService;

    /**
     * Get the current user's culture with fallback to 'en'
     */
    private function getCulture(): string
    {
        try {
            return sfContext::getInstance()->getUser()->getCulture() ?: 'en';
        } catch (\Exception $e) {
            return 'en';
        }
    }

    public function __construct()
    {
        $this->llmService = new LlmService();
        $this->promptService = new PromptService();
    }

    /**
     * Generate a description suggestion for an object
     *
     * @param int $objectId Information object ID
     * @param int|null $templateId Prompt template ID or null for auto-select
     * @param int|null $llmConfigId LLM config ID or null for default
     * @param int|null $createdBy User ID who initiated the request
     * @return array ['success' => bool, 'suggestion_id' => int, 'suggested_text' => string, ...]
     */
    public function generateSuggestion(int $objectId, ?int $templateId = null, ?int $llmConfigId = null, ?int $createdBy = null): array
    {
        // Check if we have too many pending suggestions for this object
        $maxPending = $this->getSetting('max_pending_per_object', 3);
        $pendingCount = DB::table('ahg_description_suggestion')
            ->where('object_id', $objectId)
            ->where('status', 'pending')
            ->count();

        if ($pendingCount >= $maxPending) {
            return [
                'success' => false,
                'error' => "Maximum pending suggestions ({$maxPending}) reached for this record. Please review existing suggestions first.",
            ];
        }

        // Gather context from the object
        $context = $this->gatherContext($objectId);
        if (!$context['success']) {
            return $context;
        }

        // Get template
        $template = $this->promptService->getTemplateForObject($objectId, $templateId);
        if (!$template) {
            return [
                'success' => false,
                'error' => 'No prompt template available',
            ];
        }

        // Build prompts
        $prompts = $this->promptService->buildPrompt($template, $context['data']);

        // Generate with LLM
        $result = $this->llmService->complete(
            $prompts['system'],
            $prompts['user'],
            $llmConfigId
        );

        if (!$result['success']) {
            return $result;
        }

        // Save the suggestion
        $suggestionId = $this->saveSuggestion(
            $objectId,
            $result,
            $template,
            $llmConfigId,
            $context,
            $createdBy
        );

        return [
            'success' => true,
            'suggestion_id' => $suggestionId,
            'suggested_text' => $result['text'],
            'existing_text' => $context['data']['scope_and_content'] ?? null,
            'tokens_used' => $result['tokens_used'],
            'model_used' => $result['model'],
            'generation_time_ms' => $result['generation_time_ms'] ?? 0,
            'template_name' => $template->name,
            'has_ocr' => !empty($context['data']['ocr_text']),
        ];
    }

    /**
     * Gather context data for an object
     *
     * @param int $objectId
     * @return array ['success' => bool, 'data' => array]
     */
    public function gatherContext(int $objectId): array
    {
        // Get basic object data
        $object = DB::table('information_object')
            ->where('id', $objectId)
            ->first();

        if (!$object) {
            return [
                'success' => false,
                'error' => 'Information object not found',
            ];
        }

        $culture = $this->getCulture();

        // Get i18n data
        $i18n = DB::table('information_object_i18n')
            ->where('id', $objectId)
            ->where('culture', $culture)
            ->first();

        // Fallback to 'en' if culture has no data
        if (!$i18n && $culture !== 'en') {
            $i18n = DB::table('information_object_i18n')
                ->where('id', $objectId)
                ->where('culture', 'en')
                ->first();
        }

        // Get level of description
        $level = null;
        if ($object->level_of_description_id) {
            $levelTerm = DB::table('term_i18n')
                ->where('id', $object->level_of_description_id)
                ->where('culture', $culture)
                ->first();

            // Fallback to 'en' if culture has no data
            if (!$levelTerm && $culture !== 'en') {
                $levelTerm = DB::table('term_i18n')
                    ->where('id', $object->level_of_description_id)
                    ->where('culture', 'en')
                    ->first();
            }
            $level = $levelTerm->name ?? null;
        }

        // Get repository
        $repository = null;
        if ($object->repository_id) {
            $repoI18n = DB::table('actor_i18n')
                ->where('id', $object->repository_id)
                ->where('culture', $culture)
                ->first();

            // Fallback to 'en' if culture has no data
            if (!$repoI18n && $culture !== 'en') {
                $repoI18n = DB::table('actor_i18n')
                    ->where('id', $object->repository_id)
                    ->where('culture', 'en')
                    ->first();
            }
            $repository = $repoI18n->authorized_form_of_name ?? null;
        }

        // Get creator (from events)
        $creator = null;
        $creatorEvent = DB::table('event')
            ->join('actor_i18n', 'event.actor_id', '=', 'actor_i18n.id')
            ->where('event.object_id', $objectId)
            ->where('event.type_id', 111) // Creation event
            ->where('actor_i18n.culture', $culture)
            ->select('actor_i18n.authorized_form_of_name')
            ->first();

        // Fallback to 'en' if culture has no data
        if (!$creatorEvent && $culture !== 'en') {
            $creatorEvent = DB::table('event')
                ->join('actor_i18n', 'event.actor_id', '=', 'actor_i18n.id')
                ->where('event.object_id', $objectId)
                ->where('event.type_id', 111)
                ->where('actor_i18n.culture', 'en')
                ->select('actor_i18n.authorized_form_of_name')
                ->first();
        }
        if ($creatorEvent) {
            $creator = $creatorEvent->authorized_form_of_name;
        }

        // Get date range from events
        $dateRange = $this->getDateRange($objectId);

        // Get OCR text if available
        $ocrText = $this->getOcrText($objectId);

        // Build context array
        $data = [
            'object_id' => $objectId,
            'title' => $i18n->title ?? 'Untitled',
            'identifier' => $object->identifier ?? '',
            'level_of_description' => $level ?? '',
            'date_range' => $dateRange,
            'creator' => $creator ?? '',
            'repository' => $repository ?? '',
            'repository_id' => $object->repository_id,
            'scope_and_content' => $i18n->scope_and_content ?? '',
            'extent_and_medium' => $i18n->extent_and_medium ?? '',
            'archival_history' => $i18n->archival_history ?? '',
            'arrangement' => $i18n->arrangement ?? '',
            'physical_characteristics' => $i18n->physical_characteristics ?? '',
            'finding_aids' => $i18n->finding_aids ?? '',
            'access_conditions' => $i18n->access_conditions ?? '',
            'reproduction_conditions' => $i18n->reproduction_conditions ?? '',
            'language_of_material' => $i18n->language_of_material ?? '',
            'ocr_text' => $ocrText,
        ];

        return [
            'success' => true,
            'data' => $data,
        ];
    }

    /**
     * Save a suggestion to the database
     */
    public function saveSuggestion(int $objectId, array $result, object $template, ?int $llmConfigId, array $context, ?int $createdBy = null): int
    {
        $expireDays = $this->getSetting('auto_expire_days', 30);
        $expiresAt = $expireDays > 0 ? date('Y-m-d H:i:s', strtotime("+{$expireDays} days")) : null;

        $sourceData = [
            'has_ocr' => !empty($context['data']['ocr_text']),
            'fields' => array_keys(array_filter($context['data'], function ($v) {
                return !empty($v) && $v !== 'Untitled';
            })),
        ];

        return DB::table('ahg_description_suggestion')->insertGetId([
            'object_id' => $objectId,
            'suggested_text' => $result['text'],
            'existing_text' => $context['data']['scope_and_content'] ?? null,
            'prompt_template_id' => $template->id,
            'llm_config_id' => $llmConfigId ?: $this->llmService->getDefaultConfig()->id ?? null,
            'source_data' => json_encode($sourceData),
            'status' => 'pending',
            'generation_time_ms' => $result['generation_time_ms'] ?? 0,
            'tokens_used' => $result['tokens_used'] ?? 0,
            'model_used' => $result['model'] ?? '',
            'created_by' => $createdBy,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Approve a suggestion and save to scope_and_content
     *
     * @param int $suggestionId
     * @param int $userId Reviewer user ID
     * @param string|null $editedText Optional edited version
     * @param string|null $notes Review notes
     * @return array
     */
    public function approveSuggestion(int $suggestionId, int $userId, ?string $editedText = null, ?string $notes = null): array
    {
        $suggestion = $this->getSuggestion($suggestionId);

        if (!$suggestion) {
            return ['success' => false, 'error' => 'Suggestion not found'];
        }

        if ($suggestion->status !== 'pending') {
            return ['success' => false, 'error' => 'Suggestion already processed'];
        }

        // Determine the text to save
        $textToSave = $editedText ?: $suggestion->suggested_text;
        $status = $editedText ? 'edited' : 'approved';

        // Update the suggestion record
        DB::table('ahg_description_suggestion')
            ->where('id', $suggestionId)
            ->update([
                'status' => $status,
                'edited_text' => $editedText,
                'reviewed_by' => $userId,
                'reviewed_at' => date('Y-m-d H:i:s'),
                'review_notes' => $notes,
            ]);

        // Save to scope_and_content
        $saved = $this->saveScopeAndContent($suggestion->object_id, $textToSave);

        if (!$saved) {
            return ['success' => false, 'error' => 'Failed to save scope and content'];
        }

        return [
            'success' => true,
            'status' => $status,
            'object_id' => $suggestion->object_id,
            'text_saved' => $textToSave,
        ];
    }

    /**
     * Reject a suggestion
     *
     * @param int $suggestionId
     * @param int $userId Reviewer user ID
     * @param string|null $notes Rejection reason
     * @return array
     */
    public function rejectSuggestion(int $suggestionId, int $userId, ?string $notes = null): array
    {
        $suggestion = $this->getSuggestion($suggestionId);

        if (!$suggestion) {
            return ['success' => false, 'error' => 'Suggestion not found'];
        }

        if ($suggestion->status !== 'pending') {
            return ['success' => false, 'error' => 'Suggestion already processed'];
        }

        DB::table('ahg_description_suggestion')
            ->where('id', $suggestionId)
            ->update([
                'status' => 'rejected',
                'reviewed_by' => $userId,
                'reviewed_at' => date('Y-m-d H:i:s'),
                'review_notes' => $notes,
            ]);

        return [
            'success' => true,
            'status' => 'rejected',
        ];
    }

    /**
     * Get a single suggestion by ID
     */
    public function getSuggestion(int $suggestionId): ?object
    {
        return DB::table('ahg_description_suggestion')
            ->where('id', $suggestionId)
            ->first();
    }

    /**
     * Get pending suggestions with object details
     *
     * @param int|null $repositoryId Filter by repository
     * @param int $limit Maximum to return
     * @return array
     */
    public function getPendingSuggestions(?int $repositoryId = null, int $limit = 50): array
    {
        $culture = $this->getCulture();

        $query = DB::table('ahg_description_suggestion as s')
            ->join('information_object as io', 's.object_id', '=', 'io.id')
            ->join('slug', 'io.id', '=', 'slug.object_id')
            ->leftJoin('information_object_i18n as ioi', function ($join) use ($culture) {
                $join->on('io.id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('ahg_prompt_template as pt', 's.prompt_template_id', '=', 'pt.id')
            ->leftJoin('ahg_llm_config as lc', 's.llm_config_id', '=', 'lc.id')
            ->where('s.status', 'pending')
            ->select(
                's.id',
                's.object_id',
                's.suggested_text',
                's.existing_text',
                's.tokens_used',
                's.model_used',
                's.generation_time_ms',
                's.created_at',
                's.expires_at',
                'io.identifier',
                'slug.slug',
                'ioi.title',
                'pt.name as template_name',
                'lc.name as llm_config_name'
            )
            ->orderBy('s.created_at', 'desc');

        if ($repositoryId) {
            $query->where('io.repository_id', $repositoryId);
        }

        return $query->limit($limit)->get()->toArray();
    }

    /**
     * Get suggestions for a specific object
     *
     * @param int $objectId
     * @param string|null $status Filter by status
     * @return array
     */
    public function getSuggestionsForObject(int $objectId, ?string $status = null): array
    {
        $query = DB::table('ahg_description_suggestion as s')
            ->leftJoin('ahg_prompt_template as pt', 's.prompt_template_id', '=', 'pt.id')
            ->leftJoin('ahg_llm_config as lc', 's.llm_config_id', '=', 'lc.id')
            ->leftJoin('user as u', 's.reviewed_by', '=', 'u.id')
            ->where('s.object_id', $objectId)
            ->select(
                's.*',
                'pt.name as template_name',
                'lc.name as llm_config_name',
                'u.username as reviewed_by_name'
            )
            ->orderBy('s.created_at', 'desc');

        if ($status) {
            $query->where('s.status', $status);
        }

        return $query->get()->toArray();
    }

    /**
     * Get statistics about suggestions
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $stats = DB::table('ahg_description_suggestion')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'edited' THEN 1 ELSE 0 END) as edited,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(tokens_used) as total_tokens,
                AVG(generation_time_ms) as avg_generation_time
            ")
            ->first();

        return (array) $stats;
    }

    /**
     * Clean up expired suggestions
     *
     * @return int Number deleted
     */
    public function cleanupExpired(): int
    {
        return DB::table('ahg_description_suggestion')
            ->where('status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', date('Y-m-d H:i:s'))
            ->delete();
    }

    /**
     * Get OCR text for an object
     */
    private function getOcrText(int $objectId): string
    {
        // Get digital object ID
        $digitalObject = DB::table('digital_object')
            ->where('object_id', $objectId)
            ->first();

        if (!$digitalObject) {
            return '';
        }

        // Get OCR text from IIIF plugin table
        $ocr = DB::table('iiif_ocr_text')
            ->where('digital_object_id', $digitalObject->id)
            ->first();

        if (!$ocr) {
            return '';
        }

        return $ocr->full_text ?? '';
    }

    /**
     * Get date range from events
     */
    private function getDateRange(int $objectId): string
    {
        $culture = $this->getCulture();

        $events = DB::table('event')
            ->leftJoin('event_i18n', function ($join) use ($culture) {
                $join->on('event.id', '=', 'event_i18n.id')
                     ->where('event_i18n.culture', '=', $culture);
            })
            ->where('event.object_id', $objectId)
            ->whereIn('event.type_id', [111, 118]) // Creation, Accumulation
            ->select('event.start_date', 'event.end_date', 'event_i18n.date')
            ->orderBy('event.start_date')
            ->get();

        if ($events->isEmpty()) {
            return '';
        }

        // Collect dates
        $dates = [];
        foreach ($events as $event) {
            if (!empty($event->date)) {
                $dates[] = $event->date;
            } elseif (!empty($event->start_date)) {
                $start = substr($event->start_date, 0, 4);
                $end = $event->end_date ? substr($event->end_date, 0, 4) : null;
                if ($end && $end !== $start) {
                    $dates[] = "{$start}-{$end}";
                } else {
                    $dates[] = $start;
                }
            }
        }

        return implode('; ', array_unique($dates));
    }

    /**
     * Save text to scope_and_content field
     */
    private function saveScopeAndContent(int $objectId, string $text): bool
    {
        try {
            $culture = $this->getCulture();

            $exists = DB::table('information_object_i18n')
                ->where('id', $objectId)
                ->where('culture', $culture)
                ->exists();

            if ($exists) {
                DB::table('information_object_i18n')
                    ->where('id', $objectId)
                    ->where('culture', $culture)
                    ->update(['scope_and_content' => $text]);
            } else {
                DB::table('information_object_i18n')
                    ->insert([
                        'id' => $objectId,
                        'culture' => $culture,
                        'scope_and_content' => $text,
                    ]);
            }

            return true;
        } catch (Exception $e) {
            error_log('Error saving scope_and_content: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Get a setting value
     */
    private function getSetting(string $key, $default = null)
    {
        $setting = DB::table('ahg_ai_settings')
            ->where('feature', 'suggest')
            ->where('setting_key', $key)
            ->first();

        return $setting ? $setting->setting_value : $default;
    }
}
