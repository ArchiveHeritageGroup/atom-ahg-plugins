<?php
/**
 * GLAM Advanced Search Enhancements
 */

$user = sfContext::getInstance()->getUser();
$isAuthenticated = $user->isAuthenticated();
$isAdmin = $user->isAdministrator();

$savedSearches = [];

try {
    if ($isAuthenticated) {
        $userId = $user->getAttribute('user_id');
        $savedSearches = \Illuminate\Database\Capsule\Manager::table('saved_search')
            ->where('user_id', $userId)
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
    }
} catch (Exception $e) {
    error_log("GLAM Search Error: " . $e->getMessage());
}
?>

<?php if ($isAuthenticated): ?>
<div class="advanced-search-enhancements mt-3 pt-3 border-top">
    <?php if (!empty($savedSearches)): ?>
    <div class="mb-3">
        <h6 class="text-muted mb-2"><i class="fas fa-bookmark me-1"></i><?php echo __('Saved Searches'); ?></h6>
        <div class="d-flex flex-wrap gap-1">
            <?php foreach ($savedSearches as $saved): ?>
            <?php $params = json_decode($saved->search_params, true) ?: []; ?>
            <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']) . '?' . http_build_query($params); ?>"
               class="btn btn-sm btn-outline-primary">
                <?php echo esc_entities($saved->name); ?>
            </a>
            <?php endforeach; ?>
            <a href="/index.php/searchEnhancement/savedSearches" class="btn btn-sm btn-link"><?php echo __('All'); ?></a>
        </div>
    </div>
    <?php endif; ?>

    <div class="text-end">
        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#saveGlamSearchModal">
            <i class="fas fa-bookmark me-1"></i><?php echo __('Save Search'); ?>
        </button>
    </div>
</div>

<div class="modal fade" id="saveGlamSearchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('Save This Search'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label"><?php echo __('Name'); ?> *</label>
                    <input type="text" id="save-glam-search-name" class="form-control" required>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="save-glam-search-notify">
                    <label class="form-check-label" for="save-glam-search-notify"><?php echo __('Notify me of new results'); ?></label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
                <button type="button" class="btn btn-primary" onclick="saveGlamSearch()"><?php echo __('Save'); ?></button>
            </div>
        </div>
    </div>
</div>
<script>
function saveGlamSearch() {
    var name = document.getElementById('save-glam-search-name').value;
    if (!name) { alert('Please enter a name'); return; }
    var notify = document.getElementById('save-glam-search-notify').checked ? 1 : 0;
    var params = window.location.search.substring(1);

    fetch('/index.php/searchEnhancement/saveSearch', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'name=' + encodeURIComponent(name) + '&notify=' + notify + '&search_params=' + encodeURIComponent(params) + '&entity_type=glam'
    })
    .then(function(r) { return r.json(); })
    .then(function(result) {
        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('saveGlamSearchModal')).hide();
            alert('Search saved!');
            location.reload();
        } else {
            alert(result.error || 'Error saving');
        }
    });
}
</script>
<?php endif; ?>
