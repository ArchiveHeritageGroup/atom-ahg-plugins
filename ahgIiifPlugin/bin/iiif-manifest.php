<?php
/**
 * IIIF Presentation Manifest Generator
 * URL: /iiif-manifest.php?slug=SLUG or /iiif-manifest.php?id=OBJECT_ID
 *
 * The id parameter can be either information_object.id or digital_object.id
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Load Laravel Query Builder via framework bootstrap
$frameworkBootstrap = __DIR__ . '/atom-framework/bootstrap.php';
if (file_exists($frameworkBootstrap)) {
    require_once $frameworkBootstrap;
}

use Illuminate\Database\Capsule\Manager as DB;

// Get identifier from query with validation
$slug = isset($_GET['slug']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['slug']) : '';
$objectId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (empty($slug) && (empty($objectId) || $objectId <= 0)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid slug/id parameter']);
    exit;
}

// Look up the object
$object = null;

if (!empty($slug)) {
    $result = DB::table('information_object as io')
        ->leftJoin('information_object_i18n as i18n', function ($join) {
            $join->on('io.id', '=', 'i18n.id')
                ->where('i18n.culture', '=', 'en');
        })
        ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
        ->where('s.slug', $slug)
        ->select('io.id', 'io.identifier', 'i18n.title', 's.slug')
        ->first();
    $object = $result ? (array) $result : null;
} else {
    // First try as information_object.id
    $result = DB::table('information_object as io')
        ->leftJoin('information_object_i18n as i18n', function ($join) {
            $join->on('io.id', '=', 'i18n.id')
                ->where('i18n.culture', '=', 'en');
        })
        ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
        ->where('io.id', $objectId)
        ->select('io.id', 'io.identifier', 'i18n.title', 's.slug')
        ->first();
    $object = $result ? (array) $result : null;

    // If not found, try as digital_object.id
    if (!$object) {
        $result = DB::table('digital_object as do')
            ->join('information_object as io', 'do.object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as i18n', function ($join) {
                $join->on('io.id', '=', 'i18n.id')
                    ->where('i18n.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
            ->where('do.id', $objectId)
            ->select('io.id', 'io.identifier', 'i18n.title', 's.slug')
            ->first();
        $object = $result ? (array) $result : null;
    }
}

if (!$object) {
    http_response_code(404);
    echo json_encode(['error' => 'Object not found']);
    exit;
}

// Get digital object(s) for this information object
$digitalObjects = DB::table('digital_object as do')
    ->where('do.object_id', $object['id'])
    ->orderBy('do.id')
    ->select('do.id', 'do.name', 'do.path', 'do.mime_type', 'do.byte_size')
    ->get()
    ->map(fn ($row) => (array) $row)
    ->toArray();

if (empty($digitalObjects)) {
    http_response_code(404);
    echo json_encode(['error' => 'No digital objects found for this record']);
    exit;
}

// Build base URLs
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$baseUrl = "{$protocol}://{$host}";

$label = $object['title'] ?: $object['identifier'] ?: 'Untitled';
$manifestId = "{$baseUrl}/iiif-manifest.php?slug={$object['slug']}";

// Build canvases for each digital object
$canvases = [];
$canvasIndex = 1;

// Cantaloupe direct access URL (for server-side info.json lookup)
$cantaloupeBaseUrl = 'http://127.0.0.1:8182';

foreach ($digitalObjects as $do) {
    // Build Cantaloupe identifier from path
    $imagePath = ltrim($do['path'], '/');
    $cantaloupeId = str_replace('/', '_SL_', $imagePath) . $do['name'];

    // Check if this is a multi-page TIFF
    $isMultiPageTiff = false;
    $pageCount = 1;
    $mimeType = strtolower($do['mime_type'] ?? '');
    $fileName = strtolower($do['name'] ?? '');

    if ($mimeType === 'image/tiff' || preg_match('/\.tiff?$/i', $fileName)) {
        // Check for multiple pages using Cantaloupe's page syntax
        // Try page 2 (;2) - if it exists, this is a multi-page TIFF
        $page2InfoUrl = "{$cantaloupeBaseUrl}/iiif/2/{$cantaloupeId};2/info.json";
        $page2Info = @file_get_contents($page2InfoUrl);

        if ($page2Info !== false) {
            $isMultiPageTiff = true;
            // Count pages by trying sequential page numbers
            $pageCount = 2;
            for ($i = 3; $i <= 100; $i++) { // Max 100 pages
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
        // Create a canvas for each page of the multi-page TIFF
        for ($pageNum = 1; $pageNum <= $pageCount; $pageNum++) {
            $pageCantaloupeId = "{$cantaloupeId};{$pageNum}";
            $pageImageApiBase = "{$baseUrl}/iiif/2/{$pageCantaloupeId}";

            // Get page dimensions
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
                                'profile' => 'http://iiif.io/api/image/2/level2.json'
                            ]
                        ],
                        'on' => $canvasId
                    ]
                ]
            ];

            $canvasIndex++;
        }
    } else {
        // Single-page image handling (original logic)
        $imageApiBase = "{$baseUrl}/iiif/2/{$cantaloupeId}";

        // Try to get image info from Cantaloupe via localhost (bypasses nginx)
        $localInfoUrl = "{$cantaloupeBaseUrl}/iiif/2/{$cantaloupeId}/info.json";
        $infoJson = @file_get_contents($localInfoUrl);

        if ($infoJson) {
            $info = json_decode($infoJson, true);
            $width = $info['width'] ?? 1000;
            $height = $info['height'] ?? 1000;
        } else {
            // Fallback - try via public URL with SSL context
            $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
            $publicInfoUrl = "{$imageApiBase}/info.json";
            $infoJson = @file_get_contents($publicInfoUrl, false, $ctx);
            if ($infoJson) {
                $info = json_decode($infoJson, true);
                $width = $info['width'] ?? 1000;
                $height = $info['height'] ?? 1000;
            } else {
                $width = 1000;
                $height = 1000;
            }
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
                            'profile' => 'http://iiif.io/api/image/2/level2.json'
                        ]
                    ],
                    'on' => $canvasId
                ]
            ]
        ];

        $canvasIndex++;
    }
}

// Build IIIF Presentation API 2.1 Manifest
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
            'canvases' => $canvases
        ]
    ]
];

// Add metadata
if ($object['identifier']) {
    $manifest['metadata'][] = [
        'label' => 'Identifier',
        'value' => $object['identifier']
    ];
}

// Add thumbnail from first canvas
if (!empty($canvases)) {
    $firstCanvas = $canvases[0];
    $manifest['thumbnail'] = [
        '@id' => str_replace('/full/full/', '/full/200,/', $firstCanvas['images'][0]['resource']['@id']),
        'service' => $firstCanvas['images'][0]['resource']['service']
    ];
}

echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
