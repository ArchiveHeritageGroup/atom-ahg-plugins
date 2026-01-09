<?php use_helper('Date') ?>
<?php 
// Get raw arrays from Symfony escaper
$rawMapping = $sf_data->getRaw('mapping');
$mappedCount = 0;
if (is_array($rawMapping)) {
    foreach ($rawMapping as $m) {
        if (!empty($m['include']) && !empty($m['atom_field'])) {
            $mappedCount++;
        }
    }
}
?>

<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card">
        <div class="card-header bg-warning">
          <h5 class="mb-0">⚠ Confirm Import</h5>
        </div>
        <div class="card-body">
          <div class="alert alert-info">
            <h6>Import Summary</h6>
            <ul class="mb-0">
              <li><strong>File:</strong> <?php echo htmlspecialchars($filename) ?></li>
              <li><strong>Records:</strong> <?php echo number_format($rowCount) ?></li>
              <li><strong>Mapped fields:</strong> <?php echo $mappedCount ?></li>
            </ul>
          </div>
          
          <div class="alert alert-warning">
            <strong>Note:</strong> Database import functionality is being implemented. 
            For now, please use <strong>Export to AtoM CSV</strong> and import via AtoM's standard CSV import.
          </div>
          
          <div class="d-flex justify-content-between mt-4">
            <a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'map']) ?>" class="btn btn-secondary">
              ← Back to Mapping
            </a>
            <div>
              <a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'previewData']) ?>" class="btn btn-outline-info me-2">
                👁 Preview Data
              </a>
              <a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'exportCsv']) ?>" class="btn btn-primary">
                ⬇ Export AtoM CSV
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
