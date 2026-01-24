<?php

class ahgSettingsPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Extended settings management for AtoM';
    public static $version = '1.0.0';

    public function initialize()
    {
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'settings';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);
    }

    public function loadRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        // Main settings dashboard
        $routing->prependRoute('ahg_settings_dashboard', new sfRoute('/settings/ahgSettings', [
            'module' => 'settings',
            'action' => 'index',
        ]));

        $routing->prependRoute('ahg_settings_section', new sfRoute('/settings/ahgSettings/section', [
            'module' => 'settings',
            'action' => 'section',
        ]));

        // Admin routes
        $routing->prependRoute('admin_ahg_settings', new sfRoute('/admin/ahg-settings', [
            'module' => 'settings',
            'action' => 'index',
        ]));
        $routing->prependRoute('admin_ahg_settings_section', new sfRoute('/admin/ahg-settings/section', [
            'module' => 'settings',
            'action' => 'section',
        ]));
        $routing->prependRoute('admin_ahg_settings_plugins', new sfRoute('/admin/ahg-settings/plugins', [
            'module' => 'settings',
            'action' => 'plugins',
        ]));
        $routing->prependRoute('admin_ahg_settings_ai_services', new sfRoute('/admin/ahg-settings/ai-services', [
            'module' => 'settings',
            'action' => 'aiServices',
        ]));
        $routing->prependRoute('admin_ahg_settings_email', new sfRoute('/admin/ahg-settings/email', [
            'module' => 'settings',
            'action' => 'email',
        ]));

        // Settings index and section
        $routing->prependRoute('ahg_settings_index', new sfRoute('/ahgSettings/index', [
            'module' => 'settings',
            'action' => 'index',
        ]));

        // Export/Import
        $routing->prependRoute('ahg_settings_export', new sfRoute('/ahgSettings/export', [
            'module' => 'settings',
            'action' => 'export',
        ]));
        $routing->prependRoute('ahg_settings_import', new sfRoute('/ahgSettings/import', [
            'module' => 'settings',
            'action' => 'import',
        ]));
        $routing->prependRoute('ahg_settings_reset', new sfRoute('/ahgSettings/reset', [
            'module' => 'settings',
            'action' => 'reset',
        ]));

        // Email settings
        $routing->prependRoute('ahg_settings_email', new sfRoute('/ahgSettings/email', [
            'module' => 'settings',
            'action' => 'email',
        ]));
        $routing->prependRoute('ahg_settings_email_test', new sfRoute('/ahgSettings/emailTest', [
            'module' => 'settings',
            'action' => 'emailTest',
        ]));

        // Fuseki test
        $routing->prependRoute('ahg_settings_fuseki_test', new sfRoute('/ahgSettings/fusekiTest', [
            'module' => 'settings',
            'action' => 'fusekiTest',
        ]));

        // Plugins
        $routing->prependRoute('ahg_settings_plugins', new sfRoute('/ahgSettings/plugins', [
            'module' => 'settings',
            'action' => 'plugins',
        ]));

        // DAM tools
        $routing->prependRoute('ahg_settings_save_tiff_pdf', new sfRoute('/ahgSettings/saveTiffPdfSettings', [
            'module' => 'settings',
            'action' => 'saveTiffPdfSettings',
        ]));
        $routing->prependRoute('ahg_settings_dam_tools', new sfRoute('/ahgSettings/damTools', [
            'module' => 'settings',
            'action' => 'damTools',
        ]));

        // API Keys
        $routing->prependRoute('ahg_settings_api_keys', new sfRoute('/admin/ahg-settings/api-keys', [
            'module' => 'settings',
            'action' => 'apiKeys',
        ]));

        // Preservation settings
        $routing->prependRoute('ahg_settings_preservation', new sfRoute('/ahgSettings/preservation', [
            'module' => 'settings',
            'action' => 'preservation',
        ]));

        // Levels settings
        $routing->prependRoute('ahg_settings_levels', new sfRoute('/ahgSettings/levels', [
            'module' => 'settings',
            'action' => 'levels',
        ]));
    }
}
