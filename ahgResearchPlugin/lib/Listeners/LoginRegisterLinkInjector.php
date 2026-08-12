<?php

namespace AhgResearch\Listeners;

/**
 * A way to reach researcher registration from the login screen.
 *
 * /research/register-researcher has existed and worked for as long as the plugin
 * has, and nothing linked to it from the one page where somebody without an
 * account inevitably lands. A researcher who has never registered arrives at the
 * login form, finds no way forward, and stops - the feature was reachable only
 * by someone who already knew the URL.
 *
 * Same shape as the IIIF comparison workspace: a complete capability with no
 * door into it.
 *
 * WHY INJECTED
 *
 * The login screen is base AtoM (apps/qubit/modules/user/templates/), which is
 * not ours to edit, and there is no slot or partial to extend. A
 * response.filter_content listener is the sanctioned route - the same one
 * ViewerInjector, ProvenanceInjector and CompareInjector use.
 *
 * The page carries TWO login forms: one in the header dropdown, one in
 * main-column. The link belongs on the second - the header form appears on every
 * page of the site, and hanging a registration invitation off it would put this
 * plugin's link into AtoM's global chrome.
 */
class LoginRegisterLinkInjector
{
    private const MARKER = 'ahg-research-register-link';

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

            // The login screen only, and only a GET of it: a failed POST
            // re-renders the same action, and a registration link beside an
            // "invalid password" message reads as an instruction to give up.
            if ('user' !== $context->getModuleName() || 'login' !== $context->getActionName()) {
                return $content;
            }

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

            // Registration has to be reachable, or the link is a dead end. The
            // action is public by security.yml; this checks the route resolves,
            // because url_for() on an unknown route throws - and a throw here
            // would take down the login page itself.
            $url = self::registerUrl();

            if (null === $url) {
                return $content;
            }

            $block = sprintf(
                '<p class="%s mt-3 mb-0 small">%s <a href="%s">%s</a></p>',
                self::MARKER,
                htmlspecialchars(self::t('No account?'), ENT_QUOTES),
                htmlspecialchars($url, ENT_QUOTES),
                htmlspecialchars(self::t('Register as a researcher'), ENT_QUOTES)
            );

            return self::appendToMainLoginForm($html, $block);
        } catch (\Throwable $e) {
            // A link is an enhancement. It must never take the login page down -
            // which, on a site where nobody can then sign in, would be the worst
            // page to lose.
            return $content;
        }
    }

    private static function registerUrl(): ?string
    {
        try {
            $routing = \sfContext::getInstance()->getRouting();

            if (!$routing->hasRouteName('research_public_register')) {
                return null;
            }

            return $routing->generate('research_public_register');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Append after the login form inside main-column, not the header one.
     *
     * Anchored on main-column rather than on the first </form>, because the first
     * is the header dropdown that appears site-wide.
     */
    private static function appendToMainLoginForm(string $html, string $block): string
    {
        $anchor = strpos($html, 'id="main-column"');

        if (false === $anchor) {
            return $html;
        }

        $formEnd = strpos($html, '</form>', $anchor);

        if (false === $formEnd) {
            return $html;
        }

        $at = $formEnd + strlen('</form>');

        return substr($html, 0, $at).$block.substr($html, $at);
    }

    private static function t(string $s): string
    {
        return function_exists('__') ? __($s) : $s;
    }
}
