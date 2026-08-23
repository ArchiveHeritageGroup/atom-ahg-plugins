<?php

/**
 * Archaeology - site, find and stratigraphic context recording.
 *
 * AtoM-line port of the Heratio ahg-archaeology module (atom-ahg-plugins#190,
 * parity with heratio#1428). Sites, contexts and finds each extend an
 * information_object, so hierarchy, ACL, digital objects and ICIP protocols come
 * from the core object rather than being reimplemented here.
 *
 * The point of the module is the stratigraphy. A context is the unit an
 * excavation is actually dug in, and the relationships between contexts form a
 * directed acyclic graph, not a tree - a layer can lie beneath two others at
 * once. That is why the sequence cannot ride the information_object hierarchy
 * and needs its own edge table.
 *
 * Note on routing: every :id is constrained to \d+ so that /archaeology/site/add
 * cannot be swallowed by /archaeology/site/:id. Without the constraint the
 * literal route has to be declared first and stays fragile forever.
 */
class ahgArchaeologyPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Archaeological site, find and stratigraphic context recording with Harris Matrix';
    public static $version = '0.1.0';

    public function initialize(): void
    {
        $this->registerAutoloader();

        $enabledModules = sfConfig::get('sf_enabled_modules', []);

        if (!in_array('archaeology', $enabledModules, true)) {
            $enabledModules[] = 'archaeology';
            sfConfig::set('sf_enabled_modules', $enabledModules);
        }

        $this->dispatcher->connect('routing.load_configuration', [$this, 'configureRouting']);

        // A site, a context and a find are all information objects, so AtoM serves
        // them through its ordinary description view, which knows nothing about
        // this plugin. Without this listener a site description offers no route to
        // its own Harris Matrix. See ArchaeologyPanelInjector.
        $this->dispatcher->connect(
            'response.filter_content',
            ['\AhgArchaeologyPlugin\Listeners\ArchaeologyPanelInjector', 'filter']
        );

        // Only 'manage' and 'browse' render without ahgThemeB5Plugin, so a
        // staff-facing entry has to go in 'manage' - anything else silently
        // renders nowhere. See AhgNav.
        if (class_exists('AhgNav')) {
            AhgNav::register('manage', 'archaeology', [
                'url' => '/index.php/archaeology',
                'label' => 'Archaeology',
                'credentials' => ['contributor', 'editor', 'administrator'],
                'icon' => 'fas fa-trowel-bricks',
                'weight' => 46,
            ]);
        }
    }

    /**
     * PSR-4 for this plugin's own namespace.
     *
     * Symfony 1.4's autoloader indexes classes by file scan and does not
     * understand namespaces, so a namespaced plugin registers its own - the same
     * approach ahgSiteRecordPlugin and ahgProvenancePlugin use.
     */
    private function registerAutoloader(): void
    {
        $libPath = realpath(__DIR__.'/../lib');

        if (false === $libPath) {
            return;
        }

        spl_autoload_register(static function ($class) use ($libPath) {
            $prefix = 'AhgArchaeologyPlugin\\';

            if (0 !== strpos($class, $prefix)) {
                return false;
            }

            $file = $libPath.'/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';

            if (file_exists($file)) {
                require_once $file;

                return true;
            }

            return false;
        });
    }

    public function configureRouting(sfEvent $event): void
    {
        $router = new \AtomFramework\Routing\RouteLoader('archaeology');

        $router->get('archaeology_index', '/archaeology', 'index');

        // Sites.
        $router->get('archaeology_sites', '/archaeology/sites', 'sites');
        $router->get('archaeology_map', '/archaeology/map', 'map');
        $router->any('archaeology_site_add', '/archaeology/site/add', 'siteEdit');
        $router->get('archaeology_site', '/archaeology/site/:id', 'site', ['id' => '\d+']);
        $router->any('archaeology_site_edit', '/archaeology/site/:id/edit', 'siteEdit', ['id' => '\d+']);

        // Contexts, which always belong to a site.
        $router->get('archaeology_contexts', '/archaeology/site/:siteId/contexts', 'contexts', ['siteId' => '\d+']);
        $router->get('archaeology_plan', '/archaeology/site/:siteId/plan', 'plan', ['siteId' => '\d+']);

        // CSV import of contexts and their relationships.
        $router->any('archaeology_import', '/archaeology/site/:siteId/import', 'import', ['siteId' => '\d+']);
        $router->get('archaeology_import_template', '/archaeology/site/:siteId/import/template', 'importTemplate', ['siteId' => '\d+']);

        // Printable context sheet.
        $router->get('archaeology_context_pdf', '/archaeology/context/:id/pdf', 'contextPdf', ['id' => '\d+']);
        $router->any('archaeology_context_add', '/archaeology/context/add', 'contextEdit');
        $router->get('archaeology_context', '/archaeology/context/:id', 'context', ['id' => '\d+']);
        $router->any('archaeology_context_edit', '/archaeology/context/:id/edit', 'contextEdit', ['id' => '\d+']);

        // Stratigraphic relationships. POST only - each one mutates the sequence,
        // and the reciprocal edge is written with it.
        $router->post('archaeology_relationship_add', '/archaeology/context/:id/relationship', 'relationshipStore', ['id' => '\d+']);
        $router->post('archaeology_relationship_delete', '/archaeology/context/:id/relationship/:relId/delete', 'relationshipDelete', ['id' => '\d+', 'relId' => '\d+']);

        // Finds.
        $router->get('archaeology_objects', '/archaeology/finds', 'objects');
        $router->any('archaeology_object_add', '/archaeology/find/add', 'objectEdit');
        $router->get('archaeology_object', '/archaeology/find/:id', 'object', ['id' => '\d+']);
        $router->any('archaeology_object_edit', '/archaeology/find/:id/edit', 'objectEdit', ['id' => '\d+']);

        // Contexts of a site as JSON, so the find form's context picker can
        // refresh when the site changes without a page reload.
        $router->get('archaeology_contexts_json', '/archaeology/site/:siteId/contexts.json', 'contextsJson', ['siteId' => '\d+']);

        $router->register($event->getSubject());
    }
}
