<?php

namespace AtomFramework\Console\Commands\Statistics;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

class AggregateCommand extends BaseCommand
{
    protected string $name = 'statistics:aggregate';
    protected string $description = 'Aggregate usage statistics for reporting';
    protected string $detailedDescription = <<<'EOF'
    Aggregates raw usage events into summary tables.
    This command should be run daily via cron.

    Examples:
      php bin/atom statistics:aggregate --daily              # Aggregate yesterday
      php bin/atom statistics:aggregate --daily --date=2024-01-15
      php bin/atom statistics:aggregate --monthly            # Aggregate last month
      php bin/atom statistics:aggregate --cleanup            # Remove old events
      php bin/atom statistics:aggregate --all                # Run all operations
      php bin/atom statistics:aggregate --backfill=30        # Backfill 30 days

    Cron example (run at 2am daily):
      0 2 * * * cd /usr/share/nginx/archive && php bin/atom statistics:aggregate --all
    EOF;

    protected function configure(): void
    {
        $this->addOption('daily', null, 'Run daily aggregation');
        $this->addOption('monthly', null, 'Run monthly aggregation');
        $this->addOption('cleanup', null, 'Cleanup old events');
        $this->addOption('all', null, 'Run all aggregations');
        $this->addOption('date', null, 'Date for daily aggregation (YYYY-MM-DD)');
        $this->addOption('year', null, 'Year for monthly aggregation');
        $this->addOption('month', null, 'Month for monthly aggregation');
        $this->addOption('days', null, 'Retention days for cleanup');
        $this->addOption('backfill', null, 'Backfill N days of daily aggregation');
    }

    protected function handle(): int
    {
        $pluginDir = $this->getAtomRoot() . '/atom-ahg-plugins/ahgStatisticsPlugin';
        require_once $pluginDir . '/lib/Services/StatisticsService.php';

        $service = new \StatisticsService();
        $runAll = $this->hasOption('all');

        $this->info('Starting statistics aggregation...');

        // Backfill mode
        $backfill = $this->option('backfill');
        if ($backfill) {
            $days = (int) $backfill;
            $this->info("Backfilling {$days} days of daily statistics...");

            for ($i = $days; $i >= 1; $i--) {
                $date = date('Y-m-d', strtotime("-{$i} days"));
                $count = $service->aggregateDaily($date);
                $this->line("  {$date}: {$count} records");
            }

            return 0;
        }

        // Daily aggregation
        if ($runAll || $this->hasOption('daily')) {
            $date = $this->option('date') ?? date('Y-m-d', strtotime('-1 day'));
            $this->info("Aggregating daily statistics for {$date}...");
            $count = $service->aggregateDaily($date);
            $this->success("Created {$count} aggregate records");
        }

        // Monthly aggregation
        if ($runAll || $this->hasOption('monthly')) {
            $year = $this->option('year') ?? (int) date('Y', strtotime('-1 month'));
            $month = $this->option('month') ?? (int) date('n', strtotime('-1 month'));

            $this->info("Aggregating monthly statistics for {$year}-{$month}...");
            $count = $service->aggregateMonthly($year, $month);
            $this->success("Created {$count} aggregate records");
        }

        // Cleanup old events
        if ($runAll || $this->hasOption('cleanup')) {
            $days = $this->option('days') ?? $service->getConfig('retention_days', 90);
            $this->info("Removing events older than {$days} days...");
            $deleted = $service->cleanupOldEvents((int) $days);
            $this->success("Deleted {$deleted} old events");
        }

        $this->success('Aggregation complete.');

        return 0;
    }
}
