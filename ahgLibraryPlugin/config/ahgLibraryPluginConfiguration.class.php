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
        $enabledModules[] = 'kbartVendor';
        $enabledModules[] = 'z3950';
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
        $library->any('library_export', '/library/export', 'export');
        $library->any('library_advanced_search', '/library/advanced-search', 'advancedSearch');
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

        // Acquisition API routes (must be registered before UI routes)
        $api = new \AtomFramework\Routing\RouteLoader('acquisition');
        $api->any('library_api_batch', '/api/library/batch/:api_action', 'api', ['api_action' => '[a-z-]+']);
        $api->any('library_api_budgets', '/api/library/budgets', 'api');
        $api->any('library_api_order_line_receive', '/api/library/orders/:id/lines/:line_id/receive', 'api', ['id' => '\d+', 'line_id' => '\d+']);
        $api->any('library_api_order_line', '/api/library/orders/:id/lines/:line_id', 'api', ['id' => '\d+', 'line_id' => '\d+']);
        $api->any('library_api_order_lines', '/api/library/orders/:id/lines', 'api', ['id' => '\d+']);
        $api->any('library_api_order', '/api/library/orders/:id', 'api', ['id' => '\d+']);
        $api->any('library_api_orders', '/api/library/orders', 'api');
        $api->register($routing);

        // Acquisition module routes
        $acq = new \AtomFramework\Routing\RouteLoader('acquisition');
        $acq->any('acquisition_order_view', '/acquisition/order/:order_id', 'order', ['order_id' => '\d+']);
        $acq->any('acquisition_order_edit', '/acquisition/order/edit/:id', 'orderEdit', ['id' => '\d*']);
        $acq->any('acquisition_add_line', '/acquisition/add-line', 'addLine');
        $acq->any('acquisition_receive', '/acquisition/receive', 'receive');
        $acq->any('acquisition_budgets', '/acquisition/budgets', 'budgets');
        $acq->any('acquisition_batch_capture', '/acquisition/batch-capture', 'batchCapture');
        $acq->any('acquisition_bulk_import', '/acquisition/bulk-import', 'bulkImport');
        $acq->any('acquisition_bulk_import_sample', '/acquisition/bulk-import-sample', 'bulkImportSample');
        $acq->any('acquisition_index', '/acquisition', 'index');
        $acq->register($routing);

        // Serial module routes
        $serial = new \AtomFramework\Routing\RouteLoader('serial');
        $serial->any('serial_view', '/serial/view/:id', 'view', ['id' => '\d+']);
        $serial->any('serial_edit', '/serial/edit/:id', 'edit', ['id' => '\d*']);
        $serial->any('serial_checkin', '/serial/checkin', 'checkin');
        $serial->any('serial_claim', '/serial/claim', 'claim');
        $serial->any('serial_bindery', '/serial/bindery', 'bindery');
        $serial->any('serial_index', '/serial', 'index');
        $serial->register($routing);

        // ILL module routes
        $ill = new \AtomFramework\Routing\RouteLoader('ill');
        $ill->any('ill_view', '/ill/view/:id', 'view', ['id' => '\d+']);
        $ill->any('ill_edit', '/ill/edit', 'edit');
        $ill->any('ill_status', '/ill/status', 'status');
        $ill->any('ill_index', '/ill', 'index');
        $ill->register($routing);

        // KBart Vendor management routes
        $kbart = new \AtomFramework\Routing\RouteLoader('kbartVendor');
        $kbart->any('kbart_export', '/library/kbart/export', 'export');
        $kbart->any('kbart_vendor_index', '/library/kbart/vendors', 'index');
        $kbart->any('kbart_vendor_add', '/library/kbart/vendor/add', 'add');
        $kbart->any('kbart_vendor_edit', '/library/kbart/vendor/edit/:id', 'edit', ['id' => '\d+']);
        $kbart->any('kbart_vendor_toggle', '/library/kbart/vendor/toggle/:id', 'toggle', ['id' => '\d+']);
        $kbart->any('kbart_vendor_delete', '/library/kbart/vendor/delete/:id', 'delete', ['id' => '\d+']);
        $kbart->any('kbart_vendor_fetch', '/library/kbart/vendor/fetch/:id', 'fetch', ['id' => '\d+']);
        $kbart->any('kbart_vendor_log', '/library/kbart/vendor/log/:id', 'importLog', ['id' => '\d+']);
        $kbart->register($routing);

        // OpenURL link resolver (#110)
        $openurl = new \AtomFramework\Routing\RouteLoader('openurl');
        $openurl->any('openurl_resolve', '/openurl', 'index');
        $openurl->register($routing);

        // Library Reports — COUNTER / SUSHI / FRBR (admin-only)
        $reports = new \AtomFramework\Routing\RouteLoader('libraryReports');
        $reports->any('library_reports_counter', '/admin/library/counter', 'counter');
        $reports->any('library_reports_odi', '/admin/library/odi', 'odi');
        $reports->any('library_reports_sushi', '/admin/library/sushi', 'sushiSettings');
        $reports->any('library_reports_frbr', '/admin/library/frbr', 'frbrOverride');
        $reports->any('library_reports_catalogue', '/admin/library/catalogue', 'catalogue');
        $reports->any('library_reports_creators', '/admin/library/creators', 'creators');
        $reports->any('library_reports_subjects', '/admin/library/subjects', 'subjects');
        $reports->register($routing);

        // Z39.50 client + SRU server control panel
        $z3950 = new \AtomFramework\Routing\RouteLoader('z3950');
        $z3950->any('z3950_index', '/library/z3950', 'index');
        $z3950->any('z3950_edit', '/library/z3950/edit', 'edit');
        $z3950->any('z3950_edit_id', '/library/z3950/edit/:id', 'edit', ['id' => '\d*']);
        $z3950->any('z3950_test', '/library/z3950/test', 'test');
        $z3950->any('z3950_delete', '/library/z3950/delete/:id', 'delete', ['id' => '\d+']);
        $z3950->any('z3950_sru', '/api/sru', 'sru');
        $z3950->register($routing);

        // Subject Authority Control routes
        $authority = new \AtomFramework\Routing\RouteLoader('authorityControl');
        $authority->any('authority_search', '/library/authority/search', 'search');
        $authority->any('authority_unlink', '/library/authority/unlink/:linkId', 'unlink', ['linkId' => '\d+']);
        $authority->any('authority_link_store', '/library/authority/link', 'link');
        $authority->any('authority_link', '/library/authority/link/:id', 'link', ['id' => '\d+']);
        $authority->any('authority_delete', '/library/authority/delete/:id', 'delete', ['id' => '\d+']);
        $authority->any('authority_edit_id', '/library/authority/edit/:id', 'edit', ['id' => '\d+']);
        $authority->any('authority_edit', '/library/authority/edit', 'edit');
        $authority->any('authority_view', '/library/authority/view/:id', 'view', ['id' => '\d+']);
        $authority->any('authority_index', '/library/authority', 'index');
        $authority->register($routing);

        // EDI Trading Partner routes
        $tp = new \AtomFramework\Routing\RouteLoader('tradingPartner');
        $tp->any('trading_partner_preview', '/library/trading-partners/preview/:id', 'preview', ['id' => '\d+']);
        $tp->any('trading_partner_test', '/library/trading-partners/test/:id', 'test', ['id' => '\d+']);
        $tp->any('trading_partner_toggle', '/library/trading-partners/toggle/:id', 'toggle', ['id' => '\d+']);
        $tp->any('trading_partner_delete', '/library/trading-partners/delete/:id', 'delete', ['id' => '\d+']);
        $tp->any('trading_partner_edit_id', '/library/trading-partners/edit/:id', 'edit', ['id' => '\d+']);
        $tp->any('trading_partner_edit', '/library/trading-partners/edit', 'edit');
        $tp->any('trading_partner_index', '/library/trading-partners', 'index');
        $tp->register($routing);

        // Copy Cataloguing (Z39.50 search → preview → import) routes
        $cc = new \AtomFramework\Routing\RouteLoader('copyCataloguing');
        $cc->any('copy_cataloguing_import', '/library/copy-cataloguing/import', 'import');
        $cc->any('copy_cataloguing_index', '/library/copy-cataloguing', 'index');
        $cc->register($routing);
    }
}