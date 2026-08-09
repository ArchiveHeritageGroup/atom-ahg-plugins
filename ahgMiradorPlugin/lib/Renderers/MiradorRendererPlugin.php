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

    /**
     * Keys a caller may set on the viewer.
     *
     * Everything here is passed to Mirador; nothing else is. The viewer page is
     * reachable by URL, so its query string is attacker-controlled, and the same
     * allowlist is applied again on that side - this one keeps our own callers
     * honest, the other one keeps strangers out.
     */
    private const PASSTHROUGH = [
        'window', 'workspace', 'workspaceControlPanel', 'thumbnailNavigation',
        'osdConfig', 'theme', 'galleryView', 'export',
    ];

    public function render(array $config): string
    {
        $id       = $this->attr((string) ($config['viewerId'] ?? 'v1'));
        $manifest = htmlspecialchars((string) ($config['manifestUrl'] ?? ''), ENT_QUOTES);
        $options  = is_array($config['options'] ?? null) ? $config['options'] : [];
        $height   = $this->height((string) ($options['height'] ?? '600px'));

        // Mounted in an iframe on purpose: Mirador is a full React/Material-UI app
        // that expects to own its viewport and positions panels absolutely. Inline in
        // an AtoM page it collides with Bootstrap and its panels scatter as dark
        // blocks. The iframe target is a STATIC page in this plugin's own web/ dir,
        // so the plugin still ships no routes and no PHP.
        $query = ['manifest' => (string) ($config['manifestUrl'] ?? '')];

        // Interface language. Mirador ships translations and nothing ever selected
        // one, so a reader on an Afrikaans or isiZulu interface got an English
        // viewer regardless.
        if ($culture = $this->culture($options)) {
            $query['lang'] = $culture;
        }

        // Caller overrides. $config['options'] has been accepted by this renderer
        // since it was written and read by nothing, so every setting Mirador has
        // was unreachable no matter what a caller passed.
        if ($passed = array_intersect_key($options, array_flip(self::PASSTHROUGH))) {
            $query['opts'] = json_encode($passed);
        }

        $src = '/plugins/ahgMiradorPlugin/web/viewer.html?'.http_build_query($query);

        return '<div id="mirador-' . $id . '" class="mirador-viewer" data-viewer="mirador"'
             . ' data-rendered-by="ahgMiradorPlugin"'
             . ' data-manifest="' . $manifest . '"'
             // Applied through the CSSOM by boot.js. A style attribute would be
             // dropped by the host CSP without reporting anything, which is what
             // left the viewer at the iframe default of 300x150.
             . ' data-height="' . htmlspecialchars($height, ENT_QUOTES) . '"'
             . ' data-assets="/plugins/ahgMiradorPlugin/web/mirador">'
             . '<iframe src="' . htmlspecialchars($src, ENT_QUOTES) . '" title="Mirador viewer"></iframe>'
             . '</div>';
    }

    /**
     * The language to open the viewer in.
     *
     * An explicit option wins; otherwise AtoM's current culture, when there is a
     * context to ask. Falls through to null on the CLI or in a test, where
     * sfContext does not exist and Mirador's own default is the right answer.
     */
    private function culture(array $options): ?string
    {
        $explicit = (string) ($options['language'] ?? '');

        if ($explicit) {
            return preg_match('/^[a-z]{2,3}(-[A-Za-z0-9]{2,8})?$/', $explicit) ? $explicit : null;
        }

        try {
            if (class_exists('sfContext') && \sfContext::hasInstance()) {
                $culture = \sfContext::getInstance()->getUser()->getCulture();

                return $culture ?: null;
            }
        } catch (\Throwable $e) {
            // Never worth failing a render over.
        }

        return null;
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
