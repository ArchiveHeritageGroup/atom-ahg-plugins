<?php

declare(strict_types=1);

namespace AhgBackup\Listeners;

/**
 * Contributes the "Backup & Restore" entry to the Manage menu at runtime.
 *
 * Why not a menu row in the database, which is what this plugin did before:
 * a `menu` row is DATA and plugin enablement is CODE, and nothing ties the two
 * together. Disabling the plugin left the entry in the Manage dropdown pointing at a
 * module that no longer loads. Contributing the entry from a listener means it exists
 * only while the plugin is enabled - disable it and the listener never registers, so
 * the entry disappears with no stale row to clean up.
 *
 * The same reasoning applies to any plugin that wants a menu entry; this is the
 * pattern to copy rather than an INSERT in install.sql.
 */
class MenuInjector
{
    /** The Manage dropdown is rendered only for users who may see it. */
    private const ANCHOR = 'aria-labelledby="manage-menu"';

    /** response.filter_content can fire more than once per request. */
    private static bool $injected = false;

    public static function filter(\sfEvent $event, $content)
    {
        try {
            return (new self())->inject($event, $content);
        } catch (\Throwable $e) {
            // A menu entry is an enhancement. It must never take a page down.
            return $content;
        }
    }

    private function inject(\sfEvent $event, $content)
    {
        $response = $event->getSubject();

        if (self::$injected || !$this->isHtml($response)) {
            return $content;
        }

        $content = (string) $content;

        // Already present - either a legacy database row still exists, or a theme
        // renders it. Never emit it twice.
        if (false !== stripos($content, 'node_ahgBackup')) {
            return $content;
        }

        $at = stripos($content, self::ANCHOR);
        if (false === $at) {
            return $content;   // no Manage menu on this page: anonymous, or not admin
        }

        $open = strpos($content, '>', $at);
        if (false === $open) {
            return $content;
        }

        self::$injected = true;

        $context = \sfContext::getInstance();
        $context->getConfiguration()->loadHelpers(['Url', 'I18N']);

        $label = htmlspecialchars(__('Backup & Restore'), ENT_QUOTES);
        $href = htmlspecialchars(url_for(['module' => 'backup', 'action' => 'index']), ENT_QUOTES);

        $item = '<li id="node_ahgBackup">'
              . '<a class="dropdown-item" href="' . $href . '" title="' . $label . '">' . $label . '</a></li>';

        return substr_replace($content, $item, $open + 1, 0);
    }

    private function isHtml($response): bool
    {
        if (!$response instanceof \sfWebResponse) {
            return false;
        }

        return false !== stripos((string) $response->getContentType(), 'text/html');
    }
}
