<?php

class ahgRightsPluginConfiguration extends sfPluginConfiguration
{
    /** Shown by AtoM's stock plugin admin; omitted plugins are hidden there. */
    public static $summary = 'Rights statements, PREMIS rights and Creative Commons licensing';

    public function initialize()
    {
        // Register autoloader for plugin namespace
        spl_autoload_register(function ($class) {
            if (strpos($class, 'Plugins\\ahgRightsPlugin\\') === 0) {
                $path = str_replace('Plugins\\ahgRightsPlugin\\', '', $class);
                $path = str_replace('\\', '/', $path);
                $file = sfConfig::get('sf_plugins_dir') . '/ahgRightsPlugin/lib/' . $path . '.php';
                if (file_exists($file)) {
                    require_once $file;
                    return true;
                }
            }
            return false;
        });
    }
}
