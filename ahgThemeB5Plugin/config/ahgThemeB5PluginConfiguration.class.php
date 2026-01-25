<?php
class ahgThemeB5PluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'AHG Bootstrap 5 Theme for AtoM';
    public static $version = '2.1.0';

    public function initialize()
    {
        // Register template directory for layout override
        $decoratorDirs = sfConfig::get('sf_decorator_dirs');
        $decoratorDirs[] = sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/templates';
        sfConfig::set('sf_decorator_dirs', $decoratorDirs);

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
    }
}
