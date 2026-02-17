<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Prompt Service
 *
 * Manages prompt templates and builds prompts for description generation.
 * Templates support variable substitution with context data.
 */
class PromptService
{
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

    /**
     * Available template variables
     */
    private const TEMPLATE_VARS = [
        '{title}',
        '{identifier}',
        '{level_of_description}',
        '{date_range}',
        '{creator}',
        '{repository}',
        '{extent_and_medium}',
        '{existing_metadata}',
        '{ocr_text}',
        '{ocr_section}',
    ];

    /**
     * Get the appropriate template for an object
     *
     * @param int $objectId Information object ID
     * @param int|null $templateId Specific template ID, or null to auto-select
     * @return object|null
     */
    public function getTemplateForObject(int $objectId, ?int $templateId = null): ?object
    {
        // If specific template requested, return it
        if ($templateId) {
            return $this->getTemplate($templateId);
        }

        // Get object metadata to determine best template
        $object = DB::table('information_object')
            ->where('id', $objectId)
            ->first();

        if (!$object) {
            return $this->getDefaultTemplate();
        }

        // Get level of description
        $level = null;
        if ($object->level_of_description_id) {
            $culture = $this->getCulture();
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
            $level = strtolower($levelTerm->name ?? '');
        }

        // Try to find a template matching the level
        if ($level) {
            $template = DB::table('ahg_prompt_template')
                ->where('is_active', 1)
                ->where('level_of_description', $level)
                ->where(function ($query) use ($object) {
                    $query->whereNull('repository_id')
                          ->orWhere('repository_id', $object->repository_id);
                })
                ->orderByRaw('repository_id IS NULL ASC')
                ->first();

            if ($template) {
                return $template;
            }
        }

        // Fall back to default
        return $this->getDefaultTemplate();
    }

    /**
     * Get a specific template by ID
     *
     * @param int $templateId
     * @return object|null
     */
    public function getTemplate(int $templateId): ?object
    {
        return DB::table('ahg_prompt_template')
            ->where('id', $templateId)
            ->where('is_active', 1)
            ->first();
    }

    /**
     * Get the default template
     *
     * @return object|null
     */
    public function getDefaultTemplate(): ?object
    {
        $template = DB::table('ahg_prompt_template')
            ->where('is_default', 1)
            ->where('is_active', 1)
            ->first();

        if (!$template) {
            $template = DB::table('ahg_prompt_template')
                ->where('is_active', 1)
                ->orderBy('id')
                ->first();
        }

        return $template;
    }

    /**
     * Get all templates
     *
     * @param bool $activeOnly
     * @return array
     */
    public function getTemplates(bool $activeOnly = true): array
    {
        $query = DB::table('ahg_prompt_template');

        if ($activeOnly) {
            $query->where('is_active', 1);
        }

        return $query->orderBy('is_default', 'desc')->orderBy('name')->get()->toArray();
    }

    /**
     * Build the system and user prompts from a template and context
     *
     * @param object $template Template from ahg_prompt_template
     * @param array $context Context data with variable values
     * @return array ['system' => string, 'user' => string]
     */
    public function buildPrompt(object $template, array $context): array
    {
        $userPrompt = $template->user_prompt_template;

        // Replace all template variables
        $userPrompt = $this->replaceVariable($userPrompt, '{title}', $context['title'] ?? 'Untitled');
        $userPrompt = $this->replaceVariable($userPrompt, '{identifier}', $context['identifier'] ?? '');
        $userPrompt = $this->replaceVariable($userPrompt, '{level_of_description}', $context['level_of_description'] ?? '');
        $userPrompt = $this->replaceVariable($userPrompt, '{date_range}', $context['date_range'] ?? '');
        $userPrompt = $this->replaceVariable($userPrompt, '{creator}', $context['creator'] ?? '');
        $userPrompt = $this->replaceVariable($userPrompt, '{repository}', $context['repository'] ?? '');
        $userPrompt = $this->replaceVariable($userPrompt, '{extent_and_medium}', $context['extent_and_medium'] ?? '');

        // Build existing metadata section
        $existingMetadata = $this->buildExistingMetadataSection($context);
        $userPrompt = $this->replaceVariable($userPrompt, '{existing_metadata}', $existingMetadata);

        // Build OCR section
        $ocrSection = '';
        $ocrText = $context['ocr_text'] ?? '';
        if (!empty($ocrText) && $template->include_ocr) {
            // Truncate if too long
            $maxChars = $template->max_ocr_chars ?? 8000;
            if (strlen($ocrText) > $maxChars) {
                $ocrText = substr($ocrText, 0, $maxChars) . "\n\n[OCR text truncated - {$maxChars} characters shown]";
            }
            $ocrSection = "The following OCR text was extracted from the document:\n---\n{$ocrText}\n---";
        }
        $userPrompt = $this->replaceVariable($userPrompt, '{ocr_section}', $ocrSection);
        $userPrompt = $this->replaceVariable($userPrompt, '{ocr_text}', $ocrText);

        // Clean up any remaining empty sections
        $userPrompt = preg_replace('/\n{3,}/', "\n\n", $userPrompt);
        $userPrompt = trim($userPrompt);

        return [
            'system' => $template->system_prompt,
            'user' => $userPrompt,
        ];
    }

    /**
     * Create a new template
     *
     * @param array $data
     * @return int New template ID
     */
    public function createTemplate(array $data): int
    {
        $insert = [
            'name' => $data['name'],
            'slug' => $data['slug'] ?? $this->generateSlug($data['name']),
            'system_prompt' => $data['system_prompt'],
            'user_prompt_template' => $data['user_prompt_template'],
            'level_of_description' => $data['level_of_description'] ?? null,
            'repository_id' => $data['repository_id'] ?? null,
            'is_default' => $data['is_default'] ?? 0,
            'is_active' => $data['is_active'] ?? 1,
            'include_ocr' => $data['include_ocr'] ?? 1,
            'max_ocr_chars' => $data['max_ocr_chars'] ?? 8000,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        // If setting as default, unset other defaults
        if (!empty($data['is_default'])) {
            DB::table('ahg_prompt_template')->update(['is_default' => 0]);
        }

        return DB::table('ahg_prompt_template')->insertGetId($insert);
    }

    /**
     * Update an existing template
     *
     * @param int $templateId
     * @param array $data
     * @return bool
     */
    public function updateTemplate(int $templateId, array $data): bool
    {
        $update = [];

        $fields = [
            'name', 'system_prompt', 'user_prompt_template', 'level_of_description',
            'repository_id', 'is_active', 'include_ocr', 'max_ocr_chars',
        ];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $update[$field] = $data[$field];
            }
        }

        // Handle default flag
        if (!empty($data['is_default'])) {
            DB::table('ahg_prompt_template')->update(['is_default' => 0]);
            $update['is_default'] = 1;
        }

        if (empty($update)) {
            return true;
        }

        $update['updated_at'] = date('Y-m-d H:i:s');

        return DB::table('ahg_prompt_template')
            ->where('id', $templateId)
            ->update($update) >= 0;
    }

    /**
     * Delete a template
     *
     * @param int $templateId
     * @return bool
     */
    public function deleteTemplate(int $templateId): bool
    {
        return DB::table('ahg_prompt_template')
            ->where('id', $templateId)
            ->delete() > 0;
    }

    /**
     * Get available template variables with descriptions
     *
     * @return array
     */
    public function getTemplateVariables(): array
    {
        return [
            '{title}' => 'Record title',
            '{identifier}' => 'Reference code/identifier',
            '{level_of_description}' => 'Level (fonds, series, file, item)',
            '{date_range}' => 'Date or date range',
            '{creator}' => 'Creator name',
            '{repository}' => 'Repository name',
            '{extent_and_medium}' => 'Physical extent and medium',
            '{existing_metadata}' => 'All available metadata fields',
            '{ocr_text}' => 'Raw OCR text (if available)',
            '{ocr_section}' => 'OCR text with header (if available)',
        ];
    }

    /**
     * Replace a template variable
     */
    private function replaceVariable(string $text, string $variable, string $value): string
    {
        if (empty($value)) {
            // Remove the line if the value is empty
            $pattern = '/^.*' . preg_quote($variable, '/') . '.*\n?/m';

            return preg_replace($pattern, '', $text);
        }

        return str_replace($variable, $value, $text);
    }

    /**
     * Build the existing metadata section from context
     */
    private function buildExistingMetadataSection(array $context): string
    {
        $parts = [];

        $fields = [
            'archival_history' => 'Archival History',
            'arrangement' => 'Arrangement',
            'physical_characteristics' => 'Physical Characteristics',
            'finding_aids' => 'Finding Aids',
            'access_conditions' => 'Access Conditions',
            'reproduction_conditions' => 'Reproduction Conditions',
            'language_of_material' => 'Language of Material',
        ];

        foreach ($fields as $key => $label) {
            if (!empty($context[$key])) {
                $parts[] = "{$label}:\n" . $context[$key];
            }
        }

        if (empty($parts)) {
            return '';
        }

        return "Additional Metadata:\n" . implode("\n\n", $parts);
    }

    /**
     * Generate a URL-safe slug from a name
     */
    private function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        // Ensure uniqueness
        $baseSlug = $slug;
        $counter = 1;
        while (DB::table('ahg_prompt_template')->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
