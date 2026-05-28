<?php

/**
 * ContentStateService
 *
 * Implements the IIIF Content State API:
 * - Encodes a Content State (manifestId, canvasId, xywh, rotation, etc.)
 *   into either a long-form base64url token (stateless) or short DB token (>= 30 chars).
 * - Decodes tokens back into Content State JSON.
 * - Persists short tokens with TTL for analytics and longer shares.
 *
 * Reference: IIIF Content State API 1.0
 *   https://iiif.io/api/content-state/1.0/
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 * @version 1.0.0
 */
class ContentStateService
{
    /** Short-token length threshold. >= this length uses DB storage. */
    public const SHORT_TOKEN_MIN_LEN = 30;

    /** Default TTL for stored short tokens (days). */
    public const DEFAULT_TTL_DAYS = 30;

    /** Allowed JSON-LD @type values for content state. */
    private const VALID_TYPES = [
        'ContentState',
        'SpecificResource',
        'Selector',
        'PointSelector',
        'FragmentSelector',
        'SvgSelector',
    ];

    private string $baseUrl;
    private ?PDO $pdo = null;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = $baseUrl ?: $this->detectBaseUrl();
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Encode a content state into a token (short or long-form).
     *
     * @param array $state  Content State JSON-LD compatible array
     * @param array $options { shortToken: bool (force), ttlDays: int, userId: int, objectId: int }
     * @return array { token: string, format: 'short'|'long', expiresAt: string|null }
     */
    public function encode(array $state, array $options = []): array
    {
        $json = json_encode($state, JSON_THROW_ON_ERROR);
        $b64 = $this->base64urlEncode($json);
        $token = $b64;

        // Determine whether to store as short token
        $forceShort = !empty($options['shortToken']);
        $useShort = $forceShort || strlen($b64) >= self::SHORT_TOKEN_MIN_LEN;

        if (!$useShort) {
            return [
                'token' => $b64,
                'format' => 'long',
                'expiresAt' => null,
                'decodeUrl' => "/iiif/content-state/decode?token=" . urlencode($b64),
            ];
        }

        // Generate and store short token
        $shortToken = $this->generateShortToken($state, $options);
        $ttlDays = (int) ($options['ttlDays'] ?? self::DEFAULT_TTL_DAYS);
        $expiresAt = date('Y-m-d H:i:s', time() + $ttlDays * 86400);

        $this->storeShortToken($shortToken, $json, $options, $expiresAt);

        return [
            'token' => $shortToken,
            'format' => 'short',
            'expiresAt' => $expiresAt,
            'decodeUrl' => "/iiif/content-state/decode?token=" . urlencode($shortToken),
        ];
    }

    /**
     * Decode a token (short or long-form) back to a Content State array.
     *
     * @param string $token
     * @return array|null  Content state array or null on failure
     */
    public function decode(string $token): ?array
    {
        $token = trim($token);

        if (empty($token)) {
            return null;
        }

        // Try short token lookup first
        $state = $this->lookupShortToken($token);
        if ($state !== null) {
            return $state;
        }

        // Fall back to long-form base64url decode
        return $this->decodeLongForm($token);
    }

    /**
     * Build a Content State from a manifest + canvas + region parameters.
     * Convenience method — normalises input from viewer state.
     *
     * @param string $manifestId    IIIF Manifest IRI
     * @param string $canvasId      IIIF Canvas IRI
     * @param string $region        xywh fragment selector (e.g. "100,100,400,300")
     * @param int $rotation         Degrees rotation
     * @param float $zoom           OSD zoom level (for annotation)
     * @param array $annotationPages Extra annotation page IRIs
     * @return array                Content State array
     */
    public function buildFromViewerState(
        string $manifestId,
        string $canvasId,
        string $region = '',
        int $rotation = 0,
        float $zoom = 1.0,
        array $annotationPages = []
    ): array {
        $state = [
            '@context' => [
                'http://iiif.io/api/content-state/1/context.json',
                'http://iiif.io/api/presentation/3/context.json',
            ],
            'id' => $manifestId,
            'type' => 'SpecificResource',
            'source' => [
                'id' => $manifestId,
                'type' => 'Manifest',
            ],
        ];

        $selector = [
            'type' => 'FragmentSelector',
            'value' => $canvasId,
        ];

        if (!empty($region)) {
            $selector['value'] = $canvasId . '#' . $region;
            $state['selector'] = [
                'type' => 'FragmentSelector',
                'value' => $region,
                'conformsTo' => 'http://www.w3.org/TR/media-frags/',
            ];
        }

        if ($rotation !== 0) {
            $state['selector']['rotation'] = $rotation;
        }

        if ($zoom !== 1.0) {
            $state['selector']['zoom'] = $zoom;
        }

        if (!empty($annotationPages)) {
            $state['additionalContents'] = array_map(fn($p) => ['id' => $p, 'type' => 'AnnotationPage'], $annotationPages);
        }

        $state['selector']['conformsTo'] = 'http://iiif.io/api/presentation/3/context.json';
        $state['target'] = $canvasId;

        // Wrap in SpecificResource
        return [
            '@context' => $state['@context'],
            'id' => $manifestId . '/content-state-' . substr(md5(json_encode([$canvasId, $region, $rotation])), 0, 12),
            'type' => 'ContentState',
            'canonical' => $state,
        ];
    }

    /**
     * Parse a Content State from a IIIF-compatible URL parameter.
     * Handles: ?state={base64url}, ?cs={content-state-uri}, ?cs_uri={encoded-uri}
     *
     * @param array $params  $_GET or similar
     * @return array|null
     */
    public function parseFromParams(array $params): ?array
    {
        if (!empty($params['state'])) {
            return $this->decode($params['state']);
        }

        if (!empty($params['cs'])) {
            return $this->decode($params['cs']);
        }

        if (!empty($params['cs_uri'])) {
            return $this->resolveContentStateUri(urldecode($params['cs_uri']));
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    protected function detectBaseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'psis.theahg.co.za';
        return "{$scheme}://{$host}";
    }

    protected function base64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    protected function base64urlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'), true) ?: '';
    }

    /**
     * Generate a cryptographically random short token.
     */
    protected function generateShortToken(array $state, array $options): string
    {
        $entropy = random_bytes(16);
        $timeBytes = pack('N', time());
        $userBytes = pack('N', $options['userId'] ?? 0);
        $hash = hash('sha256', $entropy . $timeBytes . $userBytes . json_encode($state), true);
        return substr($this->base64urlEncode($hash), 0, 32);
    }

    /**
     * Store a short token in the database.
     */
    protected function storeShortToken(string $token, string $json, array $options, string $expiresAt): void
    {
        $pdo = $this->getPdo();
        $stmt = $pdo->prepare("
            INSERT INTO `iiif_saved_view` (`token`, `state_json`, `user_id`, `object_id`, `expires_at`, `created_ip`)
            VALUES (:token, :state_json, :user_id, :object_id, :expires_at, :created_ip)
            ON DUPLICATE KEY UPDATE
                `state_json` = VALUES(`state_json`),
                `expires_at` = VALUES(`expires_at`),
                `user_id` = COALESCE(VALUES(`user_id`), `user_id`),
                `click_count` = `click_count`
        ");
        $stmt->execute([
            ':token' => $token,
            ':state_json' => $json,
            ':user_id' => $options['userId'] ?? null,
            ':object_id' => $options['objectId'] ?? null,
            ':expires_at' => $expiresAt,
            ':created_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }

    /**
     * Lookup a short token in the database.
     */
    protected function lookupShortToken(string $token): ?array
    {
        $pdo = $this->getPdo();

        // Increment click count atomically
        $select = $pdo->prepare("
            SELECT `state_json` FROM `iiif_saved_view`
            WHERE `token` = :token AND `expires_at` > NOW()
            LIMIT 1
        ");
        $select->execute([':token' => $token]);
        $row = $select->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        // Increment click count (fire-and-forget)
        $pdo->prepare("UPDATE `iiif_saved_view` SET `click_count` = `click_count` + 1 WHERE `token` = :token")
            ->execute([':token' => $token]);

        return json_decode($row['state_json'], true) ?: null;
    }

    /**
     * Decode a long-form base64url token directly.
     */
    protected function decodeLongForm(string $token): ?array
    {
        $decoded = $this->base64urlDecode($token);
        if (empty($decoded)) {
            return null;
        }

        $state = json_decode($decoded, true);
        if (!is_array($state) || json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        // Validate structure
        return $this->validateContentState($state) ? $state : null;
    }

    /**
     * Resolve a content state from an external IRI (http://..., mirador://, etc.)
     */
    protected function resolveContentStateUri(string $uri): ?array
    {
        // mirador:// protocol — embedded state
        if (str_starts_with($uri, 'mirador://')) {
            $json = substr($uri, strlen('mirador://'));
            $decoded = $this->base64urlDecode($json);
            if ($decoded) {
                return json_decode($decoded, true);
            }
        }

        // HTTP(S) — fetch if same origin, else return placeholder
        if (str_starts_with($uri, 'http://') || str_starts_with($uri, 'https://')) {
            // Only fetch same-origin for security
            $baseHost = parse_url($this->baseUrl, PHP_URL_HOST);
            $uriHost = parse_url($uri, PHP_URL_HOST);
            if ($baseHost === $uriHost) {
                $response = @file_get_contents($uri, false, stream_context_create([
                    'http' => ['timeout' => 3, 'ignore_errors' => true],
                ]));
                if ($response) {
                    return json_decode($response, true);
                }
            }
            // Cross-origin: return reference only
            return ['id' => $uri, 'type' => 'ContentState', 'external' => true];
        }

        return null;
    }

    /**
     * Validate a Content State array structure.
     */
    protected function validateContentState(array $state): bool
    {
        // Must have type
        if (empty($state['type'])) {
            return false;
        }

        // Must have id or source/id
        if (empty($state['id']) && empty($state['source']['id'])) {
            return false;
        }

        // Type should be one of the valid types or a known compound
        $type = $state['type'];
        return in_array($type, self::VALID_TYPES, true) || in_array($type, [
            'ContentState', 'SpecificResource', 'Annotation',
        ], true);
    }

    protected function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $dsn = 'mysql:host=' . (getenv('DATABASE_HOST') ?: 'localhost')
                . ';dbname=' . (getenv('DATABASE_NAME') ?: 'archive')
                . ';charset=utf8mb4';
            $this->pdo = new PDO($dsn,
                getenv('DATABASE_USER') ?: 'root',
                getenv('DATABASE_PASSWORD') ?: '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        }
        return $this->pdo;
    }
}
