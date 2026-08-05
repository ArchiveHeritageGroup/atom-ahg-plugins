<?php

declare(strict_types=1);

namespace AhgFavoritesPlugin\Listeners;

use AtomFramework\Views\LandingPanel;

/**
 * The favourites list, on the landing page only.
 *
 * Rendered for signed-in users only: favourites are per user, and the browse
 * action bounces an anonymous visitor to the login form.
 */
class FavoritesLandingLink
{
    public static function filter(\sfEvent $event, $content)
    {
        return LandingPanel::render($event, $content, 'ahg-favorites-link', static function (): string {
            if (!\sfContext::getInstance()->getUser()->isAuthenticated()) {
                return '';
            }

            return LandingPanel::link(
                \url_for(['module' => 'favorites', 'action' => 'browse']),
                __('Favorites'),
                'fas fa-heart',
                'ahg-favorites-link'
            );
        });
    }
}
