<?php

class ahgTermTaxonomyPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'High-performance term and taxonomy browse for subjects, places, and genres';
    public static $version = '1.0.0';

    public function initialize()
    {
        $this->registerAutoloader();

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);
        $this->dispatcher->connect('controller.change_action', [$this, 'guardTermAddTaxonomy']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'termTaxonomy';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    /**
     * term/add takes the taxonomy as a URL ("/index.php/subjects"). Base AtoM
     * resolves it through routing and, when that finds nothing - a bare slug, a
     * /taxonomy/index/id/35 path, a typo - crashes: a 500 ("route does not
     * exist") or get_class(null) in QubitAcl (CallHub CH-000214). Base is locked,
     * so an unresolvable taxonomy is answered here with a 404 instead.
     */
    public function guardTermAddTaxonomy(sfEvent $event)
    {
        $params = $event->getParameters();
        // term/add may be served by the edit action; only the add form carries ?taxonomy.
        if ('term' !== ($params['module'] ?? '') || !in_array($params['action'] ?? '', ['add', 'edit'], true)) {
            return;
        }
        $taxonomy = (string) sfContext::getInstance()->getRequest()->getParameter('taxonomy', '');
        if ('' === $taxonomy) {
            return;
        }

        $path = (string) parse_url($taxonomy, PHP_URL_PATH);
        $slug = basename($path);
        $known = '' !== $slug && '/' === substr($path, 0, 1)
            && \Illuminate\Database\Capsule\Manager::table('slug as s')
                ->join('taxonomy as t', 't.id', '=', 's.object_id')
                ->where('s.slug', $slug)->exists();
        if (!$known) {
            throw new sfError404Exception('Unknown taxonomy');
        }
    }

    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgTermTaxonomy\\') === 0) {
                $relativePath = str_replace('AhgTermTaxonomy\\', '', $class);
                $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativePath);
                $filePath = __DIR__ . '/../lib/' . $relativePath . '.php';

                if (file_exists($filePath)) {
                    require_once $filePath;
                    return true;
                }
            }

            return false;
        });
    }

    public function loadRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        // All routes target termTaxonomy module
        // Catch-all slug routes registered first = checked last
        $router = new \AtomFramework\Routing\RouteLoader('termTaxonomy');
        $router->any('term_edit_override', '/term/:slug/edit', 'edit', ['slug' => '[a-zA-Z0-9_-]+']);
        $router->any('term_delete_override', '/term/:slug/delete', 'delete', ['slug' => '[a-zA-Z0-9_-]+']);
        $router->any('term_move', '/term/:slug/move', 'move', ['slug' => '[a-zA-Z0-9_-]+']);
        $router->any('term_browse_override', '/term/:slug', 'index', ['slug' => '[a-zA-Z0-9_-]+']);
        // Taxonomy route with numeric ID
        $router->any('taxonomy_browse_override', '/taxonomy/:id', 'taxonomyIndex', ['id' => '\d+']);
        $router->register($routing);
    }
}
