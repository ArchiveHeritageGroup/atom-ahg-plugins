<?php

/**
 * ahgFederationPlugin Configuration
 *
 * Provides OAI-PMH federation capabilities including:
 * - Heritage metadata format for OAI-PMH export
 * - Harvesting client for ingesting from peer OAI-PMH sources
 * - Provenance tracking for harvested records
 */
class ahgFederationPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'OAI-PMH Federation plugin for Heritage Platform metadata exchange';
    public static $version = '1.0.0';

    /**
     * Plugin initialization
     */
    public function initialize()
    {
        // Register autoloader for plugin classes
        $this->registerAutoloader();

        // Register Heritage metadata format with OAI
        $this->dispatcher->connect('context.load_factories', [$this, 'registerOaiFormat']);

        // Add routing for harvest management
        $this->dispatcher->connect('routing.load_configuration', [$this, 'configureRouting']);

        // Enable federation module
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'federation';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }

    /**
     * Register PSR-4 autoloader for plugin classes
     */
    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            // Handle AhgFederation namespace
            if (strpos($class, 'AhgFederation\\') === 0) {
                $relativePath = str_replace('AhgFederation\\', '', $class);
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

    /**
     * Register Heritage metadata format with OAI-PMH
     */
    public function registerOaiFormat(sfEvent $event)
    {
        // Load the Heritage format class
        require_once __DIR__ . '/../lib/OaiHeritageMetadataFormat.php';
        require_once __DIR__ . '/../lib/OaiHeritageSet.php';

        // Register the Heritage metadata format
        \AhgFederation\OaiHeritageMetadataFormat::register();

        // Register custom OAI sets for federation
        $useAdditionalSets = QubitSetting::getByName('oai_additional_sets_enabled');
        if ($useAdditionalSets && $useAdditionalSets->value) {
            QubitOai::addOaiSet(new \AhgFederation\OaiHeritageSet());
        }
    }

    /**
     * Configure routes for federation admin
     */
    public function configureRouting(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('federation');

        // Federation admin dashboard
        $router->any('federation_index', '/admin/federation', 'index');

        // Peer management
        $router->any('federation_peers', '/admin/federation/peers', 'peers');
        $router->any('federation_peer_edit', '/admin/federation/peers/:id', 'editPeer', ['id' => '\d+']);
        $router->any('federation_peer_add', '/admin/federation/peers/add', 'addPeer');

        // Harvesting
        $router->any('federation_harvest', '/admin/federation/harvest/:peerId', 'harvest', ['peerId' => '\d+']);
        $router->any('federation_harvest_status', '/admin/federation/harvest/:peerId/status', 'harvestStatus', ['peerId' => '\d+']);

        // Harvest log
        $router->any('federation_log', '/admin/federation/log', 'log');

        // API endpoints for AJAX
        $router->any('federation_api_test_peer', '/admin/federation/api/test-peer', 'testPeer');
        $router->any('federation_api_harvest_run', '/admin/federation/api/harvest/:peerId', 'runHarvest', ['peerId' => '\d+']);

        $router->register($event->getSubject());
    }

    /**
     * Get plugin root path
     */
    public static function getPluginPath(): string
    {
        return dirname(__DIR__);
    }

    /**
     * Get lib path
     */
    public static function getLibPath(): string
    {
        return dirname(__DIR__) . '/lib';
    }

    /**
     * Get web assets path
     */
    public static function getWebPath(): string
    {
        return dirname(__DIR__) . '/web';
    }

    /**
     * Context load factories handler - add assets
     */
    public function contextLoadFactories(sfEvent $event)
    {
        $context = $event->getSubject();

        // Add CSS
        $context->response->addStylesheet('/plugins/ahgFederationPlugin/web/css/federation.css', 'last');

        // Add JS
        $context->response->addJavaScript('/plugins/ahgFederationPlugin/web/js/federation.js', 'last');
    }
}
