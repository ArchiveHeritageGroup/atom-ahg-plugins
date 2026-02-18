<?php
declare(strict_types=1);

namespace AhgIiif\Services\Renderers;

/**
 * Mirador 3 renderer for rich IIIF viewing with comparison and annotation.
 *
 * Lower priority than ImageRenderer (OpenSeadragon) — used as alternative viewer.
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 */
class MiradorRenderer implements RendererInterface
{
    public function supports(string $mimeType, array $context = []): bool
    {
        // Supports images, same as ImageRenderer but lower priority
        return stripos($mimeType, 'image') !== false
            && stripos($mimeType, 'pdf') === false;
    }

    public function render(array $config): string
    {
        $vid = $config['viewerId'];
        $height = $config['options']['height'] ?? '600px';
        $manifestUrl = htmlspecialchars($config['manifestUrl'] ?? '');

        $html = '<div id="mirador-wrapper-' . $vid . '" style="position:relative;">';
        $html .= '<button id="close-mirador-' . $vid . '" class="btn btn-sm btn-outline-light" ';
        $html .= 'style="position:absolute;top:8px;right:8px;z-index:1000;" title="Close Mirador">';
        $html .= '<i class="fas fa-times"></i></button>';
        $html .= '<div id="mirador-' . $vid . '" ';
        $html .= 'style="width:100%;height:' . $height . ';border-radius:8px;" ';
        $html .= 'data-manifest="' . $manifestUrl . '"></div>';
        $html .= '</div>';

        return $html;
    }

    public function getName(): string
    {
        return 'mirador';
    }

    public function getPriority(): int
    {
        return 5; // Lower than ImageRenderer (10) — used as alternative
    }
}
