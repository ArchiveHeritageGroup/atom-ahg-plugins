<?php

namespace AtomExtensions\Observability\Commands;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * observability:record-queue-depth.
 *
 * Samples the pending-job backlog from the AtoM Heratio queue engine
 * (ahg_queue_job, status='pending') per queue, and sets the
 * atom_queue_depth{queue} gauge.
 *
 * Intended to run on a short cron (e.g. every minute) so the gauge reflects
 * current backlog without intercepting enqueue/dequeue events. The metric is
 * pushed into the same APCu/Redis store the /metrics endpoint reads from, so a
 * subsequent scrape surfaces the value.
 *
 * Failure mode: a missing queue table or a single bad query logs a warning and
 * we keep going - one broken sample never sinks the rest, and never returns a
 * non-zero exit that would spam the scheduler log.
 *
 * Mirrors Heratio's RecordQueueDepthCommand, adapted from Laravel Queue::size()
 * to AtoM's database-backed queue table.
 *
 * @author The Archive and Heritage Group (Pty) Ltd
 * @license GPL-3.0
 */
class RecordQueueDepthCommand extends BaseCommand
{
    protected string $name = 'observability:record-queue-depth';
    protected string $description = 'Sample queue backlog per queue and set the atom_queue_depth gauge';
    protected string $detailedDescription = <<<'EOF'
    Samples pending jobs in ahg_queue_job per queue and records the
    atom_queue_depth{queue} Prometheus gauge into the shared metrics store.

    Examples:
      php bin/atom observability:record-queue-depth
      php bin/atom observability:record-queue-depth --queue=default

    Options:
      --queue=NAME   Sample only the named queue (default: all queues present)
    EOF;

    protected function configure(): void
    {
        $this->addOption('queue', null, 'Sample only the named queue', null);
    }

    protected function handle(): int
    {
        $this->requireServices();

        if (!class_exists('\AtomExtensions\Observability\MetricsRegistry')) {
            $this->error('MetricsRegistry not available - plugin services missing.');

            return 0; // never spam the scheduler with non-zero
        }

        $metrics = \AtomExtensions\Observability\MetricsRegistry::instance();
        $this->info('Storage driver: '.$metrics->driverName());

        $onlyQueue = $this->option('queue');
        $sampled = 0;

        try {
            if (!$this->tableExists('ahg_queue_job')) {
                $this->warning('ahg_queue_job table not present (queue engine not installed) - nothing to sample.');

                return 0;
            }

            $query = DB::table('ahg_queue_job')
                ->where('status', 'pending')
                ->selectRaw('queue, COUNT(*) as depth')
                ->groupBy('queue');

            if ($onlyQueue !== null && $onlyQueue !== '') {
                $query->where('queue', $onlyQueue);
            }

            $rows = $query->get();

            // Ensure a configured/explicit queue still reports 0 (not absent).
            $seen = [];
            foreach ($rows as $row) {
                $queue = (string) ($row->queue ?: 'default');
                $depth = (int) $row->depth;
                $metrics->gauge('queue_depth', 'Pending jobs waiting on the named queue', ['queue'], [$queue], (float) $depth);
                $seen[$queue] = true;
                $this->line(sprintf('  %s = %d', $queue, $depth));
                $sampled++;
            }

            if ($onlyQueue !== null && $onlyQueue !== '' && !isset($seen[$onlyQueue])) {
                $metrics->gauge('queue_depth', 'Pending jobs waiting on the named queue', ['queue'], [$onlyQueue], 0.0);
                $this->line(sprintf('  %s = 0', $onlyQueue));
                $sampled++;
            }
        } catch (\Throwable $e) {
            $this->warning('Queue sampling skipped: '.$e->getMessage());
        }

        $this->success(sprintf('Sampled %d queue(s).', $sampled));

        return 0;
    }

    private function requireServices(): void
    {
        $base = $this->getPluginsRoot().'/ahgObservabilityPlugin/lib/Services/';
        if (!is_dir($base)) {
            $base = $this->getAtomRoot().'/plugins/ahgObservabilityPlugin/lib/Services/';
        }
        foreach ([
            'MetricStorageInterface.php', 'InMemoryStorage.php', 'ApcuStorage.php',
            'RedisStorage.php', 'PrometheusTextRenderer.php', 'MetricsRegistry.php',
        ] as $f) {
            if (is_file($base.$f)) {
                require_once $base.$f;
            }
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
