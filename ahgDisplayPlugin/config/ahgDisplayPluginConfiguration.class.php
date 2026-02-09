<?php

class ahgDisplayPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Context-aware display system for archives, museums, galleries, libraries and DAM';
    public static $version = '1.0.0';

    public function initialize()
    {
        // Register autoloader for AhgDisplay namespace
        $this->registerAutoloader();

        // Initialize the display action registry
        $this->initializeRegistry();

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);
        $this->dispatcher->connect('template.filter_parameters', [$this, 'onTemplateFilterParameters']);
        $this->dispatcher->connect('QubitInformationObject.save', [$this, 'onInformationObjectSave']);
        $this->dispatcher->connect('QubitInformationObject.insert', [$this, 'onInformationObjectSave']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'display';
        $enabledModules[] = 'displaySearch';
        $enabledModules[] = 'treeview';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    /**
     * Register PSR-4 autoloader for plugin classes
     */
    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgDisplay\\') === 0) {
                $relativePath = str_replace('AhgDisplay\\', '', $class);
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
     * Initialize the display action registry
     */
    protected function initializeRegistry()
    {
        try {
            require_once __DIR__ . '/../lib/Registry/DisplayActionRegistry.php';
            \AhgDisplay\Registry\DisplayActionRegistry::init();
        } catch (Exception $e) {
            error_log('ahgDisplayPlugin: Failed to initialize registry: ' . $e->getMessage());
        }
    }

    public function loadRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        // Display module routes
        $display = new \AtomFramework\Routing\RouteLoader('display');
        $display->any('informationobject_browse_override', '/informationobject/browse', 'browse');
        $display->any('glam_index', '/glam', 'index');
        $display->any('glam_profiles', '/glam/profiles', 'profiles');
        $display->any('glam_levels', '/glam/levels', 'levels');
        $display->any('glam_fields', '/glam/fields', 'fields');
        $display->any('glam_set_type', '/glam/setType', 'setType');
        $display->any('glam_assign_profile', '/glam/assignProfile', 'assignProfile');
        $display->any('glam_bulk_set_type', '/glam/bulkSetType', 'bulkSetType');
        $display->any('glam_browse', '/glam/browse', 'browse');
        $display->any('glam_browse_ajax', '/glam/browseAjax', 'browseAjax');
        $display->any('glam_print', '/glam/print', 'print');
        $display->any('glam_export_csv', '/glam/exportCsv', 'exportCsv');
        $display->any('glam_change_type', '/glam/changeType', 'changeType');
        $display->any('glam_browse_settings', '/glam/settings', 'browseSettings');
        $display->any('glam_toggle_glam_browse', '/glam/toggleGlamBrowse', 'toggleGlamBrowse');
        $display->any('glam_save_browse_settings', '/glam/saveBrowseSettings', 'saveBrowseSettings');
        $display->any('glam_get_browse_settings', '/glam/getBrowseSettings', 'getBrowseSettings');
        $display->any('glam_reset_browse_settings', '/glam/resetBrowseSettings', 'resetBrowseSettings');
        $display->register($routing);

        // Display search module routes
        $displaySearch = new \AtomFramework\Routing\RouteLoader('displaySearch');
        $displaySearch->any('glam_search', '/glam/search', 'search');
        $displaySearch->any('glam_search_results', '/glam/search/results', 'browse');
        $displaySearch->register($routing);

        // Treeview routes (QubitResourceRoute - registered directly)
        $routing->prependRoute('treeview_override', new \QubitResourceRoute(
            '/:slug/treeView',
            ['module' => 'treeview', 'action' => 'view'],
            ['slug' => '[a-zA-Z0-9_-]+']
        ));
        $routing->prependRoute('treeview_sort_override', new \QubitResourceRoute(
            '/:slug/treeViewSort',
            ['module' => 'treeview', 'action' => 'sort'],
            ['slug' => '[a-zA-Z0-9_-]+']
        ));

        // Core display routes - no theme dependency (fallback)
        $routing->prependRoute('display_browse', new \sfRoute(
            '/display/browse',
            ['module' => 'display', 'action' => 'browse']
        ));
    }

    public function onTemplateFilterParameters(sfEvent $event, $parameters)
    {
        if (!isset($parameters['resource']) || (!isset($parameters['resource']->id))) {
            return $parameters;
        }
        try {
            require_once __DIR__ . '/../lib/Services/DisplayTypeDetector.php';
            $objectId = (int) $parameters['resource']->id;
            if ($objectId > 1) {
                $parameters['display_type'] = DisplayTypeDetector::detect($objectId);
                $parameters['display_profile'] = DisplayTypeDetector::getProfile($objectId);
            }
        } catch (Exception $e) {
            error_log('ahgDisplayPlugin: ' . $e->getMessage());
        }
        return $parameters;
    }

    public function onInformationObjectSave(sfEvent $event)
    {
        $object = $event->getSubject();
        if (!$object || (!isset($object->id)) || $object->id <= 1) {
            return;
        }
        try {
            require_once __DIR__ . '/../lib/Services/DisplayTypeDetector.php';
            DisplayTypeDetector::detectAndSave((int) $object->id, true);
        } catch (Exception $e) {
            error_log('ahgDisplayPlugin save: ' . $e->getMessage());
        }
    }
}
