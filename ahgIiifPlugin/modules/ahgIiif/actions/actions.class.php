<?php

/**
 * ahgIiif module actions
 *
 * Handles IIIF manifest generation for information objects
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 */
class ahgIiifActions extends sfActions
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
     * IIIF settings admin page
     */
    public function executeSettings(sfWebRequest $request)
    {
        // Check admin access
        if (!$this->context->user->isAuthenticated() || !$this->context->user->isAdministrator()) {
            $this->forward(sfConfig::get('sf_secure_module'), sfConfig::get('sf_secure_action'));
        }

        $this->setTemplate('settings');
    }
}
