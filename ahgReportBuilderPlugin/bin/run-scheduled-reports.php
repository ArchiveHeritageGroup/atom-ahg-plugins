#!/usr/bin/env php
<?php

/**
 * CLI script to run scheduled reports.
 *
 * Usage:
 *   php plugins/ahgReportBuilderPlugin/bin/run-scheduled-reports.php
 *
 * Cron example (run every hour):
 *   0 * * * * cd ' . sfConfig::get('sf_root_dir') . ' && php plugins/ahgReportBuilderPlugin/bin/run-scheduled-reports.php >> /var/log/atom-reports.log 2>&1
 */

// Ensure running from CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Find AtoM root directory
$atomRoot = dirname(dirname(dirname(dirname(__FILE__))));
if (!file_exists($atomRoot . '/config/ProjectConfiguration.class.php')) {
    // Try alternate path
    $atomRoot = sfConfig::get('sf_root_dir');
}

if (!file_exists($atomRoot . '/config/ProjectConfiguration.class.php')) {
    die("Error: Could not find AtoM installation.\n");
}

// Change to AtoM root
chdir($atomRoot);

// Bootstrap Symfony
define('SF_ROOT_DIR', realpath($atomRoot));
define('SF_APP', 'qubit');
define('SF_ENVIRONMENT', 'cli');
define('SF_DEBUG', false);

require_once SF_ROOT_DIR . '/config/ProjectConfiguration.class.php';
$configuration = ProjectConfiguration::getApplicationConfiguration(SF_APP, SF_ENVIRONMENT, SF_DEBUG);
sfContext::createInstance($configuration);

// Load plugin classes
$pluginDir = sfConfig::get('sf_plugins_dir') . '/ahgReportBuilderPlugin/lib';
require_once $pluginDir . '/DataSourceRegistry.php';
require_once $pluginDir . '/ColumnDiscovery.php';
require_once $pluginDir . '/ReportBuilderService.php';
require_once $pluginDir . '/ReportScheduler.php';

// Parse command line options
$options = getopt('', ['help', 'list', 'dry-run', 'schedule:']);

if (isset($options['help'])) {
    echo "AtoM Report Scheduler\n";
    echo "=====================\n\n";
    echo "Usage:\n";
    echo "  php run-scheduled-reports.php [options]\n\n";
    echo "Options:\n";
    echo "  --help         Show this help message\n";
    echo "  --list         List all active schedules\n";
    echo "  --dry-run      Show what would be run without executing\n";
    echo "  --schedule=ID  Run a specific schedule by ID\n\n";
    echo "Cron example (run every hour):\n";
    echo "  0 * * * * cd ' . sfConfig::get('sf_root_dir') . ' && php plugins/ahgReportBuilderPlugin/bin/run-scheduled-reports.php >> /var/log/atom-reports.log 2>&1\n\n";
    exit(0);
}

use Illuminate\Database\Capsule\Manager as DB;

echo "[" . date('Y-m-d H:i:s') . "] Report Scheduler Started\n";

// List schedules
if (isset($options['list'])) {
    $schedules = DB::table('report_schedule as s')
        ->join('custom_report as r', 's.custom_report_id', '=', 'r.id')
        ->select('s.*', 'r.name as report_name')
        ->where('s.is_active', 1)
        ->orderBy('s.next_run')
        ->get();

    if ($schedules->isEmpty()) {
        echo "No active schedules found.\n";
    } else {
        echo "\nActive Schedules:\n";
        echo str_repeat('-', 100) . "\n";
        printf("%-6s %-30s %-12s %-20s %-20s %-10s\n", 'ID', 'Report', 'Frequency', 'Next Run', 'Last Run', 'Format');
        echo str_repeat('-', 100) . "\n";

        foreach ($schedules as $s) {
            printf(
                "%-6d %-30s %-12s %-20s %-20s %-10s\n",
                $s->id,
                substr($s->report_name, 0, 28),
                $s->frequency,
                $s->next_run ?: 'Not set',
                $s->last_run ?: 'Never',
                strtoupper($s->output_format)
            );
        }
        echo str_repeat('-', 100) . "\n";
    }
    exit(0);
}

// Initialize scheduler
$scheduler = new ReportScheduler('en');

// Run specific schedule
if (isset($options['schedule'])) {
    $scheduleId = (int) $options['schedule'];
    $schedule = DB::table('report_schedule as s')
        ->join('custom_report as r', 's.custom_report_id', '=', 'r.id')
        ->select('s.*', 'r.name as report_name', 'r.data_source', 'r.columns', 'r.filters')
        ->where('s.id', $scheduleId)
        ->first();

    if (!$schedule) {
        echo "Error: Schedule #{$scheduleId} not found.\n";
        exit(1);
    }

    if (isset($options['dry-run'])) {
        echo "Dry run: Would execute schedule #{$schedule->id} - {$schedule->report_name}\n";
        exit(0);
    }

    echo "Running schedule #{$schedule->id} - {$schedule->report_name}...\n";

    try {
        $result = $scheduler->runSchedule($schedule);
        echo "Success: Generated {$result['file_path']}\n";
        if ($result['email_sent']) {
            echo "Email sent to recipients.\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }

    exit(0);
}

// Dry run mode
if (isset($options['dry-run'])) {
    $now = new DateTime();
    $dueSchedules = DB::table('report_schedule as s')
        ->join('custom_report as r', 's.custom_report_id', '=', 'r.id')
        ->select('s.*', 'r.name as report_name')
        ->where('s.is_active', 1)
        ->where('s.next_run', '<=', $now->format('Y-m-d H:i:s'))
        ->get();

    if ($dueSchedules->isEmpty()) {
        echo "No schedules due for execution.\n";
    } else {
        echo "Schedules that would be executed:\n";
        foreach ($dueSchedules as $s) {
            echo "  - #{$s->id}: {$s->report_name} (due: {$s->next_run})\n";
        }
    }
    exit(0);
}

// Run all due schedules
$results = $scheduler->runDueReports();

if (empty($results)) {
    echo "[" . date('Y-m-d H:i:s') . "] No schedules due for execution.\n";
} else {
    $success = count(array_filter($results, fn($r) => $r['success']));
    $failed = count($results) - $success;
    echo "[" . date('Y-m-d H:i:s') . "] Completed: {$success} successful, {$failed} failed.\n";
}

echo "[" . date('Y-m-d H:i:s') . "] Report Scheduler Finished\n";
