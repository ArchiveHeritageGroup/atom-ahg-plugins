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
                  <?php if (!empty($folder->share_token)): ?>
                    <i class="fas fa-share-alt text-info ms-1" title="<?php echo __('Shared'); ?>"></i>
                  <?php endif; ?>
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
            <div class="d-flex gap-1 flex-wrap">
              <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editFolderModal">
                <i class="fas fa-edit"></i> <?php echo __('Edit'); ?>
              </button>
              <?php if (empty($currentFolder->share_token)): ?>
                <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#shareFolderModal">
                  <i class="fas fa-share-alt"></i> <?php echo __('Share'); ?>
                </button>
              <?php else: ?>
                <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#shareInfoModal">
                  <i class="fas fa-share-alt"></i> <?php echo __('Shared'); ?>
                </button>
              <?php endif; ?>
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
            <!-- Export Dropdown -->
            <?php if ($count > 0): ?>
              <div class="dropdown">
                <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="fas fa-download me-1"></i><?php echo __('Export'); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <?php
                    $exportBase = $currentFolderId
                      ? "/favorites/folder/{$currentFolderId}/export"
                      : '/favorites/export';
                  ?>
                  <li><a class="dropdown-item" href="<?php echo $exportBase; ?>/csv"><i class="fas fa-file-csv me-2 text-success"></i><?php echo __('CSV'); ?></a></li>
                  <li><a class="dropdown-item" href="<?php echo $exportBase; ?>/pdf"><i class="fas fa-file-pdf me-2 text-danger"></i><?php echo __('PDF'); ?></a></li>
                  <li><a class="dropdown-item" href="<?php echo $exportBase; ?>/bibtex"><i class="fas fa-book me-2 text-info"></i><?php echo __('BibTeX'); ?></a></li>
                  <li><a class="dropdown-item" href="<?php echo $exportBase; ?>/ris"><i class="fas fa-file-alt me-2 text-secondary"></i><?php echo __('RIS'); ?></a></li>
                  <li><a class="dropdown-item" href="<?php echo $exportBase; ?>/json"><i class="fas fa-code me-2 text-warning"></i><?php echo __('JSON'); ?></a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item" href="<?php echo $exportBase; ?>/print" target="_blank"><i class="fas fa-print me-2"></i><?php echo __('Print'); ?></a></li>
                </ul>
              </div>
            <?php endif; ?>

            <!-- Import Button -->
            <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#importModal">
              <i class="fas fa-upload me-1"></i><?php echo __('Import'); ?>
            </button>

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
            <div class="d-flex gap-2 align-items-center flex-wrap">
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

              <?php if (!empty($researchEnabled)): ?>
                <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#sendToCollectionModal"
                        onclick="populateBulkIds('collectionBulkIds');">
                  <i class="fas fa-archive me-1"></i><?php echo __('Send to Collection'); ?>
                </button>
                <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#sendToProjectModal"
                        onclick="populateBulkIds('projectBulkIds');">
                  <i class="fas fa-project-diagram me-1"></i><?php echo __('Send to Project'); ?>
                </button>
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
                    <?php if (!empty($favorite->thumbnail_path)): ?>
                      <img src="<?php echo esc_entities($favorite->thumbnail_path); ?>" class="card-img-top" alt="" style="height: 120px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                      <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="form-check">
                          <input type="checkbox" class="form-check-input bulk-checkbox" value="<?php echo $favorite->id; ?>">
                        </div>
                        <div>
                          <?php if (!empty($favorite->item_updated_since)): ?>
                            <span class="badge bg-warning text-dark me-1" title="<?php echo __('Updated since favourited'); ?>"><i class="fas fa-sync-alt"></i></span>
                          <?php endif; ?>
                          <?php if (!empty($favorite->has_digital_object)): ?>
                            <i class="fas fa-camera text-info me-1" title="<?php echo __('Has digital object'); ?>"></i>
                          <?php endif; ?>
                          <a href="<?php echo url_for(['module' => 'favorites', 'action' => 'remove', 'id' => $favorite->id]); ?>"
                             class="btn btn-sm btn-outline-danger" title="<?php echo __('Remove'); ?>"
                             onclick="return confirm('<?php echo __('Remove from favorites?'); ?>');">
                            <i class="fas fa-heart-broken"></i>
                          </a>
                        </div>
                      </div>
                      <h6 class="card-title">
                        <i class="<?php echo $favorite->type_icon ?? 'fas fa-file-alt'; ?> text-muted me-1"></i>
                        <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $favorite->slug]); ?>" class="text-decoration-none fav-item-link" data-fav-id="<?php echo $favorite->id; ?>">
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
                      <?php if (!empty($favorite->date_range)): ?>
                        <p class="card-text small text-muted mb-1">
                          <i class="fas fa-calendar-alt me-1"></i><?php echo esc_entities($favorite->date_range); ?>
                        </p>
                      <?php endif; ?>
                      <?php if (!empty($favorite->repository_name)): ?>
                        <p class="card-text small text-muted mb-1">
                          <i class="fas fa-building me-1"></i><?php echo esc_entities($favorite->repository_name); ?>
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
                    <th class="text-center col-optional col-dates" style="width: 130px;"><?php echo __('Dates'); ?></th>
                    <th class="text-center col-optional col-repository" style="width: 150px;"><?php echo __('Repository'); ?></th>
                    <th class="text-center" style="width: 50px;" title="<?php echo __('Digital Object'); ?>"><i class="fas fa-camera"></i></th>
                    <th class="text-center" style="width: 110px;"><?php echo __('Date Added'); ?></th>
                    <th class="text-center" style="width: 60px;"><?php echo __('Status'); ?></th>
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
                        <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $favorite->slug]); ?>" class="text-decoration-none fav-item-link" data-fav-id="<?php echo $favorite->id; ?>">
                          <i class="<?php echo $favorite->type_icon ?? 'fas fa-file-alt'; ?> text-muted me-2"></i>
                          <?php echo esc_entities($favorite->title); ?>
                        </a>
                      </td>
                      <td class="small text-muted">
                        <?php echo esc_entities($favorite->reference_code ?? ''); ?>
                      </td>
                      <td class="text-center small text-muted">
                        <?php echo esc_entities($favorite->level_of_description ?? ''); ?>
                      </td>
                      <td class="text-center small text-muted col-optional col-dates">
                        <?php echo esc_entities($favorite->date_range ?? ''); ?>
                      </td>
                      <td class="text-center small text-muted col-optional col-repository">
                        <?php echo esc_entities($favorite->repository_name ?? ''); ?>
                      </td>
                      <td class="text-center">
                        <?php if (!empty($favorite->has_digital_object)): ?>
                          <i class="fas fa-camera text-info" title="<?php echo __('Has digital object'); ?>"></i>
                        <?php endif; ?>
                      </td>
                      <td class="text-center text-muted small">
                        <?php echo date('Y-m-d', strtotime($favorite->created_at)); ?>
                      </td>
                      <td class="text-center">
                        <?php if (!empty($favorite->item_updated_since)): ?>
                          <span class="badge bg-warning text-dark" title="<?php echo __('Item updated since favourited'); ?>"><?php echo __('Updated'); ?></span>
                        <?php elseif (empty($favorite->item_accessible)): ?>
                          <span class="badge bg-danger" title="<?php echo __('Item no longer accessible'); ?>"><?php echo __('Removed'); ?></span>
                        <?php endif; ?>
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
                           class="btn btn-sm btn-outline-primary me-1 fav-item-link" data-fav-id="<?php echo $favorite->id; ?>" title="<?php echo __('View'); ?>">
                          <i class="fas fa-eye"></i>
                        </a>
                        <?php if (!empty($researchEnabled)): ?>
                          <button type="button" class="btn btn-sm btn-outline-info me-1 cite-btn" data-fav-id="<?php echo $favorite->id; ?>" title="<?php echo __('Cite'); ?>">
                            <i class="fas fa-quote-right"></i>
                          </button>
                        <?php endif; ?>
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
                      <td colspan="10">
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

<!-- Share Folder Modal -->
<div class="modal fade" id="shareFolderModal" tabindex="-1" aria-labelledby="shareFolderModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="/favorites/folder/<?php echo $currentFolder->id; ?>/share" id="shareFolderForm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="shareFolderModalLabel"><i class="fas fa-share-alt me-2"></i><?php echo __('Share Folder'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted"><?php echo __('Generate a link to share this folder with others. They will see a read-only view of the items.'); ?></p>
          <div class="mb-3">
            <label for="expiresInDays" class="form-label"><?php echo __('Link expires in'); ?></label>
            <select class="form-select" id="expiresInDays" name="expires_in_days">
              <option value="7"><?php echo __('7 days'); ?></option>
              <option value="30" selected><?php echo __('30 days'); ?></option>
              <option value="90"><?php echo __('90 days'); ?></option>
              <option value="365"><?php echo __('1 year'); ?></option>
            </select>
          </div>
          <div id="shareResultArea" style="display:none;" class="mt-3">
            <label class="form-label"><?php echo __('Share Link'); ?></label>
            <div class="input-group">
              <input type="text" id="shareUrlInput" class="form-control" readonly>
              <button type="button" class="btn btn-outline-primary" onclick="navigator.clipboard.writeText(document.getElementById('shareUrlInput').value); this.innerHTML='<i class=\'fas fa-check\'></i>';">
                <i class="fas fa-copy"></i>
              </button>
            </div>
            <small class="text-muted mt-1 d-block" id="shareExpiresText"></small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Close'); ?></button>
          <button type="submit" class="btn btn-primary" id="shareSubmitBtn"><i class="fas fa-link me-1"></i><?php echo __('Generate Link'); ?></button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php if (!empty($currentFolder->share_token)): ?>
<!-- Share Info Modal (when already shared) -->
<div class="modal fade" id="shareInfoModal" tabindex="-1" aria-labelledby="shareInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="shareInfoModalLabel"><i class="fas fa-share-alt me-2"></i><?php echo __('Folder is Shared'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label"><?php echo __('Share Link'); ?></label>
          <div class="input-group">
            <?php
              $shareBaseUrl = '';
              try { $shareBaseUrl = sfContext::getInstance()->getRequest()->getUriPrefix(); } catch (Exception $e) {}
            ?>
            <input type="text" class="form-control" readonly value="<?php echo $shareBaseUrl; ?>/favorites/shared/<?php echo esc_entities($currentFolder->share_token); ?>">
            <button type="button" class="btn btn-outline-primary" onclick="navigator.clipboard.writeText(this.previousElementSibling.value); this.innerHTML='<i class=\'fas fa-check\'></i>';">
              <i class="fas fa-copy"></i>
            </button>
          </div>
        </div>
        <?php if ($currentFolder->share_expires_at): ?>
          <p class="text-muted small"><?php echo __('Expires: %1%', ['%1%' => $currentFolder->share_expires_at]); ?></p>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <form action="/favorites/folder/<?php echo $currentFolder->id; ?>/revoke-share" method="post" class="d-inline">
          <button type="submit" class="btn btn-danger" onclick="return confirm('<?php echo __('Revoke sharing? The link will no longer work.'); ?>');">
            <i class="fas fa-ban me-1"></i><?php echo __('Revoke Sharing'); ?>
          </button>
        </form>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Close'); ?></button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="/favorites/import" enctype="multipart/form-data">
      <?php if ($currentFolderId): ?>
        <input type="hidden" name="folder_id" value="<?php echo $currentFolderId; ?>">
      <?php endif; ?>
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="importModalLabel"><i class="fas fa-upload me-2"></i><?php echo __('Import Favorites'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="csvFile" class="form-label"><?php echo __('Upload CSV'); ?></label>
            <input type="file" class="form-control" id="csvFile" name="csv_file" accept=".csv">
            <small class="text-muted"><?php echo __('CSV must contain a "slug" or "reference_code" column.'); ?></small>
          </div>
          <div class="text-center text-muted my-2">&mdash; <?php echo __('or'); ?> &mdash;</div>
          <div class="mb-3">
            <label for="slugList" class="form-label"><?php echo __('Paste Slugs'); ?></label>
            <textarea class="form-control" id="slugList" name="slug_list" rows="4" placeholder="<?php echo __('One slug per line, or comma-separated...'); ?>"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-upload me-1"></i><?php echo __('Import'); ?></button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php if (!empty($researchEnabled)): ?>
<!-- Send to Collection Modal -->
<div class="modal fade" id="sendToCollectionModal" tabindex="-1" aria-labelledby="sendToCollectionModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="/favorites/send-to-collection" id="sendToCollectionForm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="sendToCollectionModalLabel"><i class="fas fa-archive me-2"></i><?php echo __('Send to Collection'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Select Collection'); ?></label>
            <select name="collection_id" class="form-select" id="collectionSelect">
              <option value=""><?php echo __('Loading...'); ?></option>
            </select>
          </div>
          <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" name="include_notes" value="1" checked id="includeNotesCheck">
            <label class="form-check-label" for="includeNotesCheck"><?php echo __('Include notes'); ?></label>
          </div>
          <div id="collectionBulkIds"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane me-1"></i><?php echo __('Send'); ?></button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Send to Project Modal -->
<div class="modal fade" id="sendToProjectModal" tabindex="-1" aria-labelledby="sendToProjectModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="/favorites/send-to-project" id="sendToProjectForm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="sendToProjectModalLabel"><i class="fas fa-project-diagram me-2"></i><?php echo __('Send to Project'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Select Project'); ?></label>
            <select name="project_id" class="form-select" id="projectSelect">
              <option value=""><?php echo __('Loading...'); ?></option>
            </select>
          </div>
          <div id="projectBulkIds"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane me-1"></i><?php echo __('Send'); ?></button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Cite / Send to Bibliography Modal -->
<div class="modal fade" id="citeModal" tabindex="-1" aria-labelledby="citeModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="/favorites/send-to-bibliography" id="citeForm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="citeModalLabel"><i class="fas fa-quote-right me-2"></i><?php echo __('Cite / Add to Bibliography'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Select Bibliography'); ?></label>
            <select name="bibliography_id" class="form-select" id="bibliographySelect">
              <option value=""><?php echo __('Loading...'); ?></option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Citation Style'); ?></label>
            <select name="style" class="form-select">
              <option value="chicago"><?php echo __('Chicago'); ?></option>
              <option value="apa"><?php echo __('APA'); ?></option>
              <option value="mla"><?php echo __('MLA'); ?></option>
              <option value="harvard"><?php echo __('Harvard'); ?></option>
            </select>
          </div>
          <input type="hidden" name="ids[]" id="citeFavId" value="">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="submit" class="btn btn-info text-white"><i class="fas fa-quote-right me-1"></i><?php echo __('Add Citation'); ?></button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Column Config Dropdown -->
<div class="dropdown position-fixed" style="bottom: 20px; right: 20px; z-index: 1050;">
  <button class="btn btn-sm btn-secondary rounded-circle shadow" type="button" data-bs-toggle="dropdown" title="<?php echo __('Column Settings'); ?>" style="width:36px;height:36px;">
    <i class="fas fa-columns"></i>
  </button>
  <ul class="dropdown-menu dropdown-menu-end">
    <li><h6 class="dropdown-header"><?php echo __('Optional Columns'); ?></h6></li>
    <li>
      <label class="dropdown-item">
        <input type="checkbox" class="form-check-input me-2 col-toggle" data-col="col-dates" checked>
        <?php echo __('Dates'); ?>
      </label>
    </li>
    <li>
      <label class="dropdown-item">
        <input type="checkbox" class="form-check-input me-2 col-toggle" data-col="col-repository" checked>
        <?php echo __('Repository'); ?>
      </label>
    </li>
  </ul>
</div>

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
                    var toggleBtn = document.querySelector('.notes-toggle[data-fav-id="' + favId + '"] i');
                    if (toggleBtn) {
                        if (input.value) {
                            toggleBtn.classList.add('text-warning');
                        } else {
                            toggleBtn.classList.remove('text-warning');
                        }
                    }
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

    // Share folder form AJAX
    var shareForm = document.getElementById('shareFolderForm');
    if (shareForm) {
        shareForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new URLSearchParams(new FormData(shareForm));
            fetch(shareForm.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    document.getElementById('shareResultArea').style.display = 'block';
                    document.getElementById('shareUrlInput').value = data.url;
                    document.getElementById('shareExpiresText').textContent = '<?php echo __('Expires:'); ?> ' + data.expires_at;
                    document.getElementById('shareSubmitBtn').style.display = 'none';
                }
            });
        });
    }

    // Column toggle (store in localStorage)
    document.querySelectorAll('.col-toggle').forEach(function(cb) {
        var colClass = cb.getAttribute('data-col');
        var stored = localStorage.getItem('fav_col_' + colClass);
        if (stored === 'hidden') {
            cb.checked = false;
            document.querySelectorAll('.' + colClass).forEach(function(el) { el.style.display = 'none'; });
        }
        cb.addEventListener('change', function() {
            var display = cb.checked ? '' : 'none';
            document.querySelectorAll('.' + colClass).forEach(function(el) { el.style.display = display; });
            localStorage.setItem('fav_col_' + colClass, cb.checked ? 'visible' : 'hidden');
        });
    });

    // Track last_viewed_at when clicking through to item
    document.querySelectorAll('.fav-item-link').forEach(function(link) {
        link.addEventListener('click', function() {
            var favId = link.getAttribute('data-fav-id');
            if (favId) {
                navigator.sendBeacon('/favorites/notes/' + favId + '?track_view=1');
            }
        });
    });

    // Cite button — populate modal
    document.querySelectorAll('.cite-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var favId = btn.getAttribute('data-fav-id');
            document.getElementById('citeFavId').value = favId;
            var modal = new bootstrap.Modal(document.getElementById('citeModal'));
            modal.show();
        });
    });

    // Populate bulk IDs into research modals
    window.populateBulkIds = function(containerId) {
        var container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = '';
        var checked = document.querySelectorAll('.bulk-checkbox:checked');
        checked.forEach(function(cb) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = cb.value;
            container.appendChild(input);
        });
    };

    <?php if (!empty($researchEnabled)): ?>
    // Load research collections/projects/bibliographies via AJAX when modals open
    var collectionLoaded = false, projectLoaded = false, bibLoaded = false;

    var collectionModal = document.getElementById('sendToCollectionModal');
    if (collectionModal) {
        collectionModal.addEventListener('show.bs.modal', function() {
            if (collectionLoaded) return;
            fetch('/favorites/send-to-collection?list=1', {headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var sel = document.getElementById('collectionSelect');
                sel.innerHTML = '';
                (data.collections || []).forEach(function(c) {
                    var opt = document.createElement('option');
                    opt.value = c.id; opt.textContent = c.name;
                    sel.appendChild(opt);
                });
                if (!sel.options.length) sel.innerHTML = '<option value=""><?php echo __('No collections found'); ?></option>';
                collectionLoaded = true;
            });
        });
    }

    var projectModal = document.getElementById('sendToProjectModal');
    if (projectModal) {
        projectModal.addEventListener('show.bs.modal', function() {
            if (projectLoaded) return;
            fetch('/favorites/send-to-project?list=1', {headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var sel = document.getElementById('projectSelect');
                sel.innerHTML = '';
                (data.projects || []).forEach(function(p) {
                    var opt = document.createElement('option');
                    opt.value = p.id; opt.textContent = p.name;
                    sel.appendChild(opt);
                });
                if (!sel.options.length) sel.innerHTML = '<option value=""><?php echo __('No projects found'); ?></option>';
                projectLoaded = true;
            });
        });
    }

    var citeModal = document.getElementById('citeModal');
    if (citeModal) {
        citeModal.addEventListener('show.bs.modal', function() {
            if (bibLoaded) return;
            fetch('/favorites/send-to-bibliography?list=1', {headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var sel = document.getElementById('bibliographySelect');
                sel.innerHTML = '';
                (data.bibliographies || []).forEach(function(b) {
                    var opt = document.createElement('option');
                    opt.value = b.id; opt.textContent = b.name;
                    sel.appendChild(opt);
                });
                if (!sel.options.length) sel.innerHTML = '<option value=""><?php echo __('No bibliographies found'); ?></option>';
                bibLoaded = true;
            });
        });
    }
    <?php endif; ?>
})();
</script>

<?php end_slot(); ?>
