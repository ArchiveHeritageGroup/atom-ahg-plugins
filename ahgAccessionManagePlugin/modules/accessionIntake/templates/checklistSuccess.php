<?php decorate_with('layout_1col'); ?>

<?php
  $rawAccession          = $sf_data->getRaw('accession');
  $rawChecklist          = $sf_data->getRaw('checklist');
  $rawChecklistProgress  = $sf_data->getRaw('checklistProgress');
  $rawChecklistTemplates = $sf_data->getRaw('checklistTemplates');

  $accId      = $rawAccession->id ?? $rawAccession->accession_id ?? 0;
  $identifier = $rawAccession->identifier ?? '--';

  $checklistArr = is_array($rawChecklist) ? $rawChecklist : [];
  $progressArr  = is_array($rawChecklistProgress) ? $rawChecklistProgress : (array) $rawChecklistProgress;
  $templatesArr = is_array($rawChecklistTemplates) ? $rawChecklistTemplates : [];

  $clTotal     = $progressArr['total'] ?? 0;
  $clCompleted = $progressArr['completed'] ?? 0;
  $clPct       = $progressArr['percent'] ?? 0;
?>

<?php slot('title'); ?>
  <h1>
    <i class="fas fa-tasks me-2"></i><?php echo __('Checklist'); ?>
    <small class="text-muted fs-5"><?php echo htmlspecialchars($identifier); ?></small>
  </h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="<?php echo url_for('@accession_intake_queue'); ?>"><?php echo __('Intake queue'); ?></a>
      </li>
      <li class="breadcrumb-item">
        <a href="<?php echo url_for('@accession_intake_detail?id=' . $accId); ?>"><?php echo htmlspecialchars($identifier); ?></a>
      </li>
      <li class="breadcrumb-item active"><?php echo __('Checklist'); ?></li>
    </ol>
  </nav>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <!-- Progress bar -->
  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">
          <?php echo __('Progress'); ?>: <?php echo $clCompleted; ?>/<?php echo $clTotal; ?>
        </h5>
        <?php $barColor = $clPct >= 100 ? 'success' : ($clPct >= 50 ? 'info' : 'warning'); ?>
        <span class="badge bg-<?php echo $barColor; ?> fs-6">
          <?php echo $clPct; ?>%
        </span>
      </div>
      <div class="progress" style="height: 24px;">
        <div class="progress-bar bg-<?php echo $barColor; ?>"
             role="progressbar"
             style="width: <?php echo $clPct; ?>%"
             aria-valuenow="<?php echo $clPct; ?>"
             aria-valuemin="0"
             aria-valuemax="100">
          <?php echo $clPct; ?>%
        </div>
      </div>
    </div>
  </div>

  <!-- Apply template -->
  <?php if (count($templatesArr) > 0): ?>
    <div class="card mb-3">
      <div class="card-header">
        <i class="fas fa-copy me-1"></i><?php echo __('Apply checklist template'); ?>
      </div>
      <div class="card-body">
        <div class="d-flex gap-2">
          <select id="checklist-template-select" class="form-select" style="max-width: 400px;">
            <option value=""><?php echo __('Select a checklist template...'); ?></option>
            <?php foreach ($templatesArr as $tpl): ?>
              <option value="<?php echo htmlspecialchars($tpl->id); ?>">
                <?php echo htmlspecialchars($tpl->name); ?>
                <?php if (!empty($tpl->description)): ?>
                  - <?php echo htmlspecialchars($tpl->description); ?>
                <?php endif; ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button type="button" id="apply-template-btn" class="btn btn-primary">
            <i class="fas fa-copy me-1"></i><?php echo __('Apply'); ?>
          </button>
        </div>
        <small class="text-muted mt-1 d-block">
          <?php echo __('Applying a template will add items from the selected template. Existing items are preserved.'); ?>
        </small>
      </div>
    </div>
  <?php endif; ?>

  <!-- Checklist items -->
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><i class="fas fa-list-ul me-1"></i><?php echo __('Checklist items'); ?></span>
      <span class="text-muted"><?php echo $clCompleted; ?> / <?php echo $clTotal; ?> <?php echo __('completed'); ?></span>
    </div>
    <div class="card-body p-0">
      <?php if (count($checklistArr) > 0): ?>
        <div class="list-group list-group-flush">
          <?php foreach ($checklistArr as $item): ?>
            <?php $completed = !empty($item->is_completed); ?>
            <div class="list-group-item d-flex align-items-start py-3">
              <div class="form-check me-3 mt-1">
                <input class="form-check-input checklist-toggle"
                       type="checkbox"
                       id="checklist-item-<?php echo htmlspecialchars($item->id); ?>"
                       data-item-id="<?php echo htmlspecialchars($item->id); ?>"
                       <?php echo $completed ? 'checked' : ''; ?>>
              </div>
              <div class="flex-grow-1">
                <label for="checklist-item-<?php echo htmlspecialchars($item->id); ?>"
                       class="form-check-label d-block <?php echo $completed ? 'text-decoration-line-through text-muted' : 'fw-semibold'; ?>">
                  <?php echo htmlspecialchars($item->label ?? $item->item_label ?? ''); ?>
                </label>
                <?php if ($completed): ?>
                  <small class="text-muted">
                    <i class="fas fa-check text-success me-1"></i>
                    <?php echo __('Completed by'); ?>
                    <strong><?php echo htmlspecialchars($item->completed_by_name ?? '--'); ?></strong>
                    <?php if (!empty($item->completed_at)): ?>
                      <?php echo __('on'); ?> <?php echo date('d M Y H:i', strtotime($item->completed_at)); ?>
                    <?php endif; ?>
                  </small>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="text-center py-5 text-muted">
          <i class="fas fa-tasks fa-3x mb-3"></i>
          <p class="mb-0"><?php echo __('No checklist items. Apply a template to get started.'); ?></p>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php end_slot(); ?>

<?php slot('after-content'); ?>
  <section class="actions mb-3">
    <a href="<?php echo url_for('@accession_intake_detail?id=' . $accId); ?>" class="btn atom-btn-outline-light">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to intake detail'); ?>
    </a>
  </section>
<?php end_slot(); ?>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  // Checklist item toggle
  document.querySelectorAll('.checklist-toggle').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
      var itemId = this.dataset.itemId;
      var cb = this;

      fetch('<?php echo url_for("@accession_api_checklist_toggle?id="); ?>' + itemId, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(function(resp) { return resp.json(); })
      .then(function(data) {
        if (data.success) {
          location.reload();
        } else {
          alert('<?php echo __("Failed to toggle checklist item."); ?>');
          cb.checked = !cb.checked;
        }
      })
      .catch(function() {
        alert('<?php echo __("An error occurred."); ?>');
        cb.checked = !cb.checked;
      });
    });
  });

  // Apply checklist template
  var applyBtn = document.getElementById('apply-template-btn');
  if (applyBtn) {
    applyBtn.addEventListener('click', function() {
      var templateId = document.getElementById('checklist-template-select').value;
      if (!templateId) {
        alert('<?php echo __("Please select a template."); ?>');
        return;
      }

      if (!confirm('<?php echo __("Apply this template? New items will be added to the checklist."); ?>')) {
        return;
      }

      fetch('<?php echo url_for("@accession_api_checklist_apply"); ?>', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'accession_id=<?php echo $accId; ?>&template_id=' + templateId
      })
      .then(function(resp) { return resp.json(); })
      .then(function(data) {
        if (data.success) {
          location.reload();
        } else {
          alert('<?php echo __("Failed to apply template."); ?>');
        }
      })
      .catch(function() {
        alert('<?php echo __("An error occurred."); ?>');
      });
    });
  }
});
</script>
