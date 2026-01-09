<?php
class ahgMigrationPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Universal data migration tool with sector-based destination mapping';
    public static $version = '1.0.0';

    public function contextLoadFactories(sfEvent $event)
    {
        $context = $event->getSubject();
        $context->response->addStylesheet('/plugins/ahgMigrationPlugin/css/migration.css', 'last');
        $context->response->addJavascript('/plugins/ahgMigrationPlugin/js/migration.js', 'last');
    }

    public function initialize()
    {
        $this->dispatcher->connect('context.load_factories', array($this, 'contextLoadFactories'));
        $enabledModules = sfConfig::get('sf_enabled_modules', array());
        $enabledModules[] = 'migration';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }
}
