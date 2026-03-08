<?php
class ahgLibraryPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Library & Bibliographic Cataloging';
    public static $version = '2.0.0';
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
        $enabledModules[] = 'opac';
        $enabledModules[] = 'circulation';
        $enabledModules[] = 'patron';
        $enabledModules[] = 'acquisition';
        $enabledModules[] = 'serial';
        $enabledModules[] = 'ill';
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

        // OPAC (Online Public Access Catalog) routes
        $opac = new \AtomFramework\Routing\RouteLoader('opac');
        $opac->any('opac_index', '/opac', 'index');
        $opac->any('opac_view', '/opac/view/:id', 'view', ['id' => '\d+']);
        $opac->any('opac_hold', '/opac/hold', 'hold');
        $opac->any('opac_account', '/opac/account', 'account');
        $opac->register($routing);

        // Circulation module routes
        $circ = new \AtomFramework\Routing\RouteLoader('circulation');
        $circ->any('circulation_index', '/circulation', 'index');
        $circ->any('circulation_checkout', '/circulation/checkout', 'checkout');
        $circ->any('circulation_checkin', '/circulation/checkin', 'checkin');
        $circ->any('circulation_renew', '/circulation/renew', 'renew');
        $circ->any('circulation_overdue', '/circulation/overdue', 'overdue');
        $circ->any('circulation_loan_rules', '/circulation/loan-rules', 'loanRules');
        $circ->register($routing);

        // Patron module routes
        $patron = new \AtomFramework\Routing\RouteLoader('patron');
        $patron->any('patron_view', '/patron/view/:id', 'view', ['id' => '\d+']);
        $patron->any('patron_edit', '/patron/edit/:id', 'edit', ['id' => '\d*']);
        $patron->any('patron_suspend', '/patron/suspend', 'suspend');
        $patron->any('patron_reactivate', '/patron/reactivate', 'reactivate');
        $patron->any('patron_index', '/patron', 'index');
        $patron->register($routing);

        // Acquisition module routes
        $acq = new \AtomFramework\Routing\RouteLoader('acquisition');
        $acq->any('acquisition_order_view', '/acquisition/order/:order_id', 'order', ['order_id' => '\d+']);
        $acq->any('acquisition_order_edit', '/acquisition/order/edit/:id', 'orderEdit', ['id' => '\d*']);
        $acq->any('acquisition_add_line', '/acquisition/add-line', 'addLine');
        $acq->any('acquisition_receive', '/acquisition/receive', 'receive');
        $acq->any('acquisition_budgets', '/acquisition/budgets', 'budgets');
        $acq->any('acquisition_index', '/acquisition', 'index');
        $acq->register($routing);

        // Serial module routes
        $serial = new \AtomFramework\Routing\RouteLoader('serial');
        $serial->any('serial_view', '/serial/view/:id', 'view', ['id' => '\d+']);
        $serial->any('serial_edit', '/serial/edit/:id', 'edit', ['id' => '\d*']);
        $serial->any('serial_checkin', '/serial/checkin', 'checkin');
        $serial->any('serial_claim', '/serial/claim', 'claim');
        $serial->any('serial_index', '/serial', 'index');
        $serial->register($routing);

        // ILL module routes
        $ill = new \AtomFramework\Routing\RouteLoader('ill');
        $ill->any('ill_view', '/ill/view/:id', 'view', ['id' => '\d+']);
        $ill->any('ill_edit', '/ill/edit', 'edit');
        $ill->any('ill_status', '/ill/status', 'status');
        $ill->any('ill_index', '/ill', 'index');
        $ill->register($routing);
    }
}
