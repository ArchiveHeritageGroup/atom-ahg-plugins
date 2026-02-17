<?php

namespace ahgFormsPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;

/**
 * FormService - Core service for configurable forms.
 *
 * Handles form template management, field operations,
 * form assignment resolution, and form rendering.
 */
class FormService
{
    // =========================================
    // TEMPLATE OPERATIONS
    // =========================================

    /**
     * Get all form templates.
     *
     * @param string|null $formType Filter by form type
     *
     * @return Collection
     */
    public function getTemplates(?string $formType = null): Collection
    {
        $query = DB::table('ahg_form_template')
            ->where('is_active', 1)
            ->orderBy('name');

        if ($formType) {
            $query->where('form_type', $formType);
        }

        return $query->get();
    }

    /**
     * Get a single template with its fields.
     *
     * @param int $templateId Template ID
     *
     * @return object|null
     */
    public function getTemplate(int $templateId): ?object
    {
        $template = DB::table('ahg_form_template')
            ->where('id', $templateId)
            ->first();

        if (!$template) {
            return null;
        }

        $template->fields = $this->getTemplateFields($templateId);
        $template->config = json_decode($template->config_json ?: '{}');

        return $template;
    }

    /**
     * Get fields for a template.
     *
     * @param int $templateId Template ID
     *
     * @return Collection
     */
    public function getTemplateFields(int $templateId): Collection
    {
        return DB::table('ahg_form_field')
            ->where('template_id', $templateId)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($field) {
                $field->validation_rules = json_decode($field->validation_rules ?: '{}');
                $field->options = json_decode($field->options_json ?: '[]');
                $field->conditional_logic = json_decode($field->conditional_logic ?: 'null');
                $field->label_i18n = json_decode($field->label_i18n ?: '{}');
                $field->help_text_i18n = json_decode($field->help_text_i18n ?: '{}');

                return $field;
            });
    }

    /**
     * Create a new template.
     *
     * @param array $data Template data
     *
     * @return int Template ID
     */
    public function createTemplate(array $data): int
    {
        $userId = $this->getCurrentUserId();

        return DB::table('ahg_form_template')->insertGetId([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'form_type' => $data['form_type'] ?? 'information_object',
            'config_json' => json_encode($data['config'] ?? []),
            'is_default' => $data['is_default'] ?? false,
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update a template.
     *
     * @param int   $templateId Template ID
     * @param array $data       Update data
     *
     * @return bool
     */
    public function updateTemplate(int $templateId, array $data): bool
    {
        $template = DB::table('ahg_form_template')->where('id', $templateId)->first();
        if (!$template || $template->is_system) {
            return false;
        }

        $updateData = ['updated_at' => date('Y-m-d H:i:s')];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }
        if (isset($data['config'])) {
            $updateData['config_json'] = json_encode($data['config']);
        }
        if (isset($data['is_default'])) {
            $updateData['is_default'] = $data['is_default'];
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'];
        }

        // Increment version
        $updateData['version'] = $template->version + 1;

        return DB::table('ahg_form_template')
            ->where('id', $templateId)
            ->update($updateData) > 0;
    }

    /**
     * Delete a template.
     *
     * @param int $templateId Template ID
     *
     * @return bool
     */
    public function deleteTemplate(int $templateId): bool
    {
        $template = DB::table('ahg_form_template')->where('id', $templateId)->first();
        if (!$template || $template->is_system) {
            return false;
        }

        return DB::table('ahg_form_template')
            ->where('id', $templateId)
            ->delete() > 0;
    }

    /**
     * Clone a template.
     *
     * @param int    $templateId  Source template ID
     * @param string $newName     Name for the clone
     *
     * @return int New template ID
     */
    public function cloneTemplate(int $templateId, string $newName): int
    {
        $source = $this->getTemplate($templateId);
        if (!$source) {
            throw new \Exception("Template not found: {$templateId}");
        }

        // Create new template
        $newId = DB::table('ahg_form_template')->insertGetId([
            'name' => $newName,
            'description' => $source->description,
            'form_type' => $source->form_type,
            'config_json' => $source->config_json,
            'is_default' => 0,
            'is_system' => 0,
            'created_by' => $this->getCurrentUserId(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Clone fields
        foreach ($source->fields as $field) {
            $fieldId = DB::table('ahg_form_field')->insertGetId([
                'template_id' => $newId,
                'field_name' => $field->field_name,
                'field_type' => $field->field_type,
                'label' => $field->label,
                'label_i18n' => $field->label_i18n ? json_encode($field->label_i18n) : null,
                'help_text' => $field->help_text,
                'help_text_i18n' => $field->help_text_i18n ? json_encode($field->help_text_i18n) : null,
                'placeholder' => $field->placeholder,
                'default_value' => $field->default_value,
                'validation_rules' => json_encode($field->validation_rules),
                'options_json' => $field->options_json,
                'autocomplete_source' => $field->autocomplete_source,
                'section_name' => $field->section_name,
                'tab_name' => $field->tab_name,
                'sort_order' => $field->sort_order,
                'is_repeatable' => $field->is_repeatable,
                'is_required' => $field->is_required,
                'is_readonly' => $field->is_readonly,
                'is_hidden' => $field->is_hidden,
                'conditional_logic' => $field->conditional_logic ? json_encode($field->conditional_logic) : null,
                'css_class' => $field->css_class,
                'width' => $field->width,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // Clone field mappings
            $mappings = DB::table('ahg_form_field_mapping')
                ->where('field_id', $field->id)
                ->get();

            foreach ($mappings as $mapping) {
                DB::table('ahg_form_field_mapping')->insert([
                    'field_id' => $fieldId,
                    'target_table' => $mapping->target_table,
                    'target_column' => $mapping->target_column,
                    'target_type_id' => $mapping->target_type_id,
                    'transformation' => $mapping->transformation,
                    'transformation_config' => $mapping->transformation_config,
                    'is_i18n' => $mapping->is_i18n,
                    'culture' => $mapping->culture,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        return $newId;
    }

    // =========================================
    // FIELD OPERATIONS
    // =========================================

    /**
     * Add a field to a template.
     *
     * @param int   $templateId Template ID
     * @param array $data       Field data
     *
     * @return int Field ID
     */
    public function addField(int $templateId, array $data): int
    {
        // Get max sort order
        $maxOrder = DB::table('ahg_form_field')
            ->where('template_id', $templateId)
            ->max('sort_order') ?? 0;

        return DB::table('ahg_form_field')->insertGetId([
            'template_id' => $templateId,
            'field_name' => $data['field_name'],
            'field_type' => $data['field_type'] ?? 'text',
            'label' => $data['label'],
            'label_i18n' => isset($data['label_i18n']) ? json_encode($data['label_i18n']) : null,
            'help_text' => $data['help_text'] ?? null,
            'placeholder' => $data['placeholder'] ?? null,
            'default_value' => $data['default_value'] ?? null,
            'validation_rules' => isset($data['validation_rules']) ? json_encode($data['validation_rules']) : null,
            'options_json' => isset($data['options']) ? json_encode($data['options']) : null,
            'autocomplete_source' => $data['autocomplete_source'] ?? null,
            'section_name' => $data['section_name'] ?? null,
            'tab_name' => $data['tab_name'] ?? null,
            'sort_order' => $data['sort_order'] ?? $maxOrder + 1,
            'is_repeatable' => $data['is_repeatable'] ?? false,
            'is_required' => $data['is_required'] ?? false,
            'is_readonly' => $data['is_readonly'] ?? false,
            'is_hidden' => $data['is_hidden'] ?? false,
            'conditional_logic' => isset($data['conditional_logic']) ? json_encode($data['conditional_logic']) : null,
            'css_class' => $data['css_class'] ?? null,
            'width' => $data['width'] ?? 'full',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update a field.
     *
     * @param int   $fieldId Field ID
     * @param array $data    Update data
     *
     * @return bool
     */
    public function updateField(int $fieldId, array $data): bool
    {
        $updateData = ['updated_at' => date('Y-m-d H:i:s')];

        foreach (['field_name', 'field_type', 'label', 'help_text', 'placeholder', 'default_value',
                  'autocomplete_source', 'section_name', 'tab_name', 'sort_order', 'css_class', 'width'] as $key) {
            if (isset($data[$key])) {
                $updateData[$key] = $data[$key];
            }
        }

        foreach (['is_repeatable', 'is_required', 'is_readonly', 'is_hidden'] as $key) {
            if (isset($data[$key])) {
                $updateData[$key] = (bool) $data[$key];
            }
        }

        if (isset($data['validation_rules'])) {
            $updateData['validation_rules'] = json_encode($data['validation_rules']);
        }
        if (isset($data['options'])) {
            $updateData['options_json'] = json_encode($data['options']);
        }
        if (isset($data['conditional_logic'])) {
            $updateData['conditional_logic'] = json_encode($data['conditional_logic']);
        }
        if (isset($data['label_i18n'])) {
            $updateData['label_i18n'] = json_encode($data['label_i18n']);
        }

        return DB::table('ahg_form_field')
            ->where('id', $fieldId)
            ->update($updateData) > 0;
    }

    /**
     * Delete a field.
     *
     * @param int $fieldId Field ID
     *
     * @return bool
     */
    public function deleteField(int $fieldId): bool
    {
        return DB::table('ahg_form_field')
            ->where('id', $fieldId)
            ->delete() > 0;
    }

    /**
     * Reorder fields.
     *
     * @param int   $templateId Template ID
     * @param array $fieldOrder Array of field IDs in new order
     *
     * @return bool
     */
    public function reorderFields(int $templateId, array $fieldOrder): bool
    {
        foreach ($fieldOrder as $index => $fieldId) {
            DB::table('ahg_form_field')
                ->where('id', $fieldId)
                ->where('template_id', $templateId)
                ->update(['sort_order' => $index + 1]);
        }

        return true;
    }

    // =========================================
    // FORM ASSIGNMENT
    // =========================================

    /**
     * Get form assignments.
     *
     * @return Collection
     */
    public function getAssignments(): Collection
    {
        return DB::table('ahg_form_assignment as fa')
            ->join('ahg_form_template as ft', 'ft.id', '=', 'fa.template_id')
            ->leftJoin('repository as r', 'r.id', '=', 'fa.repository_id')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('r.id', '=', 'ai.id')
                    ->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('term_i18n as ti', function ($join) {
                $join->on('fa.level_of_description_id', '=', 'ti.id')
                    ->where('ti.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->select([
                'fa.*',
                'ft.name as template_name',
                'ft.form_type',
                'ai.authorized_form_of_name as repository_name',
                'ti.name as level_name',
            ])
            ->orderBy('fa.priority', 'desc')
            ->get();
    }

    /**
     * Create a form assignment.
     *
     * @param array $data Assignment data
     *
     * @return int Assignment ID
     */
    public function createAssignment(array $data): int
    {
        return DB::table('ahg_form_assignment')->insertGetId([
            'template_id' => $data['template_id'],
            'repository_id' => $data['repository_id'] ?? null,
            'level_of_description_id' => $data['level_of_description_id'] ?? null,
            'collection_id' => $data['collection_id'] ?? null,
            'priority' => $data['priority'] ?? 100,
            'inherit_to_children' => $data['inherit_to_children'] ?? true,
            'is_active' => $data['is_active'] ?? true,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Delete an assignment.
     *
     * @param int $assignmentId Assignment ID
     *
     * @return bool
     */
    public function deleteAssignment(int $assignmentId): bool
    {
        return DB::table('ahg_form_assignment')
            ->where('id', $assignmentId)
            ->delete() > 0;
    }

    /**
     * Resolve the appropriate form template for a context.
     *
     * @param string   $formType     Form type (information_object, accession)
     * @param int|null $repositoryId Repository ID
     * @param int|null $levelId      Level of description term ID
     * @param int|null $parentId     Parent information object ID
     *
     * @return object|null Template or null for default
     */
    public function resolveTemplate(string $formType, ?int $repositoryId = null, ?int $levelId = null, ?int $parentId = null): ?object
    {
        // Build query to find matching assignment
        $query = DB::table('ahg_form_assignment as fa')
            ->join('ahg_form_template as ft', 'ft.id', '=', 'fa.template_id')
            ->where('fa.is_active', 1)
            ->where('ft.is_active', 1)
            ->where('ft.form_type', $formType);

        // Score-based matching
        $assignments = $query->select(['fa.*', 'ft.*'])->get();

        $bestMatch = null;
        $bestScore = -1;

        foreach ($assignments as $assignment) {
            $score = 0;

            // Repository match
            if ($assignment->repository_id !== null) {
                if ($assignment->repository_id == $repositoryId) {
                    $score += 100;
                } else {
                    continue; // Repository mismatch, skip
                }
            }

            // Level match
            if ($assignment->level_of_description_id !== null) {
                if ($assignment->level_of_description_id == $levelId) {
                    $score += 50;
                } else {
                    continue; // Level mismatch, skip
                }
            }

            // Collection/parent match
            if ($assignment->collection_id !== null && $parentId) {
                if ($this->isDescendantOf($parentId, $assignment->collection_id)) {
                    $score += 25;
                }
            }

            // Add priority
            $score += $assignment->priority;

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $assignment;
            }
        }

        if ($bestMatch) {
            return $this->getTemplate($bestMatch->template_id);
        }

        // Return default template
        $default = DB::table('ahg_form_template')
            ->where('form_type', $formType)
            ->where('is_default', 1)
            ->where('is_active', 1)
            ->first();

        return $default ? $this->getTemplate($default->id) : null;
    }

    // =========================================
    // DRAFT & AUTOSAVE
    // =========================================

    /**
     * Save form draft.
     *
     * @param int    $templateId Template ID
     * @param string $objectType Object type
     * @param int|null $objectId Object ID (null for new)
     * @param array  $formData   Form data
     *
     * @return int Draft ID
     */
    public function saveDraft(int $templateId, string $objectType, ?int $objectId, array $formData): int
    {
        $userId = $this->getCurrentUserId();

        // Upsert draft
        $existing = DB::table('ahg_form_draft')
            ->where('template_id', $templateId)
            ->where('object_type', $objectType)
            ->where('object_id', $objectId)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            DB::table('ahg_form_draft')
                ->where('id', $existing->id)
                ->update([
                    'form_data' => json_encode($formData),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            return $existing->id;
        }

        return DB::table('ahg_form_draft')->insertGetId([
            'template_id' => $templateId,
            'object_type' => $objectType,
            'object_id' => $objectId,
            'user_id' => $userId,
            'form_data' => json_encode($formData),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get draft for editing.
     *
     * @param int    $templateId Template ID
     * @param string $objectType Object type
     * @param int|null $objectId Object ID
     *
     * @return object|null
     */
    public function getDraft(int $templateId, string $objectType, ?int $objectId): ?object
    {
        $userId = $this->getCurrentUserId();

        $draft = DB::table('ahg_form_draft')
            ->where('template_id', $templateId)
            ->where('object_type', $objectType)
            ->where('object_id', $objectId)
            ->where('user_id', $userId)
            ->first();

        if ($draft) {
            $draft->form_data = json_decode($draft->form_data, true);
        }

        return $draft;
    }

    /**
     * Delete draft after successful save.
     *
     * @param int    $templateId Template ID
     * @param string $objectType Object type
     * @param int|null $objectId Object ID
     *
     * @return bool
     */
    public function deleteDraft(int $templateId, string $objectType, ?int $objectId): bool
    {
        $userId = $this->getCurrentUserId();

        return DB::table('ahg_form_draft')
            ->where('template_id', $templateId)
            ->where('object_type', $objectType)
            ->where('object_id', $objectId)
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    // =========================================
    // EXPORT / IMPORT
    // =========================================

    /**
     * Export a template to JSON.
     *
     * @param int $templateId Template ID
     *
     * @return array Exportable template data
     */
    public function exportTemplate(int $templateId): array
    {
        $template = $this->getTemplate($templateId);
        if (!$template) {
            throw new \Exception("Template not found: {$templateId}");
        }

        $export = [
            'name' => $template->name,
            'description' => $template->description,
            'form_type' => $template->form_type,
            'config' => $template->config,
            'fields' => [],
        ];

        foreach ($template->fields as $field) {
            $fieldExport = [
                'field_name' => $field->field_name,
                'field_type' => $field->field_type,
                'label' => $field->label,
                'label_i18n' => $field->label_i18n,
                'help_text' => $field->help_text,
                'placeholder' => $field->placeholder,
                'default_value' => $field->default_value,
                'validation_rules' => $field->validation_rules,
                'options' => $field->options,
                'autocomplete_source' => $field->autocomplete_source,
                'section_name' => $field->section_name,
                'tab_name' => $field->tab_name,
                'sort_order' => $field->sort_order,
                'is_repeatable' => $field->is_repeatable,
                'is_required' => $field->is_required,
                'conditional_logic' => $field->conditional_logic,
                'width' => $field->width,
            ];

            // Get mappings
            $mappings = DB::table('ahg_form_field_mapping')
                ->where('field_id', $field->id)
                ->get();

            $fieldExport['mappings'] = $mappings->map(function ($m) {
                return [
                    'target_table' => $m->target_table,
                    'target_column' => $m->target_column,
                    'target_type_id' => $m->target_type_id,
                    'transformation' => $m->transformation,
                    'is_i18n' => $m->is_i18n,
                ];
            })->toArray();

            $export['fields'][] = $fieldExport;
        }

        return $export;
    }

    /**
     * Import a template from JSON.
     *
     * @param array  $data Import data
     * @param string $name Override name
     *
     * @return int New template ID
     */
    public function importTemplate(array $data, ?string $name = null): int
    {
        $templateId = $this->createTemplate([
            'name' => $name ?? $data['name'],
            'description' => $data['description'] ?? null,
            'form_type' => $data['form_type'] ?? 'information_object',
            'config' => $data['config'] ?? [],
        ]);

        foreach ($data['fields'] ?? [] as $fieldData) {
            $fieldId = $this->addField($templateId, $fieldData);

            // Add mappings
            foreach ($fieldData['mappings'] ?? [] as $mapping) {
                DB::table('ahg_form_field_mapping')->insert([
                    'field_id' => $fieldId,
                    'target_table' => $mapping['target_table'],
                    'target_column' => $mapping['target_column'],
                    'target_type_id' => $mapping['target_type_id'] ?? null,
                    'transformation' => $mapping['transformation'] ?? null,
                    'is_i18n' => $mapping['is_i18n'] ?? false,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        return $templateId;
    }

    // =========================================
    // STATISTICS
    // =========================================

    /**
     * Get form statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $templates = DB::table('ahg_form_template')
            ->selectRaw('form_type, COUNT(*) as count')
            ->groupBy('form_type')
            ->pluck('count', 'form_type')
            ->toArray();

        $assignments = DB::table('ahg_form_assignment')
            ->where('is_active', 1)
            ->count();

        $drafts = DB::table('ahg_form_draft')->count();

        $submissions = DB::table('ahg_form_submission_log')
            ->selectRaw('action, COUNT(*) as count')
            ->where('submitted_at', '>=', date('Y-m-d', strtotime('-30 days')))
            ->groupBy('action')
            ->pluck('count', 'action')
            ->toArray();

        return [
            'templates_by_type' => $templates,
            'active_assignments' => $assignments,
            'pending_drafts' => $drafts,
            'submissions_30_days' => $submissions,
        ];
    }

    // =========================================
    // HELPERS
    // =========================================

    /**
     * Check if an object is a descendant of another.
     *
     * @param int $objectId   Object to check
     * @param int $ancestorId Potential ancestor
     *
     * @return bool
     */
    protected function isDescendantOf(int $objectId, int $ancestorId): bool
    {
        $object = DB::table('information_object')
            ->where('id', $objectId)
            ->select(['lft', 'rgt'])
            ->first();

        $ancestor = DB::table('information_object')
            ->where('id', $ancestorId)
            ->select(['lft', 'rgt'])
            ->first();

        if (!$object || !$ancestor) {
            return false;
        }

        return $object->lft > $ancestor->lft && $object->rgt < $ancestor->rgt;
    }

    /**
     * Get current user ID.
     *
     * @return int|null
     */
    protected function getCurrentUserId(): ?int
    {
        if (class_exists('sfContext') && \sfContext::hasInstance()) {
            $user = \sfContext::getInstance()->getUser();
            if ($user && method_exists($user, 'getAttribute')) {
                return $user->getAttribute('user_id');
            }
        }

        return null;
    }
}
