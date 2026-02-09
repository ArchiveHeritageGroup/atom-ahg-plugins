<?php

/**
 * ahgFormsPlugin Configuration
 *
 * Configurable metadata entry forms per repository/collection.
 * Similar to DSpace's configurable submission forms.
 */
class ahgFormsPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Configurable Forms: custom metadata entry forms per repository with drag-drop builder';
    public static $version = '1.0.0';

    public function contextLoadFactories(sfEvent $event)
    {
        $context = $event->getSubject();
        $context->response->addStylesheet('/plugins/ahgFormsPlugin/web/css/forms.css', 'last');
        $context->response->addJavascript('/plugins/ahgFormsPlugin/web/js/forms.js', 'last');
    }

    public function initialize()
    {
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'forms';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    public function addRoutes(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('forms');

        // Admin dashboard
        $router->any('ahg_forms_index', '/admin/forms', 'index');

        // Template management
        $router->any('ahg_forms_templates', '/admin/forms/templates', 'templates');
        $router->any('ahg_forms_template_create', '/admin/forms/template/create', 'templateCreate');
        $router->any('ahg_forms_template_edit', '/admin/forms/template/:id/edit', 'templateEdit', ['id' => '\d+']);
        $router->any('ahg_forms_template_delete', '/admin/forms/template/:id/delete', 'templateDelete', ['id' => '\d+']);
        $router->any('ahg_forms_template_clone', '/admin/forms/template/:id/clone', 'templateClone', ['id' => '\d+']);
        $router->any('ahg_forms_template_export', '/admin/forms/template/:id/export', 'templateExport', ['id' => '\d+']);
        $router->any('ahg_forms_template_import', '/admin/forms/template/import', 'templateImport');

        // Field builder (drag-drop interface)
        $router->any('ahg_forms_builder', '/admin/forms/template/:id/builder', 'builder', ['id' => '\d+']);

        // Form assignments
        $router->any('ahg_forms_assignments', '/admin/forms/assignments', 'assignments');
        $router->any('ahg_forms_assignment_create', '/admin/forms/assignment/create', 'assignmentCreate');
        $router->any('ahg_forms_assignment_delete', '/admin/forms/assignment/:id/delete', 'assignmentDelete', ['id' => '\d+']);

        // Field mappings
        $router->any('ahg_forms_mappings', '/admin/forms/mappings', 'mappings');

        // API routes for AJAX operations
        $router->any('ahg_forms_api_save_fields', '/api/forms/template/:id/fields', 'apiSaveFields', ['id' => '\d+']);
        $router->any('ahg_forms_api_reorder', '/api/forms/template/:id/reorder', 'apiReorderFields', ['id' => '\d+']);
        $router->any('ahg_forms_api_get_form', '/api/forms/render/:type/:id', 'apiGetForm', ['type' => 'informationobject|accession', 'id' => '\d+']);
        $router->any('ahg_forms_api_autosave', '/api/forms/autosave', 'apiAutosave');

        // Library of pre-built templates
        $router->any('ahg_forms_library', '/admin/forms/library', 'library');
        $router->any('ahg_forms_library_install', '/admin/forms/library/:template/install', 'libraryInstall');

        $router->register($event->getSubject());
    }
}
