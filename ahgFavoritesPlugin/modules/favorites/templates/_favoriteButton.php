<?php
/**
 * Favorite heart button partial
 *
 * Usage: get_partial('favorites/favoriteButton', ['objectId' => $id, 'slug' => $slug])
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */

$userId = sfContext::getInstance()->getUser()->getAttribute('user_id');
if (!$userId) {
    return; // Not logged in
}

require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FavoritesService.php';
$svc = new \AtomAhgPlugins\ahgFavoritesPlugin\Services\FavoritesService();
$isFav = $svc->isFavorited((int) $userId, (int) $objectId);
$btnId = 'fav-btn-'.uniqid();
?>
<button id="<?php echo $btnId; ?>"
        class="btn btn-sm btn-outline-danger favorite-toggle"
        data-slug="<?php echo esc_entities($slug); ?>"
        data-favorited="<?php echo $isFav ? '1' : '0'; ?>"
        title="<?php echo $isFav ? __('Remove from Favorites') : __('Add to Favorites'); ?>">
    <i class="fa<?php echo $isFav ? 's' : 'r'; ?> fa-heart"></i>
</button>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function() {
    var btn = document.getElementById('<?php echo $btnId; ?>');
    if (!btn) return;

    btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var slug = btn.getAttribute('data-slug');
        var icon = btn.querySelector('i');

        fetch('/favorites/ajax/toggle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'slug=' + encodeURIComponent(slug)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                if (data.favorited) {
                    icon.className = 'fas fa-heart';
                    btn.setAttribute('data-favorited', '1');
                    btn.title = '<?php echo __('Remove from Favorites'); ?>';
                } else {
                    icon.className = 'far fa-heart';
                    btn.setAttribute('data-favorited', '0');
                    btn.title = '<?php echo __('Add to Favorites'); ?>';
                }
            }
        });
    });
})();
</script>
