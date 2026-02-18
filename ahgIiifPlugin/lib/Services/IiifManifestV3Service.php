<?php
declare(strict_types=1);

namespace AhgIiif\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * IIIF Presentation API 3.0 manifest generator.
 *
 * @see https://iiif.io/api/presentation/3.0/
 * @author Johan Pieterse - The Archive and Heritage Group
 */
class IiifManifestV3Service
{
    private string $baseUrl;
    private string $cantaloupeBaseUrl;

    public function __construct(?string $baseUrl = null, ?string $cantaloupeBaseUrl = null)
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $this->baseUrl = $baseUrl ?? "{$protocol}://{$host}";
        $this->cantaloupeBaseUrl = $cantaloupeBaseUrl ?? \sfConfig::get('app_iiif_cantaloupe_internal_url', 'http://127.0.0.1:8182');
    }

    /**
     * Generate a IIIF Presentation API 3.0 manifest for an object.
     *
     * @param array $object Associative array with keys: id, identifier, title, slug
     * @param array $digitalObjects Array of digital object rows
     * @param string $culture Current culture code
     * @param int|null $cachedPageCount Cached TIFF page count (skip probing if available)
     * @return array The v3 manifest as an associative array
     */
    public function generateV3Manifest(array $object, array $digitalObjects, string $culture = 'en', ?int $cachedPageCount = null): array
    {
        $label = $object['title'] ?: $object['identifier'] ?: 'Untitled';
        $manifestId = "{$this->baseUrl}/iiif/v3/manifest/{$object['slug']}";

        $items = [];
        $canvasIndex = 1;

        foreach ($digitalObjects as $do) {
            $imagePath = ltrim($do['path'], '/');
            $cantaloupeId = str_replace('/', '_SL_', $imagePath) . $do['name'];

            $mimeType = strtolower($do['mime_type'] ?? '');
            $fileName = strtolower($do['name'] ?? '');

            // Multi-page TIFF handling
            if ($mimeType === 'image/tiff' || preg_match('/\.tiff?$/i', $fileName)) {
                $pageCount = $cachedPageCount ?? $this->probePageCount($cantaloupeId);

                if ($pageCount > 1) {
                    for ($pageNum = 1; $pageNum <= $pageCount; $pageNum++) {
                        $pageCantaloupeId = "{$cantaloupeId};{$pageNum}";
                        $dims = $this->getImageDimensions($pageCantaloupeId);
                        $items[] = $this->buildCanvas(
                            $manifestId,
                            $canvasIndex,
                            ($do['name'] ?: 'Image') . " - Page {$pageNum}",
                            $pageCantaloupeId,
                            $dims['width'],
                            $dims['height']
                        );
                        $canvasIndex++;
                    }
                    continue;
                }
            }

            // Single image / other types
            $dims = $this->getImageDimensions($cantaloupeId);
            $items[] = $this->buildCanvas(
                $manifestId,
                $canvasIndex,
                $do['name'] ?: "Image {$canvasIndex}",
                $cantaloupeId,
                $dims['width'],
                $dims['height']
            );
            $canvasIndex++;
        }

        $manifest = [
            '@context' => 'http://iiif.io/api/presentation/3/context.json',
            'id' => $manifestId,
            'type' => 'Manifest',
            'label' => [$culture => [$label]],
            'metadata' => [],
            'items' => $items,
        ];

        if ($object['identifier']) {
            $manifest['metadata'][] = [
                'label' => [$culture => ['Identifier']],
                'value' => [$culture => [$object['identifier']]],
            ];
        }

        if (!empty($items)) {
            $firstCanvas = $items[0];
            $firstAnno = $firstCanvas['items'][0]['items'][0] ?? null;
            if ($firstAnno) {
                $thumbnailId = str_replace('/full/max/', '/full/200,/', $firstAnno['body']['id']);
                $manifest['thumbnail'] = [[
                    'id' => $thumbnailId,
                    'type' => 'Image',
                    'format' => 'image/jpeg',
                    'service' => [[
                        'id' => $firstAnno['body']['service'][0]['id'] ?? '',
                        'type' => 'ImageService2',
                        'profile' => 'level2',
                    ]],
                ]];
            }
        }

        return $manifest;
    }

    /**
     * Build a v3 canvas with annotation page and painting annotation.
     */
    private function buildCanvas(string $manifestId, int $index, string $label, string $cantaloupeId, int $width, int $height): array
    {
        $canvasId = "{$manifestId}/canvas/{$index}";
        $imageApiBase = "{$this->baseUrl}/iiif/2/{$cantaloupeId}";

        return [
            'id' => $canvasId,
            'type' => 'Canvas',
            'label' => ['none' => [$label]],
            'width' => $width,
            'height' => $height,
            'items' => [
                [
                    'id' => "{$canvasId}/page",
                    'type' => 'AnnotationPage',
                    'items' => [
                        [
                            'id' => "{$canvasId}/page/annotation",
                            'type' => 'Annotation',
                            'motivation' => 'painting',
                            'body' => [
                                'id' => "{$imageApiBase}/full/max/0/default.jpg",
                                'type' => 'Image',
                                'format' => 'image/jpeg',
                                'width' => $width,
                                'height' => $height,
                                'service' => [
                                    [
                                        'id' => $imageApiBase,
                                        'type' => 'ImageService2',
                                        'profile' => 'level2',
                                    ],
                                ],
                            ],
                            'target' => $canvasId,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Probe Cantaloupe for multi-page TIFF page count.
     */
    private function probePageCount(string $cantaloupeId): int
    {
        $page2Url = "{$this->cantaloupeBaseUrl}/iiif/2/{$cantaloupeId};2/info.json";
        $page2Info = @file_get_contents($page2Url);

        if ($page2Info === false) {
            return 1;
        }

        $pageCount = 2;
        for ($i = 3; $i <= 100; $i++) {
            $pageUrl = "{$this->cantaloupeBaseUrl}/iiif/2/{$cantaloupeId};{$i}/info.json";
            $ctx = stream_context_create(['http' => ['timeout' => 1]]);
            $pageInfo = @file_get_contents($pageUrl, false, $ctx);
            if ($pageInfo === false) {
                break;
            }
            $pageCount = $i;
        }

        return $pageCount;
    }

    /**
     * Get image dimensions from Cantaloupe info.json.
     */
    private function getImageDimensions(string $cantaloupeId): array
    {
        $infoUrl = "{$this->cantaloupeBaseUrl}/iiif/2/{$cantaloupeId}/info.json";
        $infoJson = @file_get_contents($infoUrl);

        if ($infoJson) {
            $info = json_decode($infoJson, true);
            return [
                'width' => $info['width'] ?? 1000,
                'height' => $info['height'] ?? 1000,
            ];
        }

        return ['width' => 1000, 'height' => 1000];
    }
}
