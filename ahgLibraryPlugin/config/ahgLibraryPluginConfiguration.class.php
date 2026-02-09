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

        // Library module routes
        // IMPORTANT: prependRoute adds to FRONT, so add in reverse priority order
        // (most general first, most specific last)
        $library = new \AtomFramework\Routing\RouteLoader('library');

        // Library view by slug (catch-all - add FIRST so it's checked LAST)
        // Use sfRoute (NOT AhgMetadataRoute) to prevent URL generation conflicts
        $library->any('library_view', '/library/:slug', 'index', ['slug' => '[^/]+']);
        $library->any('library_edit', '/library/:slug/edit', 'edit', ['slug' => '[^/]+']);
        $library->any('library_add', '/library/add', 'edit');
        $library->any('library_browse', '/library', 'browse');
        $library->any('library_isbn_lookup', '/library/isbnLookup', 'isbnLookup');
        $library->any('library_isbn_provider_delete', '/library/isbn-provider/delete/:id', 'isbnProviderDelete');
        $library->any('library_isbn_provider_toggle', '/library/isbn-provider/toggle/:id', 'isbnProviderToggle');
        $library->any('library_isbn_provider_edit', '/library/isbn-provider/edit/:id', 'isbnProviderEdit', ['id' => '\d*']);
        $library->any('library_isbn_providers', '/library/isbn-providers', 'isbnProviders');
        $library->any('library_api_isbn', '/api/library/isbn/:isbn', 'apiIsbnLookup');
        $library->any('library_cover_proxy', '/library/cover/:isbn', 'coverProxy');
        $library->any('library_suggest_subjects', '/library/suggestSubjects', 'suggestSubjects');
        $library->register($routing);

        // ISBN module routes
        // IMPORTANT: Use 'isbn' module, NOT 'informationobject' to avoid URL generation conflicts
        $isbn = new \AtomFramework\Routing\RouteLoader('isbn');
        $isbn->any('isbn_lookup', '/isbn/lookup', 'lookup');
        $isbn->any('isbn_test', '/isbn/test', 'test');
        $isbn->any('isbn_api_test', '/isbn/apiTest', 'apiTest');
        $isbn->any('isbn_stats', '/admin/isbn/stats', 'stats');
        $isbn->register($routing);
    }
}
