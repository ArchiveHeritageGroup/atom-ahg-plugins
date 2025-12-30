<?php
/**
 * IIIF Presentation Manifest Generator
 * URL: /iiif-manifest.php?slug=SLUG or /iiif-manifest.php?id=OBJECT_ID
 * 
 * The id parameter can be either information_object.id or digital_object.id
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Load database credentials from AtoM config
$config = require __DIR__ . '/config/config.php';
$dsn = $config['all']['propel']['param']['dsn'];
$dbUser = $config['all']['propel']['param']['username'];
$dbPass = $config['all']['propel']['param']['password'];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Get identifier from query
$slug = $_GET['slug'] ?? '';
$objectId = $_GET['id'] ?? '';

if (empty($slug) && empty($objectId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing slug or id parameter']);
    exit;
}

// Look up the object
$object = null;

if (!empty($slug)) {
    $stmt = $pdo->prepare("
        SELECT io.id, io.identifier, i18n.title, s.slug
        FROM information_object io
        LEFT JOIN information_object_i18n i18n ON io.id = i18n.id AND i18n.culture = 'en'
        LEFT JOIN slug s ON io.id = s.object_id
        WHERE s.slug = ?
    ");
    $stmt->execute([$slug]);
    $object = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    // First try as information_object.id
    $stmt = $pdo->prepare("
        SELECT io.id, io.identifier, i18n.title, s.slug
        FROM information_object io
        LEFT JOIN information_object_i18n i18n ON io.id = i18n.id AND i18n.culture = 'en'
        LEFT JOIN slug s ON io.id = s.object_id
        WHERE io.id = ?
    ");
    $stmt->execute([$objectId]);
    $object = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If not found, try as digital_object.id
    if (!$object) {
        $stmt = $pdo->prepare("
            SELECT io.id, io.identifier, i18n.title, s.slug
            FROM digital_object do
            JOIN information_object io ON do.object_id = io.id
            LEFT JOIN information_object_i18n i18n ON io.id = i18n.id AND i18n.culture = 'en'
            LEFT JOIN slug s ON io.id = s.object_id
            WHERE do.id = ?
        ");
        $stmt->execute([$objectId]);
        $object = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if (!$object) {
    http_response_code(404);
    echo json_encode(['error' => 'Object not found']);
    exit;
}

// Get digital object(s) for this information object
$stmt = $pdo->prepare("
    SELECT do.id, do.name, do.path, do.mime_type, do.byte_size
    FROM digital_object do
    WHERE do.object_id = ?
    ORDER BY do.id
");
$stmt->execute([$object['id']]);
$digitalObjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

foreach ($digitalObjects as $do) {
    // Build Cantaloupe identifier from path
    $imagePath = ltrim($do['path'], '/');
    $cantaloupeId = str_replace('/', '_SL_', $imagePath) . $do['name'];

    $imageApiBase = "{$baseUrl}/iiif/2/{$cantaloupeId}";

    // Try to get image info from Cantaloupe
    $infoUrl = "{$imageApiBase}/info.json";
    $infoJson = @file_get_contents($infoUrl);

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
                        'profile' => 'http://iiif.io/api/image/2/level2.json'
                    ]
                ],
                'on' => $canvasId
            ]
        ]
    ];

    $canvasIndex++;
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
