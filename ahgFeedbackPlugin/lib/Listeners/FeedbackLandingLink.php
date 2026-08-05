<?php

declare(strict_types=1);

namespace AhgFeedbackPlugin\Listeners;

use AtomFramework\Views\LandingPanel;

/**
 * The site-wide feedback link, on the landing page only.
 *
 * Not a menu row: staticPagesMenu also renders in the record sidebar, so the link
 * followed the visitor onto every archival description, and quickLinks drops any
 * child that fails QubitObject::actionExistsForUrl() - it renders an empty
 * dropdown on the installs checked. The menu table has no landing-page-only node.
 *
 * Item feedback is a different entry point and stays on the record action bar.
 */
class FeedbackLandingLink
{
    public static function filter(\sfEvent $event, $content)
    {
        return LandingPanel::render($event, $content, 'ahg-feedback-link', static function (): string {
            return LandingPanel::link(
                \url_for(['module' => 'feedback', 'action' => 'general']),
                __('Feedback'),
                'fas fa-comment',
                'ahg-feedback-link'
            );
        });
    }
}
