<?php

/**
 * centralPingTask - check reachability of AHG Central.
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * The Archive and Heritage Group (Pty) Ltd
 * Licensed under the GNU Affero General Public License v3.0 or later.
 *
 * Usage:  php symfony central:ping
 * heratio#127 - AtoM-AHG AHG Central client.
 */
class centralPingTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
        ]);

        $this->namespace = 'central';
        $this->name = 'ping';
        $this->briefDescription = 'Ping the configured AHG Central endpoint and report HTTP status.';
        $this->detailedDescription = <<<'EOF'
The [central:ping|INFO] task checks that this install can reach AHG Central.

  [php symfony central:ping|INFO]
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        $svc = new \AhgCore\Services\AhgCentralService();
        $result = $svc->ping();

        if (!empty($result['ok'])) {
            $this->logSection('central', 'ping OK (HTTP ' . ($result['http'] ?? '') . ') against ' . $svc->apiUrl());

            return 0;
        }

        $msg = $result['error'] ?? ('HTTP ' . ($result['http'] ?? 0));
        $this->logSection('central', 'ping FAILED: ' . $msg, null, 'ERROR');

        return 1;
    }
}
