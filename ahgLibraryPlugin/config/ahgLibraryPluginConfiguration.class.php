<?php
class ahgLibraryPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Library & Bibliographic Cataloging';
    public static $version = '1.1.0';
    // Library level IDs (Book, Monograph, Periodical, Journal, Manuscript)
    public static $libraryLevelIds = [1700, 1701, 1702, 1703, 1704];

    public function initialize()
    {
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'library';
        $enabledModules[] = 'isbn';
        $enabledModules[] = 'libraryReports';
        // Note: informationobject module no longer needed - ISBN lookup moved to isbn module
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    public function contextLoadFactories(sfEvent $event)
    {
        $event->getSubject()->getConfiguration()->loadHelpers(['Asset', 'Url']);
    }

    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        // IMPORTANT: prependRoute adds to FRONT, so add in reverse priority order
        // (most general first, most specific last)

        // Library view by slug (catch-all - add FIRST so it's checked LAST)
        // Use sfRoute (NOT AhgMetadataRoute) to prevent URL generation conflicts
        // AhgMetadataRoute was causing /library/ URLs to be generated for non-library items
        $routing->prependRoute('library_view', new sfRoute(
            '/library/:slug',
            ['module' => 'library', 'action' => 'index'],
            ['slug' => '[^/]+']
        ));

        // Library edit
        $routing->prependRoute('library_edit', new sfRoute(
            '/library/:slug/edit',
            ['module' => 'library', 'action' => 'edit'],
            ['slug' => '[^/]+']
        ));

        // Library add
        $routing->prependRoute('library_add', new sfRoute(
            '/library/add',
            ['module' => 'library', 'action' => 'edit']
        ));

        // Library browse
        $routing->prependRoute('library_browse', new sfRoute(
            '/library',
            ['module' => 'library', 'action' => 'browse']
        ));

        // ISBN lookup for library module (matches JavaScript fetch call)
        $routing->prependRoute('library_isbn_lookup', new sfRoute(
            '/library/isbnLookup',
            ['module' => 'library', 'action' => 'isbnLookup']
        ));

        // ISBN provider routes (specific - add LAST so checked FIRST)
        $routing->prependRoute('library_isbn_provider_delete', new sfRoute(
            '/library/isbn-provider/delete/:id',
            ['module' => 'library', 'action' => 'isbnProviderDelete']
        ));

        $routing->prependRoute('library_isbn_provider_toggle', new sfRoute(
            '/library/isbn-provider/toggle/:id',
            ['module' => 'library', 'action' => 'isbnProviderToggle']
        ));

        $routing->prependRoute('library_isbn_provider_edit', new sfRoute(
            '/library/isbn-provider/edit/:id',
            ['module' => 'library', 'action' => 'isbnProviderEdit'],
            ['id' => '\d*']
        ));

        $routing->prependRoute('library_isbn_providers', new sfRoute(
            '/library/isbn-providers',
            ['module' => 'library', 'action' => 'isbnProviders']
        ));

        // API route
        $routing->prependRoute('library_api_isbn', new sfRoute(
            '/api/library/isbn/:isbn',
            ['module' => 'library', 'action' => 'apiIsbnLookup']
        ));

        // Cover proxy route
        $routing->prependRoute('library_cover_proxy', new sfRoute(
            '/library/cover/:isbn',
            ['module' => 'library', 'action' => 'coverProxy']
        ));

        // ISBN lookup for information objects
        // IMPORTANT: Use 'isbn' module, NOT 'informationobject' to avoid URL generation conflicts
        // Using 'informationobject' causes url_for(['module'=>'informationobject', 'slug'=>...]) to match this route
        $routing->prependRoute('isbn_lookup', new sfRoute(
            '/isbn/lookup',
            ['module' => 'isbn', 'action' => 'lookup']
        ));

        // ISBN test routes
        $routing->prependRoute('isbn_test', new sfRoute(
            '/isbn/test',
            ['module' => 'isbn', 'action' => 'test']
        ));

        $routing->prependRoute('isbn_api_test', new sfRoute(
            '/isbn/apiTest',
            ['module' => 'isbn', 'action' => 'apiTest']
        ));

        $routing->prependRoute('isbn_stats', new sfRoute(
            '/admin/isbn/stats',
            ['module' => 'isbn', 'action' => 'stats']
        ));
    }
}
