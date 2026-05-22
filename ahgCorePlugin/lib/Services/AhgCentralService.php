<?php

/**
 * AhgCentralService - AtoM-AHG client for the AHG Central cloud service.
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * The Archive and Heritage Group (Pty) Ltd
 *
 * Licensed under the GNU Affero General Public License v3.0 or later.
 *
 * AtoM-AHG counterpart of the Heratio (Laravel) AhgCore\Services\AhgCentralService
 * (heratio#127). Sends a heartbeat (this install is alive + on which version)
 * and, opt-in, a redacted open-error sync to AHG Central (central.theahg.co.za).
 *
 * Settings (managed by the AHG Integration settings page, read here via
 * AtomExtensions\Services\SettingService):
 *   - ahg_central_enabled      master switch for all outbound calls
 *   - ahg_central_error_sync   separate opt-in for the error-log sync
 *   - ahg_central_api_url      Central API base (default central.theahg.co.za)
 *   - ahg_central_api_key      shared fleet enrolment key (Bearer)
 *   - ahg_central_site_id      this install's id (falls back to the hostname)
 *
 * Central is advisory and best-effort - every method fails soft and never
 * throws, so it can never gate or break the AtoM site.
 */

namespace AhgCore\Services;

use Illuminate\Database\Capsule\Manager as DB;

class AhgCentralService
{
    /** Per-run cap on rows pulled from ahg_error_log into one POST. */
    private const ERROR_BATCH_MAX = 500;

    /** Deploy default for the Central API base. */
    private const DEFAULT_API_URL = 'https://central.theahg.co.za/api/v1';

    public function isEnabled(): bool
    {
        return $this->boolSetting('ahg_central_enabled');
    }

    /**
     * Error-sync is a separate opt-in on top of isEnabled(): error logs can
     * carry stack traces / PII, so shipping them off-box needs its own consent.
     */
    public function errorSyncEnabled(): bool
    {
        return $this->boolSetting('ahg_central_error_sync');
    }

    public function apiUrl(): string
    {
        $url = rtrim($this->setting('ahg_central_api_url', ''), '/');

        return $url !== '' ? $url : self::DEFAULT_API_URL;
    }

    public function apiKey(): string
    {
        return (string) $this->setting('ahg_central_api_key', '');
    }

    /** Operator-set value wins; otherwise auto-derive from the hostname. */
    public function siteId(): string
    {
        $id = (string) $this->setting('ahg_central_site_id', '');

        return $id !== '' ? $id : $this->defaultSiteId();
    }

    /** 'atom-' + sanitised hostname - the auto-derived site_id. */
    public function defaultSiteId(): string
    {
        $host = strtolower((string) (gethostname() ?: 'unknown'));
        $host = preg_replace('/[^a-z0-9._-]/', '-', $host) ?: 'unknown';

        return 'atom-' . $host;
    }

    /**
     * Synchronous reachability check. Returns ['ok'=>bool,'http'=>int,...].
     * Used by the `central:ping` task.
     */
    public function ping(): array
    {
        if (!$this->isEnabled()) {
            return ['ok' => false, 'http' => 0, 'error' => 'ahg_central_enabled is off'];
        }
        if ($this->apiUrl() === '') {
            return ['ok' => false, 'http' => 0, 'error' => 'ahg_central_api_url is empty'];
        }

        return $this->request('GET', '/ping');
    }

    /**
     * Heartbeat: tells Central this install is alive + on which version. An
     * unknown site_id presenting the fleet key auto-enrols on the Central side.
     * No-ops when disabled.
     */
    public function heartbeat(): array
    {
        if (!$this->isEnabled()) {
            return ['ok' => false, 'http' => 0, 'error' => 'ahg_central_enabled is off'];
        }
        if ($this->apiUrl() === '' || $this->siteId() === '') {
            return ['ok' => false, 'http' => 0, 'error' => 'apiUrl or siteId is empty'];
        }

        return $this->request('POST', '/heartbeat', [
            'site_id'   => $this->siteId(),
            'version'   => $this->version(),
            'timestamp' => gmdate('c'),
        ]);
    }

    /**
     * Push the current open ahg_error_log rows to Central.
     *
     * Open errors only (resolved_at IS NULL) and a full replace: each run
     * sends the current open set and Central stores exactly that, so resolving
     * an error at source removes it from the fleet view. Every text field is
     * redacted before it leaves the building; the PII-heavy columns (trace,
     * client_ip, user_agent, user_id, request_id) are never sent. Best-effort.
     *
     * @return array{ok:bool,sent:int,error?:string,http?:int}
     */
    public function syncErrors(int $batch = 500): array
    {
        if (!$this->isEnabled()) {
            return ['ok' => false, 'sent' => 0, 'error' => 'ahg_central_enabled is off'];
        }
        if (!$this->errorSyncEnabled()) {
            return ['ok' => false, 'sent' => 0, 'error' => 'ahg_central_error_sync is off'];
        }
        if ($this->apiUrl() === '' || $this->siteId() === '') {
            return ['ok' => false, 'sent' => 0, 'error' => 'apiUrl or siteId is empty'];
        }

        $cap = max(1, min($batch, self::ERROR_BATCH_MAX));

        try {
            $rows = DB::table('ahg_error_log')
                ->whereNull('resolved_at')
                ->orderBy('id', 'desc')
                ->limit($cap)
                ->get();
        } catch (\Throwable $e) {
            error_log('[ahg-central] ahg_error_log read failed: ' . $e->getMessage());

            return ['ok' => false, 'sent' => 0, 'error' => 'ahg_error_log read failed'];
        }

        $payload = [];
        foreach ($rows as $r) {
            $payload[] = [
                'occurred_at'     => (string) ($r->created_at ?? ''),
                'level'           => (string) ($r->level ?? ''),
                'status_code'     => isset($r->status_code) && $r->status_code !== null ? (int) $r->status_code : null,
                'message'         => $this->redact((string) ($r->message ?? '')),
                'exception_class' => (string) ($r->exception_class ?? ''),
                'file'            => (string) ($r->file ?? ''),
                'line'            => isset($r->line) && $r->line !== null ? (int) $r->line : null,
                'url'             => $this->redact($this->stripQuery((string) ($r->url ?? ''))),
                'http_method'     => (string) ($r->http_method ?? ''),
                'hostname'        => (string) ($r->hostname ?? ''),
                // Stable dedup key on the Central side - the origin row id.
                'fingerprint'     => (string) $r->id,
            ];
        }

        // replace=true: Central drops this site's existing rows and stores
        // exactly the open set posted here.
        $result = $this->request('POST', '/errors', ['errors' => $payload, 'replace' => true]);
        if (!empty($result['ok'])) {
            return ['ok' => true, 'sent' => count($payload), 'http' => (int) ($result['http'] ?? 0)];
        }

        return [
            'ok'    => false,
            'sent'  => 0,
            'http'  => (int) ($result['http'] ?? 0),
            'error' => 'POST /errors returned non-2xx',
        ];
    }

    /**
     * Mask PII (emails + 9+-digit runs) before a value leaves the building.
     * Reuses the ahgAIPlugin GuardrailService::maskPii() when that class is
     * loadable; otherwise an equivalent inline pass so redaction never no-ops.
     */
    private function redact(string $text): string
    {
        if ($text === '') {
            return '';
        }

        try {
            if (class_exists('GuardrailService')) {
                $res = (new \GuardrailService())->maskPii($text);
                if (is_array($res) && isset($res[0])) {
                    return (string) $res[0];
                }
            }
        } catch (\Throwable $e) {
            // fall through to the inline equivalent
        }

        $text = preg_replace('/[\w.+-]+@[\w-]+\.[\w.-]+/u', '[REDACTED:email]', $text) ?? $text;
        $text = preg_replace_callback(
            '/\+?\d[\d\s().-]{6,}\d/u',
            function ($m) {
                return strlen(preg_replace('/\D/', '', $m[0])) >= 9 ? '[REDACTED:number]' : $m[0];
            },
            $text
        ) ?? $text;

        return $text;
    }

    /** Drop the query string from a URL so tokens in ?params never ship. */
    private function stripQuery(string $url): string
    {
        if ($url === '') {
            return '';
        }
        $q = strpos($url, '?');

        return $q === false ? $url : substr($url, 0, $q);
    }

    private function request(string $method, string $path, ?array $jsonBody = null): array
    {
        $url = $this->apiUrl() . $path;

        $ch = curl_init($url);
        $headers = [
            'Authorization: Bearer ' . $this->apiKey(),
            'X-Heratio-Site-Id: ' . $this->siteId(),
        ];
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CUSTOMREQUEST  => $method,
        ];
        if ($jsonBody !== null) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($jsonBody);
            $headers[] = 'Content-Type: application/json';
        }
        $opts[CURLOPT_HTTPHEADER] = $headers;
        curl_setopt_array($ch, $opts);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            error_log('[ahg-central] curl error: ' . $err . ' (' . $url . ')');

            return ['ok' => false, 'http' => 0, 'error' => $err];
        }

        $ok = $httpCode >= 200 && $httpCode < 300;
        if (!$ok) {
            error_log('[ahg-central] non-2xx ' . $httpCode . ' from ' . $url);
        }

        return ['ok' => $ok, 'http' => $httpCode, 'response' => substr((string) $response, 0, 1024)];
    }

    /** Plugin-collection version, for the heartbeat. Best-effort. */
    private function version(): string
    {
        foreach (['/../../../version.json', '/../../version.json'] as $rel) {
            $path = __DIR__ . $rel;
            if (is_file($path)) {
                $json = json_decode((string) @file_get_contents($path), true);
                if (is_array($json) && isset($json['version'])) {
                    return (string) $json['version'];
                }
            }
        }

        return '';
    }

    /**
     * Read an ahg_central_* setting via the same store the AHG Integration
     * settings page writes to (AtomExtensions\Services\SettingService).
     */
    private function setting(string $key, string $default = ''): string
    {
        try {
            $setting = \AtomExtensions\Services\SettingService::getByName($key);
            if ($setting) {
                $value = $setting->getValue(['sourceCulture' => true]);
                if ($value !== null && $value !== '') {
                    return (string) $value;
                }
            }
        } catch (\Throwable $e) {
            // settings store unavailable - fall back to the default
        }

        return $default;
    }

    /** A boolean setting - the form stores '1' / '0'. */
    private function boolSetting(string $key): bool
    {
        $value = $this->setting($key, '0');

        return $value === '1' || $value === 'true';
    }
}
