<?php

class ahgProvenancePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Chain of custody and provenance tracking plugin';
    public static $version = '1.0.0';

    public function contextLoadFactories(sfEvent $event)
    {
        $context = $event->getSubject();
        
        // Add CSS
        $context->response->addStylesheet('/plugins/ahgProvenancePlugin/web/css/provenance.css', 'last');
        
        // Add JS
        $context->response->addJavaScript('/plugins/ahgProvenancePlugin/web/js/provenance.js', 'last');
    }

    public function initialize()
    {

        // Contribute this plugin's own navigation entry.
        //
        // Registered here rather than named by the theme, so the entry exists
        // exactly while this plugin is enabled and appears on any theme.
        // Without it the plugin was reachable only by typing its URL.
        if (class_exists('AhgNav')) {
            AhgNav::register('manage', 'provenance', [
                'url' => '/index.php/provenance',
                'label' => 'Provenance',
            'credentials' => ['editor', 'administrator'],
                'weight' => 40,
            ]);
        }
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);

        // Register PSR-4 autoloader for namespaced classes
        $this->registerAutoloader();

        // Show the provenance panel on stock description pages. Only theme templates
        // call the provenanceDisplay component, so without this an install with no AHG
        // theme can capture provenance but never display it.
        require_once __DIR__ . '/../lib/Listeners/ProvenanceInjector.php';
        $this->dispatcher->connect('response.filter_content', ['\AhgProvenancePlugin\Listeners\ProvenanceInjector', 'filter']);

        // Enable module
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'provenance';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }

    /**
     * Trace API + coverage routes. Other provenance actions use default routing.
     */
    public function addRoutes(sfEvent $event)
    {
        if (!class_exists('\\AtomFramework\\Routing\\RouteLoader')) {
            return;
        }
        $r = new \AtomFramework\Routing\RouteLoader('provenance');
        // NB: nginx owns "location ^~ /api/provenance/" (proxied elsewhere), so
        // these API routes live under /provenance/ to reach Symfony.
        $r->any('provenance_coverage', '/provenance/coverage', 'coverage');
        $r->any('provenance_coverage_data', '/provenance/coverage-data', 'apiCoverage');
        $r->any('provenance_api_trace', '/provenance/trace/:id', 'apiTrace', ['id' => '\d+']);
        $r->any('provenance_authenticity', '/provenance/authenticity/:id', 'authenticity', ['id' => '\d+']);
        $r->register($event->getSubject());
    }

    protected function registerAutoloader()
    {
        $libPath = sfConfig::get('sf_plugins_dir') . '/ahgProvenancePlugin/lib';
        spl_autoload_register(function ($class) use ($libPath) {
            $prefix = 'AhgProvenancePlugin\\';
            if (strpos($class, $prefix) === 0) {
                $relativeClass = substr($class, strlen($prefix));
                $file = $libPath . '/' . str_replace('\\', '/', $relativeClass) . '.php';
                if (file_exists($file)) {
                    require_once $file;
                    return true;
                }
            }
            return false;
        });
    }
}
