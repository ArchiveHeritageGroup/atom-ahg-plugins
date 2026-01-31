<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * CLI task to check POTRAZ license expiry.
 */
class cdpaLicenseCheckTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('days', null, sfCommandOption::PARAMETER_OPTIONAL, 'Days threshold for warning', '90'),
        ]);

        $this->namespace = 'cdpa';
        $this->name = 'license-check';
        $this->briefDescription = 'Check POTRAZ license expiry';
        $this->detailedDescription = <<<EOF
Check POTRAZ license expiry status.

Examples:
  php symfony cdpa:license-check              # Check with default 90-day threshold
  php symfony cdpa:license-check --days=30    # Check with 30-day threshold
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgCDPAPlugin/lib/Services/CDPAService.php';

        $service = new \ahgCDPAPlugin\Services\CDPAService();
        $license = $service->getCurrentLicense();
        $days = (int) $options['days'];

        if (!$license) {
            $this->logSection('cdpa', 'No POTRAZ license registered!', null, 'ERROR');
            $this->log('Register your license at /admin/cdpa/license');

            return 1;
        }

        $this->logSection('cdpa', '=== POTRAZ License Status ===');
        $this->log('');
        $this->log("License Number: {$license->license_number}");
        $this->log("Organization:   {$license->organization_name}");
        $this->log("Tier:           {$license->tier}");
        $this->log("Issue Date:     {$license->issue_date}");
        $this->log("Expiry Date:    {$license->expiry_date}");
        $this->log('');

        $daysRemaining = (int) floor((strtotime($license->expiry_date) - time()) / 86400);

        if ($daysRemaining < 0) {
            $this->logSection('cdpa', "LICENSE EXPIRED {$daysRemaining} days ago!", null, 'ERROR');
            $this->log('Renew immediately at https://www.potraz.gov.zw');

            return 1;
        }

        if ($daysRemaining <= $days) {
            $this->logSection('cdpa', "License expires in {$daysRemaining} days", null, 'COMMENT');
            $this->log('Consider renewing at https://www.potraz.gov.zw');

            return 0;
        }

        $this->logSection('cdpa', "License valid for {$daysRemaining} days", null, 'INFO');

        return 0;
    }
}
