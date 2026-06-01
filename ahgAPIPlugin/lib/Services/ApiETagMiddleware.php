<?php

namespace AhgAPIPlugin\Service;

/**
 * ApiETagMiddleware.
 *
 * AtoM (Symfony 1.x) port of the Heratio ahg-api ETagMiddleware.
 *
 * Adds a strong ETag header to API GET responses (sha256 of the body,
 * hex-truncated to 32 chars) and honours conditional requests via
 * If-None-Match (returning 304 Not Modified with an empty body).
 *
 * Heratio runs this as a Laravel pipeline middleware around the response.
 * AtoM has no middleware pipeline: the apiv2 actions extend sfAction and
 * emit their JSON body through renderText(). This class therefore exposes a
 * small, side-effect-free decision API plus an apply() convenience that the
 * base action can call at the single point where it knows the final body and
 * status. It owns NO shared state and edits NO other subsystem's files.
 *
 * Mirrors the Heratio behaviour exactly:
 *   - GET requests only
 *   - 2xx status codes only
 *   - empty body is skipped
 *   - bypass via an explicit flag (Heratio: request attribute "etag.bypass")
 *   - strong ETag, quoted, sha256 truncated to 32 hex chars
 *   - If-None-Match supports a comma list, "*", and weak ETags (W/"...")
 *
 * @author    The Archive and Heritage Group (Pty) Ltd
 * @license   GPL-3.0
 */
class ApiETagMiddleware
{
    /**
     * Compute the strong, quoted ETag for a response body.
     *
     * Identical algorithm to the Heratio ETagMiddleware:
     *   '"' . substr(sha256(body), 0, 32) . '"'
     */
    public function computeEtag(string $body): string
    {
        return '"' . substr(hash('sha256', $body), 0, 32) . '"';
    }

    /**
     * Decide whether an ETag should be applied for this request/response pair.
     *
     * @param string $method     HTTP method (e.g. from $request->getMethod())
     * @param int    $statusCode Final response status code
     * @param string $body       Final response body
     * @param bool   $bypass     True to skip ETag handling for this response
     */
    public function shouldApply(string $method, int $statusCode, string $body, bool $bypass = false): bool
    {
        if (strtoupper($method) !== 'GET') {
            return false;
        }

        if ($bypass) {
            return false;
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            return false;
        }

        if ($body === '') {
            return false;
        }

        return true;
    }

    /**
     * Does the supplied If-None-Match header match the given (strong) ETag?
     *
     * If-None-Match may carry a comma-separated list of ETags or "*".
     * Weak ETags (W/"...") are tolerated by stripping the weak prefix.
     *
     * @param string|null $header The raw If-None-Match header value
     */
    public function etagMatches(?string $header, string $etag): bool
    {
        if ($header === null) {
            return false;
        }

        $header = trim($header);
        if ($header === '') {
            return false;
        }

        if ($header === '*') {
            return true;
        }

        $candidates = array_map('trim', explode(',', $header));
        foreach ($candidates as $candidate) {
            // Tolerate weak ETags (W/"...")
            $stripped = preg_replace('/^W\//', '', $candidate);
            if ($stripped === $etag) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apply ETag handling to a Symfony 1.x sfWebResponse.
     *
     * Sets the ETag header when applicable. If the request's If-None-Match
     * matches, the response is converted to a 304 Not Modified: the status is
     * set to 304, the ETag header is retained, and Content-Length is removed.
     * The caller is responsible for emitting an empty body when this returns
     * true (sfAction can return sfView::NONE).
     *
     * This method does NOT write the body itself, so it is safe to call from
     * inside an action that uses renderText(): call it, and if it returns true,
     * skip renderText() and return sfView::NONE instead.
     *
     * @param object      $response    The sfWebResponse instance
     * @param string      $method      Request method
     * @param int         $statusCode  Final status code
     * @param string      $body        Final body that would be rendered
     * @param string|null $ifNoneMatch The request's If-None-Match header (null if absent)
     * @param bool        $bypass      True to skip ETag handling
     *
     * @return bool True if a 304 Not Modified should be emitted (empty body),
     *              false if the response should be rendered normally
     */
    public function apply($response, string $method, int $statusCode, string $body, ?string $ifNoneMatch, bool $bypass = false): bool
    {
        if (!$this->shouldApply($method, $statusCode, $body, $bypass)) {
            return false;
        }

        $etag = $this->computeEtag($body);

        // sfWebResponse exposes setHttpHeader(); Symfony\HttpFoundation exposes
        // a headers bag. Support both so the class is reusable across contexts.
        $this->setHeader($response, 'ETag', $etag);

        if ($this->etagMatches($ifNoneMatch, $etag)) {
            if (method_exists($response, 'setStatusCode')) {
                $response->setStatusCode(304);
            }
            $this->removeHeader($response, 'Content-Length');

            return true;
        }

        return false;
    }

    /**
     * Read the If-None-Match header from PHP's $_SERVER superglobal.
     *
     * Convenience for AtoM actions, which do not always have a clean accessor
     * for arbitrary request headers. Returns null when the header is absent.
     */
    public function getIfNoneMatchFromServer(): ?string
    {
        if (!empty($_SERVER['HTTP_IF_NONE_MATCH'])) {
            return (string) $_SERVER['HTTP_IF_NONE_MATCH'];
        }

        return null;
    }

    private function setHeader($response, string $name, string $value): void
    {
        if (is_object($response) && method_exists($response, 'setHttpHeader')) {
            // sfWebResponse
            $response->setHttpHeader($name, $value);

            return;
        }

        if (is_object($response) && isset($response->headers) && is_object($response->headers) && method_exists($response->headers, 'set')) {
            // Symfony\Component\HttpFoundation\Response
            $response->headers->set($name, $value);
        }
    }

    private function removeHeader($response, string $name): void
    {
        if (is_object($response) && method_exists($response, 'setHttpHeader')) {
            // sfWebResponse: clearing is done by setting an empty replace value.
            $response->setHttpHeader($name, null, true);

            return;
        }

        if (is_object($response) && isset($response->headers) && is_object($response->headers) && method_exists($response->headers, 'remove')) {
            $response->headers->remove($name);
        }
    }
}
