<?php

/**
 * ahgIiif module actions
 *
 * Handles IIIF manifest generation for information objects
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 */
class iiifActions extends AhgActions
{
    /**
     * Generate IIIF manifest by slug
     */
    public function executeManifest(sfWebRequest $request)
    {
        $slug = $request->getParameter('slug');

        if (empty($slug)) {
            $this->getResponse()->setStatusCode(400);
            return $this->renderText(json_encode(['error' => 'Missing slug parameter']));
        }

        return $this->generateManifest(['slug' => $slug]);
    }

    /**
     * Generate IIIF manifest by ID
     */
    public function executeManifestById(sfWebRequest $request)
    {
        $id = $request->getParameter('id');

        if (empty($id) || !is_numeric($id)) {
            $this->getResponse()->setStatusCode(400);
            return $this->renderText(json_encode(['error' => 'Missing or invalid id parameter']));
        }

        return $this->generateManifest(['id' => (int) $id]);
    }

    /**
     * Generate IIIF Presentation API 2.1 manifest
     */
    protected function generateManifest(array $params)
    {
        $this->getResponse()->setContentType('application/json');
        $this->getResponse()->setHttpHeader('Access-Control-Allow-Origin', '*');

        $db = \Illuminate\Database\Capsule\Manager::class;

        // Look up the object
        $object = null;

        if (!empty($params['slug'])) {
            $result = $db::table('information_object as io')
                ->leftJoin('information_object_i18n as i18n', function ($join) {
                    $join->on('io.id', '=', 'i18n.id')
                        ->where('i18n.culture', '=', 'en');
                })
                ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
                ->where('s.slug', $params['slug'])
                ->select('io.id', 'io.identifier', 'i18n.title', 's.slug')
                ->first();
            $object = $result ? (array) $result : null;
        } else {
            // Try as information_object.id
            $result = $db::table('information_object as io')
                ->leftJoin('information_object_i18n as i18n', function ($join) {
                    $join->on('io.id', '=', 'i18n.id')
                        ->where('i18n.culture', '=', 'en');
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
                    ->leftJoin('information_object_i18n as i18n', function ($join) {
                        $join->on('io.id', '=', 'i18n.id')
                            ->where('i18n.culture', '=', 'en');
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

        // Build URLs
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = "{$protocol}://{$host}";
        $cantaloupeBaseUrl = sfConfig::get('app_iiif_cantaloupe_internal_url', 'http://127.0.0.1:8182');

        $label = $object['title'] ?: $object['identifier'] ?: 'Untitled';
        $manifestId = "{$baseUrl}/iiif/manifest/{$object['slug']}";

        // Build canvases
        $canvases = [];
        $canvasIndex = 1;

        foreach ($digitalObjects as $do) {
            $imagePath = ltrim($do['path'], '/');
            $cantaloupeId = str_replace('/', '_SL_', $imagePath) . $do['name'];

            // Check for multi-page TIFF
            $isMultiPageTiff = false;
            $pageCount = 1;
            $mimeType = strtolower($do['mime_type'] ?? '');
            $fileName = strtolower($do['name'] ?? '');

            if ($mimeType === 'image/tiff' || preg_match('/\.tiff?$/i', $fileName)) {
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

        return $this->renderText(json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * IIIF settings admin page - display and save
     */
    public function executeSettings(sfWebRequest $request)
    {
        // Check admin access
        if (!$this->context->user->isAuthenticated() || !$this->context->user->isAdministrator()) {
            $this->forward(sfConfig::get('sf_secure_module'), sfConfig::get('sf_secure_action'));
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
    public function executeAnnotationsList(sfWebRequest $request)
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
    public function executeAnnotationsCreate(sfWebRequest $request)
    {
        $this->response->setContentType('application/json');
        $this->response->setHttpHeader('Access-Control-Allow-Origin', '*');

        if (!$this->context->user->isAuthenticated()) {
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
        $parsed['created_by'] = $this->context->user->getAttribute('user_id');

        $annotationId = $service->createAnnotation($parsed);

        return $this->renderText(json_encode([
            'success' => true,
            'id' => '#' . $annotationId,
        ]));
    }

    /**
     * Update an annotation
     * PUT /iiif/annotations/:id
     */
    public function executeAnnotationsUpdate(sfWebRequest $request)
    {
        $this->response->setContentType('application/json');
        $this->response->setHttpHeader('Access-Control-Allow-Origin', '*');

        if (!$this->context->user->isAuthenticated()) {
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

        return $this->renderText(json_encode(['success' => true]));
    }

    /**
     * Delete an annotation
     * DELETE /iiif/annotations/:id
     */
    public function executeAnnotationsDelete(sfWebRequest $request)
    {
        $this->response->setContentType('application/json');
        $this->response->setHttpHeader('Access-Control-Allow-Origin', '*');

        if (!$this->context->user->isAuthenticated()) {
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

        $service->deleteAnnotation($annotationId);

        return $this->renderText(json_encode(['success' => true]));
    }
}
