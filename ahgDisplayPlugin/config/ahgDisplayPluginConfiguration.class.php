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
        $enabledModules[] = 'ahgDisplay';
        $enabledModules[] = 'ahgDisplaySearch';
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

        // Load routes from routing.yml
        $routingFile = __DIR__ . '/routing.yml';
        if (file_exists($routingFile)) {
            $routes = sfYaml::load($routingFile);
            if (is_array($routes)) {
                foreach ($routes as $name => $config) {
                    if (isset($config['url']) && isset($config['param'])) {
                        $routing->prependRoute($name, new sfRoute(
                            $config['url'],
                            $config['param']
                        ));
                    }
                }
            }
        }

        // Core display routes - no theme dependency (fallback)
        $routing->prependRoute('display_browse', new sfRoute(
            '/display/browse',
            ['module' => 'ahgDisplay', 'action' => 'browse']
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
