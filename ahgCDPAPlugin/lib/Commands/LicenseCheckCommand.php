<?php

namespace AtomFramework\Console\Commands\Cdpa;

use AtomFramework\Console\BaseCommand;

/**
 * Check POTRAZ license expiry status.
 */
class LicenseCheckCommand extends BaseCommand
{
    protected string $name = 'cdpa:license-check';
    protected string $description = 'Check CDPA license compliance';
    protected string $detailedDescription = <<<'EOF'
    Check POTRAZ license expiry status.

    Examples:
      php bin/atom cdpa:license-check              Check with default 90-day threshold
      php bin/atom cdpa:license-check --days=30    Check with 30-day threshold
    EOF;

    protected function configure(): void
    {
        $this->addOption('days', 'd', 'Days threshold for warning', '90');
    }

    protected function handle(): int
    {
        $serviceFile = $this->getAtomRoot() . '/plugins/ahgCDPAPlugin/lib/Services/CDPAService.php';
        if (!file_exists($serviceFile)) {
            $this->error("CDPAService not found at: {$serviceFile}");

            return 1;
        }

        require_once $serviceFile;

        $service = new \ahgCDPAPlugin\Services\CDPAService();
        $license = $service->getCurrentLicense();
        $days = (int) $this->option('days', '90');

        if (!$license) {
            $this->error('No POTRAZ license registered!');
            $this->line('  Register your license at /admin/cdpa/license');

            return 1;
        }

        $this->bold('  === POTRAZ License Status ===');
        $this->newline();
        $this->line("  License Number: {$license->license_number}");
        $this->line("  Organization:   {$license->organization_name}");
        $this->line("  Tier:           {$license->tier}");
        $this->line("  Issue Date:     {$license->issue_date}");
        $this->line("  Expiry Date:    {$license->expiry_date}");
        $this->newline();

        $daysRemaining = (int) floor((strtotime($license->expiry_date) - time()) / 86400);

        if ($daysRemaining < 0) {
            $this->error("LICENSE EXPIRED {$daysRemaining} days ago!");
            $this->line('  Renew immediately at https://www.potraz.gov.zw');

            return 1;
        }

        if ($daysRemaining <= $days) {
            $this->warning("License expires in {$daysRemaining} days");
            $this->line('  Consider renewing at https://www.potraz.gov.zw');

            return 0;
        }

        $this->success("License valid for {$daysRemaining} days");

        return 0;
    }
}
