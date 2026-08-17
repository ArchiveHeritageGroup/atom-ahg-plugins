<?php

/**
 * ahgContactPlugin configuration.
 */
class ahgContactPluginConfiguration extends sfPluginConfiguration
{
    /** Shown by AtoM's stock plugin admin; omitted plugins are hidden there. */
    public static $summary = 'Extended contact information for actors and repositories';

    /**
     * Required, not decorative.
     *
     * sfPluginAdminPlugin's pluginsSuccess.php renders `$plugin::$version` for
     * every listed plugin. In PHP 8 reading an undeclared static property is a
     * fatal Error, so a plugin with $summary but no $version kills the render
     * part way down the page - the rows above it appear, everything below is
     * lost, and the save button with it.
     *
     * That is not a cosmetic gap: with no save button, no plugin on the whole
     * instance can be enabled or disabled through the interface. It blocked the
     * archaeology production install on 2026-08-17, presenting as "the plugins
     * page is broken" with nothing in any log, because AtoM renders the partial
     * page as a normal 200.
     *
     * Every AHG plugin configuration class needs both properties.
     */
    public static $version = '1.0.0';

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
