<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fas fa-heart me-2"></i><?php echo __('My Favorites'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<?php
  // Build base URL for pagination/filtering links
  $baseParams = [];
  if ($currentQuery) $baseParams['query'] = $currentQuery;
  if ($currentSort !== 'created_at') $baseParams['sort'] = $currentSort;
  if ($currentSortDir !== 'desc') $baseParams['sortDir'] = $currentSortDir;
  if ($currentFolderId) $baseParams['folder_id'] = $currentFolderId;
  if ($currentUnfiled) $baseParams['unfiled'] = 1;
  if ($viewMode !== 'table') $baseParams['view'] = $viewMode;

  $buildUrl = function($extra = []) use ($baseParams) {
      $p = array_merge($baseParams, $extra);
      return '/favorites' . ($p ? '?' . http_build_query($p) : '');
  };

  // Sort labels
  $sortOptions = [
      'created_at|desc' => __('Date Added (Newest)'),
      'created_at|asc' => __('Date Added (Oldest)'),
      'archival_description|asc' => __('Title (A-Z)'),
      'archival_description|desc' => __('Title (Z-A)'),
      'reference_code|asc' => __('Reference Code (A-Z)'),
  ];
  $currentSortKey = $currentSort . '|' . $currentSortDir;
?>

<div class="container-fluid px-0">
  <div class="row g-3">

    <!-- Folder Sidebar -->
    <div class="col-lg-3 col-md-4">
      <div class="card shadow-sm mb-3">
        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center py-2">
          <span><i class="fas fa-folder me-1"></i> <?php echo __('Folders'); ?></span>
          <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#newFolderModal" title="<?php echo __('New Folder'); ?>">
            <i class="fas fa-plus"></i>
          </button>
        </div>
        <div class="list-group list-group-flush">
          <!-- All Favourites -->
          <a href="/favorites" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center<?php echo (!$currentFolderId && !$currentUnfiled) ? ' active' : ''; ?>">
            <span><i class="fas fa-heart me-2"></i><?php echo __('All Favorites'); ?></span>
            <span class="badge bg-primary rounded-pill"><?php echo $total; ?></span>
          </a>

          <!-- Unfiled -->
          <a href="/favorites?unfiled=1" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center<?php echo $currentUnfiled ? ' active' : ''; ?>">
            <span><i class="fas fa-inbox me-2"></i><?php echo __('Unfiled'); ?></span>
            <span class="badge bg-secondary rounded-pill"><?php echo $unfiledCount; ?></span>
          </a>

          <?php if (!empty($folders)): ?>
            <li class="list-group-item px-3 py-1 bg-light"><small class="text-muted text-uppercase"><?php echo __('My Folders'); ?></small></li>
            <?php foreach ($folders as $folder): ?>
              <a href="/favorites?folder_id=<?php echo $folder->id; ?>"
                 class="list-group-item list-group-item-action d-flex justify-content-between align-items-center<?php echo ($currentFolderId == $folder->id) ? ' active' : ''; ?>"
                 <?php if ($folder->parent_id): ?>style="padding-left: 2.5rem;"<?php endif; ?>>
                <span>
                  <i class="fas fa-folder<?php echo ($currentFolderId == $folder->id) ? '-open' : ''; ?> me-2"></i>
                  <?php echo esc_entities($folder->name); ?>
                </span>
                <span class="badge bg-secondary rounded-pill"><?php echo $folder->item_count; ?></span>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($currentFolder): ?>
        <!-- Folder Actions -->
        <div class="card shadow-sm mb-3">
          <div class="card-body py-2">
            <h6 class="mb-2"><?php echo esc_entities($currentFolder->name); ?></h6>
            <?php if ($currentFolder->description): ?>
              <p class="text-muted small mb-2"><?php echo esc_entities($currentFolder->description); ?></p>
            <?php endif; ?>
            <div class="d-flex gap-1">
              <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editFolderModal">
                <i class="fas fa-edit"></i> <?php echo __('Edit'); ?>
              </button>
              <form action="/favorites/folder/<?php echo $currentFolder->id; ?>/delete" method="post" class="d-inline"
                    onsubmit="return confirm('<?php echo __('Delete this folder? Items will be moved to Unfiled.'); ?>');">
                <button type="submit" class="btn btn-sm btn-outline-danger">
                  <i class="fas fa-trash"></i> <?php echo __('Delete'); ?>
                </button>
              </form>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Main Content -->
    <div class="col-lg-9 col-md-8">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
          <span>
            <i class="fas fa-heart me-2"></i>
            <?php if ($currentFolder): ?>
              <?php echo esc_entities($currentFolder->name); ?>
            <?php elseif ($currentUnfiled): ?>
              <?php echo __('Unfiled Favorites'); ?>
            <?php else: ?>
              <?php echo __('All Favorites'); ?>
            <?php endif; ?>
            <span class="badge bg-light text-primary ms-2"><?php echo $count; ?></span>
          </span>
          <div class="d-flex gap-2 align-items-center">
            <!-- View Toggle -->
            <div class="btn-group btn-group-sm">
              <a href="<?php echo $buildUrl(['view' => 'table']); ?>" class="btn <?php echo $viewMode === 'table' ? 'btn-light' : 'btn-outline-light'; ?>" title="<?php echo __('Table View'); ?>">
                <i class="fas fa-list"></i>
              </a>
              <a href="<?php echo $buildUrl(['view' => 'grid']); ?>" class="btn <?php echo $viewMode === 'grid' ? 'btn-light' : 'btn-outline-light'; ?>" title="<?php echo __('Grid View'); ?>">
                <i class="fas fa-th"></i>
              </a>
            </div>
            <?php if ($count > 0): ?>
              <form action="<?php echo url_for(['module' => 'favorites', 'action' => 'clear']); ?>" method="post"
                    onsubmit="return confirm('<?php echo __('Are you sure you want to clear all favorites?'); ?>');">
                <button type="submit" class="btn btn-sm btn-outline-light">
                  <i class="fas fa-trash-alt me-1"></i><?php echo __('Clear All'); ?>
                </button>
              </form>
            <?php endif; ?>
          </div>
        </div>

        <!-- Search + Sort Bar -->
        <div class="card-body border-bottom py-2">
          <form method="get" action="/favorites" class="row g-2 align-items-end">
            <?php if ($currentFolderId): ?>
              <input type="hidden" name="folder_id" value="<?php echo $currentFolderId; ?>">
            <?php elseif ($currentUnfiled): ?>
              <input type="hidden" name="unfiled" value="1">
            <?php endif; ?>
            <?php if ($viewMode !== 'table'): ?>
              <input type="hidden" name="view" value="<?php echo esc_entities($viewMode); ?>">
            <?php endif; ?>

            <div class="col-md-5">
              <div class="input-group input-group-sm">
                <input type="text" name="query" class="form-control" placeholder="<?php echo __('Search favorites...'); ?>"
                       value="<?php echo esc_entities($currentQuery); ?>">
                <button class="btn btn-outline-secondary" type="submit">
                  <i class="fas fa-search"></i>
                </button>
                <?php if ($currentQuery): ?>
                  <a href="<?php echo $buildUrl(['query' => null]); ?>" class="btn btn-outline-danger" title="<?php echo __('Clear search'); ?>">
                    <i class="fas fa-times"></i>
                  </a>
                <?php endif; ?>
              </div>
            </div>
            <div class="col-md-4">
              <select name="sort" class="form-select form-select-sm" onchange="this.form.querySelector('[name=sortDir]').value=this.value.split('|')[1]; this.value=this.value.split('|')[0]; this.form.submit();">
                <?php foreach ($sortOptions as $key => $label): ?>
                  <option value="<?php echo $key; ?>" <?php echo ($key === $currentSortKey) ? 'selected' : ''; ?>>
                    <?php echo $label; ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <input type="hidden" name="sortDir" value="<?php echo esc_entities($currentSortDir); ?>">
            </div>
            <div class="col-md-3 text-end">
              <small class="text-muted">
                <?php echo __('%1% items', ['%1%' => $count]); ?>
              </small>
            </div>
          </form>
        </div>

        <!-- Bulk Action Bar (hidden until selection) -->
        <div id="bulkActionBar" class="card-body border-bottom py-2 bg-light" style="display: none;">
          <form method="post" action="/favorites/bulk" id="bulkForm">
            <div class="d-flex gap-2 align-items-center">
              <span id="selectedCount" class="text-muted small me-2">0 <?php echo __('selected'); ?></span>
              <button type="submit" name="bulk_action" value="remove" class="btn btn-sm btn-outline-danger"
                      onclick="return confirm('<?php echo __('Remove selected favorites?'); ?>');">
                <i class="fas fa-trash me-1"></i><?php echo __('Remove Selected'); ?>
              </button>
              <?php if (!empty($folders)): ?>
                <div class="input-group input-group-sm" style="max-width: 250px;">
                  <select name="target_folder_id" class="form-select form-select-sm">
                    <option value=""><?php echo __('Move to...'); ?></option>
                    <option value=""><?php echo __('Unfiled'); ?></option>
                    <?php foreach ($folders as $f): ?>
                      <option value="<?php echo $f->id; ?>"><?php echo esc_entities($f->name); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" name="bulk_action" value="move" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-folder"></i> <?php echo __('Move'); ?>
                  </button>
                </div>
              <?php endif; ?>
            </div>
            <div id="bulkIdsContainer"></div>
          </form>
        </div>

        <div class="card-body">
          <?php if (empty($favorites)): ?>
            <div class="alert alert-info mb-0">
              <i class="fas fa-info-circle me-2"></i>
              <?php if ($currentQuery): ?>
                <?php echo __('No favorites match your search.'); ?>
              <?php else: ?>
                <?php echo __('You have no favorites yet. Browse the archive and click the heart icon to add items.'); ?>
              <?php endif; ?>
            </div>
          <?php elseif ($viewMode === 'grid'): ?>
            <!-- Grid View -->
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
              <?php foreach ($favorites as $favorite): ?>
                <div class="col">
                  <div class="card h-100 border">
                    <div class="card-body">
                      <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="form-check">
                          <input type="checkbox" class="form-check-input bulk-checkbox" value="<?php echo $favorite->id; ?>">
                        </div>
                        <a href="<?php echo url_for(['module' => 'favorites', 'action' => 'remove', 'id' => $favorite->id]); ?>"
                           class="btn btn-sm btn-outline-danger" title="<?php echo __('Remove'); ?>"
                           onclick="return confirm('<?php echo __('Remove from favorites?'); ?>');">
                          <i class="fas fa-heart-broken"></i>
                        </a>
                      </div>
                      <h6 class="card-title">
                        <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $favorite->slug]); ?>" class="text-decoration-none">
                          <?php echo esc_entities($favorite->title); ?>
                        </a>
                      </h6>
                      <?php if ($favorite->reference_code): ?>
                        <p class="card-text small text-muted mb-1">
                          <i class="fas fa-barcode me-1"></i><?php echo esc_entities($favorite->reference_code); ?>
                        </p>
                      <?php endif; ?>
                      <?php if ($favorite->level_of_description): ?>
                        <p class="card-text small text-muted mb-1">
                          <i class="fas fa-layer-group me-1"></i><?php echo esc_entities($favorite->level_of_description); ?>
                        </p>
                      <?php endif; ?>
                      <p class="card-text small text-muted mb-0">
                        <i class="far fa-clock me-1"></i><?php echo date('Y-m-d', strtotime($favorite->created_at)); ?>
                      </p>
                    </div>
                    <?php if ($favorite->notes): ?>
                      <div class="card-footer bg-light small">
                        <i class="fas fa-sticky-note me-1"></i><?php echo esc_entities($favorite->notes); ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <!-- Table View -->
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width: 35px;">
                      <input type="checkbox" class="form-check-input" id="selectAll" title="<?php echo __('Select all'); ?>">
                    </th>
                    <th><?php echo __('Title'); ?></th>
                    <th style="width: 140px;"><?php echo __('Reference Code'); ?></th>
                    <th class="text-center" style="width: 110px;"><?php echo __('Level'); ?></th>
                    <th class="text-center" style="width: 110px;"><?php echo __('Date Added'); ?></th>
                    <th class="text-center" style="width: 60px;"><?php echo __('Notes'); ?></th>
                    <th class="text-center" style="width: 100px;"><?php echo __('Actions'); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($favorites as $favorite): ?>
                    <tr>
                      <td>
                        <input type="checkbox" class="form-check-input bulk-checkbox" value="<?php echo $favorite->id; ?>">
                      </td>
                      <td>
                        <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $favorite->slug]); ?>" class="text-decoration-none">
                          <i class="fas fa-file-alt text-muted me-2"></i>
                          <?php echo esc_entities($favorite->title); ?>
                        </a>
                      </td>
                      <td class="small text-muted">
                        <?php echo esc_entities($favorite->reference_code ?? ''); ?>
                      </td>
                      <td class="text-center small text-muted">
                        <?php echo esc_entities($favorite->level_of_description ?? ''); ?>
                      </td>
                      <td class="text-center text-muted small">
                        <?php echo date('Y-m-d', strtotime($favorite->created_at)); ?>
                      </td>
                      <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary notes-toggle"
                                data-fav-id="<?php echo $favorite->id; ?>"
                                title="<?php echo __('Notes'); ?>">
                          <i class="fas fa-sticky-note<?php echo $favorite->notes ? ' text-warning' : ''; ?>"></i>
                        </button>
                      </td>
                      <td class="text-center">
                        <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $favorite->slug]); ?>"
                           class="btn btn-sm btn-outline-primary me-1" title="<?php echo __('View'); ?>">
                          <i class="fas fa-eye"></i>
                        </a>
                        <a href="<?php echo url_for(['module' => 'favorites', 'action' => 'remove', 'id' => $favorite->id]); ?>"
                           class="btn btn-sm btn-outline-danger" title="<?php echo __('Remove'); ?>"
                           onclick="return confirm('<?php echo __('Remove from favorites?'); ?>');">
                          <i class="fas fa-heart-broken"></i>
                        </a>
                      </td>
                    </tr>
                    <!-- Expandable notes row -->
                    <tr class="notes-row" id="notes-row-<?php echo $favorite->id; ?>" style="display: none;">
                      <td></td>
                      <td colspan="6">
                        <div class="input-group input-group-sm">
                          <input type="text" class="form-control notes-input" id="notes-input-<?php echo $favorite->id; ?>"
                                 placeholder="<?php echo __('Add a note...'); ?>"
                                 value="<?php echo esc_entities($favorite->notes ?? ''); ?>">
                          <button class="btn btn-outline-primary notes-save" data-fav-id="<?php echo $favorite->id; ?>" type="button">
                            <i class="fas fa-save"></i> <?php echo __('Save'); ?>
                          </button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if (isset($pager) && $pager->haveToPaginate()): ?>
          <div class="card-footer">
            <?php echo get_partial('default/pager', ['pager' => $pager]); ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<!-- New Folder Modal -->
<div class="modal fade" id="newFolderModal" tabindex="-1" aria-labelledby="newFolderModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="/favorites/folder/create">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="newFolderModalLabel"><i class="fas fa-folder-plus me-2"></i><?php echo __('New Folder'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="folderName" class="form-label"><?php echo __('Folder Name'); ?> *</label>
            <input type="text" class="form-control" id="folderName" name="folder_name" required maxlength="255">
          </div>
          <div class="mb-3">
            <label for="folderDescription" class="form-label"><?php echo __('Description'); ?></label>
            <textarea class="form-control" id="folderDescription" name="folder_description" rows="2"></textarea>
          </div>
          <?php if (!empty($folders)): ?>
            <div class="mb-3">
              <label for="parentFolder" class="form-label"><?php echo __('Parent Folder'); ?></label>
              <select class="form-select" id="parentFolder" name="parent_id">
                <option value=""><?php echo __('None (top level)'); ?></option>
                <?php foreach ($folders as $f): ?>
                  <?php if (!$f->parent_id): // Only show top-level folders as parents ?>
                    <option value="<?php echo $f->id; ?>"><?php echo esc_entities($f->name); ?></option>
                  <?php endif; ?>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-folder-plus me-1"></i><?php echo __('Create Folder'); ?></button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php if ($currentFolder): ?>
<!-- Edit Folder Modal -->
<div class="modal fade" id="editFolderModal" tabindex="-1" aria-labelledby="editFolderModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="/favorites/folder/<?php echo $currentFolder->id; ?>/edit">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editFolderModalLabel"><i class="fas fa-edit me-2"></i><?php echo __('Edit Folder'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="editFolderName" class="form-label"><?php echo __('Folder Name'); ?> *</label>
            <input type="text" class="form-control" id="editFolderName" name="folder_name"
                   value="<?php echo esc_entities($currentFolder->name); ?>" required maxlength="255">
          </div>
          <div class="mb-3">
            <label for="editFolderDescription" class="form-label"><?php echo __('Description'); ?></label>
            <textarea class="form-control" id="editFolderDescription" name="folder_description" rows="2"><?php echo esc_entities($currentFolder->description ?? ''); ?></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i><?php echo __('Save Changes'); ?></button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function() {
    // Select All checkbox
    var selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            var checkboxes = document.querySelectorAll('.bulk-checkbox');
            checkboxes.forEach(function(cb) { cb.checked = selectAll.checked; });
            updateBulkBar();
        });
    }

    // Individual checkboxes
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('bulk-checkbox')) {
            updateBulkBar();
        }
    });

    function updateBulkBar() {
        var checked = document.querySelectorAll('.bulk-checkbox:checked');
        var bar = document.getElementById('bulkActionBar');
        var countEl = document.getElementById('selectedCount');
        var container = document.getElementById('bulkIdsContainer');

        if (checked.length > 0) {
            bar.style.display = 'block';
            countEl.textContent = checked.length + ' <?php echo __('selected'); ?>';

            // Populate hidden inputs
            container.innerHTML = '';
            checked.forEach(function(cb) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = cb.value;
                container.appendChild(input);
            });
        } else {
            bar.style.display = 'none';
        }
    }

    // Notes toggle
    document.querySelectorAll('.notes-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var favId = btn.getAttribute('data-fav-id');
            var row = document.getElementById('notes-row-' + favId);
            if (row) {
                row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
                if (row.style.display === 'table-row') {
                    var input = document.getElementById('notes-input-' + favId);
                    if (input) input.focus();
                }
            }
        });
    });

    // Notes save (AJAX)
    document.querySelectorAll('.notes-save').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var favId = btn.getAttribute('data-fav-id');
            var input = document.getElementById('notes-input-' + favId);
            if (!input) return;

            fetch('/favorites/notes/' + favId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'notes=' + encodeURIComponent(input.value)
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    // Update the notes icon colour
                    var toggleBtn = document.querySelector('.notes-toggle[data-fav-id="' + favId + '"] i');
                    if (toggleBtn) {
                        if (input.value) {
                            toggleBtn.classList.add('text-warning');
                        } else {
                            toggleBtn.classList.remove('text-warning');
                        }
                    }
                    // Flash green briefly
                    input.classList.add('is-valid');
                    setTimeout(function() { input.classList.remove('is-valid'); }, 1500);
                }
            });
        });
    });

    // Sort select handler
    var sortSelect = document.querySelector('select[name="sort"]');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            var parts = this.value.split('|');
            var form = this.closest('form');
            var sortDirInput = form.querySelector('[name="sortDir"]');
            this.value = parts[0];
            sortDirInput.value = parts[1];
            form.submit();
        });
    }
})();
</script>

<?php end_slot(); ?>
