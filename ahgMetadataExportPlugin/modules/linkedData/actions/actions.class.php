<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * Linked Data Actions
 *
 * Provides JSON-LD endpoints for records with content negotiation support.
 * Implements:
 * - /{slug}.jsonld - Direct JSON-LD access
 * - /linkeddata/{type}/{slug} - Typed JSON-LD endpoint
 * - /sitemap-ld.xml - Linked data sitemap
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage linkedData
 * @author     The Archive and Heritage Group (Pty) Ltd
 */

use Illuminate\Database\Capsule\Manager as DB;

class linkedDataActions extends AhgController
{
    /**
     * JSON-LD endpoint for information objects
     *
     * Accessed via /{slug}.jsonld or with Accept: application/ld+json
     */
    public function executeRecord($request)
    {
        $slug = $request->getParameter('slug');

        if (!$slug) {
            $this->forward404();
        }

        // Get resource by slug
        $resource = $this->getResourceBySlug($slug);
        if (!$resource) {
            $this->forward404();
        }

        // Check publication status
        if (!$this->isPublished($resource->id)) {
            $this->forward404();
        }

        // Generate JSON-LD
        $jsonld = $this->generateJsonLd($resource, 'informationobject');

        return $this->sendJsonLdResponse($jsonld);
    }

    /**
     * JSON-LD endpoint for repositories
     */
    public function executeRepository($request)
    {
        $slug = $request->getParameter('slug');

        if (!$slug) {
            $this->forward404();
        }

        // Get repository by slug
        $resource = $this->getRepositoryBySlug($slug);
        if (!$resource) {
            $this->forward404();
        }

        // Generate JSON-LD
        $jsonld = $this->generateJsonLd($resource, 'repository');

        return $this->sendJsonLdResponse($jsonld);
    }

    /**
     * JSON-LD endpoint for actors
     */
    public function executeActor($request)
    {
        $slug = $request->getParameter('slug');

        if (!$slug) {
            $this->forward404();
        }

        // Get actor by slug
        $resource = $this->getActorBySlug($slug);
        if (!$resource) {
            $this->forward404();
        }

        // Generate JSON-LD
        $jsonld = $this->generateJsonLd($resource, 'actor');

        return $this->sendJsonLdResponse($jsonld);
    }

    /**
     * Linked data sitemap for crawlers
     */
    public function executeSitemap($request)
    {
        $baseUrl = rtrim($this->config('app_siteBaseUrl', 'https://example.org'), '/');

        // Build XML sitemap
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString('  ');

        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        // Get published information objects
        $records = DB::table('information_object as io')
            ->join('status as s', function ($join) {
                $join->on('io.id', '=', 's.object_id')
                    ->where('s.type_id', '=', 158) // Publication status type
                    ->where('s.status_id', '=', 160); // Published
            })
            ->join('slug', 'io.id', '=', 'slug.object_id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->where('io.id', '>', 1)
            ->whereNotNull('ioi.title')
            ->where('ioi.title', '!=', '')
            ->select('slug.slug', 'io.updated_at')
            ->orderBy('io.updated_at', 'desc')
            ->limit(50000) // Sitemap limit
            ->get();

        foreach ($records as $record) {
            $xml->startElement('url');
            $xml->writeElement('loc', $baseUrl . '/' . $record->slug . '.jsonld');
            if ($record->updated_at) {
                $xml->writeElement('lastmod', date('Y-m-d', strtotime($record->updated_at)));
            }
            $xml->writeElement('changefreq', 'monthly');
            $xml->writeElement('priority', '0.8');
            $xml->endElement(); // url
        }

        // Add repositories
        $repos = DB::table('repository as r')
            ->join('slug', 'r.id', '=', 'slug.object_id')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('r.id', '=', 'ai.id')
                    ->where('ai.culture', '=', 'en');
            })
            ->whereNotNull('ai.authorized_form_of_name')
            ->select('slug.slug', 'r.updated_at')
            ->get();

        foreach ($repos as $repo) {
            $xml->startElement('url');
            $xml->writeElement('loc', $baseUrl . '/repository/' . $repo->slug . '.jsonld');
            if ($repo->updated_at) {
                $xml->writeElement('lastmod', date('Y-m-d', strtotime($repo->updated_at)));
            }
            $xml->writeElement('changefreq', 'monthly');
            $xml->writeElement('priority', '0.6');
            $xml->endElement();
        }

        // Add actors
        $actors = DB::table('actor as a')
            ->join('slug', 'a.id', '=', 'slug.object_id')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('a.id', '=', 'ai.id')
                    ->where('ai.culture', '=', 'en');
            })
            ->where('a.id', '>', 1) // Skip root
            ->whereNotNull('ai.authorized_form_of_name')
            ->select('slug.slug', 'a.updated_at')
            ->limit(10000)
            ->get();

        foreach ($actors as $actor) {
            $xml->startElement('url');
            $xml->writeElement('loc', $baseUrl . '/actor/' . $actor->slug . '.jsonld');
            if ($actor->updated_at) {
                $xml->writeElement('lastmod', date('Y-m-d', strtotime($actor->updated_at)));
            }
            $xml->writeElement('changefreq', 'monthly');
            $xml->writeElement('priority', '0.5');
            $xml->endElement();
        }

        $xml->endElement(); // urlset

        $content = $xml->outputMemory();

        $this->response->setContentType('application/xml');
        $this->response->setHttpHeader('Cache-Control', 'public, max-age=86400');
        $this->response->setContent($content);

        return sfView::NONE;
    }

    /**
     * Content negotiation handler
     *
     * Checks Accept header and redirects to JSON-LD if requested
     */
    public function executeNegotiate($request)
    {
        $slug = $request->getParameter('slug');
        $type = $request->getParameter('type', 'record');

        if (!$slug) {
            $this->forward404();
        }

        // Check Accept header for JSON-LD preference
        $acceptHeader = $request->getHttpHeader('Accept', '');
        $wantsJsonLd = $this->prefersJsonLd($acceptHeader);

        if ($wantsJsonLd) {
            // Redirect to JSON-LD endpoint
            switch ($type) {
                case 'repository':
                    $this->redirect(['module' => 'linkedData', 'action' => 'repository', 'slug' => $slug]);
                    break;
                case 'actor':
                    $this->redirect(['module' => 'linkedData', 'action' => 'actor', 'slug' => $slug]);
                    break;
                default:
                    $this->redirect(['module' => 'linkedData', 'action' => 'record', 'slug' => $slug]);
            }
        }

        // Otherwise forward to HTML view
        switch ($type) {
            case 'repository':
                $this->redirect(['module' => 'repository', 'action' => 'index', 'slug' => $slug]);
                break;
            case 'actor':
                $this->redirect(['module' => 'actor', 'action' => 'index', 'slug' => $slug]);
                break;
            default:
                $this->redirect(['module' => 'informationobject', 'action' => 'index', 'slug' => $slug]);
        }
    }

    /**
     * Generate JSON-LD for a resource
     */
    protected function generateJsonLd($resource, string $type): string
    {
        $this->loadExporter();

        $exporter = new \AhgMetadataExport\Exporters\SchemaOrgExporter();
        $exporter->setOutputFormat('jsonld');

        return $exporter->export($resource, [
            'includeDigitalObjects' => true,
            'prettyPrint' => true,
            'includeContext' => true,
        ]);
    }

    /**
     * Send JSON-LD response
     */
    protected function sendJsonLdResponse(string $jsonld)
    {
        $this->response->setContentType('application/ld+json');
        $this->response->setHttpHeader('Link', '<https://schema.org>; rel="http://www.w3.org/ns/json-ld#context"');
        $this->response->setHttpHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHttpHeader('Cache-Control', 'public, max-age=3600');
        $this->response->setContent($jsonld);

        return sfView::NONE;
    }

    /**
     * Check if Accept header prefers JSON-LD
     */
    protected function prefersJsonLd(string $acceptHeader): bool
    {
        if (empty($acceptHeader)) {
            return false;
        }

        // Parse Accept header
        $types = array_map('trim', explode(',', $acceptHeader));

        foreach ($types as $type) {
            // Extract media type (ignore parameters)
            $parts = explode(';', $type);
            $mediaType = trim($parts[0]);

            // Check for JSON-LD preference
            if (in_array($mediaType, ['application/ld+json', 'application/json+ld'])) {
                return true;
            }

            // Check quality factor if present
            $q = 1.0;
            foreach ($parts as $part) {
                if (strpos($part, 'q=') !== false) {
                    $q = (float) trim(str_replace('q=', '', $part));
                }
            }

            // If JSON-LD has higher q-value than HTML, prefer it
            if ($mediaType === 'application/ld+json' && $q > 0.5) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get resource by slug
     */
    protected function getResourceBySlug(string $slug)
    {
        return DB::table('slug')
            ->join('information_object as io', 'slug.object_id', '=', 'io.id')
            ->where('slug.slug', $slug)
            ->select('io.*', 'slug.slug')
            ->first();
    }

    /**
     * Get repository by slug
     */
    protected function getRepositoryBySlug(string $slug)
    {
        return DB::table('slug')
            ->join('repository as r', 'slug.object_id', '=', 'r.id')
            ->where('slug.slug', $slug)
            ->select('r.*', 'slug.slug')
            ->first();
    }

    /**
     * Get actor by slug
     */
    protected function getActorBySlug(string $slug)
    {
        return DB::table('slug')
            ->join('actor as a', 'slug.object_id', '=', 'a.id')
            ->where('slug.slug', $slug)
            ->where('a.id', '>', 1)
            ->select('a.*', 'slug.slug')
            ->first();
    }

    /**
     * Check if resource is published
     */
    protected function isPublished(int $objectId): bool
    {
        $status = DB::table('status')
            ->where('object_id', $objectId)
            ->where('type_id', 158) // Publication status type
            ->first();

        return $status && $status->status_id == 160; // Published
    }

    /**
     * Load the exporter class
     */
    protected function loadExporter(): void
    {
        $pluginDir = $this->config('sf_plugins_dir') . '/ahgMetadataExportPlugin';

        // Load required classes
        require_once $pluginDir . '/lib/Contracts/ExporterInterface.php';
        require_once $pluginDir . '/lib/Exporters/AbstractRdfExporter.php';
        require_once $pluginDir . '/lib/Exporters/SchemaOrgExporter.php';
    }
}
