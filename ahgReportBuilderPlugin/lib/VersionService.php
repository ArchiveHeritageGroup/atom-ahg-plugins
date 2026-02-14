<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Version Service for Report Builder.
 *
 * Manages version snapshots, history, and restoration for custom reports.
 */
class VersionService
{
    /**
     * Create a version snapshot for a report.
     *
     * Captures the current state of the report and all its sections.
     *
     * @param int         $reportId      The report ID
     * @param int         $userId        The user creating the version
     * @param string|null $changeSummary A brief summary of changes
     *
     * @return int The new version ID
     *
     * @throws \InvalidArgumentException If the report is not found
     */
    public function createVersion(int $reportId, int $userId, ?string $changeSummary = null): int
    {
        $report = DB::table('custom_report')->where('id', $reportId)->first();
        if (!$report) {
            throw new \InvalidArgumentException("Report not found: {$reportId}");
        }

        $currentVersion = $report->version ?? 1;

        // Read all sections for the report
        $sections = DB::table('report_section')
            ->where('report_id', $reportId)
            ->orderBy('position')
            ->get()
            ->toArray();

        // Build snapshot
        $snapshot = [
            'report' => (array) $report,
            'sections' => array_map(function ($section) {
                return (array) $section;
            }, $sections),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        // Insert version record
        $versionId = DB::table('report_version')->insertGetId([
            'report_id' => $reportId,
            'version_number' => $currentVersion,
            'snapshot' => json_encode($snapshot),
            'change_summary' => $changeSummary,
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Increment the report version counter
        DB::table('custom_report')
            ->where('id', $reportId)
            ->update([
                'version' => $currentVersion + 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return $versionId;
    }

    /**
     * Get all versions for a report.
     *
     * @param int $reportId The report ID
     *
     * @return array The versions ordered by version_number desc
     */
    public function getVersions(int $reportId): array
    {
        return DB::table('report_version as rv')
            ->leftJoin('user as u', 'rv.created_by', '=', 'u.id')
            ->where('rv.report_id', $reportId)
            ->select(
                'rv.id',
                'rv.report_id',
                'rv.version_number',
                'rv.change_summary',
                'rv.created_by',
                'rv.created_at',
                'u.username as creator_name'
            )
            ->orderBy('rv.version_number', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get a single version with decoded snapshot.
     *
     * @param int $versionId The version ID
     *
     * @return object|null The version or null
     */
    public function getVersion(int $versionId): ?object
    {
        $version = DB::table('report_version')
            ->where('id', $versionId)
            ->first();

        if ($version) {
            $version->snapshot = json_decode($version->snapshot, true) ?: [];
        }

        return $version;
    }

    /**
     * Restore a report from a version snapshot.
     *
     * Updates the report fields, deletes existing sections, and re-creates
     * them from the snapshot. Creates a new version noting the restoration.
     *
     * @param int $versionId The version ID to restore
     * @param int $userId    The user performing the restore
     *
     * @return bool True if restored successfully
     *
     * @throws \InvalidArgumentException If the version is not found
     */
    public function restoreVersion(int $versionId, int $userId): bool
    {
        $version = $this->getVersion($versionId);
        if (!$version) {
            throw new \InvalidArgumentException("Version not found: {$versionId}");
        }

        $snapshot = $version->snapshot;
        $reportId = $version->report_id;

        // Verify the report still exists
        $report = DB::table('custom_report')->where('id', $reportId)->first();
        if (!$report) {
            throw new \InvalidArgumentException("Report not found: {$reportId}");
        }

        $now = date('Y-m-d H:i:s');

        // Restore report fields from snapshot
        if (!empty($snapshot['report'])) {
            $reportData = $snapshot['report'];

            $restoreFields = [];
            $allowedFields = [
                'name', 'description', 'is_shared', 'is_public',
                'layout', 'data_source', 'columns', 'filters',
                'charts', 'sort_config', 'status', 'cover_config',
            ];

            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $reportData)) {
                    $restoreFields[$field] = $reportData[$field];
                }
            }

            $restoreFields['updated_at'] = $now;

            DB::table('custom_report')
                ->where('id', $reportId)
                ->update($restoreFields);
        }

        // Delete existing sections
        DB::table('report_section')
            ->where('report_id', $reportId)
            ->delete();

        // Re-create sections from snapshot
        if (!empty($snapshot['sections'])) {
            foreach ($snapshot['sections'] as $sectionData) {
                DB::table('report_section')->insert([
                    'report_id' => $reportId,
                    'section_type' => $sectionData['section_type'] ?? 'narrative',
                    'title' => $sectionData['title'] ?? null,
                    'content' => $sectionData['content'] ?? null,
                    'position' => $sectionData['position'] ?? 0,
                    'config' => $sectionData['config'] ?? json_encode([]),
                    'clearance_level' => $sectionData['clearance_level'] ?? 0,
                    'is_visible' => $sectionData['is_visible'] ?? 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Create a new version documenting the restoration
        $this->createVersion(
            $reportId,
            $userId,
            "Restored from version {$version->version_number}"
        );

        return true;
    }

    /**
     * Compare two versions and return field-level differences.
     *
     * @param int $versionId1 The first version ID
     * @param int $versionId2 The second version ID
     *
     * @return array The differences between the two versions
     *
     * @throws \InvalidArgumentException If either version is not found
     */
    public function compareVersions(int $versionId1, int $versionId2): array
    {
        $version1 = $this->getVersion($versionId1);
        $version2 = $this->getVersion($versionId2);

        if (!$version1) {
            throw new \InvalidArgumentException("Version not found: {$versionId1}");
        }
        if (!$version2) {
            throw new \InvalidArgumentException("Version not found: {$versionId2}");
        }

        $diffs = [
            'version1' => $version1->version_number,
            'version2' => $version2->version_number,
            'report_diffs' => [],
            'section_diffs' => [],
        ];

        // Compare report fields
        $report1 = $version1->snapshot['report'] ?? [];
        $report2 = $version2->snapshot['report'] ?? [];

        $compareFields = [
            'name', 'description', 'status', 'is_shared', 'is_public',
            'layout', 'data_source', 'columns', 'filters', 'charts',
            'sort_config', 'cover_config',
        ];

        foreach ($compareFields as $field) {
            $val1 = $report1[$field] ?? null;
            $val2 = $report2[$field] ?? null;

            if ($val1 !== $val2) {
                $diffs['report_diffs'][] = [
                    'field' => $field,
                    'version1_value' => $val1,
                    'version2_value' => $val2,
                ];
            }
        }

        // Compare sections
        $sections1 = $version1->snapshot['sections'] ?? [];
        $sections2 = $version2->snapshot['sections'] ?? [];

        $sectionCount1 = count($sections1);
        $sectionCount2 = count($sections2);

        if ($sectionCount1 !== $sectionCount2) {
            $diffs['section_diffs'][] = [
                'type' => 'count_change',
                'version1_count' => $sectionCount1,
                'version2_count' => $sectionCount2,
            ];
        }

        // Compare matching sections by position
        $maxSections = max($sectionCount1, $sectionCount2);
        for ($i = 0; $i < $maxSections; $i++) {
            $s1 = $sections1[$i] ?? null;
            $s2 = $sections2[$i] ?? null;

            if ($s1 === null) {
                $diffs['section_diffs'][] = [
                    'type' => 'added',
                    'position' => $i,
                    'section' => $s2,
                ];
            } elseif ($s2 === null) {
                $diffs['section_diffs'][] = [
                    'type' => 'removed',
                    'position' => $i,
                    'section' => $s1,
                ];
            } else {
                $sectionFields = ['section_type', 'title', 'content', 'config', 'clearance_level', 'is_visible'];
                $fieldDiffs = [];

                foreach ($sectionFields as $field) {
                    $v1 = $s1[$field] ?? null;
                    $v2 = $s2[$field] ?? null;
                    if ($v1 !== $v2) {
                        $fieldDiffs[] = [
                            'field' => $field,
                            'version1_value' => $v1,
                            'version2_value' => $v2,
                        ];
                    }
                }

                if (!empty($fieldDiffs)) {
                    $diffs['section_diffs'][] = [
                        'type' => 'modified',
                        'position' => $i,
                        'changes' => $fieldDiffs,
                    ];
                }
            }
        }

        return $diffs;
    }
}
