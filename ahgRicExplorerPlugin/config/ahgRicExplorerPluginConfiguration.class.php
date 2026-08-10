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

        // Navigation entry, contributed by the plugin rather than named by the
        // theme.
        //
        // These modules were reachable only because ahgThemeB5Plugin hardcodes
        // them in its own admin menu, so on a stock AtoM install - which is what
        // the published bundles target - the plugin installed, worked, and had
        // nowhere to click from. ahgCorePlugin renders these into AtoM's own
        // quick-links menu when the theme is absent, so the entry now follows
        // the plugin and disappears with it. Issue #292.
        if (class_exists('AhgNav')) {
            AhgNav::register('manage', 'ricDashboard_index', [
                'route' => ['module' => 'ricDashboard', 'action' => 'index'],
                'label' => 'RiC dashboard',
                'credentials' => ['administrator'],
                'weight' => 370,
            ]);
        }
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
