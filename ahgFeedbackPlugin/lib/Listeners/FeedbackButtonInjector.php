<?php

declare(strict_types=1);

namespace AhgFeedbackPlugin\Listeners;

use AtomFramework\Views\RecordActionBar;

/**
 * Puts the item feedback button on an archival description page.
 *
 * The plugin has always had the route - /informationobject/:slug/feedback - but
 * nothing linked to it except six theme and sector templates that each hardcode
 * the same anchor. On a stock AtoM install the action was reachable only by typing
 * the URL, which is why item feedback appeared to be missing rather than broken.
 *
 * The site-wide feedback form is a different entry point and is not handled here:
 * it is a menu row under staticPagesMenu, contributed by extension.json, because
 * AtoM already renders that menu from the database on every page.
 */
class FeedbackButtonInjector
{
    public static function filter(\sfEvent $event, $content)
    {
        return RecordActionBar::render(
            $event,
            $content,
            'ahg-feedback-btn',
            static function (string $slug): string {
                $url = \url_for(['module' => 'feedback', 'action' => 'submit', 'slug' => $slug]);

                return '<a class="btn btn-sm btn-outline-secondary ahg-feedback-btn"'
                    .' href="'.htmlspecialchars($url, ENT_QUOTES).'"'
                    .' title="'.htmlspecialchars(__('Item feedback'), ENT_QUOTES).'"'
                    .' data-bs-toggle="tooltip">'
                    .'<i class="fas fa-comment" aria-hidden="true"></i>'
                    .'<span class="visually-hidden">'.htmlspecialchars(__('Item feedback')).'</span>'
                    .'</a>';
            }
        );
    }
}
