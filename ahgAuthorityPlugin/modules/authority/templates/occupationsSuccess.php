<?php decorate_with('layout_1col'); ?>

<?php
  $rawActor       = $sf_data->getRaw('actor');
  $rawOccupations = $sf_data->getRaw('occupations');

  $actor       = is_object($rawActor) ? $rawActor : (object) $rawActor;
  $occupations = is_array($rawOccupations) ? $rawOccupations : [];
?>

<?php slot('title'); ?>
  <h1><i class="fas fa-briefcase me-2"></i><?php echo __('Structured Occupations'); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="<?php echo url_for('@ahg_authority_dashboard'); ?>"><?php echo __('Authority Dashboard'); ?></a>
      </li>
      <li class="breadcrumb-item">
        <a href="/<?php echo $actor->slug ?? ''; ?>"><?php echo htmlspecialchars($actor->name ?? ''); ?></a>
      </li>
      <li class="breadcrumb-item active"><?php echo __('Occupations'); ?></li>
    </ol>
  </nav>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between">
      <span><i class="fas fa-briefcase me-1"></i><?php echo __('Occupations'); ?></span>
      <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addOccupationModal">
        <i class="fas fa-plus me-1"></i><?php echo __('Add'); ?>
      </button>
    </div>
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th><?php echo __('Occupation'); ?></th>
            <th><?php echo __('From'); ?></th>
            <th><?php echo __('To'); ?></th>
            <th><?php echo __('Notes'); ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($occupations)): ?>
            <tr><td colspan="5" class="text-center text-muted py-3"><?php echo __('No occupations recorded.'); ?></td></tr>
          <?php else: ?>
            <?php foreach ($occupations as $occ): ?>
              <tr>
                <td>
                  <?php if ($occ->term_name): ?>
                    <span class="badge bg-info"><?php echo htmlspecialchars($occ->term_name); ?></span>
                  <?php endif; ?>
                  <?php if ($occ->occupation_text): ?>
                    <?php echo htmlspecialchars($occ->occupation_text); ?>
                  <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($occ->date_from ?? ''); ?></td>
                <td><?php echo htmlspecialchars($occ->date_to ?? ''); ?></td>
                <td><small><?php echo htmlspecialchars($occ->notes ?? ''); ?></small></td>
                <td>
                  <button class="btn btn-sm btn-outline-danger btn-delete-occ" data-id="<?php echo $occ->id; ?>">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Add Occupation Modal -->
  <div class="modal fade" id="addOccupationModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><?php echo __('Add Occupation'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Occupation (free text)'); ?></label>
            <input type="text" id="occ-text" class="form-control">
          </div>
          <div class="row g-2 mb-3">
            <div class="col-md-6">
              <label class="form-label"><?php echo __('Date from'); ?></label>
              <input type="text" id="occ-from" class="form-control" placeholder="YYYY">
            </div>
            <div class="col-md-6">
              <label class="form-label"><?php echo __('Date to'); ?></label>
              <input type="text" id="occ-to" class="form-control" placeholder="YYYY">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Notes'); ?></label>
            <textarea id="occ-notes" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="button" class="btn btn-primary" id="btn-save-occ">
            <i class="fas fa-save me-1"></i><?php echo __('Save'); ?>
          </button>
        </div>
      </div>
    </div>
  </div>

<?php end_slot(); ?>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
var actorId = <?php echo (int) $actor->id; ?>;

document.getElementById('btn-save-occ').addEventListener('click', function() {
  var data = new FormData();
  data.append('actor_id', actorId);
  data.append('occupation_text', document.getElementById('occ-text').value);
  data.append('date_from', document.getElementById('occ-from').value);
  data.append('date_to', document.getElementById('occ-to').value);
  data.append('notes', document.getElementById('occ-notes').value);

  fetch('<?php echo url_for('@ahg_authority_occupation_save'); ?>', { method: 'POST', body: data })
    .then(function(r) { return r.json(); })
    .then(function(d) { if (d.success) location.reload(); });
});

document.querySelectorAll('.btn-delete-occ').forEach(function(btn) {
  btn.addEventListener('click', function() {
    if (!confirm('<?php echo __('Delete this occupation?'); ?>')) return;
    fetch('/api/authority/occupation/' + this.dataset.id + '/delete', { method: 'POST' })
      .then(function(r) { return r.json(); })
      .then(function(d) { if (d.success) location.reload(); });
  });
});
</script>
