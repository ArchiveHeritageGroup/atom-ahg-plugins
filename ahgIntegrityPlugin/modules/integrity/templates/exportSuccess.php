<?php
$repositories = $sf_data->getRaw('repositories') ?: [];
?>

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-download me-2"></i><?php echo __('Export Ledger'); ?></h1>
    <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('Dashboard'); ?>
    </a>
  </div>

  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0"><?php echo __('Export Filters'); ?></h5></div>
    <div class="card-body">
      <form id="exportForm" class="row g-3">
        <div class="col-md-3">
          <label class="form-label"><?php echo __('Date From'); ?></label>
          <input type="date" name="date_from" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label"><?php echo __('Date To'); ?></label>
          <input type="date" name="date_to" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label"><?php echo __('Repository'); ?></label>
          <select name="repository_id" class="form-select">
            <option value=""><?php echo __('All'); ?></option>
            <?php foreach ($repositories as $repo): ?>
              <option value="<?php echo $repo->id; ?>"><?php echo htmlspecialchars($repo->name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label"><?php echo __('Outcome'); ?></label>
          <select name="outcome" class="form-select">
            <option value=""><?php echo __('All'); ?></option>
            <option value="pass">pass</option>
            <option value="mismatch">mismatch</option>
            <option value="missing">missing</option>
            <option value="unreadable">unreadable</option>
            <option value="error">error</option>
            <option value="no_baseline">no_baseline</option>
          </select>
        </div>
      </form>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-body text-center">
          <i class="fas fa-file-csv fa-3x text-success mb-3"></i>
          <h5><?php echo __('CSV Export'); ?></h5>
          <p class="text-muted"><?php echo __('Download the verification ledger as a CSV file with all columns.'); ?></p>
          <button type="button" class="btn btn-success" onclick="downloadExport('csv')">
            <i class="fas fa-download me-1"></i><?php echo __('Download CSV'); ?>
          </button>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-body text-center">
          <i class="fas fa-file-archive fa-3x text-primary mb-3"></i>
          <h5><?php echo __('Auditor Pack (ZIP)'); ?></h5>
          <p class="text-muted"><?php echo __('Download a ZIP containing summary.html, exceptions.csv, and config-snapshot.json.'); ?></p>
          <button type="button" class="btn btn-primary" onclick="downloadExport('auditor')">
            <i class="fas fa-download me-1"></i><?php echo __('Download Auditor Pack'); ?>
          </button>
        </div>
      </div>
    </div>
  </div>
</main>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function downloadExport(type) {
  var form = document.getElementById('exportForm');
  var params = new URLSearchParams(new FormData(form)).toString();
  var baseUrl = type === 'csv'
    ? '<?php echo url_for(["module" => "integrity", "action" => "exportCsv"]); ?>'
    : '<?php echo url_for(["module" => "integrity", "action" => "exportAuditor"]); ?>';
  window.location.href = baseUrl + (params ? '?' + params : '');
}
</script>
