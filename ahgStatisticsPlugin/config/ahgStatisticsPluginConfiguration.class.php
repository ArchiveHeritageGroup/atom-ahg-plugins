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
        $routing = $event->getSubject();

        // Dashboard
        $routing->prependRoute('statistics_dashboard', new sfRoute(
            '/statistics',
            ['module' => 'statistics', 'action' => 'dashboard']
        ));
        $routing->prependRoute('statistics_index', new sfRoute(
            '/statistics/dashboard',
            ['module' => 'statistics', 'action' => 'dashboard']
        ));

        // Reports
        $routing->prependRoute('statistics_views', new sfRoute(
            '/statistics/views',
            ['module' => 'statistics', 'action' => 'views']
        ));
        $routing->prependRoute('statistics_downloads', new sfRoute(
            '/statistics/downloads',
            ['module' => 'statistics', 'action' => 'downloads']
        ));
        $routing->prependRoute('statistics_geographic', new sfRoute(
            '/statistics/geographic',
            ['module' => 'statistics', 'action' => 'geographic']
        ));
        $routing->prependRoute('statistics_top_items', new sfRoute(
            '/statistics/top-items',
            ['module' => 'statistics', 'action' => 'topItems']
        ));

        // Item-level statistics
        $routing->prependRoute('statistics_item', new sfRoute(
            '/statistics/item/:object_id',
            ['module' => 'statistics', 'action' => 'item'],
            ['object_id' => '\d+']
        ));

        // Repository statistics
        $routing->prependRoute('statistics_repository', new sfRoute(
            '/statistics/repository/:id',
            ['module' => 'statistics', 'action' => 'repository'],
            ['id' => '\d+']
        ));

        // Export
        $routing->prependRoute('statistics_export', new sfRoute(
            '/statistics/export',
            ['module' => 'statistics', 'action' => 'export']
        ));

        // Admin: Configuration
        $routing->prependRoute('statistics_admin', new sfRoute(
            '/statistics/admin',
            ['module' => 'statistics', 'action' => 'admin']
        ));
        $routing->prependRoute('statistics_bots', new sfRoute(
            '/statistics/admin/bots',
            ['module' => 'statistics', 'action' => 'bots']
        ));

        // API for charts
        $routing->prependRoute('statistics_api_chart', new sfRoute(
            '/statistics/api/chart/:type',
            ['module' => 'statistics', 'action' => 'apiChart']
        ));
        $routing->prependRoute('statistics_api_summary', new sfRoute(
            '/statistics/api/summary',
            ['module' => 'statistics', 'action' => 'apiSummary']
        ));

        // Tracking pixel (for email opens, etc.)
        $routing->prependRoute('statistics_pixel', new sfRoute(
            '/statistics/pixel/:token',
            ['module' => 'statistics', 'action' => 'pixel']
        ));
    }
}
