<?php

declare(strict_types=1);

namespace arMuseumMetadataPlugin\Services\Getty;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Getty Cache Service.
 *
 * Caches Getty vocabulary lookups to reduce API calls and improve performance.
 * Supports file-based caching with configurable TTL.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class GettyCacheService
{
    private string $cacheDir;
    private LoggerInterface $logger;
    private bool $enabled;

    public function __construct(
        string $cacheDir = '/tmp/getty_cache',
        ?LoggerInterface $logger = null,
        bool $enabled = true
    ) {
        $this->cacheDir = rtrim($cacheDir, '/');
        $this->logger = $logger ?? new NullLogger();
        $this->enabled = $enabled;

        if ($enabled) {
            $this->ensureCacheDirectory();
        }
    }

    /**
     * Get cached value.
     *
     * @param string $key Cache key
     *
     * @return mixed Cached value or null if not found/expired
     */
    public function get(string $key): mixed
    {
        if (!$this->enabled) {
            return null;
        }

        $file = $this->getCacheFile($key);

        if (!file_exists($file)) {
            return null;
        }

        $content = file_get_contents($file);
        if (false === $content) {
            return null;
        }

        $data = unserialize($content);

        if (!is_array($data) || !isset($data['expires'], $data['value'])) {
            @unlink($file);

            return null;
        }

        if ($data['expires'] < time()) {
            $this->logger->debug('Getty cache expired', ['key' => $key]);
            @unlink($file);

            return null;
        }

        $this->logger->debug('Getty cache hit', ['key' => $key]);

        return $data['value'];
    }

    /**
     * Set cached value.
     *
     * @param string $key   Cache key
     * @param mixed  $value Value to cache
     * @param int    $ttl   Time to live in seconds (default: 24 hours)
     */
    public function set(string $key, mixed $value, int $ttl = 86400): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $file = $this->getCacheFile($key);
        $data = [
            'expires' => time() + $ttl,
            'value' => $value,
            'created' => time(),
            'key' => $key,
        ];

        $result = file_put_contents($file, serialize($data), LOCK_EX);

        if (false === $result) {
            $this->logger->warning('Getty cache write failed', ['key' => $key, 'file' => $file]);

            return false;
        }

        $this->logger->debug('Getty cache set', ['key' => $key, 'ttl' => $ttl]);

        return true;
    }

    /**
     * Delete cached value.
     */
    public function delete(string $key): bool
    {
        $file = $this->getCacheFile($key);

        if (file_exists($file)) {
            return @unlink($file);
        }

        return true;
    }

    /**
     * Clear all cache for a vocabulary.
     */
    public function clearVocabulary(string $vocabulary): int
    {
        $pattern = $this->cacheDir."/*_{$vocabulary}_*.cache";
        $files = glob($pattern);
        $count = 0;

        foreach ($files as $file) {
            if (@unlink($file)) {
                ++$count;
            }
        }

        $this->logger->info('Getty cache cleared for vocabulary', [
            'vocabulary' => $vocabulary,
            'files_deleted' => $count,
        ]);

        return $count;
    }

    /**
     * Clear entire cache.
     */
    public function clearAll(): int
    {
        $pattern = $this->cacheDir.'/*.cache';
        $files = glob($pattern);
        $count = 0;

        foreach ($files as $file) {
            if (@unlink($file)) {
                ++$count;
            }
        }

        $this->logger->info('Getty cache cleared completely', ['files_deleted' => $count]);

        return $count;
    }

    /**
     * Get cache statistics.
     */
    public function getStats(): array
    {
        $pattern = $this->cacheDir.'/*.cache';
        $files = glob($pattern);

        $stats = [
            'total_files' => count($files),
            'total_size' => 0,
            'expired' => 0,
            'valid' => 0,
            'by_vocabulary' => [
                'aat' => 0,
                'tgn' => 0,
                'ulan' => 0,
            ],
        ];

        foreach ($files as $file) {
            $stats['total_size'] += filesize($file);

            $content = file_get_contents($file);
            if ($content) {
                $data = @unserialize($content);
                if (is_array($data) && isset($data['expires'])) {
                    if ($data['expires'] < time()) {
                        ++$stats['expired'];
                    } else {
                        ++$stats['valid'];
                    }

                    // Count by vocabulary
                    foreach (array_keys($stats['by_vocabulary']) as $vocab) {
                        if (isset($data['key']) && str_contains($data['key'], "_{$vocab}_")) {
                            ++$stats['by_vocabulary'][$vocab];
                        }
                    }
                }
            }
        }

        $stats['total_size_mb'] = round($stats['total_size'] / 1048576, 2);

        return $stats;
    }

    /**
     * Prune expired cache entries.
     */
    public function prune(): int
    {
        $pattern = $this->cacheDir.'/*.cache';
        $files = glob($pattern);
        $count = 0;

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content) {
                $data = @unserialize($content);
                if (is_array($data) && isset($data['expires']) && $data['expires'] < time()) {
                    if (@unlink($file)) {
                        ++$count;
                    }
                }
            }
        }

        $this->logger->info('Getty cache pruned', ['expired_deleted' => $count]);

        return $count;
    }

    /**
     * Get cache file path for key.
     */
    private function getCacheFile(string $key): string
    {
        // Sanitize key for filesystem
        $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);

        return $this->cacheDir.'/'.$safeKey.'.cache';
    }

    /**
     * Ensure cache directory exists.
     */
    private function ensureCacheDirectory(): void
    {
        if (!is_dir($this->cacheDir)) {
            if (!mkdir($this->cacheDir, 0755, true)) {
                $this->logger->error('Failed to create Getty cache directory', [
                    'directory' => $this->cacheDir,
                ]);
                $this->enabled = false;
            }
        }

        if (!is_writable($this->cacheDir)) {
            $this->logger->warning('Getty cache directory not writable', [
                'directory' => $this->cacheDir,
            ]);
            $this->enabled = false;
        }
    }
}
