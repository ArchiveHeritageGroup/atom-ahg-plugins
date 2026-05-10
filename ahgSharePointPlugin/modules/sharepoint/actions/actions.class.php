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
        // TODO: list registered drives across all tenants.
        return sfView::SUCCESS;
    }

    public function executeDriveBrowse(sfWebRequest $request)
    {
        $this->checkAdmin();
        // TODO: AJAX — given tenantId, GET /sites + drives via Graph; return JSON for picker UI.
        $this->renderText(json_encode(['status' => 'not_implemented']));
        return sfView::NONE;
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

    private function phaseUnavailable(int $phase): string
    {
        $this->getResponse()->setStatusCode(503);
        $this->renderText("Action belongs to Phase {$phase} of ahgSharePointPlugin (not yet shipped).");
        return sfView::NONE;
    }
}
