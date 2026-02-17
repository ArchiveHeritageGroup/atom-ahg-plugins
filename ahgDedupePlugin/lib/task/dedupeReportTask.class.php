<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * CLI task to generate duplicate detection reports.
 */
class dedupeReportTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('status', null, sfCommandOption::PARAMETER_OPTIONAL, 'Filter by status (pending, confirmed, dismissed, merged)'),
            new sfCommandOption('method', null, sfCommandOption::PARAMETER_OPTIONAL, 'Filter by detection method'),
            new sfCommandOption('min-score', null, sfCommandOption::PARAMETER_OPTIONAL, 'Minimum similarity score (0.0-1.0)', '0.0'),
            new sfCommandOption('repository', null, sfCommandOption::PARAMETER_OPTIONAL, 'Filter by repository ID'),
            new sfCommandOption('format', null, sfCommandOption::PARAMETER_OPTIONAL, 'Output format (table, csv, json)', 'table'),
            new sfCommandOption('output', null, sfCommandOption::PARAMETER_OPTIONAL, 'Output file path (for csv/json)'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Limit number of results', '100'),
        ]);

        $this->namespace = 'dedupe';
        $this->name = 'report';
        $this->briefDescription = 'Generate duplicate detection reports';
        $this->detailedDescription = <<<EOF
Generate reports on detected duplicates with various filters and output formats.

Examples:
  php symfony dedupe:report                           # Show all pending duplicates
  php symfony dedupe:report --status=pending          # Filter by status
  php symfony dedupe:report --min-score=0.9           # High confidence matches only
  php symfony dedupe:report --format=csv --output=dupes.csv  # Export to CSV
  php symfony dedupe:report --format=json             # Output as JSON
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }

        // Build query
        $query = DB::table('ahg_duplicate_detection as dd')
            ->leftJoin('information_object as io_a', 'dd.record_a_id', '=', 'io_a.id')
            ->leftJoin('information_object as io_b', 'dd.record_b_id', '=', 'io_b.id')
            ->leftJoin('information_object_i18n as ioi_a', function ($join) {
                $join->on('io_a.id', '=', 'ioi_a.id')
                    ->where('ioi_a.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('information_object_i18n as ioi_b', function ($join) {
                $join->on('io_b.id', '=', 'ioi_b.id')
                    ->where('ioi_b.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
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
        if ($options['status']) {
            $query->where('dd.status', $options['status']);
        }

        if ($options['method']) {
            $query->where('dd.detection_method', $options['method']);
        }

        if ($options['min-score']) {
            $query->where('dd.similarity_score', '>=', (float) $options['min-score']);
        }

        if ($options['repository']) {
            $repoId = (int) $options['repository'];
            $query->where(function ($q) use ($repoId) {
                $q->where('io_a.repository_id', $repoId)
                    ->orWhere('io_b.repository_id', $repoId);
            });
        }

        $query->orderBy('dd.similarity_score', 'desc')
            ->limit((int) $options['limit']);

        $results = $query->get();

        if ($results->isEmpty()) {
            $this->logSection('dedupe', 'No duplicates found matching criteria');

            return 0;
        }

        // Output based on format
        switch ($options['format']) {
            case 'csv':
                $this->outputCsv($results, $options['output']);

                break;

            case 'json':
                $this->outputJson($results, $options['output']);

                break;

            default:
                $this->outputTable($results);
        }

        return 0;
    }

    protected function outputTable($results)
    {
        $this->logSection('dedupe', sprintf('Found %d duplicate pairs', $results->count()));
        $this->log('');

        $this->log(str_pad('ID', 8) . str_pad('Score', 8) . str_pad('Method', 20) . str_pad('Status', 12) . 'Records');
        $this->log(str_repeat('-', 100));

        foreach ($results as $row) {
            $titleA = mb_substr($row->title_a ?? 'Untitled', 0, 30);
            $titleB = mb_substr($row->title_b ?? 'Untitled', 0, 30);

            $this->log(sprintf(
                '%s%s%s%s%s <-> %s',
                str_pad($row->id, 8),
                str_pad(number_format($row->similarity_score, 2), 8),
                str_pad($row->detection_method, 20),
                str_pad($row->status, 12),
                $titleA,
                $titleB
            ));
        }

        $this->log('');

        // Summary by status
        $summary = $results->groupBy('status')->map->count();
        $this->logSection('dedupe', 'Summary by status:');
        foreach ($summary as $status => $count) {
            $this->log("  {$status}: {$count}");
        }
    }

    protected function outputCsv($results, $outputPath = null)
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
                if (false !== strpos($value, ',') || false !== strpos($value, '"')) {
                    $value = '"' . str_replace('"', '""', $value) . '"';
                }
                $values[] = $value;
            }
            $lines[] = implode(',', $values);
        }

        $csv = implode("\n", $lines);

        if ($outputPath) {
            file_put_contents($outputPath, $csv);
            $this->logSection('dedupe', "CSV exported to: {$outputPath}");
        } else {
            $this->log($csv);
        }
    }

    protected function outputJson($results, $outputPath = null)
    {
        $data = [
            'generated_at' => date('Y-m-d H:i:s'),
            'total_count' => $results->count(),
            'duplicates' => $results->toArray(),
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($outputPath) {
            file_put_contents($outputPath, $json);
            $this->logSection('dedupe', "JSON exported to: {$outputPath}");
        } else {
            $this->log($json);
        }
    }
}
