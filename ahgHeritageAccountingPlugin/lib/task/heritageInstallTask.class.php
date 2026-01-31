<?php

/**
 * CLI task for installing heritage accounting plugin.
 *
 * Usage:
 *   php symfony heritage:install                          # Install core schema
 *   php symfony heritage:install --region=africa_ipsas    # Install core + region
 *   php symfony heritage:install --all-regions            # Install core + all regions
 */
class heritageInstallTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('region', null, sfCommandOption::PARAMETER_OPTIONAL, 'Region to install (comma-separated for multiple)'),
            new sfCommandOption('all-regions', null, sfCommandOption::PARAMETER_NONE, 'Install all available regions'),
        ]);

        $this->namespace = 'heritage';
        $this->name = 'install';
        $this->briefDescription = 'Install heritage accounting database schema';
        $this->detailedDescription = <<<EOF
The [heritage:install|INFO] task installs the heritage accounting database schema.

Examples:
  [php symfony heritage:install|INFO]                              # Install core schema only
  [php symfony heritage:install --region=africa_ipsas|INFO]        # Install core + Africa IPSAS
  [php symfony heritage:install --region=africa_ipsas,south_africa_grap|INFO]  # Multiple regions
  [php symfony heritage:install --all-regions|INFO]                # Install all regions

Available regions:
  africa_ipsas, south_africa_grap, uk_frs, usa_government, usa_nonprofit,
  australia_nz, canada_psas, international_private
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        $this->logSection('heritage', '=== Heritage Accounting Plugin Installation ===');
        echo "\n";

        // Step 1: Install core schema
        $this->logSection('install', 'Step 1: Installing core schema...');

        $coreFile = sfConfig::get('sf_root_dir') . '/plugins/ahgHeritageAccountingPlugin/database/core.sql';

        if (!file_exists($coreFile)) {
            $this->logSection('install', 'Core schema file not found!', null, 'ERROR');

            return;
        }

        try {
            $sql = file_get_contents($coreFile);
            $conn = Propel::getConnection();

            // Split SQL into statements and execute each
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $statement) {
                if (!empty($statement) && !preg_match('/^--/', $statement)) {
                    $conn->exec($statement);
                }
            }
            $this->logSection('install', 'Core schema installed successfully', null, 'INFO');
        } catch (Exception $e) {
            // Tables might already exist
            if (false !== strpos($e->getMessage(), 'already exists')) {
                $this->logSection('install', 'Core tables already exist, skipping...', null, 'COMMENT');
            } else {
                $this->logSection('install', 'Core schema error: ' . $e->getMessage(), null, 'ERROR');

                return;
            }
        }

        // Step 2: Install regions if specified
        $regionsToInstall = [];

        if ($options['all-regions']) {
            $regionsToInstall = [
                'africa_ipsas',
                'south_africa_grap',
                'uk_frs',
                'usa_government',
                'usa_nonprofit',
                'australia_nz',
                'canada_psas',
                'international_private',
            ];
        } elseif (!empty($options['region'])) {
            $regionsToInstall = array_map('trim', explode(',', $options['region']));
        }

        if (!empty($regionsToInstall)) {
            echo "\n";
            $this->logSection('install', 'Step 2: Installing regions...');

            require_once sfConfig::get('sf_root_dir') . '/plugins/ahgHeritageAccountingPlugin/lib/Regions/RegionManager.php';
            $manager = RegionManager::getInstance();

            foreach ($regionsToInstall as $regionCode) {
                $this->logSection('region', "Installing {$regionCode}...");
                $result = $manager->installRegion($regionCode);

                if ($result['success']) {
                    if (!empty($result['already_installed'])) {
                        $this->logSection('region', "  {$regionCode}: already installed", null, 'COMMENT');
                    } else {
                        $this->logSection('region', "  {$regionCode}: installed ({$result['compliance_rules_installed']} rules)", null, 'INFO');
                    }
                } else {
                    $this->logSection('region', "  {$regionCode}: FAILED - {$result['error']}", null, 'ERROR');
                }
            }
        }

        // Summary
        echo "\n";
        $this->logSection('heritage', '=== Installation Complete ===');

        // Show installed regions
        $installed = \Illuminate\Database\Capsule\Manager::table('heritage_regional_config')
            ->where('is_installed', 1)
            ->pluck('region_code')
            ->toArray();

        if (!empty($installed)) {
            $this->logSection('status', 'Installed regions: ' . implode(', ', $installed));
        } else {
            $this->logSection('status', 'No regions installed. Run: php symfony heritage:region --install=<region>');
        }

        echo "\n";
        $this->logSection('help', 'Next steps:');
        $this->logSection('help', '  1. Install regions: php symfony heritage:region --install=<region>');
        $this->logSection('help', '  2. Set active region: php symfony heritage:region --set-active=<region>');
        $this->logSection('help', '  3. Access dashboard: /heritage/dashboard');
    }
}
