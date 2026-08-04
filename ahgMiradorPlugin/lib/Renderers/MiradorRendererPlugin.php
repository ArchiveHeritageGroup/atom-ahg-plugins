<?php

declare(strict_types=1);

namespace Ahg\Mirador;

use AhgIiif\Services\Renderers\RendererInterface;

/**
 * Mirador renderer, contributed to ahgIiifPlugin's registry.
 *
 * Emits markup and data-* attributes only - no <script>, no inline handlers - so
 * it satisfies the host CSP (script-src has no 'unsafe-inline') and can be booted
 * by the plugin's own JS from the data attributes.
 */
class MiradorRendererPlugin implements RendererInterface
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

        return '<div id="mirador-' . $id . '" class="mirador-viewer" data-viewer="mirador"'
             . ' data-rendered-by="ahgMiradorPlugin"'
             . ' data-manifest="' . $manifest . '"'
             . ' data-assets="/plugins/ahgMiradorPlugin/web/mirador"'
             // position:relative matters: Mirador positions its panels absolutely
             // against the nearest positioned ancestor. Without it they escape the
             // container and render as misplaced dark blocks over the page.
             . ' style="position:relative;overflow:hidden;width:100%;height:' . $height . ';background:#1a1a1a;border-radius:8px;"></div>';
    }

    public function getName(): string
    {
        return 'mirador-plugin';
    }

    /** Above the OpenSeadragon plugin (15): if both are installed, Mirador wins. */
    public function getPriority(): int
    {
        return 18;
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
