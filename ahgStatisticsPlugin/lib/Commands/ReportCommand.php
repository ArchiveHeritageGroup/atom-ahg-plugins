<?php

namespace AtomFramework\Console\Commands\Statistics;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

class ReportCommand extends BaseCommand
{
    protected string $name = 'statistics:report';
    protected string $description = 'Generate statistics reports';
    protected string $detailedDescription = <<<'EOF'
    Generate usage statistics reports in various formats.

    Examples:
      php bin/atom statistics:report                                    # Summary
      php bin/atom statistics:report --type=views                       # Views report
      php bin/atom statistics:report --type=top_items --limit=100       # Top 100 items
      php bin/atom statistics:report --type=views --format=csv --output=/tmp/views.csv
      php bin/atom statistics:report --start=2024-01-01 --end=2024-01-31
    EOF;

    protected function configure(): void
    {
        $this->addOption('type', null, 'Report type: summary, views, downloads, top_items, geographic', 'summary');
        $this->addOption('format', null, 'Output format: text, csv, json', 'text');
        $this->addOption('start', null, 'Start date (YYYY-MM-DD)');
        $this->addOption('end', null, 'End date (YYYY-MM-DD)');
        $this->addOption('output', null, 'Output file path');
        $this->addOption('limit', null, 'Limit results', '50');
    }

    protected function handle(): int
    {
        $pluginDir = $this->getAtomRoot() . '/atom-ahg-plugins/ahgStatisticsPlugin';
        require_once $pluginDir . '/lib/Services/StatisticsService.php';

        $service = new \StatisticsService();

        $startDate = $this->option('start') ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $this->option('end') ?? date('Y-m-d');
        $format = $this->option('format') ?? 'text';
        $type = $this->option('type') ?? 'summary';

        $this->info("Generating {$type} report ({$startDate} to {$endDate})...");

        $data = match ($type) {
            'summary' => $this->getSummaryData($service, $startDate, $endDate),
            'views' => $service->getViewsOverTime($startDate, $endDate),
            'downloads' => $service->getDownloadsOverTime($startDate, $endDate),
            'top_items' => $service->getTopItems('view', (int) ($this->option('limit') ?? 50), $startDate, $endDate),
            'geographic' => $service->getGeographicStats($startDate, $endDate),
            default => $service->getDashboardStats($startDate, $endDate),
        };

        $output = match ($format) {
            'csv' => $this->formatCsv($data, $type),
            'json' => json_encode($data, JSON_PRETTY_PRINT),
            default => $this->formatText($data, $type),
        };

        $outputFile = $this->option('output');
        if ($outputFile) {
            file_put_contents($outputFile, $output);
            $this->success("Report saved to: {$outputFile}");
        } else {
            $this->line($output);
        }

        return 0;
    }

    private function getSummaryData(\StatisticsService $service, string $startDate, string $endDate): array
    {
        $stats = $service->getDashboardStats($startDate, $endDate);

        // Add database info
        $stats['raw_events'] = DB::table('ahg_usage_event')->count();
        $stats['daily_aggregates'] = DB::table('ahg_statistics_daily')->count();
        $stats['monthly_aggregates'] = DB::table('ahg_statistics_monthly')->count();

        return $stats;
    }

    private function formatText(array $data, string $type): string
    {
        $output = "\n";
        $output .= str_repeat('=', 60) . "\n";
        $output .= '  Statistics Report: ' . strtoupper($type) . "\n";
        $output .= str_repeat('=', 60) . "\n\n";

        if ($type === 'summary') {
            foreach ($data as $key => $value) {
                $label = ucwords(str_replace('_', ' ', $key));
                $output .= sprintf("  %-25s %s\n", $label . ':', number_format($value));
            }
        } elseif ($type === 'top_items') {
            $output .= sprintf("  %-6s %-40s %10s %10s\n", 'Rank', 'Title', 'Views', 'Unique');
            $output .= '  ' . str_repeat('-', 70) . "\n";

            $rank = 1;
            foreach ($data as $item) {
                $title = mb_substr($item->title ?? "#{$item->object_id}", 0, 38);
                $output .= sprintf(
                    "  %-6d %-40s %10s %10s\n",
                    $rank++,
                    $title,
                    number_format($item->total),
                    number_format($item->unique_visitors)
                );
            }
        } elseif ($type === 'geographic') {
            $output .= sprintf("  %-4s %-30s %12s %12s\n", 'Code', 'Country', 'Total', 'Unique');
            $output .= '  ' . str_repeat('-', 60) . "\n";

            foreach ($data as $row) {
                $output .= sprintf(
                    "  %-4s %-30s %12s %12s\n",
                    $row->country_code,
                    $row->country_name ?? 'Unknown',
                    number_format($row->total),
                    number_format($row->unique_visitors)
                );
            }
        } else {
            $output .= sprintf("  %-12s %12s %12s\n", 'Period', 'Total', 'Unique');
            $output .= '  ' . str_repeat('-', 40) . "\n";

            foreach ($data as $row) {
                $output .= sprintf(
                    "  %-12s %12s %12s\n",
                    $row->period,
                    number_format($row->total),
                    isset($row->unique_visitors) ? number_format($row->unique_visitors) : '-'
                );
            }
        }

        $output .= "\n";

        return $output;
    }

    private function formatCsv(array $data, string $type): string
    {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');

        // For summary, convert to rows
        if ($type === 'summary') {
            fputcsv($output, ['Metric', 'Value']);
            foreach ($data as $key => $value) {
                fputcsv($output, [$key, $value]);
            }
        } else {
            // Header row
            $firstRow = is_object($data[0]) ? (array) $data[0] : $data[0];
            fputcsv($output, array_keys($firstRow));

            // Data rows
            foreach ($data as $row) {
                fputcsv($output, (array) $row);
            }
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
