<?php

namespace AtomFramework\Console\Commands\Cdpa;

use AtomFramework\Console\BaseCommand;

/**
 * Show CDPA compliance dashboard.
 */
class StatusCommand extends BaseCommand
{
    protected string $name = 'cdpa:status';
    protected string $description = 'Check CDPA status';
    protected string $detailedDescription = <<<'EOF'
    Display Zimbabwe Cyber and Data Protection Act compliance status.

    Shows:
      - POTRAZ license status
      - DPO appointment status
      - Pending data subject requests
      - Open breach incidents
      - DPIA review status

    Example:
      php bin/atom cdpa:status
    EOF;

    protected function handle(): int
    {
        $serviceFile = $this->getAtomRoot() . '/plugins/ahgCDPAPlugin/lib/Services/CDPAService.php';
        if (!file_exists($serviceFile)) {
            $this->error("CDPAService not found at: {$serviceFile}");

            return 1;
        }

        require_once $serviceFile;

        $service = new \ahgCDPAPlugin\Services\CDPAService();
        $stats = $service->getDashboardStats();
        $compliance = $service->getComplianceStatus();

        $this->bold('  === CDPA Compliance Dashboard ===');
        $this->newline();

        // Overall Status
        $statusUpper = strtoupper($compliance['status']);
        if ('compliant' === $compliance['status']) {
            $this->success("Overall Status: {$statusUpper}");
        } elseif ('warning' === $compliance['status']) {
            $this->warning("Overall Status: {$statusUpper}");
        } else {
            $this->error("Overall Status: {$statusUpper}");
        }
        $this->newline();

        // License
        $this->info('  POTRAZ License');
        if ($stats['license']) {
            $this->line("    License Number: {$stats['license']->license_number}");
            $this->line("    Tier: {$stats['license']->tier}");
            $this->line("    Expiry: {$stats['license']->expiry_date}");
            $this->line("    Status: {$stats['license_status']}");
            if (null !== $stats['license_days_remaining']) {
                $this->line("    Days Remaining: {$stats['license_days_remaining']}");
            }
        } else {
            $this->error('    NOT REGISTERED');
        }
        $this->newline();

        // DPO
        $this->info('  Data Protection Officer');
        if ($stats['dpo']) {
            $this->line("    Name: {$stats['dpo']->name}");
            $this->line("    Email: {$stats['dpo']->email}");
            $this->line("    Appointed: {$stats['dpo']->appointment_date}");
            $this->line('    Form DP2: ' . ($stats['dpo']->form_dp2_submitted ? 'Submitted' : 'Not Submitted'));
        } else {
            $this->error('    NOT APPOINTED');
        }
        $this->newline();

        // Data Subject Requests
        $this->info('  Data Subject Requests');
        $this->line("    Pending: {$stats['requests']['pending']}");
        if ($stats['requests']['overdue'] > 0) {
            $this->error("    OVERDUE: {$stats['requests']['overdue']}");
        }
        $this->line("    Last 30 days: {$stats['requests']['total_30_days']}");
        $this->newline();

        // Breaches
        $this->info('  Breach Incidents');
        $this->line("    Open: {$stats['breaches']['open']}");
        $this->line("    This year: {$stats['breaches']['this_year']}");
        $this->newline();

        // DPIA
        $this->info('  DPIA');
        $this->line("    Pending: {$stats['dpia']['pending']}");
        if ($stats['dpia']['overdue_review'] > 0) {
            $this->warning("    Overdue Reviews: {$stats['dpia']['overdue_review']}");
        }
        $this->newline();

        // Issues
        if (!empty($compliance['issues'])) {
            $this->error('  ISSUES');
            foreach ($compliance['issues'] as $issue) {
                $this->line("    - {$issue}");
            }
            $this->newline();
        }

        // Warnings
        if (!empty($compliance['warnings'])) {
            $this->warning('  WARNINGS');
            foreach ($compliance['warnings'] as $warningMsg) {
                $this->line("    - {$warningMsg}");
            }
        }

        return 0;
    }
}
