<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Section Service for Report Builder.
 *
 * Manages CRUD operations for report sections, ordering, and clearance checks.
 */
class SectionService
{
    /**
     * Get all sections for a report, ordered by position.
     *
     * @param int      $reportId       The report ID
     * @param int|null $clearanceLevel User's clearance level (null = no filter)
     *
     * @return array The sections
     */
    public function getSections(int $reportId, ?int $clearanceLevel = null): array
    {
        $query = DB::table('report_section')
            ->where('report_id', $reportId)
            ->where('is_visible', 1)
            ->orderBy('position');

        if ($clearanceLevel !== null) {
            $query->where('clearance_level', '<=', $clearanceLevel);
        }

        return $query->get()->map(function ($section) {
            $section->config = json_decode($section->config, true) ?: [];
            return $section;
        })->toArray();
    }

    /**
     * Get a single section by ID.
     *
     * @param int $sectionId The section ID
     *
     * @return object|null The section or null
     */
    public function getSection(int $sectionId): ?object
    {
        $section = DB::table('report_section')->where('id', $sectionId)->first();

        if ($section) {
            $section->config = json_decode($section->config, true) ?: [];
        }

        return $section;
    }

    /**
     * Create a new section.
     *
     * @param array $data The section data
     *
     * @return int The new section ID
     */
    public function create(array $data): int
    {
        $maxPosition = DB::table('report_section')
            ->where('report_id', $data['report_id'])
            ->max('position') ?? -1;

        return DB::table('report_section')->insertGetId([
            'report_id' => $data['report_id'],
            'section_type' => $data['section_type'],
            'title' => $data['title'] ?? null,
            'content' => $data['content'] ?? null,
            'position' => $data['position'] ?? ($maxPosition + 1),
            'config' => json_encode($data['config'] ?? []),
            'clearance_level' => $data['clearance_level'] ?? 0,
            'is_visible' => $data['is_visible'] ?? 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update a section.
     *
     * @param int   $sectionId The section ID
     * @param array $data      The data to update
     *
     * @return bool True if updated
     */
    public function update(int $sectionId, array $data): bool
    {
        $updateData = ['updated_at' => date('Y-m-d H:i:s')];

        $fields = ['title', 'content', 'position', 'clearance_level', 'is_visible', 'section_type'];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (isset($data['config'])) {
            $updateData['config'] = json_encode($data['config']);
        }

        return DB::table('report_section')
            ->where('id', $sectionId)
            ->update($updateData) > 0;
    }

    /**
     * Delete a section.
     *
     * @param int $sectionId The section ID
     *
     * @return bool True if deleted
     */
    public function delete(int $sectionId): bool
    {
        // Also delete related links and attachments
        DB::table('report_link')->where('section_id', $sectionId)->delete();
        DB::table('report_attachment')->where('section_id', $sectionId)->delete();
        DB::table('report_comment')->where('section_id', $sectionId)->delete();

        return DB::table('report_section')
            ->where('id', $sectionId)
            ->delete() > 0;
    }

    /**
     * Reorder sections for a report.
     *
     * @param int   $reportId  The report ID
     * @param array $sectionIds Ordered array of section IDs
     *
     * @return bool True if reordered
     */
    public function reorder(int $reportId, array $sectionIds): bool
    {
        foreach ($sectionIds as $position => $sectionId) {
            DB::table('report_section')
                ->where('id', (int) $sectionId)
                ->where('report_id', $reportId)
                ->update(['position' => $position]);
        }

        return true;
    }

    /**
     * Duplicate all sections from one report to another.
     *
     * @param int $sourceReportId The source report ID
     * @param int $targetReportId The target report ID
     *
     * @return int Number of sections duplicated
     */
    public function duplicateSections(int $sourceReportId, int $targetReportId): int
    {
        $sections = DB::table('report_section')
            ->where('report_id', $sourceReportId)
            ->orderBy('position')
            ->get();

        $count = 0;
        foreach ($sections as $section) {
            DB::table('report_section')->insert([
                'report_id' => $targetReportId,
                'section_type' => $section->section_type,
                'title' => $section->title,
                'content' => $section->content,
                'position' => $section->position,
                'config' => $section->config,
                'clearance_level' => $section->clearance_level,
                'is_visible' => $section->is_visible,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Get section count for a report.
     *
     * @param int $reportId The report ID
     *
     * @return int The count
     */
    public function getCount(int $reportId): int
    {
        return DB::table('report_section')
            ->where('report_id', $reportId)
            ->count();
    }
}
