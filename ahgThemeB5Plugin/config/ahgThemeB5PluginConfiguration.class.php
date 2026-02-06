<?php

class ahgThemeB5PluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'AHG Bootstrap 5 Theme for AtoM';
    public static $version = '2.1.1';

    public function initialize()
    {
        $pluginsDir = sfConfig::get('sf_plugins_dir');
        $themePath = $pluginsDir.'/ahgThemeB5Plugin/templates';

        // Register template directory for layout override
        // Add to the FRONT for highest priority, avoiding duplicates
        $decoratorDirs = sfConfig::get('sf_decorator_dirs') ?: [];

        // Remove any existing entries for this theme (prevents duplicates)
        $decoratorDirs = array_filter($decoratorDirs, function ($dir) use ($themePath) {
            return $dir !== $themePath;
        });

        // Add theme templates at the FRONT for highest priority
        array_unshift($decoratorDirs, $themePath);
        sfConfig::set('sf_decorator_dirs', array_values($decoratorDirs));

        // Move this plugin to the top to allow overwriting
        // controllers and views from other plugin modules.
        $plugins = $this->configuration->getPlugins();
        if (false !== $key = array_search('ahgThemeB5Plugin', $plugins)) {
            unset($plugins[$key]);
        }
        $this->configuration->setPlugins(
            array_merge(['ahgThemeB5Plugin'], $plugins)
        );

        // Indicate this is a Bootstrap 5 theme
        sfConfig::set('app_b5_theme', true);

        // Check for and warn about competing themes
        $this->checkCompetingThemes($plugins, $pluginsDir);
    }

    /**
     * Check for competing themes and log warnings.
     */
    protected function checkCompetingThemes(array $plugins, string $pluginsDir)
    {
        $competingThemes = [];

        // Check loaded plugins for themes
        foreach ($plugins as $plugin) {
            if ($plugin !== 'ahgThemeB5Plugin' && stripos($plugin, 'Theme') !== false) {
                $competingThemes[] = $plugin;
            }
        }

        // Also check plugins directory for non-symlinked theme directories
        // These might override even if not in the plugins list
        if (is_dir($pluginsDir)) {
            $entries = scandir($pluginsDir);
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..' || $entry === 'ahgThemeB5Plugin') {
                    continue;
                }

                $path = $pluginsDir.'/'.$entry;

                // Look for theme plugins that are real directories (not symlinks to atom-ahg-plugins)
                if (stripos($entry, 'Theme') !== false && is_dir($path)) {
                    // Check if it's a symlink pointing to atom-ahg-plugins (those are fine)
                    if (is_link($path)) {
                        $target = readlink($path);
                        if (strpos($target, 'atom-ahg-plugins') !== false) {
                            continue; // AHG plugin symlink, OK
                        }
                    }

                    // Real directory or symlink to elsewhere - potential conflict
                    if (!in_array($entry, $competingThemes)) {
                        $competingThemes[] = $entry.' (in plugins dir)';
                    }
                }
            }
        }

        if (!empty($competingThemes)) {
            error_log('ahgThemeB5Plugin: WARNING - Competing themes detected: '.implode(', ', $competingThemes).'. This may cause rendering issues.');

            // Also set a flag that can be checked by the diagnostic task
            sfConfig::set('app_ahg_theme_competing', $competingThemes);
        }
    }
}
