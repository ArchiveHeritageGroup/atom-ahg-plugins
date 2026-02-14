<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Workflow Service for Report Builder.
 *
 * Manages status transitions and approval workflow for custom reports.
 * Transition map: draft -> in_review -> approved -> published, any -> archived.
 */
class WorkflowService
{
    /**
     * Valid status transitions.
     *
     * @var array
     */
    private array $transitionMap = [
        'draft' => ['in_review', 'archived'],
        'in_review' => ['draft', 'approved', 'archived'],
        'approved' => ['published', 'in_review', 'archived'],
        'published' => ['archived'],
        'archived' => ['draft'],
    ];

    /**
     * Get the current status of a report.
     *
     * @param int $reportId The report ID
     *
     * @return string|null The current status or null if not found
     */
    public function getStatus(int $reportId): ?string
    {
        $report = DB::table('custom_report')
            ->select('status')
            ->where('id', $reportId)
            ->first();

        return $report ? ($report->status ?? 'draft') : null;
    }

    /**
     * Transition a report to a new status.
     *
     * Validates the transition, updates the status, and creates a version snapshot.
     *
     * @param int    $reportId  The report ID
     * @param string $newStatus The new status
     * @param int    $userId    The user performing the transition
     *
     * @return bool True if transitioned successfully
     *
     * @throws \InvalidArgumentException If the transition is invalid
     */
    public function transition(int $reportId, string $newStatus, int $userId): bool
    {
        $currentStatus = $this->getStatus($reportId);
        if ($currentStatus === null) {
            throw new \InvalidArgumentException("Report not found: {$reportId}");
        }

        if (!$this->isValidTransition($currentStatus, $newStatus)) {
            throw new \InvalidArgumentException(
                "Invalid status transition from '{$currentStatus}' to '{$newStatus}'"
            );
        }

        $now = date('Y-m-d H:i:s');

        // Update the report status
        DB::table('custom_report')
            ->where('id', $reportId)
            ->update([
                'status' => $newStatus,
                'updated_at' => $now,
            ]);

        // Create a version snapshot for the transition
        $this->createTransitionVersion($reportId, $userId, $currentStatus, $newStatus);

        return true;
    }

    /**
     * Get valid transitions for the current status.
     *
     * @param string $currentStatus The current status
     *
     * @return array The valid next statuses
     */
    public function getValidTransitions(string $currentStatus): array
    {
        return $this->transitionMap[$currentStatus] ?? [];
    }

    /**
     * Get reports filtered by status.
     *
     * @param string   $status The status to filter by
     * @param int|null $userId Optional user ID filter
     *
     * @return array The matching reports
     */
    public function getReportsByStatus(string $status, ?int $userId = null): array
    {
        $query = DB::table('custom_report')
            ->where('status', $status)
            ->orderBy('updated_at', 'desc');

        if ($userId !== null) {
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhere('is_shared', 1);
            });
        }

        return $query->get()->toArray();
    }

    /**
     * Check if a status transition is valid.
     *
     * @param string $from The current status
     * @param string $to   The target status
     *
     * @return bool True if the transition is valid
     */
    private function isValidTransition(string $from, string $to): bool
    {
        $validTargets = $this->transitionMap[$from] ?? [];

        return in_array($to, $validTargets, true);
    }

    /**
     * Create a version snapshot for a status transition.
     *
     * @param int    $reportId      The report ID
     * @param int    $userId        The user performing the transition
     * @param string $fromStatus    The previous status
     * @param string $toStatus      The new status
     */
    private function createTransitionVersion(int $reportId, int $userId, string $fromStatus, string $toStatus): void
    {
        $report = DB::table('custom_report')->where('id', $reportId)->first();
        if (!$report) {
            return;
        }

        $currentVersion = $report->version ?? 1;

        $sections = DB::table('report_section')
            ->where('report_id', $reportId)
            ->orderBy('position')
            ->get()
            ->toArray();

        $snapshot = [
            'report' => (array) $report,
            'sections' => array_map(function ($s) { return (array) $s; }, $sections),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        DB::table('report_version')->insert([
            'report_id' => $reportId,
            'version_number' => $currentVersion,
            'snapshot' => json_encode($snapshot),
            'change_summary' => "Status changed from '{$fromStatus}' to '{$toStatus}'",
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Increment report version
        DB::table('custom_report')
            ->where('id', $reportId)
            ->update(['version' => $currentVersion + 1]);
    }
}
