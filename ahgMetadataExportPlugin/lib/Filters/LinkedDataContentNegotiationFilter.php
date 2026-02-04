<?php

/**
 * Linked Data Content Negotiation Filter
 *
 * Intercepts requests and checks Accept header for JSON-LD preference.
 * When client requests application/ld+json, redirects to JSON-LD endpoint.
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage Filters
 * @author     The Archive and Heritage Group (Pty) Ltd
 */

class LinkedDataContentNegotiationFilter extends sfFilter
{
    /**
     * Execute the filter
     *
     * @param sfFilterChain $filterChain
     */
    public function execute($filterChain)
    {
        // Only run on first call
        if ($this->isFirstCall()) {
            $request = $this->getContext()->getRequest();
            $response = $this->getContext()->getResponse();

            // Check if this is a request that could serve JSON-LD
            if ($this->shouldNegotiate($request)) {
                $acceptHeader = $request->getHttpHeader('Accept', '');

                if ($this->prefersJsonLd($acceptHeader)) {
                    // Get current slug
                    $slug = $this->extractSlug($request);

                    if ($slug) {
                        // Determine resource type from current route
                        $type = $this->determineResourceType($request);

                        // Redirect to JSON-LD endpoint
                        $jsonldUrl = $this->buildJsonLdUrl($slug, $type);

                        $response->setStatusCode(303); // See Other
                        $response->setHttpHeader('Location', $jsonldUrl);
                        $response->setHttpHeader('Vary', 'Accept');
                        $response->setContent('');
                        $response->send();

                        return;
                    }
                }

                // Add Vary header for caching
                $response->setHttpHeader('Vary', 'Accept');

                // Add Link header pointing to JSON-LD alternate
                $slug = $this->extractSlug($request);
                if ($slug) {
                    $type = $this->determineResourceType($request);
                    $jsonldUrl = $this->buildJsonLdUrl($slug, $type);
                    $linkHeader = '<' . $jsonldUrl . '>; rel="alternate"; type="application/ld+json"';
                    $response->setHttpHeader('Link', $linkHeader);
                }
            }
        }

        // Continue with filter chain
        $filterChain->execute();
    }

    /**
     * Check if content negotiation should be performed for this request
     */
    protected function shouldNegotiate(sfWebRequest $request): bool
    {
        $module = $request->getParameter('module');
        $action = $request->getParameter('action');

        // Only negotiate for view/index actions of relevant modules
        $negotiableModules = [
            'informationobject' => ['index'],
            'repository' => ['index'],
            'actor' => ['index'],
            'sfIsadPlugin' => ['index'],
            'sfDcPlugin' => ['index'],
            'sfRadPlugin' => ['index'],
            'sfIsdfPlugin' => ['index'],
        ];

        if (isset($negotiableModules[$module])) {
            return in_array($action, $negotiableModules[$module]);
        }

        return false;
    }

    /**
     * Check if Accept header prefers JSON-LD
     */
    protected function prefersJsonLd(string $acceptHeader): bool
    {
        if (empty($acceptHeader)) {
            return false;
        }

        // Quick check for explicit JSON-LD request
        if (strpos($acceptHeader, 'application/ld+json') !== false) {
            // Parse to check quality factor
            $types = $this->parseAcceptHeader($acceptHeader);

            foreach ($types as $type => $q) {
                if ($type === 'application/ld+json' && $q > 0) {
                    // Check if HTML has higher priority
                    $htmlQ = $types['text/html'] ?? ($types['*/*'] ?? 0);
                    return $q >= $htmlQ;
                }
            }
        }

        return false;
    }

    /**
     * Parse Accept header into type => quality array
     */
    protected function parseAcceptHeader(string $header): array
    {
        $types = [];

        $parts = array_map('trim', explode(',', $header));

        foreach ($parts as $part) {
            $segments = explode(';', $part);
            $mediaType = trim($segments[0]);
            $q = 1.0;

            foreach ($segments as $segment) {
                $segment = trim($segment);
                if (strpos($segment, 'q=') === 0) {
                    $q = (float) substr($segment, 2);
                }
            }

            $types[$mediaType] = $q;
        }

        return $types;
    }

    /**
     * Extract slug from current request
     */
    protected function extractSlug(sfWebRequest $request): ?string
    {
        // Try route parameters first
        $slug = $request->getParameter('slug');

        if (!$slug) {
            // Try to extract from URI
            $uri = $request->getUri();
            $path = parse_url($uri, PHP_URL_PATH);

            // Remove leading slash and any prefix
            $path = ltrim($path, '/');

            // Remove index.php if present
            if (strpos($path, 'index.php/') === 0) {
                $path = substr($path, 10);
            }

            // Handle module-prefixed paths
            $prefixes = ['repository/', 'actor/', 'informationobject/'];
            foreach ($prefixes as $prefix) {
                if (strpos($path, $prefix) === 0) {
                    $path = substr($path, strlen($prefix));
                    break;
                }
            }

            // The slug should be the first path segment
            $segments = explode('/', $path);
            $slug = $segments[0] ?? null;
        }

        return $slug;
    }

    /**
     * Determine resource type from current request
     */
    protected function determineResourceType(sfWebRequest $request): string
    {
        $module = $request->getParameter('module');

        if (in_array($module, ['repository', 'sfIsdiahPlugin'])) {
            return 'repository';
        }

        if (in_array($module, ['actor', 'sfIsaarPlugin'])) {
            return 'actor';
        }

        return 'record';
    }

    /**
     * Build JSON-LD URL for resource
     */
    protected function buildJsonLdUrl(string $slug, string $type): string
    {
        $baseUrl = sfConfig::get('app_siteBaseUrl', '');

        if (empty($baseUrl)) {
            $request = $this->getContext()->getRequest();
            $baseUrl = $request->getUriPrefix();
        }

        $baseUrl = rtrim($baseUrl, '/');

        switch ($type) {
            case 'repository':
                return $baseUrl . '/repository/' . $slug . '.jsonld';
            case 'actor':
                return $baseUrl . '/actor/' . $slug . '.jsonld';
            default:
                return $baseUrl . '/' . $slug . '.jsonld';
        }
    }
}
