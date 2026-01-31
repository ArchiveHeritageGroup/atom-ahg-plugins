<?php

/**
 * CLI task for managing heritage accounting regions.
 *
 * Usage:
 *   php symfony heritage:region                           # List all regions
 *   php symfony heritage:region --install=africa_ipsas    # Install a region
 *   php symfony heritage:region --uninstall=uk_frs        # Uninstall a region
 *   php symfony heritage:region --set-active=africa_ipsas # Set active region
 *   php symfony heritage:region --info=africa_ipsas       # Show region details
 */
class heritageRegionTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('install', null, sfCommandOption::PARAMETER_OPTIONAL, 'Install a region'),
            new sfCommandOption('uninstall', null, sfCommandOption::PARAMETER_OPTIONAL, 'Uninstall a region'),
            new sfCommandOption('set-active', null, sfCommandOption::PARAMETER_OPTIONAL, 'Set active region for institution'),
            new sfCommandOption('info', null, sfCommandOption::PARAMETER_OPTIONAL, 'Show region details'),
            new sfCommandOption('repository', null, sfCommandOption::PARAMETER_OPTIONAL, 'Repository ID for --set-active'),
            new sfCommandOption('currency', null, sfCommandOption::PARAMETER_OPTIONAL, 'Currency override for --set-active'),
        ]);

        $this->namespace = 'heritage';
        $this->name = 'region';
        $this->briefDescription = 'Manage heritage accounting regions';
        $this->detailedDescription = <<<EOF
The [heritage:region|INFO] task manages regional heritage accounting standards.

Examples:
  [php symfony heritage:region|INFO]                             # List all regions
  [php symfony heritage:region --install=africa_ipsas|INFO]      # Install Africa IPSAS
  [php symfony heritage:region --install=south_africa_grap|INFO] # Install South Africa GRAP
  [php symfony heritage:region --uninstall=uk_frs|INFO]          # Uninstall UK FRS
  [php symfony heritage:region --set-active=africa_ipsas|INFO]   # Set active region
  [php symfony heritage:region --info=africa_ipsas|INFO]         # Show region details

Available regions:
  africa_ipsas        - Africa (IPSAS 45): Zimbabwe, Kenya, Nigeria, Ghana, etc.
  south_africa_grap   - South Africa (GRAP 103): National Treasury compliance
  uk_frs              - United Kingdom (FRS 102): Charity Commission SORP
  usa_government      - USA Government (GASB 34): State and local governments
  usa_nonprofit       - USA Non-Profit (FASB 958): Museums, galleries
  australia_nz        - Australia/NZ (AASB 116): AASB compliance
  canada_psas         - Canada (PSAS 3150): Public accounts
  international_private - International Private (IAS 16)
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        // Load RegionManager
        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgHeritageAccountingPlugin/lib/Regions/RegionManager.php';

        $manager = RegionManager::getInstance();

        // Handle specific operations
        if (!empty($options['install'])) {
            $this->installRegion($manager, $options['install']);

            return;
        }

        if (!empty($options['uninstall'])) {
            $this->uninstallRegion($manager, $options['uninstall']);

            return;
        }

        if (!empty($options['set-active'])) {
            $this->setActiveRegion($manager, $options['set-active'], $options);

            return;
        }

        if (!empty($options['info'])) {
            $this->showRegionInfo($manager, $options['info']);

            return;
        }

        // Default: list all regions
        $this->listRegions($manager);
    }

    /**
     * List all available regions.
     */
    protected function listRegions(RegionManager $manager): void
    {
        $this->logSection('heritage', '=== Heritage Accounting Regions ===');
        echo "\n";

        $regions = $manager->getAvailableRegions();

        $this->logSection('regions', 'Available Regions:');
        echo "\n";

        echo str_pad('Code', 25) . str_pad('Name', 35) . str_pad('Status', 12) . "Countries\n";
        echo str_repeat('-', 100) . "\n";

        foreach ($regions as $region) {
            $status = $region->is_installed ? '[INSTALLED]' : '[not installed]';
            $statusColor = $region->is_installed ? 'INFO' : 'COMMENT';

            $countries = is_array($region->countries) ? implode(', ', array_slice($region->countries, 0, 3)) : '';
            if (is_array($region->countries) && count($region->countries) > 3) {
                $countries .= '...';
            }

            echo str_pad($region->region_code, 25);
            echo str_pad($region->region_name, 35);
            echo str_pad($status, 12);
            echo $countries . "\n";
        }

        echo "\n";
        $this->logSection('help', 'To install a region: php symfony heritage:region --install=<region_code>');
        $this->logSection('help', 'To see region details: php symfony heritage:region --info=<region_code>');
    }

    /**
     * Install a region.
     */
    protected function installRegion(RegionManager $manager, string $regionCode): void
    {
        $this->logSection('install', "Installing region: {$regionCode}...");

        $result = $manager->installRegion($regionCode);

        if ($result['success']) {
            if (!empty($result['already_installed'])) {
                $this->logSection('install', $result['message'], null, 'COMMENT');
            } else {
                $this->logSection('install', $result['message'], null, 'INFO');
                $this->logSection('install', "Standard: {$result['standard_name']} ({$result['standard_code']})");
                $this->logSection('install', "Compliance rules installed: {$result['compliance_rules_installed']}");
                echo "\n";
                $this->logSection('next', 'To activate this region: php symfony heritage:region --set-active=' . $regionCode);
            }
        } else {
            $this->logSection('install', 'Installation failed: ' . $result['error'], null, 'ERROR');
        }
    }

    /**
     * Uninstall a region.
     */
    protected function uninstallRegion(RegionManager $manager, string $regionCode): void
    {
        $this->logSection('uninstall', "Uninstalling region: {$regionCode}...");

        $result = $manager->uninstallRegion($regionCode);

        if ($result['success']) {
            $this->logSection('uninstall', $result['message'], null, 'INFO');
        } else {
            $this->logSection('uninstall', 'Uninstall failed: ' . $result['error'], null, 'ERROR');
        }
    }

    /**
     * Set active region.
     */
    protected function setActiveRegion(RegionManager $manager, string $regionCode, array $options): void
    {
        $repositoryId = !empty($options['repository']) ? (int) $options['repository'] : null;

        $this->logSection('activate', "Setting active region to: {$regionCode}...");

        $setOptions = [];
        if (!empty($options['currency'])) {
            $setOptions['currency'] = $options['currency'];
        }

        $result = $manager->setActiveRegion($regionCode, $repositoryId, $setOptions);

        if ($result['success']) {
            $this->logSection('activate', $result['message'], null, 'INFO');
            if ($result['standard_code']) {
                $this->logSection('activate', "Accounting standard: {$result['standard_code']}");
            }
            $scope = $repositoryId ? "repository #{$repositoryId}" : 'global (all repositories)';
            $this->logSection('activate', "Scope: {$scope}");
        } else {
            $this->logSection('activate', 'Activation failed: ' . $result['error'], null, 'ERROR');
        }
    }

    /**
     * Show region details.
     */
    protected function showRegionInfo(RegionManager $manager, string $regionCode): void
    {
        $regions = $manager->getAvailableRegions();
        $region = null;

        foreach ($regions as $r) {
            if ($r->region_code === $regionCode) {
                $region = $r;
                break;
            }
        }

        if (!$region) {
            $this->logSection('info', "Unknown region: {$regionCode}", null, 'ERROR');

            return;
        }

        $this->logSection('info', "=== Region: {$region->region_name} ===");
        echo "\n";

        echo "Code:           {$region->region_code}\n";
        echo 'Status:         ' . ($region->is_installed ? 'INSTALLED' : 'Not installed') . "\n";
        echo "Default Currency: {$region->default_currency}\n";
        echo "Regulatory Body: {$region->regulatory_body}\n";
        echo "Countries:      " . (is_array($region->countries) ? implode(', ', $region->countries) : $region->countries) . "\n";

        if ($region->is_installed && $region->installed_at) {
            echo "Installed:      {$region->installed_at}\n";

            // Get standard info
            $standard = \Illuminate\Database\Capsule\Manager::table('heritage_accounting_standard')
                ->where('region_code', $regionCode)
                ->first();

            if ($standard) {
                echo "\n";
                $this->logSection('standard', 'Accounting Standard:');
                echo "  Code: {$standard->code}\n";
                echo "  Name: {$standard->name}\n";
                echo "  Description: {$standard->description}\n";

                // Count rules
                $rulesCount = \Illuminate\Database\Capsule\Manager::table('heritage_compliance_rule')
                    ->where('standard_id', $standard->id)
                    ->count();

                echo "  Compliance Rules: {$rulesCount}\n";
            }
        }

        echo "\n";

        if (!$region->is_installed) {
            $this->logSection('action', 'To install: php symfony heritage:region --install=' . $regionCode);
        } else {
            $this->logSection('action', 'To set as active: php symfony heritage:region --set-active=' . $regionCode);
            $this->logSection('action', 'To uninstall: php symfony heritage:region --uninstall=' . $regionCode);
        }
    }
}
