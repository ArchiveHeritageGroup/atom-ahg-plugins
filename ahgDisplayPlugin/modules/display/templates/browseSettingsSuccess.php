<?php
/**
 * User Browse Settings
 */
?>

<div class="container py-4" style="max-width: 700px;">
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="<?php echo url_for(['module' => 'display', 'action' => 'browse']) ?>">GLAM Browse</a>
      </li>
      <li class="breadcrumb-item active">Settings</li>
    </ol>
  </nav>

  <div class="card">
    <div class="card-header">
      <h5 class="mb-0"><i class="bi bi-gear"></i> Browse Settings</h5>
    </div>
    <div class="card-body">
      <?php if ($sf_user->hasFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
          <i class="bi bi-check-circle"></i> <?php echo $sf_user->getFlash('success') ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif ?>

      <?php if ($sf_user->hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
          <i class="bi bi-exclamation-circle"></i> <?php echo $sf_user->getFlash('error') ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif ?>

      <form method="post" action="<?php echo url_for(['module' => 'display', 'action' => 'browseSettings']) ?>">
        <h6 class="text-muted border-bottom pb-2 mb-3">Browse Interface</h6>

        <div class="mb-4">
          <div class="form-check form-switch">
            <input type="checkbox" name="use_glam_browse" id="use_glam_browse"
                   class="form-check-input" value="1"
                   <?php echo $settings['use_glam_browse'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="use_glam_browse">
              <strong>Use GLAM Browse as default</strong>
            </label>
          </div>
          <div class="form-text ms-4">
            When enabled, you'll be redirected to the GLAM browse interface instead of the standard browse.
            The GLAM browse provides faceted search, type filtering, and enhanced display options.
          </div>
        </div>

        <h6 class="text-muted border-bottom pb-2 mb-3">Default Display Options</h6>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label" for="default_view">Default View</label>
            <select name="default_view" id="default_view" class="form-select">
              <option value="list" <?php echo ($settings['default_view'] ?? 'list') === 'list' ? 'selected' : '' ?>>List</option>
              <option value="card" <?php echo ($settings['default_view'] ?? '') === 'card' ? 'selected' : '' ?>>Cards</option>
              <option value="table" <?php echo ($settings['default_view'] ?? '') === 'table' ? 'selected' : '' ?>>Table</option>
            </select>
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label" for="items_per_page">Items Per Page</label>
            <select name="items_per_page" id="items_per_page" class="form-select">
              <?php foreach ([10, 20, 30, 50, 100] as $n): ?>
                <option value="<?php echo $n ?>" <?php echo ($settings['items_per_page'] ?? 30) == $n ? 'selected' : '' ?>><?php echo $n ?></option>
              <?php endforeach ?>
            </select>
          </div>
        </div>

        <h6 class="text-muted border-bottom pb-2 mb-3">Default Sorting</h6>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label" for="default_sort_field">Sort By</label>
            <select name="default_sort_field" id="default_sort_field" class="form-select">
              <option value="updated_at" <?php echo ($settings['default_sort_field'] ?? 'updated_at') === 'updated_at' ? 'selected' : '' ?>>Last Updated</option>
              <option value="title" <?php echo ($settings['default_sort_field'] ?? '') === 'title' ? 'selected' : '' ?>>Title</option>
              <option value="identifier" <?php echo ($settings['default_sort_field'] ?? '') === 'identifier' ? 'selected' : '' ?>>Identifier</option>
              <option value="date" <?php echo ($settings['default_sort_field'] ?? '') === 'date' ? 'selected' : '' ?>>Date Created</option>
              <option value="startdate" <?php echo ($settings['default_sort_field'] ?? '') === 'startdate' ? 'selected' : '' ?>>Start Date</option>
            </select>
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label" for="default_sort_direction">Direction</label>
            <select name="default_sort_direction" id="default_sort_direction" class="form-select">
              <option value="desc" <?php echo ($settings['default_sort_direction'] ?? 'desc') === 'desc' ? 'selected' : '' ?>>Descending (newest first)</option>
              <option value="asc" <?php echo ($settings['default_sort_direction'] ?? '') === 'asc' ? 'selected' : '' ?>>Ascending (oldest first)</option>
            </select>
          </div>
        </div>

        <h6 class="text-muted border-bottom pb-2 mb-3">Additional Options</h6>

        <div class="mb-3">
          <div class="form-check">
            <input type="checkbox" name="show_facets" id="show_facets"
                   class="form-check-input" value="1"
                   <?php echo ($settings['show_facets'] ?? true) ? 'checked' : '' ?>>
            <label class="form-check-label" for="show_facets">
              Show filter sidebar (facets)
            </label>
          </div>
        </div>

        <div class="mb-4">
          <div class="form-check">
            <input type="checkbox" name="remember_filters" id="remember_filters"
                   class="form-check-input" value="1"
                   <?php echo ($settings['remember_filters'] ?? true) ? 'checked' : '' ?>>
            <label class="form-check-label" for="remember_filters">
              Remember my last used filters
            </label>
          </div>
          <div class="form-text ms-4">
            When enabled, your filter selections will be saved and applied automatically on your next visit.
          </div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg"></i> Save Settings
          </button>
          <a href="<?php echo url_for(['module' => 'display', 'action' => 'browse']) ?>"
             class="btn btn-outline-secondary">
            Cancel
          </a>
          <button type="button" class="btn btn-outline-danger ms-auto" id="reset-settings">
            <i class="bi bi-arrow-counterclockwise"></i> Reset to Defaults
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.getElementById('reset-settings').addEventListener('click', function() {
  if (confirm('Reset all browse settings to defaults?')) {
    fetch('<?php echo url_for(['module' => 'display', 'action' => 'resetBrowseSettings']) ?>', {
      method: 'POST',
      headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        window.location.reload();
      } else {
        alert('Failed to reset settings');
      }
    });
  }
});
</script>
