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

        // No data-rendered-by, deliberately. ViewerInjector derives a boot script
        // path from it (/plugins/<value>/web/js/boot.js), and this built-in ships
        // none, so tagging it injected a container nothing could start - a black
        // box plus two 404s on any install without a viewer plugin, and a second
        // dead tab beside ahgSeadragonPlugin's working one. Untagged, the injector
        // skips it: a viewer plugin supplies the viewer, and with none enabled
        // AtoM's own image display stays. If no viewer appears, enable
        // ahgSeadragonPlugin rather than tagging this.
        //
        // Geometry is a class plus a nonce-carrying <style> element rather than a
        // style attribute: a CSP nonce never covers a style ATTRIBUTE, so the
        // container would otherwise get no height and collapse.
        $n = \sfConfig::get('csp_nonce', '');
        $nonceAttr = $n ? ' ' . preg_replace('/^nonce=/', 'nonce="', $n) . '"' : '';

        $html = '<style' . $nonceAttr . '>#osd-' . $vid . '{height:' . $height . ';background:#1a1a1a;}</style>';
        $html .= '<div id="osd-' . $vid . '" class="osd-viewer"></div>';

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
