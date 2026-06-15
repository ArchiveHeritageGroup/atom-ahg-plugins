<?php

/**
 * ahgAccessibilityPlugin Configuration
 *
 * WCAG accessibility tooling for archival descriptions. v1 ships human-authored
 * image alternative text (WCAG 1.1.1): a soft-referenced store, an authoring UI,
 * a coverage dashboard, and a consumer API that a CSP-safe front-end enhancer
 * uses to apply alt text to record-page images without touching the locked theme.
 */
class ahgAccessibilityPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'WCAG accessibility tooling (image alternative text)';
    public static $version = '1.0.0';

    public function initialize()
    {
        $this->registerAutoloader();

        $this->dispatcher->connect('routing.load_configuration', [$this, 'configureRouting']);
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'accessibility';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }

    /** PSR-4 autoloader for the AhgAccessibility namespace. */
    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgAccessibility\\') === 0) {
                $relativePath = str_replace(['AhgAccessibility\\', '\\'], ['', DIRECTORY_SEPARATOR], $class);
                $filePath = __DIR__ . '/../lib/' . $relativePath . '.php';
                if (file_exists($filePath)) {
                    require_once $filePath;

                    return true;
                }
            }

            return false;
        });
    }

    /** Routes for the accessibility module. */
    public function configureRouting(sfEvent $event)
    {
        $r = new \AtomFramework\Routing\RouteLoader('accessibility');

        // Coverage dashboard + authoring.
        $r->any('accessibility_index', '/accessibility/alt-text', 'index');
        $r->any('accessibility_edit', '/accessibility/alt-text/edit/:id', 'edit', ['id' => '\d+']);
        $r->any('accessibility_save', '/accessibility/alt-text/save', 'save');

        // Consumer API for the front-end enhancer + IIIF/other consumers.
        $r->any('accessibility_api_object', '/accessibility/alt-text/api/object/:id', 'apiObject', ['id' => '\d+']);
        $r->any('accessibility_api_slug', '/accessibility/alt-text/api/slug/:slug', 'apiSlug');

        $r->register($event->getSubject());
    }

    /** Load the front-end enhancer globally; it no-ops off record pages. */
    public function contextLoadFactories(sfEvent $event)
    {
        $context = $event->getSubject();
        $context->response->addJavaScript('/plugins/ahgAccessibilityPlugin/web/js/alt-text.js', 'last');
    }

    public static function getPluginPath(): string
    {
        return dirname(__DIR__);
    }
}
