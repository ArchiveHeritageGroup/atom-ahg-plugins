<?php

// config/routes.php — Native Laravel routes for ahgSettingsPlugin
// Loaded by RouteCollector with $router in scope.
// 55 routes total.

use AtomFramework\Http\Controllers\ActionBridge;

$bridge = ActionBridge::class . '@dispatch';

// ---------------------------------------------------------------------------
// Admin aliases (pretty /admin/ahg-settings/* URLs)
// ---------------------------------------------------------------------------
$router->match(['GET', 'POST'], '/admin/ahg-settings', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'index'])
    ->name('admin_ahg_settings');

$router->match(['GET', 'POST'], '/admin/ahg-settings/section', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'section'])
    ->name('admin_ahg_settings_section');

$router->match(['GET', 'POST'], '/admin/ahg-settings/plugins', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'plugins'])
    ->name('admin_ahg_settings_plugins');

$router->match(['GET', 'POST'], '/admin/ahg-settings/ai-services', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'aiServices'])
    ->name('admin_ahg_settings_ai_services');

$router->match(['GET', 'POST'], '/admin/ahg-settings/email', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'email'])
    ->name('admin_ahg_settings_email');

$router->match(['GET', 'POST'], '/admin/ahg-settings/api-keys', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'apiKeys'])
    ->name('admin_ahg_settings_api_keys');

$router->match(['GET', 'POST'], '/admin/ahg-settings/webhooks', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'webhooks'])
    ->name('admin_ahg_settings_webhooks');

$router->match(['GET', 'POST'], '/admin/ahg-settings/tts', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'tts'])
    ->name('admin_ahg_settings_tts');

$router->match(['GET', 'POST'], '/admin/ahg-settings/ahg-integration', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'ahgIntegration'])
    ->name('admin_ahg_settings_ahg_integration');

// ---------------------------------------------------------------------------
// API routes
// ---------------------------------------------------------------------------
$router->match(['GET', 'POST'], '/api/numbering/generate', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'generateIdentifier'])
    ->name('api_generate_identifier');

$router->match(['GET', 'POST'], '/api/numbering/validate', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'validateIdentifier'])
    ->name('api_validate_identifier');

// ---------------------------------------------------------------------------
// Settings index (base /settings URL)
// ---------------------------------------------------------------------------
$router->match(['GET', 'POST'], '/settings', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'index'])
    ->name('settings_index');

// ---------------------------------------------------------------------------
// Settings module routes (/ahgSettings/*)
// ---------------------------------------------------------------------------
$router->match(['GET', 'POST'], '/ahgSettings/index', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'index'])
    ->name('ahg_settings_index');

$router->match(['GET', 'POST'], '/ahgSettings/section', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'section'])
    ->name('ahg_settings_section');

$router->match(['GET', 'POST'], '/ahgSettings/export', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'export'])
    ->name('ahg_settings_export');

$router->match(['GET', 'POST'], '/ahgSettings/import', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'import'])
    ->name('ahg_settings_import');

$router->match(['GET', 'POST'], '/ahgSettings/reset', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'reset'])
    ->name('ahg_settings_reset');

$router->match(['GET', 'POST'], '/ahgSettings/email', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'email'])
    ->name('ahg_settings_email');

$router->match(['GET', 'POST'], '/ahgSettings/emailTest', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'emailTest'])
    ->name('ahg_settings_email_test');

$router->match(['GET', 'POST'], '/ahgSettings/fusekiTest', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'fusekiTest'])
    ->name('ahg_settings_fuseki_test');

$router->match(['GET', 'POST'], '/ahgSettings/plugins', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'plugins'])
    ->name('ahg_settings_plugins');

$router->match(['GET', 'POST'], '/ahgSettings/saveTiffPdfSettings', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'saveTiffPdfSettings'])
    ->name('ahg_settings_save_tiff_pdf');

$router->match(['GET', 'POST'], '/ahgSettings/damTools', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'damTools'])
    ->name('ahg_settings_dam_tools');

$router->match(['GET', 'POST'], '/ahgSettings/preservation', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'preservation'])
    ->name('ahg_settings_preservation');

$router->match(['GET', 'POST'], '/ahgSettings/levels', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'levels'])
    ->name('ahg_settings_levels');

$router->match(['GET', 'POST'], '/ahgSettings/aiServices', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'aiServices'])
    ->name('ahg_settings_ai_services');

$router->match(['GET', 'POST'], '/ahgSettings/apiKeys', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'apiKeys'])
    ->name('ahg_settings_api_keys');

$router->match(['GET', 'POST'], '/ahgSettings/webhooks', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'webhooks'])
    ->name('ahg_settings_webhooks');

$router->match(['GET', 'POST'], '/ahgSettings/global', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'global'])
    ->name('ahg_settings_global');

$router->match(['GET', 'POST'], '/ahgSettings/language', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'language'])
    ->name('ahg_settings_language');

$router->match(['GET', 'POST'], '/ahgSettings/siteInformation', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'siteInformation'])
    ->name('ahg_settings_site_info');

$router->match(['GET', 'POST'], '/ahgSettings/pageElements', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'pageElements'])
    ->name('ahg_settings_page_elements');

$router->match(['GET', 'POST'], '/ahgSettings/permissions', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'permissions'])
    ->name('ahg_settings_permissions');

$router->match(['GET', 'POST'], '/ahgSettings/security', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'security'])
    ->name('ahg_settings_security');

$router->match(['GET', 'POST'], '/ahgSettings/treeview', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'treeview'])
    ->name('ahg_settings_treeview');

$router->match(['GET', 'POST'], '/ahgSettings/uploads', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'uploads'])
    ->name('ahg_settings_uploads');

$router->match(['GET', 'POST'], '/ahgSettings/visibleElements', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'visibleElements'])
    ->name('ahg_settings_visible_elements');

$router->match(['GET', 'POST'], '/ahgSettings/template', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'template'])
    ->name('ahg_settings_template');

$router->match(['GET', 'POST'], '/ahgSettings/findingAid', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'findingAid'])
    ->name('ahg_settings_finding_aid');

$router->match(['GET', 'POST'], '/ahgSettings/oai', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'oai'])
    ->name('ahg_settings_oai');

$router->match(['GET', 'POST'], '/ahgSettings/identifier', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'identifier'])
    ->name('ahg_settings_identifier');

// ---------------------------------------------------------------------------
// Numbering / identifier management
// ---------------------------------------------------------------------------
$router->match(['GET', 'POST'], '/ahgSettings/sectorNumbering', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'sectorNumbering'])
    ->name('ahg_settings_sector_numbering');

$router->match(['GET', 'POST'], '/ahgSettings/numberingSchemes', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'numberingSchemes'])
    ->name('ahg_settings_numbering_schemes');

$router->match(['GET', 'POST'], '/ahgSettings/numberingSchemeEdit', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'numberingSchemeEdit'])
    ->name('ahg_settings_numbering_scheme_edit');

$router->match(['GET', 'POST'], '/ahgSettings/generateIdentifier', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'generateIdentifier'])
    ->name('ahg_settings_generate_identifier');

$router->match(['GET', 'POST'], '/ahgSettings/validateIdentifier', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'validateIdentifier'])
    ->name('ahg_settings_validate_identifier');

// ---------------------------------------------------------------------------
// Miscellaneous settings subpages
// ---------------------------------------------------------------------------
$router->match(['GET', 'POST'], '/ahgSettings/diacritics', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'diacritics'])
    ->name('ahg_settings_diacritics');

$router->match(['GET', 'POST'], '/ahgSettings/csvValidator', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'csvValidator'])
    ->name('ahg_settings_csv_validator');

$router->match(['GET', 'POST'], '/ahgSettings/clipboard', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'clipboard'])
    ->name('ahg_settings_clipboard');

$router->match(['GET', 'POST'], '/ahgSettings/tts', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'tts'])
    ->name('ahg_settings_tts');

$router->match(['GET', 'POST'], '/ahgSettings/services', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'services'])
    ->name('ahg_settings_services');

$router->match(['GET', 'POST'], '/ahgSettings/cronJobs', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'cronJobs'])
    ->name('ahg_settings_cron_jobs');

$router->match(['GET', 'POST'], '/ahgSettings/systemInfo', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'systemInfo'])
    ->name('ahg_settings_system_info');

$router->match(['GET', 'POST'], '/ahgSettings/icipSettings', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'icipSettings'])
    ->name('ahg_settings_icip_settings');

$router->match(['GET', 'POST'], '/ahgSettings/ahgIntegration', $bridge)
    ->setDefaults(['_module' => 'ahgSettings', '_action' => 'ahgIntegration'])
    ->name('ahg_settings_ahg_integration');
