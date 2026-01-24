<?php

declare(strict_types=1);

namespace ahg3DModelPlugin\Provider;

use AtomFramework\Contracts\Model3DProviderInterface;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Model 3D Provider implementation.
 *
 * Adapts the Model3DService to the framework's Model3DProviderInterface.
 * This allows the framework to use 3D model capabilities without hardcoded require_once.
 */
class Model3DProvider implements Model3DProviderInterface
{
    private const SUPPORTED_FORMATS = [
        'model/gltf-binary' => 'glb',
        'model/gltf+json' => 'gltf',
        'model/obj' => 'obj',
        'model/stl' => 'stl',
        'application/octet-stream' => 'fbx',
        'application/x-ply' => 'ply',
        'model/vnd.usdz+zip' => 'usdz',
    ];

    private const FORMAT_EXTENSIONS = ['glb', 'gltf', 'obj', 'stl', 'fbx', 'ply', 'usdz'];

    /**
     * {@inheritdoc}
     */
    public function is3dModel(int $digitalObjectId): bool
    {
        try {
            $digitalObject = DB::table('digital_object')
                ->where('id', $digitalObjectId)
                ->first();

            if (!$digitalObject) {
                return false;
            }

            // Check by MIME type
            if (isset(self::SUPPORTED_FORMATS[$digitalObject->mime_type])) {
                return true;
            }

            // Check by file extension
            $name = $digitalObject->name ?? '';
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            return in_array($extension, self::FORMAT_EXTENSIONS, true);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getViewerConfig(int $digitalObjectId): array
    {
        $digitalObject = DB::table('digital_object')
            ->where('id', $digitalObjectId)
            ->first();

        if (!$digitalObject) {
            return [];
        }

        $format = self::SUPPORTED_FORMATS[$digitalObject->mime_type] ??
            strtolower(pathinfo($digitalObject->name ?? '', PATHINFO_EXTENSION));

        // Get viewer settings
        $settings = $this->getSettings();

        return [
            'viewer' => $settings['default_viewer'] ?? 'model-viewer',
            'config' => [
                'modelUrl' => '/' . trim($digitalObject->path ?? '', '/') . '/' . $digitalObject->name,
                'format' => $format,
                'mimeType' => $digitalObject->mime_type,
                'name' => $digitalObject->name,
                'size' => $digitalObject->byte_size ?? 0,
                'enableAr' => $settings['enable_ar'] ?? true,
                'enableFullscreen' => $settings['enable_fullscreen'] ?? true,
                'background' => $settings['default_background'] ?? '#f5f5f5',
                'autoRotate' => $settings['enable_auto_rotate'] ?? true,
            ],
            'formats' => self::FORMAT_EXTENSIONS,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedFormats(): array
    {
        return self::FORMAT_EXTENSIONS;
    }

    /**
     * {@inheritdoc}
     */
    public function generateThumbnail(int $digitalObjectId, array $options = []): array
    {
        // Check if model exists
        $digitalObject = DB::table('digital_object')
            ->where('id', $digitalObjectId)
            ->first();

        if (!$digitalObject) {
            return [
                'success' => false,
                'path' => null,
                'error' => 'Digital object not found',
            ];
        }

        // Check for existing thumbnail
        $existingThumb = DB::table('object_3d_thumbnail')
            ->where('digital_object_id', $digitalObjectId)
            ->first();

        if ($existingThumb && file_exists($existingThumb->path)) {
            return [
                'success' => true,
                'path' => $existingThumb->path,
                'error' => null,
            ];
        }

        // Thumbnail generation would require WebGL rendering
        // This is typically done via a Python script or headless browser
        return [
            'success' => false,
            'path' => null,
            'error' => 'Thumbnail generation not yet implemented in provider',
        ];
    }

    /**
     * Get 3D viewer settings.
     */
    private function getSettings(): array
    {
        try {
            $settings = [];
            $rows = DB::table('viewer_3d_settings')->get();

            foreach ($rows as $row) {
                $value = $row->setting_value;
                if ($row->setting_type === 'boolean') {
                    $value = (bool) $value;
                } elseif ($row->setting_type === 'integer') {
                    $value = (int) $value;
                } elseif ($row->setting_type === 'json') {
                    $value = json_decode($value, true);
                }
                $settings[$row->setting_key] = $value;
            }

            return $settings;
        } catch (\Exception $e) {
            return [
                'default_viewer' => 'model-viewer',
                'enable_ar' => true,
                'enable_fullscreen' => true,
                'default_background' => '#f5f5f5',
                'enable_auto_rotate' => true,
            ];
        }
    }
}
