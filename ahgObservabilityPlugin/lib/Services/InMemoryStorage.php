<?php

/**
 * InMemoryStorage - process-local metric storage.
 *
 * Always available, requires no extension. Counters/gauges/histograms live
 * for the duration of one PHP process only, so this is mostly useful for:
 *   - CLI runs (emit-textfile, record-queue-depth) that produce one snapshot
 *   - the always-safe fallback when neither APCu nor Redis is usable
 *
 * Because php-fpm workers do not share memory, a /metrics scrape served by
 * one worker will only reflect what that worker saw. For accurate web-tier
 * counters use the APCu adapter (single host) or Redis (multi host).
 *
 * @author The Archive and Heritage Group (Pty) Ltd
 * @license GPL-3.0
 */

namespace AtomExtensions\Observability;

class InMemoryStorage implements MetricStorageInterface
{
    /** @var array<string,array<string,mixed>> keyed by metric name */
    private array $counters = [];

    /** @var array<string,array<string,mixed>> keyed by metric name */
    private array $gauges = [];

    /** @var array<string,array<string,mixed>> keyed by metric name */
    private array $histograms = [];

    public function incCounter(string $name, string $help, array $labelNames, array $labelValues, float $value = 1.0): void
    {
        $key = $this->labelKey($labelValues);
        if (!isset($this->counters[$name])) {
            $this->counters[$name] = ['help' => $help, 'labels' => $labelNames, 'samples' => []];
        }
        $this->counters[$name]['samples'][$key]['labels'] = $labelValues;
        $this->counters[$name]['samples'][$key]['value'] =
            ($this->counters[$name]['samples'][$key]['value'] ?? 0.0) + max(0.0, $value);
    }

    public function setGauge(string $name, string $help, array $labelNames, array $labelValues, float $value): void
    {
        $key = $this->labelKey($labelValues);
        if (!isset($this->gauges[$name])) {
            $this->gauges[$name] = ['help' => $help, 'labels' => $labelNames, 'samples' => []];
        }
        $this->gauges[$name]['samples'][$key] = ['labels' => $labelValues, 'value' => $value];
    }

    public function observeHistogram(string $name, string $help, array $labelNames, array $labelValues, array $buckets, float $value): void
    {
        $key = $this->labelKey($labelValues);
        if (!isset($this->histograms[$name])) {
            $this->histograms[$name] = [
                'help' => $help,
                'labels' => $labelNames,
                'buckets' => $buckets,
                'samples' => [],
            ];
        }
        if (!isset($this->histograms[$name]['samples'][$key])) {
            $this->histograms[$name]['samples'][$key] = [
                'labels' => $labelValues,
                'buckets' => array_fill_keys(array_map('strval', $buckets), 0),
                'sum' => 0.0,
                'count' => 0,
            ];
        }
        $this->histograms[$name]['samples'][$key]['sum'] += $value;
        $this->histograms[$name]['samples'][$key]['count']++;
        foreach ($buckets as $b) {
            if ($value <= $b) {
                $this->histograms[$name]['samples'][$key]['buckets'][(string) $b]++;
            }
        }
    }

    public function collect(): array
    {
        $families = [];

        foreach ($this->counters as $name => $data) {
            $families[] = [
                'name' => $name,
                'help' => $data['help'],
                'type' => 'counter',
                'labels' => $data['labels'],
                'samples' => array_map(
                    static fn ($s) => ['labels' => $s['labels'], 'value' => $s['value']],
                    array_values($data['samples'])
                ),
            ];
        }

        foreach ($this->gauges as $name => $data) {
            $families[] = [
                'name' => $name,
                'help' => $data['help'],
                'type' => 'gauge',
                'labels' => $data['labels'],
                'samples' => array_values($data['samples']),
            ];
        }

        foreach ($this->histograms as $name => $data) {
            $samples = [];
            foreach ($data['samples'] as $s) {
                // Buckets are already stored cumulatively (each observation
                // increments every bucket whose upper bound it falls under),
                // so emit them directly - do NOT re-accumulate.
                foreach ($data['buckets'] as $b) {
                    $samples[] = ['suffix' => '_bucket', 'labels' => $s['labels'], 'le' => (string) $b, 'value' => $s['buckets'][(string) $b] ?? 0];
                }
                $samples[] = ['suffix' => '_bucket', 'labels' => $s['labels'], 'le' => '+Inf', 'value' => $s['count']];
                $samples[] = ['suffix' => '_sum', 'labels' => $s['labels'], 'value' => $s['sum']];
                $samples[] = ['suffix' => '_count', 'labels' => $s['labels'], 'value' => $s['count']];
            }
            $families[] = [
                'name' => $name,
                'help' => $data['help'],
                'type' => 'histogram',
                'labels' => $data['labels'],
                'samples' => $samples,
            ];
        }

        return $families;
    }

    public function driverName(): string
    {
        return 'inmemory';
    }

    private function labelKey(array $labelValues): string
    {
        return implode("\x1f", $labelValues);
    }
}
