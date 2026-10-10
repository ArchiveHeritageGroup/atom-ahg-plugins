<?php
// Only show for authenticated researchers with approved status
$showButton = false;
$collections = [];
if ($sf_user->isAuthenticated()) {
    try {
        $userId = $sf_user->getAttribute('user_id');
        $researcher = \Illuminate\Database\Capsule\Manager::table('research_researcher')
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->first();
        if ($researcher) {
            $showButton = true;
            $collections = \Illuminate\Database\Capsule\Manager::table('research_collection')
                ->where('researcher_id', $researcher->id)
                ->orderBy('name')
                ->get()->toArray();
        }
    } catch (Exception $e) {
        \class_exists('AhgCore\\Core\\AhgLog') && \AhgCore\Core\AhgLog::swallowed($e, basename(__FILE__).':'.__LINE__);
        // Silently fail
    }
}
?>
<?php if ($showButton): ?>
<div class="dropdown d-inline-block">
  <button class="btn btn-sm btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="<?php echo __('Add to my evidence set'); ?>">
    <i class="fas fa-folder-plus me-1" aria-hidden="true"></i><?php echo __('Add to Evidence Set'); ?>
  </button>
  <ul class="dropdown-menu dropdown-menu-end">
    <li><h6 class="dropdown-header"><?php echo __('Add to Evidence Set'); ?></h6></li>
    <?php if (!empty($collections)): ?>
      <?php foreach ($collections as $col): ?>
        <li>
          <a class="dropdown-item add-to-collection-btn" href="#" 
             data-object-id="<?php echo $objectId; ?>" 
             data-collection-id="<?php echo $col->id; ?>">
            <i class="fas fa-folder me-2"></i><?php echo htmlspecialchars($col->name); ?>
          </a>
        </li>
      <?php endforeach; ?>
      <li><hr class="dropdown-divider"></li>
    <?php endif; ?>
    <li>
      <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#newCollectionModal-<?php echo (int) $objectId; ?>" data-object-id="<?php echo $objectId; ?>">
        <i class="fas fa-plus me-2 text-success"></i><?php echo __('New Evidence Set...'); ?>
      </a>
    </li>
  </ul>
</div>
<?php endif; ?>
<?php if ($showButton): ?>
<div id="collectionResult-<?php echo (int) $objectId; ?>" class="small mt-1" role="status" aria-live="polite"></div>

<?php // The button needed its own dialog and script: it referenced both but carried neither. ?>
<div class="modal fade" id="newCollectionModal-<?php echo (int) $objectId; ?>" tabindex="-1" aria-labelledby="newCollectionLabel-<?php echo (int) $objectId; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content new-collection-form" data-object-id="<?php echo (int) $objectId; ?>">
      <div class="modal-header">
        <h5 class="modal-title" id="newCollectionLabel-<?php echo (int) $objectId; ?>"><?php echo __('New Evidence Set'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo __('Close'); ?>"></button>
      </div>
      <div class="modal-body">
        <label class="form-label" for="newCollectionName-<?php echo (int) $objectId; ?>"><?php echo __('Name'); ?></label>
        <input type="text" class="form-control" id="newCollectionName-<?php echo (int) $objectId; ?>" name="name" required maxlength="255">
        <label class="form-label mt-2" for="newCollectionDesc-<?php echo (int) $objectId; ?>"><?php echo __('Description'); ?></label>
        <textarea class="form-control" id="newCollectionDesc-<?php echo (int) $objectId; ?>" name="description" rows="2"></textarea>
        <div class="form-text"><?php echo __('This record is added to the new evidence set.'); ?></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn atom-btn-outline-light" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
        <button type="submit" class="btn atom-btn-outline-success"><?php echo __('Create and add'); ?></button>
      </div>
    </form>
  </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function () {
  var oid = <?php echo (int) $objectId; ?>;
  // The dialog renders inside the sidebar, whose stacking context puts it under
  // Bootstrap's backdrop, so nothing in it could be clicked. Lift it to <body>.
  var dlg = document.getElementById('newCollectionModal-' + oid);
  if (dlg) { document.body.appendChild(dlg); }
  var out = document.getElementById('collectionResult-' + oid);
  var say = function (ok, msg) { out.innerHTML = '<div class="alert ' + (ok ? 'alert-success' : 'alert-warning') + ' py-1 px-2 mb-0"></div>'; out.firstChild.textContent = msg; };
  var post = function (url, data) {
    return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }, body: new URLSearchParams(data).toString() })
      .then(function (r) { return r.json(); });
  };
  document.querySelectorAll('.add-to-collection-btn[data-object-id="' + oid + '"]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      e.preventDefault();
      post(<?php echo json_encode(url_for('@research_ajax_add_to_collection')); ?>, { collection_id: a.dataset.collectionId, object_id: oid })
        .then(function (d) { say(d.success, d.success ? <?php echo json_encode(__('Added to the evidence set.')); ?> : (d.error || <?php echo json_encode(__('Could not add.')); ?>)); })
        .catch(function () { say(false, <?php echo json_encode(__('Could not add.')); ?>); });
    });
  });
  var form = document.querySelector('.new-collection-form[data-object-id="' + oid + '"]');
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      post(<?php echo json_encode(url_for('@research_ajax_create_collection')); ?>, { name: form.name.value, description: form.description.value, object_id: oid })
        .then(function (d) {
          if (window.bootstrap) { window.bootstrap.Modal.getOrCreateInstance(form.closest('.modal')).hide(); }
          say(d.success, d.success ? <?php echo json_encode(__('Evidence set created, with this record in it.')); ?> : (d.error || <?php echo json_encode(__('Could not create the evidence set.')); ?>));
        })
        .catch(function () { say(false, <?php echo json_encode(__('Could not create the evidence set.')); ?>); });
    });
  }
})();
</script>
<?php endif; ?>

