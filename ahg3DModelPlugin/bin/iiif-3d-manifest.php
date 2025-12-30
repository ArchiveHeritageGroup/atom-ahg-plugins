<?php
/**
 * IIIF 3D Manifest Generator
 * URL: /iiif/3d/{id}/manifest.json
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

$objectId = $_GET['id'] ?? '';

if (empty($objectId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing id parameter']);
    exit;
}

// Look up the information object
$stmt = $pdo->prepare("
    SELECT io.id, io.identifier, i18n.title, s.slug
    FROM information_object io
    LEFT JOIN information_object_i18n i18n ON io.id = i18n.id AND i18n.culture = 'en'
    LEFT JOIN slug s ON io.id = s.object_id
    WHERE io.id = ?
");
$stmt->execute([$objectId]);
$object = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$object) {
    http_response_code(404);
    echo json_encode(['error' => 'Object not found']);
    exit;
}

// Get 3D digital objects
$stmt = $pdo->prepare("
    SELECT do.id, do.name, do.path, do.mime_type
    FROM digital_object do
    WHERE do.object_id = ?
    ORDER BY do.id
");
$stmt->execute([$object['id']]);
$digitalObjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Filter for 3D formats
$threeDFormats = ['glb', 'gltf', 'obj', 'stl', 'ply', 'fbx', 'usdz'];
$threeDObjects = array_filter($digitalObjects, function($do) use ($threeDFormats) {
    $ext = strtolower(pathinfo($do['name'], PATHINFO_EXTENSION));
    return in_array($ext, $threeDFormats) || strpos($do['mime_type'], 'model') !== false;
});

if (empty($threeDObjects)) {
    http_response_code(404);
    echo json_encode(['error' => 'No 3D objects found for this record']);
    exit;
}

// Build base URLs
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$baseUrl = "{$protocol}://{$host}";

$label = $object['title'] ?: $object['identifier'] ?: 'Untitled';
$manifestId = "{$baseUrl}/iiif/3d/{$object['id']}/manifest.json";

// Build IIIF Presentation 3.0 manifest for 3D content
$items = [];
foreach ($threeDObjects as $do) {
    $modelUrl = $baseUrl . '/' . ltrim($do['path'], '/') . $do['name'];
    $ext = strtolower(pathinfo($do['name'], PATHINFO_EXTENSION));
    
    // Determine media type
    $mediaType = 'model/gltf-binary';
    if ($ext === 'gltf') $mediaType = 'model/gltf+json';
    elseif ($ext === 'obj') $mediaType = 'model/obj';
    elseif ($ext === 'stl') $mediaType = 'model/stl';
    
    $items[] = [
        'id' => "{$manifestId}/canvas/{$do['id']}",
        'type' => 'Canvas',
        'label' => ['en' => [$do['name']]],
        'items' => [
            [
                'id' => "{$manifestId}/canvas/{$do['id']}/annotation-page",
                'type' => 'AnnotationPage',
                'items' => [
                    [
                        'id' => "{$manifestId}/canvas/{$do['id']}/annotation",
                        'type' => 'Annotation',
                        'motivation' => 'painting',
                        'body' => [
                            'id' => $modelUrl,
                            'type' => 'Model',
                            'format' => $mediaType,
                            'label' => ['en' => [$do['name']]]
                        ],
                        'target' => "{$manifestId}/canvas/{$do['id']}"
                    ]
                ]
            ]
        ]
    ];
}

// IIIF Presentation API 3.0 Manifest
$manifest = [
    '@context' => 'http://iiif.io/api/presentation/3/context.json',
    'id' => $manifestId,
    'type' => 'Manifest',
    'label' => ['en' => [$label]],
    'metadata' => [],
    'items' => $items
];

if ($object['identifier']) {
    $manifest['metadata'][] = [
        'label' => ['en' => ['Identifier']],
        'value' => ['en' => [$object['identifier']]]
    ];
}

echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
