<?php

/**
 * GET /session/status - whether the caller is signed in, and how long an idle
 * session lasts. Used by web/js/session-guard.js (#207).
 *
 * Calling it is itself activity: sfBasicSecurityUser stamps the request time
 * on every request, so "Stay signed in" is just a call to this action. It is
 * therefore only called on a user's action, never on a timer.
 */
class sessionStatusActions extends sfActions
{
    public function executeIndex(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');
        $this->getResponse()->setHttpHeader('Cache-Control', 'no-store');

        return $this->renderText(json_encode([
            'authenticated' => $this->getUser()->isAuthenticated(),
            'timeout' => self::effectiveTimeout($this->getUser()),
        ]));
    }

    /**
     * Seconds an idle session survives: the shorter of AtoM's user timeout
     * (config/factories.yml) and php.ini's session.gc_maxlifetime. Symfony raises
     * the latter at runtime, but Ubuntu's session cleaner reads php.ini, so the
     * session file can be deleted before AtoM's own timeout is reached.
     */
    public static function effectiveTimeout($user): int
    {
        $options = method_exists($user, 'getOptions') ? (array) $user->getOptions() : [];
        $timeout = isset($options['timeout']) && false !== $options['timeout'] ? (int) $options['timeout'] : 1800;
        $gc = (int) get_cfg_var('session.gc_maxlifetime');

        return $gc > 0 ? min($timeout, $gc) : $timeout;
    }
}
