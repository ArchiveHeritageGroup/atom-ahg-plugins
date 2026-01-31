<?php

/**
 * NMMZ Report Task
 *
 * Generate reports for National Museums and Monuments of Zimbabwe
 */
class nmmzReportTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('type', null, sfCommandOption::PARAMETER_REQUIRED, 'Report type (summary|monuments|antiquities|permits|sites)', 'summary'),
            new sfCommandOption('format', null, sfCommandOption::PARAMETER_REQUIRED, 'Output format (text|csv|json)', 'text'),
        ]);

        $this->namespace = 'nmmz';
        $this->name = 'report';
        $this->briefDescription = 'Generate NMMZ heritage reports';
        $this->detailedDescription = <<<'EOF'
The [nmmz:report|INFO] task generates heritage protection reports:
  - summary: Dashboard overview
  - monuments: National monuments register
  - antiquities: Antiquities register
  - permits: Export permits
  - sites: Archaeological sites

Examples:
  [php symfony nmmz:report|INFO]
  [php symfony nmmz:report --type=monuments --format=csv|INFO]
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        $service = new \AhgNMMZ\Services\NMMZService();
        $stats = $service->getDashboardStats();
        $compliance = $service->getComplianceStatus();

        if ('json' === $options['format']) {
            echo json_encode([
                'statistics' => $stats,
                'compliance' => $compliance,
            ], JSON_PRETTY_PRINT);

            return 0;
        }

        $this->logSection('nmmz', 'National Museums and Monuments of Zimbabwe');
        $this->logSection('nmmz', 'Heritage Protection Report');
        $this->log('');

        $this->logSection('monuments', 'National Monuments');
        $this->log(sprintf('  Total: %d', $stats['monuments']['total']));
        $this->log(sprintf('  Gazetted: %d', $stats['monuments']['gazetted']));
        $this->log(sprintf('  At Risk: %d', $stats['monuments']['at_risk']));
        $this->log(sprintf('  World Heritage: %d', $stats['monuments']['world_heritage']));
        $this->log('');

        $this->logSection('antiquities', 'Antiquities Register');
        $this->log(sprintf('  Total: %d', $stats['antiquities']['total']));
        $this->log(sprintf('  In Collection: %d', $stats['antiquities']['in_collection']));
        $this->log(sprintf('  Missing: %d', $stats['antiquities']['missing']));
        $this->log('');

        $this->logSection('permits', 'Export Permits');
        $this->log(sprintf('  Pending: %d', $stats['permits']['pending']));
        $this->log(sprintf('  Active: %d', $stats['permits']['active']));
        $this->log(sprintf('  This Year: %d', $stats['permits']['this_year']));
        $this->log('');

        $this->logSection('sites', 'Archaeological Sites');
        $this->log(sprintf('  Total: %d', $stats['sites']['total']));
        $this->log(sprintf('  At Risk: %d', $stats['sites']['at_risk']));
        $this->log('');

        $this->logSection('compliance', 'Compliance: '.strtoupper($compliance['status']));
        foreach ($compliance['issues'] as $issue) {
            $this->log('  [ISSUE] '.$issue);
        }
        foreach ($compliance['warnings'] as $warning) {
            $this->log('  [WARNING] '.$warning);
        }

        return 0;
    }
}
