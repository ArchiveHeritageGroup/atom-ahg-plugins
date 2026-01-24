<?php

/*
 * RiC Explorer Plugin for AtoM
 *
 * Provides Records in Context (RiC) visualization and exploration
 * capabilities integrated into AtoM's description view.
 *
 * @package    ahgRicExplorerPlugin
 * @author     The AHG / Plain Sailing
 * @version    1.0.1
 */

class ahgRicExplorerPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Records in Context (RiC) visualization and exploration for archival descriptions.';
    public static $version = '1.0.1';

    public function initialize()
    {
        // Add CSS and JS to all pages
        $this->dispatcher->connect('response.filter_content', [$this, 'filterContent']);

        // Register RIC sync listener for save/delete events
        $this->registerRicSyncListener();
    }

    /**
     * Register the RIC synchronization listener for entity events
     */
    protected function registerRicSyncListener(): void
    {
        // Entities to sync with RIC triplestore
        $syncableEntities = [
            'QubitInformationObject',
            'QubitActor',
            'QubitRepository',
            'QubitFunction',
        ];

        foreach ($syncableEntities as $entityClass) {
            // Connect to insert events (post)
            $this->dispatcher->connect(
                $entityClass . '.insert.post',
                ['RicSyncListener', 'handleSave']
            );

            // Connect to update events (post)
            $this->dispatcher->connect(
                $entityClass . '.update.post',
                ['RicSyncListener', 'handleSave']
            );

            // Connect to delete events (pre - before record is gone)
            $this->dispatcher->connect(
                $entityClass . '.delete.pre',
                ['RicSyncListener', 'handleDelete']
            );
        }
    }

    public function filterContent(sfEvent $event, $content)
    {
        // Only add resources on information object pages
        $moduleName = sfContext::getInstance()->getModuleName();
        $actionName = sfContext::getInstance()->getActionName();

        if ($moduleName == 'informationobject' && $actionName == 'index') {
            $ricCss = '<link rel="stylesheet" href="/plugins/ahgRicExplorerPlugin/web/css/ric-explorer.css">';
            $ricJs = '<script src="/plugins/ahgRicExplorerPlugin/web/js/ric-explorer.js"></script>';

            $content = str_replace('</head>', $ricCss . "\n" . '</head>', $content);
            $content = str_replace('</body>', $ricJs . "\n" . '</body>', $content);
        }

        return $content;
    }
}
