<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * CLI task to show CDPA compliance status.
 */
class cdpaStatusTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
        ]);

        $this->namespace = 'cdpa';
        $this->name = 'status';
        $this->briefDescription = 'Show CDPA compliance dashboard';
        $this->detailedDescription = <<<EOF
Display Zimbabwe Cyber and Data Protection Act compliance status.

Shows:
  - POTRAZ license status
  - DPO appointment status
  - Pending data subject requests
  - Open breach incidents
  - DPIA review status

Example:
  php symfony cdpa:status
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgCDPAPlugin/lib/Services/CDPAService.php';

        $service = new \ahgCDPAPlugin\Services\CDPAService();
        $stats = $service->getDashboardStats();
        $compliance = $service->getComplianceStatus();

        $this->logSection('cdpa', '=== CDPA Compliance Dashboard ===');
        $this->log('');

        // Overall Status
        $statusColor = 'compliant' === $compliance['status'] ? 'INFO' : ('warning' === $compliance['status'] ? 'COMMENT' : 'ERROR');
        $this->logSection('cdpa', 'Overall Status: ' . strtoupper($compliance['status']), null, $statusColor);
        $this->log('');

        // License
        $this->logSection('cdpa', 'POTRAZ License');
        if ($stats['license']) {
            $this->log("  License Number: {$stats['license']->license_number}");
            $this->log("  Tier: {$stats['license']->tier}");
            $this->log("  Expiry: {$stats['license']->expiry_date}");
            $this->log("  Status: {$stats['license_status']}");
            if ($stats['license_days_remaining'] !== null) {
                $this->log("  Days Remaining: {$stats['license_days_remaining']}");
            }
        } else {
            $this->log('  NOT REGISTERED', null, 'ERROR');
        }
        $this->log('');

        // DPO
        $this->logSection('cdpa', 'Data Protection Officer');
        if ($stats['dpo']) {
            $this->log("  Name: {$stats['dpo']->name}");
            $this->log("  Email: {$stats['dpo']->email}");
            $this->log("  Appointed: {$stats['dpo']->appointment_date}");
            $this->log('  Form DP2: ' . ($stats['dpo']->form_dp2_submitted ? 'Submitted' : 'Not Submitted'));
        } else {
            $this->log('  NOT APPOINTED', null, 'ERROR');
        }
        $this->log('');

        // Data Subject Requests
        $this->logSection('cdpa', 'Data Subject Requests');
        $this->log("  Pending: {$stats['requests']['pending']}");
        if ($stats['requests']['overdue'] > 0) {
            $this->log("  OVERDUE: {$stats['requests']['overdue']}", null, 'ERROR');
        }
        $this->log("  Last 30 days: {$stats['requests']['total_30_days']}");
        $this->log('');

        // Breaches
        $this->logSection('cdpa', 'Breach Incidents');
        $this->log("  Open: {$stats['breaches']['open']}");
        $this->log("  This year: {$stats['breaches']['this_year']}");
        $this->log('');

        // DPIA
        $this->logSection('cdpa', 'DPIA');
        $this->log("  Pending: {$stats['dpia']['pending']}");
        if ($stats['dpia']['overdue_review'] > 0) {
            $this->log("  Overdue Reviews: {$stats['dpia']['overdue_review']}", null, 'COMMENT');
        }
        $this->log('');

        // Issues
        if (!empty($compliance['issues'])) {
            $this->logSection('cdpa', 'ISSUES', null, 'ERROR');
            foreach ($compliance['issues'] as $issue) {
                $this->log("  - {$issue}");
            }
            $this->log('');
        }

        // Warnings
        if (!empty($compliance['warnings'])) {
            $this->logSection('cdpa', 'WARNINGS', null, 'COMMENT');
            foreach ($compliance['warnings'] as $warning) {
                $this->log("  - {$warning}");
            }
        }

        return 0;
    }
}
