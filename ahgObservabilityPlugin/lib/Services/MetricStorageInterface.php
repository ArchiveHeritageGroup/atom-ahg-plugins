<?php

/**
 * MetricStorageInterface - pluggable backend for metric sample storage.
 *
 * A storage adapter persists three primitive metric shapes:
 *   - counter   : monotonically increasing float, addressed by name+labels
 *   - gauge      : arbitrary float (set/inc/dec), addressed by name+labels
 *   - histogram  : per-bucket counters + running sum + observation count
 *
 * Implementations must be safe to call from php-fpm workers AND from CLI
 * processes that share the same counter space (APCu within one host, Redis
 * across hosts). InMemory is process-local and used as the always-available
 * fallback so a missing extension never breaks request serving.
 *
 * This is a self-contained mirror of the PromPHP CollectorRegistry storage
 * model used by Heratio's ahg-observability package, re-implemented without
 * the external composer dependency (which is not available under the locked
 * AtoM vendor/ tree).
 *
 * @author The Archive and Heritage Group (Pty) Ltd
 * @license GPL-3.0
 */

namespace AtomExtensions\Observability;

interface MetricStorageInterface
{
    /**
     * Increment a counter by $value (>= 0). Registers the series lazily.
     */
    public function incCounter(string $name, string $help, array $labelNames, array $labelValues, float $value = 1.0): void;

    /**
     * Set a gauge to an absolute value. Registers the series lazily.
     */
    public function setGauge(string $name, string $help, array $labelNames, array $labelValues, float $value): void;

    /**
     * Observe a single value into a histogram. Registers the series lazily.
     *
     * @param float[] $buckets Upper-bound bucket boundaries (ascending)
     */
    public function observeHistogram(string $name, string $help, array $labelNames, array $labelValues, array $buckets, float $value): void;

    /**
     * Return all collected metric families for rendering.
     *
     * Shape per family:
     *   [
     *     'name'    => 'http_requests_total',
     *     'help'    => '...',
     *     'type'    => 'counter'|'gauge'|'histogram',
     *     'labels'  => ['method','route','status'],   // declared label names
     *     'samples' => [
     *        // counter/gauge: ['labels'=>[...], 'value'=>1.0]
     *        // histogram: ['suffix'=>'_bucket','labels'=>[...],'le'=>'0.5','value'=>3]
     *        //            ['suffix'=>'_sum','labels'=>[...],'value'=>1.23]
     *        //            ['suffix'=>'_count','labels'=>[...],'value'=>5]
     *     ],
     *   ]
     *
     * @return array<int,array<string,mixed>>
     */
    public function collect(): array;

    /**
     * Best-effort identifier of the live backend ("apcu","redis","inmemory").
     */
    public function driverName(): string;
}
