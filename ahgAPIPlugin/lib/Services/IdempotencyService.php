<?php

/**
 * IdempotencyService
 *
 * AtoM/Symfony-1.x port of the Heratio ahg-api IdempotencyKeyMiddleware.
 *
 * Honours the Idempotency-Key header on non-idempotent API requests
 * (POST/PUT/PATCH):
 *   - First call: process normally, cache the response keyed by
 *     (user_id, key, route) for 24h.
 *   - Replay with same key + same request body + route: return the cached
 *     response with an `X-Idempotent-Replay: true` marker.
 *   - Replay with same key + different body/route: 409 Conflict.
 *
 * Bypass: missing header = normal pass-through (the header is optional).
 *
 * Because AtoM is Symfony 1.x (no Laravel middleware pipeline), this is
 * implemented as a service that an apiv2 mutating action calls at the top of
 * its handler. The action asks {@see begin()} whether a cached response should
 * be replayed (or a conflict returned); after producing a fresh 2xx response
 * it calls {@see store()} to cache it.
 *
 * Mirrors AhgApi\Middleware\IdempotencyKeyMiddleware (Heratio issue #652).
 *
 * @author    The Archive and Heritage Group (Pty) Ltd
 * @copyright 2026 The Archive and Heritage Group (Pty) Ltd
 */

namespace AhgAPIPlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;

class IdempotencyService
{
    /** 24-hour replay window (seconds). */
    public const TTL_SECONDS = 86400;

    /** Maximum client-supplied key length per draft RFC. */
    public const MAX_KEY_LENGTH = 64;

    /** HTTP verbs that are eligible for idempotency dedup. */
    protected const MUTATING_METHODS = ['POST', 'PUT', 'PATCH'];

    /** @var string Cached lookup result so begin()+store() share state. */
    protected $resolvedKey = '';

    /**
     * Decide what to do at the start of a mutating request.
     *
     * @param string $method      HTTP verb (POST/PUT/PATCH/...)
     * @param string $key         Raw Idempotency-Key header value ('' if none)
     * @param int    $userId      Authenticated user id (0 = anonymous)
     * @param string $route       Request path (e.g. "api/v2/descriptions")
     * @param string $requestBody Raw request body
     *
     * @return array One of:
     *   ['action' => 'pass']                                  no key / non-mutating — proceed normally
     *   ['action' => 'error',  'status' => 400, 'message' => ...] key too long
     *   ['action' => 'conflict','status' => 409, 'message' => ...] key reused with different body/route
     *   ['action' => 'replay', 'status' => int, 'body' => string, 'headers' => array]
     */
    public function begin(string $method, string $key, int $userId, string $route, string $requestBody): array
    {
        $this->resolvedKey = '';

        // Only intercept non-idempotent verbs.
        if (!in_array(strtoupper($method), self::MUTATING_METHODS, true)) {
            return ['action' => 'pass'];
        }

        $key = trim($key);
        if ('' === $key) {
            return ['action' => 'pass'];
        }

        // Safety: enforce client-side max key length per draft RFC.
        if (strlen($key) > self::MAX_KEY_LENGTH) {
            return [
                'action' => 'error',
                'status' => 400,
                'message' => 'Idempotency-Key must be <= ' . self::MAX_KEY_LENGTH . ' characters.',
            ];
        }

        // If table missing (fresh DB / not yet migrated), let the request through.
        if (!$this->tableExists()) {
            return ['action' => 'pass'];
        }

        $requestHash = hash('sha256', $requestBody);

        $existing = DB::table('ahg_api_idempotency_key')
            ->where('user_id', $userId)
            ->where('idem_key', $key)
            ->where('expires_at', '>', date('Y-m-d H:i:s'))
            ->first();

        if ($existing) {
            // Same payload + route? replay the cached response.
            if ($existing->request_hash === $requestHash && $existing->route === substr($route, 0, 255)) {
                $headers = $this->decodeHeaders($existing->response_headers ?? null);
                $headers['X-Idempotent-Replay'] = 'true';

                return [
                    'action' => 'replay',
                    'status' => (int) $existing->response_status,
                    'body' => (string) $existing->response_body,
                    'headers' => $headers,
                ];
            }

            // Same key, different request: protocol error.
            return [
                'action' => 'conflict',
                'status' => 409,
                'message' => 'Idempotency-Key has already been used with a different request body or route.',
            ];
        }

        // No cached entry — remember the key so store() can persist the result.
        $this->resolvedKey = $key;

        return ['action' => 'pass'];
    }

    /**
     * Cache a freshly-produced response after a successful mutating request.
     *
     * No-op unless begin() previously accepted a (new) key for this request.
     * Only 2xx responses are cached to avoid replaying transient errors.
     *
     * @param int    $userId         Authenticated user id
     * @param string $route          Request path
     * @param string $requestBody    Raw request body
     * @param int    $status         HTTP status of the produced response
     * @param string $responseBody   Response body to cache
     * @param array  $responseHeaders Header name => value map to cache
     */
    public function store(int $userId, string $route, string $requestBody, int $status, string $responseBody, array $responseHeaders = []): void
    {
        if ('' === $this->resolvedKey) {
            return;
        }

        if ($status < 200 || $status >= 300) {
            return;
        }

        if (!$this->tableExists()) {
            return;
        }

        try {
            DB::table('ahg_api_idempotency_key')->insert([
                'idem_key' => $this->resolvedKey,
                'user_id' => $userId,
                'route' => substr($route, 0, 255),
                'request_hash' => hash('sha256', $requestBody),
                'response_status' => $status,
                'response_body' => $responseBody,
                'response_headers' => json_encode($this->filterHeaders($responseHeaders)),
                'expires_at' => date('Y-m-d H:i:s', time() + self::TTL_SECONDS),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Duplicate-key races are fine — first writer wins; ignore.
        }
    }

    /**
     * Delete expired idempotency-key rows.
     *
     * @param bool $all When true, delete every row regardless of expiry.
     *
     * @return int Rows deleted.
     */
    public function prune(bool $all = false): int
    {
        if (!$this->tableExists()) {
            return 0;
        }

        $q = DB::table('ahg_api_idempotency_key');
        if (!$all) {
            $q = $q->where('expires_at', '<', date('Y-m-d H:i:s'));
        }

        return (int) $q->delete();
    }

    /**
     * Strip volatile/duplicate headers before caching (Set-Cookie, Date).
     */
    protected function filterHeaders(array $headers): array
    {
        $out = [];
        foreach ($headers as $name => $value) {
            $lower = strtolower((string) $name);
            if (in_array($lower, ['set-cookie', 'date'], true)) {
                continue;
            }
            $out[$name] = is_array($value) ? implode(', ', $value) : (string) $value;
        }

        return $out;
    }

    protected function decodeHeaders(?string $raw): array
    {
        if (!$raw) {
            return [];
        }
        $h = json_decode($raw, true);

        return is_array($h) ? $h : [];
    }

    protected function tableExists(): bool
    {
        try {
            return DB::schema()->hasTable('ahg_api_idempotency_key');
        } catch (\Throwable $e) {
            return false;
        }
    }
}
