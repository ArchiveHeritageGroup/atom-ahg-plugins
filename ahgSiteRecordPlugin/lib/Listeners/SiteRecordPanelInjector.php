<?php

declare(strict_types=1);

namespace AhgSiteRecordPlugin\Listeners;

/**
 * Puts the site record panel on an authority record page without depending on a
 * theme or on ahgDisplayPlugin.
 *
 * Why this exists: extension.json declares the panel through `display_panels`, but
 * something has to collect and render that - either an AHG theme template or
 * ahgDisplayPlugin's PanelInjector. On a stock AtoM install with neither (which is
 * exactly what the RARI development instance is), a site record can be stored and
 * is never displayed. Measured, not assumed: the panel rendered nowhere on
 * rari-dev until this listener existed.
 *
 * That also matters for selling the plugin on its own - it should not need two
 * other plugins to show what it captures.
 *
 * Mirrors ahgProvenancePlugin's ProvenanceInjector, which solves the same problem
 * for description pages.
 */
class SiteRecordPanelInjector
{
    /**
     * An authority record is not served by an 'actor' module - AtoM forwards
     * /{slug} to the module for the record's descriptive standard. Checking only
     * for 'actor' means this silently never fires.
     */
    private const VIEW_MODULES = ['actor', 'sfIsaarPlugin'];

    /** response.filter_content can fire more than once per request. */
    private static bool $injected = false;

    public static function filter(\sfEvent $event, $content)
    {
        try {
            return (new self())->inject($event, $content);
        } catch (\Throwable $e) {
            // A display panel is an enhancement. It must never take a record page
            // down - and it must never fail open into showing raw locality either,
            // which is why the failure path returns the content untouched.
            return $content;
        }
    }

    /**
     * Whether something else is already rendering display panels.
     *
     * With an AHG theme or ahgDisplayPlugin ENABLED, the panel is collected from
     * extension.json and injecting here would render it twice.
     *
     * Enabled, not merely present on disk. This first checked is_dir(), which is
     * wrong in a way that fails silently: PSIS has the ahgThemeB5Plugin directory
     * but the plugin is disabled, so on an instance where the directory exists
     * and neither renderer is enabled, this listener would stand down and nobody
     * would draw the panel - invisible, with no error to explain it.
     */
    private function panelRenderedElsewhere(): bool
    {
        $renderers = ['ahgThemeB5Plugin', 'ahgDisplayPlugin'];

        // The active configuration lists enabled plugins only.
        if (class_exists('\sfProjectConfiguration')) {
            $active = \sfProjectConfiguration::getActive();

            if ($active && method_exists($active, 'getPlugins')) {
                $enabled = (array) $active->getPlugins();

                foreach ($renderers as $renderer) {
                    if (in_array($renderer, $enabled, true)) {
                        return true;
                    }
                }

                return false;
            }
        }

        // No configuration to ask - fall back to presence on disk, which at worst
        // suppresses this listener rather than double-rendering the panel.
        $pluginsDir = (string) \sfConfig::get('sf_plugins_dir');

        foreach ($renderers as $renderer) {
            if (is_dir($pluginsDir.'/'.$renderer)) {
                return true;
            }
        }

        return false;
    }

    private function inject(\sfEvent $event, $content)
    {
        if (self::$injected || $this->panelRenderedElsewhere()) {
            return $content;
        }

        if (false !== stripos((string) $content, 'ahg-site-record-panel')) {
            return $content;
        }

        $response = $event->getSubject();

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

        $html = $this->render($resource);

        if ('' === $html) {
            return $content;
        }

        self::$injected = true;

        // Before the closing of the main column, which stock AtoM and the
        // Dominion themes both emit.
        $anchor = '</section>';
        $pos = strripos($content, $anchor);

        if (false === $pos) {
            return $content.$html;
        }

        return substr($content, 0, $pos).$html.substr($content, $pos);
    }

    /**
     * Render the panel partial, or '' when this authority record is not a site.
     *
     * The partial resolves locality through SiteRecordService, so this method
     * never handles a coordinate itself.
     */
    private function render($resource): string
    {
        $template = __DIR__.'/../../templates/display/_site_record_panel.php';

        if (!file_exists($template)) {
            return '';
        }

        // The partial is written for the view layer, so it uses esc_specialchars().
        // Including it from a response filter runs outside that layer, where the
        // helper is not loaded - without this the panel fatals on its first
        // escaped value and the whole page is lost.
        if (!function_exists('esc_specialchars')) {
            \sfContext::getInstance()->getConfiguration()->loadHelpers(['Escaping']);
        }

        ob_start();

        try {
            include $template;
        } catch (\Throwable $e) {
            ob_end_clean();

            return '';
        }

        return (string) ob_get_clean();
    }

    private function isHtmlGet($response): bool
    {
        if (!$response || !method_exists($response, 'getContentType')) {
            return false;
        }

        if (false === stripos((string) $response->getContentType(), 'html')) {
            return false;
        }

        return 'GET' === ($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }
}
