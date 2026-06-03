<?php

/**
 * centralHeartbeatTask - send a heartbeat to AHG Central.
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * The Archive and Heritage Group (Pty) Ltd
 * Licensed under the GNU Affero General Public License v3.0 or later.
 *
 * Usage:  php symfony central:heartbeat
 * Intended for daily cron:
 *   0 5 * * *  cd /usr/share/nginx/atom && php symfony central:heartbeat
 *
 * No-ops silently when ahg_central_enabled is off. heratio#127.
 */
class centralHeartbeatTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
        ]);

        $this->namespace = 'central';
        $this->name = 'heartbeat';
        $this->briefDescription = 'Send a heartbeat (alive + version) to AHG Central.';
        $this->detailedDescription = <<<'EOF'
The [central:heartbeat|INFO] task tells AHG Central this install is alive and on
which version. An unknown site auto-enrols on its first heartbeat. No-ops when
ahg_central_enabled is off. Run daily via cron.

  [php symfony central:heartbeat|INFO]
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        $svc = new \AhgCore\Services\AhgCentralService();

        if (!$svc->isEnabled()) {
            $this->logSection('central', 'heartbeat skipped - AHG Central is disabled.');

            return 0;
        }

        $result = $svc->heartbeat();
        if (!empty($result['ok'])) {
            $this->logSection('central', 'heartbeat OK (HTTP ' . ($result['http'] ?? '') . ')');

            return 0;
        }

        $msg = $result['error'] ?? ('HTTP ' . ($result['http'] ?? 0));
        $this->logSection('central', 'heartbeat non-2xx: ' . $msg, null, 'ERROR');

        // Heartbeat failures are non-fatal - the cron keeps trying.
        return 0;
    }
}
