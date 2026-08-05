<?php

declare(strict_types=1);

namespace AhgFavoritesPlugin\Listeners;

use AtomFramework\Views\RecordActionBar;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Puts the add/remove favourite button on an archival description page.
 *
 * The plugin already shipped a _favoriteButton partial, but nothing ever called
 * get_partial() on it - the only working button was the copy hardcoded into
 * ahgThemeB5Plugin's sfIsadPlugin override and the five sector templates. Without
 * one of those themes there was no way to favourite a record, which left the
 * favourites list reachable but permanently empty.
 *
 * Rendered as a link rather than the partial's fetch() toggle so it works with
 * the CSP in place: script-src carries no 'unsafe-inline', and the partial's
 * inline handler needs a nonce that a response filter has no clean way to reach.
 */
class FavoritesButtonInjector
{
    public static function filter(\sfEvent $event, $content)
    {
        return RecordActionBar::render(
            $event,
            $content,
            'ahg-favorite-btn',
            static function (string $slug): string {
                $user = \sfContext::getInstance()->getUser();

                // Favourites are per user, so there is nothing to offer an anonymous
                // visitor - and the add action would bounce them to login.
                if (!$user->isAuthenticated()) {
                    return '';
                }

                $userId = $user->getAttribute('user_id');
                $objectId = RecordActionBar::objectId($slug);

                if (!$userId || !$objectId) {
                    return '';
                }

                $existing = DB::table('favorites')
                    ->where('user_id', $userId)
                    ->where('archival_description_id', $objectId)
                    ->value('id');

                if ($existing) {
                    $url = \url_for(['module' => 'favorites', 'action' => 'remove', 'id' => $existing]);
                    $label = __('Remove from favourites');
                    $icon = 'fas fa-heart-crack';
                    $style = 'btn-danger';
                } else {
                    $url = \url_for(['module' => 'favorites', 'action' => 'add', 'slug' => $slug]);
                    $label = __('Add to favourites');
                    $icon = 'fas fa-heart';
                    $style = 'btn-outline-danger';
                }

                return '<a class="btn btn-sm '.$style.' ahg-favorite-btn"'
                    .' href="'.htmlspecialchars($url, ENT_QUOTES).'"'
                    .' title="'.htmlspecialchars($label, ENT_QUOTES).'"'
                    .' data-bs-toggle="tooltip">'
                    .'<i class="'.$icon.'" aria-hidden="true"></i>'
                    .'<span class="visually-hidden">'.htmlspecialchars($label).'</span>'
                    .'</a>';
            }
        );
    }
}
