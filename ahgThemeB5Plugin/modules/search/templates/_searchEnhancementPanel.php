<?php
/**
 * Search Enhancement Panel
 * 
 * Include in browse templates:
 * <?php include_partial('search/searchEnhancementPanel', ['entityType' => 'informationobject']); ?>
 * 
 * Path: /usr/share/nginx/archive/plugins/ahgThemeB5Plugin/modules/search/templates/_searchEnhancementPanel.php
 */

// Load service
require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
$searchService = new \App\Services\AdvancedSearchService();

$entityType = $entityType ?? 'informationobject';
$user = sfContext::getInstance()->getUser();
$isAuthenticated = $user->isAuthenticated();
$userId = $isAuthenticated ? $user->getAttribute('user_id') : null;
$sessionId = session_id();

// Get data
$history = $searchService->getUserHistory($userId, $sessionId, 5);
$templates = $searchService->getFeaturedTemplates();
$popular = $searchService->getPopularSearches(5, $entityType);
$savedSearches = $isAuthenticated ? $searchService->getSavedSearches($userId) : [];
?>

<div class="search-enhancement-panel mb-4">
  <!-- Quick Search Templates -->
  <?php if (!empty($templates)): ?>
  <div class="mb-3">
    <label class="form-label small text-muted"><?php echo __('Quick Searches'); ?></label>
    <div class="d-flex flex-wrap gap-2">
      <?php foreach ($templates as $template): ?>
      <a href="<?php echo url_for(['module' => 'searchEnhancement', 'action' => 'runTemplate', 'id' => $template->id]); ?>" 
         class="btn btn-sm btn-outline-<?php echo esc_entities($template->color); ?>">
        <i class="fa <?php echo esc_entities($template->icon); ?> me-1"></i>
        <?php echo esc_entities($template->name); ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Search History & Saved -->
  <div class="row">
    <?php if (!empty($history)): ?>
    <div class="col-md-6 mb-3">
      <div class="card card-body bg-light">
        <h6 class="card-title mb-2">
          <i class="fa fa-history me-1"></i><?php echo __('Recent Searches'); ?>
        </h6>
        <ul class="list-unstyled mb-0 small">
          <?php foreach (array_slice($history, 0, 5) as $item): ?>
          <li class="mb-1">
            <a href="<?php echo url_for(['module' => $entityType, 'action' => 'browse']) . '?' . http_build_query(json_decode($item->search_params, true)); ?>" 
               class="text-decoration-none">
              <?php echo esc_entities($item->search_query ?: __('(Advanced)')); ?>
              <span class="text-muted">(<?php echo $item->result_count; ?>)</span>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
        <a href="<?php echo url_for(['module' => 'searchEnhancement', 'action' => 'history']); ?>" class="small">
          <?php echo __('View all'); ?> →
        </a>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($isAuthenticated && !empty($savedSearches)): ?>
    <div class="col-md-6 mb-3">
      <div class="card card-body bg-light">
        <h6 class="card-title mb-2">
          <i class="fa fa-bookmark me-1"></i><?php echo __('Saved Searches'); ?>
        </h6>
        <ul class="list-unstyled mb-0 small">
          <?php foreach (array_slice($savedSearches, 0, 5) as $saved): ?>
          <li class="mb-1">
            <a href="<?php echo url_for(['module' => 'searchEnhancement', 'action' => 'runSavedSearch', 'id' => $saved->id]); ?>" 
               class="text-decoration-none">
              <?php echo esc_entities($saved->name); ?>
              <?php if ($saved->notify_new_results): ?>
                <i class="fa fa-bell text-info" title="<?php echo __('Notifications enabled'); ?>"></i>
              <?php endif; ?>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
        <a href="<?php echo url_for(['module' => 'searchEnhancement', 'action' => 'savedSearches']); ?>" class="small">
          <?php echo __('Manage saved'); ?> →
        </a>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Popular Searches -->
  <?php if (!empty($popular)): ?>
  <div class="mb-3">
    <label class="form-label small text-muted"><?php echo __('Popular Searches'); ?></label>
    <div class="d-flex flex-wrap gap-1">
      <?php foreach ($popular as $p): ?>
      <a href="<?php echo url_for(['module' => $entityType, 'action' => 'browse']) . '?' . http_build_query(json_decode($p->search_params, true)); ?>" 
         class="badge bg-light text-dark text-decoration-none">
        <?php echo esc_entities($p->search_query); ?>
        <span class="text-muted">(<?php echo $p->search_count; ?>)</span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Save Search Modal -->
<?php if ($isAuthenticated): ?>
<div class="modal fade" id="saveSearchModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa fa-bookmark me-2"></i><?php echo __('Save This Search'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label"><?php echo __('Name'); ?> *</label>
          <input type="text" id="save-search-name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label"><?php echo __('Description'); ?></label>
          <textarea id="save-search-description" class="form-control" rows="2"></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label"><?php echo __('Tags'); ?></label>
          <input type="text" id="save-search-tags" class="form-control" placeholder="<?php echo __('comma-separated'); ?>">
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" id="save-search-public">
          <label class="form-check-label" for="save-search-public">
            <?php echo __('Make public (shareable link)'); ?>
          </label>
        </div>
	<div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" id="save-search-public">
          <label class="form-check-label" for="save-search-public">
            <i class="fa fa-link me-1"></i><?php echo __('Make public (shareable link)'); ?>
          </label>
        </div>
        <?php if ($user->isAdministrator()): ?>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" id="save-search-global">
          <label class="form-check-label" for="save-search-global">
            <i class="fa fa-globe me-1"></i><?php echo __('Global (visible to all users)'); ?>
          </label>
        </div>
        <?php endif; ?>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" id="save-search-notify">
          <label class="form-check-label" for="save-search-notify">
            <?php echo __('Notify me of new results'); ?>
          </label>
        </div>
        <div class="mb-3" id="notify-frequency-group" style="display:none;">
          <label class="form-label"><?php echo __('Notification frequency'); ?></label>
          <select id="save-search-frequency" class="form-select">
            <option value="daily"><?php echo __('Daily'); ?></option>
            <option value="weekly" selected><?php echo __('Weekly'); ?></option>
            <option value="monthly"><?php echo __('Monthly'); ?></option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
        <button type="button" class="btn btn-primary" onclick="saveCurrentSearch()">
          <i class="fa fa-save me-1"></i><?php echo __('Save Search'); ?>
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Toggle notification frequency
document.getElementById('save-search-notify')?.addEventListener('change', function() {
  document.getElementById('notify-frequency-group').style.display = this.checked ? 'block' : 'none';
});

// Save search function
function saveCurrentSearch() {
  const name = document.getElementById('save-search-name').value;
  if (!name) {
    alert('<?php echo __('Please enter a name'); ?>');
    return;
  }
  
const data = {
    name: name,
    description: document.getElementById('save-search-description')?.value || '',
    tags: document.getElementById('save-search-tags')?.value || '',
    is_public: document.getElementById('save-search-public')?.checked ? 1 : 0,
    is_global: document.getElementById('save-search-global')?.checked ? 1 : 0,
    notify: document.getElementById('save-search-notify')?.checked ? 1 : 0,
    frequency: document.getElementById('save-search-frequency')?.value || 'weekly',
    search_params: window.location.search.substring(1),
    entity_type: '<?php echo $entityType; ?>'
  };  
  fetch('<?php echo url_for(['module' => 'searchEnhancement', 'action' => 'saveSearch']); ?>', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams(data)
  })
  .then(r => r.json())
  .then(result => {
    if (result.success) {
      bootstrap.Modal.getInstance(document.getElementById('saveSearchModal')).hide();
      alert('<?php echo __('Search saved!'); ?>');
    } else {
      alert(result.error || '<?php echo __('Error saving search'); ?>');
    }
  });
}

// Add Save Search button to search results
document.addEventListener('DOMContentLoaded', function() {
  const resultsHeader = document.querySelector('.search-results-header, .browse-header, h1');
  if (resultsHeader && window.location.search) {
    const btn = document.createElement('button');
    btn.className = 'btn btn-outline-primary btn-sm ms-2';
    btn.innerHTML = '<i class="fa fa-bookmark me-1"></i><?php echo __('Save Search'); ?>';
    btn.setAttribute('data-bs-toggle', 'modal');
    btn.setAttribute('data-bs-target', '#saveSearchModal');
    resultsHeader.appendChild(btn);
  }
});
</script>
<?php endif; ?>
