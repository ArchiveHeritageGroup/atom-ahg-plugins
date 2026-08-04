<?php

declare(strict_types=1);

namespace AhgIiif\Listeners;

/**
 * Injects a IIIF viewer into an archival description page without requiring a
 * theme template override.
 *
 * Why response.filter_content and not a template: the viewer must appear on stock
 * AtoM pages in installs that do not have ahgThemeB5Plugin. Overriding
 * informationobject templates would collide with whatever theme is installed;
 * filtering the response does not.
 *
 * The viewer markup itself comes from RendererRegistry, so which viewer appears is
 * decided by which viewer plugin is installed (ahgMiradorPlugin,
 * ahgSeadragonPlugin, ...). This class only decides *where* it goes.
 */
class ViewerInjector
{
    /** Marker left by every renderer; also tells us which plugin to boot. */
    private const PROVENANCE_ATTR = 'data-rendered-by';

    /**
     * A description page is NOT served by the 'informationobject' module - AtoM
     * forwards /{slug} to the module for the record's descriptive standard. Checking
     * only for 'informationobject' silently never fires. Same list as
     * ahgVersionControlPlugin's ViewLinkInjector, which solved this first.
     */
    private const VIEW_MODULES = [
        'informationobject',
        'sfIsadPlugin',
        'sfRadPlugin',
        'sfDcPlugin',
        'sfModsPlugin',
        'sfDacsPlugin',
    ];

    /** response.filter_content can fire more than once per request. */
    private static bool $injected = false;

    public static function filter(\sfEvent $event, $content)
    {
        try {
            return (new self())->inject($event, $content);
        } catch (\Throwable $e) {
            // A viewer is an enhancement. It must never take a record page down.
            return $content;
        }
    }

    /**
     * This injector exists for installs with NO AHG theme. When ahgThemeB5Plugin is
     * present it already dispatches viewers from its own templates, so injecting
     * here duplicates it - and duplicates whatever that renderer emits, including
     * "no data" placeholders.
     */
    private function themeProvidesViewer(): bool
    {
        return is_dir((string) \sfConfig::get('sf_plugins_dir') . '/ahgThemeB5Plugin');
    }

    private function inject(\sfEvent $event, $content)
    {
        $response = $event->getSubject();

        // Once per request, never on a themed install, never into content that
        // already carries a viewer.
        if (self::$injected || $this->themeProvidesViewer()) {
            return $content;
        }
        if (false !== stripos((string) $content, 'ahg-iiif-viewer')) {
            return $content;
        }

        if (!$this->isHtmlGet($response)) {
            return $content;
        }

        $context = \sfContext::getInstance();
        if (!in_array($context->getModuleName(), self::VIEW_MODULES, true)
            || 'index' !== $context->getActionName()) {
            return $content;
        }

        $resource = $context->getActionStack()->getLastEntry()->getActionInstance()->resource ?? null;
        if (!$resource || !isset($resource->id)) {
            return $content;
        }

        $digitalObject = $this->firstDigitalObject($resource);
        if (!$digitalObject) {
            return $content;
        }

        $viewers = $this->renderViewers($resource, $digitalObject);
        if (!$viewers) {
            return $content;
        }

        self::$injected = true;

        return $this->place($content, $viewers);
    }

    private function isHtmlGet($response): bool
    {
        if (!$response instanceof \sfWebResponse) {
            return false;
        }
        if ('GET' !== ($_SERVER['REQUEST_METHOD'] ?? 'GET')) {
            return false;
        }

        return false !== stripos((string) $response->getContentType(), 'text/html');
    }

    private function firstDigitalObject($resource)
    {
        $objects = $resource->digitalObjectsRelatedByobjectId ?? null;
        if (!$objects) {
            return null;
        }

        foreach ($objects as $object) {
            return $object;
        }

        return null;
    }

    /**
     * Render EVERY viewer plugin that supports this file, highest priority first.
     *
     * Built-in renderers are skipped: they carry no data-rendered-by, so there is no
     * plugin to load a boot script or stylesheet from, and a container nothing can
     * initialise is just a black rectangle.
     *
     * @return array<int, array{plugin:string,label:string,html:string}>
     */
    private function renderViewers($resource, $digitalObject): array
    {
        $helper = \sfConfig::get('sf_plugins_dir') . '/ahgIiifPlugin/lib/Services';
        require_once $helper . '/Renderers/RendererInterface.php';
        require_once $helper . '/RendererRegistry.php';

        $registry = new \AhgIiif\Services\RendererRegistry();
        $mimeType = (string) $digitalObject->mimeType;

        $slug = $resource->slug ?? '';

        // Use the REFERENCE derivative, never the master: /uploads/r/* is routed
        // through digitalobject/view, which gates masters behind readMaster, so a
        // master URL 404s for anonymous users. Viewers want the derivative anyway.
        $image = '';
        if (method_exists($digitalObject, 'getRepresentationByUsage')) {
            $reference = $digitalObject->getRepresentationByUsage(\QubitTerm::REFERENCE_ID);
            if ($reference) {
                $image = (string) $reference->getFullPath();
            }
        }
        if ('' === $image) {
            $image = (string) ($digitalObject->getFullPath() ?? '');
        }

        $config = [
            'viewerId' => 'io' . $resource->id,
            'mimeType' => $mimeType,
            'manifestUrl' => \sfConfig::get('app_siteBaseUrl', '') . '/iiif/manifest/' . $slug,
            'tileSource' => $image,
            'options' => ['height' => '600px'],
        ];

        $viewers = [];
        foreach ($registry->all() as $renderer) {
            if (!$renderer->supports($mimeType, [])) {
                continue;
            }

            $html = (string) $renderer->render($config);
            if ('' === $html || !preg_match('/' . self::PROVENANCE_ATTR . '="([A-Za-z0-9_-]+)"/', $html, $m)) {
                continue;
            }

            $viewers[] = [
                'plugin' => $m[1],
                'label' => $this->label($renderer->getName()),
                'html' => $html,
            ];
        }

        return $viewers;
    }

    /** Human label for a switch button, from the renderer name. */
    private function label(string $name): string
    {
        $known = [
            'mirador-plugin' => 'Mirador',
            'openseadragon-plugin' => 'OpenSeadragon',
        ];

        if (isset($known[$name])) {
            return $known[$name];
        }

        return ucwords(str_replace(['-plugin', '-', '_'], ['', ' ', ' '], $name));
    }

    /**
     * Build the viewer block and place it where AtoM's own image display was.
     *
     * With more than one viewer plugin enabled, all of them are rendered and a switch
     * button per viewer is emitted. Only the first pane is visible; the rest carry
     * `hidden`, and viewer-switch.js toggles them. Every style and script is an
     * external file - AtoM's CSP has no 'unsafe-inline', so an inline style attribute
     * is silently dropped and an inline script is blocked outright.
     *
     * @param array<int, array{plugin:string,label:string,html:string}> $viewers
     */
    private function place(string $content, array $viewers): string
    {
        $nonce = (string) \sfConfig::get('csp_nonce', '');
        $nonceAttr = $nonce ? ' ' . preg_replace('/^nonce=/', 'nonce="', $nonce) . '"' : '';

        $assets = '';
        $panes = '';
        $tabs = '';
        $seen = [];

        foreach ($viewers as $i => $viewer) {
            $plugin = $viewer['plugin'];

            if (!isset($seen[$plugin])) {
                $seen[$plugin] = true;
                $assets .= '<link rel="stylesheet" href="/plugins/' . $plugin . '/web/css/viewer.css">';
                $assets .= '<script src="/plugins/' . $plugin . '/web/js/boot.js"' . $nonceAttr . '></script>';
            }

            $active = 0 === $i;
            $panes .= '<div class="ahg-viewer-pane" data-viewer-plugin="' . $plugin . '"'
                    . ($active ? '' : ' hidden') . '>' . $viewer['html'] . '</div>';

            if (count($viewers) > 1) {
                $tabs .= '<button type="button" class="ahg-viewer-tab' . ($active ? ' is-active' : '') . '"'
                       . ' data-viewer-target="' . $plugin . '">' . htmlspecialchars($viewer['label'], ENT_QUOTES) . '</button>';
            }
        }

        $switcher = '';
        if ('' !== $tabs) {
            $assets .= '<link rel="stylesheet" href="/plugins/ahgIiifPlugin/web/css/viewer-switch.css">';
            $assets .= '<script src="/plugins/ahgIiifPlugin/web/js/viewer-switch.js"' . $nonceAttr . '></script>';
            $switcher = '<div class="ahg-viewer-tabs" role="group" aria-label="Choose viewer">' . $tabs . '</div>';
        }

        $block = $assets . '<div class="ahg-iiif-viewer mb-4">' . $switcher . $panes . '</div>';

        // Prefer REPLACING AtoM's built-in digital object display. The viewer is a
        // richer presentation of the same file, so showing both leaves the page with
        // the image twice. The block contains only an <a> and an <img>, no nested
        // divs, so a non-greedy match to the first </div> is safe.
        $replaced = preg_replace(
            '#<div class="digital-object-reference[^"]*"[^>]*>.*?</div>#s',
            $block,
            $content,
            1,
            $count
        );
        if (null !== $replaced && $count > 0) {
            return $replaced;
        }

        foreach (['<div id="content"', '<div id="main-column"'] as $anchor) {
            $at = stripos($content, $anchor);
            if (false === $at) {
                continue;
            }
            $close = strpos($content, '>', $at);
            if (false !== $close) {
                return substr_replace($content, "\n" . $block, $close + 1, 0);
            }
        }

        $pos = strripos($content, '</body>');

        return false === $pos ? $content . $block : substr_replace($content, $block . "\n", $pos, 0);
    }
}
