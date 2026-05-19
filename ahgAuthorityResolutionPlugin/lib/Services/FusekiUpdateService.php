<?php

/**
 * FusekiUpdateService — service for AtoM Heratio
 *
 * Thin SPARQL UPDATE client for the authority-resolution plugin. Reuses the
 * existing ahg_settings keys (fuseki_endpoint, fuseki_update_endpoint,
 * fuseki_username, fuseki_password) so it lives within the established
 * Heratio Fuseki integration pattern rather than introducing a new
 * framework-level abstraction.
 *
 * AtoM has no centralised Fuseki client; ahgRicExplorerPlugin actions call
 * curl ad-hoc. Rather than refactor a locked plugin, this service keeps the
 * write path inside ahgAuthorityResolutionPlugin and mirrors the contract
 * of ahg-ric's SparqlUpdateService::executeUpdate() on the Laravel side.
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * This file is part of the AHG Authority Resolution Engine plugin for
 * AtoM Heratio. Licensed under the GNU General Public License v3.0 or later,
 * matching the parent atom-ahg-plugins repository.
 */

namespace AtomFramework\Services\AuthorityResolution;

use Illuminate\Database\Capsule\Manager as DB;

class FusekiUpdateService
{
    /**
     * Execute a SPARQL UPDATE statement against the configured Fuseki
     * update endpoint. Returns ['ok'=>bool, 'status'=>int, 'error'=>?string].
     *
     * @param string $sparqlUpdate Full SPARQL UPDATE statement (PREFIX + INSERT/DELETE DATA).
     * @return array{ok:bool, status:int, error:?string}
     */
    public function executeUpdate(string $sparqlUpdate): array
    {
        $endpoint = $this->resolveUpdateEndpoint();
        if ($endpoint === '') {
            return ['ok' => false, 'status' => 0, 'error' => 'fuseki_update_endpoint not configured'];
        }
        $username = $this->setting('fuseki_username', 'admin');
        $password = $this->setting('fuseki_password', '');

        $ch = curl_init($endpoint);
        $headers = ['Content-Type: application/sparql-update'];
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $sparqlUpdate,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => (int) $this->setting('fuseki_update_timeout', 30),
            CURLOPT_FAILONERROR => false,
        ]);
        if ($username !== '' || $password !== '') {
            curl_setopt($ch, CURLOPT_USERPWD, "{$username}:{$password}");
        }

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $cerr = curl_error($ch);
        curl_close($ch);

        if ($cerr) {
            return ['ok' => false, 'status' => $status, 'error' => "curl: {$cerr}"];
        }
        if ($status >= 200 && $status < 300) {
            return ['ok' => true, 'status' => $status, 'error' => null];
        }
        $msg = is_string($body) ? trim($body) : '';
        return ['ok' => false, 'status' => $status, 'error' => "HTTP {$status}" . ($msg !== '' ? ": {$msg}" : '')];
    }

    /**
     * Execute a SPARQL SELECT query against the Fuseki query endpoint (NOT the
     * update endpoint - the brief is explicit: replace `/update` with
     * `/sparql`). GET with Accept: application/sparql-results+json. Returns
     * ['ok'=>bool, 'status'=>int, 'json'=>?array, 'error'=>?string].
     *
     * Added in Task 10 (CLI consolidation) so auth-res:status can read graph
     * triple counts without bringing in a second HTTP abstraction.
     *
     * @return array{ok:bool, status:int, json:?array, error:?string}
     */
    public function executeQuery(string $sparqlQuery): array
    {
        $endpoint = $this->resolveQueryEndpoint();
        if ($endpoint === '') {
            return ['ok' => false, 'status' => 0, 'json' => null, 'error' => 'fuseki query endpoint not configured'];
        }
        $username = $this->setting('fuseki_username', 'admin');
        $password = $this->setting('fuseki_password', '');

        $url = $endpoint . (strpos($endpoint, '?') === false ? '?' : '&') . 'query=' . rawurlencode($sparqlQuery);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => ['Accept: application/sparql-results+json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => (int) $this->setting('fuseki_query_timeout', 15),
            CURLOPT_FAILONERROR => false,
        ]);
        if ($username !== '' || $password !== '') {
            curl_setopt($ch, CURLOPT_USERPWD, "{$username}:{$password}");
        }
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $cerr = curl_error($ch);
        curl_close($ch);

        if ($cerr) {
            return ['ok' => false, 'status' => $status, 'json' => null, 'error' => "curl: {$cerr}"];
        }
        if ($status < 200 || $status >= 300) {
            $msg = is_string($body) ? trim($body) : '';
            return ['ok' => false, 'status' => $status, 'json' => null, 'error' => "HTTP {$status}" . ($msg !== '' ? ": {$msg}" : '')];
        }
        $json = is_string($body) ? json_decode($body, true) : null;
        if (!is_array($json)) {
            return ['ok' => false, 'status' => $status, 'json' => null, 'error' => 'invalid JSON from Fuseki'];
        }
        return ['ok' => true, 'status' => $status, 'json' => $json, 'error' => null];
    }

    /**
     * Convenience: run a COUNT-shaped SPARQL query and return the integer bound
     * to the first variable, or null on failure.
     */
    public function queryCount(string $sparqlQuery): ?int
    {
        $res = $this->executeQuery($sparqlQuery);
        if (!$res['ok'] || !isset($res['json']['results']['bindings'][0])) {
            return null;
        }
        $binding = $res['json']['results']['bindings'][0];
        // First variable in head.vars, in declared order.
        $vars = $res['json']['head']['vars'] ?? [];
        foreach ($vars as $v) {
            if (isset($binding[$v]['value'])) {
                return (int) $binding[$v]['value'];
            }
        }
        return null;
    }

    /**
     * Resolve the SPARQL query endpoint URL. Mirrors resolveUpdateEndpoint but
     * for reads. Order of precedence:
     *   1. ahg_settings.fuseki_query_endpoint (explicit)
     *   2. ahg_settings.fuseki_update_endpoint with trailing /update -> /sparql
     *   3. ahg_settings.fuseki_endpoint + '/sparql' (derived)
     *   4. '' (caller errors out)
     */
    private function resolveQueryEndpoint(): string
    {
        $explicit = $this->setting('fuseki_query_endpoint', '');
        if (is_string($explicit) && $explicit !== '') {
            return $explicit;
        }
        $update = $this->setting('fuseki_update_endpoint', '');
        if (is_string($update) && $update !== '') {
            if (substr($update, -7) === '/update') {
                return substr($update, 0, -7) . '/sparql';
            }
        }
        $base = $this->setting('fuseki_endpoint', '');
        if (is_string($base) && $base !== '') {
            return rtrim($base, '/') . '/sparql';
        }
        return '';
    }

    /**
     * Resolve the SPARQL UPDATE endpoint URL. Order of precedence:
     *   1. ahg_settings.fuseki_update_endpoint (explicit)
     *   2. ahg_settings.fuseki_endpoint + '/update' (derived)
     *   3. '' (caller errors out)
     */
    private function resolveUpdateEndpoint(): string
    {
        $explicit = $this->setting('fuseki_update_endpoint', '');
        if (is_string($explicit) && $explicit !== '') {
            return $explicit;
        }
        $base = $this->setting('fuseki_endpoint', '');
        if (is_string($base) && $base !== '') {
            return rtrim($base, '/') . '/update';
        }
        return '';
    }

    private function setting(string $key, $default = null)
    {
        try {
            $row = DB::table('ahg_settings')->where('setting_key', $key)->value('setting_value');
            return ($row !== null && $row !== '') ? $row : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }
}
