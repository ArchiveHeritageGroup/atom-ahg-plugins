<?php

/**
 * AbstractLookupAdapter - shared base for the seven external-source adapters.
 *
 * Centralises:
 *   - settings reads (lookup.<source>.<param>) via Capsule on ahg_settings
 *   - cache hit/miss against ahg_authority_lookup_cache with TTL respect
 *   - HTTP via raw curl (no Laravel facades, no Guzzle on SF1.4)
 *
 * Subclasses override:
 *   - source(): the stable identifier
 *   - executeRemote(): the actual HTTP call + response normalisation
 *
 * Mirror of the Laravel-side AbstractLookupAdapter.
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * This file is part of the AHG Authority Resolution Engine plugin for
 * AtoM Heratio. Licensed under the GNU General Public License v3.0 or later.
 */

namespace AtomFramework\Services\AuthorityResolution\Lookup;

use Illuminate\Database\Capsule\Manager as DB;

require_once dirname(__FILE__) . '/LookupAdapterInterface.php';

abstract class AbstractLookupAdapter implements LookupAdapterInterface
{
    /** Default HTTP timeout (seconds). Subclasses may override via settings. */
    protected const HTTP_TIMEOUT = 10;

    abstract public function source(): string;

    /**
     * Subclass-supplied implementation. Called only when:
     *   - source is enabled AND
     *   - no fresh cache row exists for (source, entity_type, query_text).
     * MUST NOT touch the cache; the base class writes it on return.
     *
     * @return array{results: array<int, array<string, mixed>>, error?: string}
     */
    abstract protected function executeRemote(string $entityType, string $queryText): array;

    public function isEnabled(): bool
    {
        return (string) $this->setting('enabled', '0') === '1';
    }

    public function lookup(string $entityType, string $queryText): array
    {
        $queryText = trim($queryText);
        if ($queryText === '') {
            return ['source' => $this->source(), 'results' => [], 'cached' => false];
        }

        if (!$this->isEnabled()) {
            return ['source' => $this->source(), 'results' => [], 'cached' => false];
        }

        $cached = $this->loadCache($entityType, $queryText);
        if ($cached !== null) {
            return [
                'source' => $this->source(),
                'results' => $cached,
                'cached' => true,
            ];
        }

        $payload = $this->executeRemote($entityType, $queryText);
        if (!is_array($payload)) {
            $payload = ['results' => [], 'error' => 'malformed adapter return'];
        }
        $results = is_array($payload['results'] ?? null) ? $payload['results'] : [];

        // Cache even empty result-sets so we don't hammer the API. Error responses
        // are NOT cached so transient failures recover automatically.
        if (!isset($payload['error'])) {
            $this->storeCache($entityType, $queryText, $results);
        }

        return [
            'source' => $this->source(),
            'results' => $results,
            'cached' => false,
            'error' => $payload['error'] ?? null,
        ];
    }

    // ---------------------------------------------------------------------
    // Settings + cache helpers (shared by every concrete adapter)
    // ---------------------------------------------------------------------

    protected function setting(string $param, $default = null)
    {
        $key = 'authority_resolution.lookup.' . $this->source() . '.' . $param;
        try {
            $row = DB::table('ahg_settings')->where('setting_key', $key)->value('setting_value');
            return ($row !== null && $row !== '') ? $row : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    protected function cacheTtl(): int
    {
        return max(0, (int) $this->setting('cache_ttl', 86400));
    }

    protected function loadCache(string $entityType, string $queryText): ?array
    {
        try {
            $row = DB::table('ahg_authority_lookup_cache')
                ->where('source', $this->source())
                ->where('entity_type', $entityType)
                ->where('query_text', $queryText)
                ->first();
            if (!$row) {
                return null;
            }
            $age = time() - strtotime((string) $row->retrieved_at);
            if ($age > (int) $row->ttl_seconds) {
                return null;
            }
            $decoded = json_decode((string) $row->payload, true);
            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function storeCache(string $entityType, string $queryText, array $results): void
    {
        $now = date('Y-m-d H:i:s');
        $ttl = $this->cacheTtl();
        $licenseNote = (string) ($this->setting('license_note', '') ?: '');
        $payload = json_encode($results, JSON_UNESCAPED_UNICODE);

        try {
            DB::table('ahg_authority_lookup_cache')
                ->updateOrInsert(
                    [
                        'source' => $this->source(),
                        'entity_type' => $entityType,
                        'query_text' => $queryText,
                    ],
                    [
                        'payload' => $payload,
                        'license_note' => $licenseNote !== '' ? $licenseNote : null,
                        'retrieved_at' => $now,
                        'ttl_seconds' => $ttl,
                    ]
                );
        } catch (\Throwable $e) {
            // Cache write failure is non-fatal - prefill still returns the live results.
        }
    }

    /**
     * Run a GET request with curl, returning either the decoded JSON body or
     * an array with an `error` key. Honours the per-adapter HTTP_TIMEOUT.
     *
     * @return array
     */
    protected function httpGetJson(string $url, array $extraHeaders = []): array
    {
        $ch = curl_init($url);
        $headers = array_merge(['Accept: application/json'], $extraHeaders);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => static::HTTP_TIMEOUT,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERAGENT => 'AHG-AuthorityResolution/0.1 (+https://heratio.theahg.co.za)',
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return ['error' => 'curl: ' . $err];
        }
        if ($status < 200 || $status >= 300) {
            $snippet = is_string($body) ? substr($body, 0, 200) : '';
            return ['error' => "HTTP {$status}" . ($snippet !== '' ? ': ' . $snippet : '')];
        }
        $decoded = is_string($body) ? json_decode($body, true) : null;
        if (!is_array($decoded)) {
            return ['error' => 'non-JSON or empty response'];
        }
        return $decoded;
    }
}
