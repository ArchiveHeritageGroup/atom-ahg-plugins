<?php

/**
 * ahgContactPlugin configuration.
 */
class ahgContactPluginConfiguration extends sfPluginConfiguration
{
    public function initialize()
    {
        // Register autoloader for Contact extension classes immediately
        $this->registerAutoloader();

        // Also connect to context.load_factories for any late initialization
        $this->dispatcher->connect('context.load_factories', [$this, 'loadContact']);
    }

    /**
     * Register PSR-4 style autoloader for plugin classes
     */
    protected function registerAutoloader()
    {
        $libPath = sfConfig::get('sf_plugins_dir') . '/ahgContactPlugin/lib';

        spl_autoload_register(function ($class) use ($libPath) {
            // Handle AtomFramework\Extensions\Contact namespace
            $prefix = 'AtomFramework\\Extensions\\Contact\\';
            if (strpos($class, $prefix) === 0) {
                $relativeClass = substr($class, strlen($prefix));
                $file = $libPath . '/Extensions/Contact/' . str_replace('\\', '/', $relativeClass) . '.php';
                if (file_exists($file)) {
                    require_once $file;
                    return true;
                }
            }
            return false;
        });
    }

    public function loadContact(sfEvent $event)
    {
        // Classes are now autoloaded, this is kept for any additional initialization
    }
}
