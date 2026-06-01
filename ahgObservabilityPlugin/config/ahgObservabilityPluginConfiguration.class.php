<?php

/**
 * ahgObservabilityPluginConfiguration.
 *
 * Wires the Prometheus exporter into AtoM (Symfony 1.x side):
 *   - enables the `observability` module (serves /metrics)
 *   - registers the /metrics route via AtomFramework RouteLoader
 *   - require_once's the plugin's namespaced service classes (Symfony 1.x
 *     does not autoload AtomExtensions\Observability\* classes)
 *   - subscribes a lightweight request-instrumentation listener that records
 *     atom_http_requests_total + atom_http_request_duration_seconds on the
 *     controller.change_action / response.filter_content events.
 *
 * The /metrics endpoint authenticates itself (bearer token OR IP allow-list)
 * inside the action - it is deliberately NOT behind the AtoM ACL filter so
 * Prometheus can scrape without a session.
 *
 * @author The Archive and Heritage Group (Pty) Ltd
 * @license GPL-3.0
 */
class ahgObservabilityPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Prometheus /metrics exporter: app/request/job metrics with APCu/Redis storage';
    public static $version = '1.0.0';

    /** @var float|null request start time captured for duration measurement */
    private static $requestStartedAt = null;

    public function initialize()
    {
        // Make the observability module routable.
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'observability';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        // Pull in the namespaced service classes (no PSR-4 autoload here).
        $this->requireServices();

        // Register routes when routing config loads.
        $this->dispatcher->connect('routing.load_configuration', [$this, 'routingLoadConfiguration']);

        // Request instrumentation: capture start as early as possible, then
        // record the metric once the response content is being filtered.
        self::$requestStartedAt = microtime(true);
        $this->dispatcher->connect('response.filter_content', [$this, 'recordRequestMetric']);
    }

    /**
     * require_once the plugin's service classes in dependency order.
     */
    private function requireServices()
    {
        $base = __DIR__.'/../lib/Services/';
        $files = [
            'MetricStorageInterface.php',
            'InMemoryStorage.php',
            'ApcuStorage.php',
            'RedisStorage.php',
            'PrometheusTextRenderer.php',
            'MetricsRegistry.php',
        ];
        foreach ($files as $f) {
            $path = $base.$f;
            if (is_file($path)) {
                require_once $path;
            }
        }
    }

    public function routingLoadConfiguration(sfEvent $event)
    {
        $routing = $event->getSubject();

        if (!class_exists('\AtomFramework\Routing\RouteLoader')) {
            return;
        }

        $loader = new \AtomFramework\Routing\RouteLoader('observability');
        // GET /metrics -> observability/metrics. No ACL; the action does its
        // own bearer-token + IP allow-list authentication.
        $loader->get('observability_metrics', '/metrics', 'metrics');
        $loader->register($routing);
    }

    /**
     * Record one http request counter + latency observation. Fully defensive:
     * a metrics backend hiccup must never disturb the response being sent.
     *
     * @param sfEvent $event response.filter_content event
     * @param string  $content the response body (returned unchanged)
     * @return string
     */
    public function recordRequestMetric(sfEvent $event, $content)
    {
        try {
            if (!class_exists('\AtomExtensions\Observability\MetricsRegistry')) {
                return $content;
            }

            $context = sfContext::getInstance();
            $request = $context->getRequest();
            $response = $context->getResponse();

            $module = (string) $request->getParameter('module', 'unknown');
            $action = (string) $request->getParameter('action', 'unknown');
            $route = $module.'/'.$action;

            // Don't instrument the scrape endpoint itself (avoids self-noise).
            if ($route === 'observability/metrics') {
                return $content;
            }

            $method = strtoupper((string) $request->getMethod());
            $status = (string) $response->getStatusCode();

            $start = self::$requestStartedAt ?? (defined('SF_START_TIME') ? SF_START_TIME : microtime(true));
            $duration = max(0.0, microtime(true) - (float) $start);

            $metrics = \AtomExtensions\Observability\MetricsRegistry::instance();

            $labelNames = ['method', 'route', 'status'];
            $labelValues = [$method, $route, $status];

            $metrics->counter('http_requests_total', 'Total HTTP requests', $labelNames, $labelValues);
            $metrics->histogram(
                'http_request_duration_seconds',
                'HTTP request duration in seconds',
                $labelNames,
                $labelValues,
                \AtomExtensions\Observability\MetricsRegistry::HTTP_BUCKETS,
                $duration
            );
        } catch (\Throwable $e) {
            // Never let metrics break the response.
        }

        return $content;
    }
}
