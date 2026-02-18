<?php
declare(strict_types=1);

namespace AhgIiif\Services\Renderers;

/**
 * 3D model renderer using Google model-viewer web component.
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 */
class ThreeDRenderer implements RendererInterface
{
    public function supports(string $mimeType, array $context = []): bool
    {
        if (!empty($context['has3D'])) {
            return true;
        }

        return stripos($mimeType, 'model') !== false
            || preg_match('/\.(glb|gltf|obj|fbx|stl|ply|3ds)$/i', $mimeType) === 1;
    }

    public function render(array $config): string
    {
        $vid = $config['viewerId'];
        $height = $config['options']['height'] ?? '600px';
        $baseUrl = $config['baseUrl'] ?? '';
        $model = $config['options']['model'] ?? null;

        if (!$model) {
            return '<div class="alert alert-warning">No 3D model data available.</div>';
        }

        $path = trim($model->path ?? '', '/');
        $modelUrl = $baseUrl . '/' . $path . '/' . ($model->filename ?? '');
        $arAttr = !empty($model->ar_enabled) ? 'ar ar-modes="webxr scene-viewer quick-look"' : '';
        $autoRotate = !empty($model->auto_rotate) ? 'auto-rotate' : '';
        $cameraOrbit = htmlspecialchars($model->camera_orbit ?? '0deg 75deg 105%');
        $bgColor = htmlspecialchars($model->background_color ?? '#f5f5f5');
        $poster = !empty($model->poster_image) ? 'poster="' . htmlspecialchars($baseUrl . $model->poster_image) . '"' : '';

        $html = '<div id="model-wrapper-' . $vid . '" class="model-wrapper">';
        $html .= '<model-viewer id="model-' . $vid . '" ';
        $html .= 'src="' . htmlspecialchars($modelUrl) . '" ';
        $html .= $poster . ' ';
        $html .= $arAttr . ' ';
        $html .= $autoRotate . ' ';
        $html .= 'camera-controls touch-action="pan-y" ';
        $html .= 'camera-orbit="' . $cameraOrbit . '" ';
        $html .= 'style="width:100%;height:' . $height . ';background-color:' . $bgColor . ';border-radius:8px;">';
        $html .= '<button slot="ar-button" class="btn btn-primary" style="position:absolute;bottom:16px;right:16px;">';
        $html .= '<i class="fas fa-cube me-1"></i>View in AR</button>';
        $html .= '</model-viewer></div>';

        return $html;
    }

    public function getName(): string
    {
        return 'model-viewer';
    }

    public function getPriority(): int
    {
        return 60;
    }
}
