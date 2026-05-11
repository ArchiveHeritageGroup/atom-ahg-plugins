<?php

/**
 * ahgTimeLimitedShareLinkPlugin Configuration
 *
 * Phase A — schema scaffolding only. Listeners + routes register in subsequent
 * phases (B onwards).
 */
class ahgTimeLimitedShareLinkPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Time-limited, auditable share links for information_object records';
    public static $version = '0.1.0';

    public function initialize(): void
    {
        $this->registerAutoloader();

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        if (!in_array('shareLink', $enabledModules, true)) {
            $enabledModules[] = 'shareLink';
            sfConfig::set('sf_enabled_modules', $enabledModules);
        }

        // Phase D — register the public recipient route.
        // Phase E — register the authenticated issue endpoint + UI injector.
        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);

        $injector = new \AhgShareLink\Listeners\ViewLinkInjector();
        $this->dispatcher->connect('response.filter_content', [$injector, 'onResponseFilterContent']);
    }

    public function loadRoutes(\sfEvent $event): void
    {
        $r = $event->getSubject();
        $r->prependRoute('share_link_recipient', new \sfRoute(
            '/share/:token',
            ['module' => 'shareLink', 'action' => 'recipient'],
            ['token' => '[A-Za-z0-9_\-]{32,64}'],
        ));
        $r->prependRoute('share_link_issue', new \sfRoute(
            '/shareLink/issue',
            ['module' => 'shareLink', 'action' => 'issue'],
            [],
        ));
        // Phase F — admin index + detail (admin auth gated in the action).
        // Phase G — revoke action.
        $r->prependRoute('share_link_admin_revoke', new \sfRoute(
            '/admin/share-links/:id/revoke',
            ['module' => 'shareLink', 'action' => 'revoke'],
            ['id' => '\d+'],
        ));
        $r->prependRoute('share_link_admin_show', new \sfRoute(
            '/admin/share-links/:id',
            ['module' => 'shareLink', 'action' => 'adminShow'],
            ['id' => '\d+'],
        ));
        $r->prependRoute('share_link_admin', new \sfRoute(
            '/admin/share-links',
            ['module' => 'shareLink', 'action' => 'admin'],
            [],
        ));
    }

    protected function registerAutoloader(): void
    {
        $pluginDir = realpath(__DIR__ . '/..');
        spl_autoload_register(static function (string $class) use ($pluginDir): void {
            $prefix = 'AhgShareLink\\';
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
