<?php

namespace AtomExtensions\Observability\Commands;

use AtomFramework\Console\BaseCommand;

/**
 * observability:emit-textfile.
 *
 * Renders the current metrics registry snapshot to a Prometheus textfile that
 * node_exporter's textfile collector scans (--collector.textfile.directory).
 * This is the pull-free path for hosts where Prometheus cannot reach the AtoM
 * /metrics endpoint directly but can scrape node_exporter.
 *
 * Writes <dir>/atom_observability.prom atomically (write to a .tmp then
 * rename) so node_exporter never reads a half-written file.
 *
 * Graceful fallback: when the directory is missing/unwritable the command
 * reports the problem and exits 0 (so a cron never alarms), and --dry-run
 * prints the payload to stdout instead of writing.
 *
 * Mirrors Heratio's EmitAiComplianceMetricsCommand textfile-write technique,
 * generalised to the whole registry snapshot.
 *
 * @author The Archive and Heritage Group (Pty) Ltd
 * @license GPL-3.0
 */
class EmitTextfileCommand extends BaseCommand
{
    protected string $name = 'observability:emit-textfile';
    protected string $description = 'Write the metrics registry snapshot to a node_exporter textfile';
    protected string $detailedDescription = <<<'EOF'
    Renders the current metrics snapshot to a Prometheus textfile picked up by
    node_exporter's textfile collector.

    Examples:
      php bin/atom observability:emit-textfile
      php bin/atom observability:emit-textfile --dir=/var/lib/node_exporter/textfile_collector
      php bin/atom observability:emit-textfile --dry-run

    Options:
      --dir=PATH   Target textfile directory (default: obs_textfile_dir setting
                   or /var/lib/node_exporter/textfile_collector)
      --dry-run    Print the payload to stdout instead of writing
    EOF;

    protected function configure(): void
    {
        $this->addOption('dir', null, 'Target textfile directory', null);
        $this->addOption('dry-run', null, 'Print instead of writing');
    }

    protected function handle(): int
    {
        $this->requireServices();

        if (!class_exists('\AtomExtensions\Observability\MetricsRegistry')) {
            $this->error('MetricsRegistry not available - plugin services missing.');

            return 0;
        }

        $payload = \AtomExtensions\Observability\MetricsRegistry::instance()->render();

        if ($this->hasOption('dry-run')) {
            $this->line($payload);

            return 0;
        }

        $dir = (string) ($this->option('dir')
            ?: \AtomExtensions\Observability\MetricsRegistry::setting('textfile_dir', '/var/lib/node_exporter/textfile_collector'));

        if ($dir === '') {
            $this->warning('No textfile directory configured.');

            return 0;
        }

        if (!is_dir($dir) && !@mkdir($dir, 0o755, true) && !is_dir($dir)) {
            $this->warning("Textfile directory not present/creatable: {$dir}");

            return 0;
        }

        $target = rtrim($dir, '/').'/atom_observability.prom';
        $tmp = $target.'.'.getmypid().'.tmp';

        if (@file_put_contents($tmp, $payload) === false) {
            $this->warning("Failed to write {$tmp} (check permissions).");

            return 0;
        }

        if (!@rename($tmp, $target)) {
            @unlink($tmp);
            $this->warning("Failed to atomically replace {$target}.");

            return 0;
        }

        $this->success("Wrote metrics snapshot to {$target}");

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
}
