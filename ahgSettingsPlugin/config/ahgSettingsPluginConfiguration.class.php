<?php

class ahgSettingsPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Extended settings management for AtoM';
    public static $version = '1.0.0';

    public function initialize()
    {
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'ahgSettings';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);
    }

    public function loadRoutes(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('ahgSettings');

        // Admin routes
        $router->any('admin_ahg_settings', '/admin/ahg-settings', 'index');
        $router->any('admin_ahg_settings_section', '/admin/ahg-settings/section', 'section');
        $router->any('admin_ahg_settings_plugins', '/admin/ahg-settings/plugins', 'plugins');
        $router->any('admin_ahg_settings_ai_services', '/admin/ahg-settings/ai-services', 'aiServices');
        $router->any('admin_ahg_settings_email', '/admin/ahg-settings/email', 'email');

        // Settings index and section
        $router->any('ahg_settings_index', '/ahgSettings/index', 'index');

        // Export/Import
        $router->any('ahg_settings_export', '/ahgSettings/export', 'export');
        $router->any('ahg_settings_import', '/ahgSettings/import', 'import');
        $router->any('ahg_settings_reset', '/ahgSettings/reset', 'reset');

        // Email settings
        $router->any('ahg_settings_email', '/ahgSettings/email', 'email');
        $router->any('ahg_settings_email_test', '/ahgSettings/emailTest', 'emailTest');

        // Fuseki test
        $router->any('ahg_settings_fuseki_test', '/ahgSettings/fusekiTest', 'fusekiTest');

        // Plugins
        $router->any('ahg_settings_plugins', '/ahgSettings/plugins', 'plugins');

        // DAM tools
        $router->any('ahg_settings_save_tiff_pdf', '/ahgSettings/saveTiffPdfSettings', 'saveTiffPdfSettings');
        $router->any('ahg_settings_dam_tools', '/ahgSettings/damTools', 'damTools');

        // API Keys
        $router->any('ahg_settings_api_keys', '/admin/ahg-settings/api-keys', 'apiKeys');

        // Preservation settings
        $router->any('ahg_settings_preservation', '/ahgSettings/preservation', 'preservation');

        // Levels settings
        $router->any('ahg_settings_levels', '/ahgSettings/levels', 'levels');

        $router->register($event->getSubject());
    }
}
