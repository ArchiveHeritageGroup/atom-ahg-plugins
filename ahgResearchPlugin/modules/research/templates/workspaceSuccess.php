<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="fas fa-briefcase text-primary me-2"></i><?php echo __('My Workspace'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php
// Convert escaped arrays to plain arrays
$collections = is_array($collections) ? $collections : (method_exists($collections, 'getRawValue') ? $collections->getRawValue() : iterator_to_array($collections));
$savedSearches = is_array($savedSearches) ? $savedSearches : (method_exists($savedSearches, 'getRawValue') ? $savedSearches->getRawValue() : (is_iterable($savedSearches) ? iterator_to_array($savedSearches) : []));
$annotations = is_array($annotations) ? $annotations : (method_exists($annotations, 'getRawValue') ? $annotations->getRawValue() : (is_iterable($annotations) ? iterator_to_array($annotations) : []));
$upcomingBookings = is_array($upcomingBookings) ? $upcomingBookings : (method_exists($upcomingBookings, 'getRawValue') ? $upcomingBookings->getRawValue() : (is_iterable($upcomingBookings) ? iterator_to_array($upcomingBookings) : []));
$pastBookings = is_array($pastBookings) ? $pastBookings : (method_exists($pastBookings, 'getRawValue') ? $pastBookings->getRawValue() : (is_iterable($pastBookings) ? iterator_to_array($pastBookings) : []));
?>

<?php if ($sf_user->hasFlash('success')): ?>
  <div class="alert alert-success alert-dismissible fade show"><?php echo $sf_user->getFlash('success'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show"><?php echo $sf_user->getFlash('error'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Profile Summary Bar -->
<div class="card bg-light mb-4">
  <div class="card-body py-3">
    <div class="row align-items-center">
      <div class="col-md-6">
        <h5 class="mb-1"><i class="fas fa-user-graduate me-2 text-primary"></i><?php echo htmlspecialchars(($researcher->title ? $researcher->title . ' ' : '') . $researcher->first_name . ' ' . $researcher->last_name); ?></h5>
        <small class="text-muted"><?php echo htmlspecialchars($researcher->institution ?: 'Independent Researcher'); ?>
          <?php if ($researcher->affiliation_type): ?><span class="badge bg-secondary ms-2"><?php echo ucfirst($researcher->affiliation_type); ?></span><?php endif; ?>
        </small>
      </div>
      <div class="col-md-6 text-md-end mt-2 mt-md-0">
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'profile']); ?>" class="btn btn-outline-secondary btn-sm me-1"><i class="fas fa-user-edit me-1"></i><?php echo __('Edit Profile'); ?></a>
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'book']); ?>" class="btn btn-primary btn-sm"><i class="fas fa-calendar-plus me-1"></i><?php echo __('Book Reading Room'); ?></a>
      </div>
    </div>
  </div>
</div>

<!-- Stats Row -->
<div class="row mb-4">
  <div class="col-6 col-md-3 mb-2">
    <div class="card text-center h-100 border-primary"><div class="card-body py-3"><h3 class="text-primary mb-0"><?php echo number_format($stats['total_collections'] ?? 0); ?></h3><small class="text-muted"><?php echo __('Collections'); ?></small></div></div>
  </div>
  <div class="col-6 col-md-3 mb-2">
    <div class="card text-center h-100 border-success"><div class="card-body py-3"><h3 class="text-success mb-0"><?php echo number_format($stats['total_items'] ?? 0); ?></h3><small class="text-muted"><?php echo __('Saved Items'); ?></small></div></div>
  </div>
  <div class="col-6 col-md-3 mb-2">
    <div class="card text-center h-100 border-info"><div class="card-body py-3"><h3 class="text-info mb-0"><?php echo number_format($stats['total_saved_searches'] ?? 0); ?></h3><small class="text-muted"><?php echo __('Saved Searches'); ?></small></div></div>
  </div>
  <div class="col-6 col-md-3 mb-2">
    <div class="card text-center h-100 border-warning"><div class="card-body py-3"><h3 class="text-warning mb-0"><?php echo number_format($stats['total_bookings'] ?? 0); ?></h3><small class="text-muted"><?php echo __('Total Visits'); ?></small></div></div>
  </div>
</div>

<div class="row">
  <!-- Left Column: Bookings -->
  <div class="col-md-4 mb-4">
    <div class="card mb-4">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2">
        <span><i class="fas fa-calendar-check me-2"></i><?php echo __('Upcoming Visits'); ?></span>
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'book']); ?>" class="btn btn-sm btn-light py-0"><i class="fas fa-plus"></i></a>
      </div>
      <?php if (!empty($upcomingBookings)): ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($upcomingBookings as $booking): ?>
            <li class="list-group-item py-2">
              <div class="d-flex justify-content-between align-items-start">
                <div><strong><?php echo date('D, M j', strtotime($booking->booking_date)); ?></strong><br>
                  <small class="text-muted"><?php echo substr($booking->start_time, 0, 5) . ' - ' . substr($booking->end_time, 0, 5); ?></small><br>
                  <small><?php echo htmlspecialchars($booking->room_name); ?></small>
                </div>
                <span class="badge bg-<?php echo $booking->status === 'confirmed' ? 'success' : 'warning'; ?>"><?php echo ucfirst($booking->status); ?></span>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <div class="card-body text-center text-muted py-4">
          <i class="fas fa-calendar fa-2x mb-2 opacity-50"></i>
          <p class="mb-2"><?php echo __('No upcoming visits'); ?></p>
          <a href="<?php echo url_for(['module' => 'research', 'action' => 'book']); ?>" class="btn btn-sm btn-outline-primary"><?php echo __('Book a visit'); ?></a>
        </div>
      <?php endif; ?>
    </div>
    <div class="card">
      <div class="card-header bg-secondary text-white py-2"><i class="fas fa-history me-2"></i><?php echo __('Recent Visits'); ?></div>
      <?php if (!empty($pastBookings)): ?>
        <ul class="list-group list-group-flush">
          <?php foreach (array_slice($pastBookings, 0, 3) as $booking): ?>
            <li class="list-group-item py-2"><small><strong><?php echo date('M j, Y', strtotime($booking->booking_date)); ?></strong> - <?php echo htmlspecialchars($booking->room_name); ?>
              <span class="badge bg-<?php echo $booking->status === 'completed' ? 'success' : 'secondary'; ?> float-end"><?php echo ucfirst($booking->status); ?></span></small>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <div class="card-body text-muted small py-3"><?php echo __('No visit history yet'); ?></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Middle Column: Collections -->
  <div class="col-md-4 mb-4">
    <div class="card h-100">
      <div class="card-header bg-success text-white d-flex justify-content-between align-items-center py-2">
        <span><i class="fas fa-folder me-2"></i><?php echo __('My Collections'); ?></span>
        <button type="button" class="btn btn-sm btn-light py-0" data-bs-toggle="modal" data-bs-target="#newCollectionModal"><i class="fas fa-plus"></i></button>
      </div>
      <?php if (!empty($collections)): ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($collections as $collection): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
              <a href="<?php echo url_for(['module' => 'research', 'action' => 'viewCollection', 'id' => $collection->id]); ?>">
                <i class="fas fa-folder-open me-2 text-muted"></i><?php echo htmlspecialchars($collection->name); ?>
              </a>
              <div>
                <span class="badge bg-secondary me-1"><?php echo $collection->item_count ?? 0; ?></span>
                <a href="<?php echo url_for(['module' => 'research', 'action' => 'generateFindingAid', 'id' => $collection->id]); ?>" class="btn btn-sm btn-outline-danger py-0 px-1" title="<?php echo __('Generate Finding Aid'); ?>"><i class="fas fa-file-pdf"></i></a>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
        <div class="card-footer text-center py-2">
          <a href="<?php echo url_for(['module' => 'research', 'action' => 'collections']); ?>" class="small"><?php echo __('Manage all collections'); ?> <i class="fas fa-arrow-right"></i></a>
        </div>
      <?php else: ?>
        <div class="card-body text-center text-muted py-4">
          <i class="fas fa-folder-open fa-2x mb-2 opacity-50"></i>
          <p class="mb-2"><?php echo __('No collections yet'); ?></p>
          <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#newCollectionModal"><?php echo __('Create your first collection'); ?></button>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right Column: Saved Searches & Annotations -->
  <div class="col-md-4 mb-4">
    <div class="card mb-4">
      <div class="card-header bg-info text-white d-flex justify-content-between align-items-center py-2">
        <span><i class="fas fa-search me-2"></i><?php echo __('Saved Searches'); ?></span>
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'savedSearches']); ?>" class="btn btn-sm btn-light py-0"><i class="fas fa-cog"></i></a>
      </div>
      <?php if (!empty($savedSearches)): ?>
        <ul class="list-group list-group-flush">
          <?php foreach (array_slice($savedSearches, 0, 5) as $search): ?>
            <li class="list-group-item py-2">
              <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']) . '?' . ($search->search_query ?? ''); ?>" class="text-decoration-none">
                <i class="fas fa-redo me-2 text-info"></i><strong><?php echo htmlspecialchars($search->name ?? 'Unnamed Search'); ?></strong>
              </a><br><small class="text-muted"><?php echo htmlspecialchars(substr($search->search_query ?? '', 0, 40)); ?>...</small>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <div class="card-body text-center text-muted py-3">
          <i class="fas fa-search fa-2x mb-2 opacity-50"></i>
          <p class="mb-0 small"><?php echo __('Save searches from browse results'); ?></p>
        </div>
      <?php endif; ?>
    </div>

    <div class="card">
      <div class="card-header bg-warning d-flex justify-content-between align-items-center py-2">
        <span><i class="fas fa-sticky-note me-2"></i><?php echo __('My Notes'); ?></span>
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'annotations']); ?>" class="btn btn-sm btn-light py-0"><i class="fas fa-list"></i></a>
      </div>
      <?php if (!empty($annotations)): ?>
        <ul class="list-group list-group-flush">
          <?php foreach (array_slice($annotations, 0, 4) as $annotation): ?>
            <li class="list-group-item py-2"><small>
              <strong><?php echo htmlspecialchars($annotation->title ?: substr($annotation->content ?? '', 0, 30) . '...'); ?></strong><br>
              <span class="text-muted">on: <?php echo htmlspecialchars($annotation->object_title ?? 'Unknown item'); ?></span></small>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <div class="card-body text-center text-muted py-3">
          <i class="fas fa-sticky-note fa-2x mb-2 opacity-50"></i>
          <p class="mb-0 small"><?php echo __('Add notes to items while browsing'); ?></p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Quick Tips -->
<div class="card border-info mt-2">
  <div class="card-body py-3">
    <h6 class="card-title"><i class="fas fa-lightbulb text-info me-2"></i><?php echo __('Research Tips'); ?></h6>
    <div class="row small text-muted">
      <div class="col-md-4"><i class="fas fa-plus-circle me-1"></i> <?php echo __('Use "Add to Collection" button while browsing to save items'); ?></div>
      <div class="col-md-4"><i class="fas fa-bookmark me-1"></i> <?php echo __('Save searches to quickly re-run them later'); ?></div>
      <div class="col-md-4"><i class="fas fa-file-pdf me-1"></i> <?php echo __('Generate finding aids from your collections'); ?></div>
    </div>
  </div>
</div>

<!-- New Collection Modal -->
<div class="modal fade" id="newCollectionModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="action" value="create_collection">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-folder-plus me-2"></i><?php echo __('Create New Collection'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Collection Name'); ?> *</label>
            <input type="text" name="collection_name" class="form-control" required placeholder="<?php echo __('e.g., My Research Project'); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Description'); ?></label>
            <textarea name="collection_description" class="form-control" rows="2" placeholder="<?php echo __('Optional description...'); ?>"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="submit" class="btn btn-success"><i class="fas fa-plus me-1"></i><?php echo __('Create'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php end_slot() ?>
