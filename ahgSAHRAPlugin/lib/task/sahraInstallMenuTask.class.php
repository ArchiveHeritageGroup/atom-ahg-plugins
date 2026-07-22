<?php

/**
 * Enable the SAHRA heritage-permit feature on this instance and add its nav
 * links - idempotent.
 *
 * Sets the master `sahra_enabled` flag and adds the two nav links via
 * nested-set (MPTT) surgery with an integrity check:
 *   - "SAHRA permits"    under "manage"     -> sahra/index          (staff)
 *   - "Heritage permits" under "quickLinks" -> sahra/my-applications (researchers)
 *
 * Leave it unrun on instances outside South Africa (e.g. Australia) - the
 * feature stays hidden. Safe to run repeatedly.
 *
 *   php symfony sahra:install-menu
 *
 * Clear the cache + reload php-fpm afterwards so the nav refreshes.
 */
class sahraInstallMenuTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
        ]);

        $this->namespace = 'sahra';
        $this->name = 'install-menu';
        $this->briefDescription = 'Enable SAHRA permits (entry point = Research dashboard)';
    }

    public function execute($arguments = [], $options = [])
    {
        new sfDatabaseManager($this->configuration);
        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
        require_once dirname(__DIR__) . '/Services/SahraPermitService.php';

        $svc = new \AhgSAHRA\Services\SahraPermitService();
        $svc->setFeatureEnabled(true);
        $svc->removeMenuLinks(); // entry point is the Research dashboard now

        $this->logSection('sahra', 'SAHRA heritage permits enabled. Entry point: the Research dashboard (/research). Clear cache + reload php-fpm.');
    }
}
