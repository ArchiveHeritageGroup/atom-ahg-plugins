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
        $options  = is_array($config['options'] ?? null) ? $config['options'] : [];

        // Site defaults from the IIIF settings screen, under anything the
        // caller passed. ahgIiifPlugin owns the table and the screen, so a
        // site with one viewer installed behaves the same as one with both.
        // Guarded: this viewer must still work if that service is older or
        // absent.
        if (class_exists('\\AhgIiif\\Services\\IiifViewerDefaults')) {
            $options = array_replace_recursive(
                \AhgIiif\Services\IiifViewerDefaults::forViewer('seadragon'),
                $options
            );
        }
        $height   = $this->height((string) ($options['height'] ?? '600px'));

        // Optional: a direct image URL, used when no IIIF image service is available
        // so the viewer still works on installs without Cantaloupe.
        $tile = isset($config['tileSource'])
            ? ' data-tile-source="' . htmlspecialchars((string) $config['tileSource'], ENT_QUOTES) . '"'
            : '';

        // Caller options. This renderer has accepted $config['options'] since it
        // was written and read only 'height' from it, so every OpenSeadragon
        // setting was unreachable no matter what a caller passed. boot.js
        // applies an allowlist to whatever arrives here.
        $opts = '';

        if ($passed = array_diff_key($options, ['height' => true])) {
            $opts = ' data-options="' . htmlspecialchars((string) json_encode($passed), ENT_QUOTES) . '"';
        }

        return '<div id="osd-' . $id . '" class="osd-viewer" data-viewer="openseadragon"'
             . ' data-rendered-by="ahgSeadragonPlugin"'
             . ' data-manifest="' . $manifest . '"'
             . $tile
             . $opts
             // Applied through the CSSOM by boot.js: a style attribute is dropped
             // by the host CSP without reporting anything.
             . ' data-height="' . htmlspecialchars($height, ENT_QUOTES) . '"'
             . ' data-assets="/plugins/ahgSeadragonPlugin/web/openseadragon"'
             . '></div>';
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
