<?php

class ahgSpectrumPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Spectrum 5.0 Museum Procedures Plugin';
    public static $version = '1.0.1';
    
    // Hard dependencies - none, this is the base
    public static $dependencies = [];
    
    // Plugins that depend on this one - will be disabled if this is disabled
    public static $dependents = ['ahgMuseumPlugin'];

    public function initialize()
    {
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);
    }

    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        // Spectrum index route
        $routing->prependRoute('spectrum_index', new sfRoute(
            '/:slug/spectrum',
            ['module' => 'spectrum', 'action' => 'index']
        ));

        // Spectrum workflow route
        $routing->prependRoute('spectrum_workflow', new sfRoute(
            '/spectrum/workflow',
            ['module' => 'spectrum', 'action' => 'workflow']
        ));

        // Spectrum label route
        $routing->prependRoute('spectrum_label', new sfRoute(
            '/:slug/spectrum/label',
            ['module' => 'spectrum', 'action' => 'label']
        ));

        // Spectrum dashboard route
        $routing->prependRoute('spectrum_dashboard', new sfRoute(
            '/spectrum/dashboard',
            ['module' => 'spectrum', 'action' => 'dashboard']
        ));
    }
}
