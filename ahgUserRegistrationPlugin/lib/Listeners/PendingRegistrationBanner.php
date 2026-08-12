<?php

namespace AhgUserRegistration\Listeners;

/**
 * Tell an administrator, on every page, that a registration is waiting.
 *
 * WHY A BANNER AND NOT JUST A MENU ENTRY
 *
 * The plugin also registers an AhgNav entry carrying a count, and that entry is
 * correct and stays. But it renders inside the "Quick links" dropdown, which is
 * closed until somebody opens it - so it only informs an administrator who had
 * already thought to go looking. That is a destination, not a notification. A
 * self-service registration is a request from a person who is now waiting, and
 * the failure mode is nobody noticing for a week.
 *
 * ahgThemeB5Plugin solves this with a banner (templates/_adminNotifications.php),
 * which is why PSIS surfaces it and a themeless instance did not. The theme gets
 * there by querying ahg_registration_request itself. That coupling is the reason
 * the notice existed on exactly one deployment: any instance without that theme
 * had no notice at all.
 *
 * WHY INJECTED
 *
 * The layout is base AtoM (apps/qubit/templates/), not ours to edit, and there is
 * no slot to extend. response.filter_content is the sanctioned route - the same
 * one ViewerInjector, CompareInjector and LoginRegisterLinkInjector use.
 *
 * NO INLINE STYLES
 *
 * Bootstrap 5 classes only. A CSP nonce covers <style> and <script> elements but
 * never a style="" attribute, so an inline style would simply be dropped and the
 * banner would render unstyled on exactly the instances that are configured
 * correctly.
 */
class PendingRegistrationBanner
{
    private const MARKER = 'ahg-pending-registrations';

    public static function filter(\sfEvent $event, $content)
    {
        try {
            $html = (string) $content;

            // response.filter_content can fire more than once per request.
            if (false !== strpos($html, self::MARKER)) {
                return $content;
            }

            $response = $event->getSubject();

            if (!$response instanceof \sfWebResponse) {
                return $content;
            }

            if (false === stripos((string) $response->getContentType(), 'html')) {
                return $content;
            }

            if (!\sfContext::hasInstance()) {
                return $content;
            }

            $context = \sfContext::getInstance();
            $request = $context->getRequest();

            if ('GET' !== strtoupper($request->getMethod())) {
                return $content;
            }

            $user = $context->getUser();

            if (!$user->isAuthenticated() || !$user->hasCredential('administrator')) {
                return $content;
            }

            // Not on the page that lists them. An administrator already looking at
            // the queue does not need to be told the queue exists.
            if ('userRegistration' === $context->getModuleName()) {
                return $content;
            }

            $count = self::awaitingReview();

            if ($count < 1) {
                return $content;
            }

            $url = self::routeUrl('admin_registrations');

            if (null === $url) {
                return $content;
            }

            return self::insertAfterHeader($html, self::banner($count, $url));
        } catch (\Throwable $e) {
            // A notice is an enhancement. It must never take a page down - and
            // this one renders on every page, so a fault here would take the
            // whole site with it.
            return $content;
        }
    }

    /**
     * Requests an administrator still has to act on.
     *
     * Both states count. "pending" is awaiting the applicant's email
     * confirmation and "verified" is awaiting approval, but an administrator can
     * act on either - the queue offers a manual verify - and a request stuck
     * unconfirmed because the mail never arrived is precisely the one somebody
     * needs to see.
     */
    private static function awaitingReview(): int
    {
        return (int) \Illuminate\Database\Capsule\Manager::table('ahg_registration_request')
            ->whereIn('status', ['pending', 'verified'])
            ->count();
    }

    private static function banner(int $count, string $url): string
    {
        $text = 1 === $count
            ? self::t('A registration request is waiting for review.')
            : sprintf(self::t('%d registration requests are waiting for review.'), $count);

        return sprintf(
            '<div class="%s container-xl mt-2">'
            .'<div class="alert alert-info d-flex align-items-center justify-content-between mb-0" role="status">'
            .'<span><i class="fas fa-user-check me-2" aria-hidden="true"></i>%s</span>'
            .'<a class="btn btn-sm btn-primary ms-3" href="%s">%s</a>'
            .'</div></div>',
            self::MARKER,
            htmlspecialchars($text, ENT_QUOTES),
            htmlspecialchars($url, ENT_QUOTES),
            htmlspecialchars(self::t('Review'), ENT_QUOTES)
        );
    }

    /**
     * Immediately below the header, above the page's own content.
     *
     * Anchored on </header> rather than <body>, so it sits inside the normal page
     * flow instead of above the navigation bar.
     */
    private static function insertAfterHeader(string $html, string $block): string
    {
        $pos = stripos($html, '</header>');

        if (false === $pos) {
            return $html;
        }

        $at = $pos + strlen('</header>');

        return substr($html, 0, $at).$block.substr($html, $at);
    }

    /**
     * The URL for a named route, or null if this instance does not have it.
     */
    private static function routeUrl(string $route): ?string
    {
        try {
            $routing = \sfContext::getInstance()->getRouting();

            if (!$routing->hasRouteName($route)) {
                return null;
            }

            return $routing->generate($route);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function t(string $text): string
    {
        try {
            return \sfContext::getInstance()->getI18N()->__($text);
        } catch (\Throwable $e) {
            return $text;
        }
    }
}
