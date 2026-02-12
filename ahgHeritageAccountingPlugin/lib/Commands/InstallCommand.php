<?php

namespace AtomFramework\Console\Commands\Heritage;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

class InstallCommand extends BaseCommand
{
    protected string $name = 'heritage:install';
    protected string $description = 'Install heritage accounting database schema';
    protected string $detailedDescription = <<<'EOF'
    Install the heritage accounting database schema and optional regional configurations.

    Examples:
      php bin/atom heritage:install                              # Install core schema only
      php bin/atom heritage:install --region=africa_ipsas         # Install core + Africa IPSAS
      php bin/atom heritage:install --region=africa_ipsas,south_africa_grap  # Multiple regions
      php bin/atom heritage:install --all-regions                 # Install all regions

    Available regions:
      africa_ipsas, south_africa_grap, uk_frs, usa_government, usa_nonprofit,
      australia_nz, canada_psas, international_private
    EOF;

    protected function configure(): void
    {
        $this->addOption('region', null, 'Region to install (comma-separated for multiple)');
        $this->addOption('all-regions', null, 'Install all available regions');
    }

    protected function handle(): int
    {
        $pluginDir = $this->getAtomRoot() . '/atom-ahg-plugins/ahgHeritageAccountingPlugin';

        $this->info('=== Heritage Accounting Plugin Installation ===');
        $this->newline();

        // Step 1: Install core schema
        $this->info('Step 1: Installing core schema...');

        $coreFile = $pluginDir . '/database/core.sql';

        if (!file_exists($coreFile)) {
            $this->error('Core schema file not found!');

            return 1;
        }

        try {
            $sql = file_get_contents($coreFile);
            $pdo = DB::connection()->getPdo();

            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $statement) {
                if (!empty($statement) && !preg_match('/^--/', $statement)) {
                    $pdo->exec($statement);
                }
            }
            $this->success('Core schema installed successfully');
        } catch (\Exception $e) {
            if (false !== strpos($e->getMessage(), 'already exists')) {
                $this->comment('Core tables already exist, skipping...');
            } else {
                $this->error('Core schema error: ' . $e->getMessage());

                return 1;
            }
        }

        // Step 2: Install regions if specified
        $regionsToInstall = [];

        if ($this->hasOption('all-regions')) {
            $regionsToInstall = [
                'africa_ipsas', 'south_africa_grap', 'uk_frs', 'usa_government',
                'usa_nonprofit', 'australia_nz', 'canada_psas', 'international_private',
            ];
        } elseif ($this->option('region')) {
            $regionsToInstall = array_map('trim', explode(',', $this->option('region')));
        }

        if (!empty($regionsToInstall)) {
            $this->newline();
            $this->info('Step 2: Installing regions...');

            require_once $pluginDir . '/lib/Regions/RegionManager.php';
            $manager = \RegionManager::getInstance();

            foreach ($regionsToInstall as $regionCode) {
                $this->info("Installing {$regionCode}...");
                $result = $manager->installRegion($regionCode);

                if ($result['success']) {
                    if (!empty($result['already_installed'])) {
                        $this->comment("  {$regionCode}: already installed");
                    } else {
                        $this->success("  {$regionCode}: installed ({$result['compliance_rules_installed']} rules)");
                    }
                } else {
                    $this->error("  {$regionCode}: FAILED - {$result['error']}");
                }
            }
        }

        // Summary
        $this->newline();
        $this->info('=== Installation Complete ===');

        $installed = DB::table('heritage_regional_config')
            ->where('is_installed', 1)
            ->pluck('region_code')
            ->toArray();

        if (!empty($installed)) {
            $this->line('Installed regions: ' . implode(', ', $installed));
        } else {
            $this->line('No regions installed. Run: php bin/atom heritage:region --install=<region>');
        }

        $this->newline();
        $this->info('Next steps:');
        $this->line('  1. Install regions: php bin/atom heritage:region --install=<region>');
        $this->line('  2. Set active region: php bin/atom heritage:region --set-active=<region>');
        $this->line('  3. Access dashboard: /heritage/dashboard');

        return 0;
    }
}
