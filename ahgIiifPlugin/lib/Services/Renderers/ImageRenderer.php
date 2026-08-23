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

        // data-rendered-by is REQUIRED. ViewerInjector::renderViewers() discards any
        // renderer whose output lacks it, so without this the renderer is silently
        // dropped, the viewer list comes back empty, and the injector never injects
        // anything on any page - which is exactly what it was doing.
        //
        // Geometry is a class plus a nonce-carrying <style> element rather than a
        // style attribute: a CSP nonce never covers a style ATTRIBUTE, so the
        // container would otherwise get no height and collapse.
        $n = \sfConfig::get('csp_nonce', '');
        $nonceAttr = $n ? ' ' . preg_replace('/^nonce=/', 'nonce="', $n) . '"' : '';

        $html = '<style' . $nonceAttr . '>#osd-' . $vid . '{height:' . $height . ';background:#1a1a1a;}</style>';
        $html .= '<div id="osd-' . $vid . '" class="osd-viewer" data-rendered-by="openseadragon"></div>';

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
