<?php

/**
 * CLI task for aggregating statistics.
 *
 * Usage:
 *   php symfony statistics:aggregate --daily           # Aggregate yesterday's data
 *   php symfony statistics:aggregate --daily --date=2024-01-15
 *   php symfony statistics:aggregate --monthly         # Aggregate last month
 *   php symfony statistics:aggregate --monthly --year=2024 --month=1
 *   php symfony statistics:aggregate --cleanup         # Remove old raw events
 *   php symfony statistics:aggregate --all             # Run all aggregations
 */
class statisticsAggregateTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('daily', null, sfCommandOption::PARAMETER_NONE, 'Run daily aggregation'),
            new sfCommandOption('monthly', null, sfCommandOption::PARAMETER_NONE, 'Run monthly aggregation'),
            new sfCommandOption('cleanup', null, sfCommandOption::PARAMETER_NONE, 'Cleanup old events'),
            new sfCommandOption('all', null, sfCommandOption::PARAMETER_NONE, 'Run all aggregations'),
            new sfCommandOption('date', null, sfCommandOption::PARAMETER_OPTIONAL, 'Date for daily aggregation (YYYY-MM-DD)'),
            new sfCommandOption('year', null, sfCommandOption::PARAMETER_OPTIONAL, 'Year for monthly aggregation'),
            new sfCommandOption('month', null, sfCommandOption::PARAMETER_OPTIONAL, 'Month for monthly aggregation'),
            new sfCommandOption('days', null, sfCommandOption::PARAMETER_OPTIONAL, 'Retention days for cleanup'),
            new sfCommandOption('backfill', null, sfCommandOption::PARAMETER_OPTIONAL, 'Backfill N days of daily aggregation'),
        ]);

        $this->namespace = 'statistics';
        $this->name = 'aggregate';
        $this->briefDescription = 'Aggregate usage statistics for reporting';
        $this->detailedDescription = <<<EOF
The [statistics:aggregate|INFO] task aggregates raw usage events into summary tables.

This command should be run daily via cron.

Examples:
  [php symfony statistics:aggregate --daily|INFO]              # Aggregate yesterday
  [php symfony statistics:aggregate --daily --date=2024-01-15|INFO]  # Specific date
  [php symfony statistics:aggregate --monthly|INFO]            # Aggregate last month
  [php symfony statistics:aggregate --cleanup|INFO]            # Remove old events
  [php symfony statistics:aggregate --all|INFO]                # Run all operations
  [php symfony statistics:aggregate --backfill=30|INFO]        # Backfill 30 days

Cron example (run at 2am daily):
  0 2 * * * cd /usr/share/nginx/archive && php symfony statistics:aggregate --all
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgStatisticsPlugin/lib/Services/StatisticsService.php';

        $service = new StatisticsService();
        $runAll = $options['all'];

        $this->logSection('statistics', 'Starting statistics aggregation...');

        // Backfill mode
        if (!empty($options['backfill'])) {
            $days = (int) $options['backfill'];
            $this->logSection('backfill', "Backfilling {$days} days of daily statistics...");

            for ($i = $days; $i >= 1; $i--) {
                $date = date('Y-m-d', strtotime("-{$i} days"));
                $count = $service->aggregateDaily($date);
                $this->log("  {$date}: {$count} records");
            }
            return;
        }

        // Daily aggregation
        if ($runAll || $options['daily']) {
            $date = $options['date'] ?? date('Y-m-d', strtotime('-1 day'));
            $this->logSection('daily', "Aggregating daily statistics for {$date}...");
            $count = $service->aggregateDaily($date);
            $this->logSection('daily', "Created {$count} aggregate records");
        }

        // Monthly aggregation (typically run on 1st of each month for previous month)
        if ($runAll || $options['monthly']) {
            $year = $options['year'] ?? (int) date('Y', strtotime('-1 month'));
            $month = $options['month'] ?? (int) date('n', strtotime('-1 month'));

            $this->logSection('monthly', "Aggregating monthly statistics for {$year}-{$month}...");
            $count = $service->aggregateMonthly($year, $month);
            $this->logSection('monthly', "Created {$count} aggregate records");
        }

        // Cleanup old events
        if ($runAll || $options['cleanup']) {
            $days = $options['days'] ?? $service->getConfig('retention_days', 90);
            $this->logSection('cleanup', "Removing events older than {$days} days...");
            $deleted = $service->cleanupOldEvents((int) $days);
            $this->logSection('cleanup', "Deleted {$deleted} old events");
        }

        $this->logSection('statistics', 'Aggregation complete.');
    }
}
