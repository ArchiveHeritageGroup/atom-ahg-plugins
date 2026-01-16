<?php

class ahgProvenancePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Chain of custody and provenance tracking plugin';
    public static $version = '1.0.0';

    public function contextLoadFactories(sfEvent $event)
    {
        $context = $event->getSubject();
        
        // Add CSS
        $context->response->addStylesheet('/plugins/ahgProvenancePlugin/css/provenance.css', 'last');
        
        // Add JS
        $context->response->addJavaScript('/plugins/ahgProvenancePlugin/js/provenance.js', 'last');
    }

    public function initialize()
    {
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        
        // Enable module
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'provenance';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }
}
