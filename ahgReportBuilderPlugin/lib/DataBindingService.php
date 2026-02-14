<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Data Binding Service for Report Builder.
 *
 * Manages live vs snapshot data binding modes for custom reports.
 * In 'live' mode, data is fetched fresh on each view.
 * In 'snapshot' mode, a cached copy is used until refreshed.
 */
class DataBindingService
{
    /**
     * Resolve data for a report based on its data mode.
     *
     * If the report is in 'snapshot' mode and has snapshot data, returns the
     * decoded snapshot. Otherwise, executes the report for live data.
     *
     * @param object $report The report object (from custom_report)
     *
     * @return array The report data
     */
    public function resolveData(object $report): array
    {
        $dataMode = $report->data_mode ?? 'live';

        if ($dataMode === 'snapshot' && !empty($report->snapshot_data)) {
            $decoded = json_decode($report->snapshot_data, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Fall back to live data via ReportBuilderService
        $service = new ReportBuilderService();

        return $service->executeReport((int) $report->id);
    }

    /**
     * Create a snapshot of the report's current data.
     *
     * Executes the report and stores the result JSON in custom_report.snapshot_data.
     *
     * @param int $reportId The report ID
     *
     * @return bool True if snapshot was created
     *
     * @throws \InvalidArgumentException If the report is not found
     */
    public function createSnapshot(int $reportId): bool
    {
        $report = DB::table('custom_report')->where('id', $reportId)->first();
        if (!$report) {
            throw new \InvalidArgumentException("Report not found: {$reportId}");
        }

        // Execute the report to get current data
        $service = new ReportBuilderService();
        $data = $service->executeReport($reportId);

        $now = date('Y-m-d H:i:s');

        return DB::table('custom_report')
            ->where('id', $reportId)
            ->update([
                'snapshot_data' => json_encode($data),
                'snapshot_at' => $now,
                'data_mode' => 'snapshot',
                'updated_at' => $now,
            ]) > 0;
    }

    /**
     * Clear the snapshot data and switch back to live mode.
     *
     * @param int $reportId The report ID
     *
     * @return bool True if cleared
     */
    public function clearSnapshot(int $reportId): bool
    {
        return DB::table('custom_report')
            ->where('id', $reportId)
            ->update([
                'snapshot_data' => null,
                'snapshot_at' => null,
                'data_mode' => 'live',
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Check if a report's snapshot is stale (older than maxAge).
     *
     * @param object $report The report object
     * @param int    $maxAge Maximum age in seconds before considered stale
     *
     * @return bool True if the snapshot is stale or missing
     */
    public function isStale(object $report, int $maxAge = 3600): bool
    {
        $snapshotAt = $report->snapshot_at ?? null;

        if (empty($snapshotAt)) {
            return true;
        }

        $snapshotTime = strtotime($snapshotAt);
        if ($snapshotTime === false) {
            return true;
        }

        return (time() - $snapshotTime) > $maxAge;
    }
}
