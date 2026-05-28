<?php

/**
 * iiifContent actions
 *
 * IIIF Content State API endpoints — encode/decode saved-view tokens.
 * Mounted at /iiif/content-state/*
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 * @version 1.0.0
 */
class iiifContentActions extends sfActions
{
    private ?ContentStateService $contentStateService = null;

    protected function getContentStateService(): ContentStateService
    {
        if ($this->contentStateService === null) {
            $baseUrl = sfConfig::get('app_iiif_base_url', 'https://psis.theahg.co.za');
            $this->contentStateService = new ContentStateService($baseUrl);
        }
        return $this->contentStateService;
    }

    /**
     * POST /iiif/content-state/encode
     *
     * Encode a viewer state (manifest, canvas, region, etc.) into a shareable token.
     *
     * Body (JSON):
     *   {
     *     "manifestId": "https://...",
     *     "canvasId": "https://.../canvas/1",
     *     "region": "100,100,400,300",
     *     "rotation": 90,
     *     "zoom": 2.5,
     *     "annotationPages": ["https://..."],
     *     "shortToken": true,        -- force short-token storage (optional)
     *     "ttlDays": 30,             -- TTL for short token (optional, default 30)
     *     "objectId": 123            -- link to information_object (optional)
     *   }
     *
     * Response 200:
     *   { "token": "...", "format": "short"|"long", "expiresAt": "ISO datetime|null", "decodeUrl": "..." }
     */
    public function executeEncode($request)
    {
        $this->forward404Unless($request->getMethod() === sfRequest::POST);

        $contentType = $request->getContentType();
        $body = $request->getRawPostParameters();

        if ($contentType === 'application/json' || $contentType === 'text/plain') {
            $data = json_decode($body, true);
        } else {
            $data = $request->getPostParameters();
        }

        if (!is_array($data)) {
            return $this->renderJson(['error' => 'Invalid JSON body'], 400);
        }

        // Validate required fields
        if (empty($data['manifestId']) || empty($data['canvasId'])) {
            return $this->renderJson([
                'error' => 'Missing required fields: manifestId and canvasId are required',
            ], 400);
        }

        // Build state from request
        $state = $this->getContentStateService()->buildFromViewerState(
            $data['manifestId'],
            $data['canvasId'],
            $data['region'] ?? '',
            (int) ($data['rotation'] ?? 0),
            (float) ($data['zoom'] ?? 1.0),
            $data['annotationPages'] ?? []
        );

        // Merge any additional fields
        if (!empty($data['additionalFields'])) {
            $state = array_merge($state, (array) $data['additionalFields']);
        }

        // Encode
        $options = [
            'shortToken' => !empty($data['shortToken']),
            'ttlDays' => (int) ($data['ttlDays'] ?? 30),
            'userId' => $this->getUser()->getAttribute('user_id'),
            'objectId' => (int) ($data['objectId'] ?? 0),
        ];

        try {
            $result = $this->getContentStateService()->encode($state, $options);
            return $this->renderJson($result);
        } catch (\Exception $e) {
            return $this->renderJson([
                'error' => 'Failed to encode content state: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /iiif/content-state/decode
     * POST /iiif/content-state/decode
     *
     * Decode a token (short or long-form) back to its Content State JSON.
     *
     * Query params / Body:
     *   token — the encoded token
     *
     * Response 200:
     *   { "contentState": {...}, "format": "short"|"long", "decodedAt": "ISO datetime" }
     */
    public function executeDecode($request)
    {
        $token = $request->getParameter('token', '');

        if (empty($token)) {
            return $this->renderJson(['error' => 'Missing token parameter'], 400);
        }

        $state = $this->getContentStateService()->decode($token);

        if ($state === null) {
            return $this->renderJson([
                'error' => 'Token not found or expired (short token) or invalid format (long token)',
                'tokenHint' => 'Ensure the token is a valid base64url-encoded JSON string or a registered short token.',
            ], 404);
        }

        return $this->renderJson([
            'contentState' => $state,
            'decodedAt' => date('c'),
        ]);
    }

    /**
     * GET /iiif/content-state/state?state={token}
     *
     * Convenience endpoint — mirrors Mirador's ?state= URL param convention.
     * Returns redirect to /iiif/viewer/:id with state applied (AJAX: returns JSON).
     *
     * Non-AJAX: returns a redirect or an HTML page that applies the state.
     */
    public function executeState($request)
    {
        $token = $request->getParameter('state', $request->getParameter('cs', ''));

        if (empty($token)) {
            $this->redirect404('Missing state token');
        }

        $state = $this->getContentStateService()->decode($token);

        if ($state === null) {
            $this->forward404('Invalid or expired state token');
        }

        // Check if this is an AJAX request
        if ($request->isXmlHttpRequest()) {
            return $this->renderJson([
                'contentState' => $state,
                'applyTo' => $this->getViewerUrlFromState($state),
            ]);
        }

        // Non-AJAX: apply state via viewer URL redirect
        $viewerUrl = $this->getViewerUrlFromState($state);
        if ($viewerUrl) {
            $this->redirect($viewerUrl);
        }

        // Fallback: show the content state as JSON
        $this->contentState = $state;
    }

    // -------------------------------------------------------------------------
    // Admin: list saved views
    // -------------------------------------------------------------------------

    /**
     * GET /admin/iiif-content-state
     * Admin panel listing recent saved views with analytics.
     */
    public function executeIndex($request)
    {
        $this->forward404Unless($this->getUser()->isAuthenticated());

        $pdo = $this->getContentStateService()->getPdo();

        // Recent saved views (last 100)
        $stmt = $pdo->query("
            SELECT v.id, v.token, v.click_count, v.expires_at, v.created_at,
                   v.object_id,
                   io.slug, ioi.title
            FROM `iiif_saved_view` v
            LEFT JOIN `information_object` io ON io.id = v.object_id
            LEFT JOIN `information_object_i18n` ioi ON ioi.id = io.id AND ioi.culture = 'en'
            ORDER BY v.created_at DESC
            LIMIT 100
        ");
        $this->savedViews = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Summary stats
        $statsStmt = $pdo->query("
            SELECT
                COUNT(*) AS total_views,
                SUM(click_count) AS total_clicks,
                COUNT(CASE WHEN expires_at > NOW() THEN 1 END) AS active_views
            FROM `iiif_saved_view`
        ");
        $this->stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function getViewerUrlFromState(array $state): ?string
    {
        $canonical = $state['canonical'] ?? $state;
        $source = $canonical['source'] ?? $canonical;

        if (empty($source['id'])) {
            return null;
        }

        $manifestId = $source['id'];

        // Try to find object by manifest URL
        // Manifest URL looks like: /iiif/manifest/:slug
        if (preg_match('#/iiif/manifest/([^/]+)#', $manifestId, $m)) {
            return '/iiif/viewer/' . $m[1];
        }

        // Fall back to object slug lookup
        $pdo = $this->getContentStateService()->getPdo();
        $stmt = $pdo->prepare("SELECT id FROM `information_object` WHERE slug = :slug LIMIT 1");
        $stmt->execute([':slug' => basename($manifestId)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? '/iiif/viewer/' . $row['id'] : null;
    }

    protected function renderJson(array $data, int $status = 200): sfView
    {
        $this->getResponse()->setHttpHeader('Content-Type', 'application/json; charset=utf-8');
        $this->getResponse()->setStatusCode($status);
        $this->data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return sfView::NONE;
    }

    /**
     * Get PDO directly (used by index action).
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->getContentStateService()->getPdo();
    }
}
