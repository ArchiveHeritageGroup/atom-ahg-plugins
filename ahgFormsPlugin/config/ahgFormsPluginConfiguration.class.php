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
        $routing = $event->getSubject();

        // Admin dashboard
        $routing->prependRoute('ahg_forms_index', new sfRoute(
            '/admin/forms',
            ['module' => 'forms', 'action' => 'index']
        ));

        // Template management
        $routing->prependRoute('ahg_forms_templates', new sfRoute(
            '/admin/forms/templates',
            ['module' => 'forms', 'action' => 'templates']
        ));

        $routing->prependRoute('ahg_forms_template_create', new sfRoute(
            '/admin/forms/template/create',
            ['module' => 'forms', 'action' => 'templateCreate']
        ));

        $routing->prependRoute('ahg_forms_template_edit', new sfRoute(
            '/admin/forms/template/:id/edit',
            ['module' => 'forms', 'action' => 'templateEdit'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('ahg_forms_template_delete', new sfRoute(
            '/admin/forms/template/:id/delete',
            ['module' => 'forms', 'action' => 'templateDelete'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('ahg_forms_template_clone', new sfRoute(
            '/admin/forms/template/:id/clone',
            ['module' => 'forms', 'action' => 'templateClone'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('ahg_forms_template_export', new sfRoute(
            '/admin/forms/template/:id/export',
            ['module' => 'forms', 'action' => 'templateExport'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('ahg_forms_template_import', new sfRoute(
            '/admin/forms/template/import',
            ['module' => 'forms', 'action' => 'templateImport']
        ));

        // Field builder (drag-drop interface)
        $routing->prependRoute('ahg_forms_builder', new sfRoute(
            '/admin/forms/template/:id/builder',
            ['module' => 'forms', 'action' => 'builder'],
            ['id' => '\d+']
        ));

        // Form assignments
        $routing->prependRoute('ahg_forms_assignments', new sfRoute(
            '/admin/forms/assignments',
            ['module' => 'forms', 'action' => 'assignments']
        ));

        $routing->prependRoute('ahg_forms_assignment_create', new sfRoute(
            '/admin/forms/assignment/create',
            ['module' => 'forms', 'action' => 'assignmentCreate']
        ));

        $routing->prependRoute('ahg_forms_assignment_delete', new sfRoute(
            '/admin/forms/assignment/:id/delete',
            ['module' => 'forms', 'action' => 'assignmentDelete'],
            ['id' => '\d+']
        ));

        // Field mappings
        $routing->prependRoute('ahg_forms_mappings', new sfRoute(
            '/admin/forms/mappings',
            ['module' => 'forms', 'action' => 'mappings']
        ));

        // API routes for AJAX operations
        $routing->prependRoute('ahg_forms_api_save_fields', new sfRoute(
            '/api/forms/template/:id/fields',
            ['module' => 'forms', 'action' => 'apiSaveFields'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('ahg_forms_api_reorder', new sfRoute(
            '/api/forms/template/:id/reorder',
            ['module' => 'forms', 'action' => 'apiReorderFields'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('ahg_forms_api_get_form', new sfRoute(
            '/api/forms/render/:type/:id',
            ['module' => 'forms', 'action' => 'apiGetForm'],
            ['type' => 'informationobject|accession', 'id' => '\d+']
        ));

        $routing->prependRoute('ahg_forms_api_autosave', new sfRoute(
            '/api/forms/autosave',
            ['module' => 'forms', 'action' => 'apiAutosave']
        ));

        // Library of pre-built templates
        $routing->prependRoute('ahg_forms_library', new sfRoute(
            '/admin/forms/library',
            ['module' => 'forms', 'action' => 'library']
        ));

        $routing->prependRoute('ahg_forms_library_install', new sfRoute(
            '/admin/forms/library/:template/install',
            ['module' => 'forms', 'action' => 'libraryInstall']
        ));
    }
}
