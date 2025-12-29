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

class arAHGThemeB5PluginConfiguration extends arDominionB5PluginConfiguration
{
    public static $summary = 'B5 theme plugin, extension of arDominionB5Plugin for The Archive and Heritage Group.';
    public static $version = '0.0.1';

    public function initialize()
    {
        parent::initialize();

        // Register modules from enabled plugins (framework-driven)
        // $this->registerFrameworkModules(); // Disabled - using settings.yml instead

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
     * Load plugin routes before the catch-all slug routes
     */
    public function loadRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        // Reports routes
        $routing->prependRoute('reports_index', new sfRoute('/reports', [
            'module' => 'reports',
            'action' => 'index'
        ]));

        $routing->prependRoute('reports_descriptions', new sfRoute('/reports/descriptions', [
            'module' => 'reports',
            'action' => 'descriptions'
        ]));

        $routing->prependRoute('reports_authorities', new sfRoute('/reports/authorities', [
            'module' => 'reports',
            'action' => 'authorities'
        ]));

        $routing->prependRoute('reports_repositories', new sfRoute('/reports/repositories', [
            'module' => 'reports',
            'action' => 'repositories'
        ]));

        $routing->prependRoute('reports_accessions', new sfRoute('/reports/accessions', [
            'module' => 'reports',
            'action' => 'accessions'
        ]));

        $routing->prependRoute('reports_storage', new sfRoute('/reports/storage', [
            'module' => 'reports',
            'action' => 'storage'
        ]));

        $routing->prependRoute('reports_recent', new sfRoute('/reports/recent', [
            'module' => 'reports',
            'action' => 'recent'
        ]));

        $routing->prependRoute('reports_activity', new sfRoute('/reports/activity', [
            'module' => 'reports',
            'action' => 'activity'
        ]));

        // Export routes
        $routing->prependRoute('export_archival', new sfRoute('/export/archival', [
            'module' => 'export',
            'action' => 'archival'
        ]));

        $routing->prependRoute('export_csv', new sfRoute('/export/csv', [
            'module' => 'export',
            'action' => 'csv'
        ]));

        $routing->prependRoute('export_ead', new sfRoute('/export/ead', [
            'module' => 'export',
            'action' => 'ead'
        ]));

        $routing->prependRoute('export_grap', new sfRoute('/export/grap', [
            'module' => 'export',
            'action' => 'grap'
        ]));

        $routing->prependRoute('export_authorities', new sfRoute('/export/authorities', [
            'module' => 'export',
            'action' => 'authorities'
        ]));

        // AHG Settings routes - module is ahgSettings, NOT settings
        $routing->prependRoute('ahg_settings_dashboard', new sfRoute('/settings/ahgSettings', [
            'module' => 'ahgSettings',
            'action' => 'index'
        ]));

        $routing->prependRoute('ahg_settings_section', new sfRoute('/settings/ahgSettings/section', [
            'module' => 'ahgSettings',
            'action' => 'section'
        ]));
    }
}
