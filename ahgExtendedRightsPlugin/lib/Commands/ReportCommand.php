<?php

namespace AtomFramework\Console\Commands\Embargo;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

class ReportCommand extends BaseCommand
{
    protected string $name = 'embargo:report';
    protected string $description = 'Generate embargo reports';
    protected string $detailedDescription = <<<'EOF'
    Generate various embargo reports. Without options, displays a summary.

    Examples:
      php bin/atom embargo:report                        # Summary statistics
      php bin/atom embargo:report --active               # List all active embargoes
      php bin/atom embargo:report --expiring=30          # Expiring within 30 days
      php bin/atom embargo:report --lifted --days=7      # Lifted in last 7 days
      php bin/atom embargo:report --expired              # Expired but not lifted
      php bin/atom embargo:report --active --format=csv  # Export active as CSV
      php bin/atom embargo:report --active --format=csv --output=/tmp/report.csv
    EOF;

    protected function configure(): void
    {
        $this->addOption('active', null, 'List all active embargoes');
        $this->addOption('expiring', null, 'List embargoes expiring in N days');
        $this->addOption('lifted', null, 'List recently lifted embargoes');
        $this->addOption('expired', null, 'List expired but not lifted embargoes');
        $this->addOption('format', null, 'Output format: table (default) or csv', 'table');
        $this->addOption('days', null, 'Days for --lifted report (default 30)', '30');
        $this->addOption('output', null, 'Output file for CSV export');
    }

    protected function handle(): int
    {
        $pluginDir = $this->getAtomRoot() . '/atom-ahg-plugins/ahgExtendedRightsPlugin';
        require_once $pluginDir . '/lib/Services/EmbargoService.php';

        $format = $this->option('format');

        if ($this->hasOption('active')) {
            return $this->reportActive($format, $this->option('output'));
        }

        $expiring = $this->option('expiring');
        if ($expiring !== null && $expiring !== '1') {
            return $this->reportExpiring((int) $expiring ?: 30, $format, $this->option('output'));
        }

        if ($this->hasOption('lifted')) {
            return $this->reportLifted((int) $this->option('days'), $format, $this->option('output'));
        }

        if ($this->hasOption('expired')) {
            return $this->reportExpired($format, $this->option('output'));
        }

        return $this->reportSummary();
    }

    private function reportSummary(): int
    {
        $this->info('=== Embargo Summary Report ===');
        $this->line('Generated: ' . date('Y-m-d H:i:s'));
        $this->newline();

        $service = new \ahgExtendedRightsPlugin\Services\EmbargoService();
        $stats = $service->getStatistics();

        $this->line("Total embargoes: {$stats['total']}");
        $this->line("Active embargoes: {$stats['active']}");
        $this->newline();

        $this->line('By type (active):');
        $this->line("  Full access restriction: {$stats['by_type']['full']}");
        $this->line("  Metadata only: {$stats['by_type']['metadata_only']}");
        $this->line("  Digital only: {$stats['by_type']['digital_only']}");
        $this->newline();

        if ($stats['expired_not_lifted'] > 0) {
            $this->warning("Expired but not lifted: {$stats['expired_not_lifted']}");
            $this->comment('  Run: php bin/atom embargo:process --lift-only');
        }

        $expiringSoon = DB::table('rights_embargo')
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [date('Y-m-d'), date('Y-m-d', strtotime('+30 days'))])
            ->count();

        if ($expiringSoon > 0) {
            $this->newline();
            $this->info("Expiring in next 30 days: {$expiringSoon}");
            $this->comment('  Run: php bin/atom embargo:report --expiring=30');
        }

        $perpetual = DB::table('rights_embargo')
            ->where('status', 'active')
            ->where('auto_release', false)
            ->count();

        if ($perpetual > 0) {
            $this->newline();
            $this->line("Perpetual (no end date): {$perpetual}");
        }

        return 0;
    }

    private function reportActive(string $format, ?string $output): int
    {
        $embargoes = DB::table('rights_embargo as e')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('e.object_id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('slug', 'e.object_id', '=', 'slug.object_id')
            ->where('e.status', 'active')
            ->select(['e.id', 'e.object_id', 'ioi.title as object_title', 'slug.slug', 'e.embargo_type', 'e.reason', 'e.start_date', 'e.end_date', 'e.auto_release', 'e.created_at'])
            ->orderBy('e.end_date')
            ->get();

        return $this->outputReport('Active Embargoes', $embargoes, $format, $output, ['ID', 'Object ID', 'Title', 'Slug', 'Type', 'Reason', 'Start', 'End', 'Auto Release', 'Created']);
    }

    private function reportExpiring(int $days, string $format, ?string $output): int
    {
        $service = new \ahgExtendedRightsPlugin\Services\EmbargoService();
        $embargoes = $service->getExpiringEmbargoes($days);

        $data = $embargoes->map(function ($e) {
            return (object) [
                'id' => $e->id,
                'object_id' => $e->object_id,
                'object_title' => $e->object_title ?? 'Unknown',
                'slug' => $e->object_slug ?? '',
                'embargo_type' => $e->embargo_type,
                'reason' => $e->reason,
                'start_date' => $e->start_date,
                'end_date' => $e->end_date,
                'days_until_expiry' => (int) ceil((strtotime($e->end_date) - time()) / 86400),
            ];
        });

        return $this->outputReport("Embargoes Expiring in {$days} Days", $data, $format, $output, ['ID', 'Object ID', 'Title', 'Slug', 'Type', 'Reason', 'Start', 'End', 'Days Left']);
    }

    private function reportLifted(int $days, string $format, ?string $output): int
    {
        $cutoff = date('Y-m-d', strtotime("-{$days} days"));

        $embargoes = DB::table('rights_embargo as e')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('e.object_id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('slug', 'e.object_id', '=', 'slug.object_id')
            ->leftJoin('user', 'user.id', '=', 'e.lifted_by')
            ->where('e.status', 'lifted')
            ->where('e.lifted_at', '>=', $cutoff)
            ->select(['e.id', 'e.object_id', 'ioi.title as object_title', 'slug.slug', 'e.embargo_type', 'e.lifted_at', 'e.lift_reason', 'user.username as lifted_by'])
            ->orderByDesc('e.lifted_at')
            ->get();

        return $this->outputReport("Embargoes Lifted in Last {$days} Days", $embargoes, $format, $output, ['ID', 'Object ID', 'Title', 'Slug', 'Type', 'Lifted At', 'Reason', 'Lifted By']);
    }

    private function reportExpired(string $format, ?string $output): int
    {
        $today = date('Y-m-d');

        $embargoes = DB::table('rights_embargo as e')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('e.object_id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('slug', 'e.object_id', '=', 'slug.object_id')
            ->where('e.status', 'active')
            ->whereNotNull('e.end_date')
            ->where('e.end_date', '<', $today)
            ->select(['e.id', 'e.object_id', 'ioi.title as object_title', 'slug.slug', 'e.embargo_type', 'e.reason', 'e.end_date', 'e.auto_release'])
            ->orderBy('e.end_date')
            ->get();

        $result = $this->outputReport('Expired Embargoes (Not Lifted)', $embargoes, $format, $output, ['ID', 'Object ID', 'Title', 'Slug', 'Type', 'Reason', 'End Date', 'Auto Release']);

        if ($embargoes->isNotEmpty() && $format === 'table') {
            $this->newline();
            $this->info('To lift these embargoes, run: php bin/atom embargo:process --lift-only');
        }

        return $result;
    }

    private function outputReport(string $title, $data, string $format, ?string $output, array $headers): int
    {
        if ($format === 'csv') {
            return $this->outputCsv($title, $data, $output, $headers);
        }

        return $this->outputTable($title, $data, $headers);
    }

    private function outputTable(string $title, $data, array $headers): int
    {
        $this->info("=== {$title} ===");
        $this->line('Generated: ' . date('Y-m-d H:i:s'));
        $this->newline();

        if ($data->isEmpty()) {
            $this->line('No records found.');

            return 0;
        }

        $this->line("Found {$data->count()} records:");
        $this->newline();

        foreach ($data as $row) {
            $row = (array) $row;
            $rowTitle = $row['object_title'] ?? $row['slug'] ?? "Object #{$row['object_id']}";
            $this->bold("#{$row['id']}: {$rowTitle}");

            $skipFields = ['id', 'object_title'];
            foreach ($row as $key => $value) {
                if (in_array($key, $skipFields)) {
                    continue;
                }
                if ($value === null || $value === '') {
                    continue;
                }
                $label = str_replace('_', ' ', ucfirst($key));
                $this->line("  {$label}: {$value}");
            }
            $this->newline();
        }

        return 0;
    }

    private function outputCsv(string $title, $data, ?string $output, array $headers): int
    {
        $csvString = '';
        $csvString .= '"' . implode('","', $headers) . "\"\n";

        foreach ($data as $row) {
            $values = array_values((array) $row);
            $csvString .= '"' . implode('","', array_map(function ($v) {
                return str_replace('"', '""', $v ?? '');
            }, $values)) . "\"\n";
        }

        if ($output) {
            file_put_contents($output, $csvString);
            $this->success("Report exported to: {$output}");
            $this->line("Records: {$data->count()}");
        } else {
            $this->line($csvString);
        }

        return 0;
    }
}
