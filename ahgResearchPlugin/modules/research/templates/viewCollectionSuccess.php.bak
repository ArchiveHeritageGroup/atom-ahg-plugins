<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<div class="d-flex justify-content-between align-items-center">
  <h1>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'collections']); ?>" class="btn btn-outline-secondary btn-sm me-2"><i class="fas fa-arrow-left"></i></a>
    <i class="fas fa-folder-open text-primary me-2"></i><?php echo htmlspecialchars($collection->name); ?>
  </h1>
  <div>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'generateFindingAid', 'id' => $collection->id]); ?>" class="btn btn-danger btn-sm me-1"><i class="fas fa-file-pdf me-1"></i><?php echo __('Finding Aid'); ?></a>
    <button type="button" class="btn btn-outline-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#editCollectionModal"><i class="fas fa-edit me-1"></i><?php echo __('Edit'); ?></button>
    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteCollectionModal"><i class="fas fa-trash me-1"></i><?php echo __('Delete'); ?></button>
  </div>
</div>
<?php end_slot() ?>

<?php slot('content') ?>
<?php if ($sf_user->hasFlash('success')): ?><div class="alert alert-success alert-dismissible fade show"><?php echo $sf_user->getFlash('success'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?><div class="alert alert-danger alert-dismissible fade show"><?php echo $sf_user->getFlash('error'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<div class="card mb-4">
  <div class="card-body">
    <div class="row">
      <div class="col-md-8"><?php if ($collection->description): ?><p class="mb-2"><?php echo htmlspecialchars($collection->description); ?></p><?php endif; ?>
        <small class="text-muted"><i class="fas fa-clock me-1"></i><?php echo __('Created'); ?>: <?php echo date('M j, Y', strtotime($collection->created_at)); ?></small>
      </div>
      <div class="col-md-4 text-md-end">
        <span class="badge bg-<?php echo $collection->is_public ? 'success' : 'secondary'; ?> me-2"><?php echo $collection->is_public ? __('Public') : __('Private'); ?></span>
        <span class="badge bg-primary"><?php echo count($collection->items ?? []); ?> <?php echo __('items'); ?></span>
      </div>
    </div>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header bg-success text-white py-2"><i class="fas fa-plus me-2"></i><?php echo __('Add Item to Collection'); ?></div>
  <div class="card-body">
    <form method="post" id="addItemForm">
      <input type="hidden" name="do" value="add_item">
      <div class="row g-3">
        <div class="col-md-5">
          <label class="form-label"><?php echo __('Search for item'); ?></label>
          <select name="object_id" id="itemSearchSelect" class="form-control" required><option value=""><?php echo __('Type to search...'); ?></option></select>
        </div>
        <div class="col-md-3">
          <label class="form-label"><?php echo __('Notes (optional)'); ?></label>
          <input type="text" name="notes" class="form-control" placeholder="<?php echo __('Add a note...'); ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label d-block">&nbsp;</label>
          <div class="form-check"><input type="checkbox" name="include_descendants" value="1" class="form-check-input" id="includeDescendants"><label class="form-check-label small" for="includeDescendants"><?php echo __('Include children'); ?></label></div>
        </div>
        <div class="col-md-2">
          <label class="form-label d-block">&nbsp;</label>
          <button type="submit" class="btn btn-success w-100"><i class="fas fa-plus me-1"></i><?php echo __('Add'); ?></button>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header"><i class="fas fa-list me-2"></i><?php echo __('Collection Items'); ?> (<?php echo count($collection->items ?? []); ?>)</div>
  <?php if (!empty($collection->items)): ?>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light"><tr><th><?php echo __('Title'); ?></th><th><?php echo __('Level'); ?></th><th width="30%"><?php echo __('Notes'); ?></th><th><?php echo __('Added'); ?></th><th width="100"><?php echo __('Actions'); ?></th></tr></thead>
      <tbody>
      <?php foreach ($collection->items as $item): ?>
        <tr>
          <td><a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $item->slug]); ?>"><?php echo htmlspecialchars($item->object_title ?: 'Untitled'); ?></a><?php if (!empty($item->identifier)): ?><br><small class="text-muted"><?php echo htmlspecialchars($item->identifier); ?></small><?php endif; ?></td>
          <td><small><?php echo htmlspecialchars($item->level_of_description ?? '-'); ?></small></td>
          <td>
            <span class="item-notes-display" id="notes-display-<?php echo $item->object_id; ?>"><?php echo htmlspecialchars($item->notes ?: '-'); ?></span>
            <form class="item-notes-form d-none" id="notes-form-<?php echo $item->object_id; ?>" method="post">
              <input type="hidden" name="do" value="update_notes"><input type="hidden" name="object_id" value="<?php echo $item->object_id; ?>">
              <div class="input-group input-group-sm"><input type="text" name="notes" class="form-control" value="<?php echo htmlspecialchars($item->notes ?? ''); ?>"><button type="submit" class="btn btn-success"><i class="fas fa-check"></i></button><button type="button" class="btn btn-secondary cancel-notes-edit" data-id="<?php echo $item->object_id; ?>"><i class="fas fa-times"></i></button></div>
            </form>
          </td>
          <td><small><?php echo date('M j, Y', strtotime($item->created_at)); ?></small></td>
          <td>
            <button type="button" class="btn btn-sm btn-outline-primary edit-notes-btn" data-id="<?php echo $item->object_id; ?>"><i class="fas fa-edit"></i></button>
            <form method="post" class="d-inline"><input type="hidden" name="do" value="remove"><input type="hidden" name="object_id" value="<?php echo $item->object_id; ?>"><button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove?')"><i class="fas fa-times"></i></button></form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="card-body text-center text-muted py-5"><i class="fas fa-inbox fa-3x mb-3 opacity-50"></i><p class="mb-0"><?php echo __('This collection is empty. Use the search above to add items.'); ?></p></div>
  <?php endif; ?>
</div>

<div class="modal fade" id="editCollectionModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post"><input type="hidden" name="do" value="update">
  <div class="modal-header"><h5 class="modal-title"><i class="fas fa-edit me-2"></i><?php echo __('Edit Collection'); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="mb-3"><label class="form-label"><?php echo __('Name'); ?> *</label><input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($collection->name); ?>" required></div>
    <div class="mb-3"><label class="form-label"><?php echo __('Description'); ?></label><textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($collection->description ?? ''); ?></textarea></div>
    <div class="form-check"><input type="checkbox" name="is_public" value="1" class="form-check-input" id="is_public" <?php echo $collection->is_public ? 'checked' : ''; ?>><label class="form-check-label" for="is_public"><?php echo __('Public'); ?></label></div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i><?php echo __('Save'); ?></button></div>
</form></div></div></div>

<div class="modal fade" id="deleteCollectionModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post"><input type="hidden" name="do" value="delete">
  <div class="modal-header bg-danger text-white"><h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i><?php echo __('Delete'); ?></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><p><?php echo __('Delete this collection?'); ?></p><p class="text-danger"><strong><?php echo htmlspecialchars($collection->name); ?></strong></p></div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button><button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i><?php echo __('Delete'); ?></button></div>
</form></div></div></div>

<link href="/plugins/ahgThemeB5Plugin/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="/plugins/ahgThemeB5Plugin/js/tom-select.complete.min.js"></script>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  new TomSelect('#itemSearchSelect', {
    valueField: 'id', labelField: 'title', searchField: ['title', 'identifier'],
    placeholder: '<?php echo __("Type to search..."); ?>', loadThrottle: 300,
    load: function(query, callback) {
      if (!query.length || query.length < 2) return callback();
      fetch('<?php echo url_for(["module" => "research", "action" => "searchItems"]); ?>?q=' + encodeURIComponent(query))
        .then(function(r) { return r.json(); })
        .then(function(j) { callback(j.items || []); })
        .catch(function() { callback(); });
    },
    render: {
      option: function(item, escape) { return '<div class="py-1"><strong>' + escape(item.title) + '</strong>' + (item.identifier ? '<br><small class="text-muted">' + escape(item.identifier) + '</small>' : '') + (item.has_children ? '<br><small class="text-info"><i class="fas fa-sitemap"></i> Has children</small>' : '') + '</div>'; },
      item: function(item, escape) { return '<div>' + escape(item.title) + '</div>'; }
    }
  });
  document.querySelectorAll('.edit-notes-btn').forEach(function(btn) {
    btn.addEventListener('click', function() { var id = this.dataset.id; document.getElementById('notes-display-' + id).classList.add('d-none'); document.getElementById('notes-form-' + id).classList.remove('d-none'); });
  });
  document.querySelectorAll('.cancel-notes-edit').forEach(function(btn) {
    btn.addEventListener('click', function() { var id = this.dataset.id; document.getElementById('notes-display-' + id).classList.remove('d-none'); document.getElementById('notes-form-' + id).classList.add('d-none'); });
  });
});
</script>
<?php end_slot() ?>
