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
        $this->dispatcher->connect('response.filter_content', [$this, 'injectLevelFilter']);
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

        // Settings index (short URL alias)
        $router->any('settings_index', '/settings', 'index');

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

        // Webhooks
        $router->any('admin_ahg_settings_webhooks', '/admin/ahg-settings/webhooks', 'webhooks');

        // TTS
        $router->any('admin_ahg_settings_tts', '/admin/ahg-settings/tts', 'tts');

        // AHG Integration
        $router->any('admin_ahg_settings_ahg_integration', '/admin/ahg-settings/ahg-integration', 'ahgIntegration');

        // Preservation settings
        $router->any('ahg_settings_preservation', '/ahgSettings/preservation', 'preservation');

        // Levels settings
        $router->any('ahg_settings_levels', '/ahgSettings/levels', 'levels');

        // AJAX: level of description choices for current sector
        $router->any('ahg_settings_level_choices', '/ahgSettings/levelChoices', 'levelChoices');

        $router->register($event->getSubject());
    }

    /**
     * Inject JS to filter Level of Description dropdown on IO edit/add pages.
     */
    public function injectLevelFilter(sfEvent $event, $content)
    {
        // Only inject on information object edit/add pages
        $module = sfContext::getInstance()->getModuleName();
        $action = sfContext::getInstance()->getActionName();

        $ioModules = [
            'informationobject', 'sfIsadPlugin', 'sfDcPlugin', 'sfRadPlugin',
            'sfModsPlugin', 'sfDacsPlugin',
            'ioManage', 'dacsManage', 'dcManage',
            'modsManage', 'radManage',
        ];

        if (!in_array($module, $ioModules, true)) {
            return $content;
        }

        // Only on edit/add actions
        $editActions = ['edit', 'add', 'create'];
        if (!in_array($action, $editActions, true)) {
            return $content;
        }

        $nonce = sfConfig::get('csp_nonce', '');
        $nonceAttr = $nonce ? ' ' . preg_replace('/^nonce=/', 'nonce="', $nonce) . '"' : '';

        $js = <<<JSEOF
<script{$nonceAttr}>
(function() {
  function filterLevels() {
    var sel = document.getElementById('levelOfDescriptionId') || document.getElementById('levelOfDescription');
    if (!sel) return;
    var xhr = new XMLHttpRequest();
    xhr.open('GET', '/index.php/ahgSettings/levelChoices', true);
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        try {
          var data = JSON.parse(xhr.responseText);
          var allowed = data.term_ids;
          if (!allowed || allowed.length === 0) return;
          var options = sel.querySelectorAll('option');
          for (var i = 0; i < options.length; i++) {
            var val = parseInt(options[i].value, 10);
            if (!val || isNaN(val)) continue;
            if (allowed.indexOf(val) === -1) {
              options[i].style.display = 'none';
              options[i].disabled = true;
            }
          }
        } catch(e) {}
      }
    };
    xhr.send();
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', filterLevels);
  } else {
    filterLevels();
  }
})();
</script>
JSEOF;

        return str_replace('</body>', $js . "\n</body>", $content);
    }
}
