<?php

/**
 * CLI Task: authority:dedup-scan
 *
 * Scan for duplicate authority records.
 * Run weekly or on demand.
 *
 * Usage:
 *   php symfony authority:dedup-scan
 *   php symfony authority:dedup-scan --limit=1000
 */
class authorityDedupeScanTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Max actors to compare', 500),
        ]);

        $this->namespace = 'authority';
        $this->name = 'dedup-scan';
        $this->briefDescription = 'Scan for duplicate authority records';
        $this->detailedDescription = <<<'EOF'
Scans authority records for duplicates using Jaro-Winkler name similarity,
date overlap, and shared external identifiers.

  php symfony authority:dedup-scan
  php symfony authority:dedup-scan --limit=1000
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/src/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgAuthorityPlugin/lib/Services/AuthorityDedupeService.php';

        $service = new \AhgAuthority\Services\AuthorityDedupeService();
        $limit = (int) $options['limit'];

        $this->logSection('authority', sprintf('Starting dedup scan (limit: %d)...', $limit));
        $startTime = microtime(true);

        $pairs = $service->scan($limit, function ($current, $total) {
            if ($current % 50 === 0) {
                $this->logSection('authority', sprintf('  Comparing actor %d / %d', $current, $total));
            }
        });

        $elapsed = round(microtime(true) - $startTime, 2);

        $this->logSection('authority', sprintf('Found %d potential duplicate pair(s) in %s seconds', count($pairs), $elapsed));

        // Display top results
        $show = array_slice($pairs, 0, 20);
        foreach ($show as $pair) {
            $this->logSection('authority', sprintf('  [%.1f%%] "%s" <-> "%s" (%s)',
                $pair['score'] * 100,
                $pair['actor_a_name'],
                $pair['actor_b_name'],
                $pair['match_type']
            ));
        }

        if (count($pairs) > 20) {
            $this->logSection('authority', sprintf('  ... and %d more', count($pairs) - 20));
        }
    }
}
