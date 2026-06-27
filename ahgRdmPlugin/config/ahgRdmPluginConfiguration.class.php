<?php

/**
 * ahgRdmPlugin configuration.
 *
 * Sovereign research-data-management module — a thin orchestration layer over
 * ahgIngestPlugin (file deposit), ahgInformationObjectManagePlugin (container IO),
 * ahgResearchPlugin (projects / DMP / ODRL), ahgAIPlugin (gateway NER) and
 * ahgDoiPlugin (DOI). Reverse port of Heratio ahg-rdm (heratio#1337).
 *
 * Phase 1 (atom-ahg-plugins#168): scaffold + Dataset model + deposit.
 */
class ahgRdmPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Sovereign RDM: dataset deposit + POPIA scan + compliance';
    public static $version = '0.5.0';

    public function contextLoadFactories(sfEvent $event)
    {
        $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($frameworkPath)) {
            require_once $frameworkPath;
        }
    }

    public function initialize()
    {
        $this->registerAutoloader();

        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'rdm';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }

    /**
     * PSR-4-style autoloader for this plugin's namespaced classes
     * (AhgRdm\… -> lib/…). Mirrors ahgInformationObjectManagePlugin so the
     * namespaced lib/Services classes load without a composer dump.
     */
    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgRdm\\') === 0) {
                $relativePath = str_replace('AhgRdm\\', '', $class);
                $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativePath);
                $filePath = __DIR__ . '/../lib/' . $relativePath . '.php';
                if (file_exists($filePath)) {
                    require_once $filePath;

                    return true;
                }
            }

            return false;
        });
    }

    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        // All paths sit under /research/datasets — the 'research' prefix is
        // excluded from the locked /:slug catch-all, and every route is >= 2
        // segments. The numeric :id constraint keeps /create from colliding
        // with /:id.
        $r = new \AtomFramework\Routing\RouteLoader('rdm');

        $r->any('rdm_datasets_index', '/research/datasets', 'index');
        $r->any('rdm_datasets_compliance', '/research/datasets/compliance', 'compliance');
        $r->any('rdm_datasets_create', '/research/datasets/create', 'create');
        $r->any('rdm_datasets_show', '/research/datasets/:id', 'show', ['id' => '\d+']);
        $r->any('rdm_datasets_deposit', '/research/datasets/:id/deposit', 'deposit', ['id' => '\d+']);
        $r->any('rdm_datasets_scan', '/research/datasets/:id/scan', 'scan', ['id' => '\d+']);
        $r->any('rdm_datasets_finding_resolve', '/research/datasets/:id/findings/:fid/resolve', 'resolveFinding', ['id' => '\d+', 'fid' => '\d+']);
        $r->any('rdm_datasets_disposition', '/research/datasets/:id/disposition', 'disposition', ['id' => '\d+']);

        // Public citable landing (no auth) — a DOI resolves here. Metadata +
        // citation + access badge only; binaries stay gated by the disposition.
        $r->any('rdm_datasets_landing', '/research/datasets/:id/landing', 'landing', ['id' => '\d+']);

        $r->register($routing);
    }

    public static function getPluginInfo()
    {
        return [
            'name' => 'RDM Manager',
            'version' => self::$version,
            'description' => self::$summary,
            'author' => 'The Archive and Heritage Group (Pty) Ltd',
            'features' => [
                'Dataset wrapper over a container information_object',
                'Per-file deposit as child IO + master digital_object',
                'POPIA scan / human gate / DOI / dashboard (later phases)',
            ],
        ];
    }
}
