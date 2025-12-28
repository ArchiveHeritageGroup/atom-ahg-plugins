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
        
        // IMPORTANT: More specific routes must be added LAST when using prependRoute
        // because prependRoute adds to the FRONT of the routing table
        // So we add in reverse order: slug (most general) first, then more specific
        
        // API routes (most specific)
        $routing->prependRoute('library_api_isbn', new sfRoute(
            '/api/library/isbn/:isbn',
            ['module' => 'ahgLibraryPlugin', 'action' => 'apiIsbnLookup']
        ));
        
        // Library view by slug (catch-all - add first so it's checked LAST)
        $routing->prependRoute('library_view', new sfRoute(
            '/library/:slug',
            ['module' => 'ahgLibraryPlugin', 'action' => 'index'],
            ['slug' => '[^/]+']
        ));
        
        // Library edit (add after slug so checked before)
        $routing->prependRoute('library_edit', new sfRoute(
            '/library/edit/:slug',
            ['module' => 'ahgLibraryPlugin', 'action' => 'edit']
        ));
        
        // Library add (specific path)
        $routing->prependRoute('library_add', new sfRoute(
            '/library/add',
            ['module' => 'ahgLibraryPlugin', 'action' => 'add']
        ));
        
        // Library browse (specific path - add LAST so checked FIRST)
        $routing->prependRoute('library_browse', new sfRoute(
            '/library/browse',
            ['module' => 'ahgLibraryPlugin', 'action' => 'browse']
        ));
    }
    
    /**
     * Check if an information object is a library item
     */
    public static function isLibraryItem($resource): bool
    {
        if (!$resource || !isset($resource->levelOfDescriptionId)) {
            return false;
        }
        return in_array($resource->levelOfDescriptionId, self::$libraryLevelIds);
    }
}
