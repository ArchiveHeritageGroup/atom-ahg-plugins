<?php

/**
 * centralSyncErrorsTask - push open errors to AHG Central.
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * The Archive and Heritage Group (Pty) Ltd
 * Licensed under the GNU Affero General Public License v3.0 or later.
 *
 * Usage:  php symfony central:sync-errors
 * Intended for cron:
 *   0 * * * *  cd /usr/share/nginx/atom && php symfony central:sync-errors
 *
 * Pushes the current open ahg_error_log rows (resolved_at IS NULL) to AHG
 * Central as a redacted full replace. No-ops unless BOTH ahg_central_enabled
 * and ahg_central_error_sync are on. heratio#127.
 */
class centralSyncErrorsTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
        ]);

        $this->namespace = 'central';
        $this->name = 'sync-errors';
        $this->briefDescription = 'Sync the open ahg_error_log rows to AHG Central (redacted, full replace).';
        $this->detailedDescription = <<<'EOF'
The [central:sync-errors|INFO] task pushes the open error-log rows to AHG
Central - redacted (emails / long numbers masked, URL query strings stripped)
and as a full replace, so resolved errors drop out of the fleet view. No-ops
unless ahg_central_enabled AND ahg_central_error_sync are both on.

  [php symfony central:sync-errors|INFO]
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        $svc = new \AhgCore\Services\AhgCentralService();

        if (!$svc->isEnabled()) {
            $this->logSection('central', 'sync-errors skipped - AHG Central is disabled.');

            return 0;
        }
        if (!$svc->errorSyncEnabled()) {
            $this->logSection('central', 'sync-errors skipped - error-sync toggle is off.');

            return 0;
        }

        $result = $svc->syncErrors();
        if (!empty($result['ok'])) {
            $this->logSection('central', 'synced ' . (int) ($result['sent'] ?? 0) . ' open error row(s).');

            return 0;
        }

        $this->logSection('central', 'sync-errors failed: ' . ($result['error'] ?? 'unknown'), null, 'ERROR');

        // Non-fatal - the cron retries on its next tick.
        return 0;
    }
}
