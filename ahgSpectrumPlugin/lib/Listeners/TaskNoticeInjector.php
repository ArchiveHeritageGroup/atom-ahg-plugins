<?php

declare(strict_types=1);

namespace AhgSpectrumPlugin\Listeners;

/**
 * Shows a standing notice when the signed-in user has outstanding procedure tasks.
 *
 * Why a listener rather than a flash: flash messages are one-shot, so a task waiting
 * for you would announce itself once and then be invisible until you happened to open
 * My Tasks. This has to persist for as long as the work does.
 *
 * Placed immediately inside #wrapper, above AtoM's own alerts block, so it appears on
 * every page without a template override and regardless of theme.
 */
class TaskNoticeInjector
{
    /** response.filter_content can fire more than once per request. */
    private static bool $injected = false;

    public static function filter(\sfEvent $event, $content)
    {
        try {
            return (new self())->inject($event, (string) $content);
        } catch (\Throwable $e) {
            // A notice is an enhancement. It must never take a page down.
            return $content;
        }
    }

    private function inject(\sfEvent $event, string $content): string
    {
        if (self::$injected || false !== stripos($content, 'ahg-task-notice')) {
            return $content;
        }
        if (!$this->isHtmlGet($event->getSubject())) {
            return $content;
        }

        $context = \sfContext::getInstance();
        $user = $context->getUser();
        if (!$user->isAuthenticated()) {
            return $content;   // the query below is the reason this check comes first
        }

        $userId = $user->getAttribute('user_id');
        if (!$userId) {
            return $content;
        }

        $tasks = \ahgSpectrumNotificationService::getPendingTasks($userId);
        $count = count($tasks);
        if (0 === $count) {
            return $content;   // nothing outstanding: say nothing
        }

        $at = stripos($content, '<div id="wrapper"');
        if (false === $at) {
            return $content;
        }
        $open = strpos($content, '>', $at);
        if (false === $open) {
            return $content;
        }

        $context->getConfiguration()->loadHelpers(['I18N', 'Url']);

        try {
            $href = url_for(['module' => 'spectrum', 'action' => 'myTasks']);
        } catch (\Throwable $e) {
            return $content;
        }

        $message = 1 === $count
            ? __('You have 1 outstanding collections task.')
            : __('You have %1% outstanding collections tasks.', ['%1%' => $count]);

        // Bootstrap utility classes only - AtoM's CSP has no 'unsafe-inline', so an
        // inline style would be dropped and the notice would render unstyled.
        $notice = '<div class="ahg-task-notice alert alert-warning alert-dismissible fade show" role="alert">'
                . '<i class="fas fa-tasks me-2" aria-hidden="true"></i>'
                . htmlspecialchars($message, ENT_QUOTES)
                . ' <a href="'.htmlspecialchars($href, ENT_QUOTES).'" class="alert-link">'
                . htmlspecialchars(__('View my tasks'), ENT_QUOTES).'</a>'
                . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="'
                . htmlspecialchars(__('Close'), ENT_QUOTES).'"></button>'
                . '</div>';

        self::$injected = true;

        return substr_replace($content, $notice, $open + 1, 0);
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
}
