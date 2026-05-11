<?php

/**
 * shareLink module — recipient landing + issuance controllers.
 *
 * Routes (registered in plugin Configuration):
 *   GET  /share/:token         → shareLink/recipient  (public, no auth)
 *   POST /shareLink/issue      → shareLink/issue      (authenticated, JSON)
 *
 * The recipient action is intentionally PUBLIC — no auth required. The token
 * is the credential. AccessService runs all validation guards.
 *
 * The issue action requires an authenticated user and runs every guard in
 * IssueService.
 *
 * @phase D, E
 */
class shareLinkActions extends sfActions
{
    /**
     * POST /shareLink/issue
     *
     * Body fields:
     *   information_object_id   (int, required)
     *   expires_at              (string, ISO 8601 or YYYY-MM-DD HH:MM:SS; optional)
     *   recipient_email         (string, optional)
     *   recipient_note          (string, optional)
     *   max_access              (int, optional)
     *
     * Returns JSON. On success:
     *   {ok: true, token, token_id, expires_at, public_url}
     * On failure:
     *   {ok: false, error: {code, message}}
     *
     * @phase E
     */
    public function executeIssue(sfWebRequest $request): void
    {
        $this->getResponse()->setContentType('application/json; charset=utf-8');

        if (!$request->isMethod('POST')) {
            $this->renderJsonError('method_not_allowed', 'POST required', 405);
            return;
        }

        $user = $this->getUser();
        if (!$user || !$user->isAuthenticated()) {
            $this->renderJsonError('not_authenticated', 'Authentication required', 401);
            return;
        }
        $userId = (int) $user->getUserID();
        if ($userId <= 0) {
            $this->renderJsonError('not_authenticated', 'No user id on session', 401);
            return;
        }

        $ioId = (int) $request->getParameter('information_object_id');
        if ($ioId <= 0) {
            $this->renderJsonError('invalid_request', 'information_object_id is required', 422);
            return;
        }

        $expiresAtParam = trim((string) $request->getParameter('expires_at'));
        $expiresAt = null;
        if ($expiresAtParam !== '') {
            try {
                $expiresAt = new \DateTimeImmutable($expiresAtParam);
            } catch (\Throwable $e) {
                $this->renderJsonError('invalid_request', 'expires_at could not be parsed', 422);
                return;
            }
        }

        $recipientEmail = trim((string) $request->getParameter('recipient_email')) ?: null;
        if ($recipientEmail !== null && !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            $this->renderJsonError('invalid_request', 'recipient_email is not a valid email address', 422);
            return;
        }
        $recipientNote = trim((string) $request->getParameter('recipient_note')) ?: null;
        $maxAccessRaw = $request->getParameter('max_access');
        $maxAccess = null;
        if ($maxAccessRaw !== null && $maxAccessRaw !== '') {
            if (!is_numeric($maxAccessRaw) || (int) $maxAccessRaw < 1) {
                $this->renderJsonError('invalid_request', 'max_access must be a positive integer', 422);
                return;
            }
            $maxAccess = (int) $maxAccessRaw;
        }

        $libDir = realpath(__DIR__ . '/../../../lib/Services');
        require_once $libDir . '/ShareLinkException.php';
        require_once $libDir . '/NotAuthenticatedException.php';
        require_once $libDir . '/PermissionDeniedException.php';
        require_once $libDir . '/InsufficientClearanceException.php';
        require_once $libDir . '/ExpiryCapExceededException.php';
        require_once $libDir . '/InvalidRequestException.php';
        require_once $libDir . '/TokenService.php';
        require_once $libDir . '/AclCheck.php';
        require_once $libDir . '/ClearanceCheck.php';
        require_once $libDir . '/IssueService.php';

        try {
            $result = (new \AhgShareLink\Services\IssueService())->issue(
                userId: $userId,
                informationObjectId: $ioId,
                expiresAt: $expiresAt,
                recipientEmail: $recipientEmail,
                recipientNote: $recipientNote,
                maxAccess: $maxAccess,
            );

            // Build absolute URL (issue service returned relative form).
            $absolute = $result['public_url'] ?? null;
            if ($absolute !== null && !preg_match('#^https?://#i', $absolute)) {
                $absolute = $request->getUriPrefix() . $absolute;
            }
            // Fallback: synthesize the URL directly from the token if helper failed.
            if ($absolute === null && isset($result['token'])) {
                $absolute = $request->getUriPrefix() . '/share/' . $result['token'];
            }

            $this->renderJson([
                'ok'         => true,
                'token'      => $result['token'],
                'token_id'   => $result['token_id'],
                'expires_at' => $result['expires_at'],
                'public_url' => $absolute,
            ], 201);
            return;
        } catch (\AhgShareLink\Services\NotAuthenticatedException $e) {
            $this->renderJsonError('not_authenticated', $e->getMessage(), 401);
        } catch (\AhgShareLink\Services\PermissionDeniedException $e) {
            $this->renderJsonError('permission_denied', $e->getMessage(), 403);
        } catch (\AhgShareLink\Services\InsufficientClearanceException $e) {
            $this->renderJsonError('insufficient_clearance', $e->getMessage(), 403);
        } catch (\AhgShareLink\Services\ExpiryCapExceededException $e) {
            $this->renderJsonError('expiry_cap_exceeded', $e->getMessage(), 422);
        } catch (\AhgShareLink\Services\InvalidRequestException $e) {
            $this->renderJsonError('invalid_request', $e->getMessage(), 422);
        } catch (\Throwable $e) {
            error_log('shareLink/issue unexpected: ' . $e->getMessage());
            $this->renderJsonError('server_error', 'An unexpected error occurred', 500);
        }
    }

    private function renderJson(array $payload, int $status = 200): void
    {
        $this->getResponse()->setStatusCode($status);
        $this->renderText(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $this->setLayout(false);
    }

    private function renderJsonError(string $code, string $message, int $status): void
    {
        $this->renderJson(['ok' => false, 'error' => ['code' => $code, 'message' => $message]], $status);
    }

    /**
     * GET /admin/share-links — admin index of all share links.
     *
     * Query params:
     *   status   active|expired|revoked|all  (default active)
     *   q        free-text search (matches token, recipient_email, IO title)
     *   issuer   user_id filter
     *   page     1-based page number
     *
     * Page size is fixed at 25.
     *
     * Permission: admin (group 100) bypass OR `share_link.list_all` ACL.
     *
     * @phase F
     */
    public function executeAdmin(sfWebRequest $request): void
    {
        [$ok, $error] = $this->checkAdminAccess();
        if (!$ok) {
            $this->getResponse()->setStatusCode($error['http']);
            $this->errorMessage = $error['message'];
            $this->setTemplate('error');
            return;
        }

        $this->loadServices();
        $filters = $this->parseAdminFilters($request);
        $page = max(1, (int) $request->getParameter('page', 1));
        $pageSize = 25;

        [$tokens, $total] = $this->queryAdminList($filters, $page, $pageSize);

        $this->tokens = $tokens;
        $this->totalCount = $total;
        $this->page = $page;
        $this->pageSize = $pageSize;
        $this->totalPages = (int) ceil(max(1, $total) / $pageSize);
        $this->filters = $filters;
        $this->issuers = $this->fetchIssuerOptions();
    }

    /**
     * GET /admin/share-links/:id — admin detail view of one share link.
     * Lists the access log for that token.
     *
     * Permission: same as executeAdmin.
     *
     * @phase F
     */
    public function executeAdminShow(sfWebRequest $request): void
    {
        [$ok, $error] = $this->checkAdminAccess();
        if (!$ok) {
            $this->getResponse()->setStatusCode($error['http']);
            $this->errorMessage = $error['message'];
            $this->setTemplate('error');
            return;
        }

        $tokenId = (int) $request->getParameter('id');
        if ($tokenId <= 0) {
            $this->forward404();
        }

        $this->loadServices();
        $DB = '\\Illuminate\\Database\\Capsule\\Manager';

        $row = $DB::table('information_object_share_token')->where('id', $tokenId)->first();
        if (!$row) {
            $this->forward404();
        }

        $issuer = $DB::table('user')->where('id', $row->issued_by)->first();
        $i18n = $DB::table('information_object_i18n')->where('id', $row->information_object_id)->orderBy('culture')->first();
        $accessLog = $DB::table('information_object_share_access')
            ->where('token_id', $tokenId)
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $this->tokenRow = $row;
        $this->issuerName = $issuer ? ($issuer->username ?: ('user #' . $row->issued_by)) : '(unknown)';
        $this->issuerEmail = $issuer ? ($issuer->email ?? null) : null;
        $this->ioTitle = $i18n ? ($i18n->title ?? ('#' . $row->information_object_id)) : ('#' . $row->information_object_id);
        $this->accessLog = $accessLog;
        $this->status = $this->resolveTokenStatus($row);
    }

    /**
     * POST /admin/share-links/:id/revoke — revoke a share-link token.
     *
     * Permission: must pass admin-access gate (share_link.list_all). The
     * RevokeService enforces own-vs-others gating internally.
     *
     * @phase G
     */
    public function executeRevoke(sfWebRequest $request): void
    {
        if (!$request->isMethod('POST')) {
            $this->getResponse()->setStatusCode(405);
            $this->errorMessage = 'POST required';
            $this->setTemplate('error');
            return;
        }

        [$ok, $error] = $this->checkAdminAccess();
        if (!$ok) {
            $this->getResponse()->setStatusCode($error['http']);
            $this->errorMessage = $error['message'];
            $this->setTemplate('error');
            return;
        }

        $tokenId = (int) $request->getParameter('id');
        if ($tokenId <= 0) {
            $this->forward404();
        }

        $libDir = realpath(__DIR__ . '/../../../lib/Services');
        require_once $libDir . '/ShareLinkException.php';
        require_once $libDir . '/NotAuthenticatedException.php';
        require_once $libDir . '/PermissionDeniedException.php';
        require_once $libDir . '/InvalidRequestException.php';
        require_once $libDir . '/AclCheck.php';
        require_once $libDir . '/RevokeService.php';

        $user = $this->getUser();
        $userId = (int) $user->getUserID();
        $reason = trim((string) $request->getParameter('reason')) ?: null;

        try {
            $result = (new \AhgShareLink\Services\RevokeService())
                ->revoke(userId: $userId, tokenId: $tokenId, reason: $reason);
        } catch (\AhgShareLink\Services\PermissionDeniedException $e) {
            $this->getResponse()->setStatusCode(403);
            $this->errorMessage = $e->getMessage();
            $this->setTemplate('error');
            return;
        } catch (\AhgShareLink\Services\InvalidRequestException $e) {
            $this->forward404();
        }

        $flashKey = $result['was_already_revoked'] ? 'info' : 'success';
        $flashMsg = $result['was_already_revoked']
            ? __('This share link was already revoked.')
            : __('Share link revoked.');
        $user->setFlash($flashKey, $flashMsg);

        $backUrl = (string) $request->getParameter('back');
        if ($backUrl === '' || !preg_match('#^/admin/share-links#', $backUrl)) {
            $backUrl = $this->generateUrl('share_link_admin', []);
        }
        $this->redirect($backUrl);
    }

    /** @return array{0:bool, 1:array{http:int,message:string}|null} */
    private function checkAdminAccess(): array
    {
        $user = $this->getUser();
        if (!$user || !$user->isAuthenticated()) {
            return [false, ['http' => 401, 'message' => 'Authentication required']];
        }
        $userId = (int) $user->getUserID();
        if ($userId <= 0) {
            return [false, ['http' => 401, 'message' => 'No user id on session']];
        }

        $this->loadServices();
        $acl = new \AhgShareLink\Services\AclCheck();
        if (!$acl->canUserDo($userId, \AhgShareLink\Services\AclCheck::ACTION_LIST_ALL)) {
            return [false, ['http' => 403, 'message' => 'You do not have permission to view share links']];
        }
        return [true, null];
    }

    /** Resolve the canonical lifecycle status of a token row. */
    private function resolveTokenStatus(object $row): string
    {
        if (!empty($row->revoked_at)) {
            return 'revoked';
        }
        if (strtotime((string) $row->expires_at) <= time()) {
            return 'expired';
        }
        if ($row->max_access !== null && (int) $row->access_count >= (int) $row->max_access) {
            return 'exhausted';
        }
        return 'active';
    }

    private function loadServices(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $libDir = realpath(__DIR__ . '/../../../lib/Services');
        require_once $libDir . '/AclCheck.php';
        $loaded = true;
    }

    /**
     * @return array{status:string,q:string,issuer:?int}
     */
    private function parseAdminFilters(sfWebRequest $request): array
    {
        $status = (string) $request->getParameter('status', 'active');
        if (!in_array($status, ['active', 'expired', 'revoked', 'exhausted', 'all'], true)) {
            $status = 'active';
        }
        $q = trim((string) $request->getParameter('q', ''));
        $issuer = $request->getParameter('issuer');
        $issuerId = ($issuer !== null && $issuer !== '' && is_numeric($issuer)) ? (int) $issuer : null;
        return ['status' => $status, 'q' => $q, 'issuer' => $issuerId];
    }

    /**
     * @return array{0:array<int,object>, 1:int}
     */
    private function queryAdminList(array $filters, int $page, int $pageSize): array
    {
        $DB = '\\Illuminate\\Database\\Capsule\\Manager';

        $base = $DB::table('information_object_share_token as t')
            ->leftJoin('user as u', 't.issued_by', '=', 'u.id')
            ->leftJoin('information_object_i18n as i18n', function ($j) {
                $j->on('i18n.id', '=', 't.information_object_id');
            });

        $base = $this->applyFilters($base, $filters);

        // total — use a subquery clone to keep group-by behavior consistent.
        $totalQ = clone $base;
        $totalRow = $totalQ->selectRaw('count(distinct t.id) as c')->first();
        $total = (int) ($totalRow->c ?? 0);

        $rows = $base
            ->select(
                't.*',
                'u.username as issuer_username',
                $DB::raw('(SELECT i.title FROM information_object_i18n i WHERE i.id = t.information_object_id ORDER BY i.culture LIMIT 1) as io_title'),
            )
            ->groupBy('t.id')
            ->orderByDesc('t.id')
            ->forPage($page, $pageSize)
            ->get();

        return [$rows, $total];
    }

    private function applyFilters($q, array $filters)
    {
        $now = date('Y-m-d H:i:s');
        switch ($filters['status']) {
            case 'active':
                // Truly active: not revoked, not expired, and either no quota
                // or quota not yet reached. Excludes exhausted tokens.
                $q->whereNull('t.revoked_at')
                    ->where('t.expires_at', '>', $now)
                    ->where(function ($qq) {
                        $qq->whereNull('t.max_access')
                            ->orWhereColumn('t.access_count', '<', 't.max_access');
                    });
                break;
            case 'expired':
                $q->whereNull('t.revoked_at')->where('t.expires_at', '<=', $now);
                break;
            case 'revoked':
                $q->whereNotNull('t.revoked_at');
                break;
            case 'exhausted':
                $q->whereNull('t.revoked_at')
                    ->where('t.expires_at', '>', $now)
                    ->whereNotNull('t.max_access')
                    ->whereColumn('t.access_count', '>=', 't.max_access');
                break;
            case 'all':
            default:
                break;
        }
        if ($filters['issuer'] !== null) {
            $q->where('t.issued_by', $filters['issuer']);
        }
        if ($filters['q'] !== '') {
            $needle = '%' . $filters['q'] . '%';
            $q->where(function ($qq) use ($needle) {
                $qq->where('t.token', 'like', $needle)
                    ->orWhere('t.recipient_email', 'like', $needle)
                    ->orWhere('i18n.title', 'like', $needle);
            });
        }
        return $q;
    }

    /**
     * @return array<int,object> users who have issued at least one share link
     */
    private function fetchIssuerOptions(): array
    {
        $DB = '\\Illuminate\\Database\\Capsule\\Manager';
        return $DB::table('information_object_share_token as t')
            ->leftJoin('user as u', 't.issued_by', '=', 'u.id')
            ->select('t.issued_by', 'u.username')
            ->distinct()
            ->orderBy('u.username')
            ->get()
            ->all();
    }

    public function executeRecipient(sfWebRequest $request): void
    {
        $token = (string) $request->getParameter('token');
        if ($token === '') {
            $this->forward404();
        }

        $libDir = realpath(__DIR__ . '/../../../lib/Services');
        require_once $libDir . '/TokenService.php';
        require_once $libDir . '/AccessResult.php';
        require_once $libDir . '/AccessService.php';

        $svc = new \AhgShareLink\Services\AccessService();
        $result = $svc->evaluate(
            token: $token,
            ip: $request->getRemoteAddress(),
            userAgent: $request->getHttpHeader('User-Agent') ?: null,
        );

        $this->result = $result;
        $this->getResponse()->setStatusCode($result->httpStatus);

        if (!$result->allowed) {
            $this->reason = $result->reason;
            $this->setTemplate('denied');
            return;
        }

        // Load the record details for rendering.
        $tokenRow = $result->tokenRow;
        $ioId = (int) $tokenRow->information_object_id;

        $this->tokenRow = $tokenRow;
        $this->informationObjectId = $ioId;
        $this->expiresAt = $tokenRow->expires_at;

        // Issuer name
        $userRow = \Illuminate\Database\Capsule\Manager::table('user')
            ->where('id', $tokenRow->issued_by)->first();
        $this->issuerName = $userRow ? ($userRow->username ?: ('user #' . $tokenRow->issued_by)) : '(unknown)';

        // Title + scope (current culture, fall back to any culture)
        $culture = $this->getUser()->getCulture() ?: 'en';
        $i18n = \Illuminate\Database\Capsule\Manager::table('information_object_i18n')
            ->where('id', $ioId)->where('culture', $culture)->first();
        if (!$i18n) {
            $i18n = \Illuminate\Database\Capsule\Manager::table('information_object_i18n')
                ->where('id', $ioId)->orderBy('culture')->first();
        }
        $this->title = $i18n->title ?? ('#' . $ioId);
        $this->scopeAndContent = $i18n->scope_and_content ?? null;
        $this->identifier = \Illuminate\Database\Capsule\Manager::table('information_object')
            ->where('id', $ioId)->value('identifier');
    }
}
