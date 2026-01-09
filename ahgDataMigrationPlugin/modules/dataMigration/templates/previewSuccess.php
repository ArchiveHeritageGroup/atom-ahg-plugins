<?php
/**
 * Data Migration - Preview transformed data
 */

// Get mapped field names for header
$mappedFields = [];
foreach ($mapping as $m) {
    if (!empty($m['include']) && !empty($m['atom_field'])) {
        $mappedFields[$m['atom_field']] = true;
    }
}
$mappedFields = array_keys($mappedFields);
?>

<div class="container-xxl py-4">
  <div class="row mb-4">
    <div class="col">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'admin', 'action' => 'index']) ?>">Admin</a></li>
          <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'index']) ?>">Data Migration</a></li>
          <li class="breadcrumb-item active">Preview</li>
        </ol>
      </nav>
      <h1><i class="bi bi-eye me-2"></i>Preview Import</h1>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card bg-primary text-white">
        <div class="card-body text-center">
          <h3><?php echo number_format($totalRows) ?></h3>
          <div>Total Records</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-success text-white">
        <div class="card-body text-center">
          <h3><?php echo count($mappedFields) ?></h3>
          <div>Mapped Fields</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-info text-white">
        <div class="card-body text-center">
          <h3><?php echo htmlspecialchars($targetTypeLabels[$targetType] ?? $targetType) ?></h3>
          <div>Target Type</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-secondary text-white">
        <div class="card-body text-center">
          <h3><?php echo count($previewData) ?></h3>
          <div>Preview Rows</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Preview Table -->
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0"><i class="bi bi-table me-2"></i>Transformed Data Preview</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped table-bordered mb-0">
          <thead class="table-dark">
            <tr>
              <th>#</th>
              <?php foreach ($mappedFields as $field): ?>
                <th><?php echo htmlspecialchars($field) ?></th>
              <?php endforeach ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($previewData as $row): ?>
              <tr>
                <td><?php echo $row['row'] ?></td>
                <?php foreach ($mappedFields as $field): ?>
                  <td>
                    <?php 
                    $val = $row['mapped'][$field] ?? '';
                    if (strlen($val) > 100) {
                      $val = substr($val, 0, 100) . '...';
                    }
                    echo nl2br(htmlspecialchars($val));
                    ?>
                  </td>
                <?php endforeach ?>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Import Options -->
  <form action="<?php echo url_for(['module' => 'dataMigration', 'action' => 'execute']) ?>" method="post">
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-gear me-2"></i>Import Options</h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Match Existing Records By</label>
            <select name="match_field" class="form-select">
              <option value="">-- Don't match (create all new) --</option>
              <option value="identifier">Identifier</option>
              <option value="legacyId">Legacy ID</option>
              <option value="title">Title</option>
              <option value="slug">Slug</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">When Record Exists</label>
            <select name="update_mode" class="form-select">
              <option value="skip">Skip (don't update)</option>
              <option value="update">Update existing record</option>
              <option value="replace">Replace entirely</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Output Mode</label>
            <select name="output_mode" class="form-select">
              <option value="import">Import to Database</option>
              <option value="csv">Export to AtoM CSV</option>
              <option value="preview">Preview Only (no changes)</option>
            </select>
          </div>
        </div>
        
        <?php if ($targetType === 'information_object'): ?>
        <div class="row g-3 mt-2">
          <div class="col-md-4">
            <label class="form-label">Publication Status</label>
            <select name="publication_status" class="form-select">
              <option value="draft">Draft</option>
              <option value="published">Published</option>
            </select>
          </div>
        </div>
        <?php endif ?>
      </div>
    </div>

    <div class="d-flex justify-content-between">
      <a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'map']) ?>" class="btn btn-secondary btn-lg">
        <i class="bi bi-arrow-left me-1"></i>Back to Mapping
      </a>
      <button type="submit" class="btn btn-success btn-lg">
        <i class="bi bi-cloud-upload me-1"></i>Execute Import (<?php echo number_format($totalRows) ?> records)
      </button>
    </div>
  </form>
</div>
