<?php

namespace AtomFramework\Console\Commands\Privacy;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Manage privacy compliance jurisdictions.
 */
class JurisdictionCommand extends BaseCommand
{
    protected string $name = 'privacy:jurisdiction';
    protected string $description = 'Manage privacy compliance jurisdictions';
    protected string $detailedDescription = <<<'EOF'
    Manage privacy compliance jurisdictions (POPIA, GDPR, CCPA, etc.).

    Examples:
      php bin/atom privacy:jurisdiction                        List all jurisdictions
      php bin/atom privacy:jurisdiction --install=popia        Install POPIA (South Africa)
      php bin/atom privacy:jurisdiction --install=gdpr         Install GDPR (EU)
      php bin/atom privacy:jurisdiction --uninstall=ccpa       Uninstall CCPA
      php bin/atom privacy:jurisdiction --set-active=popia     Set active jurisdiction
      php bin/atom privacy:jurisdiction --info=popia           Show jurisdiction details

    Available jurisdictions:
      popia             South Africa (Protection of Personal Information Act)
      gdpr              European Union (General Data Protection Regulation)
      uk_gdpr           United Kingdom (UK GDPR - post-Brexit)
      pipeda            Canada (PIPEDA)
      ccpa              USA California (CCPA/CPRA)
      ndpa              Nigeria (NDPA)
      kenya_dpa         Kenya (Data Protection Act)
      lgpd              Brazil (LGPD)
      australia_privacy Australia (Privacy Act)
      pdpa_sg           Singapore (PDPA)
    EOF;

    protected function configure(): void
    {
        $this->addOption('install', null, 'Install a jurisdiction');
        $this->addOption('uninstall', null, 'Uninstall a jurisdiction');
        $this->addOption('set-active', null, 'Set active jurisdiction for institution');
        $this->addOption('info', null, 'Show jurisdiction details');
        $this->addOption('repository', null, 'Repository ID for --set-active');
    }

    protected function handle(): int
    {
        $managerFile = $this->getAtomRoot() . '/plugins/ahgPrivacyPlugin/lib/Jurisdictions/JurisdictionManager.php';
        if (!file_exists($managerFile)) {
            $this->error("JurisdictionManager not found at: {$managerFile}");

            return 1;
        }

        require_once $managerFile;

        $manager = \JurisdictionManager::getInstance();

        // Handle specific operations
        if ($this->hasOption('install')) {
            return $this->installJurisdiction($manager, $this->option('install'));
        }

        if ($this->hasOption('uninstall')) {
            return $this->uninstallJurisdiction($manager, $this->option('uninstall'));
        }

        if ($this->hasOption('set-active')) {
            return $this->setActiveJurisdiction($manager, $this->option('set-active'));
        }

        if ($this->hasOption('info')) {
            return $this->showJurisdictionInfo($manager, $this->option('info'));
        }

        // Default: list all jurisdictions
        return $this->listJurisdictions($manager);
    }

    protected function listJurisdictions($manager): int
    {
        $this->bold('  === Privacy Compliance Jurisdictions ===');
        $this->newline();

        $jurisdictions = $manager->getAvailableJurisdictions();

        $this->info('Available Jurisdictions:');
        $this->newline();

        $headers = ['Code', 'Name', 'Status', 'Country'];
        $rows = [];

        foreach ($jurisdictions as $jurisdiction) {
            $status = $jurisdiction->is_installed ? '[INSTALLED]' : '[not installed]';
            $rows[] = [
                $jurisdiction->code,
                $jurisdiction->full_name,
                $status,
                $jurisdiction->country,
            ];
        }

        $this->table($headers, $rows);

        $this->newline();
        $this->line('  To install a jurisdiction: php bin/atom privacy:jurisdiction --install=<code>');
        $this->line('  To see jurisdiction details: php bin/atom privacy:jurisdiction --info=<code>');

        return 0;
    }

    protected function installJurisdiction($manager, string $code): int
    {
        $this->info("Installing jurisdiction: {$code}...");

        $result = $manager->installJurisdiction($code);

        if ($result['success']) {
            if (!empty($result['already_installed'])) {
                $this->comment($result['message']);
            } else {
                $this->success($result['message']);
                $this->line("  Full name: {$result['full_name']}");
                $this->line("  Lawful bases installed: {$result['lawful_bases_installed']}");
                $this->line("  Special categories installed: {$result['special_categories_installed']}");
                $this->line("  Request types installed: {$result['request_types_installed']}");
                $this->line("  Compliance rules installed: {$result['compliance_rules_installed']}");
                $this->newline();
                $this->line('  To activate: php bin/atom privacy:jurisdiction --set-active=' . $code);
            }
        } else {
            $this->error('Installation failed: ' . $result['error']);

            return 1;
        }

        return 0;
    }

    protected function uninstallJurisdiction($manager, string $code): int
    {
        $this->info("Uninstalling jurisdiction: {$code}...");

        $result = $manager->uninstallJurisdiction($code);

        if ($result['success']) {
            $this->success($result['message']);
        } else {
            $this->error('Uninstall failed: ' . $result['error']);

            return 1;
        }

        return 0;
    }

    protected function setActiveJurisdiction($manager, string $code): int
    {
        $repositoryId = $this->hasOption('repository') ? (int) $this->option('repository') : null;

        $this->info("Setting active jurisdiction to: {$code}...");

        $result = $manager->setActiveJurisdiction($code, $repositoryId);

        if ($result['success']) {
            $this->success($result['message']);
            $this->line("  Jurisdiction: {$result['jurisdiction_name']}");
            $scope = $repositoryId ? "repository #{$repositoryId}" : 'global (all repositories)';
            $this->line("  Scope: {$scope}");
        } else {
            $this->error('Activation failed: ' . $result['error']);

            return 1;
        }

        return 0;
    }

    protected function showJurisdictionInfo($manager, string $code): int
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
            $this->error("Unknown jurisdiction: {$code}");

            return 1;
        }

        $this->bold("  === Jurisdiction: {$jurisdiction->full_name} ===");
        $this->newline();

        $this->line("  Code:             {$jurisdiction->code}");
        $this->line("  Name:             {$jurisdiction->name}");
        $this->line("  Full Name:        {$jurisdiction->full_name}");
        $this->line('  Status:           ' . ($jurisdiction->is_installed ? 'INSTALLED' : 'Not installed'));
        $this->line("  Country:          {$jurisdiction->country}");
        $this->line("  Region:           {$jurisdiction->region}");
        $this->line("  Default Currency: {$jurisdiction->default_currency}");
        $this->line("  DSAR Days:        {$jurisdiction->dsar_days}");
        $this->line("  Breach Hours:     {$jurisdiction->breach_hours}");
        $this->line("  Regulator:        {$jurisdiction->regulator}");
        $this->line("  Regulator URL:    {$jurisdiction->regulator_url}");

        if ($jurisdiction->effective_date) {
            $this->line("  Effective Date:   {$jurisdiction->effective_date}");
        }

        if ($jurisdiction->is_installed && $jurisdiction->installed_at) {
            $this->line("  Installed:        {$jurisdiction->installed_at}");

            // Get stats
            $stats = $manager->getJurisdictionStats($code);

            $this->newline();
            $this->info('Installed Components:');
            $this->line("  Lawful Bases:       {$stats['lawful_bases']}");
            $this->line("  Special Categories: {$stats['special_categories']}");
            $this->line("  Request Types:      {$stats['request_types']}");
            $this->line("  Compliance Rules:   {$stats['compliance_rules']}");

            if ($stats['dsars'] > 0 || $stats['breaches'] > 0) {
                $this->newline();
                $this->info('Usage:');
                $this->line("  DSARs:              {$stats['dsars']}");
                $this->line("  Breaches:           {$stats['breaches']}");
            }
        }

        $this->newline();

        if (!$jurisdiction->is_installed) {
            $this->line('  To install: php bin/atom privacy:jurisdiction --install=' . $code);
        } else {
            $this->line('  To set as active: php bin/atom privacy:jurisdiction --set-active=' . $code);
            $this->line('  To uninstall: php bin/atom privacy:jurisdiction --uninstall=' . $code);
        }

        return 0;
    }
}
