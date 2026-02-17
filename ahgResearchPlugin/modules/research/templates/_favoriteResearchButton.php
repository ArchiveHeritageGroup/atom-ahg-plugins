<?php
/**
 * Research Favorite heart button partial with folder picker
 *
 * Usage: include_partial('research/favoriteResearchButton', [
 *     'objectId'   => $item->id,
 *     'objectType' => 'research_journal',  // research_journal|research_collection|research_project|research_bibliography|research_workspace|research_report
 *     'title'      => $item->title,
 *     'url'        => '/research/journal/entry/' . $item->id,
 * ])
 */

// Check if favorites plugin is enabled
try {
    $favEnabled = Illuminate\Database\Capsule\Manager::table('atom_plugin')
        ->where('name', 'ahgFavoritesPlugin')->where('is_enabled', 1)->exists();
} catch (Exception $e) {
    $favEnabled = false;
}
if (!$favEnabled) return;

$userId = sfContext::getInstance()->getUser()->getAttribute('user_id');
if (!$userId) return;

require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FavoritesService.php';
$svc = new \AtomAhgPlugins\ahgFavoritesPlugin\Services\FavoritesService();
$isFav = $svc->isFavoritedCustom((int) $userId, (int) $objectId, $objectType);
$uid = 'rfav-' . uniqid();
?>
<div class="btn-group" id="<?php echo $uid; ?>">
    <button type="button"
            class="btn btn-sm btn-outline-danger favorite-toggle"
            id="<?php echo $uid; ?>-btn"
            data-object-id="<?php echo (int) $objectId; ?>"
            data-object-type="<?php echo htmlspecialchars($objectType); ?>"
            data-title="<?php echo htmlspecialchars($title); ?>"
            data-url="<?php echo htmlspecialchars($url); ?>"
            data-favorited="<?php echo $isFav ? '1' : '0'; ?>"
            title="<?php echo $isFav ? __('Remove from Favorites') : __('Add to Favorites'); ?>">
        <i class="fa<?php echo $isFav ? 's' : 'r'; ?> fa-heart"></i>
        <span class="d-none d-md-inline ms-1"><?php echo $isFav ? __('Favorited') : __('Favorite'); ?></span>
    </button>
    <?php if (!$isFav): ?>
    <button type="button" class="btn btn-sm btn-outline-danger dropdown-toggle dropdown-toggle-split"
            id="<?php echo $uid; ?>-dd"
            data-bs-toggle="dropdown" aria-expanded="false"
            title="<?php echo __('Choose folder'); ?>">
        <span class="visually-hidden"><?php echo __('Choose folder'); ?></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end" id="<?php echo $uid; ?>-menu" style="min-width:200px;">
        <li><h6 class="dropdown-header"><i class="fas fa-folder me-1"></i><?php echo __('Add to folder'); ?></h6></li>
        <li><hr class="dropdown-divider"></li>
        <li class="px-3 py-1 text-muted small"><?php echo __('Loading folders...'); ?></li>
    </ul>
    <?php endif; ?>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function() {
    var wrap = document.getElementById('<?php echo $uid; ?>');
    var btn = document.getElementById('<?php echo $uid; ?>-btn');
    var ddBtn = document.getElementById('<?php echo $uid; ?>-dd');
    var menu = document.getElementById('<?php echo $uid; ?>-menu');
    if (!btn) return;

    var foldersLoaded = false;

    function toggleFav(folderId) {
        var body = 'object_id=' + encodeURIComponent(btn.dataset.objectId) +
                   '&object_type=' + encodeURIComponent(btn.dataset.objectType) +
                   '&title=' + encodeURIComponent(btn.dataset.title) +
                   '&url=' + encodeURIComponent(btn.dataset.url);
        if (folderId) body += '&folder_id=' + encodeURIComponent(folderId);

        fetch('/favorites/ajax/toggle-custom', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: body
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) return;
            var icon = btn.querySelector('i');
            var label = btn.querySelector('span');
            if (data.favorited) {
                icon.className = 'fas fa-heart';
                btn.dataset.favorited = '1';
                btn.title = '<?php echo __("Remove from Favorites"); ?>';
                if (label) label.textContent = '<?php echo __("Favorited"); ?>';
                // Hide dropdown split button once favorited
                if (ddBtn) ddBtn.style.display = 'none';
            } else {
                icon.className = 'far fa-heart';
                btn.dataset.favorited = '0';
                btn.title = '<?php echo __("Add to Favorites"); ?>';
                if (label) label.textContent = '<?php echo __("Favorite"); ?>';
                if (ddBtn) ddBtn.style.display = '';
            }
        });
    }

    // Heart button click — toggle without folder (root)
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        toggleFav(null);
    });

    // Load folders when dropdown opens
    if (ddBtn && menu) {
        ddBtn.addEventListener('click', function() {
            if (foldersLoaded) return;
            foldersLoaded = true;
            fetch('/favorites/ajax/folders', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                menu.innerHTML = '';
                // Header
                var hdr = document.createElement('li');
                hdr.innerHTML = '<h6 class="dropdown-header"><i class="fas fa-folder me-1"></i><?php echo __("Add to folder"); ?></h6>';
                menu.appendChild(hdr);

                var divider = document.createElement('li');
                divider.innerHTML = '<hr class="dropdown-divider">';
                menu.appendChild(divider);

                // "No folder (root)" option
                var rootLi = document.createElement('li');
                var rootA = document.createElement('a');
                rootA.className = 'dropdown-item';
                rootA.href = '#';
                rootA.innerHTML = '<i class="fas fa-inbox me-2 text-muted"></i><?php echo __("Unfiled"); ?>';
                rootA.addEventListener('click', function(ev) { ev.preventDefault(); toggleFav(null); });
                rootLi.appendChild(rootA);
                menu.appendChild(rootLi);

                if (data.folders && data.folders.length > 0) {
                    data.folders.forEach(function(f) {
                        var li = document.createElement('li');
                        var a = document.createElement('a');
                        a.className = 'dropdown-item';
                        a.href = '#';
                        var icon = f.icon || 'fa-folder';
                        var color = f.color || '#6c757d';
                        a.innerHTML = '<i class="fas ' + icon + ' me-2" style="color:' + color + ';"></i>' +
                                      escH(f.name) +
                                      (f.item_count ? ' <span class="badge bg-secondary ms-1">' + f.item_count + '</span>' : '');
                        (function(fId) {
                            a.addEventListener('click', function(ev) { ev.preventDefault(); toggleFav(fId); });
                        })(f.id);
                        li.appendChild(a);
                        menu.appendChild(li);
                    });
                }

                // Create new folder option
                var newDiv = document.createElement('li');
                newDiv.innerHTML = '<hr class="dropdown-divider">';
                menu.appendChild(newDiv);
                var newLi = document.createElement('li');
                var newA = document.createElement('a');
                newA.className = 'dropdown-item text-primary';
                newA.href = '/favorites';
                newA.innerHTML = '<i class="fas fa-plus me-2"></i><?php echo __("Manage folders..."); ?>';
                newLi.appendChild(newA);
                menu.appendChild(newLi);
            })
            .catch(function() {
                menu.innerHTML = '<li class="px-3 py-1 text-danger small"><?php echo __("Could not load folders"); ?></li>';
            });
        });
    }

    function escH(s) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(s || ''));
        return d.innerHTML;
    }
})();
</script>
