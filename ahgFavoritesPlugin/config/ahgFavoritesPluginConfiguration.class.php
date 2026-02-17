<?php

/**
 * ahgFavoritesPlugin configuration
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ahgFavoritesPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'User favorites/bookmarks management plugin';
    public static $version = '2.0.0';

    public function routingLoadConfiguration(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('favorites');

        // Browse
        $router->any('ahg_favorites_browse', '/favorites', 'browse');

        // Add / Remove / Clear (existing)
        $router->any('ahg_favorites_add', '/favorites/add/:slug', 'add');
        $router->any('ahg_favorites_remove', '/favorites/remove/:id', 'remove');
        $router->any('ahg_favorites_clear', '/favorites/clear', 'clear');

        // Bulk operations
        $router->post('ahg_favorites_bulk', '/favorites/bulk', 'bulk');
        $router->post('ahg_favorites_move', '/favorites/move', 'moveToFolder');

        // Notes
        $router->post('ahg_favorites_notes', '/favorites/notes/:id', 'updateNotes', ['id' => '\d+']);

        // Folders
        $router->post('ahg_favorites_folder_create', '/favorites/folder/create', 'folderCreate');
        $router->any('ahg_favorites_folder_view', '/favorites/folder/:id', 'folderView', ['id' => '\d+']);
        $router->post('ahg_favorites_folder_edit', '/favorites/folder/:id/edit', 'folderEdit', ['id' => '\d+']);
        $router->post('ahg_favorites_folder_delete', '/favorites/folder/:id/delete', 'folderDelete', ['id' => '\d+']);

        // AJAX
        $router->post('ahg_favorites_ajax_toggle', '/favorites/ajax/toggle', 'ajaxToggle');
        $router->post('ahg_favorites_ajax_toggle_custom', '/favorites/ajax/toggle-custom', 'ajaxToggleCustom');
        $router->any('ahg_favorites_ajax_search', '/favorites/ajax/search', 'ajaxSearch');
        $router->any('ahg_favorites_ajax_status', '/favorites/ajax/status/:slug', 'ajaxStatus');
        $router->any('ahg_favorites_ajax_folders', '/favorites/ajax/folders', 'ajaxFolders');

        // Export
        $router->any('ahg_favorites_export', '/favorites/export/:format', 'export');
        $router->any('ahg_favorites_export_folder', '/favorites/folder/:id/export/:format', 'exportFolder', ['id' => '\d+']);

        // Sharing
        $router->post('ahg_favorites_share_folder', '/favorites/folder/:id/share', 'shareFolder', ['id' => '\d+']);
        $router->post('ahg_favorites_revoke_sharing', '/favorites/folder/:id/revoke-share', 'revokeSharing', ['id' => '\d+']);
        $router->any('ahg_favorites_view_shared', '/favorites/shared/:token', 'viewShared');

        // Import
        $router->post('ahg_favorites_import', '/favorites/import', 'import');

        // Research integration
        $router->any('ahg_favorites_send_collection', '/favorites/send-to-collection', 'sendToCollection');
        $router->any('ahg_favorites_send_project', '/favorites/send-to-project', 'sendToProject');
        $router->any('ahg_favorites_send_bibliography', '/favorites/send-to-bibliography', 'sendToBibliography');

        $router->register($event->getSubject());
    }

    public function contextLoadFactories(sfEvent $event)
    {
        // Plugin initialization
    }

    public function initialize()
    {
        $this->dispatcher->connect('routing.load_configuration', [$this, 'routingLoadConfiguration']);
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);

        // Enable module
        $enabledModules = sfConfig::get('sf_enabled_modules');
        $enabledModules[] = 'favorites';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }
}
