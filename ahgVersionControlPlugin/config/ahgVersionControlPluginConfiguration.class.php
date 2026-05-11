<?php

/**
 * ahgVersionControlPlugin Configuration
 *
 * Version history with diff and restore for information_object and actor.
 * Phase A — schema scaffolding only. Listeners and routes wire up in later phases.
 */
class ahgVersionControlPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Version history with diff and restore for information_object and actor';
    public static $version = '0.1.0';

    public function initialize(): void
    {
        $this->registerAutoloader();

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        if (!in_array('versionControl', $enabledModules, true)) {
            $enabledModules[] = 'versionControl';
            sfConfig::set('sf_enabled_modules', $enabledModules);
        }

        // Phase D — hook the response filter for post-save version capture.
        // Base AtoM does not dispatch model-save events, so we mirror the
        // approach used by ahgAuditTrailPlugin: inspect the just-completed
        // request and decide whether it was an entity save.
        $listener = new \AhgVersionControl\Listeners\SaveListener();
        $this->dispatcher->connect('response.filter_content', [$listener, 'onResponseFilterContent']);

        // Phase G — inject a "Version history (N)" banner on the legacy IO/actor
        // view page. Listener runs after SaveListener so any just-written version
        // is included in the count.
        $injector = new \AhgVersionControl\Listeners\ViewLinkInjector();
        $this->dispatcher->connect('response.filter_content', [$injector, 'onResponseFilterContent']);

        // Phase F — register UI routes.
        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);
    }

    public function loadRoutes(\sfEvent $event): void
    {
        $r = $event->getSubject();
        $entityPattern = '(information_object|actor)';
        $r->prependRoute('version_control_restore', new \sfRoute(
            '/version-control/:entity/:id/:number/restore',
            ['module' => 'versionControl', 'action' => 'restore'],
            ['entity' => $entityPattern, 'id' => '\d+', 'number' => '\d+'],
        ));
        $r->prependRoute('version_control_show', new \sfRoute(
            '/version-control/:entity/:id/:number',
            ['module' => 'versionControl', 'action' => 'show'],
            ['entity' => $entityPattern, 'id' => '\d+', 'number' => '\d+'],
        ));
        $r->prependRoute('version_control_diff', new \sfRoute(
            '/version-control/:entity/:id/diff/:v1/:v2',
            ['module' => 'versionControl', 'action' => 'diff'],
            ['entity' => $entityPattern, 'id' => '\d+', 'v1' => '\d+', 'v2' => '\d+'],
        ));
        $r->prependRoute('version_control_list', new \sfRoute(
            '/version-control/:entity/:id',
            ['module' => 'versionControl', 'action' => 'list'],
            ['entity' => $entityPattern, 'id' => '\d+'],
        ));
    }

    /**
     * PSR-4 autoloader for AhgVersionControl\ namespace.
     */
    protected function registerAutoloader(): void
    {
        $pluginDir = realpath(__DIR__ . '/..');
        spl_autoload_register(static function (string $class) use ($pluginDir): void {
            $prefix = 'AhgVersionControl\\';
            if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
                return;
            }
            $relative = substr($class, strlen($prefix));
            $path = $pluginDir . '/lib/' . str_replace('\\', '/', $relative) . '.php';
            if (is_file($path)) {
                require_once $path;
            }
        });
    }
}
