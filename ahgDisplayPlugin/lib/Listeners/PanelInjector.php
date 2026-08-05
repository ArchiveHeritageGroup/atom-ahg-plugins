<?php

declare(strict_types=1);

namespace AhgDisplay\Listeners;

/**
 * Renders plugin display panels on a stock AtoM description page.
 *
 * DisplayActionRegistry already collects `display_panels` declarations from enabled
 * plugins and can render them, but the only caller is ahgThemeB5Plugin's templates.
 * On an install with no AHG theme every declared panel is therefore invisible -
 * declared, collected, never shown.
 *
 * This listener is that missing caller. One listener serves every plugin, present and
 * future, so a plugin contributes a panel by declaring it in extension.json and needs
 * no listener of its own.
 *
 * Because the registry only loads from ENABLED plugins, unselecting a plugin in
 * Settings removes its panel here with no further work.
 */
class PanelInjector
{
    private const REGISTRY = '\AhgDisplay\Registry\DisplayActionRegistry';

    /**
     * A description page is NOT the 'informationobject' module - AtoM forwards /{slug}
     * to the module for the record's descriptive standard.
     */
    private const VIEW_MODULES = [
        'informationobject',
        'sfIsadPlugin',
        'sfRadPlugin',
        'sfDcPlugin',
        'sfModsPlugin',
        'sfDacsPlugin',
    ];

    /**
     * Rendered in declaration order. 'sidebar' is deliberately excluded: a stock
     * install has no sidebar anchor we can rely on, and guessing one puts panels in
     * visibly wrong places.
     */
    private const POSITIONS = ['before-media', 'before-notes', 'below-content', 'after-content'];

    /** response.filter_content can fire more than once per request. */
    private static bool $injected = false;

    public static function filter(\sfEvent $event, $content)
    {
        try {
            return (new self())->inject($event, $content);
        } catch (\Throwable $e) {
            // Panels are an enhancement. They must never take a record page down.
            return $content;
        }
    }

    /** With an AHG theme installed the templates already call the registry. */
    private function themeRendersPanels(): bool
    {
        return is_dir((string) \sfConfig::get('sf_plugins_dir') . '/ahgThemeB5Plugin');
    }

    private function inject(\sfEvent $event, $content)
    {
        $response = $event->getSubject();

        if (self::$injected || $this->themeRendersPanels() || !$this->isHtmlGet($response)) {
            return $content;
        }
        if (!class_exists(self::REGISTRY)) {
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

        $html = $this->renderPanels($resource);
        if ('' === $html) {
            return $content;
        }

        self::$injected = true;

        return $this->place((string) $content, $html);
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

    private function renderPanels($resource): string
    {
        $registry = self::REGISTRY;

        // The registry is normally primed during plugin configuration; prime it here
        // too so this works regardless of load order.
        if (method_exists($registry, 'init') && !$registry::getAllPanels()) {
            $registry::init();
        }

        // For the panel templates, not for this class: they call __() and
        // get_partial() freely, and outside a theme nothing else has loaded these.
        \sfContext::getInstance()->getConfiguration()->loadHelpers(['Partial', 'I18N']);

        $out = '';
        foreach (self::POSITIONS as $position) {
            foreach ($registry::getPanelsForContext('informationobject', $position, $resource) as $panel) {
                $rendered = (string) $registry::renderPanel($panel, $resource);
                if ('' === trim($rendered)) {
                    continue;   // the panel had nothing to say for this record
                }

                $id = htmlspecialchars((string) ($panel['id'] ?? 'plugin'), ENT_QUOTES);

                // No heading is emitted here. Panels are self-titling by contract:
                // ahgThemeB5Plugin's renderer (modules/sfIsadPlugin/templates/
                // indexSuccess.php) wraps the panel and nothing more. Rendering the
                // extension.json label as well gave the Collections Procedures panel
                // two stacked headings, and made every panel look different with and
                // without the theme installed.
                $out .= '<div class="ahg-display-panel" id="panel-' . $id . '">'
                      . $rendered . '</div>';
            }
        }

        return $out;
    }

    /**
     * Placed above the digital object metadata block, matching where the provenance
     * panel sits, so plugin panels appear as a consistent group.
     */
    private function place(string $content, string $block): string
    {
        foreach (['<div class="digitalObjectMetadata"', '<div class="ahg-iiif-viewer', '<div class="digital-object-reference'] as $anchor) {
            $at = stripos($content, $anchor);
            if (false !== $at) {
                return substr_replace($content, $block . "\n", $at, 0);
            }
        }

        $pos = strripos($content, '</body>');

        return false === $pos ? $content . $block : substr_replace($content, $block . "\n", $pos, 0);
    }
}
