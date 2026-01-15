<?php
class ahgGalleryPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Gallery & Exhibition Management';
    public static $version = '1.0.0';
    
    public function initialize()
    {
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);
        $enabledModules = sfConfig::get('sf_enabled_modules');
        $enabledModules[] = 'ahgGalleryPlugin';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }
    
    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();
        
        // Order matters! Generic route first, then specific ones prepended after
        $routing->prependRoute('gallery_view', new AhgMetadataRoute(
            '/gallery/:slug',
            ['module' => 'ahgGalleryPlugin', 'action' => 'index'],
            ['slug' => '[a-zA-Z0-9_-]+']
        ));
        $routing->prependRoute('gallery_edit', new sfRoute(
            '/gallery/edit/:slug',
            ['module' => 'ahgGalleryPlugin', 'action' => 'edit'],
            ['slug' => '[a-zA-Z0-9_-]+']
        ));
        $routing->prependRoute('gallery_add', new sfRoute(
            '/gallery/add',
            ['module' => 'ahgGalleryPlugin', 'action' => 'add']
        ));
        $routing->prependRoute('gallery_browse', new sfRoute(
            '/gallery/browse',
            ['module' => 'ahgGalleryPlugin', 'action' => 'browse']
        ));
    }
}
