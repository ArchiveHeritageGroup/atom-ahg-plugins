<?php use_helper('Date') ?>
<?php // Rules moved out of style attributes: a CSP nonce covers <style>
      // elements and never an attribute, so under an enforcing policy every one
      // of these was dropped silently.
      $cspNonce = sfConfig::get('csp_nonce', ''); ?>
<style <?php echo $cspNonce ? preg_replace('/^nonce=/', 'nonce="', $cspNonce).'"' : ''; ?>>
  .datami-max-height-70vh-overflow-aut-3333 { max-height: 70vh; overflow: auto; }
</style>
<?php 
$rawData = $sf_data->getRaw('transformedData');
if (!is_array($rawData)) $rawData = [];

// Get all unique fields
$allFields = [];
foreach ($rawData as $row) {
    if (is_array($row)) {
        $allFields = array_merge($allFields, array_keys($row));
    }
}
$allFields = array_unique($allFields);
?>

<div class="container-fluid py-3">
  <div class="card">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">👁 Preview: <?php echo htmlspecialchars($filename) ?></h5>
      <span class="badge bg-light text-dark"><?php echo count($rawData) ?> records (showing max 50)</span>
    </div>
    <div class="card-body p-0">
      <?php if (empty($rawData)): ?>
        <div class="alert alert-warning m-3">No data to preview. Check your field mappings.</div>
      <?php else: ?>
      <div class="table-responsive datami-max-height-70vh-overflow-aut-3333" >
        <table class="table table-striped table-hover table-sm mb-0">
          <thead class="table-light sticky-top">
            <tr>
              <th>#</th>
              <?php foreach ($allFields as $field): ?>
                <th class="small"><?php echo htmlspecialchars($field) ?></th>
              <?php endforeach ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rawData as $i => $row): ?>
              <tr>
                <td class="text-muted small"><?php echo $i + 1 ?></td>
                <?php foreach ($allFields as $field): ?>
                  <td class="small">
                    <?php 
                    $val = is_array($row) ? ($row[$field] ?? '') : '';
                    echo htmlspecialchars(mb_substr($val, 0, 100));
                    if (mb_strlen($val) > 100) echo '...';
                    ?>
                  </td>
                <?php endforeach ?>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
      <?php endif ?>
    </div>
    <div class="card-footer d-flex justify-content-between">
      <a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'map']) ?>" class="btn btn-secondary">
        ← Back to Mapping
      </a>
      <div>
        <a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'exportCsv']) ?>" class="btn btn-outline-primary me-2">
          ⬇ Export CSV
        </a>
        <a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'exportEad']) ?>" class="btn btn-outline-secondary me-2">
          ⬇ Export EAD
        </a>
        <a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'execute']) ?>" class="btn btn-success">
          ✓ Import to Database
        </a>
      </div>
    </div>
  </div>
</div>
