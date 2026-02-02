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
        $routing = $event->getSubject();

        // Admin routes
        $routing->prependRoute('landing_page_list', new sfRoute('/admin/landing-pages', [
            'module' => 'landingPageBuilder',
            'action' => 'list',
        ]));

        $routing->prependRoute('landing_page_create', new sfRoute('/admin/landing-pages/create', [
            'module' => 'landingPageBuilder',
            'action' => 'create',
        ]));

        $routing->prependRoute('landing_page_edit', new sfRoute('/admin/landing-pages/:id/edit', [
            'module' => 'landingPageBuilder',
            'action' => 'edit',
        ], [
            'id' => '\d+',
        ]));

        $routing->prependRoute('landing_page_preview', new sfRoute('/admin/landing-pages/:id/preview', [
            'module' => 'landingPageBuilder',
            'action' => 'preview',
        ], [
            'id' => '\d+',
        ]));

        // AJAX endpoints
        $routing->prependRoute('landing_page_ajax_add_block', new sfRoute('/admin/landing-pages/ajax/add-block', [
            'module' => 'landingPageBuilder',
            'action' => 'addBlock',
        ]));

        $routing->prependRoute('landing_page_ajax_update_block', new sfRoute('/admin/landing-pages/ajax/update-block', [
            'module' => 'landingPageBuilder',
            'action' => 'updateBlock',
        ]));

        $routing->prependRoute('landing_page_ajax_delete_block', new sfRoute('/admin/landing-pages/ajax/delete-block', [
            'module' => 'landingPageBuilder',
            'action' => 'deleteBlock',
        ]));

        $routing->prependRoute('landing_page_ajax_duplicate_block', new sfRoute('/admin/landing-pages/ajax/duplicate-block', [
            'module' => 'landingPageBuilder',
            'action' => 'duplicateBlock',
        ]));

        $routing->prependRoute('landing_page_ajax_reorder', new sfRoute('/admin/landing-pages/ajax/reorder', [
            'module' => 'landingPageBuilder',
            'action' => 'reorderBlocks',
        ]));

        $routing->prependRoute('landing_page_ajax_toggle_visibility', new sfRoute('/admin/landing-pages/ajax/toggle-visibility', [
            'module' => 'landingPageBuilder',
            'action' => 'toggleVisibility',
        ]));

        $routing->prependRoute('landing_page_ajax_get_config', new sfRoute('/admin/landing-pages/ajax/get-config', [
            'module' => 'landingPageBuilder',
            'action' => 'getBlockConfig',
        ]));

        $routing->prependRoute('landing_page_ajax_update_settings', new sfRoute('/admin/landing-pages/ajax/update-settings', [
            'module' => 'landingPageBuilder',
            'action' => 'updateSettings',
        ]));

        $routing->prependRoute('landing_page_ajax_delete', new sfRoute('/admin/landing-pages/ajax/delete', [
            'module' => 'landingPageBuilder',
            'action' => 'delete',
        ]));

        $routing->prependRoute('landing_page_ajax_save_draft', new sfRoute('/admin/landing-pages/ajax/save-draft', [
            'module' => 'landingPageBuilder',
            'action' => 'saveDraft',
        ]));

        $routing->prependRoute('landing_page_ajax_publish', new sfRoute('/admin/landing-pages/ajax/publish', [
            'module' => 'landingPageBuilder',
            'action' => 'publish',
        ]));

        $routing->prependRoute('landing_page_ajax_restore_version', new sfRoute('/admin/landing-pages/ajax/restore-version', [
            'module' => 'landingPageBuilder',
            'action' => 'restoreVersion',
        ]));

        $routing->prependRoute('landing_page_ajax_move_to_column', new sfRoute('/admin/landing-pages/ajax/move-to-column', [
            'module' => 'landingPageBuilder',
            'action' => 'moveToColumn',
        ]));

        // Public routes
        $routing->prependRoute('landing_page_view', new sfRoute('/landing/:slug', [
            'module' => 'landingPageBuilder',
            'action' => 'index',
        ], [
            'slug' => '[a-z0-9\-]+',
        ]));
    }
}
