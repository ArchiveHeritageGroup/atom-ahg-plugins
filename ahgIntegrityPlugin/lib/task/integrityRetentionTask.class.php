<?php

use Illuminate\Database\Capsule\Manager as DB;

class integrityRetentionTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('list', null, sfCommandOption::PARAMETER_NONE, 'List all retention policies'),
            new sfCommandOption('status', null, sfCommandOption::PARAMETER_NONE, 'Show retention & disposition status'),
            new sfCommandOption('scan-eligible', null, sfCommandOption::PARAMETER_NONE, 'Scan for eligible disposition candidates'),
            new sfCommandOption('process-queue', null, sfCommandOption::PARAMETER_NONE, 'Process approved dispositions'),
            new sfCommandOption('hold', null, sfCommandOption::PARAMETER_OPTIONAL, 'Place legal hold on information object ID'),
            new sfCommandOption('release', null, sfCommandOption::PARAMETER_OPTIONAL, 'Release legal hold by hold ID'),
            new sfCommandOption('reason', null, sfCommandOption::PARAMETER_OPTIONAL, 'Reason for hold placement'),
            new sfCommandOption('policy-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Filter by policy ID'),
        ]);

        $this->namespace = 'integrity';
        $this->name = 'retention';
        $this->briefDescription = 'Manage retention policies, legal holds, and disposition queue';
        $this->detailedDescription = <<<'EOF'
Manage retention policies, legal holds, and the disposition review queue.

Examples:
  php symfony integrity:retention --list
  php symfony integrity:retention --status
  php symfony integrity:retention --scan-eligible
  php symfony integrity:retention --scan-eligible --policy-id=1
  php symfony integrity:retention --process-queue
  php symfony integrity:retention --hold=12345 --reason="Legal investigation"
  php symfony integrity:retention --release=1
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }

        require_once dirname(__DIR__) . '/Services/IntegrityRetentionService.php';
        $service = new IntegrityRetentionService();

        // List policies
        if (!empty($options['list'])) {
            $this->listPolicies($service);

            return;
        }

        // Status
        if (!empty($options['status'])) {
            $this->showStatus($service);

            return;
        }

        // Scan eligible
        if (!empty($options['scan-eligible'])) {
            $policyId = !empty($options['policy-id']) ? (int) $options['policy-id'] : null;
            $this->scanEligible($service, $policyId);

            return;
        }

        // Process queue
        if (!empty($options['process-queue'])) {
            $this->processQueue($service);

            return;
        }

        // Place hold
        if (!empty($options['hold'])) {
            $reason = $options['reason'] ?? 'CLI hold';
            $this->placeHold($service, (int) $options['hold'], $reason);

            return;
        }

        // Release hold
        if (!empty($options['release'])) {
            $this->releaseHold($service, (int) $options['release']);

            return;
        }

        $this->logSection('retention', 'Use --list, --status, --scan-eligible, --process-queue, --hold=IO_ID, or --release=HOLD_ID');
    }

    protected function listPolicies(IntegrityRetentionService $service): void
    {
        $policies = $service->listPolicies();

        $this->logSection('retention', 'Retention Policies', null, 'INFO');
        $this->logSection('retention', str_repeat('=', 60));

        if (empty($policies)) {
            $this->logSection('retention', '  No policies defined');

            return;
        }

        foreach ($policies as $p) {
            $status = $p->is_enabled ? 'ENABLED' : 'disabled';
            $retention = $p->retention_period_days > 0 ? $p->retention_period_days . ' days' : 'indefinite';
            $this->logSection('retention',
                "  #{$p->id} [{$status}] {$p->name} — {$retention} ({$p->trigger_type}, {$p->scope_type})",
                null, $p->is_enabled ? 'INFO' : 'COMMENT');
        }
    }

    protected function showStatus(IntegrityRetentionService $service): void
    {
        $policies = $service->listPolicies();
        $dispositionStats = $service->getDispositionStats();
        $holdCount = DB::table('integrity_legal_hold')->where('status', 'active')->count();

        $this->logSection('retention', 'Retention & Disposition Status', null, 'INFO');
        $this->logSection('retention', str_repeat('=', 60));
        $this->logSection('retention', '');
        $this->logSection('retention', "  Policies:       " . count($policies) . " total");
        $this->logSection('retention', "  Active holds:   {$holdCount}");
        $this->logSection('retention', '');

        if (!empty($dispositionStats)) {
            $this->logSection('retention', '  Disposition Queue:');
            foreach ($dispositionStats as $status => $count) {
                $this->logSection('retention', "    {$status}: {$count}");
            }
        } else {
            $this->logSection('retention', '  Disposition Queue: empty');
        }
    }

    protected function scanEligible(IntegrityRetentionService $service, ?int $policyId): void
    {
        $this->logSection('retention', 'Scanning for eligible disposition candidates...', null, 'INFO');

        $count = $service->scanEligible($policyId);

        $this->logSection('retention', "  {$count} items added to disposition queue",
            null, $count > 0 ? 'INFO' : 'COMMENT');
    }

    protected function processQueue(IntegrityRetentionService $service): void
    {
        $this->logSection('retention', 'Processing approved dispositions...', null, 'INFO');

        $count = $service->processApprovedDispositions();

        $this->logSection('retention', "  {$count} items marked as disposed",
            null, $count > 0 ? 'INFO' : 'COMMENT');
    }

    protected function placeHold(IntegrityRetentionService $service, int $ioId, string $reason): void
    {
        $holdId = $service->placeHold($ioId, $reason, 'cli');

        $this->logSection('retention',
            "  Legal hold #{$holdId} placed on IO #{$ioId}: {$reason}",
            null, 'INFO');
    }

    protected function releaseHold(IntegrityRetentionService $service, int $holdId): void
    {
        $result = $service->releaseHold($holdId, 'cli');

        if ($result) {
            $this->logSection('retention', "  Hold #{$holdId} released", null, 'INFO');
        } else {
            $this->logSection('retention', "  Hold #{$holdId} not found or already released", null, 'ERROR');
        }
    }
}
