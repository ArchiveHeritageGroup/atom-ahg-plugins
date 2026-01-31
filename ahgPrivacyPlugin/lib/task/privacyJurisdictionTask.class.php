<?php

/**
 * CLI task for managing privacy compliance jurisdictions.
 *
 * Usage:
 *   php symfony privacy:jurisdiction                          # List all jurisdictions
 *   php symfony privacy:jurisdiction --install=popia          # Install a jurisdiction
 *   php symfony privacy:jurisdiction --uninstall=gdpr         # Uninstall a jurisdiction
 *   php symfony privacy:jurisdiction --set-active=popia       # Set active jurisdiction
 *   php symfony privacy:jurisdiction --info=popia             # Show jurisdiction details
 */
class privacyJurisdictionTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('install', null, sfCommandOption::PARAMETER_OPTIONAL, 'Install a jurisdiction'),
            new sfCommandOption('uninstall', null, sfCommandOption::PARAMETER_OPTIONAL, 'Uninstall a jurisdiction'),
            new sfCommandOption('set-active', null, sfCommandOption::PARAMETER_OPTIONAL, 'Set active jurisdiction for institution'),
            new sfCommandOption('info', null, sfCommandOption::PARAMETER_OPTIONAL, 'Show jurisdiction details'),
            new sfCommandOption('repository', null, sfCommandOption::PARAMETER_OPTIONAL, 'Repository ID for --set-active'),
        ]);

        $this->namespace = 'privacy';
        $this->name = 'jurisdiction';
        $this->briefDescription = 'Manage privacy compliance jurisdictions';
        $this->detailedDescription = <<<EOF
The [privacy:jurisdiction|INFO] task manages privacy compliance jurisdictions.

Examples:
  [php symfony privacy:jurisdiction|INFO]                        # List all jurisdictions
  [php symfony privacy:jurisdiction --install=popia|INFO]        # Install POPIA (South Africa)
  [php symfony privacy:jurisdiction --install=gdpr|INFO]         # Install GDPR (EU)
  [php symfony privacy:jurisdiction --uninstall=ccpa|INFO]       # Uninstall CCPA
  [php symfony privacy:jurisdiction --set-active=popia|INFO]     # Set active jurisdiction
  [php symfony privacy:jurisdiction --info=popia|INFO]           # Show jurisdiction details

Available jurisdictions:
  popia           - South Africa (Protection of Personal Information Act)
  gdpr            - European Union (General Data Protection Regulation)
  uk_gdpr         - United Kingdom (UK GDPR - post-Brexit)
  pipeda          - Canada (PIPEDA)
  ccpa            - USA California (CCPA/CPRA)
  ndpa            - Nigeria (NDPA)
  kenya_dpa       - Kenya (Data Protection Act)
  lgpd            - Brazil (LGPD)
  australia_privacy - Australia (Privacy Act)
  pdpa_sg         - Singapore (PDPA)
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        // Load JurisdictionManager
        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgPrivacyPlugin/lib/Jurisdictions/JurisdictionManager.php';

        $manager = JurisdictionManager::getInstance();

        // Handle specific operations
        if (!empty($options['install'])) {
            $this->installJurisdiction($manager, $options['install']);

            return;
        }

        if (!empty($options['uninstall'])) {
            $this->uninstallJurisdiction($manager, $options['uninstall']);

            return;
        }

        if (!empty($options['set-active'])) {
            $this->setActiveJurisdiction($manager, $options['set-active'], $options);

            return;
        }

        if (!empty($options['info'])) {
            $this->showJurisdictionInfo($manager, $options['info']);

            return;
        }

        // Default: list all jurisdictions
        $this->listJurisdictions($manager);
    }

    /**
     * List all available jurisdictions.
     */
    protected function listJurisdictions(JurisdictionManager $manager): void
    {
        $this->logSection('privacy', '=== Privacy Compliance Jurisdictions ===');
        echo "\n";

        $jurisdictions = $manager->getAvailableJurisdictions();

        $this->logSection('jurisdictions', 'Available Jurisdictions:');
        echo "\n";

        echo str_pad('Code', 20) . str_pad('Name', 40) . str_pad('Status', 15) . "Country\n";
        echo str_repeat('-', 100) . "\n";

        foreach ($jurisdictions as $jurisdiction) {
            $status = $jurisdiction->is_installed ? '[INSTALLED]' : '[not installed]';

            echo str_pad($jurisdiction->code, 20);
            echo str_pad($jurisdiction->full_name, 40);
            echo str_pad($status, 15);
            echo $jurisdiction->country . "\n";
        }

        echo "\n";
        $this->logSection('help', 'To install a jurisdiction: php symfony privacy:jurisdiction --install=<code>');
        $this->logSection('help', 'To see jurisdiction details: php symfony privacy:jurisdiction --info=<code>');
    }

    /**
     * Install a jurisdiction.
     */
    protected function installJurisdiction(JurisdictionManager $manager, string $code): void
    {
        $this->logSection('install', "Installing jurisdiction: {$code}...");

        $result = $manager->installJurisdiction($code);

        if ($result['success']) {
            if (!empty($result['already_installed'])) {
                $this->logSection('install', $result['message'], null, 'COMMENT');
            } else {
                $this->logSection('install', $result['message'], null, 'INFO');
                $this->logSection('install', "Full name: {$result['full_name']}");
                $this->logSection('install', "Lawful bases installed: {$result['lawful_bases_installed']}");
                $this->logSection('install', "Special categories installed: {$result['special_categories_installed']}");
                $this->logSection('install', "Request types installed: {$result['request_types_installed']}");
                $this->logSection('install', "Compliance rules installed: {$result['compliance_rules_installed']}");
                echo "\n";
                $this->logSection('next', 'To activate: php symfony privacy:jurisdiction --set-active=' . $code);
            }
        } else {
            $this->logSection('install', 'Installation failed: ' . $result['error'], null, 'ERROR');
        }
    }

    /**
     * Uninstall a jurisdiction.
     */
    protected function uninstallJurisdiction(JurisdictionManager $manager, string $code): void
    {
        $this->logSection('uninstall', "Uninstalling jurisdiction: {$code}...");

        $result = $manager->uninstallJurisdiction($code);

        if ($result['success']) {
            $this->logSection('uninstall', $result['message'], null, 'INFO');
        } else {
            $this->logSection('uninstall', 'Uninstall failed: ' . $result['error'], null, 'ERROR');
        }
    }

    /**
     * Set active jurisdiction.
     */
    protected function setActiveJurisdiction(JurisdictionManager $manager, string $code, array $options): void
    {
        $repositoryId = !empty($options['repository']) ? (int) $options['repository'] : null;

        $this->logSection('activate', "Setting active jurisdiction to: {$code}...");

        $result = $manager->setActiveJurisdiction($code, $repositoryId);

        if ($result['success']) {
            $this->logSection('activate', $result['message'], null, 'INFO');
            $this->logSection('activate', "Jurisdiction: {$result['jurisdiction_name']}");
            $scope = $repositoryId ? "repository #{$repositoryId}" : 'global (all repositories)';
            $this->logSection('activate', "Scope: {$scope}");
        } else {
            $this->logSection('activate', 'Activation failed: ' . $result['error'], null, 'ERROR');
        }
    }

    /**
     * Show jurisdiction details.
     */
    protected function showJurisdictionInfo(JurisdictionManager $manager, string $code): void
    {
        $jurisdictions = $manager->getAvailableJurisdictions();
        $jurisdiction = null;

        foreach ($jurisdictions as $j) {
            if ($j->code === $code) {
                $jurisdiction = $j;
                break;
            }
        }

        if (!$jurisdiction) {
            $this->logSection('info', "Unknown jurisdiction: {$code}", null, 'ERROR');

            return;
        }

        $this->logSection('info', "=== Jurisdiction: {$jurisdiction->full_name} ===");
        echo "\n";

        echo "Code:             {$jurisdiction->code}\n";
        echo "Name:             {$jurisdiction->name}\n";
        echo "Full Name:        {$jurisdiction->full_name}\n";
        echo 'Status:           ' . ($jurisdiction->is_installed ? 'INSTALLED' : 'Not installed') . "\n";
        echo "Country:          {$jurisdiction->country}\n";
        echo "Region:           {$jurisdiction->region}\n";
        echo "Default Currency: {$jurisdiction->default_currency}\n";
        echo "DSAR Days:        {$jurisdiction->dsar_days}\n";
        echo "Breach Hours:     {$jurisdiction->breach_hours}\n";
        echo "Regulator:        {$jurisdiction->regulator}\n";
        echo "Regulator URL:    {$jurisdiction->regulator_url}\n";

        if ($jurisdiction->effective_date) {
            echo "Effective Date:   {$jurisdiction->effective_date}\n";
        }

        if ($jurisdiction->is_installed && $jurisdiction->installed_at) {
            echo "Installed:        {$jurisdiction->installed_at}\n";

            // Get stats
            $stats = $manager->getJurisdictionStats($code);

            echo "\n";
            $this->logSection('stats', 'Installed Components:');
            echo "  Lawful Bases:       {$stats['lawful_bases']}\n";
            echo "  Special Categories: {$stats['special_categories']}\n";
            echo "  Request Types:      {$stats['request_types']}\n";
            echo "  Compliance Rules:   {$stats['compliance_rules']}\n";

            if ($stats['dsars'] > 0 || $stats['breaches'] > 0) {
                echo "\n";
                $this->logSection('usage', 'Usage:');
                echo "  DSARs:              {$stats['dsars']}\n";
                echo "  Breaches:           {$stats['breaches']}\n";
            }
        }

        echo "\n";

        if (!$jurisdiction->is_installed) {
            $this->logSection('action', 'To install: php symfony privacy:jurisdiction --install=' . $code);
        } else {
            $this->logSection('action', 'To set as active: php symfony privacy:jurisdiction --set-active=' . $code);
            $this->logSection('action', 'To uninstall: php symfony privacy:jurisdiction --uninstall=' . $code);
        }
    }
}
