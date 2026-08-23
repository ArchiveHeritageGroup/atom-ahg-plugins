<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * ahgIiif module actions
 *
 * Handles IIIF manifest generation for information objects
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 */
class iiifActions extends AhgController
{
    /**
     * Generate IIIF manifest by slug
     * Default: v3. Use ?format=2 for v2.1 fallback.
     */
    public function executeManifest($request)
    {
        $slug = $request->getParameter('slug');

        if (empty($slug)) {
            $this->getResponse()->setStatusCode(400);
            return $this->renderText(json_encode(['error' => 'Missing slug parameter']));
        }

        if ($this->wantsV2($request)) {
            return $this->generateManifest(['slug' => $slug]);
        }

        return $this->generateManifestV3(['slug' => $slug]);
    }

    /**
     * Generate IIIF manifest by ID
     * Default: v3. Use ?format=2 for v2.1 fallback.
     */
    public function executeManifestById($request)
    {
        $id = $request->getParameter('id');

        if (empty($id) || !is_numeric($id)) {
            $this->getResponse()->setStatusCode(400);
            return $this->renderText(json_encode(['error' => 'Missing or invalid id parameter']));
        }

        if ($this->wantsV2($request)) {
            return $this->generateManifest(['id' => (int) $id]);
        }

        return $this->generateManifestV3(['id' => (int) $id]);
    }

    /**
     * Explicit v3 manifest endpoint by slug
     */
    public function executeManifestV3($request)
    {
        $slug = $request->getParameter('slug');

        if (empty($slug)) {
            $this->getResponse()->setStatusCode(400);
            return $this->renderText(json_encode(['error' => 'Missing slug parameter']));
        }

        return $this->generateManifestV3(['slug' => $slug]);
    }

    /**
     * Detect if client explicitly requests v2.1 manifest via ?format=2, ?version=2, or Accept header.
     * Default is now v3 (Presentation API 3.0).
     */
    private function wantsV2($request): bool
    {
        if ($request->getParameter('format') === '2' || $request->getParameter('version') === '2') {
            return true;
        }

        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return stripos($accept, 'presentation/2') !== false;
    }

    /**
     * Generate IIIF Presentation API 2.1 manifest
     */
    /**
     * Whether the current user may be served the MASTER digital object.
     *
     * Mirrors base AtoM's rule (QubitObject::getDigitalObjectPublicUrl /
     * getDigitalObjectUrl): the master is served only when it is accessible via
     * URL AND the user holds the 'readMaster' ACL permission on the record.
     * Authentication alone is NOT enough - 'readMaster' is granted to editors /
     * contributors, not to plain authenticated researchers or anonymous users.
     * Fails closed (serve the reference) on any error.
     */
    protected function userCanReadMaster(int $objectId): bool
    {
        try {
            $resource = \QubitInformationObject::getById($objectId);
            if (null === $resource) {
                return false;
            }

            $do = $resource->getDigitalObject();
            if (null !== $do && !$do->masterAccessibleViaUrl()) {
                return false;
            }

            return \QubitAcl::check($resource, 'readMaster');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Swap each master digital object for its reference derivative (usage 141,
     * thumbnail 142 as a fallback) when the user cannot read the master. A master
     * with no derivative is dropped rather than exposed. When $canMaster is true
     * the masters are returned unchanged.
     *
     * @param array<int,array<string,mixed>> $digitalObjects masters (object_id set)
     *
     * @return array<int,array<string,mixed>>
     */
    protected function applyMasterAccess(array $digitalObjects, bool $canMaster): array
    {
        if ($canMaster) {
            return $digitalObjects;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $out = [];
        foreach ($digitalObjects as $do) {
            $child = $db::table('digital_object')
                ->where('parent_id', $do['id'])
                ->whereIn('usage_id', [141, 142])
                ->orderByRaw('FIELD(usage_id, 141, 142)')
                ->select('id', 'name', 'path', 'mime_type', 'byte_size')
                ->first();

            if ($child) {
                $child = (array) $child;
                $do['id'] = $child['id'];
                $do['name'] = $child['name'];
                $do['path'] = $child['path'];
                $do['mime_type'] = $child['mime_type'];
                $do['byte_size'] = $child['byte_size'];
                $out[] = $do;
            }
            // No derivative + no master access -> omit; never expose the master.
        }

        return $out;
    }

    protected function generateManifest(array $params)
    {
        $this->getResponse()->setContentType('application/json');

        $db = \Illuminate\Database\Capsule\Manager::class;
        $culture = \AtomExtensions\Helpers\CultureHelper::getCulture();
        $forceRefresh = !empty($_GET['refresh']);

        // Lazy load namespaced services (Symfony 1.x doesn't autoload plugin namespaces)
        $pluginDir = sfConfig::get('sf_plugins_dir') . '/ahgIiifPlugin';
        require_once $pluginDir . '/lib/Services/IiifViewerService.php';
        require_once $pluginDir . '/lib/Services/IiifAuthService.php';

        // Look up the object
        $object = null;

        if (!empty($params['slug'])) {
            $result = $db::table('information_object as io')
                ->leftJoin('information_object_i18n as i18n', function ($join) use ($culture) {
                    $join->on('io.id', '=', 'i18n.id')
                        ->where('i18n.culture', '=', $culture);
                })
                ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
                ->where('s.slug', $params['slug'])
                ->select('io.id', 'io.identifier', 'i18n.title', 's.slug')
                ->first();
            $object = $result ? (array) $result : null;
        } else {
            // Try as information_object.id
            $result = $db::table('information_object as io')
                ->leftJoin('information_object_i18n as i18n', function ($join) use ($culture) {
                    $join->on('io.id', '=', 'i18n.id')
                        ->where('i18n.culture', '=', $culture);
                })
                ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
                ->where('io.id', $params['id'])
                ->select('io.id', 'io.identifier', 'i18n.title', 's.slug')
                ->first();
            $object = $result ? (array) $result : null;

            // If not found, try as digital_object.id
            if (!$object) {
                $result = $db::table('digital_object as do')
                    ->join('information_object as io', 'do.object_id', '=', 'io.id')
                    ->leftJoin('information_object_i18n as i18n', function ($join) use ($culture) {
                        $join->on('io.id', '=', 'i18n.id')
                            ->where('i18n.culture', '=', $culture);
                    })
                    ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
                    ->where('do.id', $params['id'])
                    ->select('io.id', 'io.identifier', 'i18n.title', 's.slug')
                    ->first();
                $object = $result ? (array) $result : null;
            }
        }

        if (!$object) {
            $this->getResponse()->setStatusCode(404);
            return $this->renderText(json_encode(['error' => 'Object not found']));
        }

        // Access control: only 'readMaster' users get the original; everyone else
        // (anonymous or authenticated-without-readMaster) gets the reference. Cache
        // each tier separately and mark the response private.
        $canMaster = $this->userCanReadMaster((int) $object['id']);
        $tier = 'v2-' . ($canMaster ? 'master' : 'ref');
        $this->getResponse()->setHttpHeader('Cache-Control', 'private, no-cache');

        // Check manifest cache (per access tier)
        $viewerService = new \AhgIiif\Services\IiifViewerService();
        if (!$forceRefresh) {
            $cached = $viewerService->getCachedManifest((int) $object['id'], $culture, $tier);
            if ($cached) {
                return $this->renderText($cached['manifest_json']);
            }
        }

        // Get digital objects
        $digitalObjects = $db::table('digital_object as do')
            ->where('do.object_id', $object['id'])
            ->orderBy('do.id')
            ->select('do.id', 'do.name', 'do.path', 'do.mime_type', 'do.byte_size')
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();

        if (empty($digitalObjects)) {
            $this->getResponse()->setStatusCode(404);
            return $this->renderText(json_encode(['error' => 'No digital objects found']));
        }

        // Serve the reference derivative instead of the master when unauthorised.
        $digitalObjects = $this->applyMasterAccess($digitalObjects, $canMaster);
        if (empty($digitalObjects)) {
            return $this->renderText(json_encode(['error' => 'No accessible representation']));
        }

        // Build URLs
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = "{$protocol}://{$host}";
        $cantaloupeBaseUrl = $this->config('app_iiif_cantaloupe_internal_url', 'http://127.0.0.1:8182');

        $label = $object['title'] ?: $object['identifier'] ?: 'Untitled';
        $manifestId = "{$baseUrl}/iiif/manifest/{$object['slug']}";

        // Build canvases
        $canvases = [];
        $canvasIndex = 1;
        $totalPageCount = null;

        foreach ($digitalObjects as $do) {
            $imagePath = ltrim($do['path'], '/');
            $cantaloupeId = str_replace('/', '_SL_', $imagePath) . $do['name'];

            // Check for multi-page TIFF
            $isMultiPageTiff = false;
            $pageCount = 1;
            $mimeType = strtolower($do['mime_type'] ?? '');
            $fileName = strtolower($do['name'] ?? '');

            if ($mimeType === 'image/tiff' || preg_match('/\.tiff?$/i', $fileName)) {
                // Check cached page count first to avoid expensive Cantaloupe probing
                $cachedPageCount = $viewerService->getPageCount((int) $object['id']);
                if ($cachedPageCount !== null && $cachedPageCount > 1) {
                    $isMultiPageTiff = true;
                    $pageCount = $cachedPageCount;
                } else {
                    $page2InfoUrl = "{$cantaloupeBaseUrl}/iiif/2/{$cantaloupeId};2/info.json";
                    $page2Info = @file_get_contents($page2InfoUrl);

                    if ($page2Info !== false) {
                        $isMultiPageTiff = true;
                        $pageCount = 2;
                        for ($i = 3; $i <= 100; $i++) {
                            $pageInfoUrl = "{$cantaloupeBaseUrl}/iiif/2/{$cantaloupeId};{$i}/info.json";
                            $ctx = stream_context_create(['http' => ['timeout' => 1]]);
                            $pageInfo = @file_get_contents($pageInfoUrl, false, $ctx);
                            if ($pageInfo === false) {
                                break;
                            }
                            $pageCount = $i;
                        }
                    }
                }
                $totalPageCount = $pageCount;
            }

            if ($isMultiPageTiff) {
                for ($pageNum = 1; $pageNum <= $pageCount; $pageNum++) {
                    // Use raw semicolon - Cantaloupe expects this format
                    $pageCantaloupeId = "{$cantaloupeId};{$pageNum}";
                    $pageImageApiBase = "{$baseUrl}/iiif/2/{$pageCantaloupeId}";

                    $pageInfoUrl = "{$cantaloupeBaseUrl}/iiif/2/{$pageCantaloupeId}/info.json";
                    $pageInfoJson = @file_get_contents($pageInfoUrl);

                    if ($pageInfoJson) {
                        $pageInfo = json_decode($pageInfoJson, true);
                        $width = $pageInfo['width'] ?? 1000;
                        $height = $pageInfo['height'] ?? 1000;
                    } else {
                        $width = 1000;
                        $height = 1000;
                    }

                    $canvasId = "{$manifestId}/canvas/{$canvasIndex}";

                    $canvases[] = [
                        '@type' => 'sc:Canvas',
                        '@id' => $canvasId,
                        'label' => ($do['name'] ?: 'Image') . " - Page {$pageNum}",
                        'width' => $width,
                        'height' => $height,
                        'images' => [
                            [
                                '@type' => 'oa:Annotation',
                                'motivation' => 'sc:painting',
                                'resource' => [
                                    '@id' => "{$pageImageApiBase}/full/full/0/default.jpg",
                                    '@type' => 'dctypes:Image',
                                    'format' => 'image/jpeg',
                                    'width' => $width,
                                    'height' => $height,
                                    'service' => [
                                        '@context' => 'http://iiif.io/api/image/2/context.json',
                                        '@id' => $pageImageApiBase,
                                        'profile' => 'http://iiif.io/api/image/2/level2.json',
                                    ],
                                ],
                                'on' => $canvasId,
                            ],
                        ],
                    ];

                    $canvasIndex++;
                }
            } else {
                $imageApiBase = "{$baseUrl}/iiif/2/{$cantaloupeId}";
                $localInfoUrl = "{$cantaloupeBaseUrl}/iiif/2/{$cantaloupeId}/info.json";
                $infoJson = @file_get_contents($localInfoUrl);

                if ($infoJson) {
                    $info = json_decode($infoJson, true);
                    $width = $info['width'] ?? 1000;
                    $height = $info['height'] ?? 1000;
                } else {
                    $width = 1000;
                    $height = 1000;
                }

                $canvasId = "{$manifestId}/canvas/{$canvasIndex}";

                $canvases[] = [
                    '@type' => 'sc:Canvas',
                    '@id' => $canvasId,
                    'label' => $do['name'] ?: "Image {$canvasIndex}",
                    'width' => $width,
                    'height' => $height,
                    'images' => [
                        [
                            '@type' => 'oa:Annotation',
                            'motivation' => 'sc:painting',
                            'resource' => [
                                '@id' => "{$imageApiBase}/full/full/0/default.jpg",
                                '@type' => 'dctypes:Image',
                                'format' => 'image/jpeg',
                                'width' => $width,
                                'height' => $height,
                                'service' => [
                                    '@context' => 'http://iiif.io/api/image/2/context.json',
                                    '@id' => $imageApiBase,
                                    'profile' => 'http://iiif.io/api/image/2/level2.json',
                                ],
                            ],
                            'on' => $canvasId,
                        ],
                    ],
                ];

                $canvasIndex++;
            }
        }

        // Build manifest
        $manifest = [
            '@context' => 'http://iiif.io/api/presentation/2/context.json',
            '@type' => 'sc:Manifest',
            '@id' => $manifestId,
            'label' => $label,
            'metadata' => [],
            'sequences' => [
                [
                    '@type' => 'sc:Sequence',
                    '@id' => "{$manifestId}/sequence/normal",
                    'label' => 'Normal Order',
                    'canvases' => $canvases,
                ],
            ],
        ];

        if ($object['identifier']) {
            $manifest['metadata'][] = [
                'label' => 'Identifier',
                'value' => $object['identifier'],
            ];
        }

        if (!empty($canvases)) {
            $firstCanvas = $canvases[0];
            $manifest['thumbnail'] = [
                '@id' => str_replace('/full/full/', '/full/200,/', $firstCanvas['images'][0]['resource']['@id']),
                'service' => $firstCanvas['images'][0]['resource']['service'],
            ];
        }

        // IIIF Auth: inject auth service block for protected resources
        $isProtected = false;
        try {
            $authService = new \IiifAuthService();
            $accessCheck = $authService->checkAccess((int) $object['id']);
            if (!empty($accessCheck['service'])) {
                $isProtected = true;
                $manifest['service'] = $accessCheck['service'];
            }
        } catch (\Throwable $e) {
            // Auth check failure is non-fatal — treat as public
        }

        // Set CORS header: restricted for protected, open for public
        if ($isProtected) {
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
            $this->getResponse()->setHttpHeader('Access-Control-Allow-Origin', $origin);
            $this->getResponse()->setHttpHeader('Access-Control-Allow-Credentials', 'true');
        } else {
            $this->getResponse()->setHttpHeader('Access-Control-Allow-Origin', '*');
        }

        // Cache the manifest
        $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        try {
            $viewerService->setCachedManifest((int) $object['id'], $culture, $manifestJson, $totalPageCount, $tier);
        } catch (\Throwable $e) {
            // Cache write failure is non-fatal
        }

        return $this->renderText($manifestJson);
    }

    /**
     * Generate IIIF Presentation API 3.0 manifest
     */
    protected function generateManifestV3(array $params)
    {
        $this->getResponse()->setContentType('application/ld+json;profile="http://iiif.io/api/presentation/3/context.json"');

        $db = \Illuminate\Database\Capsule\Manager::class;
        $culture = \AtomExtensions\Helpers\CultureHelper::getCulture();
        $forceRefresh = !empty($_GET['refresh']);

        $pluginDir = sfConfig::get('sf_plugins_dir') . '/ahgIiifPlugin';
        require_once $pluginDir . '/lib/Services/IiifViewerService.php';
        require_once $pluginDir . '/lib/Services/IiifManifestV3Service.php';

        // Look up the object (same logic as v2.1)
        $object = null;

        if (!empty($params['slug'])) {
            $result = $db::table('information_object as io')
                ->leftJoin('information_object_i18n as i18n', function ($join) use ($culture) {
                    $join->on('io.id', '=', 'i18n.id')
                        ->where('i18n.culture', '=', $culture);
                })
                ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
                ->where('s.slug', $params['slug'])
                ->select('io.id', 'io.identifier', 'i18n.title', 's.slug')
                ->first();
            $object = $result ? (array) $result : null;
        } else {
            $result = $db::table('information_object as io')
                ->leftJoin('information_object_i18n as i18n', function ($join) use ($culture) {
                    $join->on('io.id', '=', 'i18n.id')
                        ->where('i18n.culture', '=', $culture);
                })
                ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
                ->where('io.id', $params['id'])
                ->select('io.id', 'io.identifier', 'i18n.title', 's.slug')
                ->first();
            $object = $result ? (array) $result : null;

            if (!$object) {
                $result = $db::table('digital_object as do')
                    ->join('information_object as io', 'do.object_id', '=', 'io.id')
                    ->leftJoin('information_object_i18n as i18n', function ($join) use ($culture) {
                        $join->on('io.id', '=', 'i18n.id')
                            ->where('i18n.culture', '=', $culture);
                    })
                    ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
                    ->where('do.id', $params['id'])
                    ->select('io.id', 'io.identifier', 'i18n.title', 's.slug')
                    ->first();
                $object = $result ? (array) $result : null;
            }
        }

        if (!$object) {
            $this->getResponse()->setStatusCode(404);
            return $this->renderText(json_encode(['error' => 'Object not found']));
        }

        // Access control: only users with the 'readMaster' ACL permission (and a
        // master that is URL-accessible) get the original; everyone else -
        // anonymous or authenticated-without-readMaster - is served the reference
        // derivative. Cache each access tier separately and mark the response
        // private so a public request can never receive a staff-cached master.
        $canMaster = $this->userCanReadMaster((int) $object['id']);
        $tier = 'v3-' . ($canMaster ? 'master' : 'ref');
        $this->getResponse()->setHttpHeader('Cache-Control', 'private, no-cache');

        // Check v3 manifest cache (per access tier)
        $viewerService = new \AhgIiif\Services\IiifViewerService();
        if (!$forceRefresh) {
            $cached = $viewerService->getCachedManifest((int) $object['id'], $culture, $tier);
            if ($cached) {
                $this->getResponse()->setHttpHeader('Access-Control-Allow-Origin', '*');

                return $this->renderText($cached['manifest_json']);
            }
        }

        $digitalObjects = $db::table('digital_object as do')
            ->where('do.object_id', $object['id'])
            ->orderBy('do.id')
            ->select('do.id', 'do.name', 'do.path', 'do.mime_type', 'do.byte_size')
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();

        if (empty($digitalObjects)) {
            $this->getResponse()->setStatusCode(404);
            return $this->renderText(json_encode(['error' => 'No digital objects found']));
        }

        // Serve the reference derivative instead of the master when unauthorised.
        $digitalObjects = $this->applyMasterAccess($digitalObjects, $canMaster);
        if (empty($digitalObjects)) {
            return $this->renderText(json_encode(['error' => 'No accessible representation']));
        }

        // Use cached page count if available
        $cachedPageCount = $viewerService->getPageCount((int) $object['id']);

        $v3Service = new \AhgIiif\Services\IiifManifestV3Service();
        $manifest = $v3Service->generateV3Manifest($object, $digitalObjects, $culture, $cachedPageCount);

        $this->getResponse()->setHttpHeader('Access-Control-Allow-Origin', '*');

        // Cache the v3 manifest
        $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        try {
            $viewerService->setCachedManifest((int) $object['id'], $culture, $manifestJson, null, $tier);
        } catch (\Throwable $e) {
            // Cache write failure is non-fatal
        }

        return $this->renderText($manifestJson);
    }

    /**
     * Standalone IIIF viewer page.
     * GET /iiif/viewer/:id
     */
    public function executeViewer($request)
    {
        $id = $request->getParameter('id');

        if (empty($id) || !is_numeric($id)) {
            $this->getResponse()->setStatusCode(400);
            return $this->renderText('Invalid object ID');
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $culture = \AtomExtensions\Helpers\CultureHelper::getCulture();

        // Look up the object — try as information_object.id first, then digital_object.id
        $result = $db::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function ($join) use ($culture) {
                $join->on('io.id', '=', 'i18n.id')
                    ->where('i18n.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
            ->where('io.id', (int) $id)
            ->select('io.id', 'io.identifier', 'i18n.title', 's.slug')
            ->first();

        if (!$result) {
            $result = $db::table('digital_object as do')
                ->join('information_object as io', 'do.object_id', '=', 'io.id')
                ->leftJoin('information_object_i18n as i18n', function ($join) use ($culture) {
                    $join->on('io.id', '=', 'i18n.id')
                        ->where('i18n.culture', '=', $culture);
                })
                ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
                ->where('do.id', (int) $id)
                ->select('io.id', 'io.identifier', 'i18n.title', 's.slug')
                ->first();
        }

        if (!$result) {
            $this->forward404('Object not found');
        }

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = "{$protocol}://{$host}";

        $this->objectId = $result->id;
        $this->objectTitle = $result->title ?: $result->identifier ?: 'Untitled';
        $this->objectSlug = $result->slug;
        $this->manifestUrl = "{$baseUrl}/iiif/manifest/{$result->slug}";
        $this->baseUrl = $baseUrl;
        $this->pluginPath = sfConfig::get('app_iiif_plugin_path', '/plugins/ahgIiifPlugin/web');

        $this->response->setTitle('IIIF Viewer - ' . $this->objectTitle);
        $this->setTemplate('viewer');
    }

    /**
     * IIIF settings admin page - display and save
     */
    public function executeSettings($request)
    {
        // Check admin access
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->isAdministrator()) {
            $this->forward($this->config('sf_secure_module'), $this->config('sf_secure_action'));
        }

        $db = \Illuminate\Database\Capsule\Manager::class;

        if ($request->isMethod('post')) {
            $settings = [
                // Homepage settings
                'homepage_collection_enabled' => $request->getParameter('homepage_collection_enabled', '0'),
                'homepage_collection_id' => $request->getParameter('homepage_collection_id', ''),
                'homepage_carousel_height' => $request->getParameter('homepage_carousel_height', '450px'),
                'homepage_carousel_autoplay' => $request->getParameter('homepage_carousel_autoplay', '0'),
                'homepage_carousel_interval' => $request->getParameter('homepage_carousel_interval', '5000'),
                'homepage_show_captions' => $request->getParameter('homepage_show_captions', '0'),
                'homepage_max_items' => $request->getParameter('homepage_max_items', '12'),
                // Viewer settings
                'viewer_type' => $request->getParameter('viewer_type', 'carousel'),
                'carousel_autoplay' => $request->getParameter('carousel_autoplay', '0'),
                'carousel_interval' => $request->getParameter('carousel_interval', '5000'),
                'carousel_show_thumbnails' => $request->getParameter('carousel_show_thumbnails', '0'),
                'carousel_show_controls' => $request->getParameter('carousel_show_controls', '0'),
                'viewer_height' => $request->getParameter('viewer_height', '500px'),
                'show_zoom_controls' => $request->getParameter('show_zoom_controls', '0'),
                'enable_fullscreen' => $request->getParameter('enable_fullscreen', '0'),
                'default_zoom' => $request->getParameter('default_zoom', '1'),
                'background_color' => $request->getParameter('background_color', '#000000'),
                'show_on_browse' => $request->getParameter('show_on_browse', '0'),
                'show_on_view' => $request->getParameter('show_on_view', '0'),
            ];

            foreach ($settings as $key => $value) {
                $exists = $db::table('iiif_viewer_settings')->where('setting_key', $key)->exists();
                if ($exists) {
                    $db::table('iiif_viewer_settings')->where('setting_key', $key)->update(['setting_value' => $value]);
                } else {
                    $db::table('iiif_viewer_settings')->insert(['setting_key' => $key, 'setting_value' => $value]);
                }
            }

            $this->getUser()->setFlash('notice', 'Settings saved successfully.');
            $this->redirect(['module' => 'iiif', 'action' => 'settings']);
        }

        // Load current settings
        $this->settings = $db::table('iiif_viewer_settings')
            ->pluck('setting_value', 'setting_key')
            ->all();

        // Load collections for dropdown
        $this->collections = $db::table('iiif_collection as c')
            ->leftJoin($db::raw('(SELECT collection_id, COUNT(*) as cnt FROM iiif_collection_item GROUP BY collection_id) as items'), 'c.id', '=', 'items.collection_id')
            ->select('c.id', 'c.name', 'c.slug', 'c.is_public', $db::raw('COALESCE(items.cnt, 0) as item_count'))
            ->orderBy('c.name')
            ->get();

        $this->response->setTitle('IIIF Viewer Settings');

        $this->setTemplate('settings');
    }

    // =========================================================================
    // ANNOTATION ACTIONS
    // =========================================================================

    /**
     * Get annotations for an object
     * GET /iiif/annotations/object/:id
     */
    public function executeAnnotationsList($request)
    {
        $this->response->setContentType('application/json');

        $objectId = $request->getParameter('id');

        if (!$objectId) {
            $this->response->setStatusCode(400);

            return $this->renderText(json_encode(['error' => 'Object ID required']));
        }

        $service = new IiifAnnotationService();
        $annotations = $service->getAnnotationsForObject($objectId);
        $page = $service->formatAsAnnotationPage($annotations, $objectId);

        $this->response->setHttpHeader('Access-Control-Allow-Origin', '*');

        return $this->renderText(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Create a new annotation
     * POST /iiif/annotations
     */
    public function executeAnnotationsCreate($request)
    {
        $this->response->setContentType('application/json');
        $this->response->setHttpHeader('Access-Control-Allow-Origin', '*');

        if (!$this->getUser()->isAuthenticated()) {
            $this->response->setStatusCode(401);

            return $this->renderText(json_encode(['error' => 'Authentication required']));
        }

        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data['object_id'])) {
            $this->response->setStatusCode(400);

            return $this->renderText(json_encode(['error' => 'Invalid annotation data']));
        }

        $service = new IiifAnnotationService();
        $parsed = $service->parseAnnotoriousAnnotation($data, $data['object_id']);
        $parsed['created_by'] = $this->getUser()->getAttribute('user_id');

        $annotationId = $service->createAnnotation($parsed);

        // Invalidate manifest cache when annotations change
        try {
            $vs = new \AhgIiif\Services\IiifViewerService();
            $vs->invalidateManifestCache((int) $data['object_id']);
        } catch (\Throwable $e) { /* non-fatal */ }

        return $this->renderText(json_encode([
            'success' => true,
            'id' => '#' . $annotationId,
        ]));
    }

    /**
     * Modify an annotation (dispatches by HTTP method)
     * PUT /iiif/annotations/:id  → update
     * DELETE /iiif/annotations/:id → delete
     * GET /iiif/annotations/:id → get single annotation
     */
    public function executeAnnotationsModify($request)
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'DELETE') {
            return $this->executeAnnotationsDelete($request);
        }

        if ($method === 'PUT' || $method === 'PATCH' || $method === 'POST') {
            return $this->executeAnnotationsUpdate($request);
        }

        // GET — return single annotation
        $this->response->setContentType('application/json');
        $this->response->setHttpHeader('Access-Control-Allow-Origin', '*');
        $annotationId = $request->getParameter('id');
        $service = new IiifAnnotationService();
        $existing = $service->getAnnotation($annotationId);

        if (!$existing) {
            $this->response->setStatusCode(404);

            return $this->renderText(json_encode(['error' => 'Annotation not found']));
        }

        return $this->renderText(json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Update an annotation
     * PUT /iiif/annotations/:id
     */
    public function executeAnnotationsUpdate($request)
    {
        $this->response->setContentType('application/json');
        $this->response->setHttpHeader('Access-Control-Allow-Origin', '*');

        if (!$this->getUser()->isAuthenticated()) {
            $this->response->setStatusCode(401);

            return $this->renderText(json_encode(['error' => 'Authentication required']));
        }

        $annotationId = $request->getParameter('id');
        $data = json_decode($request->getContent(), true);

        if (!$annotationId || !$data) {
            $this->response->setStatusCode(400);

            return $this->renderText(json_encode(['error' => 'Invalid request']));
        }

        $service = new IiifAnnotationService();

        // Check annotation exists
        $existing = $service->getAnnotation($annotationId);
        if (!$existing) {
            $this->response->setStatusCode(404);

            return $this->renderText(json_encode(['error' => 'Annotation not found']));
        }

        $updateData = [];

        if (isset($data['target']['selector'])) {
            $updateData['target_selector'] = $data['target']['selector'];
        }

        if (isset($data['body'])) {
            $body = is_array($data['body']) && isset($data['body'][0]) ? $data['body'][0] : $data['body'];
            $updateData['body'] = [
                'type' => $body['type'] ?? 'TextualBody',
                'value' => $body['value'] ?? '',
                'format' => $body['format'] ?? 'text/plain',
                'purpose' => $body['purpose'] ?? null,
            ];
        }

        $service->updateAnnotation($annotationId, $updateData);

        // Invalidate manifest cache when annotations change
        try {
            $vs = new \AhgIiif\Services\IiifViewerService();
            $vs->invalidateManifestCache((int) $existing->object_id);
        } catch (\Throwable $e) { /* non-fatal */ }

        return $this->renderText(json_encode(['success' => true]));
    }

    /**
     * Delete an annotation
     * DELETE /iiif/annotations/:id
     */
    public function executeAnnotationsDelete($request)
    {
        $this->response->setContentType('application/json');
        $this->response->setHttpHeader('Access-Control-Allow-Origin', '*');

        if (!$this->getUser()->isAuthenticated()) {
            $this->response->setStatusCode(401);

            return $this->renderText(json_encode(['error' => 'Authentication required']));
        }

        $annotationId = $request->getParameter('id');

        if (!$annotationId) {
            $this->response->setStatusCode(400);

            return $this->renderText(json_encode(['error' => 'Annotation ID required']));
        }

        $service = new IiifAnnotationService();

        // Check annotation exists
        $existing = $service->getAnnotation($annotationId);
        if (!$existing) {
            $this->response->setStatusCode(404);

            return $this->renderText(json_encode(['error' => 'Annotation not found']));
        }

        $objectId = $existing->object_id;
        $service->deleteAnnotation($annotationId);

        // Invalidate manifest cache when annotations change
        try {
            $vs = new \AhgIiif\Services\IiifViewerService();
            $vs->invalidateManifestCache((int) $objectId);
        } catch (\Throwable $e) { /* non-fatal */ }

        return $this->renderText(json_encode(['success' => true]));
    }

    // =========================================================================
    // IIIF COMPARISON VIEWER (#228)
    // =========================================================================

    /**
     * Comparison viewer page.
     * GET /iiif/compare?slugs=slug1,slug2 or ?manifest=url1&manifest=url2
     */
    public function executeCompare($request)
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = "{$protocol}://{$host}";

        // Accept slugs= (comma-separated) or manifest= (repeated).
        //
        // Repeated parameters have to be read off the raw query string. PHP
        // collapses ?manifest=A&manifest=B to just B, so getParameter() sees one
        // manifest however many were sent - and a comparison of one image is not
        // a comparison. The static page this replaced used
        // URLSearchParams.getAll(), which is why repeated params appeared to work
        // there and never did here. manifest[]= is accepted too.
        $slugs = array_filter(explode(',', $request->getParameter('slugs', '')));
        $manifests = [];

        foreach (explode('&', (string) ($_SERVER['QUERY_STRING'] ?? '')) as $pair) {
            if ('' === $pair) {
                continue;
            }

            $parts = explode('=', $pair, 2);
            $key = urldecode($parts[0]);

            if ('manifest' !== $key && 'manifest[]' !== $key) {
                continue;
            }

            $value = trim(urldecode($parts[1] ?? ''));

            // Only absolute http(s) URLs: this value is handed to Mirador to
            // fetch, so anything else is either useless or an invitation.
            if ('' !== $value && preg_match('#^https?://#i', $value)) {
                $manifests[] = $value;
            }
        }

        $manifests = array_values(array_unique($manifests));

        // Convert slugs to manifest URLs
        foreach ($slugs as $slug) {
            $manifests[] = "{$baseUrl}/iiif/manifest/" . trim($slug);
        }

        if (empty($manifests)) {
            $this->forward404('No manifests specified. Use ?slugs=slug1,slug2 or ?manifest=url');
        }

        $this->manifests = $manifests;
        $this->baseUrl = $baseUrl;
        $this->pluginPath = sfConfig::get('app_iiif_plugin_path', '/plugins/ahgIiifPlugin/web');

        $this->response->setTitle('IIIF Compare');
        $this->setTemplate('compare');
    }

    // =========================================================================
    // IIIF VALIDATION (#184)
    // =========================================================================

    public function executeValidationDashboard($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->hasCredential('administrator')) {
            $this->redirect('user/login');
        }

        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgIiifPlugin/lib/Services/IiifValidationService.php';
        $service = new \AhgIiif\Services\IiifValidationService();

        $this->stats = $service->getDashboardStats();

        // Get recent objects with digital objects for quick validation
        $culture = \AtomExtensions\Helpers\CultureHelper::getCulture();
        $this->recentObjects = \Illuminate\Database\Capsule\Manager::table('digital_object as do')
            ->join('information_object_i18n as ioi', function ($join) use ($culture) {
                $join->on('do.object_id', '=', 'ioi.id')->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'do.object_id')
            ->select('do.object_id', 'ioi.title', 's.slug',
                \Illuminate\Database\Capsule\Manager::raw('COUNT(do.id) as do_count'))
            ->groupBy('do.object_id', 'ioi.title', 's.slug')
            ->orderByDesc('do.object_id')
            ->limit(50)
            ->get()
            ->toArray();
    }

    public function executeValidationRun($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->hasCredential('administrator')) {
            $this->redirect('user/login');
        }

        $objectId = (int) $request->getParameter('object_id');
        $culture = \AtomExtensions\Helpers\CultureHelper::getCulture();

        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgIiifPlugin/lib/Services/IiifValidationService.php';
        $service = new \AhgIiif\Services\IiifValidationService();

        $results = $service->validateManifest($objectId, $culture);

        $this->getResponse()->setContentType('application/json');
        return $this->renderText(json_encode([
            'object_id' => $objectId,
            'results' => $results,
            'passed' => count(array_filter($results, fn($r) => $r['status'] === 'passed')),
            'failed' => count(array_filter($results, fn($r) => $r['status'] === 'failed')),
            'warnings' => count(array_filter($results, fn($r) => $r['status'] === 'warning')),
        ]));
    }

    // =====================================================================
    // IIIF CONTENT SEARCH 2.0  (#84)
    // Search + autocomplete endpoints wired into the Pres 3 service block.
    // =====================================================================

    /**
     * GET /iiif/v3/manifest/:slug/search?q=<term>
     *
     * IIIF Content Search 2.0 — mirrors IiifContentSearchService from heratio.
     * Searches OCR text stored in iiif_ocr_text for the matching object.
     * Returns a W3C AnnotationPage with highlighted matches.
     *
     * @see https://iiif.io/api/search/2.0/
     */
    public function executeSearch($request)
    {
        $slug = $request->getParameter('slug');
        $query = (string) $request->getParameter('q', '');
        $motivation = $request->getParameter('motivation');

        $page = $this->runContentSearch($slug, $query, $motivation);

        $this->getResponse()->setHttpHeader('Content-Type', 'application/ld+json;profile="http://iiif.io/api/search/2/context.json"');
        $this->getResponse()->setHttpHeader('Access-Control-Allow-Origin', '*');
        $this->getResponse()->setHttpHeader('Cache-Control', 'no-cache, must-revalidate');

        return $this->renderText(json_encode($page, JSON_UNESCAPED_SLASHES));
    }

    /**
     * GET /iiif/v3/manifest/:slug/autocomplete?q=<prefix>
     *
     * IIIF Content Search 2.0 autocomplete — returns terms from stored OCR
     * block text that prefix-match the supplied query.
     *
     * @see https://iiif.io/api/search/2.0/
     */
    public function executeAutocomplete($request)
    {
        $slug = $request->getParameter('slug');
        $query = (string) $request->getParameter('q', '');

        $page = $this->runAutocomplete($slug, $query);

        $this->getResponse()->setHttpHeader('Content-Type', 'application/ld+json;profile="http://iiif.io/api/search/2/context.json"');
        $this->getResponse()->setHttpHeader('Access-Control-Allow-Origin', '*');
        $this->getResponse()->setHttpHeader('Cache-Control', 'no-cache, must-revalidate');

        return $this->renderText(json_encode($page, JSON_UNESCAPED_SLASHES));
    }

    /**
     * Shared content search logic — identical to IiifContentSearchService::search().
     */
    private function runContentSearch(string $slug, string $query, ?string $motivation): array
    {
        $baseUrl = rtrim($this->config('app_iiif_base_url', 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')), '/');
        $manifestRoot = "{$baseUrl}/iiif/v3/manifest/{$slug}";
        $searchPageId = "{$manifestRoot}/search?q=" . urlencode($query);

        $envelope = [
            '@context' => [
                'http://iiif.io/api/search/2/context.json',
                'http://iiif.io/api/presentation/3/context.json',
            ],
            'id' => $searchPageId,
            'type' => 'AnnotationPage',
            'label' => ['en' => ["Content Search results for \"{$query}\""]],
            'items' => [],
        ];

        if (trim($query) === '') {
            return $envelope;
        }

        // Resolve slug to object
        $object = \Illuminate\Database\Capsule\Manager::table('information_object as io')
            ->join('slug as s', 'io.id', '=', 's.object_id')
            ->where('s.slug', $slug)
            ->select('io.id')
            ->first();

        if (!$object) {
            $envelope['type'] = 'Error';
            $envelope['error'] = 'Manifest not found';
            return $envelope;
        }

        $objectId = (int) $object->id;

        // Build canvas map
        $canvasMap = $this->buildCanvasMap($objectId, $manifestRoot);
        if (empty($canvasMap)) {
            return $envelope;
        }

        // FULLTEXT search across iiif_ocr_text
        $term = trim($query);
        $ocrRows = \Illuminate\Database\Capsule\Manager::table('iiif_ocr_text')
            ->where('object_id', $objectId)
            ->whereRaw('MATCH(full_text) AGAINST (? IN NATURAL LANGUAGE MODE)', [$term])
            ->select('id', 'digital_object_id', 'language')
            ->get();

        if ($ocrRows->isEmpty()) {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';
            $ocrRows = \Illuminate\Database\Capsule\Manager::table('iiif_ocr_text')
                ->where('object_id', $objectId)
                ->where('full_text', 'LIKE', $like)
                ->select('id', 'digital_object_id', 'language')
                ->get();
        }

        if ($ocrRows->isEmpty()) {
            return $envelope;
        }

        $ocrIds = $ocrRows->pluck('id')->all();
        $ocrToDo = [];
        foreach ($ocrRows as $row) {
            $ocrToDo[(int) $row->id] = (int) $row->digital_object_id;
        }

        // Block-level LIKE search for highlighting
        $likeTerm = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';
        $blocks = \Illuminate\Database\Capsule\Manager::table('iiif_ocr_block')
            ->whereIn('ocr_id', $ocrIds)
            ->where('text', 'LIKE', $likeTerm)
            ->orderBy('ocr_id')
            ->orderBy('page_number')
            ->orderBy('block_order')
            ->orderBy('id')
            ->limit(200)
            ->select('id', 'ocr_id', 'page_number', 'text', 'x', 'y', 'width', 'height')
            ->get();

        $items = [];
        $hitIndex = 0;

        foreach ($blocks as $block) {
            $doId = $ocrToDo[(int) $block->ocr_id] ?? null;
            if ($doId === null || !isset($canvasMap[$doId])) {
                continue;
            }
            $canvasInfo = $canvasMap[$doId];
            $pageNum = (int) ($block->page_number ?: 1);
            $canvasIri = $canvasInfo['pages'][$pageNum] ?? $canvasInfo['base'];

            $hitIndex++;
            $items[] = [
                'id' => "{$manifestRoot}/search/annotation/{$hitIndex}",
                'type' => 'Annotation',
                'motivation' => $motivation ?: 'highlighting',
                'body' => [
                    'type' => 'TextualBody',
                    'value' => (string) $block->text,
                    'format' => 'text/plain',
                    'language' => 'en',
                ],
                'target' => [
                    'type' => 'SpecificResource',
                    'source' => [
                        'id' => $canvasIri,
                        'type' => 'Canvas',
                        'partOf' => [
                            'id' => $manifestRoot,
                            'type' => 'Manifest',
                        ],
                    ],
                    'selector' => [
                        'type' => 'FragmentSelector',
                        'conformsTo' => 'http://www.w3.org/TR/media-frags/',
                        'value' => sprintf('xywh=%d,%d,%d,%d', 0, 0, 1000, 1000),
                    ],
                ],
            ];

            if ($hitIndex >= 200) {
                break;
            }
        }

        $envelope['items'] = $items;
        $envelope['partOf'] = [
            'id' => $manifestRoot,
            'type' => 'Manifest',
        ];

        return $envelope;
    }

    /**
     * Shared autocomplete logic — mirrors IiifContentSearchService::autocomplete().
     */
    private function runAutocomplete(string $slug, string $query): array
    {
        $baseUrl = rtrim($this->config('app_iiif_base_url', 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')), '/');
        $id = "{$baseUrl}/iiif/v3/manifest/{$slug}/autocomplete?q=" . urlencode($query);

        $items = [];
        $term = trim($query);

        if ($term !== '') {
            $object = \Illuminate\Database\Capsule\Manager::table('information_object as io')
                ->join('slug as s', 'io.id', '=', 's.object_id')
                ->where('s.slug', $slug)
                ->select('io.id')
                ->first();

            if ($object) {
                $ocrIds = \Illuminate\Database\Capsule\Manager::table('iiif_ocr_text')
                    ->where('object_id', (int) $object->id)
                    ->pluck('id')
                    ->all();

                if (!empty($ocrIds)) {
                    $like = str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';
                    $rows = \Illuminate\Database\Capsule\Manager::table('iiif_ocr_block')
                        ->whereIn('ocr_id', $ocrIds)
                        ->where('text', 'LIKE', $like)
                        ->groupBy('text')
                        ->orderByRaw('COUNT(*) DESC')
                        ->limit(20)
                        ->select('text', \Illuminate\Database\Capsule\Manager::raw('COUNT(*) as hit_count'))
                        ->get();

                    foreach ($rows as $row) {
                        $items[] = [
                            'type' => 'TextualBody',
                            'value' => (string) $row->text,
                            'format' => 'text/plain',
                        ];
                    }
                }
            }
        }

        return [
            '@context' => 'http://iiif.io/api/search/2/context.json',
            'id' => $id,
            'type' => 'AnnotationCollection',
            'label' => ['en' => ["Autocomplete terms for \"{$term}\""]],
            'items' => $items,
        ];
    }

    /**
     * Build canvas index map matching IiifManifestV3Service::generateV3Manifest() ordering.
     */
    private function buildCanvasMap(int $objectId, string $manifestRoot): array
    {
        $digitalObjects = \Illuminate\Database\Capsule\Manager::table('digital_object')
            ->where('object_id', $objectId)
            ->orderBy('id')
            ->select('id', 'name', 'path', 'mime_type')
            ->get();

        if ($digitalObjects->isEmpty()) {
            return [];
        }

        $map = [];
        $canvasIndex = 1;
        $cantaloupeBase = 'http://127.0.0.1:8182';
        $maxProbe = 25;

        foreach ($digitalObjects as $do) {
            $imagePath = ltrim((string) $do->path, '/');
            $cantaloupeId = str_replace('/', '_SL_', $imagePath) . $do->name;
            $mimeType = strtolower($do->mime_type ?? '');
            $fileName = strtolower($do->name ?? '');
            $isMultiPage = ($mimeType === 'image/tiff' || preg_match('/\\.tiff?$/i', $fileName));
            $pageCount = 1;

            if ($isMultiPage) {
                $ctx = stream_context_create(['http' => ['timeout' => 1]]);
                $page2 = @file_get_contents("{$cantaloupeBase}/iiif/2/{$cantaloupeId};2/info.json", false, $ctx);
                if ($page2 !== false) {
                    $pageCount = 2;
                    for ($i = 3; $i <= $maxProbe; $i++) {
                        $probe = @file_get_contents("{$cantaloupeBase}/iiif/2/{$cantaloupeId};{$i}/info.json", false, $ctx);
                        if ($probe === false) {
                            break;
                        }
                        $pageCount = $i;
                    }
                }
            }

            $info = ['base' => null, 'pages' => []];
            for ($p = 1; $p <= $pageCount; $p++) {
                $iri = "{$manifestRoot}/canvas/{$canvasIndex}";
                if ($p === 1) {
                    $info['base'] = $iri;
                }
                $info['pages'][$p] = $iri;
                $canvasIndex++;
            }
            $map[(int) $do->id] = $info;
        }

        return $map;
    }

    // =====================================================================
    // IIIF Change Discovery (Activity Streams) + OCR export
    // =====================================================================

    protected function discoveryService(): \AhgIiif\Services\IiifDiscoveryService
    {
        require_once $this->config('sf_plugins_dir') . '/ahgIiifPlugin/lib/Services/IiifDiscoveryService.php';

        return new \AhgIiif\Services\IiifDiscoveryService();
    }

    /** GET /iiif/activity — Change Discovery OrderedCollection. */
    public function executeActivity($request)
    {
        $body = json_encode($this->discoveryService()->collection(), JSON_UNESCAPED_SLASHES);
        $this->getResponse()->setContentType('application/ld+json; charset=utf-8');
        $this->getResponse()->setHttpHeader('Cache-Control', 'public, max-age=300');

        return $this->renderText($body);
    }

    /** GET /iiif/activity/page/:n — one OrderedCollectionPage. */
    public function executeActivityPage($request)
    {
        $body = json_encode($this->discoveryService()->page((int) $request->getParameter('n')), JSON_UNESCAPED_SLASHES);
        $this->getResponse()->setContentType('application/ld+json; charset=utf-8');
        $this->getResponse()->setHttpHeader('Cache-Control', 'public, max-age=300');

        return $this->renderText($body);
    }

    /** GET /iiif/ocr/object/:id(.:format) — export stored OCR (txt|json|alto). */
    public function executeOcrExport($request)
    {
        $svc = $this->discoveryService();
        $objectId = (int) $request->getParameter('id');
        $row = $objectId ? $svc->ocrForObject($objectId) : null;

        if (!$row) {
            $this->getResponse()->setStatusCode(404);
            $this->getResponse()->setContentType('text/plain; charset=utf-8');

            return $this->renderText('No OCR text found for this object.');
        }

        $format = strtolower((string) $request->getParameter('format', 'txt'));
        $text = (string) ($row->full_text ?? '');

        switch ($format) {
            case 'json':
                $this->getResponse()->setContentType('application/json; charset=utf-8');

                return $this->renderText(json_encode([
                    'object_id' => $objectId,
                    'language' => $row->language ?? null,
                    'format' => $row->format ?? null,
                    'confidence' => $row->confidence ?? null,
                    'text' => $text,
                ], JSON_UNESCAPED_SLASHES));
            case 'alto':
            case 'xml':
                $this->getResponse()->setContentType('application/xml; charset=utf-8');

                return $this->renderText($svc->toAlto($text, (string) ($row->language ?? '')));
            default:
                $this->getResponse()->setContentType('text/plain; charset=utf-8');

                return $this->renderText($text);
        }
    }

    // =====================================================================
    // IIIF AI Extract (#220) — region-scoped VLM extraction over canvases.
    // The two JSON endpoints below double as the MCP-tool surface
    // (iiif_manifest_canvases, iiif_region_extract). AI routes via the gateway.
    // =====================================================================

    protected function aiExtractService(): \AhgIiif\Services\IiifAiExtractService
    {
        require_once $this->config('sf_plugins_dir') . '/ahgIiifPlugin/lib/Services/IiifAiExtractService.php';

        return new \AhgIiif\Services\IiifAiExtractService();
    }

    /** GET /iiif/ai/canvases/object/:id — list canvases (index, cantaloupe id, dims). */
    public function executeAiCanvases($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            $this->getResponse()->setStatusCode(401);

            return $this->renderText(json_encode(['error' => 'Authentication required']));
        }

        $objectId = (int) $request->getParameter('id');
        if (!$objectId) {
            $this->getResponse()->setStatusCode(400);

            return $this->renderText(json_encode(['error' => 'Object ID required']));
        }

        $canvases = $this->aiExtractService()->listCanvases($objectId);

        return $this->renderText(json_encode([
            'object_id' => $objectId,
            'count' => count($canvases),
            'canvases' => $canvases,
        ], JSON_UNESCAPED_SLASHES));
    }

    /**
     * POST /iiif/ai/extract — run a VLM extraction task on a canvas region.
     * Body (JSON): {object_id, canvas_index?, region?, task}
     */
    public function executeAiExtract($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            $this->getResponse()->setStatusCode(401);

            return $this->renderText(json_encode(['error' => 'Authentication required']));
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            // Fall back to form/query params for convenience.
            $data = [
                'object_id' => $request->getParameter('object_id'),
                'canvas_index' => $request->getParameter('canvas_index'),
                'region' => $request->getParameter('region'),
                'task' => $request->getParameter('task'),
            ];
        }

        $objectId = (int) ($data['object_id'] ?? 0);
        $task = (string) ($data['task'] ?? '');
        if (!$objectId || $task === '') {
            $this->getResponse()->setStatusCode(400);

            return $this->renderText(json_encode(['error' => 'object_id and task are required']));
        }

        $result = $this->aiExtractService()->extractRegion(
            $objectId,
            (int) ($data['canvas_index'] ?? 0),
            (string) ($data['region'] ?? 'full'),
            $task,
            $this->getUser()->getAttribute('user_id')
        );

        if (empty($result['success'])) {
            $this->getResponse()->setStatusCode(422);
        }

        return $this->renderText(json_encode($result, JSON_UNESCAPED_SLASHES));
    }

    /** GET /iiif/ai/extract/object/:id — stored extractions for an object. */
    public function executeAiExtractList($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            $this->getResponse()->setStatusCode(401);

            return $this->renderText(json_encode(['error' => 'Authentication required']));
        }

        $objectId = (int) $request->getParameter('id');
        if (!$objectId) {
            $this->getResponse()->setStatusCode(400);

            return $this->renderText(json_encode(['error' => 'Object ID required']));
        }

        $rows = $this->aiExtractService()->listExtractions($objectId);

        return $this->renderText(json_encode([
            'object_id' => $objectId,
            'count' => count($rows),
            'extractions' => $rows,
        ], JSON_UNESCAPED_SLASHES));
    }

    /** GET /iiif/ai/mcp — tiny-iiif style MCP tool manifest for the extract endpoints. */
    public function executeAiMcp($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            $this->getResponse()->setStatusCode(401);

            return $this->renderText(json_encode(['error' => 'Authentication required']));
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $baseUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

        return $this->renderText(json_encode(
            $this->aiExtractService()->mcpToolManifest($baseUrl),
            JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        ));
    }

    /** GET /iiif/ai/extract/review/object/:id — admin review + approve/reject UI. */
    public function executeAiExtractReview($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->isAdministrator()) {
            $this->forward($this->config('sf_secure_module'), $this->config('sf_secure_action'));
        }

        $this->objectId = (int) $request->getParameter('id');
        $svc = $this->aiExtractService();
        $extractions = $this->objectId ? $svc->listExtractions($this->objectId) : [];
        $canvases = $this->objectId ? $svc->listCanvases($this->objectId) : [];

        // Map canvas index → canvas so each extraction gets a region preview URL.
        $byIndex = [];
        foreach ($canvases as $c) {
            $byIndex[$c['index']] = $c;
        }
        foreach ($extractions as &$ex) {
            $canvas = $byIndex[(int) $ex['canvas_index']] ?? null;
            $ex['preview_url'] = $canvas ? $svc->previewUrl($canvas, (string) $ex['region']) : null;
        }
        unset($ex);

        $this->extractions = $extractions;
        $this->targetFields = ['scope_and_content', 'arrangement', 'physical_characteristics', 'archival_history', 'title'];

        // Object header (title + slug).
        $culture = \AtomExtensions\Helpers\CultureHelper::getCulture();
        $obj = \Illuminate\Database\Capsule\Manager::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function ($j) use ($culture) {
                $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
            ->where('io.id', $this->objectId)
            ->select('io.identifier', 'i18n.title', 's.slug')
            ->first();
        $this->objectTitle = $obj ? ($obj->title ?: $obj->identifier ?: ('#' . $this->objectId)) : ('#' . $this->objectId);
        $this->objectSlug = $obj->slug ?? null;
    }

    /** POST /iiif/ai/extract/approve — write extraction to an IO field (admin). */
    public function executeAiExtractApprove($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->isAdministrator()) {
            $this->getResponse()->setStatusCode(403);

            return $this->renderText(json_encode(['error' => 'Administrator access required']));
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            $data = [
                'extract_id' => $request->getParameter('extract_id'),
                'target_field' => $request->getParameter('target_field'),
            ];
        }

        $extractId = (int) ($data['extract_id'] ?? 0);
        if (!$extractId) {
            $this->getResponse()->setStatusCode(400);

            return $this->renderText(json_encode(['error' => 'extract_id required']));
        }

        $result = $this->aiExtractService()->approve(
            $extractId,
            (string) ($data['target_field'] ?? 'scope_and_content')
        );

        if (empty($result['success'])) {
            $this->getResponse()->setStatusCode(422);
        }

        return $this->renderText(json_encode($result, JSON_UNESCAPED_SLASHES));
    }

    /** POST /iiif/ai/extract/reject — mark extraction rejected (admin). */
    public function executeAiExtractReject($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->isAdministrator()) {
            $this->getResponse()->setStatusCode(403);

            return $this->renderText(json_encode(['error' => 'Administrator access required']));
        }

        $data = json_decode($request->getContent(), true);
        $extractId = (int) (($data['extract_id'] ?? null) ?? $request->getParameter('extract_id'));
        if (!$extractId) {
            $this->getResponse()->setStatusCode(400);

            return $this->renderText(json_encode(['error' => 'extract_id required']));
        }

        $ok = $this->aiExtractService()->reject($extractId);

        return $this->renderText(json_encode(['success' => $ok]));
    }

}
