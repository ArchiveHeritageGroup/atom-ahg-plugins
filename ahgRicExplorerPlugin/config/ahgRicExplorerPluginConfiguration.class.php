<?php

/*
 * RiC Explorer Plugin for AtoM
 * 
 * Provides Records in Context (RiC) visualization and exploration
 * capabilities integrated into AtoM's description view.
 *
 * @package    ahgRicExplorerPlugin
 * @author     The AHG / Plain Sailing
 * @version    1.0.0
 */

class ahgRicExplorerPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Records in Context (RiC) visualization and exploration for archival descriptions.';
    public static $version = '1.0.0';

    public function initialize()
    {
        // Add CSS and JS to all pages
        $this->dispatcher->connect('response.filter_content', array($this, 'filterContent'));
    }

    public function filterContent(sfEvent $event, $content)
    {
        // Only add resources on information object pages
        $moduleName = sfContext::getInstance()->getModuleName();
        $actionName = sfContext::getInstance()->getActionName();
        
        if ($moduleName == 'informationobject' && $actionName == 'index')
        {
            $ricCss = '<link rel="stylesheet" href="/plugins/ahgRicExplorerPlugin/css/ric-explorer.css">';
            $ricJs = '<script src="/plugins/ahgRicExplorerPlugin/js/ric-explorer.js"></script>';
            
            $content = str_replace('</head>', $ricCss . "\n" . '</head>', $content);
            $content = str_replace('</body>', $ricJs . "\n" . '</body>', $content);
        }
        
        return $content;
    }
}
