<?php

/**
 * ahgDiscoveryPlugin - Configuration
 *
 * Topic discovery and related content across collections using
 * NER entities, synonym expansion, and hierarchical context.
 */
class ahgDiscoveryPluginConfiguration extends sfPluginConfiguration
{
    const VERSION = '0.2.0';

    public function initialize()
    {
        // PSR-4 style autoloader for plugin classes
        spl_autoload_register(function ($class) {
            $prefix = 'AhgDiscovery\\';
            if (0 !== strpos($class, $prefix)) {
                return;
            }
            $relative = substr($class, strlen($prefix));
            $file = __DIR__ . '/../lib/' . str_replace('\\', '/', $relative) . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        });

        // Enable the discovery module
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        if (!in_array('discovery', $enabledModules)) {
            $enabledModules[] = 'discovery';
            sfConfig::set('sf_enabled_modules', $enabledModules);
        }

        // Register routes
        $this->dispatcher->connect('routing.load_configuration', [$this, 'onRoutingLoadConfiguration']);
    }

    /**
     * Register plugin routes via RouteLoader.
     */
    public function onRoutingLoadConfiguration(sfEvent $event)
    {
        if (!class_exists('AtomFramework\\Routing\\RouteLoader')) {
            return;
        }

        $routing = $event->getSubject();
        $loader = new \AtomFramework\Routing\RouteLoader('discovery');

        // Note: prependRoute order — LAST defined = checked FIRST
        // So catch-all / main page must be defined FIRST (checked last)

        // Main discovery page (catch-all — defined first, checked last)
        $loader->get('discovery_index', '/discovery', 'index');

        // Specific routes (defined last, checked first)
        $loader->get('discovery_search', '/discovery/search', 'search');
        $loader->get('discovery_related', '/discovery/related/:id', 'related', ['id' => '\d+']);
        $loader->any('discovery_click', '/discovery/click', 'click');
        $loader->get('discovery_popular', '/discovery/popular', 'popular');

        $loader->register($routing);
    }

    /**
     * Install database tables.
     */
    public function install()
    {
        $sqlFile = __DIR__ . '/../database/install.sql';
        if (!file_exists($sqlFile)) {
            return;
        }

        $sql = file_get_contents($sqlFile);
        $conn = \Propel::getConnection();
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($s) => !empty($s) && 0 !== strpos(trim($s), '--')
        );

        foreach ($statements as $stmt) {
            try {
                $conn->exec($stmt);
            } catch (\Exception $e) {
                // Table may already exist
            }
        }
    }
}
