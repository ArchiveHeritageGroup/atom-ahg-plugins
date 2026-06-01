<?php

/**
 * metricsAction - serve /metrics in Prometheus text exposition format.
 *
 * Auth model (OR semantics), mirroring Heratio's MetricsController:
 *   - If a bearer token is configured (obs_token) AND the request presents a
 *     matching `Authorization: Bearer <token>` header: ALLOW.
 *   - If the client IP is in the allow-list (obs_allowed_ips, comma list):
 *     ALLOW.
 *   - Otherwise: 401.
 *
 * The deliberate consequence of empty token + empty allow-list is "deny all" -
 * correct fail-closed behaviour for a metrics endpoint on the public vhost.
 * Default allow-list is loopback (127.0.0.1, ::1) which matches the typical
 * Prometheus-on-same-host posture.
 *
 * This action is reached via the AtomFramework route /metrics. It does its own
 * authentication and is intentionally NOT behind the AtoM ACL/login filter so
 * Prometheus can scrape without a session cookie.
 *
 * @author The Archive and Heritage Group (Pty) Ltd
 * @license GPL-3.0
 */
class metricsAction extends sfAction
{
    public function execute($request)
    {
        // The plugin Configuration require_once's these, but guard in case the
        // action is hit before initialize() ran (e.g. cold module load).
        if (!class_exists('\AtomExtensions\Observability\MetricsRegistry')) {
            $base = dirname(__DIR__, 2).'/lib/Services/';
            foreach ([
                'MetricStorageInterface.php', 'InMemoryStorage.php', 'ApcuStorage.php',
                'RedisStorage.php', 'PrometheusTextRenderer.php', 'MetricsRegistry.php',
            ] as $f) {
                if (is_file($base.$f)) {
                    require_once $base.$f;
                }
            }
        }

        if (!$this->isAuthorised($request)) {
            $this->getResponse()->setStatusCode(401);
            $this->getResponse()->setContentType('text/plain; charset=utf-8');

            return $this->renderText("Unauthorised\n");
        }

        $body = \AtomExtensions\Observability\MetricsRegistry::instance()->render();

        $this->getResponse()->setStatusCode(200);
        $this->getResponse()->setContentType(\AtomExtensions\Observability\PrometheusTextRenderer::MIME_TYPE);
        // Don't let an upstream cache serve stale counters to Prometheus.
        $this->getResponse()->setHttpHeader('Cache-Control', 'no-store');

        return $this->renderText($body);
    }

    private function isAuthorised($request): bool
    {
        $token = (string) \AtomExtensions\Observability\MetricsRegistry::setting('token', '');
        $allowedIps = $this->allowedIps();

        $bearer = $this->bearer($request);
        if ($token !== '' && $bearer !== null && hash_equals($token, $bearer)) {
            return true;
        }

        $ip = (string) $request->getRemoteAddress();
        if (!empty($allowedIps) && in_array($ip, $allowedIps, true)) {
            return true;
        }

        return false;
    }

    /**
     * @return string[]
     */
    private function allowedIps(): array
    {
        $raw = (string) \AtomExtensions\Observability\MetricsRegistry::setting('allowed_ips', '127.0.0.1,::1');
        $parts = array_map('trim', explode(',', $raw));

        return array_values(array_filter($parts, static fn ($v) => $v !== ''));
    }

    private function bearer($request): ?string
    {
        $header = $request->getHttpHeader('Authorization');
        if (!is_string($header) || $header === '') {
            return null;
        }
        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
            return null;
        }

        return trim($m[1]);
    }
}
