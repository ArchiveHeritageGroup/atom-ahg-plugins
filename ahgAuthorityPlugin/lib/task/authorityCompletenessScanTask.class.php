<?php

/**
 * CLI Task: authority:completeness-scan
 *
 * Batch calculate completeness scores for all authority records.
 * Run daily or weekly via cron.
 *
 * Usage:
 *   php symfony authority:completeness-scan
 *   php symfony authority:completeness-scan --limit=100
 */
class authorityCompletenessScanTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Max actors to scan (0 = all)', 0),
        ]);

        $this->namespace = 'authority';
        $this->name = 'completeness-scan';
        $this->briefDescription = 'Calculate completeness scores for authority records';
        $this->detailedDescription = <<<'EOF'
Scans authority records and calculates completeness scores based on
ISAAR(CPF) fields, external identifiers, relations, and resources.

  php symfony authority:completeness-scan
  php symfony authority:completeness-scan --limit=100
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        // Bootstrap Laravel DB
        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/src/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgAuthorityPlugin/lib/Services/AuthorityCompletenessService.php';
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgAuthorityPlugin/lib/Services/AuthorityIdentifierService.php';

        $service = new \AhgAuthority\Services\AuthorityCompletenessService();
        $limit = (int) $options['limit'];

        $this->logSection('authority', 'Starting completeness scan...');

        $startTime = microtime(true);
        $count = $service->batchCalculate($limit, function ($current, $total) {
            if ($current % 100 === 0 || $current === $total) {
                $this->logSection('authority', sprintf('Progress: %d / %d', $current, $total));
            }
        });

        $elapsed = round(microtime(true) - $startTime, 2);
        $this->logSection('authority', sprintf('Completed: %d actors scored in %s seconds', $count, $elapsed));
    }
}
