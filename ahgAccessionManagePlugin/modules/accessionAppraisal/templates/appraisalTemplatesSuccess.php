<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Appraisal Templates'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<?php
  $flash = $sf_user->getFlash('notice', '');
  $sectorLabels = [
      'archive' => __('Archive'),
      'library' => __('Library'),
      'museum' => __('Museum'),
      'gallery' => __('Gallery'),
      'dam' => __('DAM'),
  ];
  $templates = $sf_data->getRaw('templates');
?>

<?php if ($flash): ?>
<div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
  <?php echo htmlspecialchars($flash); ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'accessionManage', 'action' => 'dashboard']); ?>"><?php echo __('Accessions'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Appraisal Templates'); ?></li>
  </ol>
</nav>

<div class="row">
  <div class="col-lg-7">
    <!-- Existing Templates -->
    <div class="card mb-4">
      <div class="card-header">
        <i class="fas fa-file-alt me-2"></i><?php echo __('Templates'); ?>
      </div>
      <div class="card-body p-0">
        <?php if (is_array($templates) && count($templates) > 0): ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Name'); ?></th>
                <th><?php echo __('Sector'); ?></th>
                <th class="text-center"><?php echo __('Default'); ?></th>
                <th class="text-center"><?php echo __('Criteria'); ?></th>
                <th><?php echo __('Actions'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($templates as $t): ?>
              <?php
                $criteriaData = json_decode($t->criteria ?? '[]', true);
                $criteriaCount = is_array($criteriaData) ? count($criteriaData) : 0;
                $descText = $t->description ?? '';
                $truncDesc = mb_strlen($descText) > 60 ? mb_substr($descText, 0, 60) . '...' : $descText;
              ?>
              <tr>
                <td>
                  <strong><?php echo htmlspecialchars($t->name); ?></strong>
                  <?php if (!empty($descText)): ?>
                  <br><small class="text-muted"><?php echo htmlspecialchars($truncDesc); ?></small>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($t->sector)): ?>
                  <span class="badge bg-secondary"><?php echo $sectorLabels[$t->sector] ?? ucfirst($t->sector); ?></span>
                  <?php else: ?>
                  <span class="text-muted"><?php echo __('All'); ?></span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <?php if ($t->is_default): ?>
                  <span class="badge bg-success"><i class="fas fa-check"></i></span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <span class="badge bg-info"><?php echo $criteriaCount; ?></span>
                </td>
                <td>
                  <form method="post" action="<?php echo url_for('@accession_appraisal_templates'); ?>" class="d-inline" onsubmit="return confirm('<?php echo __('Delete this template?'); ?>');">
                    <input type="hidden" name="form_action" value="delete">
                    <input type="hidden" name="template_id" value="<?php echo htmlspecialchars($t->id); ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="<?php echo __('Delete'); ?>">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
              <!-- Expandable criteria detail -->
              <?php if ($criteriaCount > 0): ?>
              <tr class="criteria-row" id="criteria_<?php echo htmlspecialchars($t->id); ?>" style="display:none;">
                <td colspan="5" class="bg-light">
                  <div class="px-3 py-2">
                    <strong class="small text-uppercase text-muted"><?php echo __('Criteria:'); ?></strong>
                    <table class="table table-sm table-borderless mb-0 mt-1">
                      <thead>
                        <tr class="text-muted small">
                          <th><?php echo __('Criterion'); ?></th>
                          <th style="width:80px;"><?php echo __('Weight'); ?></th>
                          <th><?php echo __('Description'); ?></th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($criteriaData as $c): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($c['criterion_name'] ?? ''); ?></td>
                          <td><?php echo number_format($c['weight'] ?? 1.00, 2); ?></td>
                          <td class="text-muted"><?php echo htmlspecialchars($c['description'] ?? ''); ?></td>
                        </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </td>
              </tr>
              <?php endif; ?>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <div class="text-center py-4 text-muted">
          <i class="fas fa-file-alt fa-2x mb-2 d-block"></i>
          <?php echo __('No templates defined yet. Create one using the form.'); ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <!-- Create New Template -->
    <div class="card mb-4 border-success">
      <div class="card-header bg-success text-white">
        <i class="fas fa-plus-circle me-2"></i><?php echo __('Create New Template'); ?>
      </div>
      <div class="card-body">
        <form method="post" action="<?php echo url_for('@accession_appraisal_templates'); ?>" id="templateForm">
          <input type="hidden" name="form_action" value="create">

          <div class="mb-3">
            <label class="form-label"><?php echo __('Name'); ?> <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required placeholder="<?php echo __('e.g. Archival Significance Assessment'); ?>">
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Description'); ?></label>
            <textarea name="description" class="form-control" rows="2" placeholder="<?php echo __('Brief description of when to use this template...'); ?>"></textarea>
          </div>

          <div class="row mb-3">
            <div class="col-md-7">
              <label class="form-label"><?php echo __('Sector'); ?></label>
              <select name="sector" class="form-select">
                <option value=""><?php echo __('-- All Sectors --'); ?></option>
                <?php foreach ($sectorLabels as $val => $label): ?>
                <option value="<?php echo htmlspecialchars($val); ?>"><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-5">
              <label class="form-label">&nbsp;</label>
              <div class="form-check mt-2">
                <input type="checkbox" name="is_default" value="1" class="form-check-input" id="isDefault">
                <label class="form-check-label" for="isDefault"><?php echo __('Default template'); ?></label>
              </div>
            </div>
          </div>

          <hr>

          <div class="d-flex justify-content-between align-items-center mb-3">
            <label class="form-label fw-bold mb-0"><?php echo __('Criteria'); ?></label>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addCriterionBtn">
              <i class="fas fa-plus me-1"></i><?php echo __('Add Criterion'); ?>
            </button>
          </div>

          <div id="criteriaContainer">
            <!-- Initial criterion row -->
            <div class="criterion-row card card-body bg-light mb-2 p-3">
              <div class="row g-2">
                <div class="col-md-5">
                  <input type="text" name="criterion_names[]" class="form-control form-control-sm" placeholder="<?php echo __('Criterion name'); ?>" required>
                </div>
                <div class="col-md-2">
                  <input type="number" name="criterion_weights[]" class="form-control form-control-sm" step="0.01" min="0" value="1.00" placeholder="<?php echo __('Weight'); ?>">
                </div>
                <div class="col-md-4">
                  <input type="text" name="criterion_descriptions[]" class="form-control form-control-sm" placeholder="<?php echo __('Description'); ?>">
                </div>
                <div class="col-md-1 text-end">
                  <button type="button" class="btn btn-sm btn-outline-danger remove-criterion-btn" title="<?php echo __('Remove'); ?>">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <hr class="mt-4">

          <button type="submit" class="btn btn-success w-100">
            <i class="fas fa-save me-1"></i><?php echo __('Create Template'); ?>
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  // ---- Add criterion row ----
  var container = document.getElementById('criteriaContainer');
  var addBtn = document.getElementById('addCriterionBtn');

  addBtn.addEventListener('click', function() {
    var row = document.createElement('div');
    row.className = 'criterion-row card card-body bg-light mb-2 p-3';
    row.innerHTML =
      '<div class="row g-2">' +
        '<div class="col-md-5">' +
          '<input type="text" name="criterion_names[]" class="form-control form-control-sm" placeholder="<?php echo __('Criterion name'); ?>" required>' +
        '</div>' +
        '<div class="col-md-2">' +
          '<input type="number" name="criterion_weights[]" class="form-control form-control-sm" step="0.01" min="0" value="1.00" placeholder="<?php echo __('Weight'); ?>">' +
        '</div>' +
        '<div class="col-md-4">' +
          '<input type="text" name="criterion_descriptions[]" class="form-control form-control-sm" placeholder="<?php echo __('Description'); ?>">' +
        '</div>' +
        '<div class="col-md-1 text-end">' +
          '<button type="button" class="btn btn-sm btn-outline-danger remove-criterion-btn" title="<?php echo __('Remove'); ?>">' +
            '<i class="fas fa-times"></i>' +
          '</button>' +
        '</div>' +
      '</div>';
    container.appendChild(row);
    bindRemoveButtons();
  });

  // ---- Remove criterion row ----
  function bindRemoveButtons() {
    var buttons = container.querySelectorAll('.remove-criterion-btn');
    buttons.forEach(function(btn) {
      btn.onclick = function() {
        var rows = container.querySelectorAll('.criterion-row');
        if (rows.length > 1) {
          btn.closest('.criterion-row').remove();
        }
      };
    });
  }
  bindRemoveButtons();

  // ---- Toggle criteria detail rows in template list ----
  var badges = document.querySelectorAll('.badge.bg-info');
  badges.forEach(function(badge) {
    var row = badge.closest('tr');
    if (row) {
      badge.style.cursor = 'pointer';
      badge.title = '<?php echo __('Click to expand criteria'); ?>';
      badge.addEventListener('click', function() {
        var nextRow = row.nextElementSibling;
        if (nextRow && nextRow.classList.contains('criteria-row')) {
          nextRow.style.display = nextRow.style.display === 'none' ? '' : 'none';
        }
      });
    }
  });
});
</script>
<?php end_slot(); ?>
