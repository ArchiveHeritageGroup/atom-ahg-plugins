<?php
/**
 * GLAM Advanced Search Enhancements - with dropdown for saved searches
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
            ->limit(10)
            ->get()
            ->toArray();
    }
} catch (Exception $e) {
    // Silently fail
}
?>

<div class="advanced-search-enhancements mt-3 pt-2 border-top">
  <?php if ($isAuthenticated): ?>
  <div class="d-flex align-items-center flex-wrap gap-2">
    <?php if (!empty($savedSearches)): ?>
    <div class="dropdown">
      <button class="btn btn-sm btn-outline-success dropdown-toggle py-0 px-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-bookmark me-1"></i><?php echo __('Saved Searches'); ?> (<?php echo count($savedSearches); ?>)
      </button>
      <ul class="dropdown-menu">
        <?php foreach ($savedSearches as $saved): ?>
        <?php $params = json_decode($saved->search_params, true) ?: []; ?>
        <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']) . '?' . http_build_query($params); ?>">
          <i class="fas fa-search me-2 text-muted"></i><?php echo esc_entities($saved->name); ?>
        </a></li>
        <?php endforeach; ?>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="/index.php/searchEnhancement/savedSearches">
          <i class="fas fa-cog me-2 text-muted"></i><?php echo __('Manage Saved Searches'); ?>
        </a></li>
      </ul>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($sf_request->getParameterHolder()->getAll())): ?>
    <button type="button" class="btn btn-sm btn-success py-0 px-2" data-bs-toggle="modal" data-bs-target="#saveGlamSearchModal">
      <i class="fas fa-bookmark me-1"></i><?php echo __('Save Search'); ?>
    </button>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<?php if ($isAuthenticated): ?>
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
          <input type="text" id="glam-save-search-name" class="form-control" required>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" id="glam-save-search-public">
          <label class="form-check-label" for="glam-save-search-public">
            <i class="fas fa-link me-1"></i><?php echo __('Make public (shareable link)'); ?>
          </label>
        </div>
        <?php if ($isAdmin): ?>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" id="glam-save-search-global">
          <label class="form-check-label" for="glam-save-search-global">
            <i class="fas fa-globe me-1"></i><?php echo __('Global (visible to all users)'); ?>
          </label>
        </div>
        <?php endif; ?>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="glam-save-search-notify">
          <label class="form-check-label" for="glam-save-search-notify"><?php echo __('Notify me of new results'); ?></label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
        <button type="button" class="btn btn-primary" onclick="saveGlamSearch()"><?php echo __('Save'); ?></button>
      </div>
    </div>
  </div>
</div>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function saveGlamSearch() {
  var name = document.getElementById('glam-save-search-name').value;
  if (!name) { alert('Please enter a name'); return; }
  var notify = document.getElementById('glam-save-search-notify').checked ? 1 : 0;
  var isPublic = document.getElementById('glam-save-search-public').checked ? 1 : 0;
  var isGlobal = document.getElementById('glam-save-search-global')?.checked ? 1 : 0;
  var params = window.location.search.substring(1);

  var xhr = new XMLHttpRequest();
  xhr.open('POST', '/index.php/searchEnhancement/saveSearch', true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.onload = function() {
    if (xhr.status === 200) {
      try {
        var result = JSON.parse(xhr.responseText);
        if (result.success) {
          var modal = bootstrap.Modal.getInstance(document.getElementById('saveGlamSearchModal'));
          if (modal) modal.hide();
          alert('Search saved!');
          location.reload();
        } else {
          alert(result.error || 'Error saving');
        }
      } catch(e) {
        alert('Error: ' + e.message);
      }
    }
  };
  xhr.send('name=' + encodeURIComponent(name) + '&notify=' + notify + '&is_public=' + isPublic + '&is_global=' + isGlobal + '&search_params=' + encodeURIComponent(params) + '&entity_type=informationobject');
}
</script>
<?php endif; ?>
