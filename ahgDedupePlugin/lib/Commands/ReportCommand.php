<?php

namespace AtomFramework\Console\Commands\Dedupe;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Generate duplicate detection reports.
 */
class ReportCommand extends BaseCommand
{
    protected string $name = 'dedupe:report';
    protected string $description = 'Generate deduplication reports';
    protected string $detailedDescription = <<<'EOF'
    Generate reports on detected duplicates with various filters and output formats.

    Examples:
      php bin/atom dedupe:report                           Show all pending duplicates
      php bin/atom dedupe:report --status=pending          Filter by status
      php bin/atom dedupe:report --min-score=0.9           High confidence matches only
      php bin/atom dedupe:report --format=csv --output=dupes.csv  Export to CSV
      php bin/atom dedupe:report --format=json             Output as JSON
    EOF;

    protected function configure(): void
    {
        $this->addOption('status', 's', 'Filter by status (pending, confirmed, dismissed, merged)');
        $this->addOption('method', 'm', 'Filter by detection method');
        $this->addOption('min-score', null, 'Minimum similarity score (0.0-1.0)', '0.0');
        $this->addOption('repository', 'r', 'Filter by repository ID');
        $this->addOption('format', null, 'Output format (table, csv, json)', 'table');
        $this->addOption('output', 'o', 'Output file path (for csv/json)');
        $this->addOption('limit', 'l', 'Limit number of results', '100');
    }

    protected function handle(): int
    {
        // Build query
        $query = DB::table('ahg_duplicate_detection as dd')
            ->leftJoin('information_object as io_a', 'dd.record_a_id', '=', 'io_a.id')
            ->leftJoin('information_object as io_b', 'dd.record_b_id', '=', 'io_b.id')
            ->leftJoin('information_object_i18n as ioi_a', function ($join) {
                $join->on('io_a.id', '=', 'ioi_a.id')
                    ->where('ioi_a.culture', '=', 'en');
            })
            ->leftJoin('information_object_i18n as ioi_b', function ($join) {
                $join->on('io_b.id', '=', 'ioi_b.id')
                    ->where('ioi_b.culture', '=', 'en');
            })
            ->select([
                'dd.id',
                'dd.record_a_id',
                'dd.record_b_id',
                'ioi_a.title as title_a',
                'ioi_b.title as title_b',
                'io_a.identifier as identifier_a',
                'io_b.identifier as identifier_b',
                'dd.similarity_score',
                'dd.detection_method',
                'dd.status',
                'dd.detected_at',
                'dd.reviewed_at',
            ]);

        // Apply filters
        $statusFilter = $this->option('status');
        if ($statusFilter) {
            $query->where('dd.status', $statusFilter);
        }

        $methodFilter = $this->option('method');
        if ($methodFilter) {
            $query->where('dd.detection_method', $methodFilter);
        }

        $minScore = $this->option('min-score', '0.0');
        if ($minScore && (float) $minScore > 0) {
            $query->where('dd.similarity_score', '>=', (float) $minScore);
        }

        $repoFilter = $this->option('repository');
        if ($repoFilter) {
            $repoId = (int) $repoFilter;
            $query->where(function ($q) use ($repoId) {
                $q->where('io_a.repository_id', $repoId)
                    ->orWhere('io_b.repository_id', $repoId);
            });
        }

        $query->orderBy('dd.similarity_score', 'desc')
            ->limit((int) $this->option('limit', '100'));

        $results = $query->get();

        if ($results->isEmpty()) {
            $this->info('No duplicates found matching criteria');

            return 0;
        }

        $format = $this->option('format', 'table');
        $outputPath = $this->option('output');

        // Output based on format
        switch ($format) {
            case 'csv':
                $this->outputCsv($results, $outputPath);

                break;

            case 'json':
                $this->outputJson($results, $outputPath);

                break;

            default:
                $this->outputTable($results);
        }

        return 0;
    }

    private function outputTable($results): void
    {
        $this->info(sprintf('Found %d duplicate pairs', $results->count()));
        $this->newline();

        $this->line(
            str_pad('ID', 8)
            . str_pad('Score', 8)
            . str_pad('Method', 20)
            . str_pad('Status', 12)
            . 'Records'
        );
        $this->line(str_repeat('-', 100));

        foreach ($results as $row) {
            $titleA = mb_substr($row->title_a ?? 'Untitled', 0, 30);
            $titleB = mb_substr($row->title_b ?? 'Untitled', 0, 30);

            $this->line(sprintf(
                '%s%s%s%s%s <-> %s',
                str_pad($row->id, 8),
                str_pad(number_format($row->similarity_score, 2), 8),
                str_pad($row->detection_method, 20),
                str_pad($row->status, 12),
                $titleA,
                $titleB
            ));
        }

        $this->newline();

        // Summary by status
        $summary = $results->groupBy('status')->map->count();
        $this->info('Summary by status:');
        foreach ($summary as $status => $count) {
            $this->line("  {$status}: {$count}");
        }
    }

    private function outputCsv($results, ?string $outputPath = null): void
    {
        $headers = [
            'id',
            'record_a_id',
            'record_b_id',
            'title_a',
            'title_b',
            'identifier_a',
            'identifier_b',
            'similarity_score',
            'detection_method',
            'status',
            'detected_at',
            'reviewed_at',
        ];

        $lines = [];
        $lines[] = implode(',', $headers);

        foreach ($results as $row) {
            $values = [];
            foreach ($headers as $header) {
                $value = $row->{$header} ?? '';
                // Escape quotes and wrap in quotes if contains comma
                if (false !== strpos((string) $value, ',') || false !== strpos((string) $value, '"')) {
                    $value = '"' . str_replace('"', '""', (string) $value) . '"';
                }
                $values[] = $value;
            }
            $lines[] = implode(',', $values);
        }

        $csv = implode("\n", $lines);

        if ($outputPath) {
            file_put_contents($outputPath, $csv);
            $this->success("CSV exported to: {$outputPath}");
        } else {
            $this->line($csv);
        }
    }

    private function outputJson($results, ?string $outputPath = null): void
    {
        $data = [
            'generated_at' => date('Y-m-d H:i:s'),
            'total_count' => $results->count(),
            'duplicates' => $results->toArray(),
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($outputPath) {
            file_put_contents($outputPath, $json);
            $this->success("JSON exported to: {$outputPath}");
        } else {
            $this->line($json);
        }
    }
}
