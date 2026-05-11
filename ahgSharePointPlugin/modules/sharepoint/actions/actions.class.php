<?php

/**
 * sharepoint module — admin UI + AJAX endpoints + (Phase 2) webhook receiver.
 *
 * Phase 1 actions: index, tenants, tenantEdit, tenantTest, drives, driveBrowse, mapping.
 * Phase 2 actions: subscriptions, events, eventDetail, webhook (returns 503 in Phase 1).
 * Phase 3 action: federatedSearch.
 *
 * @phase 1
 */
class sharepointActions extends sfActions
{
    public function executeIndex(sfWebRequest $request)
    {
        $this->checkAdmin();
        // TODO (Phase 1): aggregate tenant/drive/sync_state counts for the dashboard tile grid.
        return sfView::SUCCESS;
    }

    public function executeTenants(sfWebRequest $request)
    {
        $this->checkAdmin();
        // TODO: list rows from SharePointTenantRepository::all().
        return sfView::SUCCESS;
    }

    public function executeTenantEdit(sfWebRequest $request)
    {
        $this->checkAdmin();
        // TODO: GET = render form, POST = validate + persist via repository.
        // client_secret field is write-only (renders empty, only updates on non-empty submit).
        // Encrypt client_secret via EncryptionService before persisting.
        return sfView::SUCCESS;
    }

    public function executeTenantTest(sfWebRequest $request)
    {
        $this->checkAdmin();
        // TODO: invoke same logic as `php symfony sharepoint:test-connection --tenant=:id`,
        // return JSON result for AJAX consumer in tenantEdit page.
        $this->renderText(json_encode(['status' => 'not_implemented']));
        return sfView::NONE;
    }

    public function executeDrives(sfWebRequest $request)
    {
        $this->checkAdmin();
        $this->drives = \Illuminate\Database\Capsule\Manager::table('sharepoint_drive')
            ->leftJoin('sharepoint_tenant', 'sharepoint_tenant.id', '=', 'sharepoint_drive.tenant_id')
            ->select(
                'sharepoint_drive.*',
                'sharepoint_tenant.name AS tenant_name',
            )
            ->orderBy('sharepoint_drive.site_title')
            ->get();
        $this->tenants = \Illuminate\Database\Capsule\Manager::table('sharepoint_tenant')
            ->where('status', '!=', 'disabled')
            ->orderBy('name')
            ->get();
        return sfView::SUCCESS;
    }

    public function executeDriveBrowse(sfWebRequest $request)
    {
        $this->checkAdmin();
        $this->loadSharePointBrowser();
        $tenantId = (int) $request->getParameter('tenant_id');
        $op = (string) $request->getParameter('op');
        $browser = new \AtomExtensions\SharePoint\Services\SharePointBrowserService();
        $payload = ['op' => $op];
        try {
            if ($op === 'sites') {
                $payload['sites'] = $browser->listSites($tenantId, $request->getParameter('search'));
            } elseif ($op === 'drives') {
                $payload['drives'] = $browser->listDrives($tenantId, (string) $request->getParameter('site_id'));
            } else {
                throw new \InvalidArgumentException('unknown op: ' . $op);
            }
        } catch (\Throwable $e) {
            $payload['error'] = $e->getMessage();
            $this->getResponse()->setStatusCode(500);
        }
        $this->getResponse()->setContentType('application/json');
        $this->renderText(json_encode($payload));
        return sfView::NONE;
    }

    public function executeDriveRegister(sfWebRequest $request)
    {
        $this->checkAdmin();
        $this->tenants = \Illuminate\Database\Capsule\Manager::table('sharepoint_tenant')
            ->where('status', '!=', 'disabled')
            ->orderBy('name')
            ->get();
        return sfView::SUCCESS;
    }

    public function executeDriveSave(sfWebRequest $request)
    {
        $this->checkAdmin();
        if (!$request->isMethod('post')) {
            $this->redirect(['module' => 'sharepoint', 'action' => 'drives']);
        }
        $attrs = [
            'tenant_id' => (int) $request->getParameter('tenant_id'),
            'site_id' => (string) $request->getParameter('site_id'),
            'site_url' => (string) $request->getParameter('site_url'),
            'site_title' => $request->getParameter('site_title') ?: null,
            'drive_id' => (string) $request->getParameter('drive_id'),
            'drive_name' => $request->getParameter('drive_name') ?: null,
            'sector' => $request->getParameter('sector') ?: 'archive',
            'default_parent_placement' => $request->getParameter('default_parent_placement') ?: 'top_level',
            'ingest_enabled' => $request->getParameter('ingest_enabled') ? 1 : 0,
            'ai_processing_inherit' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        // Dedupe by (tenant_id, drive_id)
        $existing = \Illuminate\Database\Capsule\Manager::table('sharepoint_drive')
            ->where('tenant_id', $attrs['tenant_id'])
            ->where('drive_id', $attrs['drive_id'])
            ->first();
        if ($existing) {
            \Illuminate\Database\Capsule\Manager::table('sharepoint_drive')
                ->where('id', $existing->id)
                ->update([
                    'site_id' => $attrs['site_id'],
                    'site_url' => $attrs['site_url'],
                    'site_title' => $attrs['site_title'],
                    'drive_name' => $attrs['drive_name'],
                    'sector' => $attrs['sector'],
                    'default_parent_placement' => $attrs['default_parent_placement'],
                    'ingest_enabled' => $attrs['ingest_enabled'],
                    'updated_at' => $attrs['updated_at'],
                ]);
            $this->getUser()->setFlash('notice', 'Drive updated.');
        } else {
            \Illuminate\Database\Capsule\Manager::table('sharepoint_drive')->insert($attrs);
            $this->getUser()->setFlash('notice', 'Drive registered.');
        }
        $this->redirect(['module' => 'sharepoint', 'action' => 'drives']);
    }

    public function executeDriveDelete(sfWebRequest $request)
    {
        $this->checkAdmin();
        $id = (int) $request->getParameter('id');
        // Block delete when rules still reference it.
        $ruleCount = \Illuminate\Database\Capsule\Manager::table('sharepoint_ingest_rule')->where('drive_id', $id)->count();
        if ($ruleCount > 0) {
            $this->getUser()->setFlash('error', "Cannot delete drive: {$ruleCount} rule(s) still reference it.");
            $this->redirect(['module' => 'sharepoint', 'action' => 'drives']);
        }
        \Illuminate\Database\Capsule\Manager::table('sharepoint_mapping')->where('drive_id', $id)->delete();
        \Illuminate\Database\Capsule\Manager::table('sharepoint_drive')->where('id', $id)->delete();
        $this->getUser()->setFlash('notice', 'Drive deleted.');
        $this->redirect(['module' => 'sharepoint', 'action' => 'drives']);
    }

    public function executeColumns(sfWebRequest $request)
    {
        $this->checkAdmin();
        $this->loadSharePointBrowser();
        $driveId = (int) $request->getParameter('drive_id');
        $drive = \Illuminate\Database\Capsule\Manager::table('sharepoint_drive')->where('id', $driveId)->first();
        if (!$drive) {
            $this->getResponse()->setStatusCode(404);
            $this->renderText(json_encode(['error' => 'drive not found']));
            return sfView::NONE;
        }
        try {
            $browser = new \AtomExtensions\SharePoint\Services\SharePointBrowserService();
            $columns = $browser->listColumns((int) $drive->tenant_id, $drive->drive_id);
        } catch (\Throwable $e) {
            $this->getResponse()->setStatusCode(500);
            $this->renderText(json_encode(['error' => $e->getMessage()]));
            return sfView::NONE;
        }
        $this->getResponse()->setContentType('application/json');
        $this->renderText(json_encode(['columns' => $columns]));
        return sfView::NONE;
    }

    private function loadSharePointBrowser(): void
    {
        $base = __DIR__ . '/../../../lib/Services';
        require_once $base . '/GraphTokenCache.php';
        require_once $base . '/GraphClientService.php';
        require_once $base . '/SharePointBrowserService.php';
        require_once dirname($base) . '/Repositories/SharePointTenantRepository.php';
        require_once dirname($base) . '/Repositories/SharePointDriveRepository.php';
    }

    public function executeMapping(sfWebRequest $request)
    {
        $this->checkAdmin();
        // TODO: GET = render mapping editor (current rows + SP columns inferred via Graph).
        // POST = persist rows in sharepoint_mapping.
        return sfView::SUCCESS;
    }

    // ---- Phase 2 stubs ----

    public function executeSubscriptions(sfWebRequest $request)
    {
        $this->checkAdmin();
        require_once __DIR__ . '/../../../lib/Repositories/SharePointSubscriptionRepository.php';
        $repo = new \AtomExtensions\SharePoint\Repositories\SharePointSubscriptionRepository();
        // TODO render: list subs grouped by drive with countdown to expiry.
        $this->subscriptions = \Illuminate\Database\Capsule\Manager::table('sharepoint_subscription')
            ->orderBy('expires_at')
            ->get();
        return sfView::SUCCESS;
    }

    public function executeEvents(sfWebRequest $request)
    {
        $this->checkAdmin();
        $status = $request->getParameter('status');
        $query = \Illuminate\Database\Capsule\Manager::table('sharepoint_event')
            ->orderByDesc('received_at')
            ->limit(200);
        if ($status) {
            $query->where('status', $status);
        }
        $this->events = $query->get();
        $this->statusFilter = $status;
        return sfView::SUCCESS;
    }

    public function executeEventDetail(sfWebRequest $request)
    {
        $this->checkAdmin();
        $id = (int) $request->getParameter('id');
        $this->event = \Illuminate\Database\Capsule\Manager::table('sharepoint_event')
            ->where('id', $id)
            ->first();
        if ($this->event === null) {
            $this->forward404();
        }
        // POST = retry: re-dispatch the queue job for this event.
        if ($request->isMethod(sfRequest::POST) && $request->getParameter('form_action') === 'retry') {
            if (class_exists('\\AtomFramework\\Services\\QueueService')) {
                \AtomFramework\Services\QueueService::dispatch(
                    'sharepoint:ingest-event',
                    ['event_id' => $id],
                    'integrations',
                );
                \Illuminate\Database\Capsule\Manager::table('sharepoint_event')
                    ->where('id', $id)
                    ->update(['status' => 'queued', 'last_error' => null]);
            }
            $this->redirect(['module' => 'sharepoint', 'action' => 'eventDetail', 'id' => $id]);
        }
        return sfView::SUCCESS;
    }

    /**
     * PUBLIC, NO CSRF, NO AUTH. Graph webhook receiver.
     *
     * Two flows handled (Phase 2.A):
     *   1. Subscription validation handshake — Graph GETs ?validationToken=...
     *      We MUST echo it as text/plain 200 within 10s.
     *   2. Notification delivery — Graph POSTs JSON {value:[{...},{...}]}
     *      We validate clientState, INSERT sharepoint_event rows, enqueue
     *      sharepoint:ingest-event jobs, return 202.
     */
    public function executeWebhook(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('text/plain');

        // Validation handshake — Graph echoes a token to confirm the URL works.
        $validationToken = $request->getParameter('validationToken');
        if ($validationToken !== null && $validationToken !== '') {
            $this->getResponse()->setStatusCode(200);
            $this->renderText($validationToken);
            return sfView::NONE;
        }

        // Reject anything that isn't POST.
        if (!$request->isMethod(sfRequest::POST)) {
            $this->getResponse()->setStatusCode(405);
            $this->renderText('Method not allowed');
            return sfView::NONE;
        }

        $rawBody = file_get_contents('php://input');
        $payload = json_decode($rawBody ?: '{}', true);
        if (!is_array($payload)) {
            $this->getResponse()->setStatusCode(400);
            $this->renderText('Invalid JSON');
            return sfView::NONE;
        }

        require_once __DIR__ . '/../../../lib/Services/SharePointWebhookHandler.php';
        require_once __DIR__ . '/../../../lib/Repositories/SharePointSubscriptionRepository.php';
        require_once __DIR__ . '/../../../lib/Repositories/SharePointEventRepository.php';

        $handler = new \AtomExtensions\SharePoint\Services\SharePointWebhookHandler(
            new \AtomExtensions\SharePoint\Repositories\SharePointSubscriptionRepository(),
            new \AtomExtensions\SharePoint\Repositories\SharePointEventRepository(),
        );

        $result = $handler->handleNotifications($payload);

        $this->getResponse()->setStatusCode(202);
        $this->getResponse()->setContentType('application/json');
        $this->renderText(json_encode([
            'accepted' => $result['accepted'],
            'dropped' => $result['dropped'],
        ]));
        return sfView::NONE;
    }

    // ---- Phase 2.B — User mapping admin ----

    public function executeUserMappings(sfWebRequest $request)
    {
        $this->checkAdmin();
        require_once __DIR__ . '/../../../lib/Repositories/SharePointUserMappingRepository.php';
        $repo = new \AtomExtensions\SharePoint\Repositories\SharePointUserMappingRepository();
        $this->mappings = $repo->all();
        return sfView::SUCCESS;
    }

    public function executeUserMappingEdit(sfWebRequest $request)
    {
        $this->checkAdmin();
        require_once __DIR__ . '/../../../lib/Repositories/SharePointUserMappingRepository.php';
        $repo = new \AtomExtensions\SharePoint\Repositories\SharePointUserMappingRepository();
        $id = (int) $request->getParameter('id');
        $this->mapping = $id > 0 ? \Illuminate\Database\Capsule\Manager::table('sharepoint_user_mapping')->where('id', $id)->first() : null;

        if ($request->isMethod(sfRequest::POST) && $request->getParameter('form_action') === 'delete' && $id > 0) {
            $repo->delete($id);
            $this->redirect(['module' => 'sharepoint', 'action' => 'userMappings']);
        }
        return sfView::SUCCESS;
    }

    // ---- Phase 2.B — Push endpoints (PUBLIC, AAD-bearer-authed, NO CSRF) ----

    /**
     * POST /api/v2/sharepoint/push/projection
     * Body: { tenant_id, drive_id, items: [{site_id, drive_id, item_id}] }
     * Returns 200 JSON: [ {sp_item_id, metadata, disposition, name, mimeType, size}, ... ]
     */
    public function executePushProjection(sfWebRequest $request)
    {
        return $this->handlePushAction($request, function (array $body, array $claims) {
            require_once __DIR__ . '/../../../lib/Services/SharePointMappingService.php';
            require_once __DIR__ . '/../../../lib/Services/SharePointRetentionMapper.php';
            require_once __DIR__ . '/../../../lib/Services/GraphClientService.php';
            require_once __DIR__ . '/../../../lib/Services/SharePointPushService.php';
            require_once __DIR__ . '/../../../lib/Repositories/SharePointTenantRepository.php';
            require_once __DIR__ . '/../../../lib/Repositories/SharePointDriveRepository.php';

            $svc = new \AtomExtensions\SharePoint\Services\SharePointPushService(
                new \AtomExtensions\SharePoint\Services\GraphClientService(),
                new \AtomExtensions\SharePoint\Repositories\SharePointTenantRepository(),
                new \AtomExtensions\SharePoint\Repositories\SharePointDriveRepository(),
                new \AtomExtensions\SharePoint\Services\SharePointMappingService(),
                new \AtomExtensions\SharePoint\Services\SharePointRetentionMapper(),
            );
            return $svc->project($body, $claims);
        });
    }

    /**
     * POST /api/v2/sharepoint/push
     * Body: { tenant_id, drive_id, repository_id?, parent_id?, items: [{...,metadata}] }
     * Returns 201 JSON: { ingest_job_id, ingest_session_id }
     */
    public function executePush(sfWebRequest $request)
    {
        return $this->handlePushAction($request, function (array $body, array $claims) {
            require_once __DIR__ . '/../../../lib/Services/SharePointMappingService.php';
            require_once __DIR__ . '/../../../lib/Services/SharePointRetentionMapper.php';
            require_once __DIR__ . '/../../../lib/Services/GraphClientService.php';
            require_once __DIR__ . '/../../../lib/Services/SharePointPushService.php';
            require_once __DIR__ . '/../../../lib/Services/SharePointUserMappingService.php';
            require_once __DIR__ . '/../../../lib/Repositories/SharePointTenantRepository.php';
            require_once __DIR__ . '/../../../lib/Repositories/SharePointDriveRepository.php';
            require_once __DIR__ . '/../../../lib/Repositories/SharePointUserMappingRepository.php';

            $userMap = new \AtomExtensions\SharePoint\Services\SharePointUserMappingService(
                new \AtomExtensions\SharePoint\Repositories\SharePointUserMappingRepository(),
            );
            $userId = $userMap->resolve($claims);
            if ($userId === null) {
                throw new \RuntimeException('AAD user not mapped and auto-create disabled');
            }

            $svc = new \AtomExtensions\SharePoint\Services\SharePointPushService(
                new \AtomExtensions\SharePoint\Services\GraphClientService(),
                new \AtomExtensions\SharePoint\Repositories\SharePointTenantRepository(),
                new \AtomExtensions\SharePoint\Repositories\SharePointDriveRepository(),
                new \AtomExtensions\SharePoint\Services\SharePointMappingService(),
                new \AtomExtensions\SharePoint\Services\SharePointRetentionMapper(),
            );
            $jobId = $svc->commit($body, $userId, $claims);
            return ['ingest_job_id' => $jobId];
        });
    }

    /**
     * GET /api/v2/sharepoint/push/jobs/{id} — poll ingest_job status for the SPFx dialog.
     */
    public function executePushJob(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');
        try {
            $claims = $this->validateBearer($request);
            if ($claims === null) {
                $this->getResponse()->setStatusCode(401);
                $this->renderText(json_encode(['error' => 'unauthorized']));
                return sfView::NONE;
            }
            $id = (int) $request->getParameter('id');
            $row = \Illuminate\Database\Capsule\Manager::table('ingest_job')->where('id', $id)->first();
            if ($row === null) {
                $this->getResponse()->setStatusCode(404);
                $this->renderText(json_encode(['error' => 'not_found']));
                return sfView::NONE;
            }
            $this->renderText(json_encode([
                'id' => (int) $row->id,
                'status' => $row->status ?? null,
                'progress' => $row->progress ?? null,
                'error' => $row->error_message ?? null,
                'primary_object_id' => isset($row->primary_object_id) ? (int) $row->primary_object_id : null,
            ]));
        } catch (\Throwable $e) {
            $this->getResponse()->setStatusCode(500);
            $this->renderText(json_encode(['error' => $e->getMessage()]));
        }
        return sfView::NONE;
    }

    // ---- Phase 3 stubs ----

    public function executeFederatedSearch(sfWebRequest $request)
    {
        return $this->phaseUnavailable(3);
    }

    /**
     * Common flow for push endpoints: validate AAD JWT, decode body, run the
     * supplied closure, return the result as JSON.
     */
    private function handlePushAction(sfWebRequest $request, callable $closure): string
    {
        $this->getResponse()->setContentType('application/json');
        if (!$request->isMethod(sfRequest::POST)) {
            $this->getResponse()->setStatusCode(405);
            $this->renderText(json_encode(['error' => 'method_not_allowed']));
            return sfView::NONE;
        }
        try {
            $claims = $this->validateBearer($request);
            if ($claims === null) {
                $this->getResponse()->setStatusCode(401);
                $this->renderText(json_encode(['error' => 'unauthorized']));
                return sfView::NONE;
            }
            $rawBody = file_get_contents('php://input');
            $body = json_decode($rawBody ?: '{}', true);
            if (!is_array($body)) {
                $this->getResponse()->setStatusCode(400);
                $this->renderText(json_encode(['error' => 'invalid_json']));
                return sfView::NONE;
            }
            $result = $closure($body, $claims);
            $this->renderText(json_encode($result));
        } catch (\Throwable $e) {
            $this->getResponse()->setStatusCode(500);
            $this->renderText(json_encode(['error' => $e->getMessage()]));
        }
        return sfView::NONE;
    }

    /**
     * Pull bearer token from Authorization header, validate via JWT validator.
     * Returns claims array or null on failure.
     */
    private function validateBearer(sfWebRequest $request): ?array
    {
        $authHeader = $request->getHttpHeader('Authorization');
        if (!$authHeader || stripos($authHeader, 'Bearer ') !== 0) {
            return null;
        }
        $token = trim(substr($authHeader, 7));
        if ($token === '') {
            return null;
        }
        $tenantId = (int) ($request->getParameter('tenant_id') ?? 0);
        if ($tenantId <= 0) {
            // Try body
            $body = json_decode(file_get_contents('php://input') ?: '{}', true);
            $tenantId = (int) ($body['tenant_id'] ?? 0);
        }
        if ($tenantId <= 0) {
            return null;
        }

        require_once __DIR__ . '/../../../lib/Services/GraphTokenValidatorService.php';
        require_once __DIR__ . '/../../../lib/Repositories/SharePointTenantRepository.php';
        $validator = new \AtomExtensions\SharePoint\Services\GraphTokenValidatorService(
            new \AtomExtensions\SharePoint\Repositories\SharePointTenantRepository(),
        );
        try {
            return $validator->validate($token, $tenantId);
        } catch (\Throwable $e) {
            return null;
        }
    }

    // ---- helpers ----

    private function checkAdmin(): void
    {
        if (!\sfContext::getInstance()->getUser()->hasGroup(\QubitAclGroup::ADMINISTRATOR_ID)) {
            $this->forward('admin', 'secure');
        }
    }

    // ─── Phase 2 (v2 ingest plan) — rules + mapping templates admin ─────

    public function executeRules(sfWebRequest $request)
    {
        $this->checkAdmin();
        $this->rules = \Illuminate\Database\Capsule\Manager::table('sharepoint_ingest_rule')
            ->leftJoin('sharepoint_drive', 'sharepoint_ingest_rule.drive_id', '=', 'sharepoint_drive.id')
            ->select(
                'sharepoint_ingest_rule.*',
                'sharepoint_drive.drive_name as drive_name',
                'sharepoint_drive.site_title as site_title',
            )
            ->orderBy('sharepoint_ingest_rule.name')
            ->get();
        $this->drives = \Illuminate\Database\Capsule\Manager::table('sharepoint_drive')
            ->orderBy('site_title')
            ->get();
        return sfView::SUCCESS;
    }

    public function executeRuleEdit(sfWebRequest $request)
    {
        $this->checkAdmin();
        $id = (int) $request->getParameter('id');
        $this->rule = $id > 0
            ? \Illuminate\Database\Capsule\Manager::table('sharepoint_ingest_rule')->where('id', $id)->first()
            : null;
        $this->drives = \Illuminate\Database\Capsule\Manager::table('sharepoint_drive')
            ->orderBy('site_title')
            ->get();
        $this->repositories = \Illuminate\Database\Capsule\Manager::table('repository')
            ->join('actor_i18n', function ($j) {
                $j->on('actor_i18n.id', '=', 'repository.id')
                  ->where('actor_i18n.culture', '=', 'en');
            })
            ->select('repository.id', 'repository.identifier', 'actor_i18n.authorized_form_of_name AS name')
            ->orderBy('actor_i18n.authorized_form_of_name')
            ->get();
        $this->parentLabel = null;
        if ($this->rule && !empty($this->rule->parent_id)) {
            $this->parentLabel = \Illuminate\Database\Capsule\Manager::table('information_object')
                ->join('information_object_i18n', 'information_object.id', '=', 'information_object_i18n.id')
                ->where('information_object.id', (int) $this->rule->parent_id)
                ->select('information_object.id', 'information_object.identifier', 'information_object_i18n.title')
                ->first();
        }
        $this->templatesByDrive = \Illuminate\Database\Capsule\Manager::table('sharepoint_mapping_template')
            ->orderBy('drive_id')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->groupBy('drive_id');
        return sfView::SUCCESS;
    }

    public function executeRuleSave(sfWebRequest $request)
    {
        $this->checkAdmin();
        if (!$request->isMethod('post')) {
            $this->redirect(['module' => 'sharepoint', 'action' => 'rules']);
        }
        $id = (int) $request->getParameter('id');
        $processFlags = [];
        foreach (['virus_scan', 'ocr', 'ner', 'summarize', 'spellcheck', 'translate', 'format_id', 'face_detect'] as $f) {
            $processFlags[$f] = $request->getParameter('process_' . $f) ? 1 : 0;
        }
        $attrs = [
            'drive_id' => (int) $request->getParameter('drive_id'),
            'template_id' => $request->getParameter('template_id') ? (int) $request->getParameter('template_id') : null,
            'name' => (string) $request->getParameter('name'),
            'folder_path' => $request->getParameter('folder_path') ?: null,
            'file_pattern' => $request->getParameter('file_pattern') ?: null,
            'retention_label' => ($request->getParameter('retention_mode') === 'on')
                ? ($request->getParameter('retention_label') ?: null)
                : null,
            'sector' => $request->getParameter('sector') ?: 'archive',
            'standard' => $request->getParameter('standard') ?: 'isadg',
            'repository_id' => $request->getParameter('repository_id') ? (int) $request->getParameter('repository_id') : null,
            'parent_id' => $request->getParameter('parent_id') ? (int) $request->getParameter('parent_id') : null,
            'parent_placement' => $request->getParameter('parent_placement') ?: 'top_level',
            'process_flags' => json_encode($processFlags),
            'schedule_cron' => $request->getParameter('schedule_cron') ?: '*/15 * * * *',
            'is_enabled' => $request->getParameter('is_enabled') ? 1 : 0,
        ];
        if ($id > 0) {
            \Illuminate\Database\Capsule\Manager::table('sharepoint_ingest_rule')->where('id', $id)->update($attrs);
        } else {
            \Illuminate\Database\Capsule\Manager::table('sharepoint_ingest_rule')->insert($attrs);
        }
        $this->getUser()->setFlash('notice', 'Rule saved.');
        $this->redirect(['module' => 'sharepoint', 'action' => 'rules']);
    }

    public function executeRuleDelete(sfWebRequest $request)
    {
        $this->checkAdmin();
        $id = (int) $request->getParameter('id');
        \Illuminate\Database\Capsule\Manager::table('sharepoint_ingest_rule')->where('id', $id)->delete();
        $this->getUser()->setFlash('notice', 'Rule deleted.');
        $this->redirect(['module' => 'sharepoint', 'action' => 'rules']);
    }

    public function executeRuleRun(sfWebRequest $request)
    {
        $this->checkAdmin();
        $id = (int) $request->getParameter('id');
        // Fire the CLI task in the background so the request returns immediately.
        $atomRoot = sfConfig::get('sf_root_dir');
        $cmd = "cd " . escapeshellarg($atomRoot)
            . " && nohup php symfony sharepoint:auto-ingest --rule=" . (int) $id . " --force"
            . " >> /var/log/atom/sp-autoingest.log 2>&1 &";
        @exec($cmd);
        $this->getUser()->setFlash('notice', "Rule #{$id} scheduled to run in background.");
        $this->redirect(['module' => 'sharepoint', 'action' => 'rules']);
    }

    public function executeMappings(sfWebRequest $request)
    {
        $this->checkAdmin();
        // Load IngestService so the template can call getTargetFields()
        $svcPath = sfConfig::get('sf_plugins_dir') . '/ahgIngestPlugin/lib/Services/IngestService.php';
        if (file_exists($svcPath) && !class_exists('\AhgIngestPlugin\Services\IngestService')) {
            require_once $svcPath;
        }
        $driveId = (int) $request->getParameter('drive_id');
        $templateRaw = (string) $request->getParameter('template_id', '');
        $isNew = ($templateRaw === 'new');
        $templateId = $isNew ? 0 : (int) $templateRaw;
        $this->drives = \Illuminate\Database\Capsule\Manager::table('sharepoint_drive')
            ->orderBy('site_title')
            ->get();
        $this->selectedDriveId = $driveId;
        $this->templates = collect();
        $this->selectedTemplate = null;
        $this->mappings = collect();
        if ($driveId > 0) {
            $this->templates = \Illuminate\Database\Capsule\Manager::table('sharepoint_mapping_template')
                ->where('drive_id', $driveId)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get();
            if (!$isNew) {
                if ($templateId > 0) {
                    $this->selectedTemplate = $this->templates->firstWhere('id', $templateId);
                }
                if (!$this->selectedTemplate) {
                    $this->selectedTemplate = $this->templates->firstWhere('is_default', 1) ?: $this->templates->first();
                }
                if ($this->selectedTemplate) {
                    $this->mappings = \Illuminate\Database\Capsule\Manager::table('sharepoint_mapping')
                        ->where('template_id', $this->selectedTemplate->id)
                        ->orderBy('sort_order')
                        ->get();
                }
            }
        }
        return sfView::SUCCESS;
    }

    public function executeMappingsSave(sfWebRequest $request)
    {
        $this->checkAdmin();
        if (!$request->isMethod('post')) {
            $this->redirect(['module' => 'sharepoint', 'action' => 'mappings']);
        }
        $driveId = (int) $request->getParameter('drive_id');
        $templateId = (int) $request->getParameter('template_id');
        $name = trim((string) $request->getParameter('template_name', ''));
        $sector = (string) $request->getParameter('sector', 'archive');
        $standard = (string) $request->getParameter('standard', 'isadg');
        $isDefault = $request->getParameter('is_default') ? 1 : 0;

        if ($driveId <= 0) {
            $this->getUser()->setFlash('error', 'Drive id required.');
            $this->redirect(['module' => 'sharepoint', 'action' => 'mappings']);
        }
        if ($name === '') {
            $this->getUser()->setFlash('error', 'Template name is required.');
            $this->redirect(['module' => 'sharepoint', 'action' => 'mappings', 'drive_id' => $driveId, 'template_id' => $templateId]);
        }

        $db = \Illuminate\Database\Capsule\Manager::connection();
        $db->transaction(function () use ($db, &$templateId, $driveId, $name, $sector, $standard, $isDefault, $request) {
            $now = date('Y-m-d H:i:s');
            if ($templateId > 0) {
                $db->table('sharepoint_mapping_template')
                    ->where('id', $templateId)
                    ->where('drive_id', $driveId)
                    ->update([
                        'name' => $name,
                        'sector' => $sector,
                        'standard' => $standard,
                        'is_default' => $isDefault,
                        'updated_at' => $now,
                    ]);
            } else {
                $templateId = (int) $db->table('sharepoint_mapping_template')->insertGetId([
                    'drive_id' => $driveId,
                    'name' => $name,
                    'sector' => $sector,
                    'standard' => $standard,
                    'is_default' => $isDefault,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            if ($isDefault) {
                $db->table('sharepoint_mapping_template')
                    ->where('drive_id', $driveId)
                    ->where('id', '!=', $templateId)
                    ->update(['is_default' => 0, 'updated_at' => $now]);
            }
            $db->table('sharepoint_mapping')->where('template_id', $templateId)->delete();
            $sourceFields = (array) $request->getParameter('source_field', []);
            $targetFields = (array) $request->getParameter('target_field', []);
            $transforms = (array) $request->getParameter('transform', []);
            $defaults = (array) $request->getParameter('default_value', []);
            foreach ($sourceFields as $i => $src) {
                if (trim((string) $src) === '' || trim((string) ($targetFields[$i] ?? '')) === '') {
                    continue;
                }
                $db->table('sharepoint_mapping')->insert([
                    'drive_id' => $driveId,
                    'template_id' => $templateId,
                    'source_field' => $src,
                    'target_field' => $targetFields[$i],
                    'target_standard' => $standard,
                    'transform' => $transforms[$i] ?? null,
                    'default_value' => $defaults[$i] ?? null,
                    'sort_order' => $i,
                    'is_required' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });

        $this->getUser()->setFlash('notice', 'Mapping template saved.');
        $this->redirect(['module' => 'sharepoint', 'action' => 'mappings', 'drive_id' => $driveId, 'template_id' => $templateId]);
    }

    public function executeMappingTemplateDelete(sfWebRequest $request)
    {
        $this->checkAdmin();
        if (!$request->isMethod('post')) {
            $this->redirect(['module' => 'sharepoint', 'action' => 'mappings']);
        }
        $driveId = (int) $request->getParameter('drive_id');
        $templateId = (int) $request->getParameter('template_id');
        if ($templateId > 0 && $driveId > 0) {
            \Illuminate\Database\Capsule\Manager::table('sharepoint_mapping_template')
                ->where('id', $templateId)
                ->where('drive_id', $driveId)
                ->delete(); // FK cascade removes mapping rows
            $this->getUser()->setFlash('notice', 'Template deleted.');
        }
        $this->redirect(['module' => 'sharepoint', 'action' => 'mappings', 'drive_id' => $driveId]);
    }

    private function phaseUnavailable(int $phase): string
    {
        $this->getResponse()->setStatusCode(503);
        $this->renderText("Action belongs to Phase {$phase} of ahgSharePointPlugin (not yet shipped).");
        return sfView::NONE;
    }
}
