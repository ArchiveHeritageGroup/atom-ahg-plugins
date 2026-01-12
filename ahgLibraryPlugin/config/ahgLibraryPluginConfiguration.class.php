<?php
class ahgLibraryPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Library & Bibliographic Cataloging';
    public static $version = '1.0.0';
    // Library level IDs (Book, Monograph, Periodical, Journal, Manuscript)
    public static $libraryLevelIds = [1700, 1701, 1702, 1703, 1704];
    
    public function initialize()
    {
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);
        $enabledModules = sfConfig::get('sf_enabled_modules');
        $enabledModules[] = 'ahgLibraryPlugin';
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
        $routing->prependRoute('library_view', new sfRoute(
            '/library/:slug',
            ['module' => 'ahgLibraryPlugin', 'action' => 'index'],
            ['slug' => '[^/]+']
        ));
        
        // Library edit
        $routing->prependRoute('library_edit', new sfRoute(
            '/library/:slug/edit',
            ['module' => 'ahgLibraryPlugin', 'action' => 'edit'],
            ['slug' => '[^/]+']
        ));
        
        // Library add
        $routing->prependRoute('library_add', new sfRoute(
            '/library/add',
            ['module' => 'ahgLibraryPlugin', 'action' => 'edit']
        ));
        
        // Library browse
        $routing->prependRoute('library_browse', new sfRoute(
            '/library',
            ['module' => 'ahgLibraryPlugin', 'action' => 'browse']
        ));
        
        // ISBN lookup
        $routing->prependRoute('library_isbn_lookup', new sfRoute(
            '/library/isbn-lookup',
            ['module' => 'ahgLibraryPlugin', 'action' => 'isbnLookup']
        ));
        
        // ISBN provider routes (specific - add LAST so checked FIRST)
        $routing->prependRoute('library_isbn_provider_delete', new sfRoute(
            '/library/isbn-provider/delete/:id',
            ['module' => 'ahgLibraryPlugin', 'action' => 'isbnProviderDelete']
        ));
        
        $routing->prependRoute('library_isbn_provider_toggle', new sfRoute(
            '/library/isbn-provider/toggle/:id',
            ['module' => 'ahgLibraryPlugin', 'action' => 'isbnProviderToggle']
        ));
        
        $routing->prependRoute('library_isbn_provider_edit', new sfRoute(
            '/library/isbn-provider/edit/:id',
            ['module' => 'ahgLibraryPlugin', 'action' => 'isbnProviderEdit'],
            ['id' => '\d*']
        ));
        
        $routing->prependRoute('library_isbn_providers', new sfRoute(
            '/library/isbn-providers',
            ['module' => 'ahgLibraryPlugin', 'action' => 'isbnProviders']
        ));
        
        // API route
        $routing->prependRoute('library_api_isbn', new sfRoute(
            '/api/library/isbn/:isbn',
            ['module' => 'ahgLibraryPlugin', 'action' => 'apiIsbnLookup']
        ));

        // Cover proxy route
        $routing->prependRoute('library_cover_proxy', new sfRoute(
            '/library/cover/:isbn',
            ['module' => 'ahgLibraryPlugin', 'action' => 'coverProxy']
        ));
    }
}
