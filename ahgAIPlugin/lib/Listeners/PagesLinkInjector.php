<?php

declare(strict_types=1);

namespace AhgAIPlugin\Listeners;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Adds "Pages: type and privacy" to the AI Tools list on a description page
 * whose master file is a PDF (catalogue#320). The list is drawn by the locked
 * theme, so the item is added by filtering the response.
 */
class PagesLinkInjector
{
    private const VIEW_MODULES = ['informationobject', 'sfIsadPlugin', 'sfRadPlugin', 'sfDcPlugin', 'sfModsPlugin', 'sfDacsPlugin'];

    private const LIST = '/(<h2 class="h4">AI Tools<\/h2>\s*<ul class="list-unstyled">)/';

    public static function filter(\sfEvent $event, $content)
    {
        try {
            $content = (string) $content;
            if (false === strpos($content, 'AI Tools') || false !== strpos($content, 'ahg-pages-link')
                || false === stripos((string) $event->getSubject()->getContentType(), 'html')) {
                return $content;
            }
            $context = \sfContext::getInstance();
            if (!in_array($context->getModuleName(), self::VIEW_MODULES, true) || 'index' !== $context->getActionName()) {
                return $content;
            }
            $user = $context->getUser();
            if (!$user->isAuthenticated() || !($user->isAdministrator() || $user->hasCredential('editor'))) {
                return $content;
            }
            $resource = $context->getActionStack()->getLastEntry()->getActionInstance()->resource ?? null;
            if (!$resource || empty($resource->slug)) {
                return $content;
            }
            $hasPdf = DB::table('digital_object')->where('object_id', (int) $resource->id)->whereNull('parent_id')
                ->where('mime_type', 'application/pdf')->exists();
            if (!$hasPdf) {
                return $content;
            }
            $context->getConfiguration()->loadHelpers(['I18N']);
            $href = htmlspecialchars($context->getRouting()->generate('ahg_ai_pages', ['slug' => $resource->slug]), ENT_QUOTES, 'UTF-8');
            $item = '<li class="ahg-pages-link"><a href="' . $href . '"><i class="fas fa-layer-group me-1"></i>' . htmlspecialchars(__('Pages: type and privacy'), ENT_QUOTES, 'UTF-8') . '</a></li>';

            return preg_replace(self::LIST, '$1' . str_replace(['\\', '$'], ['\\\\', '\\$'], $item), $content, 1);
        } catch (\Throwable $e) {
            // An extra menu item must never take a record page down.
            return $content;
        }
    }
}
