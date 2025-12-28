<?php
class ahgAuditTrailPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Comprehensive audit trail logging for AtoM';
    public static $version = '1.0.0';
    
    public function initialize(): void
    {
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'ahgAuditTrail';
        sfConfig::set('sf_enabled_modules', $enabledModules);
        
        // Connect to events for automatic audit logging
        $this->dispatcher->connect('context.load_factories', [$this, 'onContextLoadFactories']);
    }
    
    public function onContextLoadFactories(sfEvent $event): void
    {
        // Register audit components when context is ready
    }
}
