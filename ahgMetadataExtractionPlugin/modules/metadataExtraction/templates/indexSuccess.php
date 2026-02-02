<?php decorate_with('layout_1col.php') ?>

<?php slot('title') ?>
  <h1><?php echo __('Metadata Extraction'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>

<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><?php echo __('Digital Objects'); ?></h5>
    <div>
      <a href="<?php echo url_for(['module' => 'metadataExtraction', 'action' => 'status']) ?>" class="btn btn-outline-secondary btn-sm me-2">
        <i class="bi bi-info-circle me-1"></i><?php echo __('Status'); ?>
      </a>
      <?php if ($exifToolAvailable): ?>
        <a href="<?php echo url_for(['module' => 'metadataExtraction', 'action' => 'batchExtract']) ?>" class="btn btn-primary btn-sm">
          <i class="bi bi-lightning me-1"></i><?php echo __('Batch Extract'); ?>
        </a>
      <?php endif ?>
    </div>
  </div>
  <div class="card-body">

    <?php if ($sf_user->hasFlash('notice')): ?>
      <div class="alert alert-success"><?php echo $sf_user->getFlash('notice') ?></div>
    <?php endif ?>

    <?php if ($sf_user->hasFlash('error')): ?>
      <div class="alert alert-danger"><?php echo $sf_user->getFlash('error') ?></div>
    <?php endif ?>

    <?php if (!$exifToolAvailable): ?>
      <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong><?php echo __('ExifTool Not Available'); ?></strong>
        <p class="mb-0 mt-2"><?php echo __('ExifTool is not installed on this system. Install it with: <code>sudo apt install exiftool</code>'); ?></p>
      </div>
    <?php endif ?>

    <!-- Filters -->
    <form method="get" class="mb-4">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label"><?php echo __('MIME Type'); ?></label>
          <select name="mime_type" class="form-select form-select-sm">
            <option value=""><?php echo __('All types'); ?></option>
            <?php foreach ($mimeTypes as $mime): ?>
              <option value="<?php echo htmlspecialchars($mime) ?>" <?php echo $filterMimeType === $mime ? 'selected' : '' ?>>
                <?php echo htmlspecialchars($mime) ?>
              </option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label"><?php echo __('Has Metadata'); ?></label>
          <select name="extracted" class="form-select form-select-sm">
            <option value=""><?php echo __('All'); ?></option>
            <option value="yes" <?php echo $filterExtracted === 'yes' ? 'selected' : '' ?>><?php echo __('Yes - has metadata'); ?></option>
            <option value="no" <?php echo $filterExtracted === 'no' ? 'selected' : '' ?>><?php echo __('No - not extracted'); ?></option>
          </select>
        </div>
        <div class="col-md-4 d-flex align-items-end">
          <button type="submit" class="btn btn-secondary btn-sm me-2"><?php echo __('Filter'); ?></button>
          <a href="<?php echo url_for(['module' => 'metadataExtraction', 'action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm"><?php echo __('Clear'); ?></a>
        </div>
      </div>
    </form>

    <!-- Results -->
    <p class="text-muted small"><?php echo __('Showing %1% of %2% digital objects', ['%1%' => count($digitalObjects), '%2%' => $totalCount]); ?></p>

    <?php if (count($digitalObjects) > 0): ?>
      <div class="table-responsive">
        <table class="table table-striped table-hover">
          <thead>
            <tr>
              <th><?php echo __('ID'); ?></th>
              <th><?php echo __('File Name'); ?></th>
              <th><?php echo __('MIME Type'); ?></th>
              <th><?php echo __('Size'); ?></th>
              <th><?php echo __('Parent Record'); ?></th>
              <th><?php echo __('Metadata'); ?></th>
              <th><?php echo __('Actions'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($digitalObjects as $obj): ?>
              <tr>
                <td><?php echo $obj->id ?></td>
                <td>
                  <a href="<?php echo url_for(['module' => 'metadataExtraction', 'action' => 'view', 'id' => $obj->id]) ?>">
                    <?php echo htmlspecialchars($obj->name ?: basename($obj->path)) ?>
                  </a>
                </td>
                <td><code class="small"><?php echo htmlspecialchars($obj->mime_type ?? 'unknown') ?></code></td>
                <td><?php echo $obj->byte_size ? number_format($obj->byte_size / 1024, 1) . ' KB' : '-' ?></td>
                <td>
                  <?php if ($obj->information_object_id): ?>
                    <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'show', 'slug' => $obj->information_object_id]) ?>">
                      <?php echo htmlspecialchars(mb_substr($obj->record_title ?? 'Untitled', 0, 40)) ?>
                    </a>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif ?>
                </td>
                <td>
                  <?php if ($obj->metadata_count > 0): ?>
                    <span class="badge bg-success"><?php echo $obj->metadata_count ?> <?php echo __('fields'); ?></span>
                  <?php else: ?>
                    <span class="badge bg-secondary"><?php echo __('None'); ?></span>
                  <?php endif ?>
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <a href="<?php echo url_for(['module' => 'metadataExtraction', 'action' => 'view', 'id' => $obj->id]) ?>"
                       class="btn btn-outline-primary" title="<?php echo __('View metadata'); ?>">
                      <i class="bi bi-eye"></i>
                    </a>
                    <?php if ($exifToolAvailable): ?>
                      <button type="button" class="btn btn-outline-success extract-btn"
                              data-id="<?php echo $obj->id ?>" title="<?php echo __('Extract metadata'); ?>">
                        <i class="bi bi-download"></i>
                      </button>
                    <?php endif ?>
                  </div>
                </td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation">
          <ul class="pagination pagination-sm justify-content-center">
            <?php if ($page > 1): ?>
              <li class="page-item">
                <a class="page-link" href="<?php echo url_for(['module' => 'metadataExtraction', 'action' => 'index', 'page' => $page - 1, 'mime_type' => $filterMimeType, 'extracted' => $filterExtracted]) ?>">
                  <?php echo __('Previous'); ?>
                </a>
              </li>
            <?php endif ?>

            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
              <li class="page-item <?php echo $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="<?php echo url_for(['module' => 'metadataExtraction', 'action' => 'index', 'page' => $i, 'mime_type' => $filterMimeType, 'extracted' => $filterExtracted]) ?>">
                  <?php echo $i ?>
                </a>
              </li>
            <?php endfor ?>

            <?php if ($page < $totalPages): ?>
              <li class="page-item">
                <a class="page-link" href="<?php echo url_for(['module' => 'metadataExtraction', 'action' => 'index', 'page' => $page + 1, 'mime_type' => $filterMimeType, 'extracted' => $filterExtracted]) ?>">
                  <?php echo __('Next'); ?>
                </a>
              </li>
            <?php endif ?>
          </ul>
        </nav>
      <?php endif ?>

    <?php else: ?>
      <div class="alert alert-info">
        <?php echo __('No digital objects found matching your criteria.'); ?>
      </div>
    <?php endif ?>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.extract-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var id = this.dataset.id;
      var button = this;

      button.disabled = true;
      button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

      fetch('<?php echo url_for(['module' => 'metadataExtraction', 'action' => 'extract']) ?>?id=' + id, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          location.reload();
        } else {
          alert('Error: ' + (data.error || 'Unknown error'));
          button.disabled = false;
          button.innerHTML = '<i class="bi bi-download"></i>';
        }
      })
      .catch(err => {
        alert('Error: ' + err.message);
        button.disabled = false;
        button.innerHTML = '<i class="bi bi-download"></i>';
      });
    });
  });
});
</script>

<?php end_slot() ?>
