<?php
/**
 * ahgRicExplorerPlugin
 * 
 * Records in Context (RiC-O) Explorer plugin for AtoM.
 * 
 * Features:
 * - Semantic search with natural language queries
 * - Interactive graph visualization (2D/3D)
 * - GRAP 103 compliance dashboard
 * - SPARQL query interface
 * 
 * @package    ahgRicExplorerPlugin
 * @author     The AHG
 * @version    1.0.0
 */

class ahgRicExplorerPlugin
{
    /**
     * Plugin version
     */
    const VERSION = '1.0.0';
    
    /**
     * Register plugin hooks
     */
    public static function register()
    {
        // Register event listeners
        $dispatcher = sfContext::getInstance()->getEventDispatcher();
        
        // Add menu items
        $dispatcher->connect('menu.main', ['ahgRicExplorerPlugin', 'onMenuMain']);
        
        // Add sidebar widget on information object view
        $dispatcher->connect('informationobject.show', ['ahgRicExplorerPlugin', 'onInformationObjectShow']);
        
        // Add admin menu
        $dispatcher->connect('menu.admin', ['ahgRicExplorerPlugin', 'onMenuAdmin']);
    }
    
    /**
     * Add items to main navigation menu
     */
    public static function onMenuMain(sfEvent $event)
    {
        $menu = $event->getSubject();
        
        // Add semantic search to main menu
        if (sfConfig::get('app_ric_enable_search_widget', true)) {
            $menu->addChild('ricSearch', [
                'label' => 'Semantic Search',
                'route' => '@ric_semantic_search',
                'attributes' => ['class' => 'ric-menu-item']
            ]);
        }
    }
    
    /**
     * Add RiC panel to information object view
     */
    public static function onInformationObjectShow(sfEvent $event)
    {
        if (sfConfig::get('app_ric_enable_explorer_panel', true)) {
            $response = sfContext::getInstance()->getResponse();
            
            // Include panel component
            $response->addSlot('ricExplorerPanel',
                get_component('ricExplorer', 'ricPanel', [
                    'resource' => $event['resource']
                ])
            );
        }
    }
    
    /**
     * Add items to admin menu
     */
    public static function onMenuAdmin(sfEvent $event)
    {
        $menu = $event->getSubject();
        
        $ricMenu = $menu->addChild('ric', [
            'label' => 'RiC Explorer',
            'attributes' => ['class' => 'dropdown-submenu']
        ]);
        
        $ricMenu->addChild('ricSearch', [
            'label' => 'Semantic Search',
            'route' => '@ric_semantic_search'
        ]);
        
        $ricMenu->addChild('ricExplorer', [
            'label' => 'Graph Explorer',
            'route' => '@ric_explorer'
        ]);
        
        $ricMenu->addChild('grapDashboard', [
            'label' => 'GRAP Dashboard',
            'uri' => sfConfig::get('app_grap_dashboard_url', '/grap/')
        ]);
    }
    
    /**
     * Get Fuseki endpoint URL
     */
    public static function getFusekiEndpoint()
    {
        return sfConfig::get('app_ric_fuseki_endpoint', 'http://localhost:3030/ric');
    }
    
    /**
     * Get search API URL
     */
    public static function getSearchApiUrl()
    {
        return sfConfig::get('app_ric_search_api', 'http://localhost:5001/api');
    }
    
    /**
     * Execute SPARQL query
     */
    public static function executeSparql($query)
    {
        $endpoint = self::getFusekiEndpoint() . '/query';
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $query,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/sparql-query',
                'Accept: application/json'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return null;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Build RiC URI for an AtoM entity
     */
    public static function buildRicUri($entityType, $id)
    {
        $baseUri = sfConfig::get('app_ric_base_uri', 'https://archives.theahg.co.za/ric');
        $instance = sfConfig::get('app_ric_instance_id', 'atom');
        
        return "{$baseUri}/{$instance}/{$entityType}/{$id}";
    }
}

// Auto-register on plugin load
ahgRicExplorerPlugin::register();
