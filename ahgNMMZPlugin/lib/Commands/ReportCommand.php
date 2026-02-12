<?php

namespace AtomFramework\Console\Commands\Nmmz;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Generate NMMZ heritage reports for National Museums and Monuments of Zimbabwe.
 */
class ReportCommand extends BaseCommand
{
    protected string $name = 'nmmz:report';
    protected string $description = 'Generate NMMZ heritage reports';
    protected string $detailedDescription = <<<'EOF'
    Generates heritage protection reports for NMMZ:
      - summary: Dashboard overview
      - monuments: National monuments register
      - antiquities: Antiquities register
      - permits: Export permits
      - sites: Archaeological sites

    Examples:
      php bin/atom nmmz:report
      php bin/atom nmmz:report --type=monuments --format=csv
      php bin/atom nmmz:report --type=summary --format=json
    EOF;

    protected function configure(): void
    {
        $this->addOption('type', null, 'Report type (summary|monuments|antiquities|permits|sites)', 'summary');
        $this->addOption('format', null, 'Output format (text|csv|json)', 'text');
    }

    protected function handle(): int
    {
        $service = new \AhgNMMZ\Services\NMMZService();
        $stats = $service->getDashboardStats();
        $compliance = $service->getComplianceStatus();

        $format = $this->option('format');

        if ('json' === $format) {
            echo json_encode([
                'statistics' => $stats,
                'compliance' => $compliance,
            ], JSON_PRETTY_PRINT);

            return 0;
        }

        $this->bold('National Museums and Monuments of Zimbabwe');
        $this->info('Heritage Protection Report');
        $this->newline();

        $this->info('National Monuments');
        $this->line(sprintf('  Total: %d', $stats['monuments']['total']));
        $this->line(sprintf('  Gazetted: %d', $stats['monuments']['gazetted']));
        $this->line(sprintf('  At Risk: %d', $stats['monuments']['at_risk']));
        $this->line(sprintf('  World Heritage: %d', $stats['monuments']['world_heritage']));
        $this->newline();

        $this->info('Antiquities Register');
        $this->line(sprintf('  Total: %d', $stats['antiquities']['total']));
        $this->line(sprintf('  In Collection: %d', $stats['antiquities']['in_collection']));
        $this->line(sprintf('  Missing: %d', $stats['antiquities']['missing']));
        $this->newline();

        $this->info('Export Permits');
        $this->line(sprintf('  Pending: %d', $stats['permits']['pending']));
        $this->line(sprintf('  Active: %d', $stats['permits']['active']));
        $this->line(sprintf('  This Year: %d', $stats['permits']['this_year']));
        $this->newline();

        $this->info('Archaeological Sites');
        $this->line(sprintf('  Total: %d', $stats['sites']['total']));
        $this->line(sprintf('  At Risk: %d', $stats['sites']['at_risk']));
        $this->newline();

        $this->info('Compliance: ' . strtoupper($compliance['status']));
        foreach ($compliance['issues'] as $issue) {
            $this->error('  [ISSUE] ' . $issue);
        }
        foreach ($compliance['warnings'] as $warning) {
            $this->warning('  [WARNING] ' . $warning);
        }

        return 0;
    }
}
