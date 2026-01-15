<?php
class ahgSpectrumPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Spectrum 5.0 Museum Procedures Plugin';
    public static $version = '1.0.1';
    
    // Hard dependencies - none, this is the base
    public static $dependencies = [];
    
    // Plugins that depend on this one - will be disabled if this is disabled
    public static $dependents = ['ahgMuseumPlugin'];
    
    public function initialize()
    {
        // Routes are defined in routing.yml - no duplicates needed here
        
        // Enable modules
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'spectrum';
        $enabledModules[] = 'api';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }
}
