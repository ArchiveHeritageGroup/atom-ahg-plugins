<?php
/**
 * Advanced Search Enhancements - Simplified
 */

$user = sfContext::getInstance()->getUser();
$isAuthenticated = $user->isAuthenticated();
$isAdmin = $user->isAdministrator();

// Get saved searches directly with simple query
$savedSearches = [];
$templates = [];

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

    $templates = \Illuminate\Database\Capsule\Manager::table('search_template')
        ->where('is_active', 1)
        ->where('is_featured', 1)
        ->orderBy('sort_order')
        ->limit(6)
        ->get()
        ->toArray();
} catch (Exception $e) {
    // Silently fail if tables don't exist
}
?>

<div class="advanced-search-enhancements mt-4 pt-3 border-top">
  <div class="row">
    <?php if (!empty($templates)): ?>
    <div class="col-md-6 mb-3">
      <h6 class="text-muted mb-2"><i class="fa fa-bolt me-1"></i><?php echo __('Quick Searches'); ?></h6>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($templates as $template): ?>
        <?php $params = json_decode($template->search_params, true) ?: []; ?>
        <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']) . '?' . http_build_query($params); ?>"
           class="btn btn-sm btn-outline-<?php echo esc_entities($template->color ?: 'secondary'); ?>">
          <i class="fa <?php echo esc_entities($template->icon ?: 'fa-search'); ?> me-1"></i>
          <?php echo esc_entities($template->name); ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($isAuthenticated && !empty($savedSearches)): ?>
    <div class="col-md-6 mb-3">
      <h6 class="text-muted mb-2"><i class="fa fa-bookmark me-1"></i><?php echo __('Saved Searches'); ?></h6>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($savedSearches as $saved): ?>
        <?php $params = json_decode($saved->search_params, true) ?: []; ?>
        <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']) . '?' . http_build_query($params); ?>"
           class="btn btn-sm btn-outline-primary">
          <?php echo esc_entities($saved->name); ?>
        </a>
        <?php endforeach; ?>
        <a href="/index.php/searchEnhancement/savedSearches" class="btn btn-sm btn-link"><?php echo __('All'); ?> →</a>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <?php if ($isAuthenticated && !empty($_GET)): ?>
  <div class="text-end mt-2">
    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#saveSearchModal">
      <i class="fa fa-bookmark me-1"></i><?php echo __('Save This Search'); ?>
    </button>
  </div>
  <?php endif; ?>
</div>

<?php if ($isAuthenticated): ?>
<div class="modal fade" id="saveSearchModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo __('Save This Search'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label"><?php echo __('Name'); ?> *</label>
          <input type="text" id="save-search-name" class="form-control" required>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" id="save-search-public">
          <label class="form-check-label" for="save-search-public">
            <i class="fa fa-link me-1"></i><?php echo __('Make public (shareable link)'); ?>
          </label>
        </div>
        <?php if ($isAdmin): ?>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" id="save-search-global">
          <label class="form-check-label" for="save-search-global">
            <i class="fa fa-globe me-1"></i><?php echo __('Global (visible to all users)'); ?>
          </label>
        </div>
        <?php endif; ?>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="save-search-notify">
          <label class="form-check-label" for="save-search-notify"><?php echo __('Notify me of new results'); ?></label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
        <button type="button" class="btn btn-primary" onclick="saveCurrentSearch()"><?php echo __('Save'); ?></button>
      </div>
    </div>
  </div>
</div>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function saveCurrentSearch() {
  var name = document.getElementById('save-search-name').value;
  if (!name) { alert('Please enter a name'); return; }
  var notify = document.getElementById('save-search-notify').checked ? 1 : 0;
  var isPublic = document.getElementById('save-search-public').checked ? 1 : 0;
  var isGlobal = document.getElementById('save-search-global')?.checked ? 1 : 0;
  var params = window.location.search.substring(1);

  var xhr = new XMLHttpRequest();
  xhr.open('POST', '/index.php/searchEnhancement/saveSearch', true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.onload = function() {
    if (xhr.status === 200) {
      try {
        var result = JSON.parse(xhr.responseText);
        if (result.success) {
          var modal = bootstrap.Modal.getInstance(document.getElementById('saveSearchModal'));
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
