<?php

/**
 * ahgCorePlugin Configuration
 *
 * Core utilities plugin for AHG extensions.
 * Provides shared services and contracts for all AHG plugins.
 */
class ahgCorePluginConfiguration extends sfPluginConfiguration
{
    /**
     * Plugin initialization
     */
    public function initialize()
    {
        // Register autoloader for AhgCore namespace
        $this->registerAutoloader();

        // Register global error notification handler
        \AhgCore\Services\ErrorNotificationService::register();
    }

    /**
     * Register PSR-4 autoloader for plugin classes
     */
    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            // Handle AhgCore namespace
            if (strpos($class, 'AhgCore\\') === 0) {
                $relativePath = str_replace('AhgCore\\', '', $class);
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
}
