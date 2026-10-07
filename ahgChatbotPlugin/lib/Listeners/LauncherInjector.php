<?php

namespace AhgChatbotPlugin\Listeners;

use AtomExtensions\Services\AhgSettingsService;

/**
 * Puts the "Ask the archive" launcher on every HTML page.
 *
 * Injected from the plugin rather than a theme template, so it exists exactly
 * while the plugin is enabled and works with or without the AHG theme.
 * CSP-clean: configuration travels in data- attributes, the script carries the
 * request nonce, and there is no inline style or handler.
 */
class LauncherInjector
{
    /** response.filter_content can fire more than once per request. */
    private static bool $done = false;

    public static function filter(\sfEvent $event, $content)
    {
        try {
            return self::inject($event->getSubject(), (string) $content);
        } catch (\Throwable $e) {
            // The launcher is an extra. It must never take a page down.
            return $content;
        }
    }

    private static function inject($response, string $content): string
    {
        if (self::$done
            || !$response instanceof \sfWebResponse
            || 'GET' !== ($_SERVER['REQUEST_METHOD'] ?? 'GET')
            || false === stripos((string) $response->getContentType(), 'text/html')
            || false === stripos($content, '</body>')
            || false !== strpos($content, 'id="ahg-chatbot"')) {
            return $content;
        }

        if (!AhgSettingsService::getBool('chatbot_enabled', true)) {
            return $content;
        }
        $context = \sfContext::getInstance();
        if (!AhgSettingsService::getBool('chatbot_public', true) && !$context->getUser()->isAuthenticated()) {
            return $content;
        }
        // Not on its own admin page or inside edit forms, where it only gets in the way.
        if ('chatbot' === $context->getModuleName() || in_array($context->getActionName(), ['edit', 'add', 'create'], true)) {
            return $content;
        }

        self::$done = true;

        $e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
        $nonce = (string) \sfConfig::get('csp_nonce', '');
        $nonceAttr = $nonce ? ' '.preg_replace('/^nonce=/', 'nonce="', $nonce).'"' : '';
        $base = '/plugins/ahgChatbotPlugin/web';
        $ver = \ahgChatbotPluginConfiguration::$version;

        $block = '<link rel="stylesheet" href="'.$base.'/css/chatbot.css?v='.$ver.'">'
            .'<div id="ahg-chatbot"'
            .' data-ask-url="'.$e($context->getController()->genUrl('chatbot/ask')).'"'
            .' data-feedback-url="'.$e($context->getController()->genUrl('chatbot/feedback')).'"'
            .' data-label="'.$e(AhgSettingsService::get('chatbot_button_label', 'Ask the archive')).'"'
            .' data-notice="'.$e(AhgSettingsService::get('chatbot_notice', '')).'"'
            .' data-page-slug="'.$e(self::pageSlug($context)).'"'
            .' data-help="'.(self::helpAvailable() ? '1' : '0').'"'
            .'></div>'
            .'<script src="'.$base.'/js/chatbot.js?v='.$ver.'"'.$nonceAttr.' defer></script>';

        $pos = strripos($content, '</body>');

        return substr_replace($content, $block."\n", $pos, 0);
    }

    /** The "Help using the site" tab needs ahgHelpPlugin's articles. */
    private static function helpAvailable(): bool
    {
        require_once __DIR__.'/../Services/ChatbotHelp.php';

        return \AhgChatbotPlugin\Services\ChatbotHelp::available();
    }

    /**
     * Slug of the page being viewed, so questions can be about "this". Any view
     * page qualifies; the retriever keeps it only if it is a published,
     * unrestricted description (actors and terms have no publication status,
     * so they drop out there).
     */
    private static function pageSlug(\sfContext $context): string
    {
        return 'index' === $context->getActionName() ? (string) $context->getRequest()->getParameter('slug', '') : '';
    }
}
