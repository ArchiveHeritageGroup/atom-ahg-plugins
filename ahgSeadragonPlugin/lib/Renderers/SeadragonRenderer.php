<?php

declare(strict_types=1);

namespace Ahg\Seadragon;

use AhgIiif\Services\Renderers\RendererInterface;

/**
 * OpenSeadragon deep-zoom renderer, contributed to ahgIiifPlugin's registry.
 *
 * Emits markup and data-* attributes only - no <script>, no inline handlers - so
 * it satisfies the host CSP (script-src has no 'unsafe-inline') and can be booted
 * by the plugin's own JS from the data attributes.
 */
class SeadragonRenderer implements RendererInterface
{
    public function supports(string $mimeType, array $context = []): bool
    {
        return stripos($mimeType, 'image') !== false;
    }

    public function render(array $config): string
    {
        $id       = $this->attr((string) ($config['viewerId'] ?? 'v1'));
        $manifest = htmlspecialchars((string) ($config['manifestUrl'] ?? ''), ENT_QUOTES);
        $height   = $this->height((string) ($config['options']['height'] ?? '600px'));

        return '<div id="osd-' . $id . '" class="osd-viewer" data-viewer="openseadragon"'
             . ' data-rendered-by="ahgSeadragonPlugin"'
             . ' data-manifest="' . $manifest . '"'
             . ' style="width:100%;height:' . $height . ';background:#1a1a1a;border-radius:8px;"></div>';
    }

    public function getName(): string
    {
        return 'openseadragon-plugin';
    }

    /** Above ahgIiifPlugin's built-in ImageRenderer (10) so an installed viewer wins. */
    public function getPriority(): int
    {
        return 15;
    }

    private function attr(string $v): string
    {
        return preg_replace('/[^A-Za-z0-9_-]/', '', $v) ?: 'v1';
    }

    private function height(string $v): string
    {
        return preg_match('/^\d+(px|%|vh)$/', $v) ? $v : '600px';
    }
}
