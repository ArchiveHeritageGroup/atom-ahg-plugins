<?php

/**
 * ahgRecordsManagePlugin configuration.
 *
 * Records-management file plan / classification scheme (#118). Registers a
 * PSR-4 autoloader for AhgRecordsManage\ and enables the recordsManage module.
 */
class ahgRecordsManagePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Records-management file plan / classification scheme';
    public static $version = '0.1.0';

    public function initialize(): void
    {
        $this->registerAutoloader();

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        if (!in_array('recordsManage', $enabledModules, true)) {
            $enabledModules[] = 'recordsManage';
            sfConfig::set('sf_enabled_modules', $enabledModules);
        }
    }

    /** PSR-4 autoloader for the AhgRecordsManage\ namespace. */
    protected function registerAutoloader(): void
    {
        $pluginDir = realpath(__DIR__ . '/..');
        spl_autoload_register(static function (string $class) use ($pluginDir): void {
            $prefix = 'AhgRecordsManage\\';
            if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
                return;
            }
            $relative = substr($class, strlen($prefix));
            $path = $pluginDir . '/lib/' . str_replace('\\', '/', $relative) . '.php';
            if (is_file($path)) {
                require_once $path;
            }
        });
    }
}
