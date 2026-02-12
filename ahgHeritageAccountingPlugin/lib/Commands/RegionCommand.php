<?php

namespace AtomFramework\Console\Commands\Heritage;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

class RegionCommand extends BaseCommand
{
    protected string $name = 'heritage:region';
    protected string $description = 'Manage heritage accounting regions';
    protected string $detailedDescription = <<<'EOF'
    Manage regional heritage accounting standards.

    Examples:
      php bin/atom heritage:region                             # List all regions
      php bin/atom heritage:region --install=africa_ipsas       # Install Africa IPSAS
      php bin/atom heritage:region --uninstall=uk_frs           # Uninstall UK FRS
      php bin/atom heritage:region --set-active=africa_ipsas    # Set active region
      php bin/atom heritage:region --info=africa_ipsas          # Show region details

    Available regions:
      africa_ipsas, south_africa_grap, uk_frs, usa_government, usa_nonprofit,
      australia_nz, canada_psas, international_private
    EOF;

    protected function configure(): void
    {
        $this->addOption('install', null, 'Install a region');
        $this->addOption('uninstall', null, 'Uninstall a region');
        $this->addOption('set-active', null, 'Set active region for institution');
        $this->addOption('info', null, 'Show region details');
        $this->addOption('repository', null, 'Repository ID for --set-active');
        $this->addOption('currency', null, 'Currency override for --set-active');
    }

    protected function handle(): int
    {
        $pluginDir = $this->getAtomRoot() . '/atom-ahg-plugins/ahgHeritageAccountingPlugin';
        require_once $pluginDir . '/lib/Regions/RegionManager.php';

        $manager = \RegionManager::getInstance();

        $install = $this->option('install');
        if ($install && $install !== '1') {
            return $this->installRegion($manager, $install);
        }

        $uninstall = $this->option('uninstall');
        if ($uninstall && $uninstall !== '1') {
            return $this->uninstallRegion($manager, $uninstall);
        }

        $setActive = $this->option('set-active');
        if ($setActive && $setActive !== '1') {
            return $this->setActiveRegion($manager, $setActive);
        }

        $infoCode = $this->option('info');
        if ($infoCode && $infoCode !== '1') {
            return $this->showRegionInfo($manager, $infoCode);
        }

        return $this->listRegions($manager);
    }

    private function listRegions($manager): int
    {
        $this->info('=== Heritage Accounting Regions ===');
        $this->newline();

        $regions = $manager->getAvailableRegions();

        $this->line(str_pad('Code', 25) . str_pad('Name', 35) . str_pad('Status', 12) . 'Countries');
        $this->line(str_repeat('-', 100));

        foreach ($regions as $region) {
            $status = $region->is_installed ? '[INSTALLED]' : '[not installed]';
            $countries = is_array($region->countries) ? implode(', ', array_slice($region->countries, 0, 3)) : '';
            if (is_array($region->countries) && count($region->countries) > 3) {
                $countries .= '...';
            }

            $this->line(str_pad($region->region_code, 25) . str_pad($region->region_name, 35) . str_pad($status, 12) . $countries);
        }

        $this->newline();
        $this->comment('To install a region: php bin/atom heritage:region --install=<region_code>');
        $this->comment('To see region details: php bin/atom heritage:region --info=<region_code>');

        return 0;
    }

    private function installRegion($manager, string $regionCode): int
    {
        $this->info("Installing region: {$regionCode}...");

        $result = $manager->installRegion($regionCode);

        if ($result['success']) {
            if (!empty($result['already_installed'])) {
                $this->comment($result['message']);
            } else {
                $this->success($result['message']);
                $this->line("Standard: {$result['standard_name']} ({$result['standard_code']})");
                $this->line("Compliance rules installed: {$result['compliance_rules_installed']}");
                $this->newline();
                $this->comment('To activate this region: php bin/atom heritage:region --set-active=' . $regionCode);
            }
        } else {
            $this->error('Installation failed: ' . $result['error']);

            return 1;
        }

        return 0;
    }

    private function uninstallRegion($manager, string $regionCode): int
    {
        $this->info("Uninstalling region: {$regionCode}...");

        $result = $manager->uninstallRegion($regionCode);

        if ($result['success']) {
            $this->success($result['message']);
        } else {
            $this->error('Uninstall failed: ' . $result['error']);

            return 1;
        }

        return 0;
    }

    private function setActiveRegion($manager, string $regionCode): int
    {
        $repositoryId = $this->option('repository') ? (int) $this->option('repository') : null;

        $this->info("Setting active region to: {$regionCode}...");

        $setOptions = [];
        if ($this->option('currency')) {
            $setOptions['currency'] = $this->option('currency');
        }

        $result = $manager->setActiveRegion($regionCode, $repositoryId, $setOptions);

        if ($result['success']) {
            $this->success($result['message']);
            if ($result['standard_code']) {
                $this->line("Accounting standard: {$result['standard_code']}");
            }
            $scope = $repositoryId ? "repository #{$repositoryId}" : 'global (all repositories)';
            $this->line("Scope: {$scope}");
        } else {
            $this->error('Activation failed: ' . $result['error']);

            return 1;
        }

        return 0;
    }

    private function showRegionInfo($manager, string $regionCode): int
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
            $this->error("Unknown region: {$regionCode}");

            return 1;
        }

        $this->info("=== Region: {$region->region_name} ===");
        $this->newline();

        $this->line("Code:           {$region->region_code}");
        $this->line('Status:         ' . ($region->is_installed ? 'INSTALLED' : 'Not installed'));
        $this->line("Default Currency: {$region->default_currency}");
        $this->line("Regulatory Body: {$region->regulatory_body}");
        $this->line('Countries:      ' . (is_array($region->countries) ? implode(', ', $region->countries) : $region->countries));

        if ($region->is_installed && $region->installed_at) {
            $this->line("Installed:      {$region->installed_at}");

            $standard = DB::table('heritage_accounting_standard')
                ->where('region_code', $regionCode)
                ->first();

            if ($standard) {
                $this->newline();
                $this->bold('Accounting Standard:');
                $this->line("  Code: {$standard->code}");
                $this->line("  Name: {$standard->name}");
                $this->line("  Description: {$standard->description}");

                $rulesCount = DB::table('heritage_compliance_rule')
                    ->where('standard_id', $standard->id)
                    ->count();

                $this->line("  Compliance Rules: {$rulesCount}");
            }
        }

        $this->newline();

        if (!$region->is_installed) {
            $this->comment('To install: php bin/atom heritage:region --install=' . $regionCode);
        } else {
            $this->comment('To set as active: php bin/atom heritage:region --set-active=' . $regionCode);
            $this->comment('To uninstall: php bin/atom heritage:region --uninstall=' . $regionCode);
        }

        return 0;
    }
}
