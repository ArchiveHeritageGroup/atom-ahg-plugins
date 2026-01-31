<?php

/**
 * CLI task for generating statistics reports.
 *
 * Usage:
 *   php symfony statistics:report                     # Show summary
 *   php symfony statistics:report --format=csv        # Export to CSV
 *   php symfony statistics:report --type=views        # Views report
 *   php symfony statistics:report --type=downloads    # Downloads report
 *   php symfony statistics:report --type=top_items    # Top items report
 *   php symfony statistics:report --type=geographic   # Geographic distribution
 */
class statisticsReportTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('type', null, sfCommandOption::PARAMETER_OPTIONAL, 'Report type: summary, views, downloads, top_items, geographic', 'summary'),
            new sfCommandOption('format', null, sfCommandOption::PARAMETER_OPTIONAL, 'Output format: text, csv, json', 'text'),
            new sfCommandOption('start', null, sfCommandOption::PARAMETER_OPTIONAL, 'Start date (YYYY-MM-DD)', null),
            new sfCommandOption('end', null, sfCommandOption::PARAMETER_OPTIONAL, 'End date (YYYY-MM-DD)', null),
            new sfCommandOption('output', null, sfCommandOption::PARAMETER_OPTIONAL, 'Output file path'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Limit results', 50),
        ]);

        $this->namespace = 'statistics';
        $this->name = 'report';
        $this->briefDescription = 'Generate statistics reports';
        $this->detailedDescription = <<<EOF
The [statistics:report|INFO] task generates usage statistics reports.

Examples:
  [php symfony statistics:report|INFO]                                    # Summary
  [php symfony statistics:report --type=views|INFO]                       # Views report
  [php symfony statistics:report --type=top_items --limit=100|INFO]       # Top 100 items
  [php symfony statistics:report --type=views --format=csv --output=/tmp/views.csv|INFO]
  [php symfony statistics:report --start=2024-01-01 --end=2024-01-31|INFO]
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgStatisticsPlugin/lib/Services/StatisticsService.php';

        $service = new StatisticsService();

        $startDate = $options['start'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $options['end'] ?? date('Y-m-d');
        $format = $options['format'] ?? 'text';
        $type = $options['type'] ?? 'summary';

        $this->logSection('report', "Generating {$type} report ({$startDate} to {$endDate})...");

        $data = match ($type) {
            'summary' => $this->getSummaryData($service, $startDate, $endDate),
            'views' => $service->getViewsOverTime($startDate, $endDate),
            'downloads' => $service->getDownloadsOverTime($startDate, $endDate),
            'top_items' => $service->getTopItems('view', (int) ($options['limit'] ?? 50), $startDate, $endDate),
            'geographic' => $service->getGeographicStats($startDate, $endDate),
            default => $service->getDashboardStats($startDate, $endDate),
        };

        $output = match ($format) {
            'csv' => $this->formatCsv($data, $type),
            'json' => json_encode($data, JSON_PRETTY_PRINT),
            default => $this->formatText($data, $type),
        };

        if (!empty($options['output'])) {
            file_put_contents($options['output'], $output);
            $this->logSection('output', "Report saved to: {$options['output']}");
        } else {
            echo $output;
        }
    }

    protected function getSummaryData(StatisticsService $service, string $startDate, string $endDate): array
    {
        $stats = $service->getDashboardStats($startDate, $endDate);

        // Add database info
        $stats['raw_events'] = \Illuminate\Database\Capsule\Manager::table('ahg_usage_event')->count();
        $stats['daily_aggregates'] = \Illuminate\Database\Capsule\Manager::table('ahg_statistics_daily')->count();
        $stats['monthly_aggregates'] = \Illuminate\Database\Capsule\Manager::table('ahg_statistics_monthly')->count();

        return $stats;
    }

    protected function formatText(array $data, string $type): string
    {
        $output = "\n";
        $output .= str_repeat('=', 60) . "\n";
        $output .= "  Statistics Report: " . strtoupper($type) . "\n";
        $output .= str_repeat('=', 60) . "\n\n";

        if ($type === 'summary') {
            foreach ($data as $key => $value) {
                $label = ucwords(str_replace('_', ' ', $key));
                $output .= sprintf("  %-25s %s\n", $label . ':', number_format($value));
            }
        } elseif ($type === 'top_items') {
            $output .= sprintf("  %-6s %-40s %10s %10s\n", 'Rank', 'Title', 'Views', 'Unique');
            $output .= "  " . str_repeat('-', 70) . "\n";

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
            $output .= "  " . str_repeat('-', 60) . "\n";

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
            $output .= "  " . str_repeat('-', 40) . "\n";

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

    protected function formatCsv(array $data, string $type): string
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
