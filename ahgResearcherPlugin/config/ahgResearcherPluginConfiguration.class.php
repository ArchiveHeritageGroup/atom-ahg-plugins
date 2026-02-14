<?php

/**
 * ahgResearcherPlugin configuration.
 *
 * Researcher collection upload and approval workflow.
 * Supports online submissions and offline exchange import
 * from ahgPortableExportPlugin viewer edit mode.
 */
class ahgResearcherPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Researcher collection upload and approval workflow';
    public static $version = '1.0.0';

    public function contextLoadFactories(sfEvent $event)
    {
        $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($frameworkPath)) {
            require_once $frameworkPath;
        }
    }

    public function initialize()
    {
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'researcher';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }

    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        $r = new \AtomFramework\Routing\RouteLoader('researcher');

        // Dashboard
        $r->any('researcher_dashboard', '/researcher', 'dashboard');

        // Submissions list
        $r->any('researcher_submissions', '/researcher/submissions', 'submissions');

        // Create / View / Edit submission
        $r->any('researcher_new_submission', '/researcher/submission/new', 'newSubmission');
        $r->any('researcher_view_submission', '/researcher/submission/:id', 'viewSubmission', ['id' => '\d+']);
        $r->any('researcher_edit_submission', '/researcher/submission/:id/edit', 'editSubmission', ['id' => '\d+']);

        // Items within submission
        $r->any('researcher_add_item', '/researcher/submission/:id/item/add', 'addItem', ['id' => '\d+']);
        $r->any('researcher_edit_item', '/researcher/submission/:id/item/:itemId', 'editItem', ['id' => '\d+', 'itemId' => '\d+']);
        $r->any('researcher_delete_item', '/researcher/submission/:id/item/:itemId/delete', 'deleteItem', ['id' => '\d+', 'itemId' => '\d+']);

        // Workflow actions
        $r->any('researcher_submit', '/researcher/submission/:id/submit', 'submit', ['id' => '\d+']);
        $r->any('researcher_resubmit', '/researcher/submission/:id/resubmit', 'resubmit', ['id' => '\d+']);

        // Create from research collection
        $r->any('researcher_from_collection', '/researcher/from-collection/:collectionId', 'createFromCollection', ['collectionId' => '\d+']);

        // Exchange import
        $r->any('researcher_import_exchange', '/researcher/import', 'importExchange');

        // Publish
        $r->any('researcher_publish', '/researcher/submission/:id/publish', 'publish', ['id' => '\d+']);

        // AJAX endpoints
        $r->any('researcher_api_upload', '/researcher/api/upload', 'apiUpload');
        $r->any('researcher_api_delete_file', '/researcher/api/delete-file', 'apiDeleteFile');
        $r->any('researcher_api_autocomplete', '/researcher/api/autocomplete', 'apiAutocomplete');

        $r->register($routing);
    }

    public static function getPluginInfo()
    {
        return [
            'name' => 'Researcher Collection Upload',
            'version' => self::$version,
            'description' => self::$summary,
            'author' => 'The Archive and Heritage Group (Pty) Ltd',
            'features' => [
                'Online collection upload with ISAD(G) forms',
                'Offline exchange import from Portable Export viewer',
                'Two-step archivist approval workflow',
                'Publish approved submissions to AtoM records',
                'File upload with SHA-256 checksums',
                'Support for new repositories, creators, subjects, places, genre',
            ],
        ];
    }
}
