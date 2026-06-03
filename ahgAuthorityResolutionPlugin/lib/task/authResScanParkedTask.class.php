<?php

/**
 * authResScanParkedTask - Symfony 1.4 task for AtoM Heratio
 *
 * Task 7 background sweep. For every row in ahg_mention_park, dry-runs
 * candidate generation against the current authority store and compares
 * to the persisted ahg_mention_candidate set. Mismatch => flips
 * new_candidate_available = 1 + stamps new_candidate_check_at. The
 * archivist sees the flag in the park-queue UI and chooses whether to
 * un-park.
 *
 * Operator wires this to cron (suggested: daily at 02:00):
 *   0 2 * * * cd /usr/share/nginx/archive && sudo -u www-data php symfony auth-res:scan-parked
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * Licensed under the GNU General Public License v3.0 or later.
 */

// Explicit requires: Symfony 1.4 has no PSR-4 autoloader for our namespaced
// plugin classes. ParkQueueService chains its own require_once chain.
require_once __DIR__ . '/../Services/ParkQueueService.php';

use AtomFramework\Services\AuthorityResolution\ParkQueueService;

class authResScanParkedTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'Application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'Environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'Connection', 'propel'),
        ]);

        $this->namespace = 'auth-res';
        $this->name = 'scan-parked';
        $this->briefDescription = 'Flag parked mentions whose candidate set has changed since parking.';
        $this->detailedDescription = <<<EOF
Task 7 of the AHG Authority Resolution Engine. Sweeps every row of
ahg_mention_park, dry-runs candidate generation against the live
authority store, and flags new_candidate_available = 1 on parked rows
whose candidate set has changed since parking.

Idempotent. The flag is sticky - once raised it stays until the archivist
un-parks (deletes the park row), even if a later sweep finds the
candidate set unchanged again. This avoids losing a real signal to a
transient lookup-source outage.

Usage:
  php symfony auth-res:scan-parked
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        $service = new ParkQueueService();
        $flagged = $service->scanForNewCandidates();

        $this->logSection('auth-res', sprintf(
            'Park sweep complete. %d parked mention(s) newly flagged with new_candidate_available=1.',
            $flagged
        ));
        return 0;
    }
}
