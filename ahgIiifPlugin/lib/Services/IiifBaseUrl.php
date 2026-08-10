<?php

/**
 * Where this instance's IIIF identifiers point.
 *
 * IIIF identifiers - annotation `@id`s, content-state target URIs, manifest ids -
 * are durable identifiers rather than convenience URLs. Getting the base wrong
 * outlives the request that got it wrong: the identifier is stored, shared, and
 * quoted back later by something that was not there when it was minted.
 *
 * This existed in four places with four different answers, two of which were
 * wrong in the same way:
 *
 *     $host = $_SERVER['HTTP_HOST'] ?? 'psis.theahg.co.za';
 *     $this->baseUrl = "https://{$host}";
 *
 * A hardcoded scheme means an instance served over http mints https identifiers,
 * and a viewer on an http page then refuses to fetch them as mixed content. A
 * customer's domain as the fallback means any request without HTTP_HOST - CLI
 * tasks, queued jobs, sync - mints identifiers pointing at somebody else's site.
 *
 * Precedence follows get_iiif_base_url() in IiifViewerHelper, which was already
 * the plugin's answer to this question. Configuration first, because a site that
 * cares about durable identifiers should pin them rather than let them follow
 * whichever hostname a request happened to arrive on.
 *
 * @author The Archive and Heritage Group
 */
final class IiifBaseUrl
{
    /**
     * Base URL with no trailing slash, e.g. https://archive.example.org
     */
    public static function detect(): string
    {
        if (class_exists('sfConfig')) {
            foreach (['app_iiif_base_url', 'app_siteBaseUrl'] as $key) {
                $configured = (string) \sfConfig::get($key, '');

                if ('' !== $configured) {
                    return rtrim($configured, '/');
                }
            }
        }

        if (empty($_SERVER['HTTP_HOST'])) {
            // No request to derive from. localhost is obviously local and
            // obviously wrong, which is the point: it cannot be mistaken for a
            // real published identifier the way another site's domain can.
            return 'http://localhost';
        }

        return self::scheme().'://'.$_SERVER['HTTP_HOST'];
    }

    /**
     * The scheme the client actually used.
     *
     * HTTPS is unset in php-fpm when nginx terminates TLS and does not pass it,
     * so the proxy header has to be honoured or every identifier minted behind a
     * TLS-terminating proxy comes out as http.
     */
    public static function scheme(): string
    {
        if (!empty($_SERVER['HTTPS']) && 'off' !== $_SERVER['HTTPS']) {
            return 'https';
        }

        if ('https' === ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) {
            return 'https';
        }

        if (443 === (int) ($_SERVER['SERVER_PORT'] ?? 0)) {
            return 'https';
        }

        return 'http';
    }
}
