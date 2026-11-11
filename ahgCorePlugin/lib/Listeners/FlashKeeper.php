<?php

namespace AhgCore\Listeners;

/**
 * Keeps flash messages for the next page when a request answers with JSON.
 *
 * Symfony marks every flash for removal at the start of the next request and
 * removes it at the end. Pages fire background JSON requests (/ahgVoice/getSettings,
 * /help/api/context-map, ...) about a second after they load. When a form was
 * submitted before those landed, one of them arrived between the POST and the page
 * it redirects to, counted as "the next request", and removed the message unseen:
 * the site-wide "lost flash" fault. A JSON answer never shows a flash, so it now
 * leaves the flashes for the page that will.
 */
class FlashKeeper
{
    public static function filter(\sfEvent $event, $content)
    {
        try {
            if (false !== stripos((string) $event->getSubject()->getContentType(), 'json') && \sfContext::hasInstance()) {
                \sfContext::getInstance()->getUser()->getAttributeHolder()->removeNamespace('symfony/user/sfUser/flash/remove');
            }
        } catch (\Throwable $e) {
            // Never let a message fix break a response.
        }

        return $content;
    }
}
