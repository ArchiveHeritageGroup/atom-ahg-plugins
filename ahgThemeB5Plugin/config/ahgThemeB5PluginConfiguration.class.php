<?php

/*
 * This file is part of the Access to Memory (AtoM) software.
 *
 * Access to Memory (AtoM) is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Access to Memory (AtoM) is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Access to Memory (AtoM).  If not, see <http://www.gnu.org/licenses/>.
 */

require_once sfConfig::get('sf_plugins_dir')
    .'/arDominionB5Plugin/config/arDominionB5PluginConfiguration.class.php';

class ahgThemeB5PluginConfiguration extends arDominionB5PluginConfiguration
{
    public static $summary = 'B5 theme plugin, extension of arDominionB5Plugin for The Archive and Heritage Group.';
    public static $version = '0.0.1';

    public function initialize()
    {
        parent::initialize();

        // Enable theme modules programmatically (settings.yml merging is unreliable)
        $this->enableThemeModules();

        // Register audit trail hooks (moved from ProjectConfiguration)
        $this->registerAuditTrailHooks();

        // Register additional mime types for archival formats
        require_once $this->rootDir.'/lib/AhgMimeTypeExtension.class.php';
        AhgMimeTypeExtension::register();

        // Add this plugin templates before arDominionB5Plugin
        sfConfig::set('sf_decorator_dirs', array_merge(
            [$this->rootDir.'/templates'],
            sfConfig::get('sf_decorator_dirs')
        ));

        // Move this plugin to the top to allow overwriting
        // controllers and views from other plugin modules.
        $plugins = $this->configuration->getPlugins();
        if (false !== $key = array_search($this->name, $plugins)) {
            unset($plugins[$key]);
        }
        $this->configuration->setPlugins(array_merge([$this->name], $plugins));

        // Load plugin routes
        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);
    }

    /**
     * Enable theme modules programmatically.
     * Settings.yml merging is unreliable for themes, so we do it here like arRestApiPlugin.
     */
    private function enableThemeModules(): void
    {
        // Only theme-specific module overrides remain
        // Plugin-specific modules moved to their respective plugins:
        // - api, identifierApi → ahgAPIPlugin
        // - media, ahgMediaSettings → ahgIiifPlugin
        // - accessFilter → ahgSecurityClearancePlugin
        // - isbn → ahgLibraryPlugin
        // - requesttopublish → ahgRequestToPublishPlugin
        // - searchEnhancement → ahgSemanticSearchPlugin
        $themeModules = [
            'informationobject',
        ];

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        foreach ($themeModules as $module) {
            if (!in_array($module, $enabledModules)) {
                $enabledModules[] = $module;
            }
        }
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    /**
     * Register modules discovered from enabled plugins.
     */
    private function registerFrameworkModules(): void
    {
        $servicePath = sfConfig::get('sf_root_dir') . '/atom-framework/src/Services/ModuleDiscoveryService.php';

        if (!file_exists($servicePath)) {
            return;
        }

        require_once $servicePath;

        try {
            \AtomExtensions\Services\ModuleDiscoveryService::registerModules();
        } catch (\Exception $e) {
            error_log('AHGTheme: Module discovery failed - ' . $e->getMessage());
        }
    }

    /**
     * Register audit trail event hooks.
     * Moved from ProjectConfiguration for cleaner separation.
     */
    private function registerAuditTrailHooks(): void
    {
        $listenerPath = sfConfig::get('sf_plugins_dir') 
            . '/ahgAuditTrailPlugin/lib/ahgAuditTrailListener.class.php';

        if (!file_exists($listenerPath)) {
            return;
        }

        require_once $listenerPath;

        $this->dispatcher->connect(
            'component.method_not_found',
            ['ahgAuditTrailListener', 'listenToMethodNotFound']
        );

        $this->dispatcher->connect(
            'response.filter_content',

            ['ahgAuditTrailListener', 'logAction']
        );
    }

    /**
     * Register sector redirect hooks for library items
     */
    private function registerSectorRedirectHooks(): void
    {
        $listenerPath = sfConfig::get('sf_plugins_dir')
            . '/ahgThemeB5Plugin/lib/SectorRedirectListener.class.php';
        if (!file_exists($listenerPath)) {
            return;
        }
        require_once $listenerPath;
        $this->dispatcher->connect(
            'request.filter_parameters',
            ['SectorRedirectListener', 'redirectLibraryItems']
        );
    }

    /**
     * Load plugin routes before the catch-all slug routes
     */
    public function loadRoutes(sfEvent $event)
    {
        // Routes moved to dedicated plugins:
        // - ahgSettingsPlugin: ahgSettings routes
        // - ahgReportsPlugin: reports routes
        // - ahgExportPlugin: export routes
        // - ahgLabelPlugin: label routes
        // - ahgLandingPagePlugin: landing page routes
        // - ahgTiffPdfMergePlugin: tiffpdfmerge routes
    }
}
