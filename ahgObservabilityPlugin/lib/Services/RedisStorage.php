<?php

/**
 * RedisStorage - cross-host metric storage backed by the phpredis extension.
 *
 * The right choice when php-fpm workers AND background queue workers on
 * multiple hosts must share the same counters. Uses Redis hashes + atomic
 * HINCRBYFLOAT so concurrent writers accumulate safely.
 *
 * Graceful degradation: the caller (MetricsRegistry) only selects this
 * adapter when the phpredis extension is loaded; if the server is configured
 * but unreachable at connect time we throw, and MetricsRegistry falls back to
 * InMemory so request serving is never blocked by a metrics backend outage.
 *
 * @author The Archive and Heritage Group (Pty) Ltd
 * @license GPL-3.0
 */

namespace AtomExtensions\Observability;

class RedisStorage implements MetricStorageInterface
{
    private const PREFIX = 'ahgobs:';

    private const INDEX_KEY = 'ahgobs:__index__';

    /** @var \Redis */
    private $redis;

    /**
     * @throws \RuntimeException when phpredis is missing or the server is unreachable
     */
    public function __construct(string $host = '127.0.0.1', int $port = 6379, ?string $password = null, int $database = 0)
    {
        if (!class_exists('\Redis')) {
            throw new \RuntimeException('phpredis extension not loaded');
        }
        $this->redis = new \Redis();
        if (!@$this->redis->connect($host, $port, 0.5)) {
            throw new \RuntimeException("Redis unreachable at {$host}:{$port}");
        }
        if ($password !== null && $password !== '') {
            $this->redis->auth($password);
        }
        if ($database > 0) {
            $this->redis->select($database);
        }
    }

    public function incCounter(string $name, string $help, array $labelNames, array $labelValues, float $value = 1.0): void
    {
        $key = $this->seriesKey('counter', $name, $labelValues);
        $this->registerMeta('counter', $name, $help, $labelNames, $labelValues, [], $key);
        $this->redis->hIncrByFloat($key, 'v', max(0.0, $value));
    }

    public function setGauge(string $name, string $help, array $labelNames, array $labelValues, float $value): void
    {
        $key = $this->seriesKey('gauge', $name, $labelValues);
        $this->registerMeta('gauge', $name, $help, $labelNames, $labelValues, [], $key);
        $this->redis->hSet($key, 'v', (string) $value);
    }

    public function observeHistogram(string $name, string $help, array $labelNames, array $labelValues, array $buckets, float $value): void
    {
        $key = $this->seriesKey('histogram', $name, $labelValues);
        $this->registerMeta('histogram', $name, $help, $labelNames, $labelValues, $buckets, $key);
        $this->redis->hIncrByFloat($key, 'sum', $value);
        $this->redis->hIncrBy($key, 'count', 1);
        foreach ($buckets as $b) {
            if ($value <= $b) {
                $this->redis->hIncrBy($key, 'b:'.(string) $b, 1);
            }
        }
    }

    public function collect(): array
    {
        $index = $this->redis->hGetAll(self::INDEX_KEY);
        if (!is_array($index) || empty($index)) {
            return [];
        }

        $families = [];
        foreach ($index as $entryJson) {
            $entry = json_decode($entryJson, true);
            if (!is_array($entry)) {
                continue;
            }
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

            $hash = $this->redis->hGetAll($entry['key']);
            if ($type === 'counter') {
                $families[$famKey]['samples'][] = ['labels' => $entry['labelValues'], 'value' => (float) ($hash['v'] ?? 0)];
            } elseif ($type === 'gauge') {
                $families[$famKey]['samples'][] = ['labels' => $entry['labelValues'], 'value' => (float) ($hash['v'] ?? 0)];
            } else {
                // Buckets are stored cumulatively at observe time; emit as-is.
                foreach ($entry['buckets'] as $b) {
                    $families[$famKey]['samples'][] = [
                        'suffix' => '_bucket', 'labels' => $entry['labelValues'], 'le' => (string) $b, 'value' => (int) ($hash['b:'.(string) $b] ?? 0),
                    ];
                }
                $count = (int) ($hash['count'] ?? 0);
                $sum = (float) ($hash['sum'] ?? 0);
                $families[$famKey]['samples'][] = ['suffix' => '_bucket', 'labels' => $entry['labelValues'], 'le' => '+Inf', 'value' => $count];
                $families[$famKey]['samples'][] = ['suffix' => '_sum', 'labels' => $entry['labelValues'], 'value' => $sum];
                $families[$famKey]['samples'][] = ['suffix' => '_count', 'labels' => $entry['labelValues'], 'value' => $count];
            }
        }

        return array_values($families);
    }

    public function driverName(): string
    {
        return 'redis';
    }

    private function seriesKey(string $type, string $name, array $labelValues): string
    {
        return self::PREFIX.$type.':'.$name.':'.md5(implode("\x1f", $labelValues));
    }

    private function registerMeta(string $type, string $name, string $help, array $labelNames, array $labelValues, array $buckets, string $key): void
    {
        if ($this->redis->hExists(self::INDEX_KEY, $key)) {
            return;
        }
        $this->redis->hSet(self::INDEX_KEY, $key, json_encode([
            'type' => $type,
            'name' => $name,
            'help' => $help,
            'labelNames' => $labelNames,
            'labelValues' => $labelValues,
            'buckets' => $buckets,
            'key' => $key,
        ]));
    }
}
