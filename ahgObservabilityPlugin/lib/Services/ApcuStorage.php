<?php

/**
 * ApcuStorage - cross-worker metric storage backed by the APCu extension.
 *
 * Counters and histogram buckets use apcu_inc()/apcu_add() which are atomic,
 * so concurrent php-fpm workers accumulate into the same shared series. This
 * is the right adapter for a single-host AtoM deployment: accurate within one
 * box, reset on php-fpm restart (acceptable for Prometheus, which handles
 * counter resets natively).
 *
 * Series metadata (help text, declared label names, bucket boundaries) is
 * stored alongside the values so collect() can rebuild the family without a
 * central registry. A per-namespace index key tracks which series exist.
 *
 * All apcu_* access is probed via apcu_enabled() upstream; this class assumes
 * the extension is usable (see MetricsRegistry::resolveStorage()).
 *
 * @author The Archive and Heritage Group (Pty) Ltd
 * @license GPL-3.0
 */

namespace AtomExtensions\Observability;

class ApcuStorage implements MetricStorageInterface
{
    private const PREFIX = 'ahgobs:';

    private const INDEX_KEY = 'ahgobs:__index__';

    public function incCounter(string $name, string $help, array $labelNames, array $labelValues, float $value = 1.0): void
    {
        $key = $this->seriesKey('counter', $name, $labelValues);
        $this->registerMeta('counter', $name, $help, $labelNames, $labelValues, [], $key);
        // apcu_inc only handles integers; counters here are integral request
        // counts, but support float by storing scaled when needed.
        if ($value === (float) (int) $value) {
            if (apcu_add($key.':v', 0, 0)) {
                // freshly created
            }
            apcu_inc($key.':v', (int) $value);
        } else {
            $current = (float) apcu_fetch($key.':vf') ?: 0.0;
            apcu_store($key.':vf', $current + max(0.0, $value), 0);
        }
    }

    public function setGauge(string $name, string $help, array $labelNames, array $labelValues, float $value): void
    {
        $key = $this->seriesKey('gauge', $name, $labelValues);
        $this->registerMeta('gauge', $name, $help, $labelNames, $labelValues, [], $key);
        apcu_store($key.':v', $value, 0);
    }

    public function observeHistogram(string $name, string $help, array $labelNames, array $labelValues, array $buckets, float $value): void
    {
        $key = $this->seriesKey('histogram', $name, $labelValues);
        $this->registerMeta('histogram', $name, $help, $labelNames, $labelValues, $buckets, $key);

        apcu_add($key.':sum', 0.0, 0);
        $sum = (float) apcu_fetch($key.':sum');
        apcu_store($key.':sum', $sum + $value, 0);

        apcu_add($key.':count', 0, 0);
        apcu_inc($key.':count');

        foreach ($buckets as $b) {
            if ($value <= $b) {
                $bk = $key.':b:'.(string) $b;
                apcu_add($bk, 0, 0);
                apcu_inc($bk);
            }
        }
    }

    public function collect(): array
    {
        $index = apcu_fetch(self::INDEX_KEY);
        if (!is_array($index)) {
            return [];
        }

        // Group series by metric name + type.
        $families = [];
        foreach ($index as $entry) {
            $name = $entry['name'];
            $type = $entry['type'];
            $famKey = $type.'|'.$name;
            if (!isset($families[$famKey])) {
                $families[$famKey] = [
                    'name' => $name,
                    'help' => $entry['help'],
                    'type' => $type,
                    'labels' => $entry['labelNames'],
                    'samples' => [],
                ];
            }

            $key = $entry['key'];
            if ($type === 'counter') {
                $v = apcu_fetch($key.':v');
                $vf = apcu_fetch($key.':vf');
                $value = (float) ($v !== false ? $v : 0) + (float) ($vf !== false ? $vf : 0);
                $families[$famKey]['samples'][] = ['labels' => $entry['labelValues'], 'value' => $value];
            } elseif ($type === 'gauge') {
                $value = apcu_fetch($key.':v');
                $families[$famKey]['samples'][] = ['labels' => $entry['labelValues'], 'value' => (float) ($value !== false ? $value : 0)];
            } else { // histogram
                // Buckets are stored cumulatively at observe time; emit as-is.
                foreach ($entry['buckets'] as $b) {
                    $bv = apcu_fetch($key.':b:'.(string) $b);
                    $families[$famKey]['samples'][] = [
                        'suffix' => '_bucket', 'labels' => $entry['labelValues'], 'le' => (string) $b, 'value' => (int) ($bv !== false ? $bv : 0),
                    ];
                }
                $count = (int) (apcu_fetch($key.':count') ?: 0);
                $sum = (float) (apcu_fetch($key.':sum') ?: 0.0);
                $families[$famKey]['samples'][] = ['suffix' => '_bucket', 'labels' => $entry['labelValues'], 'le' => '+Inf', 'value' => $count];
                $families[$famKey]['samples'][] = ['suffix' => '_sum', 'labels' => $entry['labelValues'], 'value' => $sum];
                $families[$famKey]['samples'][] = ['suffix' => '_count', 'labels' => $entry['labelValues'], 'value' => $count];
            }
        }

        return array_values($families);
    }

    public function driverName(): string
    {
        return 'apcu';
    }

    private function seriesKey(string $type, string $name, array $labelValues): string
    {
        return self::PREFIX.$type.':'.$name.':'.md5(implode("\x1f", $labelValues));
    }

    /**
     * Idempotently register series metadata into the index so collect() can
     * enumerate every series without a central registry instance.
     */
    private function registerMeta(string $type, string $name, string $help, array $labelNames, array $labelValues, array $buckets, string $key): void
    {
        $index = apcu_fetch(self::INDEX_KEY);
        if (!is_array($index)) {
            $index = [];
        }
        if (!isset($index[$key])) {
            $index[$key] = [
                'type' => $type,
                'name' => $name,
                'help' => $help,
                'labelNames' => $labelNames,
                'labelValues' => $labelValues,
                'buckets' => $buckets,
                'key' => $key,
            ];
            apcu_store(self::INDEX_KEY, $index, 0);
        }
    }
}
