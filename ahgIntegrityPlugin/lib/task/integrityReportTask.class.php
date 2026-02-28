<?php

use Illuminate\Database\Capsule\Manager as DB;

class integrityReportTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('date-from', null, sfCommandOption::PARAMETER_OPTIONAL, 'Start date (YYYY-MM-DD)'),
            new sfCommandOption('date-to', null, sfCommandOption::PARAMETER_OPTIONAL, 'End date (YYYY-MM-DD)'),
            new sfCommandOption('repository-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Filter by repository'),
            new sfCommandOption('dead-letter', null, sfCommandOption::PARAMETER_NONE, 'Show dead letter queue report'),
            new sfCommandOption('summary', null, sfCommandOption::PARAMETER_NONE, 'Show summary report'),
            new sfCommandOption('format', null, sfCommandOption::PARAMETER_OPTIONAL, 'Output format: text, json, csv', 'text'),
        ]);

        $this->namespace = 'integrity';
        $this->name = 'report';
        $this->briefDescription = 'Generate integrity verification reports';
        $this->detailedDescription = <<<'EOF'
Generate reports on integrity verification results, including run summaries,
outcome breakdowns, and dead letter queue status.

Examples:
  php symfony integrity:report --summary
  php symfony integrity:report --date-from=2026-01-01 --date-to=2026-02-28
  php symfony integrity:report --repository-id=5
  php symfony integrity:report --dead-letter
  php symfony integrity:report --summary --format=json
  php symfony integrity:report --summary --format=csv
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }

        $format = $options['format'] ?? 'text';

        // Dead letter report
        if (!empty($options['dead-letter'])) {
            $this->deadLetterReport($format);

            return;
        }

        // Summary report
        if (!empty($options['summary'])) {
            $this->summaryReport($format);

            return;
        }

        // Filtered ledger report
        $this->ledgerReport($options, $format);
    }

    protected function summaryReport(string $format): void
    {
        require_once dirname(__DIR__) . '/Services/IntegrityService.php';
        $service = new IntegrityService();
        $stats = $service->getDashboardStats();

        $recentRuns = DB::table('integrity_run')
            ->orderByDesc('started_at')
            ->limit(10)
            ->get()
            ->values()
            ->all();

        if ($format === 'json') {
            echo json_encode(['stats' => $stats, 'recent_runs' => $recentRuns], JSON_PRETTY_PRINT) . "\n";

            return;
        }

        if ($format === 'csv') {
            echo "metric,value\n";
            echo "total_master_objects,{$stats['total_master_objects']}\n";
            echo "total_verifications,{$stats['total_verifications']}\n";
            echo "total_passed,{$stats['total_passed']}\n";
            echo "pass_rate,{$stats['pass_rate']}\n";
            echo "open_dead_letters,{$stats['open_dead_letters']}\n";
            echo "schedules_enabled,{$stats['enabled_schedules']}/{$stats['schedule_count']}\n";

            return;
        }

        // Text format
        $this->logSection('report', 'Integrity Verification Summary Report', null, 'INFO');
        $this->logSection('report', str_repeat('=', 50));
        $this->logSection('report', '');
        $this->logSection('report', "  Master digital objects:  {$stats['total_master_objects']}");
        $this->logSection('report', "  Total verifications:     {$stats['total_verifications']}");
        $this->logSection('report', "  Total passed:            {$stats['total_passed']}");
        $this->logSection('report', "  Pass rate:               " . ($stats['pass_rate'] !== null ? $stats['pass_rate'] . '%' : 'N/A'));
        $this->logSection('report', "  Open dead letters:       {$stats['open_dead_letters']}");
        $this->logSection('report', "  Schedules:               {$stats['enabled_schedules']}/{$stats['schedule_count']} enabled");
        $this->logSection('report', '');

        if (!empty($stats['recent_outcomes'])) {
            $this->logSection('report', '  Outcomes (last 30 days):');
            foreach ($stats['recent_outcomes'] as $outcome => $count) {
                $this->logSection('report', "    {$outcome}: {$count}");
            }
            $this->logSection('report', '');
        }

        if (!empty($recentRuns)) {
            $this->logSection('report', '  Recent Runs:');
            foreach ($recentRuns as $run) {
                $this->logSection('report',
                    "    #{$run->id} [{$run->status}] {$run->started_at} — " .
                    "scanned: {$run->objects_scanned}, passed: {$run->objects_passed}, " .
                    "failed: {$run->objects_failed}");
            }
        }
    }

    protected function deadLetterReport(string $format): void
    {
        $entries = DB::table('integrity_dead_letter')
            ->orderByDesc('last_failure_at')
            ->get()
            ->values()
            ->all();

        if ($format === 'json') {
            echo json_encode($entries, JSON_PRETTY_PRINT) . "\n";

            return;
        }

        if ($format === 'csv') {
            echo "id,digital_object_id,failure_type,status,consecutive_failures,first_failure_at,last_failure_at,retry_count\n";
            foreach ($entries as $e) {
                echo "{$e->id},{$e->digital_object_id},{$e->failure_type},{$e->status},{$e->consecutive_failures},{$e->first_failure_at},{$e->last_failure_at},{$e->retry_count}\n";
            }

            return;
        }

        $this->logSection('report', 'Dead Letter Queue Report', null, 'INFO');
        $this->logSection('report', str_repeat('=', 50));
        $this->logSection('report', '');

        $statusCounts = [];
        foreach ($entries as $e) {
            $statusCounts[$e->status] = ($statusCounts[$e->status] ?? 0) + 1;
        }

        $this->logSection('report', '  Summary:');
        foreach ($statusCounts as $status => $count) {
            $this->logSection('report', "    {$status}: {$count}");
        }
        $this->logSection('report', "    Total: " . count($entries));
        $this->logSection('report', '');

        $open = array_filter($entries, fn ($e) => in_array($e->status, ['open', 'acknowledged', 'investigating']));
        if (!empty($open)) {
            $this->logSection('report', '  Active entries:');
            foreach ($open as $e) {
                $this->logSection('report',
                    "    DO#{$e->digital_object_id} [{$e->failure_type}] {$e->status} — " .
                    "{$e->consecutive_failures} failures since {$e->first_failure_at}",
                    null, 'ERROR');
            }
        } else {
            $this->logSection('report', '  No active dead letter entries', null, 'INFO');
        }
    }

    protected function ledgerReport(array $options, string $format): void
    {
        $query = DB::table('integrity_ledger');

        if (!empty($options['date-from'])) {
            $query->where('verified_at', '>=', $options['date-from'] . ' 00:00:00');
        }

        if (!empty($options['date-to'])) {
            $query->where('verified_at', '<=', $options['date-to'] . ' 23:59:59');
        }

        if (!empty($options['repository-id'])) {
            $query->where('repository_id', (int) $options['repository-id']);
        }

        $entries = $query->orderByDesc('verified_at')->limit(500)->get()->values()->all();

        if ($format === 'json') {
            echo json_encode($entries, JSON_PRETTY_PRINT) . "\n";

            return;
        }

        if ($format === 'csv') {
            echo "id,digital_object_id,outcome,algorithm,file_path,verified_at\n";
            foreach ($entries as $e) {
                $path = str_replace(',', ';', $e->file_path ?? '');
                echo "{$e->id},{$e->digital_object_id},{$e->outcome},{$e->algorithm},{$path},{$e->verified_at}\n";
            }

            return;
        }

        $this->logSection('report', 'Integrity Ledger Report', null, 'INFO');
        $this->logSection('report', str_repeat('=', 50));

        // Outcome breakdown
        $outcomes = [];
        foreach ($entries as $e) {
            $outcomes[$e->outcome] = ($outcomes[$e->outcome] ?? 0) + 1;
        }

        $this->logSection('report', '');
        $this->logSection('report', "  Entries: " . count($entries) . " (latest 500)");
        $this->logSection('report', '  Outcomes:');
        foreach ($outcomes as $outcome => $count) {
            $style = $outcome === 'pass' ? 'INFO' : 'ERROR';
            $this->logSection('report', "    {$outcome}: {$count}", null, $style);
        }

        // Show failures
        $failures = array_filter($entries, fn ($e) => $e->outcome !== 'pass');
        if (!empty($failures)) {
            $this->logSection('report', '');
            $this->logSection('report', '  Failures:');
            foreach (array_slice($failures, 0, 20) as $e) {
                $this->logSection('report',
                    "    DO#{$e->digital_object_id} [{$e->outcome}] {$e->verified_at}" .
                    ($e->error_detail ? " — {$e->error_detail}" : ''),
                    null, 'ERROR');
            }
            if (count($failures) > 20) {
                $remaining = count($failures) - 20;
                $this->logSection('report', "    ... and {$remaining} more");
            }
        }
    }
}
