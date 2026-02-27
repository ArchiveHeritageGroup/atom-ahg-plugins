<?php

/**
 * ahgGISPlugin — Configuration
 *
 * Geospatial search and GeoJSON export for heritage records.
 */
class ahgGISPluginConfiguration extends sfPluginConfiguration
{
    const VERSION = '0.1.0';

    public function initialize()
    {
        // PSR-4 autoloader for plugin classes
        spl_autoload_register(function ($class) {
            $prefix = 'AhgGIS\\';
            if (0 !== strpos($class, $prefix)) {
                return;
            }
            $relative = substr($class, strlen($prefix));
            $file = __DIR__ . '/../lib/' . str_replace('\\', '/', $relative) . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        });

        // Enable the gis module
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        if (!in_array('gis', $enabledModules)) {
            $enabledModules[] = 'gis';
            sfConfig::set('sf_enabled_modules', $enabledModules);
        }

        // Register routes
        $this->dispatcher->connect('routing.load_configuration', [$this, 'onRoutingLoadConfiguration']);
    }

    public function onRoutingLoadConfiguration(sfEvent $event)
    {
        if (!class_exists('AtomFramework\\Routing\\RouteLoader')) {
            return;
        }

        $routing = $event->getSubject();
        $loader = new \AtomFramework\Routing\RouteLoader('gis');

        // Bounding box search (JSON API)
        $loader->get('gis_bbox', '/gis/bbox', 'bbox');

        // Radius search (JSON API)
        $loader->get('gis_radius', '/gis/radius', 'radius');

        // GeoJSON export
        $loader->get('gis_geojson', '/gis/geojson', 'geojson');

        $loader->register($routing);
    }

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
                // Index may already exist
            }
        }
    }
}
