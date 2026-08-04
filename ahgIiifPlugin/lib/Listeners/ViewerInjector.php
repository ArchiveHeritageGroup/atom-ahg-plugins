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

        $html = $this->renderViewer($resource, $digitalObject);
        if ('' === $html) {
            return $content;
        }

        self::$injected = true;

        return $this->place($content, $html);
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

    private function renderViewer($resource, $digitalObject): string
    {
        $helper = \sfConfig::get('sf_plugins_dir') . '/ahgIiifPlugin/lib/Services';
        require_once $helper . '/Renderers/RendererInterface.php';
        require_once $helper . '/RendererRegistry.php';

        $registry = new \AhgIiif\Services\RendererRegistry();
        $renderer = $registry->getRenderer((string) $digitalObject->mimeType, []);
        if (!$renderer) {
            return '';
        }

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

        return $renderer->render([
            'viewerId' => 'io' . $resource->id,
            'mimeType' => (string) $digitalObject->mimeType,
            'manifestUrl' => \sfConfig::get('app_siteBaseUrl', '') . '/iiif/manifest/' . $slug,
            'tileSource' => $image,
            'options' => ['height' => '600px'],
        ]);
    }

    /**
     * Insert before the closing </body>, and load the owning plugin's boot script
     * with the CSP nonce - the fragment itself never carries a <script>, so this is
     * the only place a script tag is allowed to appear.
     */
    private function place(string $content, string $html): string
    {
        $plugin = '';
        if (preg_match('/' . self::PROVENANCE_ATTR . '="([A-Za-z0-9_-]+)"/', $html, $m)) {
            $plugin = $m[1];
        }

        $nonce = (string) \sfConfig::get('csp_nonce', '');
        $nonceAttr = $nonce ? ' ' . preg_replace('/^nonce=/', 'nonce="', $nonce) . '"' : '';

        $block = '<div class="ahg-iiif-viewer mb-4">' . $html . '</div>';
        if ($plugin) {
            $block .= '<script src="/plugins/' . $plugin . '/web/js/boot.js"' . $nonceAttr . '></script>';
        }

        // Put it at the top of the content area. Appending before </body> puts the
        // viewer *after the footer*, where nobody scrolls - it renders but appears
        // to do nothing.
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
