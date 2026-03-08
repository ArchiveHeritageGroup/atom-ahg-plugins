<?php

use AtomFramework\Http\Controllers\AhgController;

class registryActions extends AhgController
{
    protected $pluginDir;

    public function boot(): void
    {
        $bootstrapFile = $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrapFile)) {
            require_once $bootstrapFile;
        }

        $this->pluginDir = $this->config('sf_plugins_dir') . '/ahgRegistryPlugin';
    }

    protected function loadService(string $name): object
    {
        $repoDir = $this->pluginDir . '/lib/Repositories/';
        $svcDir = $this->pluginDir . '/lib/Services/';

        // Load all repositories (services depend on them)
        foreach (glob($repoDir . '*.php') as $file) {
            require_once $file;
        }

        require_once $svcDir . $name . '.php';

        $class = '\\AhgRegistry\\Services\\' . $name;

        return new $class($this->culture());
    }

    protected function requireLogin(): ?object
    {
        $user = \sfContext::getInstance()->getUser();
        if (!$user || !$user->isAuthenticated()) {
            $this->redirect('/registry/login');

            return null;
        }

        return $user;
    }

    protected function requireAdminUser(): ?object
    {
        $user = $this->requireLogin();
        if (!$user) {
            return null;
        }

        if (!$user->hasCredential('administrator')) {
            $this->forward404();

            return null;
        }

        return $user;
    }

    protected function isAdmin(): bool
    {
        $user = \sfContext::getInstance()->getUser();

        return $user && $user->isAuthenticated() && $user->hasCredential('administrator');
    }

    /**
     * Log an error to the ahg_error_log table via ErrorNotificationService.
     */
    protected function logError(string $message, ?\Throwable $e = null): void
    {
        try {
            \AhgCore\Services\ErrorNotificationService::logToDatabase(
                'error',
                '[Registry] ' . $message,
                $e ? $e->getFile() : __FILE__,
                $e ? $e->getLine() : __LINE__,
                $e ? $e->getTraceAsString() : null,
                $e
            );
        } catch (\Throwable $ignored) {
            // Silently fail — never let logging break the app
        }
    }

    /**
     * Get the current user's institution, with admin fallback.
     * Admin: tries created_by first, then falls back to first institution.
     * Regular user: only returns institution they created.
     */
    protected function getMyInstitution(): ?object
    {
        $userId = $this->getCurrentUserId();
        $db = \Illuminate\Database\Capsule\Manager::class;

        // Admin can switch institutions via ?inst= parameter
        if ($this->isAdmin()) {
            $switchId = (int) \sfContext::getInstance()->getRequest()->getParameter('inst', 0);
            if ($switchId) {
                $inst = $db::table('registry_institution')->where('id', $switchId)->first();
                if ($inst) {
                    return $inst;
                }
            }
        }

        $inst = $db::table('registry_institution')
            ->where('created_by', $userId)->first();

        // Admin fallback: use first institution if none matched by created_by
        if (!$inst && $this->isAdmin()) {
            $inst = $db::table('registry_institution')
                ->orderBy('id', 'asc')->first();
        }

        return $inst;
    }

    /**
     * Get the current user's vendor (matched by created_by).
     */
    protected function getMyVendor(): ?object
    {
        $userId = $this->getCurrentUserId();
        $db = \Illuminate\Database\Capsule\Manager::class;

        $vendor = $db::table('registry_vendor')
            ->where('created_by', $userId)->first();

        // Admin fallback: use first vendor if none matched by created_by
        if (!$vendor && $this->isAdmin()) {
            $vendor = $db::table('registry_vendor')
                ->orderBy('id', 'asc')->first();
        }

        return $vendor;
    }

    protected function getRegistrySetting(string $key, $default = null)
    {
        return \Illuminate\Database\Capsule\Manager::table('registry_settings')
            ->where('setting_key', $key)
            ->value('setting_value') ?? $default;
    }

    /**
     * Save tags for an entity (replaces all existing tags).
     */
    protected function saveTags(string $entityType, int $entityId, string $tagsString): void
    {
        $db = \Illuminate\Database\Capsule\Manager::class;

        // Delete existing tags
        $db::table('registry_tag')
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->delete();

        // Parse and insert new tags
        $tags = array_filter(array_map('trim', explode(',', $tagsString)));
        foreach ($tags as $tag) {
            if ('' === $tag) {
                continue;
            }
            $db::table('registry_tag')->insert([
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'tag' => $tag,
            ]);
        }
    }

    protected function isFavorited(string $entityType, int $entityId): bool
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            return false;
        }

        return (bool) \Illuminate\Database\Capsule\Manager::table('registry_favorite')
            ->where('user_id', $userId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->first();
    }

    protected function getCurrentUserEmail(): ?string
    {
        $user = \sfContext::getInstance()->getUser();
        if ($user && $user->isAuthenticated()) {
            $userId = $user->getAttribute('user_id');
            if ($userId) {
                $row = \Illuminate\Database\Capsule\Manager::table('user')
                    ->where('id', $userId)
                    ->value('email');

                return $row ?: null;
            }
        }

        return null;
    }

    protected function getCurrentUserId(): ?int
    {
        $user = \sfContext::getInstance()->getUser();

        return $user && $user->isAuthenticated() ? (int) $user->getAttribute('user_id') : null;
    }

    protected function jsonResponse(array $data, int $status = 200): string
    {
        $response = \sfContext::getInstance()->getResponse();
        $response->setContentType('application/json');
        $response->setStatusCode($status);
        $response->setContent(json_encode($data, JSON_UNESCAPED_UNICODE));

        return \sfView::NONE;
    }

    // ================================================================
    // PUBLIC: Home, Community, Search, Map
    // ================================================================

    public function executeIndex($request)
    {
        $svc = $this->loadService('InstitutionService');
        $vendorSvc = $this->loadService('VendorService');
        $softwareSvc = $this->loadService('SoftwareService');
        $blogSvc = $this->loadService('BlogService');
        $discussionSvc = $this->loadService('DiscussionService');

        $db = \Illuminate\Database\Capsule\Manager::class;

        // Build cross-entity stats for the homepage
        $standardCount = 0;
        try {
            $standardCount = $db::table('registry_standard')->where('is_active', 1)->count();
        } catch (\Exception $e) {
            // Table may not exist yet
        }

        $this->stats = [
            'institutions' => $db::table('registry_institution')->where('is_active', 1)->count(),
            'vendors' => $db::table('registry_vendor')->where('is_active', 1)->count(),
            'software' => $db::table('registry_software')->where('is_active', 1)->count(),
            'groups' => $db::table('registry_user_group')->where('is_active', 1)->count(),
            'standards' => $standardCount,
        ];

        // Per-user favorites or admin-featured fallback
        $userId = $this->getCurrentUserId();
        $this->userFavoritesMode = false;

        if ($userId) {
            $favInstitutions = $db::table('registry_favorite as f')
                ->join('registry_institution as ri', 'ri.id', '=', 'f.entity_id')
                ->where('f.user_id', $userId)
                ->where('f.entity_type', 'institution')
                ->where('ri.is_active', 1)
                ->select('ri.*')
                ->orderBy('ri.name')
                ->limit(6)
                ->get();

            if ($favInstitutions->count() > 0) {
                $this->featuredInstitutions = $favInstitutions;
                $this->userFavoritesMode = true;
            } else {
                $this->featuredInstitutions = $svc->browse(['featured' => true, 'limit' => 6])['items'];
            }
        } else {
            $this->featuredInstitutions = $svc->browse(['featured' => true, 'limit' => 6])['items'];
        }

        // Get user's favorite IDs for star toggle on cards
        $this->userFavoriteIds = [];
        if ($userId) {
            $this->userFavoriteIds = $db::table('registry_favorite')
                ->where('user_id', $userId)
                ->where('entity_type', 'institution')
                ->pluck('entity_id')
                ->all();
        }

        $this->featuredVendors = $vendorSvc->browse(['featured' => true, 'limit' => 6])['items'];
        $this->featuredSoftware = $softwareSvc->browse(['featured' => true, 'limit' => 6])['items'];
        $this->recentBlog = $blogSvc->getPublished(4);
        $this->recentDiscussions = $discussionSvc->getRecentAcrossGroups(5);
    }

    public function executeCommunity($request)
    {
        $groupSvc = $this->loadService('UserGroupService');
        $discussionSvc = $this->loadService('DiscussionService');
        $blogSvc = $this->loadService('BlogService');

        $this->featuredGroups = $groupSvc->browse(['featured' => true, 'limit' => 6])['items'];
        $this->recentDiscussions = $discussionSvc->getRecentAcrossGroups(10);
        $this->latestBlog = $blogSvc->getPublished(6);
        $this->currentUserEmail = $this->getCurrentUserEmail();
    }

    public function executeSearch($request)
    {
        $svc = $this->loadService('RegistrySearchService');

        $query = trim($request->getParameter('q', ''));
        $type = $request->getParameter('type', '');
        $page = max(1, (int) $request->getParameter('page', 1));

        $this->query = $query;
        $this->type = $type;
        $this->results = [];
        $this->total = 0;
        $this->page = $page;

        if ('' !== $query) {
            $result = $svc->search($query, ['type' => $type, 'page' => $page, 'limit' => 20]);
            // Convert associative arrays to objects for template property access
            $this->results = array_map(function ($r) { return (object) $r; }, $result['items']);
            $this->total = $result['total'];
        }
    }

    public function executeMap($request)
    {
        $svc = $this->loadService('InstitutionService');
        $this->institutions = $svc->getForMap();
        $this->defaultLat = $this->getRegistrySetting('map_default_lat', '-30.5595');
        $this->defaultLng = $this->getRegistrySetting('map_default_lng', '22.9375');
        $this->defaultZoom = $this->getRegistrySetting('map_default_zoom', '5');
    }

    // ================================================================
    // PUBLIC: Institution Browse & View
    // ================================================================

    public function executeInstitutionBrowse($request)
    {
        $svc = $this->loadService('InstitutionService');

        $this->result = $svc->browse([
            'page' => max(1, (int) $request->getParameter('page', 1)),
            'limit' => 24,
            'type' => $request->getParameter('type', ''),
            'country' => $request->getParameter('country', ''),
            'sector' => $request->getParameter('sector', ''),
            'size' => $request->getParameter('size', ''),
            'governance' => $request->getParameter('governance', ''),
            'uses_atom' => $request->getParameter('uses_atom', ''),
            'search' => $request->getParameter('q', ''),
            'sort' => $request->getParameter('sort', 'name'),
            'direction' => $request->getParameter('dir', 'asc'),
        ]);
    }

    public function executeInstanceView($request)
    {
        $db = \Illuminate\Database\Capsule\Manager::class;
        $id = (int) $request->getParameter('id');

        $this->instance = $db::table('registry_instance')->where('id', $id)->first();
        if (!$this->instance) {
            $this->forward404();

            return;
        }

        $this->institution = $db::table('registry_institution')
            ->where('id', $this->instance->institution_id)->first();

        // Vendor info
        $this->hostingVendor = null;
        $this->maintenanceVendor = null;
        if (!empty($this->instance->hosting_vendor_id)) {
            $this->hostingVendor = $db::table('registry_vendor')
                ->where('id', $this->instance->hosting_vendor_id)->first();
        }
        if (!empty($this->instance->maintained_by_vendor_id)) {
            $this->maintenanceVendor = $db::table('registry_vendor')
                ->where('id', $this->instance->maintained_by_vendor_id)->first();
        }

        // Sync history
        $this->syncLogs = $db::table('registry_sync_log')
            ->where('instance_id', $id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    public function executeInstitutionView($request)
    {
        $svc = $this->loadService('InstitutionService');
        $slug = $request->getParameter('slug');

        // Support both slug and numeric ID lookup
        $this->institution = $svc->view($slug);
        if (!$this->institution && is_numeric($slug)) {
            $inst = \Illuminate\Database\Capsule\Manager::table('registry_institution')->where('id', (int) $slug)->first();
            if ($inst && !empty($inst->slug)) {
                $this->redirect(url_for(['module' => 'registry', 'action' => 'institutionView', 'slug' => $inst->slug]));

                return;
            }
        }
        if (!$this->institution) {
            $this->forward404();

            return;
        }

        $this->isAdmin = $this->isAdmin();
        $this->currentUserId = $this->getCurrentUserId();
        $this->isFavorited = $this->isFavorited('institution', (int) $this->institution['institution']->id);
    }

    // ================================================================
    // PUBLIC: Vendor Browse & View
    // ================================================================

    public function executeVendorBrowse($request)
    {
        $svc = $this->loadService('VendorService');

        $this->result = $svc->browse([
            'page' => max(1, (int) $request->getParameter('page', 1)),
            'limit' => 24,
            'type' => $request->getParameter('type', ''),
            'country' => $request->getParameter('country', ''),
            'specialization' => $request->getParameter('specialization', ''),
            'search' => $request->getParameter('q', ''),
            'sort' => $request->getParameter('sort', 'name'),
            'direction' => $request->getParameter('dir', 'asc'),
        ]);
    }

    public function executeVendorView($request)
    {
        $svc = $this->loadService('VendorService');
        $slug = $request->getParameter('slug');

        $this->vendor = $svc->view($slug);
        if (!$this->vendor && is_numeric($slug)) {
            $v = \Illuminate\Database\Capsule\Manager::table('registry_vendor')->where('id', (int) $slug)->first();
            if ($v && !empty($v->slug)) {
                $this->redirect(url_for(['module' => 'registry', 'action' => 'vendorView', 'slug' => $v->slug]));

                return;
            }
        }
        if (!$this->vendor) {
            $this->forward404();

            return;
        }

        $this->isAdmin = $this->isAdmin();
        $this->currentUserId = $this->getCurrentUserId();
        $this->isFavorited = $this->isFavorited('vendor', (int) $this->vendor['vendor']->id);
    }

    // ================================================================
    // PUBLIC: Software Browse, View, Releases
    // ================================================================

    public function executeSoftwareBrowse($request)
    {
        $svc = $this->loadService('SoftwareService');

        $this->result = $svc->browse([
            'page' => max(1, (int) $request->getParameter('page', 1)),
            'limit' => 24,
            'category' => $request->getParameter('category', ''),
            'vendor' => $request->getParameter('vendor', ''),
            'license' => $request->getParameter('license', ''),
            'pricing' => $request->getParameter('pricing', ''),
            'sector' => $request->getParameter('sector', ''),
            'search' => $request->getParameter('q', ''),
            'sort' => $request->getParameter('sort', 'name'),
            'direction' => $request->getParameter('dir', 'asc'),
        ]);
    }

    public function executeSoftwareView($request)
    {
        $svc = $this->loadService('SoftwareService');
        $slug = $request->getParameter('slug');

        $this->software = $svc->view($slug);
        if (!$this->software && is_numeric($slug)) {
            $sw = \Illuminate\Database\Capsule\Manager::table('registry_software')->where('id', (int) $slug)->first();
            if ($sw && !empty($sw->slug)) {
                $this->redirect(url_for(['module' => 'registry', 'action' => 'softwareView', 'slug' => $sw->slug]));

                return;
            }
        }
        if (!$this->software) {
            $this->forward404();

            return;
        }

        $this->isAdmin = $this->isAdmin();
        $this->currentUserId = $this->getCurrentUserId();
        $this->isFavorited = $this->isFavorited('software', (int) $this->software['software']->id);

        // Load components/plugins for this software
        $db = \Illuminate\Database\Capsule\Manager::class;
        $swId = (int) $this->software['software']->id;

        $this->components = $db::table('registry_software_component')
            ->where('software_id', $swId)
            ->where('is_active', 1)
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get()
            ->all();

        // Standards conformance
        try {
            $this->standardsConformance = $db::table('registry_software_standard as ss')
                ->join('registry_standard as st', 'ss.standard_id', '=', 'st.id')
                ->where('ss.software_id', $swId)
                ->select('st.name', 'st.acronym', 'st.slug', 'ss.conformance_level', 'ss.notes')
                ->orderBy('st.sort_order')
                ->get()->all();
        } catch (\Exception $e) {
            $this->standardsConformance = [];
        }

        // Setup guide count
        try {
            $this->setupGuideCount = $db::table('registry_setup_guide')
                ->where('software_id', $swId)
                ->where('is_active', 1)
                ->count();
        } catch (\Exception $e) {
            $this->setupGuideCount = 0;
        }
    }

    public function executeSoftwareReleases($request)
    {
        $svc = $this->loadService('SoftwareService');
        $slug = $request->getParameter('slug');

        $software = $svc->view($slug);
        if (!$software) {
            $this->forward404();

            return;
        }

        $this->software = $software;
        $this->releases = $svc->getReleases($software['software']->id);
    }

    // ================================================================
    // PUBLIC: Groups, Discussions, Blog
    // ================================================================

    public function executeGroupBrowse($request)
    {
        $svc = $this->loadService('UserGroupService');

        $this->result = $svc->browse([
            'page' => max(1, (int) $request->getParameter('page', 1)),
            'limit' => 24,
            'type' => $request->getParameter('type', ''),
            'country' => $request->getParameter('country', ''),
            'region' => $request->getParameter('region', ''),
            'is_virtual' => $request->getParameter('virtual', ''),
            'search' => $request->getParameter('q', ''),
            'sort' => $request->getParameter('sort', 'name'),
            'direction' => $request->getParameter('dir', 'asc'),
        ]);
    }

    public function executeGroupView($request)
    {
        $svc = $this->loadService('UserGroupService');
        $slug = $request->getParameter('slug');

        $this->group = $svc->view($slug);
        if (!$this->group) {
            $this->forward404();

            return;
        }

        $this->currentUserEmail = $this->getCurrentUserEmail();
        $this->isMember = false;
        if ($this->currentUserEmail && isset($this->group['group']->id)) {
            $this->isMember = $svc->isMember($this->group['group']->id, $this->currentUserEmail);
        }
    }

    public function executeGroupJoin($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $svc = $this->loadService('UserGroupService');
        $slug = $request->getParameter('slug');
        $email = $this->getCurrentUserEmail();
        $userId = $this->getCurrentUserId();

        try {
            $svc->join($slug, $email, $user->getAttribute('user_name', ''), $userId, null);
        } catch (\Throwable $e) {
            $this->logError('Group join failed (slug=' . $slug . '): ' . $e->getMessage(), $e);
            $user->setFlash('error', 'An error occurred while joining the group.');
            $this->redirect(url_for(['module' => 'registry', 'action' => 'groupView', 'slug' => $slug]));

            return;
        }

        $user->setFlash('success', 'You have successfully joined this group.');
        $this->redirect(url_for(['module' => 'registry', 'action' => 'groupView', 'slug' => $slug]));
    }

    public function executeGroupLeave($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $svc = $this->loadService('UserGroupService');
        $slug = $request->getParameter('slug');
        $email = $this->getCurrentUserEmail();

        $svc->leave($slug, $email);
        $user->setFlash('success', 'You have left this group.');

        $this->redirect(url_for(['module' => 'registry', 'action' => 'groupView', 'slug' => $slug]));
    }

    public function executeGroupToggleNotifications($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $slug = $request->getParameter('slug');
        $email = $this->getCurrentUserEmail();
        $db = \Illuminate\Database\Capsule\Manager::class;

        $group = $db::table('registry_user_group')->where('slug', $slug)->first();
        if (!$group) {
            $this->forward404();
            return;
        }

        $member = $db::table('registry_user_group_member')
            ->where('group_id', $group->id)
            ->where('email', $email)
            ->where('is_active', 1)
            ->first();

        if ($member) {
            $newVal = $member->email_notifications ? 0 : 1;
            $db::table('registry_user_group_member')
                ->where('id', $member->id)
                ->update(['email_notifications' => $newVal]);

            $user->setFlash('success', $newVal ? 'Email notifications enabled.' : 'Email notifications disabled.');
        }

        $this->redirect(url_for(['module' => 'registry', 'action' => 'groupView', 'slug' => $slug]));
    }

    public function executeGroupMembers($request)
    {
        $svc = $this->loadService('UserGroupService');
        $slug = $request->getParameter('slug');

        $group = $svc->view($slug);
        if (!$group) {
            $this->forward404();

            return;
        }

        $this->group = $group;
        $this->members = $svc->getMembers($group['group']->id);
    }

    public function executeDiscussionList($request)
    {
        $groupSvc = $this->loadService('UserGroupService');
        $discSvc = $this->loadService('DiscussionService');
        $slug = $request->getParameter('slug');

        $group = $groupSvc->view($slug);
        if (!$group) {
            $this->forward404();

            return;
        }

        $this->group = $group;
        $this->result = $discSvc->browse($group['group']->id, [
            'page' => max(1, (int) $request->getParameter('page', 1)),
            'limit' => 20,
            'topic_type' => $request->getParameter('topic_type', ''),
            'search' => $request->getParameter('q', ''),
        ]);

        $this->currentUserEmail = $this->getCurrentUserEmail();
    }

    public function executeDiscussionView($request)
    {
        $discSvc = $this->loadService('DiscussionService');
        $groupSvc = $this->loadService('UserGroupService');
        $slug = $request->getParameter('slug');
        $id = (int) $request->getParameter('id');

        $group = $groupSvc->view($slug);
        if (!$group) {
            $this->forward404();

            return;
        }

        $this->group = $group;
        $this->discussion = $discSvc->view($id);
        if (!$this->discussion) {
            $this->forward404();

            return;
        }

        $this->currentUserEmail = $this->getCurrentUserEmail();
        $this->isMember = false;
        if ($this->currentUserEmail) {
            $this->isMember = $groupSvc->isMember($group['group']->id, $this->currentUserEmail);
        }
    }

    public function executeDiscussionNew($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $groupSvc = $this->loadService('UserGroupService');
        $slug = $request->getParameter('slug');

        $group = $groupSvc->view($slug);
        if (!$group) {
            $this->forward404();

            return;
        }

        $this->group = $group;
        $this->errors = [];

        if ($request->isMethod('post')) {
            $discSvc = $this->loadService('DiscussionService');

            $data = [
                'title' => trim($request->getParameter('title', '')),
                'content' => trim($request->getParameter('content', '')),
                'topic_type' => $request->getParameter('topic_type', 'discussion'),
                'author_email' => $this->getCurrentUserEmail(),
                'author_name' => $user->getAttribute('user_name', ''),
                'author_user_id' => $this->getCurrentUserId(),
            ];

            if ('' === $data['title']) {
                $this->errors[] = 'Title is required.';
            }
            if ('' === $data['content']) {
                $this->errors[] = 'Content is required.';
            }

            if (empty($this->errors)) {
                try {
                    $result = $discSvc->create($group['group']->id, $data);
                    if (!empty($result['success'])) {
                        $this->notifyGroupMembers($group['group'], $data['title'], $data['content'], $data['author_name'], $result['id'], $data['author_email']);
                        $this->redirect(url_for(['module' => 'registry', 'action' => 'discussionView', 'slug' => $slug, 'id' => $result['id']]));

                        return;
                    }
                    $this->errors = [$result['error'] ?? 'Failed to create discussion'];
                } catch (\Throwable $e) {
                    $this->logError('Discussion create failed (group=' . $slug . '): ' . $e->getMessage(), $e);
                    $this->errors[] = 'An error occurred while creating the discussion.';
                }
            }

            $this->formData = $data;
        }
    }

    public function executeDiscussionReply($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $discSvc = $this->loadService('DiscussionService');
        $slug = $request->getParameter('slug');
        $id = (int) $request->getParameter('id');

        $data = [
            'content' => trim($request->getParameter('content', '')),
            'parent_reply_id' => $request->getParameter('parent_reply_id', null),
            'author_email' => $this->getCurrentUserEmail(),
            'author_name' => $user->getAttribute('user_name', ''),
            'author_user_id' => $this->getCurrentUserId(),
        ];

        if ('' !== $data['content']) {
            try {
                $discSvc->reply($id, $data);

                // Notify group members about the reply
                $db = \Illuminate\Database\Capsule\Manager::class;
                $discussion = $db::table('registry_discussion')->where('id', $id)->first();
                if ($discussion) {
                    $group = $db::table('registry_user_group')->where('id', $discussion->group_id)->first();
                    if ($group) {
                        $replyPreview = mb_substr(strip_tags($data['content']), 0, 200);
                        $this->notifyGroupMembers($group, 'Re: ' . $discussion->title, $replyPreview, $data['author_name'], $id, $data['author_email']);
                    }
                }
            } catch (\Throwable $e) {
                $this->logError('Discussion reply failed (id=' . $id . '): ' . $e->getMessage(), $e);
            }
        }

        $this->redirect(url_for(['module' => 'registry', 'action' => 'discussionView', 'slug' => $slug, 'id' => $id]));
    }

    public function executeBlogList($request)
    {
        $svc = $this->loadService('BlogService');

        $this->result = $svc->browse([
            'page' => max(1, (int) $request->getParameter('page', 1)),
            'limit' => 12,
            'category' => $request->getParameter('category', ''),
            'status' => 'published',
            'search' => $request->getParameter('q', ''),
        ]);
    }

    public function executeBlogView($request)
    {
        $svc = $this->loadService('BlogService');
        $slug = $request->getParameter('slug');

        $this->post = $svc->view($slug);
        if (!$this->post) {
            $this->forward404();

            return;
        }

        // Load or auto-create discussion thread for this blog post
        $db = \Illuminate\Database\Capsule\Manager::class;
        $postId = (int) $this->post->id;

        $discussion = $db::table('registry_discussion')
            ->where('blog_post_id', $postId)
            ->first();

        if (!$discussion) {
            $discId = $db::table('registry_discussion')->insertGetId([
                'group_id' => null,
                'blog_post_id' => $postId,
                'author_email' => $this->post->author_name ?? 'system',
                'author_name' => $this->post->author_name ?? 'System',
                'title' => $this->post->title,
                'content' => 'Comments for: ' . $this->post->title,
                'topic_type' => 'discussion',
                'status' => 'active',
                'reply_count' => 0,
                'view_count' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $discussion = $db::table('registry_discussion')->where('id', $discId)->first();
        }

        $discSvc = $this->loadService('DiscussionService');
        $this->discussion = $discSvc->view((int) $discussion->id);
        $this->currentUserEmail = $this->getCurrentUserEmail();
    }

    public function executeBlogReply($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $slug = $request->getParameter('slug');

        // Find blog post by slug
        $db = \Illuminate\Database\Capsule\Manager::class;
        $post = $db::table('registry_blog_post')
            ->where('slug', $slug)
            ->where('status', 'published')
            ->first();

        if (!$post) {
            $this->forward404();

            return;
        }

        // Check if comments are enabled
        if (isset($post->comments_enabled) && !$post->comments_enabled) {
            $this->redirect(url_for(['module' => 'registry', 'action' => 'blogView', 'slug' => $slug]));

            return;
        }

        // Find or create discussion for this blog post
        $discussion = $db::table('registry_discussion')
            ->where('blog_post_id', $post->id)
            ->first();

        if (!$discussion) {
            $this->forward404();

            return;
        }

        $data = [
            'content' => trim($request->getParameter('content', '')),
            'parent_reply_id' => $request->getParameter('parent_reply_id', null),
            'author_email' => $this->getCurrentUserEmail(),
            'author_name' => $user->getAttribute('user_name', ''),
            'author_user_id' => $this->getCurrentUserId(),
        ];

        if ('' !== $data['content']) {
            try {
                $discSvc = $this->loadService('DiscussionService');
                $discSvc->reply((int) $discussion->id, $data);

                // Update blog post comment_count
                $commentCount = $db::table('registry_discussion_reply')
                    ->where('discussion_id', $discussion->id)
                    ->where('status', 'active')
                    ->count();

                $db::table('registry_blog_post')
                    ->where('id', $post->id)
                    ->update(['comment_count' => $commentCount]);
            } catch (\Throwable $e) {
                $this->logError('Blog reply failed (slug=' . $slug . '): ' . $e->getMessage(), $e);
            }
        }

        $this->redirect(url_for(['module' => 'registry', 'action' => 'blogView', 'slug' => $slug]) . '#comments');
    }

    // ================================================================
    // SELF-SERVICE: Institution
    // ================================================================

    public function executeMyInstitutionDashboard($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $svc = $this->loadService('InstitutionService');

        $this->institution = $this->getMyInstitution();

        if (!$this->institution) {
            $this->redirect(url_for(['module' => 'registry', 'action' => 'institutionRegister']));

            return;
        }

        $instSvc = $this->loadService('InstanceService');
        $contactSvc = $this->loadService('ContactService');
        $relSvc = $this->loadService('RelationshipService');

        $this->instances = $instSvc->findByInstitution($this->institution->id);
        $this->contacts = $contactSvc->findByEntity('institution', $this->institution->id);
        $this->vendors = $relSvc->getInstitutionVendors($this->institution->id);
        $this->software = $relSvc->getInstitutionSoftware($this->institution->id);
    }

    public function executeInstitutionRegister($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $this->errors = [];

        if ($request->isMethod('post')) {
            $svc = $this->loadService('InstitutionService');

            $data = [
                'name' => trim($request->getParameter('name', '')),
                'institution_type' => $request->getParameter('institution_type', 'archive'),
                'description' => trim($request->getParameter('description', '')),
                'short_description' => trim($request->getParameter('short_description', '')),
                'website' => trim($request->getParameter('website', '')),
                'email' => trim($request->getParameter('email', '')),
                'phone' => trim($request->getParameter('phone', '')),
                'street_address' => trim($request->getParameter('street_address', '')),
                'city' => trim($request->getParameter('city', '')),
                'province_state' => trim($request->getParameter('province_state', '')),
                'postal_code' => trim($request->getParameter('postal_code', '')),
                'country' => trim($request->getParameter('country', '')) ?: $this->getRegistrySetting('default_country', 'South Africa'),
                'size' => $request->getParameter('size', '') ?: null,
                'governance' => $request->getParameter('governance', '') ?: null,
                'parent_body' => trim($request->getParameter('parent_body', '')) ?: null,
                'established_year' => $request->getParameter('established_year', '') !== '' ? (int) $request->getParameter('established_year') : null,
                'collection_summary' => trim($request->getParameter('collection_summary', '')) ?: null,
                'total_holdings' => trim($request->getParameter('total_holdings', '')) ?: null,
                'management_system' => trim($request->getParameter('management_system', '')) ?: null,
                'uses_atom' => $request->getParameter('uses_atom', 0) ? 1 : 0,
                'created_by' => $this->getCurrentUserId(),
            ];

            if ('' === $data['name']) {
                $this->errors[] = 'Institution name is required.';
            }

            if (empty($this->errors)) {
                try {
                    $id = $svc->create($data);
                    $this->redirect(url_for(['module' => 'registry', 'action' => 'myInstitutionDashboard']));

                    return;
                } catch (\Throwable $e) {
                    $this->logError('Institution register failed: ' . $e->getMessage(), $e);
                    $this->errors[] = 'An error occurred while registering the institution.';
                }
            }

            $this->formData = (object) $data;
        }
    }

    public function executeInstitutionEdit($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $editId = (int) $request->getParameter('id', 0);

        // Admin can edit any institution by ID; regular users edit their own
        if ($editId && $this->isAdmin()) {
            $this->institution = $db::table('registry_institution')->where('id', $editId)->first();
        } else {
            $this->institution = $this->getMyInstitution();
        }

        if (!$this->institution) {
            $this->redirect(url_for(['module' => 'registry', 'action' => 'institutionRegister']));

            return;
        }

        // Load existing tags for display
        $this->institutionTags = \Illuminate\Database\Capsule\Manager::table('registry_tag')
            ->where('entity_type', 'institution')
            ->where('entity_id', $this->institution->id)
            ->orderBy('tag')
            ->get()
            ->all();

        $this->errors = [];

        if ($request->isMethod('post')) {
            $svc = $this->loadService('InstitutionService');

            $data = [
                'name' => trim($request->getParameter('name', '')),
                'institution_type' => $request->getParameter('institution_type', 'archive'),
                'description' => trim($request->getParameter('description', '')),
                'short_description' => trim($request->getParameter('short_description', '')),
                'website' => trim($request->getParameter('website', '')),
                'email' => trim($request->getParameter('email', '')),
                'phone' => trim($request->getParameter('phone', '')),
                'fax' => trim($request->getParameter('fax', '')),
                'street_address' => trim($request->getParameter('street_address', '')),
                'city' => trim($request->getParameter('city', '')),
                'province_state' => trim($request->getParameter('province_state', '')),
                'postal_code' => trim($request->getParameter('postal_code', '')),
                'country' => trim($request->getParameter('country', '')),
                'latitude' => ('' !== trim((string) $request->getParameter('latitude', ''))) ? (float) $request->getParameter('latitude') : null,
                'longitude' => ('' !== trim((string) $request->getParameter('longitude', ''))) ? (float) $request->getParameter('longitude') : null,
                'size' => $request->getParameter('size', '') ?: null,
                'governance' => $request->getParameter('governance', '') ?: null,
                'parent_body' => trim($request->getParameter('parent_body', '')) ?: null,
                'established_year' => $request->getParameter('established_year', '') !== '' ? (int) $request->getParameter('established_year') : null,
                'accreditation' => trim($request->getParameter('accreditation', '')) ?: null,
                'collection_summary' => trim($request->getParameter('collection_summary', '')) ?: null,
                'total_holdings' => trim($request->getParameter('total_holdings', '')) ?: null,
                'digitization_percentage' => $request->getParameter('digitization_percentage', '') !== '' ? (int) $request->getParameter('digitization_percentage') : null,
                'management_system' => trim($request->getParameter('management_system', '')) ?: null,
                'uses_atom' => $request->getParameter('uses_atom', 0) ? 1 : 0,
                'institution_url' => trim($request->getParameter('institution_url', '')) ?: null,
                'open_to_public' => $request->getParameter('open_to_public', 0) ? 1 : 0,
            ];

            // Handle JSON array fields
            $standards = $request->getParameter('descriptive_standards', []);
            $data['descriptive_standards'] = is_array($standards) && !empty($standards) ? json_encode(array_values($standards)) : null;

            $strengths = trim($request->getParameter('collection_strengths', ''));
            if ($strengths) {
                $data['collection_strengths'] = json_encode(array_map('trim', explode(',', $strengths)));
            } else {
                $data['collection_strengths'] = null;
            }

            $sectors = $request->getParameter('glam_sectors', []);
            if (is_array($sectors) && !empty($sectors)) {
                $data['glam_sectors'] = json_encode(array_values($sectors));
            }

            // Handle logo upload
            $logoFile = isset($_FILES['logo']) && !empty($_FILES['logo']['tmp_name']) ? $_FILES['logo'] : null;
            if ($logoFile) {
                $uploadDir = \sfConfig::get('sf_upload_dir', '/usr/share/nginx/archive/uploads') . '/registry/institutions';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $ext = strtolower(pathinfo($logoFile['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'webp'])) {
                    $filename = 'institution-' . $this->institution->id . '-' . time() . '.' . $ext;
                    if (move_uploaded_file($logoFile['tmp_name'], $uploadDir . '/' . $filename)) {
                        $data['logo_path'] = '/uploads/registry/institutions/' . $filename;
                    }
                }
            }

            if ('' === $data['name']) {
                $this->errors[] = 'Institution name is required.';
            }

            if (empty($this->errors)) {
                try {
                    $result = $svc->update($this->institution->id, $data);

                    // Save tags
                    $tagsStr = trim($request->getParameter('tags', ''));
                    $this->saveTags('institution', $this->institution->id, $tagsStr);

                    $sfUser = \sfContext::getInstance()->getUser();
                    if (!empty($result['success'])) {
                        $sfUser->setFlash('success', __('Institution updated successfully.'));
                    } else {
                        $sfUser->setFlash('error', $result['error'] ?? __('Failed to save changes.'));
                    }

                    // Admin goes back to admin list; regular user goes to dashboard
                    if ($editId && $this->isAdmin()) {
                        $this->redirect(url_for(['module' => 'registry', 'action' => 'adminInstitutions']));
                    } else {
                        $this->redirect(url_for(['module' => 'registry', 'action' => 'myInstitutionDashboard']));
                    }

                    return;
                } catch (\Throwable $e) {
                    $this->logError('Institution update failed (id=' . $this->institution->id . '): ' . $e->getMessage(), $e);
                    $this->errors[] = 'An error occurred while saving the institution.';
                }
            }
        }
    }

    public function executeMyInstitutionContacts($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $this->setTemplate('contactsManage');

        $this->institution = $this->getMyInstitution();
        if (!$this->institution) {
            $this->redirect(url_for(['module' => 'registry', 'action' => 'institutionRegister']));

            return;
        }

        $svc = $this->loadService('ContactService');
        $this->contacts = $svc->findByEntity('institution', $this->institution->id);
        $this->entityType = 'institution';
        $this->entityId = $this->institution->id;
        $this->backUrl = url_for(['module' => 'registry', 'action' => 'myInstitutionDashboard']);
    }

    public function executeMyInstitutionContactAdd($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $this->setTemplate('contactForm');

        $db = \Illuminate\Database\Capsule\Manager::class;

        // Admin can target a specific institution via ?inst= parameter
        $targetId = (int) $request->getParameter('inst', 0);
        if ($targetId && $this->isAdmin()) {
            $institution = $db::table('registry_institution')->where('id', $targetId)->first();
        } else {
            $institution = $this->getMyInstitution();
        }

        if (!$institution) {
            $this->redirect(url_for(['module' => 'registry', 'action' => 'institutionRegister']));

            return;
        }

        $this->entityType = 'institution';
        $this->entityId = $institution->id;
        $this->errors = [];
        $this->contact = null;
        $this->backUrl = '/registry/institutions/' . urlencode($institution->slug);

        if ($request->isMethod('post')) {
            $svc = $this->loadService('ContactService');
            $data = $this->getContactFormData($request, 'institution', $institution->id);

            if ('' === $data['first_name'] || '' === $data['last_name']) {
                $this->errors[] = 'First name and last name are required.';
            }

            if (empty($this->errors)) {
                try {
                    $svc->create($data);
                    $this->redirect($this->backUrl);

                    return;
                } catch (\Throwable $e) {
                    $this->logError('Institution contact add failed: ' . $e->getMessage(), $e);
                    $this->errors[] = 'An error occurred while saving the contact.';
                }
            }

            $this->contact = (object) $data;
        }
    }

    public function executeMyInstitutionContactEdit($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $this->setTemplate('contactForm');

        $svc = $this->loadService('ContactService');
        $id = (int) $request->getParameter('id');
        $this->contact = \Illuminate\Database\Capsule\Manager::table('registry_contact')
            ->where('id', $id)->first();
        if (!$this->contact) {
            $this->forward404();

            return;
        }

        $this->entityType = $this->contact->entity_type;
        $this->entityId = $this->contact->entity_id;
        $this->errors = [];
        $inst = \Illuminate\Database\Capsule\Manager::table('registry_institution')
            ->where('id', $this->contact->entity_id)->first();
        $this->backUrl = $inst ? '/registry/institutions/' . urlencode($inst->slug) : url_for(['module' => 'registry', 'action' => 'myInstitutionContacts']);

        if ($request->isMethod('post')) {
            $data = $this->getContactFormData($request, $this->contact->entity_type, $this->contact->entity_id);

            if ('' === $data['first_name'] || '' === $data['last_name']) {
                $this->errors[] = 'First name and last name are required.';
            }

            if (empty($this->errors)) {
                try {
                    $svc->update($id, $data);
                    $this->redirect($this->backUrl);

                    return;
                } catch (\Throwable $e) {
                    $this->logError('Contact update failed (id=' . $id . '): ' . $e->getMessage(), $e);
                    $this->errors[] = 'An error occurred while saving the contact.';
                }
            }

            $this->contact = (object) $data;
        }
    }

    public function executeMyInstitutionInstances($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $this->setTemplate('instancesManage');

        $this->institution = $this->getMyInstitution();
        if (!$this->institution) {
            $this->redirect(url_for(['module' => 'registry', 'action' => 'institutionRegister']));

            return;
        }

        $svc = $this->loadService('InstanceService');
        $this->instances = $svc->findByInstitution($this->institution->id);
    }

    public function executeMyInstitutionInstanceAdd($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $this->setTemplate('instanceForm');

        $institution = $this->getMyInstitution();
        if (!$institution) {
            $this->redirect(url_for(['module' => 'registry', 'action' => 'institutionRegister']));

            return;
        }

        $this->institution = $institution;
        $this->instance = null;
        $this->errors = [];
        $this->vendors = \Illuminate\Database\Capsule\Manager::table('registry_vendor')
            ->where('is_active', 1)->orderBy('name')->get();
        $this->allSoftware = \Illuminate\Database\Capsule\Manager::table('registry_software')
            ->where('is_active', 1)->orderBy('name')->select('id', 'name', 'slug', 'latest_version')->get()->all();

        if ($request->isMethod('post')) {
            $svc = $this->loadService('InstanceService');

            $features = $request->getParameter('features', []);
            $featureUsage = is_array($features) ? json_encode($features) : null;

            $langRaw = trim($request->getParameter('languages', ''));
            $langJson = null;
            if ('' !== $langRaw) {
                $langArr = array_values(array_filter(array_map('trim', explode(',', $langRaw))));
                $langJson = !empty($langArr) ? json_encode($langArr) : null;
            }

            $data = [
                'institution_id' => $institution->id,
                'name' => trim($request->getParameter('name', '')),
                'url' => trim($request->getParameter('url', '')),
                'instance_type' => $request->getParameter('instance_type', 'production'),
                'software' => trim($request->getParameter('software', 'heratio')),
                'software_version' => trim($request->getParameter('software_version', '')),
                'hosting' => $request->getParameter('hosting', '') ?: null,
                'hosting_vendor_id' => $request->getParameter('hosting_vendor_id', '') ?: null,
                'maintained_by_vendor_id' => $request->getParameter('maintained_by_vendor_id', '') ?: null,
                'os_environment' => trim($request->getParameter('os_environment', '')) ?: null,
                'languages' => $langJson,
                'record_count' => $request->getParameter('record_count', '') !== '' ? (int) $request->getParameter('record_count') : null,
                'digital_object_count' => $request->getParameter('digital_object_count', '') !== '' ? (int) $request->getParameter('digital_object_count') : null,
                'storage_gb' => $request->getParameter('storage_gb', '') !== '' ? $request->getParameter('storage_gb') : null,
                'descriptive_standard' => $request->getParameter('descriptive_standard', '') ?: null,
                'feature_usage' => $featureUsage,
                'sync_enabled' => $request->getParameter('sync_enabled', 0) ? 1 : 0,
                'description' => trim($request->getParameter('description', '')),
                'is_public' => $request->getParameter('is_public', 0) ? 1 : 0,
            ];

            if ('' === $data['name']) {
                $this->errors[] = 'Instance name is required.';
            }

            if (empty($this->errors)) {
                $svc->create($data);
                $this->redirect(url_for(['module' => 'registry', 'action' => 'myInstitutionInstances']));

                return;
            }

            $this->instance = (object) $data;
        }
    }

    public function executeMyInstitutionInstanceEdit($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $this->setTemplate('instanceForm');

        $svc = $this->loadService('InstanceService');
        $id = (int) $request->getParameter('id');
        $this->instance = \Illuminate\Database\Capsule\Manager::table('registry_instance')
            ->where('id', $id)->first();
        if (!$this->instance) {
            $this->forward404();

            return;
        }

        $this->institution = \Illuminate\Database\Capsule\Manager::table('registry_institution')
            ->where('id', $this->instance->institution_id)->first();
        $this->errors = [];
        $this->vendors = \Illuminate\Database\Capsule\Manager::table('registry_vendor')
            ->where('is_active', 1)->orderBy('name')->get();
        $this->allSoftware = \Illuminate\Database\Capsule\Manager::table('registry_software')
            ->where('is_active', 1)->orderBy('name')->select('id', 'name', 'slug', 'latest_version')->get()->all();

        if ($request->isMethod('post')) {
            $features = $request->getParameter('features', []);
            $featureUsage = is_array($features) ? json_encode($features) : null;

            $langRaw = trim($request->getParameter('languages', ''));
            $langJson = null;
            if ('' !== $langRaw) {
                $langArr = array_values(array_filter(array_map('trim', explode(',', $langRaw))));
                $langJson = !empty($langArr) ? json_encode($langArr) : null;
            }

            $data = [
                'name' => trim($request->getParameter('name', '')),
                'url' => trim($request->getParameter('url', '')),
                'instance_type' => $request->getParameter('instance_type', 'production'),
                'software' => trim($request->getParameter('software', 'heratio')),
                'software_version' => trim($request->getParameter('software_version', '')),
                'hosting' => $request->getParameter('hosting', '') ?: null,
                'hosting_vendor_id' => $request->getParameter('hosting_vendor_id', '') ?: null,
                'maintained_by_vendor_id' => $request->getParameter('maintained_by_vendor_id', '') ?: null,
                'os_environment' => trim($request->getParameter('os_environment', '')) ?: null,
                'languages' => $langJson,
                'record_count' => $request->getParameter('record_count', '') !== '' ? (int) $request->getParameter('record_count') : null,
                'digital_object_count' => $request->getParameter('digital_object_count', '') !== '' ? (int) $request->getParameter('digital_object_count') : null,
                'storage_gb' => $request->getParameter('storage_gb', '') !== '' ? $request->getParameter('storage_gb') : null,
                'descriptive_standard' => $request->getParameter('descriptive_standard', '') ?: null,
                'feature_usage' => $featureUsage,
                'sync_enabled' => $request->getParameter('sync_enabled', 0) ? 1 : 0,
                'description' => trim($request->getParameter('description', '')),
                'is_public' => $request->getParameter('is_public', 0) ? 1 : 0,
            ];

            if ('' === $data['name']) {
                $this->errors[] = 'Instance name is required.';
            }

            if (empty($this->errors)) {
                $svc->update($id, $data);
                $this->redirect(url_for(['module' => 'registry', 'action' => 'myInstitutionInstances']));

                return;
            }

            $this->instance = (object) array_merge((array) $this->instance, $data);
        }
    }

    public function executeMyInstitutionInstanceDelete($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $id = (int) $request->getParameter('id');
        $instance = \Illuminate\Database\Capsule\Manager::table('registry_instance')
            ->where('id', $id)->first();

        if ($instance) {
            \Illuminate\Database\Capsule\Manager::table('registry_instance')->where('id', $id)->delete();
        }

        $this->redirect(url_for(['module' => 'registry', 'action' => 'myInstitutionInstances']));
    }

    public function executeMyInstitutionSoftware($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $this->setTemplate('institutionSoftware');

        $this->institution = $this->getMyInstitution();
        if (!$this->institution) {
            $this->redirect(url_for(['module' => 'registry', 'action' => 'institutionRegister']));

            return;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $svc = $this->loadService('RelationshipService');

        // Handle POST: add or remove software
        if ($request->isMethod('post')) {
            $formAction = $request->getParameter('form_action', '');

            if ('add' === $formAction) {
                $softwareId = (int) $request->getParameter('software_id', 0);
                $versionInUse = trim($request->getParameter('version_in_use', ''));
                $notes = trim($request->getParameter('notes', ''));

                if ($softwareId > 0) {
                    $svc->assignSoftware([
                        'institution_id' => $this->institution->id,
                        'software_id' => $softwareId,
                        'version_in_use' => $versionInUse ?: null,
                        'notes' => $notes ?: null,
                    ]);
                }
            } elseif ('remove' === $formAction) {
                $assignmentId = (int) $request->getParameter('assignment_id', 0);
                if ($assignmentId > 0) {
                    $svc->removeSoftwareAssignment($assignmentId);
                }
            }

            $this->redirect(url_for(['module' => 'registry', 'action' => 'myInstitutionSoftware']));

            return;
        }

        $this->software = $svc->getInstitutionSoftware($this->institution->id);

        // Get all available software for the dropdown
        $this->allSoftware = $db::table('registry_software')
            ->where('is_active', 1)
            ->orderBy('name', 'asc')
            ->select('id', 'name', 'latest_version')
            ->get()
            ->all();
    }

    public function executeMyInstitutionVendors($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $this->setTemplate('institutionVendors');

        $this->institution = $this->getMyInstitution();
        if (!$this->institution) {
            $this->redirect(url_for(['module' => 'registry', 'action' => 'institutionRegister']));

            return;
        }

        $svc = $this->loadService('RelationshipService');
        $this->vendors = $svc->getInstitutionVendors($this->institution->id);
    }

    public function executeMyInstitutionReview($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $this->setTemplate('reviewForm');

        $type = $request->getParameter('type');
        $entityId = (int) $request->getParameter('id');

        if (!in_array($type, ['vendor', 'software'])) {
            $this->forward404();

            return;
        }

        $this->entityType = $type;
        $this->entityId = $entityId;
        $this->errors = [];

        // Get entity name for display
        $table = 'vendor' === $type ? 'registry_vendor' : 'registry_software';
        $this->entity = \Illuminate\Database\Capsule\Manager::table($table)
            ->where('id', $entityId)->first();
        if (!$this->entity) {
            $this->forward404();

            return;
        }

        if ($request->isMethod('post')) {
            $svc = $this->loadService('ReviewService');

            $institution = $this->getMyInstitution();

            $data = [
                'entity_type' => $type,
                'entity_id' => $entityId,
                'reviewer_institution_id' => $institution ? $institution->id : null,
                'reviewer_name' => trim($request->getParameter('reviewer_name', '')),
                'reviewer_email' => $this->getCurrentUserEmail(),
                'rating' => max(1, min(5, (int) $request->getParameter('rating', 5))),
                'title' => trim($request->getParameter('title', '')),
                'comment' => trim($request->getParameter('comment', '')),
            ];

            if ('' === $data['comment']) {
                $this->errors[] = 'Please write a review comment.';
            }

            if (empty($this->errors)) {
                $svc->create($data);
                $slug = $this->entity->slug ?? '';
                $action = 'vendor' === $type ? 'vendorView' : 'softwareView';
                $this->redirect(url_for(['module' => 'registry', 'action' => $action, 'slug' => $slug]));

                return;
            }
        }
    }

    // ================================================================
    // SELF-SERVICE: Vendor
    // ================================================================

    public function executeMyVendorDashboard($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $this->vendor = $this->getMyVendor();

        if (!$this->vendor) {
            $this->redirect(url_for(['module' => 'registry', 'action' => 'vendorRegister']));

            return;
        }

        $relSvc = $this->loadService('RelationshipService');
        $contactSvc = $this->loadService('ContactService');

        $this->clients = $relSvc->getVendorClients($this->vendor->id);
        $this->contacts = $contactSvc->findByEntity('vendor', $this->vendor->id);
        $this->software = \Illuminate\Database\Capsule\Manager::table('registry_software')
            ->where('vendor_id', $this->vendor->id)->where('is_active', 1)->get()->all();
    }

    public function executeVendorRegister($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $this->errors = [];

        if ($request->isMethod('post')) {
            $svc = $this->loadService('VendorService');

            $data = [
                'name' => trim($request->getParameter('name', '')),
                'vendor_type' => json_encode(array_values(array_filter((array) $request->getParameter('vendor_type', [])))),
                'description' => trim($request->getParameter('description', '')),
                'short_description' => trim($request->getParameter('short_description', '')),
                'website' => trim($request->getParameter('website', '')),
                'email' => trim($request->getParameter('email', '')),
                'phone' => trim($request->getParameter('phone', '')),
                'street_address' => trim($request->getParameter('street_address', '')),
                'city' => trim($request->getParameter('city', '')),
                'province_state' => trim($request->getParameter('province_state', '')),
                'postal_code' => trim($request->getParameter('postal_code', '')),
                'country' => trim($request->getParameter('country', '')) ?: $this->getRegistrySetting('default_country', 'South Africa'),
                'company_registration' => trim($request->getParameter('company_registration', '')) ?: null,
                'established_year' => $request->getParameter('established_year', '') !== '' ? (int) $request->getParameter('established_year') : null,
                'team_size' => in_array($request->getParameter('team_size', ''), ['solo', '2-5', '6-20', '21-50', '50+']) ? $request->getParameter('team_size') : null,
                'github_url' => trim($request->getParameter('github_url', '')) ?: null,
                'created_by' => $this->getCurrentUserId(),
            ];

            if ('' === $data['name']) {
                $this->errors[] = 'Vendor name is required.';
            }

            if (empty($this->errors)) {
                try {
                    $svc->create($data);
                    $this->redirect(url_for(['module' => 'registry', 'action' => 'myVendorDashboard']));

                    return;
                } catch (\Throwable $e) {
                    $this->logError('Vendor register failed: ' . $e->getMessage(), $e);
                    $this->errors[] = 'An error occurred while registering the vendor.';
                }
            }

            $this->formData = (object) $data;
        }
    }

    public function executeVendorEdit($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $editId = (int) $request->getParameter('id', 0);
        $userId = $this->getCurrentUserId();

        if ($editId) {
            // Load specific vendor — admin can edit any, regular user only their own
            $this->vendor = $db::table('registry_vendor')->where('id', $editId)->first();
            if ($this->vendor && !$this->isAdmin() && (int) ($this->vendor->created_by ?? 0) !== $userId) {
                $this->vendor = null;
            }
        } else {
            $this->vendor = $this->getMyVendor();
        }

        if (!$this->vendor) {
            $this->forward404();

            return;
        }

        // Load existing tags for display
        $this->vendorTags = \Illuminate\Database\Capsule\Manager::table('registry_tag')
            ->where('entity_type', 'vendor')
            ->where('entity_id', $this->vendor->id)
            ->orderBy('tag')
            ->get()
            ->all();

        $this->errors = [];

        if ($request->isMethod('post')) {
            $svc = $this->loadService('VendorService');

            $data = [
                'name' => trim($request->getParameter('name', '')),
                'vendor_type' => json_encode(array_values(array_filter((array) $request->getParameter('vendor_type', [])))),
                'description' => trim($request->getParameter('description', '')),
                'short_description' => trim($request->getParameter('short_description', '')),
                'website' => trim($request->getParameter('website', '')),
                'email' => trim($request->getParameter('email', '')),
                'phone' => trim($request->getParameter('phone', '')),
                'street_address' => trim($request->getParameter('street_address', '')),
                'city' => trim($request->getParameter('city', '')),
                'province_state' => trim($request->getParameter('province_state', '')),
                'postal_code' => trim($request->getParameter('postal_code', '')),
                'country' => trim($request->getParameter('country', '')),
                'company_registration' => trim($request->getParameter('company_registration', '')) ?: null,
                'vat_number' => trim($request->getParameter('vat_number', '')) ?: null,
                'established_year' => $request->getParameter('established_year', '') !== '' ? (int) $request->getParameter('established_year') : null,
                'team_size' => in_array($request->getParameter('team_size', ''), ['solo', '2-5', '6-20', '21-50', '50+']) ? $request->getParameter('team_size') : null,
                'github_url' => trim($request->getParameter('github_url', '')) ?: null,
                'gitlab_url' => trim($request->getParameter('gitlab_url', '')) ?: null,
                'linkedin_url' => trim($request->getParameter('linkedin_url', '')) ?: null,
            ];

            // Handle logo upload
            $logoFile = isset($_FILES['logo']) && !empty($_FILES['logo']['tmp_name']) ? $_FILES['logo'] : null;
            if ($logoFile) {
                $uploadDir = \sfConfig::get('sf_upload_dir', '/usr/share/nginx/archive/uploads') . '/registry/vendors';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $ext = strtolower(pathinfo($logoFile['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'webp'])) {
                    $filename = 'vendor-' . $this->vendor->id . '-' . time() . '.' . $ext;
                    if (move_uploaded_file($logoFile['tmp_name'], $uploadDir . '/' . $filename)) {
                        $data['logo_path'] = '/uploads/registry/vendors/' . $filename;
                    }
                }
            }

            if ('' === $data['name']) {
                $this->errors[] = 'Vendor name is required.';
            }

            if (empty($this->errors)) {
                try {
                    $svc->update($this->vendor->id, $data);

                    // Save tags
                    $tagsStr = trim($request->getParameter('tags', ''));
                    $this->saveTags('vendor', $this->vendor->id, $tagsStr);

                    if ($editId && $this->isAdmin()) {
                        $this->redirect(url_for(['module' => 'registry', 'action' => 'adminVendors']));
                    } else {
                        $this->redirect(url_for(['module' => 'registry', 'action' => 'myVendorDashboard']));
                    }

                    return;
                } catch (\Throwable $e) {
                    $this->logError('Vendor update failed (id=' . $this->vendor->id . '): ' . $e->getMessage(), $e);
                    $this->errors[] = 'An error occurred while saving the vendor.';
                }
            }
        }
    }

    public function executeMyVendorContacts($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $this->setTemplate('contactsManage');

        $this->vendor = $this->getMyVendor();
        if (!$this->vendor) {
            $this->forward404();

            return;
        }

        $svc = $this->loadService('ContactService');
        $this->contacts = $svc->findByEntity('vendor', $this->vendor->id);
        $this->entityType = 'vendor';
        $this->entityId = $this->vendor->id;
        $this->backUrl = url_for(['module' => 'registry', 'action' => 'myVendorDashboard']);
    }

    public function executeMyVendorContactAdd($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $this->setTemplate('contactForm');

        $db = \Illuminate\Database\Capsule\Manager::class;

        // Admin can target a specific vendor via ?vendor= parameter
        $targetId = (int) $request->getParameter('vendor', 0);
        if ($targetId && $this->isAdmin()) {
            $vendor = $db::table('registry_vendor')->where('id', $targetId)->first();
        } else {
            $vendor = $this->getMyVendor();
        }

        if (!$vendor) {
            $this->forward404();

            return;
        }

        $this->entityType = 'vendor';
        $this->entityId = $vendor->id;
        $this->errors = [];
        $this->contact = null;
        $this->backUrl = '/registry/vendors/' . urlencode($vendor->slug);

        if ($request->isMethod('post')) {
            $svc = $this->loadService('ContactService');
            $data = $this->getContactFormData($request, 'vendor', $vendor->id);

            if ('' === $data['first_name'] || '' === $data['last_name']) {
                $this->errors[] = 'First name and last name are required.';
            }

            if (empty($this->errors)) {
                try {
                    $svc->create($data);
                    $this->redirect($this->backUrl);

                    return;
                } catch (\Throwable $e) {
                    $this->logError('Vendor contact add failed: ' . $e->getMessage(), $e);
                    $this->errors[] = 'An error occurred while saving the contact.';
                }
            }

            $this->contact = (object) $data;
        }
    }

    public function executeMyVendorContactEdit($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $this->setTemplate('contactForm');

        $svc = $this->loadService('ContactService');
        $id = (int) $request->getParameter('id');
        $this->contact = \Illuminate\Database\Capsule\Manager::table('registry_contact')
            ->where('id', $id)->first();
        if (!$this->contact) {
            $this->forward404();

            return;
        }

        $this->entityType = $this->contact->entity_type;
        $this->entityId = $this->contact->entity_id;
        $this->errors = [];
        $vendorRow = \Illuminate\Database\Capsule\Manager::table('registry_vendor')
            ->where('id', $this->contact->entity_id)->first();
        $this->backUrl = $vendorRow ? '/registry/vendors/' . urlencode($vendorRow->slug) : url_for(['module' => 'registry', 'action' => 'myVendorContacts']);

        if ($request->isMethod('post')) {
            $data = $this->getContactFormData($request, $this->contact->entity_type, $this->contact->entity_id);

            if ('' === $data['first_name'] || '' === $data['last_name']) {
                $this->errors[] = 'First name and last name are required.';
            }

            if (empty($this->errors)) {
                $svc->update($id, $data);
                $this->redirect($this->backUrl);

                return;
            }

            $this->contact = (object) $data;
        }
    }

    public function executeMyVendorClients($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $this->setTemplate('vendorClients');

        $this->vendor = $this->getMyVendor();
        if (!$this->vendor) {
            $this->forward404();

            return;
        }

        $svc = $this->loadService('RelationshipService');
        $this->clients = $svc->getVendorClients($this->vendor->id);
        $this->institutions = \Illuminate\Database\Capsule\Manager::table('registry_institution')
            ->where('is_active', 1)->orderBy('name')->get();
    }

    public function executeMyVendorClientAdd($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $this->setTemplate('vendorClientForm');

        $vendor = $this->getMyVendor();
        if (!$vendor) {
            $this->forward404();

            return;
        }

        $this->vendor = $vendor;
        $this->errors = [];
        $this->institutions = \Illuminate\Database\Capsule\Manager::table('registry_institution')
            ->where('is_active', 1)->orderBy('name')->get();

        if ($request->isMethod('post')) {
            $svc = $this->loadService('RelationshipService');

            $data = [
                'vendor_id' => $vendor->id,
                'institution_id' => (int) $request->getParameter('institution_id'),
                'relationship_type' => $request->getParameter('relationship_type', 'developer'),
                'service_description' => trim($request->getParameter('service_description', '')),
                'start_date' => $request->getParameter('start_date', null) ?: null,
                'is_public' => $request->getParameter('is_public', 1) ? 1 : 0,
            ];

            if (!$data['institution_id']) {
                $this->errors[] = 'Please select an institution.';
            }

            if (empty($this->errors)) {
                $svc->createVendorRelationship($data);
                $svc->updateClientCount($vendor->id);
                $this->redirect(url_for(['module' => 'registry', 'action' => 'myVendorClients']));

                return;
            }
        }
    }

    public function executeMyVendorSoftware($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $this->setTemplate('vendorSoftwareManage');

        $this->vendor = $this->getMyVendor();
        if (!$this->vendor) {
            $this->forward404();

            return;
        }

        $this->software = \Illuminate\Database\Capsule\Manager::table('registry_software')
            ->where('vendor_id', $this->vendor->id)->orderBy('name')->get();
    }

    public function executeMyVendorSoftwareAdd($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $this->setTemplate('vendorSoftwareForm');

        $vendor = $this->getMyVendor();
        if (!$vendor) {
            $this->forward404();

            return;
        }

        $this->vendor = $vendor;
        $this->software = null;
        $this->errors = [];

        if ($request->isMethod('post')) {
            $svc = $this->loadService('SoftwareService');

            $data = [
                'name' => trim($request->getParameter('name', '')),
                'vendor_id' => $vendor->id,
                'category' => $request->getParameter('category', 'other'),
                'description' => trim($request->getParameter('description', '')),
                'short_description' => trim($request->getParameter('short_description', '')),
                'website' => trim($request->getParameter('website', '')),
                'documentation_url' => trim($request->getParameter('documentation_url', '')),
                'install_url' => trim($request->getParameter('install_url', '')) ?: null,
                'git_provider' => $request->getParameter('git_provider', 'none'),
                'git_url' => trim($request->getParameter('git_url', '')),
                'git_default_branch' => trim($request->getParameter('git_default_branch', 'main')),
                'git_is_public' => $request->getParameter('git_is_public', 1) ? 1 : 0,
                'license' => trim($request->getParameter('license', '')),
                'pricing_model' => $request->getParameter('pricing_model', 'open_source'),
                'pricing_details' => trim($request->getParameter('pricing_details', '')),
                'created_by' => $this->getCurrentUserId(),
            ];

            // GLAM sectors (multi-select checkboxes → JSON)
            $glamSectors = $request->getParameter('glam_sectors', []);
            $data['glam_sectors'] = is_array($glamSectors) && !empty($glamSectors) ? json_encode(array_values($glamSectors)) : null;

            if ('' === $data['name']) {
                $this->errors[] = 'Software name is required.';
            }

            if (empty($this->errors)) {
                try {
                    $id = $svc->create($data);
                    $this->redirect(url_for(['module' => 'registry', 'action' => 'myVendorSoftware']));

                    return;
                } catch (\Throwable $e) {
                    $this->logError('Software add failed: ' . $e->getMessage(), $e);
                    $this->errors[] = 'An error occurred while adding the software.';
                }
            }

            $this->software = (object) $data;
        }
    }

    public function executeMyVendorSoftwareEdit($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $this->setTemplate('vendorSoftwareForm');

        $id = (int) $request->getParameter('id');
        $this->software = \Illuminate\Database\Capsule\Manager::table('registry_software')
            ->where('id', $id)->first();
        if (!$this->software) {
            $this->forward404();

            return;
        }

        $this->errors = [];

        if ($request->isMethod('post')) {
            $svc = $this->loadService('SoftwareService');

            $data = [
                'name' => trim($request->getParameter('name', '')),
                'category' => $request->getParameter('category', 'other'),
                'description' => trim($request->getParameter('description', '')),
                'short_description' => trim($request->getParameter('short_description', '')),
                'website' => trim($request->getParameter('website', '')),
                'documentation_url' => trim($request->getParameter('documentation_url', '')),
                'install_url' => trim($request->getParameter('install_url', '')) ?: null,
                'git_provider' => $request->getParameter('git_provider', 'none'),
                'git_url' => trim($request->getParameter('git_url', '')),
                'git_default_branch' => trim($request->getParameter('git_default_branch', 'main')),
                'git_is_public' => $request->getParameter('git_is_public', 1) ? 1 : 0,
                'license' => trim($request->getParameter('license', '')),
                'pricing_model' => $request->getParameter('pricing_model', 'open_source'),
                'pricing_details' => trim($request->getParameter('pricing_details', '')),
            ];

            // GLAM sectors (multi-select checkboxes → JSON)
            $glamSectors = $request->getParameter('glam_sectors', []);
            $data['glam_sectors'] = is_array($glamSectors) && !empty($glamSectors) ? json_encode(array_values($glamSectors)) : null;

            if ('' === $data['name']) {
                $this->errors[] = 'Software name is required.';
            }

            if (empty($this->errors)) {
                $svc->update($id, $data);
                $this->redirect(url_for(['module' => 'registry', 'action' => 'myVendorSoftware']));

                return;
            }

            $this->software = (object) array_merge((array) $this->software, $data);
        }
    }

    public function executeMyVendorSoftwareReleases($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $this->setTemplate('vendorReleaseManage');

        $id = (int) $request->getParameter('id');
        $this->software = \Illuminate\Database\Capsule\Manager::table('registry_software')
            ->where('id', $id)->first();
        if (!$this->software) {
            $this->forward404();

            return;
        }

        $svc = $this->loadService('SoftwareService');
        $this->releases = $svc->getReleases($id);
    }

    public function executeMyVendorSoftwareReleaseAdd($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $this->setTemplate('vendorReleaseForm');

        $softwareId = (int) $request->getParameter('id');
        $this->software = \Illuminate\Database\Capsule\Manager::table('registry_software')
            ->where('id', $softwareId)->first();
        if (!$this->software) {
            $this->forward404();

            return;
        }

        $this->errors = [];

        if ($request->isMethod('post')) {
            $svc = $this->loadService('SoftwareService');

            $data = [
                'version' => trim($request->getParameter('version', '')),
                'release_type' => $request->getParameter('release_type', 'patch'),
                'release_notes' => trim($request->getParameter('release_notes', '')),
                'git_tag' => trim($request->getParameter('git_tag', '')),
                'git_commit' => trim($request->getParameter('git_commit', '')),
                'is_stable' => $request->getParameter('is_stable', 1) ? 1 : 0,
                'released_at' => $request->getParameter('released_at', null) ?: date('Y-m-d H:i:s'),
            ];

            if ('' === $data['version']) {
                $this->errors[] = 'Version is required.';
            }

            if (empty($this->errors)) {
                $svc->createRelease($softwareId, $data);
                $this->redirect(url_for(['module' => 'registry', 'action' => 'myVendorSoftwareReleases', 'id' => $softwareId]));

                return;
            }
        }
    }

    public function executeMyVendorSoftwareUpload($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $this->setTemplate('vendorSoftwareUpload');

        $softwareId = (int) $request->getParameter('id');
        $this->software = \Illuminate\Database\Capsule\Manager::table('registry_software')
            ->where('id', $softwareId)->first();
        if (!$this->software) {
            $this->forward404();

            return;
        }

        $this->errors = [];

        if ($request->isMethod('post')) {
            $svc = $this->loadService('SoftwareService');

            if (isset($_FILES['package']) && 0 === $_FILES['package']['error']) {
                $result = $svc->handleUpload($softwareId, $_FILES['package']);
                if (isset($result['error'])) {
                    $this->errors[] = $result['error'];
                } else {
                    $this->redirect(url_for(['module' => 'registry', 'action' => 'myVendorSoftwareReleases', 'id' => $softwareId]));

                    return;
                }
            } else {
                $this->errors[] = 'Please select a file to upload.';
            }
        }
    }

    // ================================================================
    // SELF-SERVICE: Vendor Call/Issue Log
    // ================================================================

    public function executeMyVendorCallLog($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $vendor = $this->getMyVendor();
        if (!$vendor) {
            $this->forward404();

            return;
        }

        $this->vendor = $vendor;
        $db = \Illuminate\Database\Capsule\Manager::class;

        // Filters
        $status = $request->getParameter('status', '');
        $type = $request->getParameter('type', '');
        $priority = $request->getParameter('priority', '');

        $query = $db::table('registry_vendor_call_log')
            ->where('vendor_id', $vendor->id)
            ->orderBy('created_at', 'desc');

        if ('' !== $status) {
            $query->where('status', $status);
        }
        if ('' !== $type) {
            $query->where('interaction_type', $type);
        }
        if ('' !== $priority) {
            $query->where('priority', $priority);
        }

        $this->logs = $query->get();
        $this->filterStatus = $status;
        $this->filterType = $type;
        $this->filterPriority = $priority;

        // Stats
        $this->totalOpen = $db::table('registry_vendor_call_log')
            ->where('vendor_id', $vendor->id)
            ->whereIn('status', ['open', 'in_progress', 'escalated'])
            ->count();
        $this->totalResolved = $db::table('registry_vendor_call_log')
            ->where('vendor_id', $vendor->id)
            ->whereIn('status', ['resolved', 'closed'])
            ->count();
        $this->overdueFollowUps = $db::table('registry_vendor_call_log')
            ->where('vendor_id', $vendor->id)
            ->whereNotNull('follow_up_date')
            ->where('follow_up_date', '<', date('Y-m-d'))
            ->whereNotIn('status', ['resolved', 'closed'])
            ->count();
    }

    public function executeMyVendorCallLogAdd($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $this->setTemplate('vendorCallLogForm');

        $vendor = $this->getMyVendor();
        if (!$vendor) {
            $this->forward404();

            return;
        }

        $this->vendor = $vendor;
        $this->entry = null;
        $this->errors = [];
        $this->isEdit = false;

        $db = \Illuminate\Database\Capsule\Manager::class;
        $this->institutions = $db::table('registry_vendor_institution')
            ->join('registry_institution', 'registry_institution.id', '=', 'registry_vendor_institution.institution_id')
            ->where('registry_vendor_institution.vendor_id', $vendor->id)
            ->where('registry_vendor_institution.is_active', 1)
            ->select('registry_institution.id', 'registry_institution.name')
            ->orderBy('registry_institution.name')
            ->get();

        if ($request->isMethod('post')) {
            $data = $this->getCallLogFormData($request, $vendor->id);

            if ('' === $data['subject']) {
                $this->errors[] = 'Subject is required.';
            }

            if (empty($this->errors)) {
                $data['logged_by_user_id'] = $this->getCurrentUserId();
                $data['logged_by_name'] = $this->getCurrentUserName();
                $data['logged_by_email'] = $this->getCurrentUserEmail();
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');

                $db::table('registry_vendor_call_log')->insert($data);

                \sfContext::getInstance()->getUser()->setFlash('success', 'Call log entry created.');
                $this->redirect(url_for(['module' => 'registry', 'action' => 'myVendorCallLog']));

                return;
            }

            $this->entry = (object) $data;
        }
    }

    public function executeMyVendorCallLogEdit($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $this->setTemplate('vendorCallLogForm');

        $vendor = $this->getMyVendor();
        if (!$vendor) {
            $this->forward404();

            return;
        }

        $this->vendor = $vendor;
        $this->isEdit = true;
        $this->errors = [];

        $db = \Illuminate\Database\Capsule\Manager::class;
        $id = (int) $request->getParameter('id');
        $this->entry = $db::table('registry_vendor_call_log')
            ->where('id', $id)
            ->where('vendor_id', $vendor->id)
            ->first();

        if (!$this->entry) {
            $this->forward404();

            return;
        }

        $this->institutions = $db::table('registry_vendor_institution')
            ->join('registry_institution', 'registry_institution.id', '=', 'registry_vendor_institution.institution_id')
            ->where('registry_vendor_institution.vendor_id', $vendor->id)
            ->where('registry_vendor_institution.is_active', 1)
            ->select('registry_institution.id', 'registry_institution.name')
            ->orderBy('registry_institution.name')
            ->get();

        if ($request->isMethod('post')) {
            $data = $this->getCallLogFormData($request, $vendor->id);

            if ('' === $data['subject']) {
                $this->errors[] = 'Subject is required.';
            }

            if (empty($this->errors)) {
                $data['updated_at'] = date('Y-m-d H:i:s');

                // If status changed to resolved, set resolved fields
                if ('resolved' === $data['status'] || 'closed' === $data['status']) {
                    if (empty($this->entry->resolved_at)) {
                        $data['resolved_at'] = date('Y-m-d H:i:s');
                        $data['resolved_by'] = $this->getCurrentUserName();
                    }
                }

                $db::table('registry_vendor_call_log')->where('id', $id)->update($data);

                \sfContext::getInstance()->getUser()->setFlash('success', 'Call log entry updated.');
                $this->redirect(url_for(['module' => 'registry', 'action' => 'myVendorCallLog']));

                return;
            }

            $this->entry = (object) array_merge((array) $this->entry, $data);
        }
    }

    public function executeMyVendorCallLogView($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $vendor = $this->getMyVendor();
        if (!$vendor) {
            $this->forward404();

            return;
        }

        $this->vendor = $vendor;

        $db = \Illuminate\Database\Capsule\Manager::class;
        $id = (int) $request->getParameter('id');
        $this->entry = $db::table('registry_vendor_call_log')
            ->where('id', $id)
            ->where('vendor_id', $vendor->id)
            ->first();

        if (!$this->entry) {
            $this->forward404();

            return;
        }

        $this->institution = null;
        if (!empty($this->entry->institution_id)) {
            $this->institution = $db::table('registry_institution')
                ->where('id', $this->entry->institution_id)
                ->first();
        }
    }

    protected function getCallLogFormData($request, int $vendorId): array
    {
        $validTypes = ['call', 'email', 'meeting', 'support_ticket', 'site_visit', 'video_call', 'other'];
        $validDirections = ['inbound', 'outbound'];
        $validStatuses = ['open', 'in_progress', 'resolved', 'closed', 'escalated'];
        $validPriorities = ['low', 'medium', 'high', 'urgent'];

        $instId = (int) $request->getParameter('institution_id', 0);

        return [
            'vendor_id' => $vendorId,
            'institution_id' => $instId > 0 ? $instId : null,
            'interaction_type' => in_array($request->getParameter('interaction_type', ''), $validTypes) ? $request->getParameter('interaction_type') : 'call',
            'direction' => in_array($request->getParameter('direction', ''), $validDirections) ? $request->getParameter('direction') : 'outbound',
            'subject' => trim($request->getParameter('subject', '')),
            'description' => trim($request->getParameter('description', '')),
            'status' => in_array($request->getParameter('status', ''), $validStatuses) ? $request->getParameter('status') : 'open',
            'priority' => in_array($request->getParameter('priority', ''), $validPriorities) ? $request->getParameter('priority') : 'medium',
            'contact_name' => trim($request->getParameter('contact_name', '')),
            'contact_email' => trim($request->getParameter('contact_email', '')),
            'contact_phone' => trim($request->getParameter('contact_phone', '')),
            'resolution' => trim($request->getParameter('resolution', '')),
            'follow_up_date' => trim($request->getParameter('follow_up_date', '')) ?: null,
            'follow_up_notes' => trim($request->getParameter('follow_up_notes', '')),
            'duration_minutes' => (int) $request->getParameter('duration_minutes', 0) ?: null,
        ];
    }

    // ================================================================
    // SELF-SERVICE: Groups & Blog
    // ================================================================

    public function executeMyGroups($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $svc = $this->loadService('UserGroupService');
        $email = $this->getCurrentUserEmail();

        $this->myGroups = $svc->getMyGroups($email);
    }

    public function executeGroupCreate($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $this->errors = [];
        $this->group = null;

        if ($request->isMethod('post')) {
            $svc = $this->loadService('UserGroupService');

            $data = [
                'name' => trim($request->getParameter('name', '')),
                'description' => trim($request->getParameter('description', '')),
                'group_type' => $request->getParameter('group_type', 'regional'),
                'website' => trim($request->getParameter('website', '')),
                'email' => trim($request->getParameter('email', '')),
                'city' => trim($request->getParameter('city', '')),
                'country' => trim($request->getParameter('country', '')),
                'region' => trim($request->getParameter('region', '')),
                'is_virtual' => $request->getParameter('is_virtual', 0) ? 1 : 0,
                'meeting_frequency' => in_array((string) $request->getParameter('meeting_frequency', ''), ['weekly', 'biweekly', 'monthly', 'quarterly', 'annual', 'adhoc']) ? (string) $request->getParameter('meeting_frequency') : null,
                'meeting_format' => in_array((string) $request->getParameter('meeting_format', ''), ['in_person', 'virtual', 'hybrid']) ? (string) $request->getParameter('meeting_format') : null,
                'meeting_platform' => trim($request->getParameter('meeting_platform', '')),
                'mailing_list_url' => trim($request->getParameter('mailing_list_url', '')),
                'slack_url' => trim($request->getParameter('slack_url', '')),
                'discord_url' => trim($request->getParameter('discord_url', '')),
                'organizer_name' => $user->getAttribute('user_name', ''),
                'organizer_email' => $this->getCurrentUserEmail(),
                'created_by' => $this->getCurrentUserId(),
            ];

            if ('' === $data['name']) {
                $this->errors[] = 'Group name is required.';
            }

            if (empty($this->errors)) {
                try {
                    $id = $svc->create($data);

                    // Auto-join creator as organizer
                    $svc->join($data['name'], $this->getCurrentUserEmail(), $data['organizer_name'], $this->getCurrentUserId(), null);
                    \Illuminate\Database\Capsule\Manager::table('registry_user_group_member')
                        ->where('group_id', $id)
                        ->where('email', $this->getCurrentUserEmail())
                        ->update(['role' => 'organizer']);

                    $this->redirect(url_for(['module' => 'registry', 'action' => 'myGroups']));

                    return;
                } catch (\Throwable $e) {
                    $this->logError('Group create failed: ' . $e->getMessage(), $e);
                    $this->errors[] = 'An error occurred while creating the group.';
                }
            }

            $this->group = (object) $data;
        }
    }

    public function executeGroupEdit($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $id = (int) $request->getParameter('id');
        $this->group = \Illuminate\Database\Capsule\Manager::table('registry_user_group')
            ->where('id', $id)->first();
        if (!$this->group) {
            $this->forward404();

            return;
        }

        $this->errors = [];

        if ($request->isMethod('post')) {
            $svc = $this->loadService('UserGroupService');

            $data = [
                'name' => trim($request->getParameter('name', '')),
                'description' => trim($request->getParameter('description', '')),
                'group_type' => $request->getParameter('group_type', 'regional'),
                'website' => trim($request->getParameter('website', '')),
                'email' => trim($request->getParameter('email', '')),
                'city' => trim($request->getParameter('city', '')),
                'country' => trim($request->getParameter('country', '')),
                'region' => trim($request->getParameter('region', '')),
                'is_virtual' => $request->getParameter('is_virtual', 0) ? 1 : 0,
                'meeting_frequency' => in_array((string) $request->getParameter('meeting_frequency', ''), ['weekly', 'biweekly', 'monthly', 'quarterly', 'annual', 'adhoc']) ? (string) $request->getParameter('meeting_frequency') : null,
                'meeting_format' => in_array((string) $request->getParameter('meeting_format', ''), ['in_person', 'virtual', 'hybrid']) ? (string) $request->getParameter('meeting_format') : null,
                'meeting_platform' => trim($request->getParameter('meeting_platform', '')),
                'next_meeting_at' => $request->getParameter('next_meeting_at', null) ?: null,
                'next_meeting_details' => trim($request->getParameter('next_meeting_details', '')),
                'mailing_list_url' => trim($request->getParameter('mailing_list_url', '')),
                'slack_url' => trim($request->getParameter('slack_url', '')),
                'discord_url' => trim($request->getParameter('discord_url', '')),
            ];

            if ('' === $data['name']) {
                $this->errors[] = 'Group name is required.';
            }

            if (empty($this->errors)) {
                $svc->update($id, $data);
                $this->redirect(url_for(['module' => 'registry', 'action' => 'myGroups']));

                return;
            }

            $this->group = (object) array_merge((array) $this->group, $data);
        }
    }

    public function executeGroupMembersManage($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $id = (int) $request->getParameter('id');
        $this->group = \Illuminate\Database\Capsule\Manager::table('registry_user_group')
            ->where('id', $id)->first();
        if (!$this->group) {
            $this->forward404();

            return;
        }

        $svc = $this->loadService('UserGroupService');
        $this->members = $svc->getMembers($id);

        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action', '');
            $email = $request->getParameter('member_email', '');
            $role = $request->getParameter('member_role', 'member');

            if ('update_role' === $action && $email) {
                $svc->updateMemberRole($id, $email, $role);
            } elseif ('remove' === $action && $email) {
                $svc->leave($this->group->slug, $email);
            }

            $this->redirect(url_for(['module' => 'registry', 'action' => 'groupMembersManage', 'id' => $id]));

            return;
        }
    }

    public function executeMyBlog($request)
    {
        $admin = $this->requireAdminUser();
        if (!$admin) {
            return;
        }

        // Admin sees all blog posts
        $this->posts = \Illuminate\Database\Capsule\Manager::table('registry_blog_post')
            ->orderByDesc('created_at')
            ->get();
    }

    public function executeBlogNew($request)
    {
        $user = $this->requireAdminUser();
        if (!$user) {
            return;
        }

        $this->errors = [];
        $this->post = null;

        if ($request->isMethod('post')) {
            $svc = $this->loadService('BlogService');

            $data = [
                'title' => trim($request->getParameter('title', '')),
                'content' => trim($request->getParameter('content', '')),
                'excerpt' => trim($request->getParameter('excerpt', '')),
                'author_type' => $request->getParameter('author_type', 'admin'),
                'author_name' => $user->getAttribute('user_name', $this->getCurrentUserEmail()),
                'category' => $request->getParameter('category', 'news'),
                'comments_enabled' => (int) $request->getParameter('comments_enabled', 1),
                'status' => 'draft',
            ];

            // Determine author_id based on type
            $userId = $this->getCurrentUserId();
            if ('vendor' === $data['author_type']) {
                $vendor = \Illuminate\Database\Capsule\Manager::table('registry_vendor')
                    ->where('created_by', $userId)->first();
                $data['author_id'] = $vendor ? $vendor->id : null;
            } elseif ('institution' === $data['author_type']) {
                $inst = \Illuminate\Database\Capsule\Manager::table('registry_institution')
                    ->where('created_by', $userId)->first();
                $data['author_id'] = $inst ? $inst->id : null;
            }

            if ('' === $data['title']) {
                $this->errors[] = 'Title is required.';
            }
            if ('' === $data['content']) {
                $this->errors[] = 'Content is required.';
            }

            if (empty($this->errors)) {
                try {
                    $id = $svc->create($data);
                    $this->redirect(url_for(['module' => 'registry', 'action' => 'myBlog']));

                    return;
                } catch (\Throwable $e) {
                    $this->logError('Blog create failed: ' . $e->getMessage(), $e);
                    $this->errors[] = 'An error occurred while creating the blog post.';
                }
            }

            $this->post = (object) $data;
        }
    }

    public function executeBlogEdit($request)
    {
        $user = $this->requireAdminUser();
        if (!$user) {
            return;
        }

        $id = (int) $request->getParameter('id');
        $this->post = \Illuminate\Database\Capsule\Manager::table('registry_blog_post')
            ->where('id', $id)->first();
        if (!$this->post) {
            $this->forward404();

            return;
        }

        $this->errors = [];

        if ($request->isMethod('post')) {
            $svc = $this->loadService('BlogService');

            $data = [
                'title' => trim($request->getParameter('title', '')),
                'content' => trim($request->getParameter('content', '')),
                'excerpt' => trim($request->getParameter('excerpt', '')),
                'category' => $request->getParameter('category', 'news'),
                'comments_enabled' => (int) $request->getParameter('comments_enabled', 0),
            ];

            if ('' === $data['title']) {
                $this->errors[] = 'Title is required.';
            }

            if (empty($this->errors)) {
                try {
                    $svc->update($id, $data);
                    $this->redirect(url_for(['module' => 'registry', 'action' => 'myBlog']));

                    return;
                } catch (\Throwable $e) {
                    $this->logError('Blog update failed (id=' . $id . '): ' . $e->getMessage(), $e);
                    $this->errors[] = 'An error occurred while saving the blog post.';
                }
            }

            $this->post = (object) array_merge((array) $this->post, $data);
        }
    }

    // ================================================================
    // ADMIN
    // ================================================================

    public function executeAdminDashboard($request)
    {
        $admin = $this->requireAdminUser();
        if (!$admin) {
            return;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;

        $this->stats = [
            'institutions' => $db::table('registry_institution')->count(),
            'institutions_pending' => $db::table('registry_institution')->where('is_verified', 0)->count(),
            'vendors' => $db::table('registry_vendor')->count(),
            'vendors_pending' => $db::table('registry_vendor')->where('is_verified', 0)->count(),
            'software' => $db::table('registry_software')->count(),
            'instances' => $db::table('registry_instance')->count(),
            'instances_online' => $db::table('registry_instance')->where('status', 'online')->count(),
            'groups' => $db::table('registry_user_group')->count(),
            'discussions' => $db::table('registry_discussion')->count(),
            'blog_posts' => $db::table('registry_blog_post')->count(),
            'blog_pending' => $db::table('registry_blog_post')->where('status', 'pending_review')->count(),
            'reviews' => $db::table('registry_review')->count(),
            'users_pending' => $db::table('user')->where('active', 0)->count(),
            'users_total' => $db::table('user')->count(),
        ];
    }

    public function executeAdminInstitutions($request)
    {
        $admin = $this->requireAdminUser();
        if (!$admin) {
            return;
        }

        $svc = $this->loadService('InstitutionService');
        $this->result = $svc->browse([
            'page' => max(1, (int) $request->getParameter('page', 1)),
            'limit' => 50,
            'search' => $request->getParameter('q', ''),
            'sort' => $request->getParameter('sort', 'created_at'),
            'direction' => 'desc',
            'include_inactive' => true,
        ]);
    }

    public function executeAdminInstitutionVerify($request)
    {
        $admin = $this->requireAdminUser();
        if (!$admin) {
            return;
        }

        $id = (int) $request->getParameter('id');
        $svc = $this->loadService('InstitutionService');

        $action = $request->getParameter('form_action', 'verify');
        $notes = trim($request->getParameter('notes', ''));

        if ('verify' === $action) {
            $svc->verify($id, $this->getCurrentUserId(), $notes);
        } elseif ('unverify' === $action) {
            $svc->update($id, ['is_verified' => 0, 'verified_at' => null, 'verified_by' => null]);
        } elseif ('feature' === $action) {
            $svc->toggleFeatured($id);
        } elseif ('suspend' === $action) {
            $svc->update($id, ['is_active' => 0]);
        } elseif ('activate' === $action) {
            $svc->update($id, ['is_active' => 1]);
        } elseif ('delete' === $action) {
            $svc->delete($id);
        }

        $this->redirect(url_for(['module' => 'registry', 'action' => 'adminInstitutions']));
    }

    public function executeAdminVendors($request)
    {
        $admin = $this->requireAdminUser();
        if (!$admin) {
            return;
        }

        $svc = $this->loadService('VendorService');
        $this->result = $svc->browse([
            'page' => max(1, (int) $request->getParameter('page', 1)),
            'limit' => 50,
            'search' => $request->getParameter('q', ''),
            'sort' => $request->getParameter('sort', 'created_at'),
            'direction' => 'desc',
        ]);
    }

    public function executeAdminVendorVerify($request)
    {
        $admin = $this->requireAdminUser();
        if (!$admin) {
            return;
        }

        $id = (int) $request->getParameter('id');
        $svc = $this->loadService('VendorService');

        $action = $request->getParameter('form_action', 'verify');
        $notes = trim($request->getParameter('notes', ''));

        if ('verify' === $action) {
            $svc->verify($id, $this->getCurrentUserId(), $notes);
        } elseif ('unverify' === $action) {
            $svc->update($id, ['is_verified' => 0, 'verified_at' => null, 'verified_by' => null]);
        } elseif ('feature' === $action) {
            $svc->toggleFeatured($id);
        } elseif ('suspend' === $action) {
            $svc->update($id, ['is_active' => 0]);
        } elseif ('activate' === $action) {
            $svc->update($id, ['is_active' => 1]);
        } elseif ('delete' === $action) {
            $svc->delete($id);
        }

        $this->redirect(url_for(['module' => 'registry', 'action' => 'adminVendors']));
    }

    public function executeAdminSoftware($request)
    {
        $admin = $this->requireAdminUser();
        if (!$admin) {
            return;
        }

        $svc = $this->loadService('SoftwareService');
        $this->result = $svc->browse([
            'page' => max(1, (int) $request->getParameter('page', 1)),
            'limit' => 50,
            'search' => $request->getParameter('q', ''),
            'sort' => 'created_at',
            'direction' => 'desc',
        ]);
    }

    public function executeAdminSoftwareVerify($request)
    {
        $admin = $this->requireAdminUser();
        if (!$admin) {
            return;
        }

        $id = (int) $request->getParameter('id');
        $svc = $this->loadService('SoftwareService');

        $action = $request->getParameter('form_action', 'verify');
        $notes = trim($request->getParameter('notes', ''));

        if ('verify' === $action) {
            $svc->verify($id, $this->getCurrentUserId(), $notes);
        } elseif ('unverify' === $action) {
            $svc->update($id, ['is_verified' => 0, 'verified_at' => null, 'verified_by' => null]);
        } elseif ('feature' === $action) {
            $svc->toggleFeatured($id);
        } elseif ('suspend' === $action) {
            $svc->update($id, ['is_active' => 0]);
        } elseif ('activate' === $action) {
            $svc->update($id, ['is_active' => 1]);
        } elseif ('delete' === $action) {
            $svc->delete($id);
        }

        $this->redirect(url_for(['module' => 'registry', 'action' => 'adminSoftware']));
    }

    public function executeAdminGroups($request)
    {
        $admin = $this->requireAdminUser();
        if (!$admin) {
            return;
        }

        $svc = $this->loadService('UserGroupService');
        $this->result = $svc->browse([
            'page' => max(1, (int) $request->getParameter('page', 1)),
            'limit' => 50,
            'search' => $request->getParameter('q', ''),
            'sort' => 'created_at',
            'direction' => 'desc',
            'admin_mode' => true,
        ]);
    }

    public function executeAdminGroupEdit($request)
    {
        $admin = $this->requireAdminUser();
        if (!$admin) {
            return;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $id = (int) $request->getParameter('id');
        $this->group = $db::table('registry_user_group')->where('id', $id)->first();
        if (!$this->group) {
            $this->forward404();
            return;
        }

        $this->errors = [];
        $this->success = null;

        if ($request->isMethod('post')) {
            $svc = $this->loadService('UserGroupService');

            $data = [
                'name' => trim($request->getParameter('name', '')),
                'description' => trim($request->getParameter('description', '')),
                'group_type' => $request->getParameter('group_type', 'regional'),
                'website' => trim($request->getParameter('website', '')),
                'email' => trim($request->getParameter('email', '')),
                'city' => trim($request->getParameter('city', '')),
                'country' => trim($request->getParameter('country', '')),
                'region' => trim($request->getParameter('region', '')),
                'is_virtual' => $request->getParameter('is_virtual', 0) ? 1 : 0,
                'meeting_frequency' => in_array((string) $request->getParameter('meeting_frequency', ''), ['weekly', 'biweekly', 'monthly', 'quarterly', 'annual', 'adhoc']) ? (string) $request->getParameter('meeting_frequency') : null,
                'meeting_format' => in_array((string) $request->getParameter('meeting_format', ''), ['in_person', 'virtual', 'hybrid']) ? (string) $request->getParameter('meeting_format') : null,
                'meeting_platform' => trim($request->getParameter('meeting_platform', '')),
                'next_meeting_at' => $request->getParameter('next_meeting_at', null) ?: null,
                'next_meeting_details' => trim($request->getParameter('next_meeting_details', '')),
                'mailing_list_url' => trim($request->getParameter('mailing_list_url', '')),
                'slack_url' => trim($request->getParameter('slack_url', '')),
                'discord_url' => trim($request->getParameter('discord_url', '')),
                'organizer_name' => trim($request->getParameter('organizer_name', '')),
                'organizer_email' => trim($request->getParameter('organizer_email', '')),
                // Admin-only fields
                'is_active' => $request->getParameter('is_active', 0) ? 1 : 0,
                'is_verified' => $request->getParameter('is_verified', 0) ? 1 : 0,
                'is_featured' => $request->getParameter('is_featured', 0) ? 1 : 0,
            ];

            // Handle focus_areas
            $focusRaw = trim($request->getParameter('focus_areas', ''));
            if (!empty($focusRaw)) {
                $data['focus_areas'] = json_encode(array_map('trim', explode(',', $focusRaw)));
            } else {
                $data['focus_areas'] = null;
            }

            if ('' === $data['name']) {
                $this->errors[] = 'Group name is required.';
            }

            if (empty($this->errors)) {
                $svc->update($id, $data);
                $this->success = 'Group updated successfully.';
                $this->group = $db::table('registry_user_group')->where('id', $id)->first();
            } else {
                $this->group = (object) array_merge((array) $this->group, $data);
            }
        }
    }

    public function executeAdminGroupMembers($request)
    {
        $admin = $this->requireAdminUser();
        if (!$admin) {
            return;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $id = (int) $request->getParameter('id');
        $this->group = $db::table('registry_user_group')->where('id', $id)->first();
        if (!$this->group) {
            $this->forward404();
            return;
        }

        $svc = $this->loadService('UserGroupService');
        $this->success = null;
        $this->error = null;

        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action', '');
            $memberId = (int) $request->getParameter('member_id', 0);

            if ('update_role' === $action && $memberId) {
                $role = $request->getParameter('member_role', 'member');
                $member = $db::table('registry_user_group_member')->where('id', $memberId)->first();
                if ($member) {
                    $svc->updateMemberRole($id, $member->email, $role);
                    $this->success = 'Role updated.';
                }
            } elseif ('toggle_active' === $action && $memberId) {
                $svc->toggleMemberActive($memberId);
                $this->success = 'Member status updated.';
            } elseif ('remove' === $action && $memberId) {
                $svc->removeMember($memberId);
                $this->success = 'Member removed.';
            } elseif ('add' === $action) {
                $email = strtolower(trim($request->getParameter('new_email', '')));
                $name = trim($request->getParameter('new_name', ''));
                $role = $request->getParameter('new_role', 'member');
                if (!empty($email)) {
                    $result = $svc->join($this->group->slug, $email, $name ?: null);
                    if ($result['success']) {
                        if ('member' !== $role) {
                            $svc->updateMemberRole($id, $email, $role);
                        }
                        $this->success = 'Member added.';
                    } else {
                        $this->error = $result['error'] ?? 'Failed to add member.';
                    }
                } else {
                    $this->error = 'Email is required.';
                }
            }
        }

        $this->members = $svc->getAllMembers($id, [
            'page' => max(1, (int) $request->getParameter('page', 1)),
            'limit' => 50,
            'search' => $request->getParameter('q', ''),
            'is_active' => $request->getParameter('status', ''),
        ]);
    }

    public function executeAdminGroupEmail($request)
    {
        $admin = $this->requireAdminUser();
        if (!$admin) {
            return;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $id = (int) $request->getParameter('id');
        $group = $db::table('registry_user_group')->where('id', $id)->first();
        if (!$group) {
            $this->forward404();
            return;
        }

        $subject = trim($request->getParameter('email_subject', ''));
        $body = trim($request->getParameter('email_body', ''));

        if (empty($subject) || empty($body)) {
            \sfContext::getInstance()->getUser()->setFlash('error', 'Subject and body are required.');
            $this->redirect('/registry/admin/groups/' . $id . '/members');
            return;
        }

        // Get active members
        $members = $db::table('registry_user_group_member')
            ->where('group_id', $id)
            ->where('is_active', 1)
            ->get()->all();

        // Load SMTP settings from registry_settings
        $nlSvc = $this->loadService('NewsletterService');
        $smtp = $nlSvc->getSmtpSettings();

        $sent = 0;
        $failed = 0;

        foreach ($members as $member) {
            $success = $this->sendGroupEmail($smtp, $member->email, $subject, $body, $group->name);
            if ($success) {
                $sent++;
            } else {
                $failed++;
            }
        }

        \sfContext::getInstance()->getUser()->setFlash('success',
            "Email sent to {$sent} members" . ($failed > 0 ? " ({$failed} failed)" : '') . '.');
        $this->redirect('/registry/admin/groups/' . $id . '/members');
    }

    protected function sendGroupEmail(array $smtp, string $to, string $subject, string $body, string $groupName): bool
    {
        $htmlBody = '<div style="font-family:sans-serif;max-width:600px;margin:0 auto;">'
            . '<h2 style="color:#2563eb;">' . htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8') . '</h2>'
            . '<hr>'
            . nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'))
            . '<hr style="margin-top:30px;">'
            . '<p style="font-size:12px;color:#999;">You received this because you are a member of ' . htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8') . '.</p>'
            . '</div>';

        if (!empty($smtp['smtp_enabled']) && class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            try {
                require_once \sfConfig::get('sf_root_dir') . '/atom-framework/vendor/autoload.php';
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $smtp['smtp_host'];
                $mail->Port = (int) $smtp['smtp_port'];
                $mail->SMTPAuth = true;
                $mail->Username = $smtp['smtp_username'];
                $mail->Password = $smtp['smtp_password'];
                if ('tls' === $smtp['smtp_encryption']) {
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                } elseif ('ssl' === $smtp['smtp_encryption']) {
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                }
                $mail->setFrom(
                    $smtp['smtp_from_email'] ?: 'noreply@theahg.co.za',
                    $smtp['smtp_from_name'] ?: 'AtoM Registry'
                );
                $mail->addAddress($to);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $htmlBody;
                $mail->send();
                return true;
            } catch (\Exception $e) {
                error_log('Registry group email error: ' . $e->getMessage());
                return false;
            }
        }

        $fromEmail = $smtp['smtp_from_email'] ?: 'noreply@theahg.co.za';
        $fromName = $smtp['smtp_from_name'] ?: 'AtoM Registry';
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $fromName . ' <' . $fromEmail . '>',
        ];

        return @mail($to, $subject, $htmlBody, implode("\r\n", $headers));
    }

    protected function notifyGroupMembers(object $group, string $title, string $content, string $authorName, int $discussionId, ?string $excludeEmail = null): void
    {
        $db = \Illuminate\Database\Capsule\Manager::class;

        // Get members with email notifications enabled
        $members = $db::table('registry_user_group_member')
            ->where('group_id', $group->id)
            ->where('is_active', 1)
            ->where('email_notifications', 1)
            ->get()->all();

        if (empty($members)) {
            return;
        }

        $nlSvc = $this->loadService('NewsletterService');
        $smtp = $nlSvc->getSmtpSettings();

        $baseUrl = \sfConfig::get('app_registry_base_url', '');
        $viewUrl = $baseUrl . '/registry/groups/' . urlencode($group->slug) . '/discussions/' . $discussionId;
        $groupUrl = $baseUrl . '/registry/groups/' . urlencode($group->slug);

        $preview = htmlspecialchars(mb_substr(strip_tags($content), 0, 300), ENT_QUOTES, 'UTF-8');

        $htmlBody = '<div style="font-family:sans-serif;max-width:600px;margin:0 auto;">'
            . '<p style="color:#6c757d;font-size:12px;margin-bottom:4px;">'
            . htmlspecialchars($group->name, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<h2 style="color:#2563eb;margin-top:0;">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>'
            . '<p style="color:#666;">by <strong>' . htmlspecialchars($authorName ?: 'Anonymous', ENT_QUOTES, 'UTF-8') . '</strong></p>'
            . '<div style="background:#f8f9fa;padding:16px;border-radius:8px;margin:16px 0;">' . $preview . '</div>'
            . '<p><a href="' . htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8') . '" style="background:#2563eb;color:#fff;padding:10px 20px;text-decoration:none;border-radius:6px;display:inline-block;">View Discussion</a></p>'
            . '<hr style="margin-top:30px;">'
            . '<p style="font-size:12px;color:#999;">You received this because you are subscribed to email notifications for '
            . '<a href="' . htmlspecialchars($groupUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($group->name, ENT_QUOTES, 'UTF-8') . '</a>.'
            . ' You can disable notifications in your group settings.</p>'
            . '</div>';

        $subject = '[' . $group->name . '] ' . $title;

        foreach ($members as $member) {
            // Skip the author to avoid notifying themselves
            if ($excludeEmail && strtolower($member->email) === strtolower($excludeEmail)) {
                continue;
            }

            $this->sendDiscussionNotification($smtp, $member->email, $subject, $htmlBody);
        }
    }

    protected function sendDiscussionNotification(array $smtp, string $to, string $subject, string $htmlBody): bool
    {
        if (!empty($smtp['smtp_enabled']) && class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            try {
                require_once \sfConfig::get('sf_root_dir') . '/atom-framework/vendor/autoload.php';
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $smtp['smtp_host'];
                $mail->Port = (int) $smtp['smtp_port'];
                $mail->SMTPAuth = true;
                $mail->Username = $smtp['smtp_username'];
                $mail->Password = $smtp['smtp_password'];
                if ('tls' === $smtp['smtp_encryption']) {
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                } elseif ('ssl' === $smtp['smtp_encryption']) {
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                }
                $mail->setFrom(
                    $smtp['smtp_from_email'] ?: 'noreply@theahg.co.za',
                    $smtp['smtp_from_name'] ?: 'AtoM Registry'
                );
                $mail->addAddress($to);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $htmlBody;
                $mail->send();
                return true;
            } catch (\Exception $e) {
                error_log('Registry discussion notification error: ' . $e->getMessage());
                return false;
            }
        }

        $fromEmail = $smtp['smtp_from_email'] ?: 'noreply@theahg.co.za';
        $fromName = $smtp['smtp_from_name'] ?: 'AtoM Registry';
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $fromName . ' <' . $fromEmail . '>',
        ];

        return @mail($to, $subject, $htmlBody, implode("\r\n", $headers));
    }

    public function executeAdminGroupVerify($request)
    {
        $admin = $this->requireAdminUser();
        if (!$admin) {
            return;
        }

        if (!$request->isMethod('post')) {
            $this->redirect('/registry/admin/groups');
            return;
        }

        $id = (int) $request->getParameter('id');
        $svc = $this->loadService('UserGroupService');

        $action = $request->getParameter('form_action', 'verify');
        $notes = trim($request->getParameter('notes', ''));

        if ('verify' === $action) {
            $svc->verify($id, $this->getCurrentUserId(), $notes);
        } elseif ('unverify' === $action) {
            $svc->update($id, ['is_verified' => 0, 'verified_at' => null, 'verified_by' => null]);
        } elseif ('feature' === $action) {
            $svc->toggleFeatured($id);
        } elseif ('suspend' === $action) {
            $svc->update($id, ['is_active' => 0]);
        } elseif ('activate' === $action) {
            $svc->update($id, ['is_active' => 1]);
        } elseif ('delete' === $action) {
            $svc->delete($id);
        }

        $this->redirect(url_for(['module' => 'registry', 'action' => 'adminGroups']));
    }

    public function executeAdminDiscussions($request)
    {
        $admin = $this->requireAdminUser();
        if (!$admin) {
            return;
        }

        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $query = \Illuminate\Database\Capsule\Manager::table('registry_discussion')
            ->leftJoin('registry_user_group', 'registry_discussion.group_id', '=', 'registry_user_group.id')
            ->select('registry_discussion.*', 'registry_user_group.name as group_name');

        $status = $request->getParameter('status', '');
        if ($status) {
            $query->where('registry_discussion.status', $status);
        }

        $this->total = (clone $query)->count();
        $this->discussions = $query->orderByDesc('registry_discussion.created_at')
            ->offset($offset)->limit($limit)->get();
        $this->page = $page;

        if ($request->isMethod('post')) {
            $discId = (int) $request->getParameter('discussion_id');
            $action = $request->getParameter('form_action', '');
            $discSvc = $this->loadService('DiscussionService');

            if ('hide' === $action) {
                $discSvc->update($discId, ['status' => 'hidden']);
            } elseif ('spam' === $action) {
                $discSvc->update($discId, ['status' => 'spam']);
            } elseif ('activate' === $action) {
                $discSvc->update($discId, ['status' => 'active']);
            } elseif ('lock' === $action) {
                $discSvc->lock($discId);
            } elseif ('pin' === $action) {
                $discSvc->pin($discId);
            }

            $this->redirect(url_for(['module' => 'registry', 'action' => 'adminDiscussions']));

            return;
        }
    }

    public function executeAdminBlog($request)
    {
        $admin = $this->requireAdminUser();
        if (!$admin) {
            return;
        }

        $svc = $this->loadService('BlogService');
        $this->result = $svc->browse([
            'page' => max(1, (int) $request->getParameter('page', 1)),
            'limit' => 50,
            'search' => $request->getParameter('q', ''),
            'sort' => 'created_at',
            'direction' => 'desc',
        ]);

        if ($request->isMethod('post')) {
            $postId = (int) $request->getParameter('post_id');
            $action = $request->getParameter('form_action', '');

            if ('publish' === $action) {
                $svc->publish($postId);
            } elseif ('archive' === $action) {
                $svc->archive($postId);
            } elseif ('feature' === $action) {
                $svc->toggleFeatured($postId);
            } elseif ('pin' === $action) {
                $svc->togglePinned($postId);
            }

            $this->redirect(url_for(['module' => 'registry', 'action' => 'adminBlog']));

            return;
        }
    }

    public function executeAdminReviews($request)
    {
        $admin = $this->requireAdminUser();
        if (!$admin) {
            return;
        }

        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $this->total = \Illuminate\Database\Capsule\Manager::table('registry_review')->count();
        $this->reviews = \Illuminate\Database\Capsule\Manager::table('registry_review')
            ->orderByDesc('created_at')
            ->offset($offset)->limit($limit)->get();
        $this->page = $page;

        if ($request->isMethod('post')) {
            $reviewId = (int) $request->getParameter('review_id');
            $action = $request->getParameter('form_action', '');
            $svc = $this->loadService('ReviewService');

            if ('toggle_visibility' === $action) {
                $svc->toggleVisibility($reviewId);
            } elseif ('delete' === $action) {
                $svc->delete($reviewId);
            }

            $this->redirect(url_for(['module' => 'registry', 'action' => 'adminReviews']));

            return;
        }
    }

    public function executeAdminSync($request)
    {
        $admin = $this->requireAdminUser();
        if (!$admin) {
            return;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;

        $this->instances = $db::table('registry_instance')
            ->leftJoin('registry_institution', 'registry_instance.institution_id', '=', 'registry_institution.id')
            ->select('registry_instance.*', 'registry_institution.name as institution_name')
            ->where('registry_instance.sync_enabled', 1)
            ->orderByDesc('registry_instance.last_heartbeat_at')
            ->get();

        $this->recentLogs = $db::table('registry_sync_log')
            ->leftJoin('registry_instance', 'registry_sync_log.instance_id', '=', 'registry_instance.id')
            ->select('registry_sync_log.*', 'registry_instance.name as instance_name')
            ->orderByDesc('registry_sync_log.created_at')
            ->limit(50)
            ->get();
    }

    public function executeAdminSettings($request)
    {
        $admin = $this->requireAdminUser();
        if (!$admin) {
            return;
        }

        $this->settings = \Illuminate\Database\Capsule\Manager::table('registry_settings')
            ->orderBy('setting_key')->get();

        $this->saved = false;

        if ($request->isMethod('post')) {
            foreach ($this->settings as $setting) {
                $value = $request->getParameter('setting_' . $setting->setting_key, $setting->setting_value);
                \Illuminate\Database\Capsule\Manager::table('registry_settings')
                    ->where('id', $setting->id)
                    ->update(['setting_value' => $value]);
            }
            $this->saved = true;
            $this->settings = \Illuminate\Database\Capsule\Manager::table('registry_settings')
                ->orderBy('setting_key')->get();
        }
    }

    public function executeAdminEmail($request)
    {
        $admin = $this->requireAdminUser();
        if (!$admin) {
            return;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $smtpKeys = ['smtp_enabled', 'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password', 'smtp_from_email', 'smtp_from_name'];

        $this->saved = false;
        $this->testResult = null;

        if ($request->isMethod('post')) {
            $formAction = $request->getParameter('form_action', 'save');

            if ('test' === $formAction) {
                // Send test email
                $testEmail = trim($request->getParameter('test_email', ''));
                if (empty($testEmail)) {
                    $this->testResult = ['success' => false, 'error' => 'Please enter a test email address.'];
                } else {
                    $settings = [];
                    foreach ($smtpKeys as $key) {
                        $row = $db::table('registry_settings')->where('setting_key', $key)->first();
                        $settings[$key] = $row ? $row->setting_value : '';
                    }
                    $this->testResult = $this->sendTestEmail($settings, $testEmail);
                }
            } else {
                // Save settings
                foreach ($smtpKeys as $key) {
                    $value = $request->getParameter($key, '');
                    $db::table('registry_settings')->where('setting_key', $key)->update(['setting_value' => $value]);
                }
                $this->saved = true;
            }
        }

        // Load current settings
        $this->emailSettings = [];
        foreach ($smtpKeys as $key) {
            $row = $db::table('registry_settings')->where('setting_key', $key)->first();
            $this->emailSettings[$key] = $row ? $row->setting_value : '';
        }
    }

    protected function sendTestEmail(array $settings, string $to): array
    {
        if (empty($settings['smtp_enabled'])) {
            return ['success' => false, 'error' => 'SMTP is disabled.'];
        }

        $subject = 'AtoM Registry - Test Email';
        $body = '<h2>Test Email</h2><p>This is a test email from the AtoM Registry.</p><p>If you received this, your email settings are configured correctly.</p><p><small>Sent at: ' . date('Y-m-d H:i:s') . '</small></p>';

        // Try PHPMailer first
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $settings['smtp_host'];
                $mail->Port = (int) $settings['smtp_port'];
                $mail->SMTPAuth = true;
                $mail->Username = $settings['smtp_username'];
                $mail->Password = $settings['smtp_password'];
                if ('tls' === $settings['smtp_encryption']) {
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                } elseif ('ssl' === $settings['smtp_encryption']) {
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                }
                $mail->setFrom($settings['smtp_from_email'], $settings['smtp_from_name']);
                $mail->addAddress($to);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $body;
                $mail->send();

                return ['success' => true];
            } catch (\Exception $e) {
                return ['success' => false, 'error' => $e->getMessage()];
            }
        }

        // Fallback to mail()
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $settings['smtp_from_name'] . ' <' . $settings['smtp_from_email'] . '>',
        ];

        $sent = @mail($to, $subject, $body, implode("\r\n", $headers));

        return $sent ? ['success' => true] : ['success' => false, 'error' => 'mail() returned false. Check server mail configuration.'];
    }

    // ================================================================
    // ADMIN: Footer Settings
    // ================================================================

    public function executeAdminFooter($request)
    {
        $admin = $this->requireAdminUser();
        if (!$admin) {
            return;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $footerKeys = ['footer_description', 'footer_copyright', 'footer_columns'];

        $this->saved = false;
        $this->errors = [];

        if ($request->isMethod('post')) {
            // Build columns JSON from posted form data
            $columns = [];
            for ($i = 0; $i < 4; ++$i) {
                $title = trim($request->getParameter('col_' . $i . '_title', ''));
                $labels = $request->getParameter('col_' . $i . '_label', []);
                $urls = $request->getParameter('col_' . $i . '_url', []);

                if ('' === $title) {
                    continue;
                }

                $links = [];
                if (is_array($labels) && is_array($urls)) {
                    $count = min(count($labels), count($urls));
                    for ($j = 0; $j < $count; ++$j) {
                        $label = trim($labels[$j] ?? '');
                        $url = trim($urls[$j] ?? '');
                        if ('' !== $label && '' !== $url) {
                            $links[] = ['label' => $label, 'url' => $url];
                        }
                    }
                }

                $columns[] = ['title' => $title, 'links' => $links];
            }

            $cleanJson = json_encode($columns, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $values = [
                'footer_description' => $request->getParameter('footer_description', ''),
                'footer_copyright'   => $request->getParameter('footer_copyright', ''),
                'footer_columns'     => $cleanJson,
            ];

            foreach ($values as $key => $value) {
                $exists = $db::table('registry_settings')->where('setting_key', $key)->first();
                if ($exists) {
                    $db::table('registry_settings')
                        ->where('setting_key', $key)
                        ->update(['setting_value' => $value]);
                } else {
                    $type = ('footer_columns' === $key) ? 'json' : 'text';
                    $db::table('registry_settings')->insert([
                        'setting_key'   => $key,
                        'setting_value' => $value,
                        'setting_type'  => $type,
                        'description'   => 'Footer ' . str_replace('footer_', '', $key),
                    ]);
                }
            }

            $this->saved = true;
        }

        // Load current footer settings
        $this->footerSettings = [];
        foreach ($footerKeys as $key) {
            $row = $db::table('registry_settings')->where('setting_key', $key)->first();
            $this->footerSettings[$key] = $row ? $row->setting_value : '';
        }

        // Decode columns for template
        $this->footerColumns = json_decode($this->footerSettings['footer_columns'] ?: '[]', true) ?: [];
    }

    public function executeAdminImport($request)
    {
        $admin = $this->requireAdminUser();
        if (!$admin) {
            return;
        }

        $this->preview = null;
        $this->imported = false;
        $this->errors = [];

        if ($request->isMethod('post')) {
            $svc = $this->loadService('RegistryImportService');
            $jsonData = trim($request->getParameter('import_data', ''));
            $action = $request->getParameter('form_action', 'preview');

            if ('' === $jsonData) {
                $this->errors[] = 'Please paste the WordPress export JSON data.';
            } else {
                $data = json_decode($jsonData, true);
                if (!$data) {
                    $this->errors[] = 'Invalid JSON data.';
                } elseif ('preview' === $action) {
                    $this->preview = $svc->preview($data);
                } elseif ('import' === $action) {
                    $result = $svc->execute($data);
                    $this->imported = true;
                    $this->importResult = $result;
                }
            }
        }
    }

    // ================================================================
    // ADMIN: User Approval
    // ================================================================

    public function executeAdminUsers($request)
    {
        $admin = $this->requireAdminUser();
        if (!$admin) {
            return;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;

        // Handle approve/reject actions
        if ($request->isMethod('post')) {
            $userId = (int) $request->getParameter('user_id');
            $formAction = $request->getParameter('form_action', '');

            try {
                if ($userId > 0 && 'approve' === $formAction) {
                    $db::table('user')->where('id', $userId)->update(['active' => 1]);

                    // Optionally assign administrator group
                    if ($request->getParameter('make_admin')) {
                        $adminGroupId = \AtomExtensions\Constants\AclConstants::ADMINISTRATOR_ID;
                        $exists = $db::table('acl_user_group')
                            ->where('user_id', $userId)
                            ->where('group_id', $adminGroupId)
                            ->exists();
                        if (!$exists) {
                            $db::table('acl_user_group')->insert([
                                'user_id' => $userId,
                                'group_id' => $adminGroupId,
                            ]);
                        }
                    }
                } elseif ($userId > 0 && 'reject' === $formAction) {
                    // Delete user + actor + object chain
                    $db::table('acl_user_group')->where('user_id', $userId)->delete();
                    $db::table('registry_user_group_member')->where('user_id', $userId)->delete();
                    $db::table('user')->where('id', $userId)->delete();
                    $db::table('actor_i18n')->where('id', $userId)->delete();
                    $db::table('actor')->where('id', $userId)->delete();
                    $db::table('object')->where('id', $userId)->delete();
                }
            } catch (\Throwable $e) {
                $this->logError('Admin user ' . $formAction . ' failed (user_id=' . $userId . '): ' . $e->getMessage(), $e);
            }
        }

        // Fetch all pending (inactive) users
        // Note: user table has no created_at — it lives on the object table (AtoM inheritance)
        $this->pendingUsers = $db::table('user')
            ->leftJoin('actor_i18n', function ($j) {
                $j->on('actor_i18n.id', '=', 'user.id')
                  ->where('actor_i18n.culture', '=', $this->culture());
            })
            ->leftJoin('object', 'object.id', '=', 'user.id')
            ->where('user.active', 0)
            ->select('user.id', 'user.email', 'user.username', 'actor_i18n.authorized_form_of_name as name', 'object.created_at')
            ->orderBy('object.created_at', 'desc')
            ->get()
            ->all();

        // Also fetch recently approved users (last 20)
        $this->activeUsers = $db::table('user')
            ->leftJoin('actor_i18n', function ($j) {
                $j->on('actor_i18n.id', '=', 'user.id')
                  ->where('actor_i18n.culture', '=', $this->culture());
            })
            ->where('user.active', 1)
            ->select('user.id', 'user.email', 'user.username', 'actor_i18n.authorized_form_of_name as name')
            ->orderBy('user.id', 'desc')
            ->limit(20)
            ->get()
            ->all();
    }

    // ================================================================
    // USER MANAGEMENT
    // ================================================================

    public function executeAdminUserManage($request)
    {
        $admin = $this->requireAdminUser();
        if (!$admin) {
            return;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;

        // Handle bulk actions
        if ($request->isMethod('post')) {
            $formAction = $request->getParameter('form_action', '');
            $userId = (int) $request->getParameter('user_id');

            try {
                if ($userId > 0 && 'toggle_active' === $formAction) {
                    $user = $db::table('user')->where('id', $userId)->first();
                    if ($user) {
                        $db::table('user')->where('id', $userId)->update(['active' => $user->active ? 0 : 1]);
                    }
                } elseif ($userId > 0 && 'delete' === $formAction) {
                    // Delete user + actor + object chain
                    $db::table('acl_user_group')->where('user_id', $userId)->delete();
                    $db::table('registry_user_group_member')->where('user_id', $userId)->delete();
                    $db::table('user')->where('id', $userId)->delete();
                    $db::table('actor_i18n')->where('id', $userId)->delete();
                    $db::table('actor')->where('id', $userId)->delete();
                    $db::table('object')->where('id', $userId)->delete();
                }
            } catch (\Throwable $e) {
                $this->logError('Admin user manage ' . $formAction . ' failed (user_id=' . $userId . '): ' . $e->getMessage(), $e);
            }
        }

        // Search / filter
        $search = trim($request->getParameter('q', ''));
        $filter = $request->getParameter('filter', 'all'); // all, active, inactive, admin

        $query = $db::table('user')
            ->leftJoin('actor_i18n', function ($j) {
                $j->on('actor_i18n.id', '=', 'user.id')
                  ->where('actor_i18n.culture', '=', $this->culture());
            })
            ->leftJoin('object', 'object.id', '=', 'user.id')
            ->select(
                'user.id', 'user.username', 'user.email', 'user.active',
                'actor_i18n.authorized_form_of_name as name',
                'object.created_at'
            );

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('user.username', 'like', "%{$search}%")
                  ->orWhere('user.email', 'like', "%{$search}%")
                  ->orWhere('actor_i18n.authorized_form_of_name', 'like', "%{$search}%");
            });
        }

        if ('active' === $filter) {
            $query->where('user.active', 1);
        } elseif ('inactive' === $filter) {
            $query->where('user.active', 0);
        } elseif ('admin' === $filter) {
            $adminGroupId = \AtomExtensions\Constants\AclConstants::ADMINISTRATOR_ID;
            $query->whereExists(function ($q) use ($adminGroupId) {
                $q->select($q->raw(1))
                  ->from('acl_user_group')
                  ->whereColumn('acl_user_group.user_id', 'user.id')
                  ->where('acl_user_group.group_id', $adminGroupId);
            });
        }

        $query->orderBy('user.id', 'desc');

        // Pagination
        $page = max(1, (int) $request->getParameter('page', 1));
        $perPage = 25;
        $total = (clone $query)->count();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);

        $this->users = $query->offset(($page - 1) * $perPage)->limit($perPage)->get()->all();

        // Fetch group memberships for displayed users
        $userIds = array_map(function ($u) { return $u->id; }, $this->users);
        $this->userGroups = [];
        if (!empty($userIds)) {
            $memberships = $db::table('acl_user_group')
                ->join('acl_group_i18n', function ($j) {
                    $j->on('acl_group_i18n.id', '=', 'acl_user_group.group_id')
                      ->where('acl_group_i18n.culture', '=', 'en');
                })
                ->whereIn('acl_user_group.user_id', $userIds)
                ->select('acl_user_group.user_id', 'acl_user_group.group_id', 'acl_group_i18n.name as group_name')
                ->get();
            foreach ($memberships as $m) {
                $this->userGroups[$m->user_id][] = $m;
            }
        }

        $this->search = $search;
        $this->filter = $filter;
        $this->page = $page;
        $this->totalPages = $totalPages;
        $this->total = $total;
    }

    public function executeAdminUserEdit($request)
    {
        $admin = $this->requireAdminUser();
        if (!$admin) {
            return;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $userId = (int) $request->getParameter('id');

        $this->editUser = $db::table('user')
            ->leftJoin('actor_i18n', function ($j) {
                $j->on('actor_i18n.id', '=', 'user.id')
                  ->where('actor_i18n.culture', '=', $this->culture());
            })
            ->leftJoin('object', 'object.id', '=', 'user.id')
            ->where('user.id', $userId)
            ->select('user.id', 'user.username', 'user.email', 'user.active',
                'actor_i18n.authorized_form_of_name as name', 'object.created_at')
            ->first();

        if (!$this->editUser) {
            $this->forward404();
            return;
        }

        // Fetch current group memberships
        $this->currentGroups = $db::table('acl_user_group')
            ->where('user_id', $userId)
            ->pluck('group_id')
            ->all();

        // All assignable groups (excluding anonymous=98 and root=1)
        $this->allGroups = $db::table('acl_group')
            ->leftJoin('acl_group_i18n', function ($j) {
                $j->on('acl_group_i18n.id', '=', 'acl_group.id')
                  ->where('acl_group_i18n.culture', '=', 'en');
            })
            ->whereNotIn('acl_group.id', [1, 98])
            ->select('acl_group.id', 'acl_group_i18n.name')
            ->orderBy('acl_group.id')
            ->get()
            ->all();

        // Registry group memberships
        $this->registryGroups = $db::table('registry_user_group_member as m')
            ->join('registry_user_group as g', 'g.id', '=', 'm.group_id')
            ->where('m.user_id', $userId)
            ->select('g.id', 'g.name', 'm.role')
            ->get()
            ->all();

        $this->saved = false;

        if ($request->isMethod('post')) {
            $formAction = $request->getParameter('form_action', '');

            if ('save' === $formAction) {
                try {
                    $newName = trim($request->getParameter('name', ''));
                    $newEmail = trim($request->getParameter('email', ''));
                    $newUsername = trim($request->getParameter('username', ''));
                    $active = (int) $request->getParameter('active', 0);

                    // Update user table
                    $db::table('user')->where('id', $userId)->update([
                        'email' => $newEmail,
                        'username' => $newUsername,
                        'active' => $active,
                    ]);

                    // Update actor_i18n name
                    $db::table('actor_i18n')
                        ->where('id', $userId)
                        ->where('culture', $this->culture())
                        ->update(['authorized_form_of_name' => $newName]);

                    // Update group memberships
                    $selectedGroups = $request->getParameter('groups', []);
                    if (!is_array($selectedGroups)) {
                        $selectedGroups = [];
                    }
                    $selectedGroups = array_map('intval', $selectedGroups);

                    // Remove existing (except root=1 and anonymous=98)
                    $db::table('acl_user_group')
                        ->where('user_id', $userId)
                        ->whereNotIn('group_id', [1, 98])
                        ->delete();

                    // Insert selected
                    foreach ($selectedGroups as $gid) {
                        if ($gid > 0 && !in_array($gid, [1, 98])) {
                            $db::table('acl_user_group')->insert([
                                'user_id' => $userId,
                                'group_id' => $gid,
                            ]);
                        }
                    }

                    $this->saved = true;
                } catch (\Throwable $e) {
                    $this->logError('Admin user edit failed (user_id=' . $userId . '): ' . $e->getMessage(), $e);
                }

                // Refresh data
                $this->editUser = $db::table('user')
                    ->leftJoin('actor_i18n', function ($j) {
                        $j->on('actor_i18n.id', '=', 'user.id')
                          ->where('actor_i18n.culture', '=', $this->culture());
                    })
                    ->leftJoin('object', 'object.id', '=', 'user.id')
                    ->where('user.id', $userId)
                    ->select('user.id', 'user.username', 'user.email', 'user.active',
                        'actor_i18n.authorized_form_of_name as name', 'object.created_at')
                    ->first();

                $this->currentGroups = $db::table('acl_user_group')
                    ->where('user_id', $userId)
                    ->pluck('group_id')
                    ->all();
            }
        }
    }

    public function executeAdminUserResetPassword($request)
    {
        $admin = $this->requireAdminUser();
        if (!$admin) {
            return;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $userId = (int) $request->getParameter('id');

        $user = $db::table('user')->where('id', $userId)->first();
        if (!$user) {
            $this->forward404();
            return;
        }

        $newPassword = $request->getParameter('new_password', '');
        if (strlen($newPassword) < 6) {
            $this->getUser()->setFlash('error', 'Password must be at least 6 characters.');
            $this->redirect('/registry/admin/users/' . $userId . '/edit');
            return;
        }

        try {
            // AtoM two-layer password: sha1(salt + plaintext) → argon2i
            $salt = bin2hex(random_bytes(16));
            $sha1 = sha1($salt . $newPassword);
            $hash = password_hash($sha1, PASSWORD_ARGON2I);

            $db::table('user')->where('id', $userId)->update([
                'salt' => $salt,
                'password_hash' => $hash,
            ]);

            $this->getUser()->setFlash('notice', 'Password reset successfully.');
        } catch (\Throwable $e) {
            $this->logError('Password reset failed (user_id=' . $userId . '): ' . $e->getMessage(), $e);
            $this->getUser()->setFlash('error', 'Password reset failed. Please try again.');
        }
        $this->redirect('/registry/admin/users/' . $userId . '/edit');
    }

    // ================================================================
    // SYNC API
    // ================================================================

    public function executeApiSyncRegister($request)
    {
        $svc = $this->loadService('SyncService');

        $payload = json_decode($request->getContent(), true);
        if (!$payload) {
            return $this->jsonResponse(['error' => 'Invalid JSON payload'], 400);
        }

        $result = $svc->register($payload);

        return $this->jsonResponse($result, isset($result['error']) ? 400 : 200);
    }

    public function executeApiSyncHeartbeat($request)
    {
        $svc = $this->loadService('SyncService');

        $token = $request->getHttpHeader('X-Sync-Token') ?: $request->getParameter('sync_token', '');
        $payload = json_decode($request->getContent(), true) ?: [];

        if ('' === $token) {
            return $this->jsonResponse(['error' => 'Missing sync token'], 401);
        }

        $result = $svc->heartbeat($token, $payload);

        return $this->jsonResponse($result, isset($result['error']) ? 400 : 200);
    }

    public function executeApiSyncUpdate($request)
    {
        $svc = $this->loadService('SyncService');

        $token = $request->getHttpHeader('X-Sync-Token') ?: $request->getParameter('sync_token', '');
        $payload = json_decode($request->getContent(), true) ?: [];

        if ('' === $token) {
            return $this->jsonResponse(['error' => 'Missing sync token'], 401);
        }

        $result = $svc->update($token, $payload);

        return $this->jsonResponse($result, isset($result['error']) ? 400 : 200);
    }

    public function executeApiSyncStatus($request)
    {
        $svc = $this->loadService('SyncService');

        $token = $request->getParameter('sync_token', '');
        if ('' === $token) {
            return $this->jsonResponse(['error' => 'Missing sync token'], 401);
        }

        $result = $svc->getStatus($token);

        return $this->jsonResponse($result, isset($result['error']) ? 404 : 200);
    }

    public function executeApiDirectory($request)
    {
        $svc = $this->loadService('SyncService');

        return $this->jsonResponse($svc->getDirectory());
    }

    public function executeApiSoftwareLatest($request)
    {
        $svc = $this->loadService('SoftwareService');
        $slug = $request->getParameter('slug');

        $software = \Illuminate\Database\Capsule\Manager::table('registry_software')
            ->where('slug', $slug)->where('is_active', 1)->first();

        if (!$software) {
            return $this->jsonResponse(['error' => 'Software not found'], 404);
        }

        $latest = $svc->getLatestVersion($software->id);

        return $this->jsonResponse([
            'software' => $software->name,
            'slug' => $software->slug,
            'latest_version' => $software->latest_version,
            'release' => $latest ? [
                'version' => $latest->version,
                'release_type' => $latest->release_type,
                'released_at' => $latest->released_at,
                'git_tag' => $latest->git_tag,
                'is_stable' => (bool) $latest->is_stable,
            ] : null,
        ]);
    }

    // ================================================================
    // Helpers
    // ================================================================

    protected function getContactFormData($request, string $entityType, int $entityId): array
    {
        $roles = $request->getParameter('roles', []);
        if (is_string($roles)) {
            $roles = array_filter(array_map('trim', explode(',', $roles)));
        }

        return [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'first_name' => trim($request->getParameter('first_name', '')),
            'last_name' => trim($request->getParameter('last_name', '')),
            'email' => trim($request->getParameter('email', '')),
            'phone' => trim($request->getParameter('phone', '')),
            'mobile' => trim($request->getParameter('mobile', '')),
            'job_title' => trim($request->getParameter('job_title', '')),
            'department' => trim($request->getParameter('department', '')),
            'roles' => !empty($roles) ? json_encode(array_values($roles)) : null,
            'is_primary' => $request->getParameter('is_primary', 0) ? 1 : 0,
            'is_public' => $request->getParameter('is_public', 1) ? 1 : 0,
            'notes' => trim($request->getParameter('notes', '')),
        ];
    }

    // ================================================================
    // FAVORITES: Toggle favorite on any entity
    // =========================================================================
    // Notes (universal)
    // =========================================================================

    public function executeNoteSave($request)
    {
        $user = $this->requireLogin();
        if (!$user) { return; }

        $db = \Illuminate\Database\Capsule\Manager::class;

        $entityType = trim($request->getParameter('entity_type', ''));
        $entityId = (int) $request->getParameter('entity_id', 0);
        $content = trim($request->getParameter('note_content', ''));
        $returnUrl = $request->getParameter('return_url', '/registry');

        if (empty($entityType) || $entityId < 1 || $content === '') {
            $this->redirect($returnUrl);
            return;
        }

        $regUser = $db::table('registry_user')
            ->where('atom_user_id', (int) $user->getAttribute('user_id'))
            ->first();

        $userName = $regUser->display_name ?? ($regUser->username ?? 'Unknown');

        $db::table('registry_note')->insert([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'user_id' => $regUser->id ?? null,
            'user_name' => $userName,
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->redirect($returnUrl);
    }

    public function executeNoteDelete($request)
    {
        $user = $this->requireLogin();
        if (!$user) { return; }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $id = (int) $request->getParameter('id', 0);
        $returnUrl = $request->getParameter('return_url', '/registry');

        $note = $db::table('registry_note')->where('id', $id)->first();
        if (!$note) {
            $this->redirect($returnUrl);
            return;
        }

        // Only author or admin can delete
        $regUser = $db::table('registry_user')
            ->where('atom_user_id', (int) $user->getAttribute('user_id'))
            ->first();
        $isAdmin = $this->isAdmin();

        if ($isAdmin || ($regUser && (int) $regUser->id === (int) $note->user_id)) {
            $db::table('registry_note')->where('id', $id)->delete();
        }

        $this->redirect($returnUrl);
    }

    public function executeNotePin($request)
    {
        if (!$this->requireAdminUser()) { return; }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $id = (int) $request->getParameter('id', 0);
        $returnUrl = $request->getParameter('return_url', '/registry');

        $note = $db::table('registry_note')->where('id', $id)->first();
        if ($note) {
            $db::table('registry_note')->where('id', $id)->update([
                'is_pinned' => $note->is_pinned ? 0 : 1,
            ]);
        }

        $this->redirect($returnUrl);
    }

    // ================================================================

    public function executeFavoriteToggle($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $entityType = $request->getParameter('entity_type', '');
        $entityId = (int) $request->getParameter('entity_id', 0);
        $returnUrl = $request->getParameter('return', '/registry/');

        $validTypes = ['institution', 'vendor', 'software', 'group'];
        if (!in_array($entityType, $validTypes) || $entityId < 1) {
            $this->redirect($returnUrl);
            return;
        }

        $userId = $this->getCurrentUserId();
        $existing = $db::table('registry_favorite')
            ->where('user_id', $userId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->first();

        if ($existing) {
            $db::table('registry_favorite')->where('id', $existing->id)->delete();
        } else {
            $db::table('registry_favorite')->insert([
                'user_id' => $userId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->redirect($returnUrl);
    }

    public function executeMyFavorites($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $userId = $this->getCurrentUserId();

        // Load all favorites grouped by type
        $favorites = $db::table('registry_favorite')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();

        $this->institutions = [];
        $this->vendors = [];
        $this->software = [];
        $this->groups = [];

        foreach ($favorites as $fav) {
            switch ($fav->entity_type) {
                case 'institution':
                    $item = $db::table('registry_institution')->where('id', $fav->entity_id)->first();
                    if ($item) { $this->institutions[] = $item; }
                    break;
                case 'vendor':
                    $item = $db::table('registry_vendor')->where('id', $fav->entity_id)->first();
                    if ($item) { $this->vendors[] = $item; }
                    break;
                case 'software':
                    $item = $db::table('registry_software')->where('id', $fav->entity_id)->first();
                    if ($item) { $this->software[] = $item; }
                    break;
                case 'group':
                    $item = $db::table('registry_user_group')->where('id', $fav->entity_id)->first();
                    if ($item) { $this->groups[] = $item; }
                    break;
            }
        }
    }

    // ================================================================
    // NEWSLETTER: Subscribe, Unsubscribe, Admin
    // ================================================================

    public function executeNewsletterSubscribe($request)
    {
        $this->result = null;
        $this->error = null;

        $user = \sfContext::getInstance()->getUser();

        // Check if logged-in user is already subscribed (on GET)
        if (!$request->isMethod('post') && $user && $user->isAuthenticated()) {
            $email = strtolower(trim($user->getAttribute('user_email', '')));
            // Fallback: look up email from DB if not in session
            if (empty($email)) {
                $userId = $user->getAttribute('user_id');
                if ($userId) {
                    $email = strtolower(trim(\Illuminate\Database\Capsule\Manager::table('user')
                        ->where('id', $userId)->value('email') ?? ''));
                    if (!empty($email)) {
                        $user->setAttribute('user_email', $email);
                    }
                }
            }
            if (!empty($email)) {
                $existing = \Illuminate\Database\Capsule\Manager::table('registry_newsletter_subscriber')
                    ->where('email', $email)
                    ->where('status', 'active')
                    ->first();
                if ($existing) {
                    $this->result = ['success' => true, 'already_subscribed' => true];
                }
            }
        }

        if ($request->isMethod('post')) {
            $svc = $this->loadService('NewsletterService');
            $data = [
                'email' => trim($request->getParameter('email', '')),
                'name' => trim($request->getParameter('name', '')),
                'auto_confirm' => true,
            ];

            if ($user && $user->isAuthenticated()) {
                $data['user_id'] = $user->getAttribute('user_id');
            }

            $this->result = $svc->subscribe($data);
            if (empty($this->result['success'])) {
                $this->error = $this->result['error'] ?? 'Subscription failed.';
            }
        }
    }

    public function executeNewsletterUnsubscribe($request)
    {
        $svc = $this->loadService('NewsletterService');
        $token = $request->getParameter('token', '');

        $this->result = null;
        $this->error = null;

        if (!empty($token)) {
            $this->result = $svc->unsubscribe($token);
            if (empty($this->result['success'])) {
                $this->error = $this->result['error'] ?? 'Unsubscribe failed.';
            }
        }
    }

    public function executeNewsletterBrowse($request)
    {
        $svc = $this->loadService('NewsletterService');
        $this->newsletters = $svc->browse([
            'status' => 'sent',
            'page' => max(1, (int) $request->getParameter('page', 1)),
            'limit' => 12,
        ]);
    }

    public function executeNewsletterView($request)
    {
        $svc = $this->loadService('NewsletterService');
        $this->newsletter = $svc->findById((int) $request->getParameter('id', 0));

        if (!$this->newsletter || 'sent' !== $this->newsletter->status) {
            $this->forward404();
        }
    }

    public function executeAdminNewsletters($request)
    {
        if (!$this->isAdmin()) {
            $this->redirect(url_for(['module' => 'registry', 'action' => 'login']));
            return;
        }

        $svc = $this->loadService('NewsletterService');
        $this->newsletters = $svc->browse([
            'page' => max(1, (int) $request->getParameter('page', 1)),
            'limit' => 20,
        ]);
        $this->subscriberStats = $svc->getSubscriberStats();
    }

    public function executeAdminNewsletterForm($request)
    {
        if (!$this->isAdmin()) {
            $this->redirect(url_for(['module' => 'registry', 'action' => 'login']));
            return;
        }

        $svc = $this->loadService('NewsletterService');
        $id = (int) $request->getParameter('id', 0);
        $this->newsletter = $id ? $svc->findById($id) : null;
        $this->errors = [];

        if ($request->isMethod('post')) {
            $data = [
                'subject' => trim($request->getParameter('subject', '')),
                'content' => trim($request->getParameter('content', '')),
                'excerpt' => trim($request->getParameter('excerpt', '')),
                'author_name' => \sfContext::getInstance()->getUser()->getAttribute('user_name', ''),
                'author_user_id' => $this->getCurrentUserId(),
            ];

            if (empty($data['subject'])) {
                $this->errors[] = 'Subject is required.';
            }
            if (empty($data['content'])) {
                $this->errors[] = 'Content is required.';
            }

            if (empty($this->errors)) {
                if ($this->newsletter) {
                    $result = $svc->update($this->newsletter->id, $data);
                } else {
                    $result = $svc->create($data);
                }

                if (!empty($result['success'])) {
                    $sfUser = \sfContext::getInstance()->getUser();
                    $sfUser->setFlash('success', $this->newsletter ? 'Newsletter updated.' : 'Newsletter created.');
                    $this->redirect(url_for(['module' => 'registry', 'action' => 'adminNewsletters']));
                    return;
                }
                $this->errors[] = $result['error'] ?? 'Save failed.';
            }

            $this->newsletter = (object) $data;
        }
    }

    public function executeAdminNewsletterSend($request)
    {
        if (!$this->isAdmin()) {
            $this->redirect(url_for(['module' => 'registry', 'action' => 'login']));
            return;
        }

        $svc = $this->loadService('NewsletterService');
        $id = (int) $request->getParameter('id', 0);
        $sfUser = \sfContext::getInstance()->getUser();

        $result = $svc->send($id);
        if (!empty($result['success'])) {
            $sfUser->setFlash('success', sprintf('Newsletter sent to %d subscribers (%d failed).', $result['sent'], $result['failed']));
        } else {
            $sfUser->setFlash('error', $result['error'] ?? 'Send failed.');
        }

        $this->redirect(url_for(['module' => 'registry', 'action' => 'adminNewsletters']));
    }

    public function executeAdminSubscribers($request)
    {
        if (!$this->isAdmin()) {
            $this->redirect(url_for(['module' => 'registry', 'action' => 'login']));
            return;
        }

        $svc = $this->loadService('NewsletterService');
        $this->subscribers = $svc->browseSubscribers([
            'page' => max(1, (int) $request->getParameter('page', 1)),
            'limit' => 50,
            'status' => $request->getParameter('status', ''),
            'search' => $request->getParameter('q', ''),
        ]);
        $this->stats = $svc->getSubscriberStats();
    }

    // ================================================================
    // AUTH: Login, Register, Logout, OAuth
    // ================================================================

    public function executeLogin($request)
    {
        $user = \sfContext::getInstance()->getUser();
        if ($user && $user->isAuthenticated()) {
            $this->redirect('/registry/');
            return;
        }

        $this->error = null;
        $this->groups = [];

        // Fetch featured user groups for the registration sidebar
        $groupSvc = $this->loadService('UserGroupService');
        $this->groups = $groupSvc->browse(['featured' => true, 'limit' => 5])['items'];

        // Enabled OAuth providers
        require_once $this->pluginDir . '/lib/Services/OAuthService.php';
        $this->oauthProviders = \AhgRegistry\Services\OAuthService::getEnabledProviders();

        if ($request->isMethod('post')) {
            $email = trim($request->getParameter('email', ''));
            $password = $request->getParameter('password', '');

            if (empty($email) || empty($password)) {
                $this->error = 'Please enter your email and password.';
                return;
            }

            // Authenticate via AtoM user table
            $db = \Illuminate\Database\Capsule\Manager::class;
            $userRow = $db::table('user')
                ->where('email', $email)
                ->first();

            if (!$userRow) {
                $this->error = 'Invalid email or password.';
                return;
            }

            // Verify password using AtoM's dual-layer: password_verify(sha1(salt + password), hash)
            $validPassword = false;
            if (!empty($userRow->password_hash) && !empty($userRow->salt)) {
                $validPassword = password_verify(sha1($userRow->salt . $password), $userRow->password_hash);
            }
            // Legacy fallback: plain SHA-1 stored directly
            if (!$validPassword && !empty($userRow->salt) && !empty($userRow->password_hash)) {
                $validPassword = sha1($userRow->salt . $password) === $userRow->password_hash;
            }

            if (!$validPassword) {
                $this->error = 'Invalid email or password.';
                return;
            }

            if (!$userRow->active) {
                $this->error = 'Your account is pending admin approval. You will be notified once your account is activated.';
                return;
            }

            // Log in via Symfony session
            $user->setAuthenticated(true);
            $user->setAttribute('user_id', $userRow->id);

            // Get username from actor_i18n
            $actorName = $db::table('actor_i18n')
                ->where('id', $userRow->id)
                ->where('culture', $this->culture())
                ->value('authorized_form_of_name');
            $user->setAttribute('username', $actorName ?: $email);
            $user->setAttribute('user_name', $actorName ?: $email);
            $user->setAttribute('user_email', $email);

            // Set credentials (check ACL groups)
            $aclGroups = $db::table('acl_user_group')
                ->where('user_id', $userRow->id)
                ->pluck('group_id')
                ->toArray();

            // Group 100 = administrator
            if (in_array(100, $aclGroups)) {
                $user->addCredential('administrator');
            }

            // Redirect to intended URL or home
            $returnUrl = $request->getParameter('return', '/registry/');
            $this->redirect($returnUrl);
        }
    }

    public function executeRegister($request)
    {
        $user = \sfContext::getInstance()->getUser();
        if ($user && $user->isAuthenticated()) {
            $this->redirect('/registry/');
            return;
        }

        $this->error = null;
        $this->success = null;

        // Fetch user groups for joining during registration
        $groupSvc = $this->loadService('UserGroupService');
        $this->groups = $groupSvc->browse(['limit' => 50, 'active' => true])['items'];

        if ($request->isMethod('post')) {
            $name = trim($request->getParameter('name', ''));
            $email = trim($request->getParameter('email', ''));
            $password = $request->getParameter('password', '');
            $passwordConfirm = $request->getParameter('password_confirm', '');
            $selectedGroups = $request->getParameter('groups', []);

            // Validation
            if (empty($name) || empty($email) || empty($password)) {
                $this->error = 'Please fill in all required fields.';
                return;
            }

            if ($password !== $passwordConfirm) {
                $this->error = 'Passwords do not match.';
                return;
            }

            if (strlen($password) < 8) {
                $this->error = 'Password must be at least 8 characters.';
                return;
            }

            $db = \Illuminate\Database\Capsule\Manager::class;

            // Check if email already in use
            $existing = $db::table('user')->where('email', $email)->first();
            if ($existing) {
                $this->error = 'An account with that email already exists. Please log in instead.';
                return;
            }

            // Create object → actor → user (AtoM entity inheritance)
            $now = date('Y-m-d H:i:s');
            $culture = $this->culture();

            try {
                $db::connection()->beginTransaction();

                // 1. Insert into object table
                $objectId = $db::table('object')->insertGetId([
                    'class_name' => 'QubitUser',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                // 2. Insert into actor table
                $db::table('actor')->insert([
                    'id' => $objectId,
                    'entity_type_id' => \QubitTerm::PERSON_ID ?? 178,
                    'source_culture' => $culture,
                ]);

                // 3. Insert into actor_i18n
                $db::table('actor_i18n')->insert([
                    'id' => $objectId,
                    'culture' => $culture,
                    'authorized_form_of_name' => $name,
                ]);

                // 4. Insert into user table (active=0 — requires admin approval)
                // AtoM password: sha1(salt + plaintext) → argon2i
                $salt = bin2hex(random_bytes(16));
                $sha1 = sha1($salt . $password);
                $passwordHash = password_hash($sha1, PASSWORD_ARGON2I);
                $db::table('user')->insert([
                    'id' => $objectId,
                    'username' => $email,
                    'email' => $email,
                    'salt' => $salt,
                    'password_hash' => $passwordHash,
                    'active' => 0,
                ]);

                // 5. Assign to authenticated group
                $db::table('acl_user_group')->insert([
                    'user_id' => $objectId,
                    'group_id' => \AtomExtensions\Constants\AclConstants::AUTHENTICATED_ID,
                ]);

                $db::connection()->commit();
            } catch (\Throwable $e) {
                $db::connection()->rollBack();
                $this->logError('Registration failed for email=' . $email . ': ' . $e->getMessage(), $e);
                $this->error = 'Registration failed. Please try again.';

                return;
            }

            // 6. Join selected user groups (non-critical — outside transaction)
            if (is_array($selectedGroups)) {
                foreach ($selectedGroups as $groupId) {
                    $groupId = (int) $groupId;
                    if ($groupId > 0) {
                        try {
                            $db::table('registry_user_group_member')->insert([
                                'group_id' => $groupId,
                                'user_id' => $objectId,
                                'email' => $email,
                                'name' => $name,
                                'role' => 'member',
                                'is_active' => 1,
                                'joined_at' => $now,
                            ]);
                            $db::table('registry_user_group')
                                ->where('id', $groupId)
                                ->increment('member_count');
                        } catch (\Throwable $e) {
                            // Non-critical — user account still created
                        }
                    }
                }
            }

            // 7. Newsletter subscription (if opted in)
            if ($request->getParameter('newsletter_subscribe')) {
                try {
                    $nlSvc = $this->loadService('NewsletterService');
                    $nlSvc->subscribe([
                        'email' => $email,
                        'name' => $name,
                        'user_id' => $objectId,
                        'auto_confirm' => true,
                    ]);
                } catch (\Throwable $e) {
                    // Non-critical
                }
            }

            $this->success = 'Account created! An administrator will review and activate your account.';
            $this->redirect('/registry/login?registered=pending');
        }
    }

    public function executeLogout($request)
    {
        $user = \sfContext::getInstance()->getUser();
        if ($user) {
            $user->setAuthenticated(false);
            $user->clearCredentials();
            $user->getAttributeHolder()->clear();
        }

        $this->redirect('/registry/login');
    }

    public function executeOauthStart($request)
    {
        $provider = $request->getParameter('provider', '');
        require_once $this->pluginDir . '/lib/Services/OAuthService.php';

        $redirectUri = $request->getUriPrefix() . '/registry/oauth/' . $provider . '/callback';
        $authUrl = \AhgRegistry\Services\OAuthService::getAuthUrl($provider, $redirectUri);

        if (!$authUrl) {
            $this->redirect('/registry/login?error=oauth_not_configured');
            return;
        }

        $this->redirect($authUrl);
    }

    public function executeOauthCallback($request)
    {
        $provider = $request->getParameter('provider', '');
        $code = $request->getParameter('code', '');
        $state = $request->getParameter('state', '');
        $error = $request->getParameter('error', '');

        require_once $this->pluginDir . '/lib/Services/OAuthService.php';

        if ($error || empty($code)) {
            $this->redirect('/registry/login?error=oauth_denied');
            return;
        }

        // Validate CSRF state
        if (!\AhgRegistry\Services\OAuthService::validateState($state)) {
            $this->redirect('/registry/login?error=invalid_state');
            return;
        }

        $redirectUri = $request->getUriPrefix() . '/registry/oauth/' . $provider . '/callback';
        $oauthData = \AhgRegistry\Services\OAuthService::handleCallback($provider, $code, $redirectUri);

        if (!$oauthData) {
            $this->redirect('/registry/login?error=oauth_failed');
            return;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;

        // Check if this OAuth account is already linked
        $linked = \AhgRegistry\Services\OAuthService::findByProviderAccount($provider, $oauthData['provider_user_id']);

        if ($linked) {
            // Log in existing user
            $userRow = $db::table('user')->where('id', $linked->user_id)->first();
            if ($userRow && $userRow->active) {
                $sfUser = \sfContext::getInstance()->getUser();
                $sfUser->setAuthenticated(true);
                $sfUser->setAttribute('user_id', $userRow->id);

                $actorName = $db::table('actor_i18n')
                    ->where('id', $userRow->id)
                    ->where('culture', $this->culture())
                    ->value('authorized_form_of_name');
                $sfUser->setAttribute('username', $actorName ?: $userRow->email);
                $sfUser->setAttribute('user_email', $userRow->email);

                $aclGroups = $db::table('acl_user_group')
                    ->where('user_id', $userRow->id)
                    ->pluck('group_id')
                    ->toArray();
                if (in_array(100, $aclGroups)) {
                    $sfUser->addCredential('administrator');
                }

                // Update OAuth tokens
                \AhgRegistry\Services\OAuthService::linkAccount($userRow->id, $provider, $oauthData);

                $this->redirect('/registry/');
                return;
            }
        }

        // Check if email matches an existing user
        if (!empty($oauthData['email'])) {
            $userRow = $db::table('user')->where('email', $oauthData['email'])->first();
            if ($userRow) {
                // Link and log in
                \AhgRegistry\Services\OAuthService::linkAccount($userRow->id, $provider, $oauthData);

                $sfUser = \sfContext::getInstance()->getUser();
                $sfUser->setAuthenticated(true);
                $sfUser->setAttribute('user_id', $userRow->id);
                $sfUser->setAttribute('username', $oauthData['name'] ?: $userRow->email);

                $this->redirect('/registry/');
                return;
            }
        }

        // No existing user — auto-register
        $now = date('Y-m-d H:i:s');
        $name = $oauthData['name'] ?: 'User';
        $email = $oauthData['email'] ?: $provider . '_' . $oauthData['provider_user_id'] . '@oauth.local';

        $objectId = $db::table('object')->insertGetId([
            'class_name' => 'QubitUser',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $db::table('actor')->insert(['id' => $objectId, 'entity_type_id' => 178]);
        $db::table('actor_i18n')->insert([
            'id' => $objectId,
            'culture' => $this->culture(),
            'authorized_form_of_name' => $name,
        ]);

        $db::table('user')->insert([
            'id' => $objectId,
            'username' => $email,
            'email' => $email,
            'password_hash' => '',
            'active' => 1,
        ]);

        $db::table('acl_user_group')->insert(['user_id' => $objectId, 'group_id' => 4]);

        \AhgRegistry\Services\OAuthService::linkAccount($objectId, $provider, $oauthData);

        $sfUser = \sfContext::getInstance()->getUser();
        $sfUser->setAuthenticated(true);
        $sfUser->setAttribute('user_id', $objectId);
        $sfUser->setAttribute('username', $name);

        $this->redirect('/registry/');
    }

    // ================================================================
    // SOFTWARE COMPONENTS (Plugins/Modules management)
    // ================================================================

    public function executeSoftwareComponents($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $id = (int) $request->getParameter('id');
        $this->software = $db::table('registry_software')->where('id', $id)->first();
        if (!$this->software) {
            $this->forward404();

            return;
        }

        $this->components = $db::table('registry_software_component')
            ->where('software_id', $id)
            ->orderBy('category', 'asc')
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get()
            ->all();
    }

    public function executeSoftwareComponentAdd($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $softwareId = (int) $request->getParameter('id');
        $this->software = $db::table('registry_software')->where('id', $softwareId)->first();
        if (!$this->software) {
            $this->forward404();

            return;
        }

        $this->component = null;
        $this->errors = [];

        if ($request->isMethod('post')) {
            $data = [
                'software_id' => $softwareId,
                'name' => trim($request->getParameter('name', '')),
                'slug' => trim($request->getParameter('slug', '')),
                'component_type' => $request->getParameter('component_type', 'plugin'),
                'category' => trim($request->getParameter('category', '')) ?: null,
                'description' => trim($request->getParameter('description', '')) ?: null,
                'short_description' => trim($request->getParameter('short_description', '')) ?: null,
                'version' => trim($request->getParameter('version', '')) ?: null,
                'is_required' => $request->getParameter('is_required', 0) ? 1 : 0,
                'git_url' => trim($request->getParameter('git_url', '')) ?: null,
                'documentation_url' => trim($request->getParameter('documentation_url', '')) ?: null,
                'icon_class' => trim($request->getParameter('icon_class', '')) ?: null,
                'sort_order' => $request->getParameter('sort_order', '') !== '' ? (int) $request->getParameter('sort_order') : 100,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ('' === $data['name']) {
                $this->errors[] = 'Component name is required.';
            }
            if ('' === $data['slug']) {
                $data['slug'] = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['name']), '-'));
            }

            if (empty($this->errors)) {
                $db::table('registry_software_component')->insert($data);
                $this->redirect(url_for(['module' => 'registry', 'action' => 'softwareComponents', 'id' => $softwareId]));

                return;
            }

            $this->component = (object) $data;
        }
    }

    public function executeSoftwareComponentEdit($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $compId = (int) $request->getParameter('comp_id');
        $this->component = $db::table('registry_software_component')->where('id', $compId)->first();
        if (!$this->component) {
            $this->forward404();

            return;
        }

        $this->software = $db::table('registry_software')->where('id', $this->component->software_id)->first();
        $this->errors = [];

        if ($request->isMethod('post')) {
            $data = [
                'name' => trim($request->getParameter('name', '')),
                'slug' => trim($request->getParameter('slug', '')),
                'component_type' => $request->getParameter('component_type', 'plugin'),
                'category' => trim($request->getParameter('category', '')) ?: null,
                'description' => trim($request->getParameter('description', '')) ?: null,
                'short_description' => trim($request->getParameter('short_description', '')) ?: null,
                'version' => trim($request->getParameter('version', '')) ?: null,
                'is_required' => $request->getParameter('is_required', 0) ? 1 : 0,
                'git_url' => trim($request->getParameter('git_url', '')) ?: null,
                'documentation_url' => trim($request->getParameter('documentation_url', '')) ?: null,
                'icon_class' => trim($request->getParameter('icon_class', '')) ?: null,
                'sort_order' => $request->getParameter('sort_order', '') !== '' ? (int) $request->getParameter('sort_order') : 100,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ('' === $data['name']) {
                $this->errors[] = 'Component name is required.';
            }

            if (empty($this->errors)) {
                $db::table('registry_software_component')->where('id', $compId)->update($data);
                $this->redirect(url_for(['module' => 'registry', 'action' => 'softwareComponents', 'id' => $this->component->software_id]));

                return;
            }

            $this->component = (object) array_merge((array) $this->component, $data);
        }
    }

    public function executeSoftwareComponentDelete($request)
    {
        $user = $this->requireLogin();
        if (!$user) {
            return;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $compId = (int) $request->getParameter('comp_id');
        $comp = $db::table('registry_software_component')->where('id', $compId)->first();
        if (!$comp) {
            $this->forward404();

            return;
        }

        $db::table('registry_software_component')->where('id', $compId)->delete();
        $this->redirect(url_for(['module' => 'registry', 'action' => 'softwareComponents', 'id' => $comp->software_id]));
    }

    // =========================================================================
    // Standards: Public Browse & View
    // =========================================================================

    public function executeStandardBrowse($request)
    {
        $svc = $this->loadService('StandardService');

        $this->result = $svc->browse([
            'search' => $request->getParameter('q', ''),
            'category' => $request->getParameter('category', ''),
            'sector' => $request->getParameter('sector', ''),
            'page' => (int) $request->getParameter('page', 1),
            'limit' => 24,
        ]);

        $this->isAdmin = $this->isAdmin();
    }

    public function executeStandardView($request)
    {
        $svc = $this->loadService('StandardService');
        $slug = $request->getParameter('slug', '');

        $this->standard = $svc->getStandard($slug);
        if (!$this->standard) {
            $this->forward404();

            return;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $currentCat = $this->standard['standard']->category ?? '';
        $currentId = (int) $this->standard['standard']->id;
        $this->relatedStandards = $db::table('registry_standard')
            ->where('category', $currentCat)
            ->where('id', '!=', $currentId)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->limit(5)
            ->get()
            ->all();

        $this->isAdmin = $this->isAdmin();
    }

    public function executeStandardsSchema($request)
    {
        $this->redirect(url_for(['module' => 'registry', 'action' => 'erdView', 'slug' => 'standards-conformance']));
    }

    // =========================================================================
    // ERD Documentation: Public Browse & View
    // =========================================================================

    public function executeErdBrowse($request)
    {
        $db = \Illuminate\Database\Capsule\Manager::class;

        $query = $db::table('registry_erd')->where('is_active', 1);

        if (!empty($request->getParameter('category'))) {
            $query->where('category', $request->getParameter('category'));
        }
        if (!empty($request->getParameter('vendor'))) {
            $query->where('vendor_id', (int) $request->getParameter('vendor'));
        }

        $this->categories = $db::table('registry_erd')
            ->where('is_active', 1)
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();

        // Vendors that have ERD entries
        $this->vendors = $db::table('registry_erd as e')
            ->join('registry_vendor as v', 'v.id', '=', 'e.vendor_id')
            ->where('e.is_active', 1)
            ->select('v.id', 'v.name', $db::raw('COUNT(e.id) as erd_count'))
            ->groupBy('v.id', 'v.name')
            ->orderBy('v.name')
            ->get()
            ->all();

        $this->items = $query->orderBy('sort_order')->get()->all();
        $this->selectedCategory = $request->getParameter('category', '');
        $this->selectedVendor = $request->getParameter('vendor', '');
        $this->isAdmin = $this->isAdmin();
    }

    public function executeErdView($request)
    {
        $db = \Illuminate\Database\Capsule\Manager::class;
        $slug = $request->getParameter('slug', '');

        $this->erd = $db::table('registry_erd')->where('slug', $slug)->where('is_active', 1)->first();
        if (!$this->erd) {
            $this->forward404();
            return;
        }

        // Parse tables_json and fetch live schema
        $tables = json_decode($this->erd->tables_json, true);
        if (!is_array($tables)) { $tables = []; }

        $this->schema = [];
        $this->counts = [];
        $tableList = [];
        foreach ($tables as $tbl) {
            $cols = $db::select(
                "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, COLUMN_DEFAULT, EXTRA, COLUMN_COMMENT
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
                 ORDER BY ORDINAL_POSITION",
                [$tbl]
            );
            if (!empty($cols)) {
                $this->schema[$tbl] = $cols;
                $tableList[] = $tbl;
            }
            try {
                $this->counts[$tbl] = $db::table($tbl)->count();
            } catch (\Exception $e) {
                $this->counts[$tbl] = 0;
            }
        }

        // Foreign keys for these tables
        $this->foreignKeys = [];
        if (!empty($tableList)) {
            $placeholders = implode(',', array_fill(0, count($tableList), '?'));
            $this->foreignKeys = $db::select(
                "SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME, CONSTRAINT_NAME
                 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND REFERENCED_TABLE_NAME IS NOT NULL
                   AND TABLE_NAME IN ($placeholders)
                 ORDER BY TABLE_NAME, COLUMN_NAME",
                $tableList
            );
        }

        $this->isAdmin = $this->isAdmin();
    }

    // =========================================================================
    // ERD Documentation: Admin
    // =========================================================================

    public function executeAdminErd($request)
    {
        if (!$this->requireAdminUser()) { return; }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $this->items = $db::table('registry_erd')->orderBy('sort_order')->get()->all();
    }

    public function executeAdminErdEdit($request)
    {
        if (!$this->requireAdminUser()) { return; }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $id = (int) $request->getParameter('id', 0);

        if ($request->isMethod('POST')) {
            $vendorId = $request->getParameter('vendor_id', '');
            $data = [
                'plugin_name' => $request->getParameter('plugin_name', ''),
                'vendor_id' => $vendorId !== '' ? (int) $vendorId : null,
                'display_name' => $request->getParameter('display_name', ''),
                'category' => $request->getParameter('form_category', 'general'),
                'description' => $request->getParameter('description', ''),
                'tables_json' => html_entity_decode($request->getParameter('tables_json', '[]'), ENT_QUOTES, 'UTF-8'),
                'diagram' => html_entity_decode($request->getParameter('diagram', ''), ENT_QUOTES, 'UTF-8'),
                'notes' => $request->getParameter('notes', ''),
                'icon' => $request->getParameter('icon', 'fas fa-database'),
                'color' => $request->getParameter('color', 'primary'),
                'sort_order' => (int) $request->getParameter('sort_order', 100),
                'is_active' => $request->getParameter('is_active') ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            // Handle diagram image upload
            $imgFile = $_FILES['diagram_image'] ?? null;
            if ($imgFile && $imgFile['error'] === UPLOAD_ERR_OK && $imgFile['size'] > 0) {
                $allowed = ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/svg+xml',
                            'application/pdf', 'image/vnd.microsoft.icon'];
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($imgFile['tmp_name']);

                if (in_array($mime, $allowed)) {
                    $uploadDir = \sfConfig::get('sf_root_dir') . '/uploads/registry/erd';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0775, true);
                    }
                    $ext = pathinfo($imgFile['name'], PATHINFO_EXTENSION) ?: 'png';
                    $filename = 'erd-' . ($id > 0 ? $id : time()) . '-' . time() . '.' . strtolower($ext);
                    if (move_uploaded_file($imgFile['tmp_name'], $uploadDir . '/' . $filename)) {
                        $data['diagram_image'] = '/uploads/registry/erd/' . $filename;

                        // Remove old image if replacing
                        if ($id > 0) {
                            $existing = $db::table('registry_erd')->where('id', $id)->value('diagram_image');
                            if ($existing && $existing !== $data['diagram_image']) {
                                $oldPath = \sfConfig::get('sf_root_dir') . $existing;
                                if (file_exists($oldPath)) {
                                    @unlink($oldPath);
                                }
                            }
                        }
                    }
                }
            }

            // Handle image removal
            if ($request->getParameter('remove_diagram_image') == '1' && $id > 0) {
                $existing = $db::table('registry_erd')->where('id', $id)->value('diagram_image');
                if ($existing) {
                    $oldPath = \sfConfig::get('sf_root_dir') . $existing;
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                $data['diagram_image'] = null;
            }

            if ($id > 0) {
                $db::table('registry_erd')->where('id', $id)->update($data);
            } else {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['display_name']), '-'));
                $data['slug'] = $slug;
                $data['created_at'] = date('Y-m-d H:i:s');
                $id = $db::table('registry_erd')->insertGetId($data);
            }

            $this->redirect(url_for(['module' => 'registry', 'action' => 'adminErd']));
            return;
        }

        $this->erd = $id > 0
            ? $db::table('registry_erd')->where('id', $id)->first()
            : null;
    }

    // =========================================================================
    // Setup Guides: Public Browse & View
    // =========================================================================

    public function executeSetupGuideBrowse($request)
    {
        $svc = $this->loadService('StandardService');
        $swSlug = $request->getParameter('slug', '');

        $db = \Illuminate\Database\Capsule\Manager::class;
        $this->software = $db::table('registry_software')->where('slug', $swSlug)->where('is_active', 1)->first();
        if (!$this->software) {
            $this->forward404();

            return;
        }

        $this->result = $svc->browseGuides((int) $this->software->id, [
            'category' => $request->getParameter('category', ''),
            'page' => (int) $request->getParameter('page', 1),
            'limit' => 20,
        ]);

        $this->isAdmin = $this->isAdmin();
    }

    public function executeSetupGuideView($request)
    {
        $svc = $this->loadService('StandardService');
        $swSlug = $request->getParameter('slug', '');
        $guideSlug = $request->getParameter('guideSlug', '');

        $db = \Illuminate\Database\Capsule\Manager::class;
        $this->software = $db::table('registry_software')->where('slug', $swSlug)->where('is_active', 1)->first();
        if (!$this->software) {
            $this->forward404();

            return;
        }

        $this->guide = $svc->getGuide((int) $this->software->id, $guideSlug);
        if (!$this->guide) {
            $this->forward404();

            return;
        }

        // Increment view count
        $db::table('registry_setup_guide')->where('id', (int) $this->guide->id)->increment('view_count');

        // Other guides for sidebar
        $this->otherGuides = $db::table('registry_setup_guide')
            ->where('software_id', (int) $this->software->id)
            ->where('id', '!=', (int) $this->guide->id)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->limit(10)
            ->get()
            ->all();
    }

    // =========================================================================
    // Admin: Standards Management
    // =========================================================================

    public function executeAdminStandards($request)
    {
        $user = $this->requireAdminUser();
        if (!$user) {
            return;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;

        // Handle delete
        if ($request->isMethod('post') && $request->getParameter('form_action') === 'delete') {
            $id = (int) $request->getParameter('id');
            $db::table('registry_standard_extension')->where('standard_id', $id)->delete();
            $db::table('registry_software_standard')->where('standard_id', $id)->delete();
            $db::table('registry_standard')->where('id', $id)->delete();
            $this->redirect(url_for(['module' => 'registry', 'action' => 'adminStandards']));

            return;
        }

        $query = $db::table('registry_standard')->orderBy('sort_order');
        $category = $request->getParameter('category', '');
        if ($category) {
            $query->where('category', $category);
        }

        $this->standards = $query->get()->all();
        $this->categories = $db::table('registry_standard')
            ->selectRaw('category, COUNT(*) as cnt')
            ->groupBy('category')
            ->orderBy('category')
            ->get()->all();
        $this->currentCategory = $category;
    }

    public function executeAdminStandardEdit($request)
    {
        $user = $this->requireAdminUser();
        if (!$user) {
            return;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $id = (int) $request->getParameter('id', 0);

        if ($id) {
            $this->standard = $db::table('registry_standard')->where('id', $id)->first();
            if (!$this->standard) {
                $this->forward404();

                return;
            }
            $this->extensions = $db::table('registry_standard_extension')
                ->where('standard_id', $id)
                ->orderBy('sort_order')
                ->get()->all();
        } else {
            $this->standard = null;
            $this->extensions = [];
        }

        if ($request->isMethod('post')) {
            $svc = $this->loadService('StandardService');
            $data = [
                'name' => $request->getParameter('name', ''),
                'acronym' => $request->getParameter('acronym', '') ?: null,
                'category' => $request->getParameter('category', 'descriptive'),
                'short_description' => $request->getParameter('short_description', '') ?: null,
                'description' => $request->getParameter('description', '') ?: null,
                'website_url' => $request->getParameter('website_url', '') ?: null,
                'issuing_body' => $request->getParameter('issuing_body', '') ?: null,
                'current_version' => $request->getParameter('current_version', '') ?: null,
                'publication_year' => $request->getParameter('publication_year', '') ?: null,
                'sector_applicability' => json_encode($request->getParameter('sectors', [])),
                'is_featured' => $request->getParameter('is_featured') ? 1 : 0,
                'is_active' => $request->getParameter('is_active', 1) ? 1 : 0,
                'sort_order' => (int) $request->getParameter('sort_order', 100),
            ];

            if ($id) {
                $data['id'] = $id;
            }
            $data['slug'] = $svc->generateSlug($data['acronym'] ?: $data['name'], 'registry_standard', $id ?: null);

            $savedId = $svc->saveStandard($data);
            $this->redirect(url_for(['module' => 'registry', 'action' => 'adminStandards']));

            return;
        }
    }

    public function executeAdminExtensionEdit($request)
    {
        $user = $this->requireAdminUser();
        if (!$user) {
            return;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $standardId = (int) $request->getParameter('standardId');
        $id = (int) $request->getParameter('id', 0);

        $this->parentStandard = $db::table('registry_standard')->where('id', $standardId)->first();
        if (!$this->parentStandard) {
            $this->forward404();

            return;
        }

        if ($id) {
            $this->extension = $db::table('registry_standard_extension')->where('id', $id)->first();
            if (!$this->extension) {
                $this->forward404();

                return;
            }
        } else {
            $this->extension = null;
        }

        if ($request->isMethod('post')) {
            $svc = $this->loadService('StandardService');
            $data = [
                'standard_id' => $standardId,
                'extension_type' => $request->getParameter('extension_type', 'addition'),
                'title' => $request->getParameter('title', ''),
                'description' => $request->getParameter('description', ''),
                'rationale' => $request->getParameter('rationale', '') ?: null,
                'plugin_name' => $request->getParameter('plugin_name', '') ?: null,
                'api_endpoint' => $request->getParameter('api_endpoint', '') ?: null,
                'db_tables' => $request->getParameter('db_tables', '') ?: null,
                'is_active' => $request->getParameter('is_active', 1) ? 1 : 0,
                'sort_order' => (int) $request->getParameter('sort_order', 100),
                'created_by' => $this->getCurrentUserId(),
            ];

            if ($id) {
                $data['id'] = $id;
            }

            $svc->saveExtension($data);
            $this->redirect(url_for(['module' => 'registry', 'action' => 'adminStandardEdit', 'id' => $standardId]));

            return;
        }
    }

    public function executeAdminExtensionDelete($request)
    {
        $user = $this->requireAdminUser();
        if (!$user) {
            return;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $id = (int) $request->getParameter('id');
        $ext = $db::table('registry_standard_extension')->where('id', $id)->first();
        if (!$ext) {
            $this->forward404();

            return;
        }

        $db::table('registry_standard_extension')->where('id', $id)->delete();
        $this->redirect(url_for(['module' => 'registry', 'action' => 'adminStandardEdit', 'id' => $ext->standard_id]));
    }

    public function executeAdminSetupGuides($request)
    {
        $user = $this->requireAdminUser();
        if (!$user) {
            return;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;

        // Handle delete
        if ($request->isMethod('post') && $request->getParameter('form_action') === 'delete') {
            $id = (int) $request->getParameter('id');
            $db::table('registry_setup_guide')->where('id', $id)->delete();
            $this->redirect(url_for(['module' => 'registry', 'action' => 'adminSetupGuides']));

            return;
        }

        $this->guides = $db::table('registry_setup_guide as g')
            ->join('registry_software as s', 'g.software_id', '=', 's.id')
            ->select('g.*', 's.name as software_name', 's.slug as software_slug')
            ->orderBy('s.name')
            ->orderBy('g.sort_order')
            ->get()->all();
    }
}
