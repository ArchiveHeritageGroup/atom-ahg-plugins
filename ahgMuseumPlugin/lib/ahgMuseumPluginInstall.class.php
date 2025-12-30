<?php
// plugins/ahgMuseumPlugin/lib/ahgMuseumPluginInstall.class.php

class ahgMuseumPluginInstall
{
    /**
     * Install plugin routes into AtoM
     */
    public static function installRoutes()
    {
        $pluginDir = sfConfig::get('sf_plugins_dir') . '/ahgMuseumPlugin';
        $routingFile = sfConfig::get('sf_app_dir') . '/config/routing.yml';
        $pluginRouting = $pluginDir . '/config/routing.yml';
        
        if (!file_exists($pluginRouting)) {
            throw new Exception('Plugin routing.yml not found');
        }
        
        $mainRouting = file_get_contents($routingFile);
        
        // Check if already installed
        if (strpos($mainRouting, 'ahgMuseumPlugin Routes') !== false) {
            return 'Routes already installed';
        }
        
        // Append plugin routes
        $pluginRoutes = file_get_contents($pluginRouting);
        $mainRouting .= "\n\n# =============================================\n";
        $mainRouting .= "# ahgMuseumPlugin Routes (auto-installed)\n";
        $mainRouting .= "# =============================================\n";
        $mainRouting .= $pluginRoutes;
        
        file_put_contents($routingFile, $mainRouting);
        
        return 'Routes installed successfully';
    }
    
    /**
     * Remove plugin routes from AtoM
     */
    public static function removeRoutes()
    {
        $routingFile = sfConfig::get('sf_app_dir') . '/config/routing.yml';
        $content = file_get_contents($routingFile);
        
        // Remove plugin routes section
        $pattern = '/\n*# =+\n# ahgMuseumPlugin Routes.*?(?=\n# =|\z)/s';
        $content = preg_replace($pattern, '', $content);
        
        file_put_contents($routingFile, $content);
        
        return 'Routes removed';
    }
}