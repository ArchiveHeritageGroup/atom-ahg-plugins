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

        // Geometry lives in viewer-switch.css. Only the configured height varies,
        // so only that is emitted here - in a <style> ELEMENT carrying the CSP
        // nonce, because a nonce never covers a style="" ATTRIBUTE and the
        // container would otherwise collapse to zero height under any policy.
        $n = \sfConfig::get('csp_nonce', '');
        $nonceAttr = $n ? ' ' . preg_replace('/^nonce=/', 'nonce="', $n) . '"' : '';

        $html = '<style' . $nonceAttr . '>#mirador-' . $vid . '{height:' . htmlspecialchars((string) $height) . ';}</style>';
        $html .= '<div id="mirador-wrapper-' . $vid . '" class="mirador-wrapper">';
        $html .= '<button id="close-mirador-' . $vid . '" class="btn btn-sm btn-outline-light ahg-mirador-close" ';
        $html .= 'title="Close Mirador">';
        $html .= '<i class="fas fa-times"></i></button>';
        $html .= '<div id="mirador-' . $vid . '" class="ahg-mirador-frame" ';
        // Required by ViewerInjector::renderViewers(); without it this renderer is
        // silently discarded from the viewer list.
        $html .= 'data-rendered-by="mirador" ';
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
