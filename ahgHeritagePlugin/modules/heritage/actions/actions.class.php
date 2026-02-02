<?php

/**
 * Heritage module actions.
 *
 * Handles landing page, search, and API endpoints for the Heritage platform.
 */
class heritageActions extends sfActions
{
    /**
     * Landing page action.
     *
     * @param sfWebRequest $request
     */
    public function executeLanding(sfWebRequest $request)
    {
        // Bootstrap the framework
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $institutionId = $request->getParameter('institution_id');
        $culture = $this->context->user->getCulture();

        $controller = new \AtomFramework\Heritage\Controllers\Api\LandingController($culture);
        $response = $controller->index($institutionId ? (int) $institutionId : null, $culture);

        if (!$response['success']) {
            $this->error = $response['error'] ?? 'An error occurred';

            return sfView::ERROR;
        }

        $this->data = $response['data'];
        $this->config = $this->data['config'] ?? [];
        $this->heroImages = $this->data['hero_images'] ?? [];
        $this->filters = $this->data['filters'] ?? [];
        $this->stories = $this->data['stories'] ?? [];
        $this->recentActivity = $this->data['recent_activity'] ?? [];
        $this->recentAdditions = $this->data['recent_additions'] ?? [];
        $this->stats = $this->data['stats'] ?? [];

        // Fetch curated collections (IIIF + Archival)
        $this->curatedCollections = $this->getCuratedCollections($culture, 12);

        return sfView::SUCCESS;
    }

    /**
     * Get curated collections from the heritage_featured_collection table.
     * Only shows explicitly selected collections.
     */
    protected function getCuratedCollections(string $culture, int $limit = 12): array
    {
        $result = [];

        // Get featured collections from the selection table
        $featured = \Illuminate\Database\Capsule\Manager::table('heritage_featured_collection')
            ->where('is_enabled', 1)
            ->orderBy('display_order')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($featured as $item) {
            if ($item->source_type === 'iiif') {
                // Get IIIF collection details
                $collection = \Illuminate\Database\Capsule\Manager::table('iiif_collection as c')
                    ->leftJoin('iiif_collection_i18n as ci', function ($join) use ($culture) {
                        $join->on('c.id', '=', 'ci.collection_id')
                            ->where('ci.culture', '=', $culture);
                    })
                    ->where('c.id', $item->source_id)
                    ->select([
                        'c.id', 'c.name', 'c.slug', 'c.description', 'c.thumbnail_url',
                        \Illuminate\Database\Capsule\Manager::raw('COALESCE(ci.name, c.name) as display_name'),
                        \Illuminate\Database\Capsule\Manager::raw('COALESCE(ci.description, c.description) as display_description'),
                    ])
                    ->first();

                if (!$collection) continue;

                $itemCount = \Illuminate\Database\Capsule\Manager::table('iiif_collection_item')
                    ->where('collection_id', $collection->id)
                    ->count();

                $thumbnail = $item->thumbnail_path ?: $collection->thumbnail_url;
                if (!$thumbnail) {
                    $firstItem = \Illuminate\Database\Capsule\Manager::table('iiif_collection_item as ci')
                        ->leftJoin('digital_object as do', 'ci.object_id', '=', 'do.object_id')
                        ->where('ci.collection_id', $collection->id)
                        ->whereNotNull('ci.object_id')
                        ->select(['do.path', 'do.name'])
                        ->orderBy('ci.sort_order')
                        ->first();

                    if ($firstItem && $firstItem->path && $firstItem->name) {
                        $thumbnail = rtrim($firstItem->path, '/') . '/' . pathinfo($firstItem->name, PATHINFO_FILENAME) . '_142.jpg';
                    }
                }

                $result[] = [
                    'type' => 'iiif',
                    'id' => $collection->id,
                    'name' => $item->title ?: ($collection->display_name ?: $collection->name),
                    'slug' => $collection->slug,
                    'description' => $item->description ?: ($collection->display_description ?: $collection->description),
                    'thumbnail' => $thumbnail,
                    'item_count' => $itemCount,
                    'sort_order' => $item->display_order,
                ];
            } else {
                // Get archival collection (information_object) details
                $collection = \Illuminate\Database\Capsule\Manager::table('information_object as io')
                    ->leftJoin('information_object_i18n as ioi', function ($join) use ($culture) {
                        $join->on('io.id', '=', 'ioi.id')
                            ->where('ioi.culture', '=', $culture);
                    })
                    ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
                    ->leftJoin('digital_object as do', 'io.id', '=', 'do.object_id')
                    ->where('io.id', $item->source_id)
                    ->select([
                        'io.id', 'ioi.title', 'ioi.scope_and_content as description',
                        's.slug', 'do.path as thumb_path', 'do.name as thumb_name',
                        'io.lft', 'io.rgt',
                    ])
                    ->first();

                if (!$collection) continue;

                $itemCount = (int) (($collection->rgt - $collection->lft - 1) / 2);

                $thumbnail = $item->thumbnail_path;
                if (!$thumbnail && $collection->thumb_path && $collection->thumb_name) {
                    $thumbnail = rtrim($collection->thumb_path, '/') . '/' . pathinfo($collection->thumb_name, PATHINFO_FILENAME) . '_142.jpg';
                }
                if (!$thumbnail) {
                    $firstChild = \Illuminate\Database\Capsule\Manager::table('information_object as io')
                        ->join('digital_object as do', 'io.id', '=', 'do.object_id')
                        ->where('io.lft', '>', $collection->lft)
                        ->where('io.rgt', '<', $collection->rgt)
                        ->select(['do.path', 'do.name'])
                        ->orderBy('io.lft')
                        ->first();

                    if ($firstChild && $firstChild->path && $firstChild->name) {
                        $thumbnail = rtrim($firstChild->path, '/') . '/' . pathinfo($firstChild->name, PATHINFO_FILENAME) . '_142.jpg';
                    }
                }

                $result[] = [
                    'type' => 'archival',
                    'id' => $collection->id,
                    'name' => $item->title ?: $collection->title,
                    'slug' => $collection->slug,
                    'description' => $item->description ?: $collection->description,
                    'thumbnail' => $thumbnail,
                    'item_count' => $itemCount,
                    'sort_order' => $item->display_order,
                ];
            }
        }

        return $result;
    }

    /**
     * Search results page action.
     *
     * @param sfWebRequest $request
     */
    public function executeSearch(sfWebRequest $request)
    {
        // Bootstrap the framework
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $params = [
            'query' => $request->getParameter('q', ''),
            'filters' => $request->getParameter('filters', []),
            'page' => (int) $request->getParameter('page', 1),
            'limit' => (int) $request->getParameter('limit', 20),
            'institution_id' => $request->getParameter('institution_id'),
            'culture' => $this->context->user->getCulture(),
        ];

        // Parse filters from query string if needed
        if (is_string($params['filters'])) {
            $params['filters'] = json_decode($params['filters'], true) ?: [];
        }

        // Also check for individual filter params (e.g., ?content_type[]=photograph)
        foreach ($request->getParameterHolder()->getAll() as $key => $value) {
            if (is_array($value) && !in_array($key, ['filters', 'q', 'page', 'limit'])) {
                $params['filters'][$key] = $value;
            }
        }

        $culture = $params['culture'];
        $controller = new \AtomFramework\Heritage\Controllers\Api\DiscoverController($culture);
        $response = $controller->search($params);

        if (!$response['success']) {
            $this->error = $response['error'] ?? 'Search failed';

            return sfView::ERROR;
        }

        $this->results = $response['data'];
        $this->query = $params['query'];
        $this->filters = $params['filters'];
        $this->page = $params['page'];
        $this->limit = $params['limit'];

        // Get filter options for sidebar
        $filterService = new \AtomFramework\Heritage\Filters\FilterService($culture);
        $this->filterOptions = $filterService->getFiltersWithValues($params['institution_id']);

        return sfView::SUCCESS;
    }

    /**
     * API: Get landing page data.
     *
     * @param sfWebRequest $request
     */
    public function executeApiLanding(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $institutionId = $request->getParameter('institution_id');
        $culture = $this->context->user->getCulture();

        $controller = new \AtomFramework\Heritage\Controllers\Api\LandingController($culture);
        $response = $controller->index($institutionId ? (int) $institutionId : null, $culture);

        return $this->renderJson($response);
    }

    /**
     * API: Search/discover.
     *
     * @param sfWebRequest $request
     */
    public function executeApiDiscover(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        // Get JSON body for POST requests
        $params = [];
        if ($request->isMethod('post')) {
            $content = $request->getContent();
            $params = json_decode($content, true) ?: [];
        } else {
            $params = [
                'query' => $request->getParameter('q', ''),
                'filters' => $request->getParameter('filters', []),
                'page' => (int) $request->getParameter('page', 1),
                'limit' => (int) $request->getParameter('limit', 20),
            ];
        }

        $params['culture'] = $this->context->user->getCulture();

        $controller = new \AtomFramework\Heritage\Controllers\Api\DiscoverController($params['culture']);

        // Validate params
        $errors = $controller->validateParams($params);
        if (!empty($errors)) {
            return $this->renderJson([
                'success' => false,
                'errors' => $errors,
            ]);
        }

        $response = $controller->search($params);

        return $this->renderJson($response);
    }

    /**
     * API: Autocomplete suggestions.
     *
     * @param sfWebRequest $request
     */
    public function executeApiAutocomplete(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $query = $request->getParameter('q', '');
        $institutionId = $request->getParameter('institution_id');
        $limit = min(20, max(1, (int) $request->getParameter('limit', 10)));
        $culture = $this->context->user->getCulture();

        $controller = new \AtomFramework\Heritage\Controllers\Api\DiscoverController($culture);
        $response = $controller->autocomplete($query, $institutionId ? (int) $institutionId : null, $limit);

        return $this->renderJson($response);
    }

    /**
     * Admin: Configuration page.
     *
     * @param sfWebRequest $request
     */
    public function executeAdminConfig(sfWebRequest $request)
    {
        // Check admin access
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $institutionId = $request->getParameter('institution_id');
        $culture = $this->context->user->getCulture();

        $controller = new \AtomFramework\Heritage\Controllers\Api\Admin\ConfigController($culture);

        // Handle POST (save)
        if ($request->isMethod('post')) {
            $data = $request->getParameterHolder()->getAll();
            unset($data['module'], $data['action'], $data['institution_id']);

            // Convert checkbox values
            foreach (['show_curated_stories', 'show_community_activity', 'show_filters', 'show_stats', 'show_recent_additions'] as $field) {
                $data[$field] = isset($data[$field]) ? 1 : 0;
            }

            // Handle suggested searches as array
            if (isset($data['suggested_searches']) && is_string($data['suggested_searches'])) {
                $data['suggested_searches'] = array_filter(array_map('trim', explode("\n", $data['suggested_searches'])));
            }

            $response = $controller->updateLandingConfig($data, $institutionId ? (int) $institutionId : null);

            if ($response['success']) {
                $this->getUser()->setFlash('notice', 'Configuration saved successfully');
            } else {
                $this->getUser()->setFlash('error', $response['error'] ?? 'Failed to save configuration');
            }

            $this->redirect(['module' => 'heritage', 'action' => 'adminConfig']);
        }

        // Get current config
        $response = $controller->getLandingConfig($institutionId ? (int) $institutionId : null);
        $this->config = $response['data'] ?? null;

        // Get filters
        $filterResponse = $controller->getFilters($institutionId ? (int) $institutionId : null);
        $this->filters = $filterResponse['data'] ?? [];

        // Get stories
        $storyResponse = $controller->getStories($institutionId ? (int) $institutionId : null);
        $this->stories = $storyResponse['data'] ?? [];

        // Get hero images
        $heroResponse = $controller->getHeroImages($institutionId ? (int) $institutionId : null);
        $this->heroImages = $heroResponse['data'] ?? [];

        return sfView::SUCCESS;
    }

    /**
     * API: Log a click on a search result.
     *
     * @param sfWebRequest $request
     */
    public function executeApiClick(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        if (!$request->isMethod('post')) {
            return $this->renderJson([
                'success' => false,
                'error' => 'POST method required',
            ]);
        }

        $content = $request->getContent();
        $params = json_decode($content, true) ?: [];
        $culture = $this->context->user->getCulture();

        $controller = new \AtomFramework\Heritage\Controllers\Api\DiscoverController($culture);
        $response = $controller->logClick($params);

        return $this->renderJson($response);
    }

    /**
     * API: Update dwell time for a click.
     *
     * @param sfWebRequest $request
     */
    public function executeApiDwell(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        if (!$request->isMethod('post')) {
            return $this->renderJson([
                'success' => false,
                'error' => 'POST method required',
            ]);
        }

        $content = $request->getContent();
        $params = json_decode($content, true) ?: [];
        $culture = $this->context->user->getCulture();

        $controller = new \AtomFramework\Heritage\Controllers\Api\DiscoverController($culture);
        $response = $controller->updateDwellTime($params);

        return $this->renderJson($response);
    }

    /**
     * API: Get search analytics.
     *
     * @param sfWebRequest $request
     */
    public function executeApiAnalytics(sfWebRequest $request)
    {
        // Check admin access
        if (!$this->context->user->isAdministrator()) {
            return $this->renderJson([
                'success' => false,
                'error' => 'Unauthorized',
            ]);
        }

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $institutionId = $request->getParameter('institution_id');
        $days = (int) $request->getParameter('days', 30);
        $culture = $this->context->user->getCulture();

        $controller = new \AtomFramework\Heritage\Controllers\Api\DiscoverController($culture);
        $response = $controller->analytics(
            $institutionId ? (int) $institutionId : null,
            $days
        );

        return $this->renderJson($response);
    }

    /**
     * Render JSON response.
     */
    protected function renderJson(array $data)
    {
        $this->getResponse()->setContentType('application/json');
        $this->getResponse()->setContent(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return sfView::NONE;
    }

    // ========================================================================
    // SESSION 8: ADMIN CONFIGURATION
    // ========================================================================

    /**
     * Admin dashboard.
     */
    public function executeAdminDashboard(sfWebRequest $request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $institutionId = $request->getParameter('institution_id');
        $culture = $this->context->user->getCulture();

        $configController = new \AtomFramework\Heritage\Controllers\Api\Admin\ConfigController($culture);
        $analyticsController = new \AtomFramework\Heritage\Controllers\Api\AnalyticsController();

        // Get dashboard data
        $this->userStats = $configController->getUserStats()['data'] ?? [];
        $this->alertCounts = $analyticsController->getAlertCounts($institutionId ? (int) $institutionId : null)['data'] ?? [];

        return sfView::SUCCESS;
    }

    /**
     * Admin feature toggles.
     */
    public function executeAdminFeatures(sfWebRequest $request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $institutionId = $request->getParameter('institution_id');
        $culture = $this->context->user->getCulture();

        $controller = new \AtomFramework\Heritage\Controllers\Api\Admin\ConfigController($culture);

        // Handle POST
        if ($request->isMethod('post')) {
            $featureCode = $request->getParameter('feature_code');
            $action = $request->getParameter('toggle_action');

            if ($featureCode && $action === 'toggle') {
                $controller->toggleFeature($featureCode, $institutionId ? (int) $institutionId : null);
                $this->getUser()->setFlash('notice', 'Feature toggle updated');
            }

            $this->redirect(['module' => 'heritage', 'action' => 'adminFeatures']);
        }

        $response = $controller->getFeatureToggles($institutionId ? (int) $institutionId : null);
        $this->features = $response['data'] ?? [];

        return sfView::SUCCESS;
    }

    /**
     * Admin branding configuration.
     */
    public function executeAdminBranding(sfWebRequest $request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $institutionId = $request->getParameter('institution_id');
        $culture = $this->context->user->getCulture();

        $controller = new \AtomFramework\Heritage\Controllers\Api\Admin\ConfigController($culture);

        // Handle POST
        if ($request->isMethod('post')) {
            $data = $request->getParameterHolder()->getAll();
            unset($data['module'], $data['action'], $data['institution_id']);

            // Handle social links as JSON
            if (isset($data['social_facebook']) || isset($data['social_twitter'])) {
                $data['social_links'] = [
                    'facebook' => $data['social_facebook'] ?? null,
                    'twitter' => $data['social_twitter'] ?? null,
                    'instagram' => $data['social_instagram'] ?? null,
                    'linkedin' => $data['social_linkedin'] ?? null,
                ];
                unset($data['social_facebook'], $data['social_twitter'], $data['social_instagram'], $data['social_linkedin']);
            }

            $response = $controller->updateBrandingConfig($data, $institutionId ? (int) $institutionId : null);

            if ($response['success']) {
                $this->getUser()->setFlash('notice', 'Branding configuration saved');
            } else {
                $this->getUser()->setFlash('error', $response['error'] ?? 'Failed to save configuration');
            }

            $this->redirect(['module' => 'heritage', 'action' => 'adminBranding']);
        }

        $response = $controller->getBrandingConfig($institutionId ? (int) $institutionId : null);
        $this->branding = $response['data'] ?? [];

        return sfView::SUCCESS;
    }

    /**
     * Admin user management.
     */
    public function executeAdminUsers(sfWebRequest $request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $culture = $this->context->user->getCulture();
        $controller = new \AtomFramework\Heritage\Controllers\Api\Admin\ConfigController($culture);

        $params = [
            'page' => (int) $request->getParameter('page', 1),
            'limit' => 25,
            'search' => $request->getParameter('search'),
            'trust_level' => $request->getParameter('trust_level'),
        ];

        $response = $controller->getUsers($params);
        $this->userData = $response['data'] ?? [];

        $trustResponse = $controller->getTrustLevels();
        $this->trustLevels = $trustResponse['data'] ?? [];

        return sfView::SUCCESS;
    }

    /**
     * Admin featured/curated collections management.
     */
    public function executeAdminFeaturedCollections(sfWebRequest $request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $culture = $this->context->user->getCulture();
        $action = $request->getParameter('featured_action');
        $featuredId = $request->getParameter('featured_id');

        // Handle POST actions
        if ($request->isMethod('post')) {
            try {
                if ($action === 'add') {
                    $sourceType = $request->getParameter('source_type');
                    $sourceId = (int) $request->getParameter('source_id');
                    $title = $request->getParameter('title');
                    $description = $request->getParameter('description');
                    $displayOrder = (int) $request->getParameter('display_order', 100);

                    // Check if already exists
                    $exists = \Illuminate\Database\Capsule\Manager::table('heritage_featured_collection')
                        ->where('source_type', $sourceType)
                        ->where('source_id', $sourceId)
                        ->exists();

                    if ($exists) {
                        $this->getUser()->setFlash('error', 'This collection is already featured');
                    } else {
                        \Illuminate\Database\Capsule\Manager::table('heritage_featured_collection')->insert([
                            'source_type' => $sourceType,
                            'source_id' => $sourceId,
                            'title' => $title ?: null,
                            'description' => $description ?: null,
                            'display_order' => $displayOrder,
                            'is_enabled' => 1,
                        ]);
                        $this->getUser()->setFlash('notice', 'Collection added to featured list');
                    }
                } elseif ($action === 'remove' && $featuredId) {
                    \Illuminate\Database\Capsule\Manager::table('heritage_featured_collection')
                        ->where('id', $featuredId)
                        ->delete();
                    $this->getUser()->setFlash('notice', 'Collection removed from featured list');
                } elseif ($action === 'toggle' && $featuredId) {
                    $featured = \Illuminate\Database\Capsule\Manager::table('heritage_featured_collection')
                        ->where('id', $featuredId)
                        ->first();
                    if ($featured) {
                        \Illuminate\Database\Capsule\Manager::table('heritage_featured_collection')
                            ->where('id', $featuredId)
                            ->update(['is_enabled' => $featured->is_enabled ? 0 : 1]);
                        $this->getUser()->setFlash('notice', 'Collection ' . ($featured->is_enabled ? 'disabled' : 'enabled'));
                    }
                } elseif ($action === 'reorder') {
                    $order = $request->getParameter('order', []);
                    foreach ($order as $position => $id) {
                        \Illuminate\Database\Capsule\Manager::table('heritage_featured_collection')
                            ->where('id', $id)
                            ->update(['display_order' => $position * 10]);
                    }
                    $this->getUser()->setFlash('notice', 'Order updated');
                }
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Error: ' . $e->getMessage());
            }

            $this->redirect(['module' => 'heritage', 'action' => 'adminFeaturedCollections']);
        }

        // Get current featured collections
        $this->featured = \Illuminate\Database\Capsule\Manager::table('heritage_featured_collection as fc')
            ->orderBy('fc.display_order')
            ->orderBy('fc.id')
            ->get()
            ->map(function ($item) use ($culture) {
                // Get source details
                if ($item->source_type === 'iiif') {
                    $source = \Illuminate\Database\Capsule\Manager::table('iiif_collection')
                        ->where('id', $item->source_id)
                        ->select(['name', 'slug'])
                        ->first();
                    $item->source_name = $source ? $source->name : '[Deleted]';
                    $item->source_slug = $source ? $source->slug : null;
                } else {
                    $source = \Illuminate\Database\Capsule\Manager::table('information_object as io')
                        ->leftJoin('information_object_i18n as ioi', function ($join) use ($culture) {
                            $join->on('io.id', '=', 'ioi.id')
                                ->where('ioi.culture', '=', $culture);
                        })
                        ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
                        ->where('io.id', $item->source_id)
                        ->select(['ioi.title', 's.slug'])
                        ->first();
                    $item->source_name = $source ? ($source->title ?: '[Untitled]') : '[Deleted]';
                    $item->source_slug = $source ? $source->slug : null;
                }
                return $item;
            })
            ->toArray();

        // Get available IIIF collections
        $this->iiifCollections = \Illuminate\Database\Capsule\Manager::table('iiif_collection')
            ->where('is_public', 1)
            ->orderBy('name')
            ->select(['id', 'name', 'slug'])
            ->get()
            ->toArray();

        // Get available archival collections (top-level fonds)
        $this->archivalCollections = \Illuminate\Database\Capsule\Manager::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($join) use ($culture) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
            ->where('io.parent_id', 1)
            ->whereNotNull('ioi.title')
            ->where('ioi.title', '!=', '')
            ->orderBy('ioi.title')
            ->select(['io.id', 'ioi.title', 's.slug'])
            ->get()
            ->toArray();

        return sfView::SUCCESS;
    }

    /**
     * Admin hero slides management.
     */
    public function executeAdminHeroSlides(sfWebRequest $request)
    {
        if (!$this->context->user->isAdministrator() && !$this->context->user->isAuthenticated()) {
            $this->forward('admin', 'secure');
        }

        // Check for editor role as well
        if (!$this->context->user->isAdministrator()) {
            $user = $this->context->user;
            $groups = $user->getAclGroups();
            $isEditor = false;
            foreach ($groups as $group) {
                if (in_array($group->id, [QubitAclGroup::ADMINISTRATOR_ID, QubitAclGroup::EDITOR_ID])) {
                    $isEditor = true;
                    break;
                }
            }
            if (!$isEditor) {
                $this->forward('admin', 'secure');
            }
        }

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $institutionId = $request->getParameter('institution_id');
        $action = $request->getParameter('slide_action');
        $slideId = $request->getParameter('slide_id');

        $service = new \AtomFramework\Heritage\Discovery\DiscoveryService($this->context->user->getCulture());

        // Handle actions
        if ($request->isMethod('post')) {
            try {
                if ($action === 'create' || $action === 'update') {
                    $data = [
                        'institution_id' => $institutionId ? (int) $institutionId : null,
                        'title' => $request->getParameter('title'),
                        'subtitle' => $request->getParameter('subtitle'),
                        'description' => $request->getParameter('description'),
                        'image_alt' => $request->getParameter('image_alt'),
                        'overlay_type' => $request->getParameter('overlay_type', 'gradient'),
                        'overlay_color' => $request->getParameter('overlay_color', '#000000'),
                        'overlay_opacity' => (float) $request->getParameter('overlay_opacity', 0.5),
                        'text_position' => $request->getParameter('text_position', 'left'),
                        'ken_burns' => $request->getParameter('ken_burns') ? 1 : 0,
                        'cta_text' => $request->getParameter('cta_text'),
                        'cta_url' => $request->getParameter('cta_url'),
                        'cta_style' => $request->getParameter('cta_style', 'primary'),
                        'source_collection' => $request->getParameter('source_collection'),
                        'photographer_credit' => $request->getParameter('photographer_credit'),
                        'display_order' => (int) $request->getParameter('display_order', 100),
                        'display_duration' => (int) $request->getParameter('display_duration', 8),
                        'is_enabled' => $request->getParameter('is_enabled') ? 1 : 0,
                        'start_date' => $request->getParameter('start_date') ?: null,
                        'end_date' => $request->getParameter('end_date') ?: null,
                    ];

                    // Handle file upload
                    $uploadedFile = $request->getFiles('hero_image');
                    if ($uploadedFile && $uploadedFile['error'] === UPLOAD_ERR_OK) {
                        $imagePath = $this->handleHeroImageUpload($uploadedFile);
                        if ($imagePath) {
                            $data['image_path'] = $imagePath;
                        }
                    } elseif ($action === 'create') {
                        // Check for URL input
                        $imageUrl = $request->getParameter('image_url');
                        if ($imageUrl) {
                            $data['image_path'] = $imageUrl;
                        } else {
                            $this->getUser()->setFlash('error', 'Please upload an image or provide an image URL');
                            $this->redirect(['module' => 'heritage', 'action' => 'adminHeroSlides']);
                        }
                    }

                    if ($action === 'create') {
                        $service->saveHeroSlide($data);
                        $this->getUser()->setFlash('notice', 'Hero slide created successfully');
                    } else {
                        // Keep existing image if no new one uploaded
                        if (!isset($data['image_path'])) {
                            unset($data['image_path']);
                        }
                        $service->updateHeroSlide((int) $slideId, $data);
                        $this->getUser()->setFlash('notice', 'Hero slide updated successfully');
                    }
                } elseif ($action === 'delete' && $slideId) {
                    $service->deleteHeroSlide((int) $slideId);
                    $this->getUser()->setFlash('notice', 'Hero slide deleted');
                } elseif ($action === 'toggle' && $slideId) {
                    $slide = \Illuminate\Database\Capsule\Manager::table('heritage_hero_slide')
                        ->where('id', $slideId)
                        ->first();
                    if ($slide) {
                        $newStatus = $slide->is_enabled ? 0 : 1;
                        $service->updateHeroSlide((int) $slideId, ['is_enabled' => $newStatus]);
                        $this->getUser()->setFlash('notice', 'Slide ' . ($newStatus ? 'enabled' : 'disabled'));
                    }
                }
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Error: ' . $e->getMessage());
            }

            $this->redirect(['module' => 'heritage', 'action' => 'adminHeroSlides']);
        }

        // Get hero slides
        $this->slides = \Illuminate\Database\Capsule\Manager::table('heritage_hero_slide')
            ->where(function ($q) use ($institutionId) {
                $q->whereNull('institution_id');
                if ($institutionId) {
                    $q->orWhere('institution_id', $institutionId);
                }
            })
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->toArray();

        // Get edit slide if requested
        $editId = $request->getParameter('edit');
        if ($editId) {
            $this->editSlide = \Illuminate\Database\Capsule\Manager::table('heritage_hero_slide')
                ->where('id', $editId)
                ->first();
        }

        return sfView::SUCCESS;
    }

    /**
     * Handle hero image upload.
     */
    protected function handleHeroImageUpload(array $file): ?string
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $maxSize = 10 * 1024 * 1024; // 10MB

        if (!in_array($file['type'], $allowedTypes)) {
            throw new \Exception('Invalid file type. Allowed: JPG, PNG, WebP, GIF');
        }

        if ($file['size'] > $maxSize) {
            throw new \Exception('File too large. Maximum size: 10MB');
        }

        // Create upload directory
        $uploadDir = sfConfig::get('sf_upload_dir') . '/heritage/hero';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'hero_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $targetPath = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new \Exception('Failed to save uploaded file');
        }

        // Return web-accessible path
        return '/uploads/heritage/hero/' . $filename;
    }

    // ========================================================================
    // SESSION 6: ACCESS MEDIATION
    // ========================================================================

    /**
     * Request access form.
     */
    public function executeRequestAccess(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $slug = $request->getParameter('slug');
        $culture = $this->context->user->getCulture();

        // Get object
        $object = \Illuminate\Database\Capsule\Manager::table('information_object')
            ->leftJoin('information_object_i18n', function ($join) use ($culture) {
                $join->on('information_object.id', '=', 'information_object_i18n.id')
                    ->where('information_object_i18n.culture', '=', $culture);
            })
            ->where('information_object.slug', $slug)
            ->select(['information_object.*', 'information_object_i18n.title'])
            ->first();

        if (!$object) {
            $this->forward404();
        }

        $this->resource = $object;

        $controller = new \AtomFramework\Heritage\Controllers\Api\AccessController();
        $purposeResponse = $controller->getPurposes();
        $this->purposes = $purposeResponse['data'] ?? [];

        // Handle POST
        if ($request->isMethod('post') && $this->context->user->isAuthenticated()) {
            $data = [
                'user_id' => $this->context->user->getAttribute('user_id'),
                'object_id' => $object->id,
                'purpose_id' => $request->getParameter('purpose_id'),
                'justification' => $request->getParameter('justification'),
                'research_description' => $request->getParameter('research_description'),
                'institution_affiliation' => $request->getParameter('institution_affiliation'),
            ];

            $response = $controller->createAccessRequest($data);

            if ($response['success']) {
                $this->getUser()->setFlash('notice', 'Access request submitted successfully');
                $this->redirect(['module' => 'heritage', 'action' => 'myAccessRequests']);
            } else {
                $this->getUser()->setFlash('error', $response['error'] ?? 'Failed to submit request');
            }
        }

        return sfView::SUCCESS;
    }

    /**
     * User's access requests.
     */
    public function executeMyAccessRequests(sfWebRequest $request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $userId = $this->context->user->getAttribute('user_id');
        $controller = new \AtomFramework\Heritage\Controllers\Api\AccessController();

        $params = [
            'page' => (int) $request->getParameter('page', 1),
            'status' => $request->getParameter('status'),
        ];

        $response = $controller->getUserRequests($userId, $params);
        $this->requestData = $response['data'] ?? [];

        return sfView::SUCCESS;
    }

    /**
     * Admin access requests.
     */
    public function executeAdminAccessRequests(sfWebRequest $request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $controller = new \AtomFramework\Heritage\Controllers\Api\AccessController();

        // Handle approve/deny
        if ($request->isMethod('post')) {
            $requestId = (int) $request->getParameter('request_id');
            $action = $request->getParameter('decision');
            $userId = $this->context->user->getAttribute('user_id');

            if ($action === 'approve') {
                $controller->approveRequest($requestId, $userId, [
                    'notes' => $request->getParameter('notes'),
                    'valid_until' => $request->getParameter('valid_until'),
                ]);
                $this->getUser()->setFlash('notice', 'Request approved');
            } elseif ($action === 'deny') {
                $controller->denyRequest($requestId, $userId, $request->getParameter('notes'));
                $this->getUser()->setFlash('notice', 'Request denied');
            }

            $this->redirect(['module' => 'heritage', 'action' => 'adminAccessRequests']);
        }

        $params = [
            'page' => (int) $request->getParameter('page', 1),
        ];

        $response = $controller->getPendingRequests($params);
        $this->requestData = $response['data'] ?? [];

        $statsResponse = $controller->getRequestStats();
        $this->stats = $statsResponse['data'] ?? [];

        return sfView::SUCCESS;
    }

    /**
     * Admin embargoes.
     */
    public function executeAdminEmbargoes(sfWebRequest $request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $controller = new \AtomFramework\Heritage\Controllers\Api\AccessController();

        // Handle remove
        if ($request->isMethod('post') && $request->getParameter('action') === 'remove') {
            $id = (int) $request->getParameter('embargo_id');
            $controller->removeEmbargo($id);
            $this->getUser()->setFlash('notice', 'Embargo removed');
            $this->redirect(['module' => 'heritage', 'action' => 'adminEmbargoes']);
        }

        $params = [
            'page' => (int) $request->getParameter('page', 1),
        ];

        $response = $controller->getEmbargoes($params);
        $this->embargoData = $response['data'] ?? [];

        $expiringResponse = $controller->getExpiringEmbargoes(30);
        $this->expiringEmbargoes = $expiringResponse['data'] ?? [];

        $statsResponse = $controller->getEmbargoStats();
        $this->stats = $statsResponse['data'] ?? [];

        return sfView::SUCCESS;
    }

    /**
     * Admin POPIA dashboard.
     */
    public function executeAdminPopia(sfWebRequest $request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $controller = new \AtomFramework\Heritage\Controllers\Api\AccessController();

        // Handle resolve
        if ($request->isMethod('post') && $request->getParameter('action') === 'resolve') {
            $id = (int) $request->getParameter('flag_id');
            $userId = $this->context->user->getAttribute('user_id');
            $notes = $request->getParameter('resolution_notes');
            $controller->resolvePOPIAFlag($id, $userId, $notes);
            $this->getUser()->setFlash('notice', 'Flag resolved');
            $this->redirect(['module' => 'heritage', 'action' => 'adminPopia']);
        }

        $params = [
            'page' => (int) $request->getParameter('page', 1),
            'severity' => $request->getParameter('severity'),
            'flag_type' => $request->getParameter('flag_type'),
        ];

        $response = $controller->getPOPIAFlags($params);
        $this->flagData = $response['data'] ?? [];

        $statsResponse = $controller->getPOPIAStats();
        $this->stats = $statsResponse['data'] ?? [];

        return sfView::SUCCESS;
    }

    // ========================================================================
    // SESSION 7: CUSTODIAN INTERFACE
    // ========================================================================

    /**
     * Custodian dashboard.
     */
    public function executeCustodianDashboard(sfWebRequest $request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $culture = $this->context->user->getCulture();
        $userId = $this->context->user->getAttribute('user_id');

        $controller = new \AtomFramework\Heritage\Controllers\Api\CustodianController($culture);
        $response = $controller->getDashboard($userId);
        $this->dashboardData = $response['data'] ?? [];

        return sfView::SUCCESS;
    }

    /**
     * Custodian item editor.
     */
    public function executeCustodianItem(sfWebRequest $request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $slug = $request->getParameter('slug');
        $culture = $this->context->user->getCulture();

        // Get object by slug
        $object = \Illuminate\Database\Capsule\Manager::table('information_object')
            ->where('slug', $slug)
            ->first();

        if (!$object) {
            $this->forward404();
        }

        $controller = new \AtomFramework\Heritage\Controllers\Api\CustodianController($culture);

        // Handle POST
        if ($request->isMethod('post')) {
            $data = $request->getParameterHolder()->getAll();
            unset($data['module'], $data['action'], $data['slug']);

            $userId = $this->context->user->getAttribute('user_id');
            $response = $controller->updateItem($object->id, $data, $userId);

            if ($response['success']) {
                $this->getUser()->setFlash('notice', 'Item updated successfully');
            } else {
                $this->getUser()->setFlash('error', $response['error'] ?? 'Failed to update item');
            }

            $this->redirect(['module' => 'heritage', 'action' => 'custodianItem', 'slug' => $slug]);
        }

        $response = $controller->getItem($object->id);
        $this->item = $response['data'] ?? null;
        $this->slug = $slug;

        // Get history
        $historyResponse = $controller->getItemHistory($object->id, ['limit' => 20]);
        $this->history = $historyResponse['data']['logs'] ?? [];

        return sfView::SUCCESS;
    }

    /**
     * Custodian batch operations.
     */
    public function executeCustodianBatch(sfWebRequest $request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $culture = $this->context->user->getCulture();
        $controller = new \AtomFramework\Heritage\Controllers\Api\CustodianController($culture);

        $params = [
            'page' => (int) $request->getParameter('page', 1),
            'status' => $request->getParameter('status'),
        ];

        $response = $controller->getBatchJobs($params);
        $this->jobData = $response['data'] ?? [];

        return sfView::SUCCESS;
    }

    /**
     * Custodian audit history.
     */
    public function executeCustodianHistory(sfWebRequest $request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $culture = $this->context->user->getCulture();
        $controller = new \AtomFramework\Heritage\Controllers\Api\CustodianController($culture);

        $params = [
            'page' => (int) $request->getParameter('page', 1),
            'user_id' => $request->getParameter('user_id'),
            'category' => $request->getParameter('category'),
            'date_from' => $request->getParameter('date_from'),
            'date_to' => $request->getParameter('date_to'),
            'days' => (int) $request->getParameter('days', 30),
        ];

        $response = $controller->searchAuditLogs($params);
        $this->logData = $response['data'] ?? [];

        return sfView::SUCCESS;
    }

    // ========================================================================
    // SESSION 9: ANALYTICS & LEARNING
    // ========================================================================

    /**
     * Analytics dashboard.
     */
    public function executeAnalyticsDashboard(sfWebRequest $request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $institutionId = $request->getParameter('institution_id');
        $days = (int) $request->getParameter('days', 30);

        $controller = new \AtomFramework\Heritage\Controllers\Api\AnalyticsController();
        $response = $controller->getDashboard($institutionId ? (int) $institutionId : null, $days);
        $this->dashboardData = $response['data'] ?? [];
        $this->days = $days;

        return sfView::SUCCESS;
    }

    /**
     * Analytics search insights.
     */
    public function executeAnalyticsSearch(sfWebRequest $request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $institutionId = $request->getParameter('institution_id');
        $days = (int) $request->getParameter('days', 30);

        $controller = new \AtomFramework\Heritage\Controllers\Api\AnalyticsController();

        $popularResponse = $controller->getPopularQueries($institutionId ? (int) $institutionId : null, $days);
        $this->popularQueries = $popularResponse['data'] ?? [];

        $zeroResponse = $controller->getZeroResultQueries($institutionId ? (int) $institutionId : null, $days);
        $this->zeroResultQueries = $zeroResponse['data'] ?? [];

        $trendingResponse = $controller->getTrendingQueries($institutionId ? (int) $institutionId : null);
        $this->trendingQueries = $trendingResponse['data'] ?? [];

        $conversionResponse = $controller->getConversionAnalysis($institutionId ? (int) $institutionId : null, $days);
        $this->conversion = $conversionResponse['data'] ?? [];

        $patternsResponse = $controller->getSearchPatterns($institutionId ? (int) $institutionId : null, $days);
        $this->patterns = $patternsResponse['data'] ?? [];

        $this->days = $days;

        return sfView::SUCCESS;
    }

    /**
     * Analytics content insights.
     */
    public function executeAnalyticsContent(sfWebRequest $request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $institutionId = $request->getParameter('institution_id');
        $days = (int) $request->getParameter('days', 30);

        $controller = new \AtomFramework\Heritage\Controllers\Api\AnalyticsController();
        $response = $controller->getDashboard($institutionId ? (int) $institutionId : null, $days);

        $this->contentData = $response['data']['content'] ?? [];
        $this->days = $days;

        return sfView::SUCCESS;
    }

    /**
     * Analytics alerts.
     */
    public function executeAnalyticsAlerts(sfWebRequest $request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $institutionId = $request->getParameter('institution_id');
        $controller = new \AtomFramework\Heritage\Controllers\Api\AnalyticsController();

        // Handle dismiss
        if ($request->isMethod('post') && $request->getParameter('action') === 'dismiss') {
            $alertId = (int) $request->getParameter('alert_id');
            $userId = $this->context->user->getAttribute('user_id');
            $controller->dismissAlert($alertId, $userId);
            $this->getUser()->setFlash('notice', 'Alert dismissed');
            $this->redirect(['module' => 'heritage', 'action' => 'analyticsAlerts']);
        }

        // Generate alerts
        if ($request->getParameter('generate') === '1') {
            $controller->generateAlerts($institutionId ? (int) $institutionId : null);
            $this->getUser()->setFlash('notice', 'System alerts generated');
            $this->redirect(['module' => 'heritage', 'action' => 'analyticsAlerts']);
        }

        $params = [
            'page' => (int) $request->getParameter('page', 1),
            'category' => $request->getParameter('category'),
            'severity' => $request->getParameter('severity'),
        ];

        $response = $controller->getAlerts($institutionId ? (int) $institutionId : null, $params);
        $this->alertData = $response['data'] ?? [];

        $countsResponse = $controller->getAlertCounts($institutionId ? (int) $institutionId : null);
        $this->alertCounts = $countsResponse['data'] ?? [];

        return sfView::SUCCESS;
    }

    // ========================================================================
    // CONTRIBUTIONS MODULE
    // ========================================================================

    /**
     * Contributor login.
     */
    public function executeContributorLogin(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        // Check if already logged in
        if ($this->getUser()->getAttribute('contributor_id')) {
            $this->redirect(['module' => 'heritage', 'action' => 'myContributions']);
        }

        $this->error = null;

        if ($request->isMethod('post')) {
            $email = $request->getParameter('email');
            $password = $request->getParameter('password');

            $service = new \AtomFramework\Heritage\Contributions\ContributorService();
            $response = $service->login($email, $password);

            if ($response['success']) {
                // Store in session
                $this->getUser()->setAttribute('contributor_id', $response['data']['contributor']['id']);
                $this->getUser()->setAttribute('contributor_token', $response['data']['token']);
                $this->getUser()->setAttribute('contributor_name', $response['data']['contributor']['display_name']);

                // Redirect to intended page or contributions
                $returnUrl = $this->getUser()->getAttribute('contributor_return_url');
                $this->getUser()->setAttribute('contributor_return_url', null);

                if ($returnUrl) {
                    $this->redirect($returnUrl);
                }

                $this->redirect(['module' => 'heritage', 'action' => 'myContributions']);
            }

            $this->error = $response['error'];
        }

        return sfView::SUCCESS;
    }

    /**
     * Contributor registration.
     */
    public function executeContributorRegister(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $this->error = null;
        $this->success = false;

        if ($request->isMethod('post')) {
            $email = $request->getParameter('email');
            $displayName = $request->getParameter('display_name');
            $password = $request->getParameter('password');
            $confirmPassword = $request->getParameter('confirm_password');
            $agreeTerms = $request->getParameter('agree_terms');

            // Validate
            if (!$agreeTerms) {
                $this->error = 'You must agree to the terms and conditions';
            } elseif ($password !== $confirmPassword) {
                $this->error = 'Passwords do not match';
            } else {
                $service = new \AtomFramework\Heritage\Contributions\ContributorService();
                $response = $service->register($email, $displayName, $password);

                if ($response['success']) {
                    $this->success = true;

                    // Send verification email
                    $this->sendVerificationEmail(
                        $response['data']['email'],
                        $response['data']['display_name'],
                        $response['data']['verify_token']
                    );
                } else {
                    $this->error = $response['error'];
                }
            }
        }

        return sfView::SUCCESS;
    }

    /**
     * Contributor logout.
     */
    public function executeContributorLogout(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $token = $this->getUser()->getAttribute('contributor_token');
        if ($token) {
            $service = new \AtomFramework\Heritage\Contributions\ContributorService();
            $service->logout($token);
        }

        $this->getUser()->setAttribute('contributor_id', null);
        $this->getUser()->setAttribute('contributor_token', null);
        $this->getUser()->setAttribute('contributor_name', null);

        $this->getUser()->setFlash('notice', 'You have been logged out');
        $this->redirect(['module' => 'heritage', 'action' => 'landing']);
    }

    /**
     * Email verification.
     */
    public function executeContributorVerify(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $token = $request->getParameter('token');
        $service = new \AtomFramework\Heritage\Contributions\ContributorService();
        $response = $service->verifyEmail($token);

        $this->success = $response['success'];
        $this->error = $response['error'] ?? null;

        return sfView::SUCCESS;
    }

    /**
     * Contribution form for an item.
     */
    public function executeContribute(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $slug = $request->getParameter('slug');
        $culture = $this->context->user->getCulture();

        // Get the item
        $item = \Illuminate\Database\Capsule\Manager::table('information_object as io')
            ->join('slug as s', 'io.id', '=', 's.object_id')
            ->leftJoin('information_object_i18n as ioi', function ($join) use ($culture) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('digital_object as do', 'io.id', '=', 'do.object_id')
            ->where('s.slug', $slug)
            ->select([
                'io.id',
                's.slug',
                'ioi.title',
                'ioi.scope_and_content',
                'do.path as thumbnail_path',
                'do.name as thumbnail_name',
                'do.mime_type',
            ])
            ->first();

        if (!$item) {
            $this->forward404();
        }

        $this->item = $item;
        $this->slug = $slug;

        // Build thumbnail URL
        $this->thumbnail = null;
        if ($item->thumbnail_path && $item->thumbnail_name) {
            $path = rtrim($item->thumbnail_path, '/');
            $basename = pathinfo($item->thumbnail_name, PATHINFO_FILENAME);
            $this->thumbnail = $path . '/' . $basename . '_142.jpg';
        }

        // Check if contributor is logged in
        $this->contributorId = $this->getUser()->getAttribute('contributor_id');
        $this->contributorName = $this->getUser()->getAttribute('contributor_name');

        // Get contribution opportunities
        $service = new \AtomFramework\Heritage\Contributions\ContributionService($culture);
        $opportunitiesResponse = $service->getOpportunities($item->id);
        $this->opportunities = $opportunitiesResponse['data']['opportunities'] ?? [];

        // Get existing approved contributions for this item
        $existingResponse = $service->getByItem($item->id, 'approved', 1, 10);
        $this->existingContributions = $existingResponse['data']['contributions'] ?? [];

        // Selected type
        $this->selectedType = $request->getParameter('type', 'transcription');

        return sfView::SUCCESS;
    }

    /**
     * User's contribution history.
     */
    public function executeMyContributions(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $contributorId = $this->getUser()->getAttribute('contributor_id');
        if (!$contributorId) {
            $this->getUser()->setAttribute('contributor_return_url', $request->getUri());
            $this->redirect(['module' => 'heritage', 'action' => 'contributorLogin']);
        }

        $culture = $this->context->user->getCulture();
        $service = new \AtomFramework\Heritage\Contributions\ContributionService($culture);

        $params = [
            'page' => (int) $request->getParameter('page', 1),
            'status' => $request->getParameter('status'),
        ];

        $response = $service->getByContributor($contributorId, $params['status'], $params['page']);
        $this->contributionData = $response['data'] ?? [];

        // Get contributor profile
        $contributorService = new \AtomFramework\Heritage\Contributions\ContributorService();
        $profileResponse = $contributorService->getProfile($contributorId);
        $this->profile = $profileResponse['data'] ?? [];

        return sfView::SUCCESS;
    }

    /**
     * Public contributor profile.
     */
    public function executeContributorProfile(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $contributorId = (int) $request->getParameter('id');

        $service = new \AtomFramework\Heritage\Contributions\ContributorService();
        $response = $service->getProfile($contributorId);

        if (!$response['success']) {
            $this->forward404();
        }

        $this->profile = $response['data'];

        return sfView::SUCCESS;
    }

    /**
     * Leaderboard.
     */
    public function executeLeaderboard(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $period = $request->getParameter('period'); // week, month, or null for all-time

        $service = new \AtomFramework\Heritage\Contributions\ContributorService();
        $response = $service->getLeaderboard(50, $period);

        $this->leaderboard = $response['data'] ?? [];
        $this->period = $period;

        // Get contribution stats
        $contributionService = new \AtomFramework\Heritage\Contributions\ContributionService();
        $statsResponse = $contributionService->getStats();
        $this->stats = $statsResponse['data'] ?? [];

        return sfView::SUCCESS;
    }

    /**
     * Review queue (Custodian/Admin).
     */
    public function executeReviewQueue(sfWebRequest $request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $culture = $this->context->user->getCulture();
        $service = new \AtomFramework\Heritage\Contributions\ContributionService($culture);

        $params = [
            'page' => (int) $request->getParameter('page', 1),
            'type' => $request->getParameter('type'),
        ];

        $response = $service->getPendingReview($params['page'], $params['type']);
        $this->queueData = $response['data'] ?? [];

        // Get types for filter
        $typesResponse = $service->getTypes();
        $this->types = $typesResponse['data'] ?? [];

        return sfView::SUCCESS;
    }

    /**
     * Single contribution review.
     */
    public function executeReviewContribution(sfWebRequest $request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $contributionId = (int) $request->getParameter('id');
        $culture = $this->context->user->getCulture();

        $service = new \AtomFramework\Heritage\Contributions\ContributionService($culture);

        // Handle approve/reject
        if ($request->isMethod('post')) {
            $action = $request->getParameter('decision');
            $notes = $request->getParameter('notes');
            $reviewerId = $this->context->user->getAttribute('user_id');

            if ($action === 'approve') {
                $service->approve($contributionId, $reviewerId, $notes);
                $this->getUser()->setFlash('notice', 'Contribution approved');
            } elseif ($action === 'reject') {
                $service->reject($contributionId, $reviewerId, $notes);
                $this->getUser()->setFlash('notice', 'Contribution rejected');
            }

            $this->redirect(['module' => 'heritage', 'action' => 'reviewQueue']);
        }

        $response = $service->getForReview($contributionId);

        if (!$response['success']) {
            $this->forward404();
        }

        $this->contribution = $response['data'];

        return sfView::SUCCESS;
    }

    /**
     * API: Submit contribution.
     */
    public function executeApiSubmitContribution(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        if (!$request->isMethod('post')) {
            return $this->renderJson(['success' => false, 'error' => 'POST method required']);
        }

        $contributorId = $this->getUser()->getAttribute('contributor_id');
        if (!$contributorId) {
            return $this->renderJson(['success' => false, 'error' => 'Not authenticated']);
        }

        $content = $request->getContent();
        $data = json_decode($content, true) ?: [];

        if (empty($data['item_id']) || empty($data['type_code']) || empty($data['content'])) {
            return $this->renderJson(['success' => false, 'error' => 'Missing required fields']);
        }

        $culture = $this->context->user->getCulture();
        $service = new \AtomFramework\Heritage\Contributions\ContributionService($culture);
        $response = $service->create(
            $contributorId,
            (int) $data['item_id'],
            $data['type_code'],
            $data['content']
        );

        return $this->renderJson($response);
    }

    /**
     * API: Get contribution status.
     */
    public function executeApiContributionStatus(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $contributionId = (int) $request->getParameter('id');
        $culture = $this->context->user->getCulture();

        $service = new \AtomFramework\Heritage\Contributions\ContributionService($culture);
        $response = $service->getForReview($contributionId);

        if (!$response['success']) {
            return $this->renderJson(['success' => false, 'error' => 'Contribution not found']);
        }

        return $this->renderJson([
            'success' => true,
            'data' => [
                'id' => $response['data']['id'],
                'status' => $response['data']['status'],
                'created_at' => $response['data']['created_at'],
                'reviewed_at' => $response['data']['reviewed_at'],
                'review_notes' => $response['data']['review_notes'],
            ],
        ]);
    }

    /**
     * API: Suggest tags.
     */
    public function executeApiSuggestTags(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $query = $request->getParameter('q', '');
        $limit = min(20, max(1, (int) $request->getParameter('limit', 10)));
        $culture = $this->context->user->getCulture();

        if (strlen($query) < 2) {
            return $this->renderJson(['success' => true, 'data' => []]);
        }

        // Search existing subject terms
        $terms = \Illuminate\Database\Capsule\Manager::table('term_i18n as ti')
            ->join('term as t', 'ti.id', '=', 't.id')
            ->where('t.taxonomy_id', 35) // Subjects taxonomy
            ->where('ti.culture', $culture)
            ->where('ti.name', 'LIKE', '%' . $query . '%')
            ->orderBy('ti.name')
            ->limit($limit)
            ->select(['ti.name'])
            ->pluck('name')
            ->toArray();

        return $this->renderJson(['success' => true, 'data' => $terms]);
    }

    /**
     * Helper: Get current contributor.
     */
    protected function getContributor(): ?object
    {
        $token = $this->getUser()->getAttribute('contributor_token');
        if (!$token) {
            return null;
        }

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $service = new \AtomFramework\Heritage\Contributions\ContributorService();
        return $service->validateSession($token);
    }

    // ========================================================================
    // ENHANCED LANDING PAGE: EXPLORE & TIMELINE
    // ========================================================================

    /**
     * Explore categories landing.
     */
    public function executeExplore(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $institutionId = $request->getParameter('institution_id');
        $culture = $this->context->user->getCulture();
        $categoryCode = $request->getParameter('category');

        $service = new \AtomFramework\Heritage\Discovery\DiscoveryService($culture);

        // Get all categories
        $this->categories = $service->getExploreCategories($institutionId ? (int) $institutionId : null);

        // If specific category, get items
        if ($categoryCode) {
            $this->currentCategory = $service->getExploreCategory($categoryCode, $institutionId ? (int) $institutionId : null);
            if ($this->currentCategory) {
                $page = max(1, (int) $request->getParameter('page', 1));
                $limit = 24;
                $offset = ($page - 1) * $limit;

                $result = $service->getExploreCategoryItems($categoryCode, $institutionId ? (int) $institutionId : null, $limit, $offset);
                $this->items = $result['items'];
                $this->totalItems = $result['total'];
                $this->page = $page;
                $this->totalPages = ceil($result['total'] / $limit);
            }
        }

        return sfView::SUCCESS;
    }

    /**
     * Timeline navigation page.
     */
    public function executeTimeline(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $institutionId = $request->getParameter('institution_id');
        $culture = $this->context->user->getCulture();
        $periodId = $request->getParameter('period_id');

        $service = new \AtomFramework\Heritage\Discovery\DiscoveryService($culture);

        // Get all timeline periods
        $this->periods = $service->getTimelinePeriods($institutionId ? (int) $institutionId : null);

        // If specific period, get items
        if ($periodId) {
            $page = max(1, (int) $request->getParameter('page', 1));
            $limit = 24;
            $offset = ($page - 1) * $limit;

            $result = $service->getTimelinePeriodItems((int) $periodId, $institutionId ? (int) $institutionId : null, $limit, $offset);
            $this->currentPeriod = $result['period'];
            $this->items = $result['items'];
            $this->totalItems = $result['total'];
            $this->page = $page;
            $this->totalPages = ceil($result['total'] / $limit);
        }

        return sfView::SUCCESS;
    }

    /**
     * Creators/People browse page.
     */
    public function executeCreators(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $institutionId = $request->getParameter('institution_id');
        $culture = $this->context->user->getCulture();
        $searchQuery = trim($request->getParameter('q', ''));

        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = 24;
        $offset = ($page - 1) * $limit;

        // If search query provided, search creators directly
        if ($searchQuery) {
            $result = $this->searchCreators($searchQuery, $institutionId ? (int) $institutionId : null, $limit, $offset);
        } else {
            $service = new \AtomFramework\Heritage\Discovery\DiscoveryService($culture);
            $result = $service->getExploreCategoryItems('people', $institutionId ? (int) $institutionId : null, $limit, $offset);
        }

        $this->creators = $result['items'];
        $this->totalItems = $result['total'];
        $this->page = $page;
        $this->totalPages = ceil($result['total'] / $limit);
        $this->searchQuery = $searchQuery;

        return sfView::SUCCESS;
    }

    /**
     * Creators autocomplete for search.
     */
    public function executeCreatorsAutocomplete(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $query = trim($request->getParameter('q', ''));
        $results = [];

        if (strlen($query) >= 2) {
            $creators = \Illuminate\Database\Capsule\Manager::table('actor as a')
                ->join('actor_i18n as ai', function ($join) {
                    $join->on('a.id', '=', 'ai.id')
                        ->where('ai.culture', '=', 'en');
                })
                ->join('slug as s', 'a.id', '=', 's.object_id')
                ->leftJoin('relation as r', function ($join) {
                    $join->on('a.id', '=', 'r.object_id')
                        ->where('r.type_id', '=', QubitTerm::CREATION_ID);
                })
                ->where('ai.authorized_form_of_name', 'LIKE', "%{$query}%")
                ->where('a.id', '!=', QubitActor::ROOT_ID)
                ->select(
                    'a.id',
                    'ai.authorized_form_of_name as name',
                    's.slug',
                    \Illuminate\Database\Capsule\Manager::raw('COUNT(DISTINCT r.id) as item_count')
                )
                ->groupBy('a.id', 'ai.authorized_form_of_name', 's.slug')
                ->orderByRaw('COUNT(DISTINCT r.id) DESC')
                ->limit(15)
                ->get();

            foreach ($creators as $creator) {
                $results[] = [
                    'id' => $creator->id,
                    'name' => $creator->name,
                    'slug' => $creator->slug,
                    'count' => (int) $creator->item_count,
                ];
            }
        }

        $this->getResponse()->setContentType('application/json');

        return $this->renderText(json_encode(['results' => $results]));
    }

    /**
     * Search creators by name.
     */
    protected function searchCreators(string $query, ?int $institutionId, int $limit, int $offset): array
    {
        $baseQuery = \Illuminate\Database\Capsule\Manager::table('actor as a')
            ->join('actor_i18n as ai', function ($join) {
                $join->on('a.id', '=', 'ai.id')
                    ->where('ai.culture', '=', 'en');
            })
            ->join('slug as s', 'a.id', '=', 's.object_id')
            ->leftJoin('relation as r', function ($join) {
                $join->on('a.id', '=', 'r.object_id')
                    ->where('r.type_id', '=', QubitTerm::CREATION_ID);
            })
            ->where('ai.authorized_form_of_name', 'LIKE', "%{$query}%")
            ->where('a.id', '!=', QubitActor::ROOT_ID);

        // Get total count
        $total = (clone $baseQuery)->distinct()->count('a.id');

        // Get paginated results
        $creators = $baseQuery
            ->select(
                'a.id',
                'ai.authorized_form_of_name as name',
                's.slug',
                \Illuminate\Database\Capsule\Manager::raw('COUNT(DISTINCT r.id) as item_count')
            )
            ->groupBy('a.id', 'ai.authorized_form_of_name', 's.slug')
            ->orderByRaw('COUNT(DISTINCT r.id) DESC')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $items = [];
        foreach ($creators as $creator) {
            $items[] = [
                'id' => $creator->id,
                'name' => $creator->name,
                'slug' => $creator->slug,
                'count' => (int) $creator->item_count,
            ];
        }

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Featured collections page.
     */
    public function executeCollections(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $institutionId = $request->getParameter('institution_id');
        $culture = $this->context->user->getCulture();
        $collectionId = $request->getParameter('id');

        $service = new \AtomFramework\Heritage\Discovery\DiscoveryService($culture);

        if ($collectionId) {
            // Single collection view
            $this->collection = $service->getFeaturedCollection((int) $collectionId);
            if (!$this->collection) {
                $this->forward404();
            }
            $this->setTemplate('collectionDetail');
        } else {
            // Collection listing
            $this->collections = $service->getFeaturedCollections($institutionId ? (int) $institutionId : null, 20);
        }

        return sfView::SUCCESS;
    }

    /**
     * Trending/popular items page.
     */
    public function executeTrending(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $institutionId = $request->getParameter('institution_id');
        $culture = $this->context->user->getCulture();

        $service = new \AtomFramework\Heritage\Discovery\DiscoveryService($culture);

        $result = $service->getExploreCategoryItems('trending', $institutionId ? (int) $institutionId : null, 50);
        $this->items = $result['items'];

        return sfView::SUCCESS;
    }

    // ========================================================================
    // ENHANCED LANDING PAGE: API ENDPOINTS
    // ========================================================================

    /**
     * API: Get hero slides.
     */
    public function executeApiHeroSlides(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $institutionId = $request->getParameter('institution_id');
        $culture = $this->context->user->getCulture();

        $service = new \AtomFramework\Heritage\Discovery\DiscoveryService($culture);
        $slides = $service->getHeroSlides($institutionId ? (int) $institutionId : null);

        return $this->renderJson(['success' => true, 'data' => $slides]);
    }

    /**
     * API: Get featured collections.
     */
    public function executeApiFeaturedCollections(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $institutionId = $request->getParameter('institution_id');
        $limit = min(20, max(1, (int) $request->getParameter('limit', 6)));
        $culture = $this->context->user->getCulture();

        $service = new \AtomFramework\Heritage\Discovery\DiscoveryService($culture);
        $collections = $service->getFeaturedCollections($institutionId ? (int) $institutionId : null, $limit);

        return $this->renderJson(['success' => true, 'data' => $collections]);
    }

    /**
     * API: Get explore categories.
     */
    public function executeApiExploreCategories(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $institutionId = $request->getParameter('institution_id');
        $culture = $this->context->user->getCulture();

        $service = new \AtomFramework\Heritage\Discovery\DiscoveryService($culture);
        $categories = $service->getExploreCategories($institutionId ? (int) $institutionId : null);

        return $this->renderJson(['success' => true, 'data' => $categories]);
    }

    /**
     * API: Get explore category items.
     */
    public function executeApiExploreCategoryItems(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $institutionId = $request->getParameter('institution_id');
        $categoryCode = $request->getParameter('category');
        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = min(100, max(1, (int) $request->getParameter('limit', 24)));
        $culture = $this->context->user->getCulture();

        if (!$categoryCode) {
            return $this->renderJson(['success' => false, 'error' => 'Category code required']);
        }

        $service = new \AtomFramework\Heritage\Discovery\DiscoveryService($culture);
        $offset = ($page - 1) * $limit;
        $result = $service->getExploreCategoryItems($categoryCode, $institutionId ? (int) $institutionId : null, $limit, $offset);

        return $this->renderJson([
            'success' => true,
            'data' => $result['items'],
            'meta' => [
                'total' => $result['total'],
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($result['total'] / $limit),
            ],
        ]);
    }

    /**
     * API: Get timeline periods.
     */
    public function executeApiTimelinePeriods(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $institutionId = $request->getParameter('institution_id');
        $culture = $this->context->user->getCulture();

        $service = new \AtomFramework\Heritage\Discovery\DiscoveryService($culture);
        $periods = $service->getTimelinePeriods($institutionId ? (int) $institutionId : null);

        return $this->renderJson(['success' => true, 'data' => $periods]);
    }

    /**
     * API: Get timeline period items.
     */
    public function executeApiTimelinePeriodItems(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $institutionId = $request->getParameter('institution_id');
        $periodId = $request->getParameter('period_id');
        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = min(100, max(1, (int) $request->getParameter('limit', 24)));
        $culture = $this->context->user->getCulture();

        if (!$periodId) {
            return $this->renderJson(['success' => false, 'error' => 'Period ID required']);
        }

        $service = new \AtomFramework\Heritage\Discovery\DiscoveryService($culture);
        $offset = ($page - 1) * $limit;
        $result = $service->getTimelinePeriodItems((int) $periodId, $institutionId ? (int) $institutionId : null, $limit, $offset);

        return $this->renderJson([
            'success' => true,
            'data' => $result['items'],
            'period' => $result['period'],
            'meta' => [
                'total' => $result['total'],
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($result['total'] / $limit),
            ],
        ]);
    }

    /**
     * Send verification email to new contributor.
     *
     * @param string $email       Recipient email
     * @param string $displayName Recipient name
     * @param string $token       Verification token
     */
    protected function sendVerificationEmail(string $email, string $displayName, string $token): bool
    {
        $siteName = sfConfig::get('app_siteTitle', 'Heritage Portal');
        $baseUrl = sfConfig::get('app_siteBaseUrl', $this->getRequest()->getUriPrefix());
        $verifyUrl = $baseUrl . url_for([
            'module' => 'heritage',
            'action' => 'contributorVerify',
            'token' => $token,
        ]);

        $subject = sprintf('%s - Verify your email address', $siteName);

        $body = <<<EMAIL
Hello {$displayName},

Thank you for registering with {$siteName}.

Please click the link below to verify your email address:

{$verifyUrl}

This link will expire in 48 hours.

If you did not create an account, please ignore this email.

Best regards,
{$siteName} Team
EMAIL;

        // Use Symfony mailer if available, otherwise PHP mail
        try {
            if (class_exists('sfMail')) {
                $mail = new sfMail();
                $mail->initialize();
                $mail->setFrom(sfConfig::get('app_mail_from', 'noreply@' . $_SERVER['HTTP_HOST']));
                $mail->addAddress($email);
                $mail->setSubject($subject);
                $mail->setBody($body);

                return $mail->send();
            }

            // Fallback to PHP mail
            $headers = [
                'From: ' . sfConfig::get('app_mail_from', 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost')),
                'Reply-To: ' . sfConfig::get('app_mail_from', 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost')),
                'X-Mailer: PHP/' . phpversion(),
                'Content-Type: text/plain; charset=UTF-8',
            ];

            return mail($email, $subject, $body, implode("\r\n", $headers));
        } catch (\Exception $e) {
            // Log error but don't fail registration
            error_log('Failed to send verification email: ' . $e->getMessage());

            return false;
        }
    }
}
