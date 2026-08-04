<?php

declare(strict_types=1);

namespace AhgProvenancePlugin\Listeners;

/**
 * Puts the provenance panel on an archival description page without requiring a
 * theme template override.
 *
 * Why this exists: the panel is rendered by the `provenanceDisplay` component, and
 * the only callers of it are theme templates (ahgThemeB5Plugin, ahgMuseumPlugin,
 * ahgLibraryPlugin). On a stock AtoM install with no AHG theme, provenance data can
 * be captured and stored but is never displayed. Filtering the response shows it
 * without colliding with whatever theme is installed.
 *
 * Mirrors ahgIiifPlugin's ViewerInjector, which solved the same problem for viewers.
 */
class ProvenanceInjector
{
    /**
     * A description page is NOT served by the 'informationobject' module - AtoM
     * forwards /{slug} to the module for the record's descriptive standard, so
     * checking only for 'informationobject' means this silently never fires.
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
            // A display panel is an enhancement. It must never take a record page down.
            return $content;
        }
    }

    /**
     * When an AHG theme is present its templates already call the component, so
     * injecting here would render the panel twice.
     */
    private function themeProvidesPanel(): bool
    {
        return is_dir((string) \sfConfig::get('sf_plugins_dir') . '/ahgThemeB5Plugin');
    }

    private function inject(\sfEvent $event, $content)
    {
        $response = $event->getSubject();

        if (self::$injected || $this->themeProvidesPanel()) {
            return $content;
        }
        if (false !== stripos((string) $content, 'ahg-provenance-panel')) {
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

        // Render nothing at all when the record has no provenance: an empty
        // "Provenance" heading on every description is worse than no heading.
        $service = new \AhgProvenancePlugin\Service\ProvenanceService();
        $provenance = $service->getProvenanceForObject((int) $resource->id, $this->culture());
        if (empty($provenance['exists'])) {
            return $content;
        }

        $panel = $this->render((int) $resource->id);
        if ('' === $panel) {
            return $content;
        }

        self::$injected = true;

        return $this->place($content, $panel);
    }

    private function culture(): string
    {
        return (string) \sfContext::getInstance()->getUser()->getCulture();
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

    /**
     * Render the same component the themes use, so there is one panel implementation
     * and not a second copy that drifts.
     */
    private function render(int $objectId): string
    {
        $context = \sfContext::getInstance();
        // I18N too: place() calls __() on the heading, and the component template
        // uses it as well.
        $context->getConfiguration()->loadHelpers(['Partial', 'I18N']);

        return (string) get_component('provenance', 'provenanceDisplay', ['objectId' => $objectId]);
    }

    /**
     * The stylesheet is emitted as a <link> rather than an inline <style>: AtoM's CSP
     * has no 'unsafe-inline', so inline styles are silently dropped by the browser.
     */
    private function place(string $content, string $panel): string
    {
        // Heading markup copied from AtoM's own section headers ("Identity area",
        // "Digital object metadata") so the panel reads as part of the page rather
        // than as something bolted on.
        $heading = '<h2 class="h5 mb-0 atom-section-header">'
                 . '<div class="d-flex p-3 border-bottom text-primary">'
                 . htmlspecialchars(__('Provenance & Chain of Custody'), ENT_QUOTES)
                 . '</div></h2>';

        $block = '<link rel="stylesheet" href="/plugins/ahgProvenancePlugin/web/css/provenance.css">'
               . '<div class="ahg-provenance-panel"><section id="provenanceArea">'
               . $heading
               . $panel
               . '</section></div>';

        // Sit directly above the digital object metadata block. The viewer anchors are
        // fallbacks for records rendered without that block; which viewer marker is
        // present depends on whether ahgIiifPlugin's ViewerInjector has already run on
        // this content, since it replaces AtoM's own block with its viewer.
        $before = [
            '<div class="digitalObjectMetadata"',
            '<link rel="stylesheet" href="/plugins/ahgSeadragonPlugin',
            '<link rel="stylesheet" href="/plugins/ahgMiradorPlugin',
            '<div class="ahg-iiif-viewer',
            '<div class="digital-object-reference',
        ];
        foreach ($before as $anchor) {
            $at = stripos($content, $anchor);
            if (false !== $at) {
                return substr_replace($content, $block . "\n", $at, 0);
            }
        }

        // No digital object on this record: fall back to the usual containers.
        foreach (['<section id="relatedMaterialArea"', '<div id="content"', '<div id="main-column"'] as $anchor) {
            $at = stripos($content, $anchor);
            if (false === $at) {
                continue;
            }
            if (0 === strpos($anchor, '<section')) {
                return substr_replace($content, $block . "\n", $at, 0);
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
