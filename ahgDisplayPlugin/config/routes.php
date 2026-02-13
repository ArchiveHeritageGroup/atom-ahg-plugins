<?php

// config/routes.php — Native Laravel routes for ahgDisplayPlugin
// Loaded by RouteCollector with $router in scope.
// 22 routes total.

use AtomFramework\Http\Controllers\ActionBridge;

$bridge = ActionBridge::class . '@dispatch';

// ---------------------------------------------------------------------------
// Information object browse override
// ---------------------------------------------------------------------------
$router->match(['GET', 'POST'], '/informationobject/browse', $bridge)
    ->setDefaults(['_module' => 'display', '_action' => 'browse'])
    ->name('informationobject_browse_override');

// ---------------------------------------------------------------------------
// GLAM index and configuration
// ---------------------------------------------------------------------------
$router->match(['GET', 'POST'], '/glam', $bridge)
    ->setDefaults(['_module' => 'display', '_action' => 'index'])
    ->name('glam_index');

$router->match(['GET', 'POST'], '/glam/profiles', $bridge)
    ->setDefaults(['_module' => 'display', '_action' => 'profiles'])
    ->name('glam_profiles');

$router->match(['GET', 'POST'], '/glam/levels', $bridge)
    ->setDefaults(['_module' => 'display', '_action' => 'levels'])
    ->name('glam_levels');

$router->match(['GET', 'POST'], '/glam/fields', $bridge)
    ->setDefaults(['_module' => 'display', '_action' => 'fields'])
    ->name('glam_fields');

$router->match(['GET', 'POST'], '/glam/setType', $bridge)
    ->setDefaults(['_module' => 'display', '_action' => 'setType'])
    ->name('glam_set_type');

$router->match(['GET', 'POST'], '/glam/assignProfile', $bridge)
    ->setDefaults(['_module' => 'display', '_action' => 'assignProfile'])
    ->name('glam_assign_profile');

$router->match(['GET', 'POST'], '/glam/bulkSetType', $bridge)
    ->setDefaults(['_module' => 'display', '_action' => 'bulkSetType'])
    ->name('glam_bulk_set_type');

// ---------------------------------------------------------------------------
// GLAM search (displaySearch module)
// ---------------------------------------------------------------------------
$router->match(['GET', 'POST'], '/glam/search', $bridge)
    ->setDefaults(['_module' => 'displaySearch', '_action' => 'search'])
    ->name('glam_search');

$router->match(['GET', 'POST'], '/glam/search/results', $bridge)
    ->setDefaults(['_module' => 'displaySearch', '_action' => 'browse'])
    ->name('glam_search_results');

// ---------------------------------------------------------------------------
// GLAM browse, print, export
// ---------------------------------------------------------------------------
$router->match(['GET', 'POST'], '/glam/browse', $bridge)
    ->setDefaults(['_module' => 'display', '_action' => 'browse'])
    ->name('glam_browse');

$router->match(['GET', 'POST'], '/glam/browseAjax', $bridge)
    ->setDefaults(['_module' => 'display', '_action' => 'browseAjax'])
    ->name('glam_browse_ajax');

$router->match(['GET', 'POST'], '/glam/print', $bridge)
    ->setDefaults(['_module' => 'display', '_action' => 'print'])
    ->name('glam_print');

$router->match(['GET', 'POST'], '/glam/exportCsv', $bridge)
    ->setDefaults(['_module' => 'display', '_action' => 'exportCsv'])
    ->name('glam_export_csv');

$router->match(['GET', 'POST'], '/glam/changeType', $bridge)
    ->setDefaults(['_module' => 'display', '_action' => 'changeType'])
    ->name('glam_change_type');

// ---------------------------------------------------------------------------
// GLAM user browse settings
// ---------------------------------------------------------------------------
$router->match(['GET', 'POST'], '/glam/settings', $bridge)
    ->setDefaults(['_module' => 'display', '_action' => 'browseSettings'])
    ->name('glam_browse_settings');

$router->match(['GET', 'POST'], '/glam/toggleGlamBrowse', $bridge)
    ->setDefaults(['_module' => 'display', '_action' => 'toggleGlamBrowse'])
    ->name('glam_toggle_glam_browse');

$router->match(['GET', 'POST'], '/glam/saveBrowseSettings', $bridge)
    ->setDefaults(['_module' => 'display', '_action' => 'saveBrowseSettings'])
    ->name('glam_save_browse_settings');

$router->match(['GET', 'POST'], '/glam/getBrowseSettings', $bridge)
    ->setDefaults(['_module' => 'display', '_action' => 'getBrowseSettings'])
    ->name('glam_get_browse_settings');

$router->match(['GET', 'POST'], '/glam/resetBrowseSettings', $bridge)
    ->setDefaults(['_module' => 'display', '_action' => 'resetBrowseSettings'])
    ->name('glam_reset_browse_settings');

// ---------------------------------------------------------------------------
// Treeview overrides (with slug regex constraint)
// ---------------------------------------------------------------------------
$router->match(['GET', 'POST'], '/{slug}/treeView', $bridge)
    ->setDefaults(['_module' => 'treeview', '_action' => 'view'])
    ->where('slug', '[a-zA-Z0-9_-]+')
    ->name('treeview_override');

$router->match(['GET', 'POST'], '/{slug}/treeViewSort', $bridge)
    ->setDefaults(['_module' => 'treeview', '_action' => 'sort'])
    ->where('slug', '[a-zA-Z0-9_-]+')
    ->name('treeview_sort_override');
