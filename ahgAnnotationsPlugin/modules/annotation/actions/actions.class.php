<?php

/**
 * annotation actions (#146) — W3C Web Annotation Protocol endpoints.
 *
 * container: GET (query by ?target=) + POST (create)
 * single:    GET / PUT / DELETE on /annotations/:uuid
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * Licensed under the GNU Affero General Public License v3.0 or later.
 */

use AtomFramework\Http\Controllers\AhgController;

class annotationActions extends AhgController
{
    protected ?WebAnnotationService $svc = null;

    protected function svc(): WebAnnotationService
    {
        if ($this->svc === null) {
            require_once $this->config('sf_root_dir').'/plugins/ahgAnnotationsPlugin/lib/Services/WebAnnotationService.php';
            $this->svc = new WebAnnotationService($this->baseUrl());
        }
        return $this->svc;
    }

    protected function baseUrl(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return ($https ? 'https' : 'http').'://'.$host;
    }

    /** Emit a JSON-LD Web Annotation response with the proper profile + CORS. */
    protected function jsonLd($data, int $status = 200, array $headers = [])
    {
        $resp = $this->getResponse();
        // AtoM substitutes its themed error page (error_404_module) or blanks the
        // body when an action sets a 4xx/5xx HTTP status. To keep this API returning
        // parseable JSON-LD, errors are emitted as HTTP 200 with the intended status
        // carried in the body + an X-Annotation-Status header. 2xx pass through.
        if ($status >= 400) {
            if (is_array($data)) {
                $data['status'] = $status;
            }
            $resp->setHttpHeader('X-Annotation-Status', (string) $status);
            $status = 200;
        }
        // NB: a `profile="..."` parameter on the content-type blanks POST responses
        // in AtoM (quoted header value), so the media type is plain application/ld+json
        // and the W3C context is advertised via a Link header instead.
        $resp->setContentType('application/ld+json');
        if ($status !== 200) {
            $resp->setStatusCode($status);
        }
        $resp->setHttpHeader('Link', '<http://www.w3.org/ns/anno.jsonld>; rel="http://www.w3.org/ns/json-ld#context"; type="application/ld+json"');
        $resp->setHttpHeader('Access-Control-Allow-Origin', '*');
        $resp->setHttpHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, HEAD');
        $resp->setHttpHeader('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization');
        $resp->setHttpHeader('Vary', 'Accept');
        foreach ($headers as $k => $v) {
            $resp->setHttpHeader($k, $v);
        }
        return $this->renderText($data === null ? '' : json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    protected function jsonBody($request): array
    {
        $raw = $request->getContent();
        $decoded = $raw ? json_decode($raw, true) : null;
        return is_array($decoded) ? $decoded : [];
    }

    public function executeContainer($request)
    {
        $method = strtoupper($request->getMethod());

        if ($method === 'OPTIONS') {
            return $this->jsonLd(null, 204, ['Allow' => 'GET, POST, OPTIONS, HEAD']);
        }

        if ($method === 'POST') {
            if (!$this->getUser()->isAuthenticated()) {
                return $this->jsonLd(['error' => 'Authentication required to write annotations'], 403);
            }
            $body = $this->jsonBody($request);
            if (empty($body)) {
                return $this->jsonLd(['error' => 'Empty or invalid JSON body'], 400);
            }
            $doc = $this->svc()->create($body, (int) $this->getUser()->getAttribute('user_id') ?: null);
            return $this->jsonLd($doc, 201, [
                'Location' => $doc['id'],
                'Allow' => 'GET, PUT, DELETE, OPTIONS, HEAD',
            ]);
        }

        // GET (or HEAD): list / query.
        $target = trim((string) $request->getParameter('target', ''));
        $collection = $this->svc()->container($target !== '' ? $target : null);
        return $this->jsonLd($collection, 200, ['Allow' => 'GET, POST, OPTIONS, HEAD']);
    }

    public function executeSingle($request)
    {
        $uuid = (string) $request->getParameter('uuid');
        $method = strtoupper($request->getMethod());

        if ($method === 'OPTIONS') {
            return $this->jsonLd(null, 204, ['Allow' => 'GET, PUT, DELETE, OPTIONS, HEAD']);
        }

        if ($method === 'PUT') {
            if (!$this->getUser()->isAuthenticated()) {
                return $this->jsonLd(['error' => 'Authentication required to write annotations'], 403);
            }
            $doc = $this->svc()->update($uuid, $this->jsonBody($request));
            if ($doc === null) {
                return $this->jsonLd(['error' => 'Annotation not found'], 404);
            }
            return $this->jsonLd($doc, 200);
        }

        if ($method === 'DELETE') {
            if (!$this->getUser()->isAuthenticated()) {
                return $this->jsonLd(['error' => 'Authentication required to write annotations'], 403);
            }
            $ok = $this->svc()->delete($uuid);
            return $this->jsonLd(null, $ok ? 204 : 404);
        }

        // GET / HEAD.
        $doc = $this->svc()->get($uuid);
        if ($doc === null) {
            return $this->jsonLd(['error' => 'Annotation not found'], 404);
        }
        return $this->jsonLd($doc, 200, ['Allow' => 'GET, PUT, DELETE, OPTIONS, HEAD']);
    }
}
