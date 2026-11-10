<?php

declare(strict_types=1);

namespace AhgLabelPlugin\Listeners;

/**
 * Adds "Print label" to the Collections Management list on a description page.
 *
 * The label page (/label/{slug}) was reachable only from library items; nothing on
 * an archival description linked to it. The theme's side menu is locked, so the
 * link is added by filtering the response, as ahgWorkflowPlugin does for its block.
 */
class LabelLinkInjector
{
    /** AtoM forwards /{slug} to the module of the record's descriptive standard. */
    private const VIEW_MODULES = ['informationobject', 'sfIsadPlugin', 'sfRadPlugin', 'sfDcPlugin', 'sfModsPlugin', 'sfDacsPlugin'];

    private const LIST = '/(<section class="sidebar-widget ahg-collections-management">\s*<h2[^>]*>.*?<\/h2>\s*<ul class="list-unstyled">)/s';

    private static bool $injected = false;

    public static function filter(\sfEvent $event, $content)
    {
        try {
            return (new self())->inject($event, (string) $content);
        } catch (\Throwable $e) {
            // An extra menu item must never take a record page down.
            return $content;
        }
    }

    private function inject(\sfEvent $event, string $content): string
    {
        if (self::$injected || false === strpos($content, 'ahg-collections-management') || false !== strpos($content, 'ahg-label-link')) {
            return $content;
        }
        if (false === stripos((string) $event->getSubject()->getContentType(), 'html')) {
            return $content;
        }
        $context = \sfContext::getInstance();
        if (!in_array($context->getModuleName(), self::VIEW_MODULES, true) || 'index' !== $context->getActionName()) {
            return $content;
        }
        $user = $context->getUser();
        if (!$user->isAuthenticated() || !($user->isAdministrator() || $user->hasCredential('editor') || $user->hasCredential('contributor'))) {
            return $content;
        }
        $resource = $context->getActionStack()->getLastEntry()->getActionInstance()->resource ?? null;
        if (!$resource || empty($resource->slug) || (int) ($resource->id ?? 0) <= 1) {
            return $content;
        }

        $context->getConfiguration()->loadHelpers(['I18N']);
        $e = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $href = $context->getRouting()->generate('label_index', ['slug' => $resource->slug]);
        $item = '<li class="ahg-label-link"><a href="' . $e($href) . '"><i class="fas fa-barcode fa-fw me-2"></i>' . $e(__('Print label')) . '</a></li>';

        $out = preg_replace(self::LIST, '$1' . str_replace(['\\', '$'], ['\\\\', '\\$'], $item), $content, 1, $n);
        if ($n) {
            self::$injected = true;

            return $out;
        }

        return $content;
    }
}
