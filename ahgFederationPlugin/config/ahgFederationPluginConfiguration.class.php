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
        $routing = $event->getSubject();

        // Federation admin dashboard
        $routing->prependRoute('federation_index', new sfRoute(
            '/admin/federation',
            ['module' => 'federation', 'action' => 'index']
        ));

        // Peer management
        $routing->prependRoute('federation_peers', new sfRoute(
            '/admin/federation/peers',
            ['module' => 'federation', 'action' => 'peers']
        ));

        $routing->prependRoute('federation_peer_edit', new sfRoute(
            '/admin/federation/peers/:id',
            ['module' => 'federation', 'action' => 'editPeer'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('federation_peer_add', new sfRoute(
            '/admin/federation/peers/add',
            ['module' => 'federation', 'action' => 'addPeer']
        ));

        // Harvesting
        $routing->prependRoute('federation_harvest', new sfRoute(
            '/admin/federation/harvest/:peerId',
            ['module' => 'federation', 'action' => 'harvest'],
            ['peerId' => '\d+']
        ));

        $routing->prependRoute('federation_harvest_status', new sfRoute(
            '/admin/federation/harvest/:peerId/status',
            ['module' => 'federation', 'action' => 'harvestStatus'],
            ['peerId' => '\d+']
        ));

        // Harvest log
        $routing->prependRoute('federation_log', new sfRoute(
            '/admin/federation/log',
            ['module' => 'federation', 'action' => 'log']
        ));

        // API endpoints for AJAX
        $routing->prependRoute('federation_api_test_peer', new sfRoute(
            '/admin/federation/api/test-peer',
            ['module' => 'federation', 'action' => 'testPeer']
        ));

        $routing->prependRoute('federation_api_harvest_run', new sfRoute(
            '/admin/federation/api/harvest/:peerId',
            ['module' => 'federation', 'action' => 'runHarvest'],
            ['peerId' => '\d+']
        ));
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
