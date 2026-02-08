<?php

class ahgActorBrowsePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'High-performance actor browse and autocomplete';
    public static $version = '1.0.0';

    public function initialize()
    {
        $this->registerAutoloader();

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'actorBrowse';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgActorBrowse\\') === 0) {
                $relativePath = str_replace('AhgActorBrowse\\', '', $class);
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

    public function loadRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

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
    }
}
