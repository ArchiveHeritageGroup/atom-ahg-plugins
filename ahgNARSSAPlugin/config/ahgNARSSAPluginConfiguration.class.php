<?php

/**
 * ahgNARSSAPlugin configuration.
 *
 * Closes GCIS RFB-001 gap: NARSSA transfer manifest export.
 * Operates downstream of ahgExtendedRightsPlugin's disposal workflow when
 * disposal_action.action_type = 'transfer_narssa'.
 */
class ahgNARSSAPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'South African NARSSA Act 1996 transfer manifest generator';
    public static $version = '0.1.0';

    public function initialize(): void
    {
        $this->registerAutoloader();

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        if (!in_array('narssa', $enabledModules, true)) {
            $enabledModules[] = 'narssa';
            sfConfig::set('sf_enabled_modules', $enabledModules);
        }
    }

    /** PSR-4 autoloader for the AhgNARSSA\ namespace. */
    protected function registerAutoloader(): void
    {
        $pluginDir = realpath(__DIR__ . '/..');
        spl_autoload_register(static function (string $class) use ($pluginDir): void {
            $prefix = 'AhgNARSSA\\';
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
