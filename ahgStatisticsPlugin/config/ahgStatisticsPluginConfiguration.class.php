<?php

class ahgStatisticsPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Usage statistics tracking with views, downloads, and reporting';
    public static $version = '1.0.0';

    public function contextLoadFactories(sfEvent $event)
    {
        $context = $event->getSubject();
        $context->getConfiguration()->loadHelpers(['Asset', 'Url']);
    }

    public function initialize()
    {
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);

        // Connect to page view events for tracking
        $this->dispatcher->connect('response.filter_content', [$this, 'trackPageView']);

        $enabledModules = sfConfig::get('sf_enabled_modules');
        $enabledModules[] = 'statistics';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    public function trackPageView(sfEvent $event, $content)
    {
        // Only track in production and for GET requests
        if (sfConfig::get('sf_environment') === 'cli' || $_SERVER['REQUEST_METHOD'] !== 'GET') {
            return $content;
        }

        try {
            $context = sfContext::getInstance();
            $module = $context->getModuleName();
            $action = $context->getActionName();

            // Track information object views
            if ($module === 'informationobject' && $action === 'index') {
                $this->logView($context);
            }

            // Track digital object downloads
            if ($module === 'digitalobject' && in_array($action, ['download', 'view'])) {
                $this->logDownload($context);
            }
        } catch (Exception $e) {
            // Silently fail - don't break page rendering
            error_log('Statistics tracking error: ' . $e->getMessage());
        }

        return $content;
    }

    protected function logView($context): void
    {
        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgStatisticsPlugin/lib/Services/StatisticsService.php';
        $service = new StatisticsService();

        $request = $context->getRequest();
        $slug = $request->getParameter('slug');

        if ($slug) {
            $objectId = \Illuminate\Database\Capsule\Manager::table('slug')
                ->where('slug', $slug)
                ->value('object_id');

            if ($objectId) {
                $service->logEvent('view', 'information_object', $objectId);
            }
        }
    }

    protected function logDownload($context): void
    {
        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgStatisticsPlugin/lib/Services/StatisticsService.php';
        $service = new StatisticsService();

        $request = $context->getRequest();
        $slug = $request->getParameter('slug');

        if ($slug) {
            $digitalObject = \Illuminate\Database\Capsule\Manager::table('digital_object as d')
                ->join('slug as s', 'd.information_object_id', '=', 's.object_id')
                ->where('s.slug', $slug)
                ->first();

            if ($digitalObject) {
                $service->logEvent('download', 'digital_object', $digitalObject->id, $digitalObject->information_object_id);
            }
        }
    }

    public function addRoutes(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('statistics');

        // Dashboard
        $router->any('statistics_dashboard', '/statistics', 'dashboard');
        $router->any('statistics_index', '/statistics/dashboard', 'dashboard');

        // Reports
        $router->any('statistics_views', '/statistics/views', 'views');
        $router->any('statistics_downloads', '/statistics/downloads', 'downloads');
        $router->any('statistics_geographic', '/statistics/geographic', 'geographic');
        $router->any('statistics_top_items', '/statistics/top-items', 'topItems');

        // Item-level statistics
        $router->any('statistics_item', '/statistics/item/:object_id', 'item', ['object_id' => '\d+']);

        // Repository statistics
        $router->any('statistics_repository', '/statistics/repository/:id', 'repository', ['id' => '\d+']);

        // Export
        $router->any('statistics_export', '/statistics/export', 'export');

        // Admin: Configuration
        $router->any('statistics_admin', '/statistics/admin', 'admin');
        $router->any('statistics_bots', '/statistics/admin/bots', 'bots');

        // API for charts
        $router->any('statistics_api_chart', '/statistics/api/chart/:type', 'apiChart');
        $router->any('statistics_api_summary', '/statistics/api/summary', 'apiSummary');

        // Tracking pixel (for email opens, etc.)
        $router->any('statistics_pixel', '/statistics/pixel/:token', 'pixel');

        $router->register($event->getSubject());
    }
}
