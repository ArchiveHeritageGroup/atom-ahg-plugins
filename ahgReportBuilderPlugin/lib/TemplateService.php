<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Template Service for Report Builder.
 *
 * CRUD operations for reusable report structures (report_template table).
 * Templates can be system-level, institution-level, or user-level.
 */
class TemplateService
{
    /**
     * Get templates with optional filters.
     *
     * @param string|null $category Filter by category
     * @param string|null $scope    Filter by scope (system, institution, user)
     *
     * @return array The templates
     */
    public function getTemplates(?string $category = null, ?string $scope = null): array
    {
        $query = DB::table('report_template')
            ->where('is_active', 1)
            ->orderBy('category')
            ->orderBy('name');

        if ($category !== null) {
            $query->where('category', $category);
        }

        if ($scope !== null) {
            $query->where('scope', $scope);
        }

        return $query->get()->map(function ($template) {
            $template->structure = json_decode($template->structure, true) ?: [];

            return $template;
        })->toArray();
    }

    /**
     * Get a single template by ID.
     *
     * @param int $id The template ID
     *
     * @return object|null The template or null
     */
    public function getTemplate(int $id): ?object
    {
        $template = DB::table('report_template')
            ->where('id', $id)
            ->first();

        if ($template) {
            $template->structure = json_decode($template->structure, true) ?: [];
        }

        return $template;
    }

    /**
     * Create a new template.
     *
     * @param array $data The template data
     *
     * @return int The new template ID
     */
    public function create(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        return DB::table('report_template')->insertGetId([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? 'custom',
            'scope' => $data['scope'] ?? 'user',
            'structure' => json_encode($data['structure'] ?? []),
            'created_by' => $data['created_by'] ?? null,
            'repository_id' => $data['repository_id'] ?? null,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Update an existing template.
     *
     * @param int   $id   The template ID
     * @param array $data The data to update
     *
     * @return bool True if updated
     */
    public function update(int $id, array $data): bool
    {
        $updateData = ['updated_at' => date('Y-m-d H:i:s')];

        $fields = ['name', 'description', 'category', 'scope', 'repository_id', 'is_active'];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (isset($data['structure'])) {
            $updateData['structure'] = json_encode($data['structure']);
        }

        return DB::table('report_template')
            ->where('id', $id)
            ->update($updateData) > 0;
    }

    /**
     * Delete a template.
     *
     * @param int $id The template ID
     *
     * @return bool True if deleted
     */
    public function delete(int $id): bool
    {
        return DB::table('report_template')
            ->where('id', $id)
            ->delete() > 0;
    }

    /**
     * Create a template from an existing report.
     *
     * Clones the report's sections into a template structure JSON array.
     *
     * @param int         $reportId The source report ID
     * @param string      $name     The template name
     * @param string      $category The template category
     * @param string      $scope    The template scope (system, institution, user)
     * @param int|null    $userId   The user creating the template
     *
     * @return int The new template ID
     */
    public function createFromReport(int $reportId, string $name, string $category = 'custom', string $scope = 'user', ?int $userId = null): int
    {
        // Read the report
        $report = DB::table('custom_report')->where('id', $reportId)->first();
        if (!$report) {
            throw new \InvalidArgumentException("Report not found: {$reportId}");
        }

        // Read all sections for the report
        $sections = DB::table('report_section')
            ->where('report_id', $reportId)
            ->orderBy('position')
            ->get();

        // Build structure JSON from sections
        $structure = [];
        foreach ($sections as $section) {
            $structure[] = [
                'section_type' => $section->section_type,
                'title' => $section->title,
                'content' => $section->content,
                'position' => $section->position,
                'config' => json_decode($section->config, true) ?: [],
                'clearance_level' => $section->clearance_level,
                'is_visible' => $section->is_visible,
            ];
        }

        return $this->create([
            'name' => $name,
            'description' => "Created from report: {$report->name}",
            'category' => $category,
            'scope' => $scope,
            'structure' => $structure,
            'created_by' => $userId,
            'repository_id' => null,
        ]);
    }

    /**
     * Apply a template structure to an existing report.
     *
     * Creates report_section rows from the template's structure JSON.
     *
     * @param int $templateId The template ID
     * @param int $reportId   The target report ID
     *
     * @return int Number of sections created
     */
    public function applyToReport(int $templateId, int $reportId): int
    {
        $template = $this->getTemplate($templateId);
        if (!$template) {
            throw new \InvalidArgumentException("Template not found: {$templateId}");
        }

        $report = DB::table('custom_report')->where('id', $reportId)->first();
        if (!$report) {
            throw new \InvalidArgumentException("Report not found: {$reportId}");
        }

        $now = date('Y-m-d H:i:s');
        $count = 0;

        foreach ($template->structure as $index => $sectionDef) {
            DB::table('report_section')->insert([
                'report_id' => $reportId,
                'section_type' => $sectionDef['section_type'] ?? 'narrative',
                'title' => $sectionDef['title'] ?? null,
                'content' => $sectionDef['content'] ?? null,
                'position' => $sectionDef['position'] ?? $index,
                'config' => json_encode($sectionDef['config'] ?? []),
                'clearance_level' => $sectionDef['clearance_level'] ?? 0,
                'is_visible' => $sectionDef['is_visible'] ?? 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $count++;
        }

        // Link template to report
        DB::table('custom_report')
            ->where('id', $reportId)
            ->update([
                'template_id' => $templateId,
                'updated_at' => $now,
            ]);

        return $count;
    }

    /**
     * Get pre-built system templates.
     *
     * @return array The system templates
     */
    public function getPreBuilt(): array
    {
        return DB::table('report_template')
            ->where('scope', 'system')
            ->where('is_active', 1)
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(function ($template) {
                $template->structure = json_decode($template->structure, true) ?: [];

                return $template;
            })
            ->toArray();
    }
}
