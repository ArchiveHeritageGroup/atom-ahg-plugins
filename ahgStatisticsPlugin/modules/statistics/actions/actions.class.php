<?php

class statisticsActions extends AhgActions
{
    protected ?StatisticsService $service = null;

    protected function getService(): StatisticsService
    {
        if ($this->service === null) {
            require_once sfConfig::get('sf_root_dir') . '/plugins/ahgStatisticsPlugin/lib/Services/StatisticsService.php';
            $this->service = new StatisticsService();
        }
        return $this->service;
    }

    protected function requireAuth(): void
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('user/login');
        }
    }

    protected function requireAdmin(): void
    {
        $this->requireAuth();
        if (!$this->context->user->hasCredential('administrator')) {
            $this->forward404('Administrator access required');
        }
    }

    // =========================================================================
    // DASHBOARD
    // =========================================================================

    public function executeDashboard(sfWebRequest $request)
    {
        $this->requireAdmin();

        $this->startDate = $request->getParameter('start', date('Y-m-d', strtotime('-30 days')));
        $this->endDate = $request->getParameter('end', date('Y-m-d'));

        $service = $this->getService();
        $this->stats = $service->getDashboardStats($this->startDate, $this->endDate);
        $this->topItems = $service->getTopItems('view', 10, $this->startDate, $this->endDate);
        $this->topDownloads = $service->getTopItems('download', 10, $this->startDate, $this->endDate);
        $this->geoStats = array_slice($service->getGeographicStats($this->startDate, $this->endDate), 0, 10);
        $this->viewsData = $service->getViewsOverTime($this->startDate, $this->endDate);
    }

    // =========================================================================
    // VIEWS REPORT
    // =========================================================================

    public function executeViews(sfWebRequest $request)
    {
        $this->requireAdmin();

        $this->startDate = $request->getParameter('start', date('Y-m-d', strtotime('-30 days')));
        $this->endDate = $request->getParameter('end', date('Y-m-d'));
        $this->groupBy = $request->getParameter('group', 'day');

        $this->data = $this->getService()->getViewsOverTime($this->startDate, $this->endDate, $this->groupBy);
    }

    // =========================================================================
    // DOWNLOADS REPORT
    // =========================================================================

    public function executeDownloads(sfWebRequest $request)
    {
        $this->requireAdmin();

        $this->startDate = $request->getParameter('start', date('Y-m-d', strtotime('-30 days')));
        $this->endDate = $request->getParameter('end', date('Y-m-d'));

        $this->data = $this->getService()->getDownloadsOverTime($this->startDate, $this->endDate);
        $this->topDownloads = $this->getService()->getTopItems('download', 50, $this->startDate, $this->endDate);
    }

    // =========================================================================
    // TOP ITEMS
    // =========================================================================

    public function executeTopItems(sfWebRequest $request)
    {
        $this->requireAdmin();

        $this->startDate = $request->getParameter('start', date('Y-m-d', strtotime('-30 days')));
        $this->endDate = $request->getParameter('end', date('Y-m-d'));
        $this->eventType = $request->getParameter('type', 'view');
        $this->limit = min((int) $request->getParameter('limit', 50), 500);

        $this->items = $this->getService()->getTopItems($this->eventType, $this->limit, $this->startDate, $this->endDate);
    }

    // =========================================================================
    // GEOGRAPHIC
    // =========================================================================

    public function executeGeographic(sfWebRequest $request)
    {
        $this->requireAdmin();

        $this->startDate = $request->getParameter('start', date('Y-m-d', strtotime('-30 days')));
        $this->endDate = $request->getParameter('end', date('Y-m-d'));

        $this->data = $this->getService()->getGeographicStats($this->startDate, $this->endDate);
    }

    // =========================================================================
    // ITEM STATISTICS
    // =========================================================================

    public function executeItem(sfWebRequest $request)
    {
        $objectId = (int) $request->getParameter('object_id');

        $this->startDate = $request->getParameter('start', date('Y-m-d', strtotime('-30 days')));
        $this->endDate = $request->getParameter('end', date('Y-m-d'));

        // Get object info
        $this->object = Qubit::db()->table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
            ->where('io.id', $objectId)
            ->select('io.id', 'io.identifier', 'ioi.title', 's.slug')
            ->first();

        if (!$this->object) {
            $this->forward404('Object not found');
        }

        $this->stats = $this->getService()->getItemStats($objectId, $this->startDate, $this->endDate);
    }

    // =========================================================================
    // REPOSITORY STATISTICS
    // =========================================================================

    public function executeRepository(sfWebRequest $request)
    {
        $this->requireAuth();

        $repositoryId = (int) $request->getParameter('id');

        $this->startDate = $request->getParameter('start', date('Y-m-d', strtotime('-30 days')));
        $this->endDate = $request->getParameter('end', date('Y-m-d'));

        // Get repository info
        $this->repository = Qubit::db()->table('actor as a')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('a.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
            })
            ->where('a.id', $repositoryId)
            ->select('a.id', 'ai.authorized_form_of_name as name')
            ->first();

        if (!$this->repository) {
            $this->forward404('Repository not found');
        }

        $this->stats = $this->getService()->getRepositoryStats($repositoryId, $this->startDate, $this->endDate);
    }

    // =========================================================================
    // EXPORT
    // =========================================================================

    public function executeExport(sfWebRequest $request)
    {
        $this->requireAdmin();

        $type = $request->getParameter('type', 'views');
        $startDate = $request->getParameter('start', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->getParameter('end', date('Y-m-d'));

        $csv = $this->getService()->exportToCsv($type, $startDate, $endDate);

        $this->getResponse()->setContentType('text/csv');
        $this->getResponse()->setHttpHeader('Content-Disposition', "attachment; filename=\"statistics_{$type}_{$startDate}_{$endDate}.csv\"");

        return $this->renderText($csv);
    }

    // =========================================================================
    // ADMIN: CONFIGURATION
    // =========================================================================

    public function executeAdmin(sfWebRequest $request)
    {
        $this->requireAdmin();

        $service = $this->getService();

        if ($request->isMethod('post')) {
            $settings = [
                'retention_days' => $request->getParameter('retention_days'),
                'geoip_enabled' => $request->getParameter('geoip_enabled', 0),
                'geoip_database_path' => $request->getParameter('geoip_database_path'),
                'bot_filtering_enabled' => $request->getParameter('bot_filtering_enabled', 0),
                'anonymize_ip' => $request->getParameter('anonymize_ip', 0),
                'exclude_admin_views' => $request->getParameter('exclude_admin_views', 0),
            ];

            foreach ($settings as $key => $value) {
                $type = in_array($key, ['retention_days']) ? 'integer' : (in_array($key, ['geoip_database_path']) ? 'string' : 'boolean');
                $service->setConfig($key, $value, $type);
            }

            $this->context->user->setFlash('notice', 'Settings saved');
            $this->redirect('statistics/admin');
        }

        $this->config = [
            'retention_days' => $service->getConfig('retention_days', 90),
            'geoip_enabled' => $service->getConfig('geoip_enabled', true),
            'geoip_database_path' => $service->getConfig('geoip_database_path', '/usr/share/GeoIP/GeoLite2-City.mmdb'),
            'bot_filtering_enabled' => $service->getConfig('bot_filtering_enabled', true),
            'anonymize_ip' => $service->getConfig('anonymize_ip', true),
            'exclude_admin_views' => $service->getConfig('exclude_admin_views', true),
        ];

        // Database stats
        $this->dbStats = [
            'raw_events' => Qubit::db()->table('ahg_usage_event')->count(),
            'daily_aggregates' => Qubit::db()->table('ahg_statistics_daily')->count(),
            'monthly_aggregates' => Qubit::db()->table('ahg_statistics_monthly')->count(),
            'bot_patterns' => Qubit::db()->table('ahg_bot_list')->count(),
        ];
    }

    // =========================================================================
    // ADMIN: BOT LIST
    // =========================================================================

    public function executeBots(sfWebRequest $request)
    {
        $this->requireAdmin();

        $service = $this->getService();

        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action');

            if ($action === 'add') {
                $service->addBot([
                    'name' => $request->getParameter('name'),
                    'pattern' => $request->getParameter('pattern'),
                    'category' => $request->getParameter('category', 'crawler'),
                ]);
                $this->context->user->setFlash('notice', 'Bot pattern added');
            } elseif ($action === 'delete') {
                $service->deleteBot((int) $request->getParameter('id'));
                $this->context->user->setFlash('notice', 'Bot pattern deleted');
            } elseif ($action === 'toggle') {
                $id = (int) $request->getParameter('id');
                $bot = Qubit::db()->table('ahg_bot_list')->where('id', $id)->first();
                if ($bot) {
                    $service->updateBot($id, ['is_active' => $bot->is_active ? 0 : 1]);
                }
            }

            $this->redirect('statistics/admin/bots');
        }

        $this->bots = $service->getBotList();
    }

    // =========================================================================
    // API ENDPOINTS
    // =========================================================================

    public function executeApiChart(sfWebRequest $request)
    {
        $this->requireAdmin();

        $type = $request->getParameter('type', 'views');
        $startDate = $request->getParameter('start', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->getParameter('end', date('Y-m-d'));

        $service = $this->getService();

        $data = match ($type) {
            'views' => $service->getViewsOverTime($startDate, $endDate),
            'downloads' => $service->getDownloadsOverTime($startDate, $endDate),
            'geographic' => $service->getGeographicStats($startDate, $endDate),
            default => [],
        };

        $this->getResponse()->setContentType('application/json');
        return $this->renderText(json_encode($data));
    }

    public function executeApiSummary(sfWebRequest $request)
    {
        $this->requireAdmin();

        $startDate = $request->getParameter('start', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->getParameter('end', date('Y-m-d'));

        $stats = $this->getService()->getDashboardStats($startDate, $endDate);

        $this->getResponse()->setContentType('application/json');
        return $this->renderText(json_encode($stats));
    }

    /**
     * Tracking pixel endpoint.
     */
    public function executePixel(sfWebRequest $request)
    {
        // 1x1 transparent GIF
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        $this->getResponse()->setContentType('image/gif');
        $this->getResponse()->setHttpHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
        $this->getResponse()->setHttpHeader('Pragma', 'no-cache');
        $this->getResponse()->setHttpHeader('Expires', '0');

        return $this->renderText($gif);
    }
}
