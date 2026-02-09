<?php

class ahgLandingPagePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Visual landing page builder for AtoM';
    public static $version = '1.0.0';

    public function initialize()
    {
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'landingPageBuilder';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        // Register autoloader for AtomExtensions namespace
        $pluginLibPath = dirname(__FILE__).'/../lib/';
        spl_autoload_register(function ($class) use ($pluginLibPath) {
            // Only handle AtomExtensions namespace
            if (strpos($class, 'AtomExtensions\\') !== 0) {
                return false;
            }

            // Map namespace to directory structure
            // AtomExtensions\Services\LandingPageService -> lib/Services/LandingPageService.php
            // AtomExtensions\Repositories\LandingPageRepository -> lib/Repositories/LandingPageRepository.php
            $relativePath = str_replace('AtomExtensions\\', '', $class);
            $relativePath = str_replace('\\', '/', $relativePath);
            $file = $pluginLibPath.$relativePath.'.php';

            if (file_exists($file)) {
                require_once $file;
                return true;
            }

            return false;
        }, true, true);

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);
    }

    public function loadRoutes(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('landingPageBuilder');

        // Admin routes
        $router->any('landing_page_list', '/admin/landing-pages', 'list');
        $router->any('landing_page_create', '/admin/landing-pages/create', 'create');
        $router->any('landing_page_edit', '/admin/landing-pages/:id/edit', 'edit', ['id' => '\d+']);
        $router->any('landing_page_preview', '/admin/landing-pages/:id/preview', 'preview', ['id' => '\d+']);

        // AJAX endpoints
        $router->any('landing_page_ajax_add_block', '/admin/landing-pages/ajax/add-block', 'addBlock');
        $router->any('landing_page_ajax_update_block', '/admin/landing-pages/ajax/update-block', 'updateBlock');
        $router->any('landing_page_ajax_delete_block', '/admin/landing-pages/ajax/delete-block', 'deleteBlock');
        $router->any('landing_page_ajax_duplicate_block', '/admin/landing-pages/ajax/duplicate-block', 'duplicateBlock');
        $router->any('landing_page_ajax_reorder', '/admin/landing-pages/ajax/reorder', 'reorderBlocks');
        $router->any('landing_page_ajax_toggle_visibility', '/admin/landing-pages/ajax/toggle-visibility', 'toggleVisibility');
        $router->any('landing_page_ajax_get_config', '/admin/landing-pages/ajax/get-config', 'getBlockConfig');
        $router->any('landing_page_ajax_update_settings', '/admin/landing-pages/ajax/update-settings', 'updateSettings');
        $router->any('landing_page_ajax_delete', '/admin/landing-pages/ajax/delete', 'delete');
        $router->any('landing_page_ajax_save_draft', '/admin/landing-pages/ajax/save-draft', 'saveDraft');
        $router->any('landing_page_ajax_publish', '/admin/landing-pages/ajax/publish', 'publish');
        $router->any('landing_page_ajax_restore_version', '/admin/landing-pages/ajax/restore-version', 'restoreVersion');
        $router->any('landing_page_ajax_move_to_column', '/admin/landing-pages/ajax/move-to-column', 'moveToColumn');

        // Public routes
        $router->any('landing_page_view', '/landing/:slug', 'index', ['slug' => '[a-z0-9\-]+']);

        $router->register($event->getSubject());
    }
}
