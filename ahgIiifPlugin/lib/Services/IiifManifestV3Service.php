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
        // Build multi-language labels from all available i18n rows
        $multiLangLabels = $this->getMultiLanguageLabels((int) ($object['id'] ?? 0));
        $label = !empty($multiLangLabels)
            ? $multiLangLabels
            : [$culture => [$object['title'] ?: $object['identifier'] ?: 'Untitled']];
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
            'label' => $label,
            'metadata' => [],
            'items' => $items,
        ];

        if ($object['identifier']) {
            $manifest['metadata'][] = [
                'label' => [$culture => ['Identifier']],
                'value' => [$culture => [$object['identifier']]],
            ];
        }

        // Multi-language summary/scope from i18n
        $multiLangSummary = $this->getMultiLanguageField((int) ($object['id'] ?? 0), 'scope_and_content');
        if (!empty($multiLangSummary)) {
            $manifest['summary'] = $multiLangSummary;
        }

        // Rights (#184): Pull from rights table → map to URI
        $rightsUri = $this->resolveRightsUri($object['id'] ?? 0, $culture);
        if ($rightsUri) {
            $manifest['rights'] = $rightsUri;
        }

        // Required statement (#184): Institution attribution (multi-language)
        $attribution = $this->resolveRequiredStatement($object['id'] ?? 0, $culture);
        if ($attribution) {
            $manifest['requiredStatement'] = [
                'label' => ['none' => ['Attribution']],
                'value' => [$culture => [$attribution]],
            ];
        }

        // Provider (#184): Institution details
        $provider = $this->resolveProvider($object['id'] ?? 0, $culture);
        if ($provider) {
            $manifest['provider'] = [$provider];
        }

        // seeAlso (#184): Link back to the AtoM description page
        if (!empty($object['slug'])) {
            $manifest['seeAlso'] = [[
                'id' => "{$this->baseUrl}/{$object['slug']}",
                'type' => 'Dataset',
                'label' => [$culture => ['Archival description']],
                'format' => 'text/html',
            ]];
        }

        // Thumbnail with dimensions (#184)
        if (!empty($items)) {
            $firstCanvas = $items[0];
            $firstAnno = $firstCanvas['items'][0]['items'][0] ?? null;
            if ($firstAnno) {
                $thumbnailId = str_replace('/full/max/', '/full/200,/', $firstAnno['body']['id']);
                $serviceId = $firstAnno['body']['service'][0]['id'] ?? '';
                $manifest['thumbnail'] = [[
                    'id' => $thumbnailId,
                    'type' => 'Image',
                    'format' => 'image/jpeg',
                    'width' => 200,
                    'height' => (int) round(200 * ($firstCanvas['height'] / max($firstCanvas['width'], 1))),
                    'service' => [
                        [
                            'id' => $serviceId,
                            'type' => 'ImageService2',
                            'profile' => 'level2',
                        ],
                        [
                            'id' => $serviceId,
                            'type' => 'ImageService3',
                            'profile' => 'level2',
                        ],
                    ],
                ]];
            }
        }

        // Auth 2.0 service injection
        $authService = $this->resolveAuthService((int) ($object['id'] ?? 0));
        if ($authService) {
            // Add auth context
            $manifest['@context'] = [
                'http://iiif.io/api/presentation/3/context.json',
                'http://iiif.io/api/auth/2/context.json',
            ];
            $manifest['service'] = [$authService];
        }

        return $manifest;
    }

    /**
     * Resolve Auth 2.0 service description for this object (if protected).
     */
    private function resolveAuthService(int $objectId): ?array
    {
        if (!$objectId) {
            return null;
        }

        try {
            require_once dirname(__DIR__) . '/Services/IiifAuthService.php';
            $auth = new \IiifAuthService($this->baseUrl);
            $service = $auth->getAuth2ServiceForObject($objectId);

            if (!$service) {
                return null;
            }

            return $auth->formatServiceDescriptionV2($service);
        } catch (\Exception $e) {
            // Auth lookup failure is non-fatal
            return null;
        }
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
                                    [
                                        'id' => $imageApiBase,
                                        'type' => 'ImageService3',
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

    /**
     * Resolve rights URI from the rights table for this object.
     * Maps to Creative Commons or RightsStatements.org URIs.
     */
    /**
     * Resolve rights URI from the rights table for this object.
     * In AtoM, rights are linked to objects via the `relation` table.
     */
    private function resolveRightsUri(int $objectId, string $culture = 'en'): ?string
    {
        if (!$objectId) {
            return null;
        }

        try {
            // AtoM links rights to objects via the relation table
            $right = DB::table('relation as rel')
                ->join('rights as r', 'r.id', '=', 'rel.object_id')
                ->leftJoin('rights_i18n as ri', function ($join) {
                    $join->on('r.id', '=', 'ri.id')->where('ri.culture', '=', $culture);
                })
                ->where('rel.subject_id', $objectId)
                ->where('rel.type_id', function ($q) {
                    $q->select('id')->from('term')->where('taxonomy_id', 59)->limit(1);
                })
                ->select('r.basis_id', 'ri.license_note', 'ri.rights_note')
                ->first();

            if (!$right) {
                return null;
            }

            // Check for Creative Commons or RightsStatements.org URL
            foreach (['license_note', 'rights_note'] as $field) {
                $text = $right->$field ?? '';
                if (preg_match('#https?://creativecommons\.org/\S+#', $text, $m)) {
                    return $m[0];
                }
                if (preg_match('#https?://rightsstatements\.org/\S+#', $text, $m)) {
                    return $m[0];
                }
            }
        } catch (\Exception $e) {
            // Rights lookup failure is non-fatal
        }

        return null;
    }

    /**
     * Resolve requiredStatement (institution attribution) for the object.
     */
    private function resolveRequiredStatement(int $objectId, string $culture): ?string
    {
        if (!$objectId) {
            return null;
        }

        // Try ahg_settings for IIIF-specific attribution
        try {
            $attribution = DB::table('ahg_settings')
                ->where('setting_key', 'iiif_attribution')
                ->value('setting_value');

            if (!empty($attribution)) {
                return $attribution;
            }
        } catch (\Exception $e) {
            // ahg_settings table may not exist
        }

        // Fall back to repository name
        $repoId = DB::table('information_object')
            ->where('id', $objectId)
            ->value('repository_id');

        if ($repoId) {
            $name = DB::table('actor_i18n')
                ->where('id', $repoId)
                ->where('culture', $culture)
                ->value('authorized_form_of_name');

            if (!empty($name)) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Resolve provider (institution) for the manifest.
     */
    private function resolveProvider(int $objectId, string $culture): ?array
    {
        if (!$objectId) {
            return null;
        }

        $repoId = DB::table('information_object')
            ->where('id', $objectId)
            ->value('repository_id');

        if (!$repoId) {
            return null;
        }

        $repo = DB::table('actor_i18n')
            ->where('id', $repoId)
            ->where('culture', $culture)
            ->select('authorized_form_of_name')
            ->first();

        if (!$repo || empty($repo->authorized_form_of_name)) {
            return null;
        }

        $slug = DB::table('slug')
            ->where('object_id', $repoId)
            ->value('slug');

        $provider = [
            'id' => $slug ? "{$this->baseUrl}/{$slug}" : "{$this->baseUrl}",
            'type' => 'Agent',
            'label' => [$culture => [$repo->authorized_form_of_name]],
        ];

        // Add homepage
        $provider['homepage'] = [[
            'id' => $this->baseUrl,
            'type' => 'Text',
            'label' => [$culture => [$repo->authorized_form_of_name]],
            'format' => 'text/html',
        ]];

        return $provider;
    }

    /**
     * Get multi-language labels (title) for an object from all available cultures.
     *
     * @return array<string, string[]> e.g. {"en": ["Title"], "af": ["Titel"]}
     */
    private function getMultiLanguageLabels(int $objectId): array
    {
        if (!$objectId) {
            return [];
        }

        $rows = DB::table('information_object_i18n')
            ->where('id', $objectId)
            ->whereNotNull('title')
            ->where('title', '!=', '')
            ->select('culture', 'title')
            ->get();

        $labels = [];
        foreach ($rows as $row) {
            $labels[$row->culture] = [$row->title];
        }

        return $labels;
    }

    /**
     * Get a multi-language field from information_object_i18n.
     *
     * @return array<string, string[]> Language map
     */
    private function getMultiLanguageField(int $objectId, string $field): array
    {
        if (!$objectId) {
            return [];
        }

        $allowed = ['scope_and_content', 'extent_and_medium', 'archival_history', 'arrangement'];
        if (!in_array($field, $allowed, true)) {
            return [];
        }

        $rows = DB::table('information_object_i18n')
            ->where('id', $objectId)
            ->whereNotNull($field)
            ->where($field, '!=', '')
            ->select('culture', $field)
            ->get();

        $values = [];
        foreach ($rows as $row) {
            $values[$row->culture] = [$row->$field];
        }

        return $values;
    }
}
