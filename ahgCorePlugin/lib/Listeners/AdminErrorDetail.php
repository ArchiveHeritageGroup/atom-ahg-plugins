<?php

namespace AhgCore\Listeners;

/**
 * Show an administrator what actually broke.
 *
 * The stock page says "Oops! An Error Occurred" and "The server returned a 500
 * Internal Server Error" and nothing else - by design, because it is also what a
 * member of the public sees. For whoever has to fix it that is the same sentence
 * every time, so every report starts with a round trip to the server to find out
 * which of thousands of lines it was. The audit-filter timeout, the registration
 * duplicate-key and the AhgNav fatal all presented identically.
 *
 * Administrators now get the exception class, message, file, line and trace on
 * the page itself. Everyone else sees exactly what they saw before.
 *
 * WHY THIS HOOK
 *
 * sfException::printStackTrace() renders vendor/symfony/lib/exception/data/
 * error.html.php, which is base AtoM and not ours to touch. Immediately before
 * rendering it does:
 *
 *     $event = $dispatcher->notifyUntil(new sfEvent($e, 'application.throw_exception'));
 *     if ($event->isProcessed()) { return; }
 *
 * so a listener that returns true takes over the response and symfony renders
 * nothing. That is a documented extension point, not a workaround.
 *
 * SAFETY
 *
 * This runs while the request is already failing, which is the worst possible
 * place for a second fault: anything thrown here would replace a bad error page
 * with a blank one. Every step is guarded, and any doubt at all - not an
 * administrator, no context, an exception while deciding - returns false and
 * lets symfony render the page it always did.
 */
class AdminErrorDetail
{
    public static function handle(\sfEvent $event)
    {
        try {
            if (!self::isAdministrator()) {
                return false;
            }

            $exception = $event->getSubject();

            if (!$exception instanceof \Throwable) {
                return false;
            }

            self::render($exception);

            // Tells notifyUntil the event is handled, so symfony returns without
            // rendering its own page over the top of this one.
            return true;
        } catch (\Throwable $ignored) {
            // Fall back to the stock page. A worse error page is still a page.
            return false;
        }
    }

    /**
     * Whether the current user is an administrator.
     *
     * Deliberately strict. A stack trace names internal paths and can carry
     * fragments of a query, so anything short of a confirmed administrator gets
     * the generic page. The context may itself be half-built when a request dies
     * early, hence the guards.
     */
    private static function isAdministrator(): bool
    {
        if (!class_exists('\sfContext', false) || !\sfContext::hasInstance()) {
            return false;
        }

        $user = \sfContext::getInstance()->getUser();

        if (!$user || !method_exists($user, 'isAuthenticated') || !$user->isAuthenticated()) {
            return false;
        }

        return method_exists($user, 'hasCredential') && $user->hasCredential('administrator');
    }

    private static function render(\Throwable $e): void
    {
        if (!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-Type: text/html; charset=utf-8');
        }

        $rows = [
            'Type' => get_class($e),
            'Message' => $e->getMessage(),
            'File' => $e->getFile().':'.$e->getLine(),
        ];

        if (\sfContext::hasInstance()) {
            try {
                $request = \sfContext::getInstance()->getRequest();
                $rows['URL'] = $request->getMethod().' '.$request->getUri();
            } catch (\Throwable $ignored) {
            }
        }

        // A previous exception is usually the real cause - the outer one is often
        // just the wrapper that reached the surface.
        if (null !== ($previous = $e->getPrevious())) {
            $rows['Caused by'] = get_class($previous).': '.$previous->getMessage()
                .' ('.$previous->getFile().':'.$previous->getLine().')';
        }

        $esc = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

        $detail = '';

        foreach ($rows as $label => $value) {
            $detail .= '<tr><th>'.$esc($label).'</th><td>'.$esc($value).'</td></tr>';
        }

        echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
            .'<meta name="robots" content="noindex, nofollow">'
            .'<title>Error - AtoM</title><style>'
            .'body{font:14px/1.5 -apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;margin:0;padding:2rem;'
            .'background:#f5f5f5;color:#212529}'
            .'.w{max-width:1100px;margin:0 auto;background:#fff;border:1px solid #dee2e6;border-radius:.4rem;'
            .'overflow:hidden}'
            .'h1{margin:0;padding:1rem 1.25rem;background:#842029;color:#fff;font-size:1.25rem}'
            .'.n{padding:.75rem 1.25rem;background:#fff3cd;border-bottom:1px solid #ffecb5;font-size:.875rem}'
            .'table{width:100%;border-collapse:collapse}'
            .'th,td{padding:.6rem 1.25rem;border-bottom:1px solid #eee;text-align:left;vertical-align:top}'
            .'th{width:8rem;color:#6c757d;font-weight:600;white-space:nowrap}'
            .'td{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;word-break:break-word}'
            .'pre{margin:0;padding:1rem 1.25rem;background:#f8f9fa;border-top:1px solid #dee2e6;'
            .'font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.8125rem;overflow-x:auto}'
            .'a{color:#0d6efd}'
            .'</style></head><body><div class="w">'
            .'<h1>Oops! An Error Occurred</h1>'
            .'<div class="n">You are seeing the detail below because you are signed in as an '
            .'administrator. Other users see only the generic error page.</div>'
            .'<table>'.$detail.'</table>'
            .'<pre>'.$esc($e->getTraceAsString()).'</pre>'
            .'</div></body></html>';
    }
}
