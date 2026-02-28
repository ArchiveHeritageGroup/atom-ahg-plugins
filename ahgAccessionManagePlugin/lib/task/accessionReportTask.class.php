<?php

class accessionReportTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('status', null, sfCommandOption::PARAMETER_NONE, 'Summary stats'),
            new sfCommandOption('valuation', null, sfCommandOption::PARAMETER_NONE, 'Portfolio valuation report'),
            new sfCommandOption('export-csv', null, sfCommandOption::PARAMETER_NONE, 'Export accessions with V2 fields to CSV'),
            new sfCommandOption('repository', null, sfCommandOption::PARAMETER_REQUIRED, 'Filter by repository ID'),
            new sfCommandOption('date-from', null, sfCommandOption::PARAMETER_REQUIRED, 'Filter from date (YYYY-MM-DD)'),
            new sfCommandOption('date-to', null, sfCommandOption::PARAMETER_REQUIRED, 'Filter to date (YYYY-MM-DD)'),
        ]);

        $this->namespace = 'accession';
        $this->name = 'report';
        $this->briefDescription = 'Accession reports and exports';
        $this->detailedDescription = <<<'EOF'
The [accession:report|INFO] task generates accession reports.

  [php symfony accession:report --status|INFO]              Summary stats
  [php symfony accession:report --valuation|INFO]           Portfolio valuation report
  [php symfony accession:report --export-csv|INFO]          Export accessions to CSV
  [php symfony accession:report --export-csv --date-from=2024-01-01|INFO]  Export with date filter
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        // Bootstrap Laravel Query Builder
        $frameworkBootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($frameworkBootstrap)) {
            require_once $frameworkBootstrap;
        }

        $pluginDir = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgAccessionManagePlugin';
        require_once $pluginDir . '/lib/Services/AccessionIntakeService.php';
        require_once $pluginDir . '/lib/Services/AccessionAppraisalService.php';
        require_once $pluginDir . '/lib/Services/AccessionContainerService.php';
        require_once $pluginDir . '/lib/Services/AccessionCrudService.php';

        // --status
        if ($options['status']) {
            $this->showStatusReport();

            return;
        }

        // --valuation
        if ($options['valuation']) {
            $this->showValuationReport($options);

            return;
        }

        // --export-csv
        if ($options['export-csv']) {
            $this->exportCsv($options);

            return;
        }

        $this->logSection('accession', 'Use --status, --valuation, or --export-csv');
    }

    protected function showStatusReport()
    {
        $db = \Illuminate\Database\Capsule\Manager::class;

        $this->logSection('report', 'Accession Status Report');
        $this->logSection('report', str_repeat('=', 50));

        // Total accessions
        $total = $db::table('accession')->count();
        $this->logSection('report', sprintf('Total accessions: %d', $total));

        // By V2 status
        try {
            $byStatus = $db::table('accession_v2')
                ->selectRaw('status, COUNT(*) as cnt')
                ->groupBy('status')
                ->pluck('cnt', 'status')
                ->all();

            if (!empty($byStatus)) {
                $this->logSection('report', '');
                $this->logSection('report', 'By Intake Status:');
                foreach ($byStatus as $status => $count) {
                    $this->logSection('report', sprintf('  %-20s %d', $status, $count));
                }
            }

            // Queue depth
            $intakeService = new \AhgAccessionManage\Services\AccessionIntakeService();
            $stats = $intakeService->getQueueStats();
            $this->logSection('report', '');
            $this->logSection('report', sprintf('Queue depth (submitted+review): %d',
                ($stats['byStatus']['submitted'] ?? 0) + ($stats['byStatus']['under_review'] ?? 0)
            ));
            if ($stats['avgTimeToAcceptHours'] !== null) {
                $this->logSection('report', sprintf('Avg processing time: %.1f hours', $stats['avgTimeToAcceptHours']));
            }
            $this->logSection('report', sprintf('Overdue (>7 days): %d', $stats['overdue']));
        } catch (\Exception $e) {
            $this->logSection('report', 'V2 tables not installed — showing base data only', null, 'COMMENT');
        }

        // Recent accessions
        $recent = $db::table('accession as a')
            ->leftJoin('accession_i18n as ai', function ($j) {
                $j->on('a.id', '=', 'ai.id')
                    ->where('ai.culture', '=', 'en');
            })
            ->select('a.identifier', 'ai.title', 'a.date', 'a.created_at')
            ->orderBy('a.created_at', 'desc')
            ->limit(10)
            ->get();

        $this->logSection('report', '');
        $this->logSection('report', 'Recent Accessions (last 10):');
        $this->logSection('report', sprintf('  %-15s %-35s %-12s', 'Identifier', 'Title', 'Date'));
        $this->logSection('report', '  ' . str_repeat('-', 65));
        foreach ($recent as $r) {
            $this->logSection('report', sprintf(
                '  %-15s %-35s %-12s',
                $r->identifier ?? '',
                mb_substr($r->title ?? '', 0, 33),
                $r->date ?? ''
            ));
        }
    }

    protected function showValuationReport(array $options)
    {
        $this->logSection('report', 'Portfolio Valuation Report');
        $this->logSection('report', str_repeat('=', 50));

        try {
            $service = new \AhgAccessionManage\Services\AccessionAppraisalService();
            $report = $service->getValuationReport([
                'repository_id' => $options['repository'] ?? null,
            ]);

            $this->logSection('report', sprintf('Valued accessions: %d', $report['accession_count']));
            $this->logSection('report', '');

            if (!empty($report['by_currency'])) {
                $this->logSection('report', 'Total Value by Currency:');
                foreach ($report['by_currency'] as $cur => $val) {
                    $this->logSection('report', sprintf('  %s %s', $cur, number_format($val, 2)));
                }
            }

            if (!empty($report['by_type'])) {
                $this->logSection('report', '');
                $this->logSection('report', 'Valuations by Type:');
                foreach ($report['by_type'] as $type => $count) {
                    $this->logSection('report', sprintf('  %-20s %d', $type, $count));
                }
            }
        } catch (\Exception $e) {
            $this->logSection('report', 'Valuation tables not installed', null, 'ERROR');
        }
    }

    protected function exportCsv(array $options)
    {
        $db = \Illuminate\Database\Capsule\Manager::class;

        $query = $db::table('accession as a')
            ->leftJoin('accession_i18n as ai', function ($j) {
                $j->on('a.id', '=', 'ai.id')
                    ->where('ai.culture', '=', 'en');
            })
            ->leftJoin('accession_v2 as v2', 'a.id', '=', 'v2.accession_id')
            ->leftJoin('slug', 'a.id', '=', 'slug.object_id')
            ->select(
                'a.id', 'a.identifier', 'a.date', 'a.created_at', 'a.updated_at',
                'ai.title', 'ai.scope_and_content', 'ai.appraisal',
                'ai.source_of_acquisition', 'ai.location_information',
                'ai.received_extent_units', 'ai.processing_notes',
                'v2.status as v2_status', 'v2.priority as v2_priority',
                'v2.submitted_at', 'v2.accepted_at',
                'slug.slug'
            );

        if (!empty($options['date-from'])) {
            $query->where('a.created_at', '>=', $options['date-from']);
        }
        if (!empty($options['date-to'])) {
            $query->where('a.created_at', '<=', $options['date-to'] . ' 23:59:59');
        }
        if (!empty($options['repository'])) {
            $query->join('relation as rel', function ($j) use ($options) {
                $j->on('a.id', '=', 'rel.subject_id')
                    ->where('rel.object_id', '=', (int) $options['repository']);
            });
        }

        $rows = $query->orderBy('a.identifier')->get();

        $filename = sfConfig::get('sf_root_dir') . '/downloads/accessions_export_' . date('Y-m-d_His') . '.csv';
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $fp = fopen($filename, 'w');

        // Header
        fputcsv($fp, [
            'ID', 'Identifier', 'Title', 'Date', 'Scope & Content',
            'Source of Acquisition', 'Location', 'Extent',
            'Processing Notes', 'Appraisal',
            'V2 Status', 'V2 Priority', 'Submitted At', 'Accepted At',
            'Created At', 'Updated At', 'Slug',
        ]);

        foreach ($rows as $row) {
            fputcsv($fp, [
                $row->id, $row->identifier, $row->title, $row->date,
                $row->scope_and_content, $row->source_of_acquisition,
                $row->location_information, $row->received_extent_units,
                $row->processing_notes, $row->appraisal,
                $row->v2_status ?? '', $row->v2_priority ?? '',
                $row->submitted_at ?? '', $row->accepted_at ?? '',
                $row->created_at, $row->updated_at, $row->slug,
            ]);
        }

        fclose($fp);

        $this->logSection('report', sprintf('Exported %d accessions to %s', count($rows), $filename));
    }
}
