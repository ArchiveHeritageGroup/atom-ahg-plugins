<?php

/**
 * CLI task for generating embargo reports.
 *
 * Provides various reports on embargo status across the system.
 *
 * Usage:
 *   php symfony embargo:report                    # Summary report
 *   php symfony embargo:report --active           # List active embargoes
 *   php symfony embargo:report --expiring=30      # List embargoes expiring in N days
 *   php symfony embargo:report --lifted           # List recently lifted embargoes
 *   php symfony embargo:report --format=csv       # Export as CSV
 */
class embargoReportTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('active', null, sfCommandOption::PARAMETER_NONE, 'List all active embargoes'),
            new sfCommandOption('expiring', null, sfCommandOption::PARAMETER_OPTIONAL, 'List embargoes expiring in N days'),
            new sfCommandOption('lifted', null, sfCommandOption::PARAMETER_NONE, 'List recently lifted embargoes'),
            new sfCommandOption('expired', null, sfCommandOption::PARAMETER_NONE, 'List expired but not lifted embargoes'),
            new sfCommandOption('format', null, sfCommandOption::PARAMETER_OPTIONAL, 'Output format: table (default) or csv', 'table'),
            new sfCommandOption('days', null, sfCommandOption::PARAMETER_OPTIONAL, 'Days for --lifted report (default 30)', 30),
            new sfCommandOption('output', null, sfCommandOption::PARAMETER_OPTIONAL, 'Output file for CSV export'),
        ]);

        $this->namespace = 'embargo';
        $this->name = 'report';
        $this->briefDescription = 'Generate embargo reports';
        $this->detailedDescription = <<<EOF
The [embargo:report|INFO] task generates various embargo reports.

Without options, displays a summary of embargo statistics.

Examples:
  [php symfony embargo:report|INFO]                        # Summary statistics
  [php symfony embargo:report --active|INFO]               # List all active embargoes
  [php symfony embargo:report --expiring=30|INFO]          # Expiring within 30 days
  [php symfony embargo:report --lifted --days=7|INFO]      # Lifted in last 7 days
  [php symfony embargo:report --expired|INFO]              # Expired but not lifted
  [php symfony embargo:report --active --format=csv|INFO]  # Export active as CSV
  [php symfony embargo:report --active --format=csv --output=/tmp/report.csv|INFO]
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        // Load EmbargoService
        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgExtendedRightsPlugin/lib/Services/EmbargoService.php';

        $format = $options['format'];

        // Determine report type
        if ($options['active']) {
            $this->reportActive($format, $options['output']);
        } elseif ($options['expiring'] !== null) {
            $days = (int) $options['expiring'] ?: 30;
            $this->reportExpiring($days, $format, $options['output']);
        } elseif ($options['lifted']) {
            $days = (int) $options['days'];
            $this->reportLifted($days, $format, $options['output']);
        } elseif ($options['expired']) {
            $this->reportExpired($format, $options['output']);
        } else {
            $this->reportSummary();
        }
    }

    /**
     * Display summary statistics.
     */
    protected function reportSummary(): void
    {
        $this->logSection('embargo', '=== Embargo Summary Report ===');
        $this->logSection('embargo', 'Generated: ' . date('Y-m-d H:i:s'));
        echo "\n";

        $service = new \ahgExtendedRightsPlugin\Services\EmbargoService();
        $stats = $service->getStatistics();

        $this->logSection('stats', "Total embargoes: {$stats['total']}");
        $this->logSection('stats', "Active embargoes: {$stats['active']}");
        echo "\n";

        $this->logSection('stats', 'By type (active):');
        $this->logSection('stats', "  Full access restriction: {$stats['by_type']['full']}");
        $this->logSection('stats', "  Metadata only: {$stats['by_type']['metadata_only']}");
        $this->logSection('stats', "  Digital only: {$stats['by_type']['digital_only']}");
        echo "\n";

        if ($stats['expired_not_lifted'] > 0) {
            $this->logSection('stats', "Expired but not lifted: {$stats['expired_not_lifted']}", null, 'COMMENT');
            $this->logSection('stats', '  Run: php symfony embargo:process --lift-only', null, 'COMMENT');
        }

        // Expiring soon
        $expiringSoon = \Illuminate\Database\Capsule\Manager::table('rights_embargo')
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [date('Y-m-d'), date('Y-m-d', strtotime('+30 days'))])
            ->count();

        if ($expiringSoon > 0) {
            echo "\n";
            $this->logSection('stats', "Expiring in next 30 days: {$expiringSoon}", null, 'INFO');
            $this->logSection('stats', '  Run: php symfony embargo:report --expiring=30', null, 'INFO');
        }

        // Perpetual embargoes
        $perpetual = \Illuminate\Database\Capsule\Manager::table('rights_embargo')
            ->where('status', 'active')
            ->where('auto_release', false)
            ->count();

        if ($perpetual > 0) {
            echo "\n";
            $this->logSection('stats', "Perpetual (no end date): {$perpetual}");
        }
    }

    /**
     * Report active embargoes.
     *
     * @param string      $format Output format
     * @param string|null $output Output file
     */
    protected function reportActive(string $format, ?string $output): void
    {
        $embargoes = \Illuminate\Database\Capsule\Manager::table('rights_embargo as e')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('e.object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('slug', 'e.object_id', '=', 'slug.object_id')
            ->where('e.status', 'active')
            ->select([
                'e.id',
                'e.object_id',
                'ioi.title as object_title',
                'slug.slug',
                'e.embargo_type',
                'e.reason',
                'e.start_date',
                'e.end_date',
                'e.auto_release',
                'e.created_at',
            ])
            ->orderBy('e.end_date')
            ->get();

        $this->outputReport('Active Embargoes', $embargoes, $format, $output, [
            'ID', 'Object ID', 'Title', 'Slug', 'Type', 'Reason', 'Start', 'End', 'Auto Release', 'Created',
        ]);
    }

    /**
     * Report expiring embargoes.
     *
     * @param int         $days   Days until expiry
     * @param string      $format Output format
     * @param string|null $output Output file
     */
    protected function reportExpiring(int $days, string $format, ?string $output): void
    {
        $service = new \ahgExtendedRightsPlugin\Services\EmbargoService();
        $embargoes = $service->getExpiringEmbargoes($days);

        // Transform to expected format
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

        $this->outputReport("Embargoes Expiring in {$days} Days", $data, $format, $output, [
            'ID', 'Object ID', 'Title', 'Slug', 'Type', 'Reason', 'Start', 'End', 'Days Left',
        ]);
    }

    /**
     * Report recently lifted embargoes.
     *
     * @param int         $days   Days to look back
     * @param string      $format Output format
     * @param string|null $output Output file
     */
    protected function reportLifted(int $days, string $format, ?string $output): void
    {
        $cutoff = date('Y-m-d', strtotime("-{$days} days"));

        $embargoes = \Illuminate\Database\Capsule\Manager::table('rights_embargo as e')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('e.object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('slug', 'e.object_id', '=', 'slug.object_id')
            ->leftJoin('user', 'user.id', '=', 'e.lifted_by')
            ->where('e.status', 'lifted')
            ->where('e.lifted_at', '>=', $cutoff)
            ->select([
                'e.id',
                'e.object_id',
                'ioi.title as object_title',
                'slug.slug',
                'e.embargo_type',
                'e.lifted_at',
                'e.lift_reason',
                'user.username as lifted_by',
            ])
            ->orderByDesc('e.lifted_at')
            ->get();

        $this->outputReport("Embargoes Lifted in Last {$days} Days", $embargoes, $format, $output, [
            'ID', 'Object ID', 'Title', 'Slug', 'Type', 'Lifted At', 'Reason', 'Lifted By',
        ]);
    }

    /**
     * Report expired but not lifted embargoes.
     *
     * @param string      $format Output format
     * @param string|null $output Output file
     */
    protected function reportExpired(string $format, ?string $output): void
    {
        $today = date('Y-m-d');

        $embargoes = \Illuminate\Database\Capsule\Manager::table('rights_embargo as e')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('e.object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('slug', 'e.object_id', '=', 'slug.object_id')
            ->where('e.status', 'active')
            ->whereNotNull('e.end_date')
            ->where('e.end_date', '<', $today)
            ->select([
                'e.id',
                'e.object_id',
                'ioi.title as object_title',
                'slug.slug',
                'e.embargo_type',
                'e.reason',
                'e.end_date',
                'e.auto_release',
            ])
            ->orderBy('e.end_date')
            ->get();

        $this->outputReport('Expired Embargoes (Not Lifted)', $embargoes, $format, $output, [
            'ID', 'Object ID', 'Title', 'Slug', 'Type', 'Reason', 'End Date', 'Auto Release',
        ]);

        if ($embargoes->isNotEmpty() && $format === 'table') {
            echo "\n";
            $this->logSection('action', 'To lift these embargoes, run: php symfony embargo:process --lift-only', null, 'INFO');
        }
    }

    /**
     * Output report in requested format.
     *
     * @param string      $title   Report title
     * @param mixed       $data    Report data
     * @param string      $format  Output format
     * @param string|null $output  Output file
     * @param array       $headers Column headers
     */
    protected function outputReport(string $title, $data, string $format, ?string $output, array $headers): void
    {
        if ($format === 'csv') {
            $this->outputCsv($title, $data, $output, $headers);
        } else {
            $this->outputTable($title, $data, $headers);
        }
    }

    /**
     * Output as table.
     *
     * @param string $title   Report title
     * @param mixed  $data    Report data
     * @param array  $headers Column headers
     */
    protected function outputTable(string $title, $data, array $headers): void
    {
        $this->logSection('embargo', "=== {$title} ===");
        $this->logSection('embargo', 'Generated: ' . date('Y-m-d H:i:s'));
        echo "\n";

        if ($data->isEmpty()) {
            $this->logSection('embargo', 'No records found.');

            return;
        }

        $this->logSection('embargo', "Found {$data->count()} records:");
        echo "\n";

        foreach ($data as $row) {
            $row = (array) $row;
            $title = $row['object_title'] ?? $row['slug'] ?? "Object #{$row['object_id']}";
            $this->logSection('item', "#{$row['id']}: {$title}");

            // Output key fields
            $skipFields = ['id', 'object_title'];
            foreach ($row as $key => $value) {
                if (in_array($key, $skipFields)) {
                    continue;
                }
                if ($value === null || $value === '') {
                    continue;
                }
                $label = str_replace('_', ' ', ucfirst($key));
                $this->logSection('item', "  {$label}: {$value}");
            }
            echo "\n";
        }
    }

    /**
     * Output as CSV.
     *
     * @param string      $title   Report title
     * @param mixed       $data    Report data
     * @param string|null $output  Output file
     * @param array       $headers Column headers
     */
    protected function outputCsv(string $title, $data, ?string $output, array $headers): void
    {
        $csv = [];

        // Header row
        $csv[] = $headers;

        // Data rows
        foreach ($data as $row) {
            $csv[] = array_values((array) $row);
        }

        // Convert to CSV string
        $csvString = '';
        foreach ($csv as $line) {
            $csvString .= '"' . implode('","', array_map(function ($v) {
                return str_replace('"', '""', $v ?? '');
            }, $line)) . "\"\n";
        }

        if ($output) {
            file_put_contents($output, $csvString);
            $this->logSection('csv', "Report exported to: {$output}");
            $this->logSection('csv', "Records: {$data->count()}");
        } else {
            echo $csvString;
        }
    }
}
