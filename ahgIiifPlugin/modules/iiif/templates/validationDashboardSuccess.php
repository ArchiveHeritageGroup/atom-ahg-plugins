<?php use_helper('Url'); ?>
<?php $n = sfConfig::get('csp_nonce', ''); $nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : ''; ?>

<div class="container-fluid py-4">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo url_for('admin/plugins'); ?>">Admin</a></li>
      <li class="breadcrumb-item active">IIIF Validation</li>
    </ol>
  </nav>

  <h1 class="h3 mb-4"><i class="fas fa-check-double me-2"></i>IIIF Compliance Dashboard</h1>

  <!-- Stats Cards -->
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card text-center border-primary">
        <div class="card-body py-3">
          <h3 class="text-primary mb-0"><?php echo $stats['total'] ?? 0; ?></h3>
          <small class="text-muted">Objects Validated</small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center border-success">
        <div class="card-body py-3">
          <h3 class="text-success mb-0"><?php echo $stats['passed'] ?? 0; ?></h3>
          <small class="text-muted">Fully Compliant</small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center border-danger">
        <div class="card-body py-3">
          <h3 class="text-danger mb-0"><?php echo $stats['failed'] ?? 0; ?></h3>
          <small class="text-muted">Issues Found</small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center border-warning">
        <div class="card-body py-3">
          <h3 class="text-warning mb-0"><?php echo $stats['warning'] ?? 0; ?></h3>
          <small class="text-muted">Warnings</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Recent Failures -->
  <?php if (!empty($stats['recent_failures'])): ?>
  <div class="card mb-4">
    <div class="card-header bg-danger text-white">
      <h5 class="card-title mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Recent Failures</h5>
    </div>
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>Object</th>
            <th>Check</th>
            <th>Details</th>
            <th>When</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($stats['recent_failures'] as $f): ?>
          <tr>
            <td>
              <a href="<?php echo url_for("admin/iiif-validation/run/{$f->object_id}"); ?>">
                <?php echo htmlspecialchars($f->title ?? "ID: {$f->object_id}"); ?>
              </a>
            </td>
            <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($f->validation_type); ?></span></td>
            <td><small><?php echo htmlspecialchars($f->details ?? ''); ?></small></td>
            <td><small class="text-muted"><?php echo $f->validated_at; ?></small></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Objects with Digital Objects -->
  <div class="card">
    <div class="card-header">
      <h5 class="card-title mb-0"><i class="fas fa-images me-2"></i>Objects with Digital Objects</h5>
    </div>
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>Title</th>
            <th class="text-center">Digital Objects</th>
            <th class="text-center">Validate</th>
            <th class="text-center">Result</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentObjects as $obj): ?>
          <tr id="row-<?php echo $obj->object_id; ?>">
            <td>
              <?php if ($obj->slug): ?>
                <a href="<?php echo url_for("{$obj->slug}"); ?>" target="_blank">
                  <?php echo htmlspecialchars($obj->title ?? 'Untitled'); ?>
                </a>
              <?php else: ?>
                <?php echo htmlspecialchars($obj->title ?? 'Untitled'); ?>
              <?php endif; ?>
            </td>
            <td class="text-center"><span class="badge bg-info"><?php echo $obj->do_count; ?></span></td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-primary btn-validate" data-id="<?php echo $obj->object_id; ?>">
                <i class="fas fa-check-double"></i> Validate
              </button>
            </td>
            <td class="text-center validation-result" id="result-<?php echo $obj->object_id; ?>">
              <span class="text-muted">—</span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script <?php echo $nonceAttr; ?>>
document.querySelectorAll('.btn-validate').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = this.dataset.id;
        var resultCell = document.getElementById('result-' + id);
        resultCell.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        btn.disabled = true;

        fetch('/admin/iiif-validation/run/' + id, {credentials: 'same-origin'})
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var html = '';
            if (data.failed > 0) {
                html = '<span class="badge bg-danger">' + data.failed + ' failed</span> ';
            }
            if (data.warnings > 0) {
                html += '<span class="badge bg-warning text-dark">' + data.warnings + ' warnings</span> ';
            }
            if (data.passed > 0) {
                html += '<span class="badge bg-success">' + data.passed + ' passed</span>';
            }
            resultCell.innerHTML = html || '<span class="badge bg-success">All passed</span>';
            btn.disabled = false;
        })
        .catch(function() {
            resultCell.innerHTML = '<span class="badge bg-secondary">Error</span>';
            btn.disabled = false;
        });
    });
});
</script>
