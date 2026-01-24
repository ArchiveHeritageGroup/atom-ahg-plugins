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
        $routing = $event->getSubject();

        // Admin routes
        $routing->prependRoute('admin_ahg_settings', new sfRoute('/admin/ahg-settings', [
            'module' => 'ahgSettings',
            'action' => 'index',
        ]));
        $routing->prependRoute('admin_ahg_settings_section', new sfRoute('/admin/ahg-settings/section', [
            'module' => 'ahgSettings',
            'action' => 'section',
        ]));
        $routing->prependRoute('admin_ahg_settings_plugins', new sfRoute('/admin/ahg-settings/plugins', [
            'module' => 'ahgSettings',
            'action' => 'plugins',
        ]));
        $routing->prependRoute('admin_ahg_settings_ai_services', new sfRoute('/admin/ahg-settings/ai-services', [
            'module' => 'ahgSettings',
            'action' => 'aiServices',
        ]));
        $routing->prependRoute('admin_ahg_settings_email', new sfRoute('/admin/ahg-settings/email', [
            'module' => 'ahgSettings',
            'action' => 'email',
        ]));

        // Settings index and section
        $routing->prependRoute('ahg_settings_index', new sfRoute('/ahgSettings/index', [
            'module' => 'ahgSettings',
            'action' => 'index',
        ]));

        // Export/Import
        $routing->prependRoute('ahg_settings_export', new sfRoute('/ahgSettings/export', [
            'module' => 'ahgSettings',
            'action' => 'export',
        ]));
        $routing->prependRoute('ahg_settings_import', new sfRoute('/ahgSettings/import', [
            'module' => 'ahgSettings',
            'action' => 'import',
        ]));
        $routing->prependRoute('ahg_settings_reset', new sfRoute('/ahgSettings/reset', [
            'module' => 'ahgSettings',
            'action' => 'reset',
        ]));

        // Email settings
        $routing->prependRoute('ahg_settings_email', new sfRoute('/ahgSettings/email', [
            'module' => 'ahgSettings',
            'action' => 'email',
        ]));
        $routing->prependRoute('ahg_settings_email_test', new sfRoute('/ahgSettings/emailTest', [
            'module' => 'ahgSettings',
            'action' => 'emailTest',
        ]));

        // Fuseki test
        $routing->prependRoute('ahg_settings_fuseki_test', new sfRoute('/ahgSettings/fusekiTest', [
            'module' => 'ahgSettings',
            'action' => 'fusekiTest',
        ]));

        // Plugins
        $routing->prependRoute('ahg_settings_plugins', new sfRoute('/ahgSettings/plugins', [
            'module' => 'ahgSettings',
            'action' => 'plugins',
        ]));

        // DAM tools
        $routing->prependRoute('ahg_settings_save_tiff_pdf', new sfRoute('/ahgSettings/saveTiffPdfSettings', [
            'module' => 'ahgSettings',
            'action' => 'saveTiffPdfSettings',
        ]));
        $routing->prependRoute('ahg_settings_dam_tools', new sfRoute('/ahgSettings/damTools', [
            'module' => 'ahgSettings',
            'action' => 'damTools',
        ]));

        // API Keys
        $routing->prependRoute('ahg_settings_api_keys', new sfRoute('/admin/ahg-settings/api-keys', [
            'module' => 'ahgSettings',
            'action' => 'apiKeys',
        ]));

        // Preservation settings
        $routing->prependRoute('ahg_settings_preservation', new sfRoute('/ahgSettings/preservation', [
            'module' => 'ahgSettings',
            'action' => 'preservation',
        ]));

        // Levels settings
        $routing->prependRoute('ahg_settings_levels', new sfRoute('/ahgSettings/levels', [
            'module' => 'ahgSettings',
            'action' => 'levels',
        ]));
    }
}
