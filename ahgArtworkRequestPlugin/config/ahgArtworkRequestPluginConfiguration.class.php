<?php

/**
 * ahgArtworkRequestPlugin configuration.
 *
 * Staff requests to place artworks in offices and shared spaces.
 */
class ahgArtworkRequestPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Staff requests to place artworks in offices and shared spaces';
    public static $version = '0.1.0';

    public function initialize()
    {
        // PSR-4 for this plugin's namespace. Registered here rather than relying
        // on the framework autoloader so the plugin works on an install that has
        // only the runtime.
        spl_autoload_register(static function ($class) {
            if (0 !== strpos($class, 'AhgArtworkRequest\\')) {
                return;
            }

            $path = __DIR__.'/../lib/'.str_replace('\\', '/', substr($class, strlen('AhgArtworkRequest\\'))).'.php';

            if (file_exists($path)) {
                require_once $path;
            }
        });

        // Contribute the navigation entry rather than waiting for a theme to
        // name it, so the plugin is reachable on stock AtoM and disappears
        // cleanly when it is disabled.
        if (class_exists('AhgNav')) {
            AhgNav::register('manage', 'artworkRequest', [
                'url' => '/index.php/artworkRequest/review',
                'label' => 'Artwork requests',
                'credentials' => ['editor', 'administrator'],
                'weight' => 55,
            ]);
        }

        $enabledModules = sfConfig::get('sf_enabled_modules', []);

        if (!in_array('artworkRequest', $enabledModules, true)) {
            $enabledModules[] = 'artworkRequest';
            sfConfig::set('sf_enabled_modules', $enabledModules);
        }
    }
}
