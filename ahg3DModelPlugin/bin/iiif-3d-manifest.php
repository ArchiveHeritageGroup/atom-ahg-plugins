<?php
/**
 * IIIF 3D Manifest Generator
 * URL: /iiif/3d/{id}/manifest.json
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Load Laravel Query Builder via framework bootstrap
$frameworkBootstrap = __DIR__ . '/../../atom-framework/bootstrap.php';
if (file_exists($frameworkBootstrap)) {
    require_once $frameworkBootstrap;
}

use Illuminate\Database\Capsule\Manager as DB;

$objectId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (empty($objectId) || $objectId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid id parameter']);
    exit;
}

// Look up the information object
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

if (!$object) {
    http_response_code(404);
    echo json_encode(['error' => 'Object not found']);
    exit;
}

// Get 3D digital objects
$digitalObjects = DB::table('digital_object as do')
    ->where('do.object_id', $object['id'])
    ->orderBy('do.id')
    ->select('do.id', 'do.name', 'do.path', 'do.mime_type')
    ->get()
    ->map(fn ($row) => (array) $row)
    ->toArray();

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
