<?php
declare(strict_types=1);

namespace AhgIiif\Services\Renderers;

/**
 * Image renderer using OpenSeadragon deep zoom viewer.
 *
 * Handles all image/* MIME types via IIIF Image API + Cantaloupe.
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 */
class ImageRenderer implements RendererInterface
{
    public function supports(string $mimeType, array $context = []): bool
    {
        return stripos($mimeType, 'image') !== false
            && stripos($mimeType, 'pdf') === false;
    }

    public function render(array $config): string
    {
        $vid = $config['viewerId'];
        $height = $config['options']['height'] ?? '600px';

        $html = '<div id="osd-' . $vid . '" class="osd-viewer" ';
        $html .= 'style="width:100%;height:' . $height . ';background:#1a1a1a;border-radius:8px;"></div>';

        return $html;
    }

    public function getName(): string
    {
        return 'openseadragon';
    }

    public function getPriority(): int
    {
        return 10;
    }
}
