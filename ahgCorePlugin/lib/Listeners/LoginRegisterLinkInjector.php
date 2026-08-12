<?php

namespace AhgCore\Listeners;

/**
 * A way to reach registration from the login screen.
 *
 * Registration has existed and worked for as long as the plugins have, and
 * nothing linked to it from the one page where somebody without an account
 * inevitably lands. They arrive at the login form, find no way forward, and
 * stop - the feature was reachable only by someone who already knew the URL.
 *
 * WHY IN ahgCorePlugin
 *
 * It first lived in ahgResearchPlugin, which was wrong: it is not about
 * researchers. It offers whichever registrations an instance has, and an
 * instance may well have the ordinary-account one and not the researcher one -
 * in which case the link would have vanished with the plugin that had no part in
 * it. ahgCorePlugin is present wherever any AHG plugin is, which is exactly the
 * condition under which this should work.
 *
 * WHY INJECTED
 *
 * The login screen is base AtoM (apps/qubit/modules/user/templates/), which is
 * not ours to edit, and there is no slot or partial to extend. A
 * response.filter_content listener is the sanctioned route - the same one
 * ViewerInjector, ProvenanceInjector and CompareInjector use.
 *
 * BOTH LOGIN SURFACES, NOT JUST THE LOGIN PAGE
 *
 * There are two forms: the header dropdown, which AtoM renders on every page for
 * an anonymous visitor, and the one in main-column on /user/login.
 *
 * The first version of this put the link only on the login page, reasoning that
 * the header form is global chrome and this plugin should not write into it.
 * That reasoning was wrong in practice: almost nobody navigates to /user/login.
 * They click "Log in" in the header, get the dropdown, and never see the page the
 * link was on - which is exactly how it was reported, twice, as missing.
 *
 * So it goes on both. The header is where people actually sign in, and an archive
 * that wants researchers to register has every reason to say so there.
 */
class LoginRegisterLinkInjector
{
    private const MARKER = 'ahg-register-link';

    public static function filter(\sfEvent $event, $content)
    {
        try {
            $html = (string) $content;

            // Once per response.
            if (false !== strpos($html, self::MARKER)) {
                return $content;
            }

            $response = $event->getSubject();

            if (!$response instanceof \sfWebResponse) {
                return $content;
            }

            $context = \sfContext::getInstance();

            // GET only. A failed POST re-renders the login action, and a
            // registration link beside an "invalid password" message reads as an
            // instruction to give up rather than try again.
            if ('GET' !== strtoupper($context->getRequest()->getMethod())) {
                return $content;
            }

            if (false === stripos((string) $response->getContentType(), 'html')) {
                return $content;
            }

            // Someone already signed in has no use for it.
            if ($context->getUser()->isAuthenticated()) {
                return $content;
            }

            // Offer whichever registrations this instance actually has.
            //
            // There are two, owned by different plugins and answering different
            // questions: ahgUserRegistrationPlugin's /register creates an ordinary
            // account, and this plugin's /research/register-researcher creates an
            // account AND a researcher record with reading-room entitlements.
            //
            // Hardcoding the researcher link, as the first version did, told a
            // visitor who only wanted an account that the sole way in was to
            // apply as a researcher. Which route exists is a deployment fact, so
            // it is read from the routing table rather than assumed - a route
            // that is not registered is simply not offered, and an instance with
            // neither gets no block at all.
            $links = [];

            foreach ([
                ['user_register', 'Create an account'],
                ['research_public_register', 'Register as a researcher'],
            ] as [$route, $label]) {
                if (null !== ($url = self::routeUrl($route))) {
                    $links[] = sprintf(
                        '<a href="%s">%s</a>',
                        htmlspecialchars($url, ENT_QUOTES),
                        htmlspecialchars(self::t($label), ENT_QUOTES)
                    );
                }
            }

            if ([] === $links) {
                return $content;
            }

            $block = sprintf(
                '<p class="%s mt-3 mb-0 small">%s %s</p>',
                self::MARKER,
                htmlspecialchars(self::t('No account?'), ENT_QUOTES),
                implode(' &middot; ', $links)
            );

            return self::appendToLoginForms($html, $block);
        } catch (\Throwable $e) {
            // A link is an enhancement. It must never take the login page down -
            // which, on a site where nobody can then sign in, would be the worst
            // page to lose.
            return $content;
        }
    }

    /**
     * The URL for a named route, or null if this instance does not have it.
     *
     * Never throws: url_for()/generate() raise on an unknown route, and a throw
     * from a response filter would take down the page it is decorating - here,
     * the login page, which is the worst one to lose.
     */
    private static function routeUrl(string $name): ?string
    {
        try {
            $routing = \sfContext::getInstance()->getRouting();

            if (!$routing->hasRouteName($name)) {
                return null;
            }

            return $routing->generate($name);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Append the block after every form that carries a password field.
     *
     * Both surfaces get it: the header dropdown, which is on every page, and the
     * main-column form on /user/login. Anchoring on the password input rather
     * than on a container class means this does not depend on the theme's markup
     * - the stock theme and ahgThemeB5Plugin lay the page out differently, and
     * both render a form with a password in it.
     */
    private static function appendToLoginForms(string $html, string $block): string
    {
        $out = '';
        $offset = 0;

        while (true) {
            $end = strpos($html, '</form>', $offset);

            if (false === $end) {
                break;
            }

            $start = strrpos(substr($html, 0, $end), '<form');
            $form = false === $start ? '' : substr($html, $start, $end - $start);

            $out .= substr($html, $offset, $end - $offset + strlen('</form>'));

            if (false !== stripos($form, 'name="password"')) {
                $out .= $block;
            }

            $offset = $end + strlen('</form>');
        }

        return $out.substr($html, $offset);
    }

    private static function t(string $s): string
    {
        return function_exists('__') ? __($s) : $s;
    }
}
