<?php decorate_with('layout_1col.php') ?>

<?php slot('title') ?>
  <h1><?php echo __('Extracted Metadata'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>

<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">
      <i class="bi bi-file-earmark me-2"></i>
      <?php echo htmlspecialchars($digitalObject->name ?: basename($digitalObject->path)) ?>
    </h5>
    <div>
      <a href="<?php echo url_for(['module' => 'metadataExtraction', 'action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i><?php echo __('Back to List'); ?>
      </a>
    </div>
  </div>
  <div class="card-body">

    <!-- Digital Object Info -->
    <div class="row mb-4">
      <div class="col-md-6">
        <h6><?php echo __('Digital Object Details'); ?></h6>
        <table class="table table-sm">
          <tr>
            <th class="w-25"><?php echo __('ID'); ?></th>
            <td><?php echo $digitalObject->id ?></td>
          </tr>
          <tr>
            <th><?php echo __('File Name'); ?></th>
            <td><?php echo htmlspecialchars($digitalObject->name ?: basename($digitalObject->path)) ?></td>
          </tr>
          <tr>
            <th><?php echo __('MIME Type'); ?></th>
            <td><code><?php echo htmlspecialchars($digitalObject->mime_type ?? 'unknown') ?></code></td>
          </tr>
          <tr>
            <th><?php echo __('Size'); ?></th>
            <td><?php echo $digitalObject->byte_size ? number_format($digitalObject->byte_size / 1024, 1) . ' KB' : '-' ?></td>
          </tr>
          <tr>
            <th><?php echo __('Path'); ?></th>
            <td><small class="text-muted"><?php echo htmlspecialchars($digitalObject->path) ?></small></td>
          </tr>
          <tr>
            <th><?php echo __('Parent Record'); ?></th>
            <td>
              <?php if ($digitalObject->slug): ?>
                <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $digitalObject->slug]) ?>">
                  <?php echo htmlspecialchars($digitalObject->record_title ?? 'Untitled') ?>
                </a>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif ?>
            </td>
          </tr>
        </table>
      </div>
      <div class="col-md-6">
        <h6><?php echo __('Actions'); ?></h6>
        <div class="d-flex gap-2 flex-wrap">
          <button type="button" class="btn btn-success btn-sm" id="extractBtn">
            <i class="bi bi-download me-1"></i><?php echo __('Extract Metadata'); ?>
          </button>
          <?php if (count($metadata) > 0): ?>
            <button type="button" class="btn btn-danger btn-sm" id="deleteBtn">
              <i class="bi bi-trash me-1"></i><?php echo __('Delete Metadata'); ?>
            </button>
          <?php endif ?>
        </div>

        <div class="mt-3">
          <span class="badge bg-info"><?php echo count($metadata) ?> <?php echo __('metadata fields extracted'); ?></span>
        </div>
      </div>
    </div>

    <hr>

    <!-- Extracted Metadata -->
    <?php if (count($groupedMetadata) > 0): ?>
      <h6><?php echo __('Extracted Metadata'); ?></h6>

      <div class="accordion" id="metadataAccordion">
        <?php $index = 0; ?>
        <?php foreach ($groupedMetadata as $group => $fields): ?>
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : '' ?>" type="button"
                      data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index ?>">
                <?php echo htmlspecialchars($group) ?>
                <span class="badge bg-secondary ms-2"><?php echo count($fields) ?></span>
              </button>
            </h2>
            <div id="collapse<?php echo $index ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : '' ?>"
                 data-bs-parent="#metadataAccordion">
              <div class="accordion-body p-0">
                <table class="table table-sm table-striped mb-0">
                  <thead class="table-light">
                    <tr>
                      <th style="width: 30%"><?php echo __('Field'); ?></th>
                      <th><?php echo __('Value'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($fields as $field): ?>
                      <tr>
                        <td>
                          <code class="small"><?php echo htmlspecialchars($field->name) ?></code>
                        </td>
                        <td>
                          <?php
                          $value = $field->value;
                          // Try to decode JSON arrays
                          $decoded = json_decode($value, true);
                          if (is_array($decoded)) {
                              echo '<ul class="mb-0 small">';
                              foreach ($decoded as $item) {
                                  echo '<li>' . htmlspecialchars((string) $item) . '</li>';
                              }
                              echo '</ul>';
                          } elseif (strlen($value) > 200) {
                              echo '<span class="small">' . htmlspecialchars(substr($value, 0, 200)) . '...</span>';
                          } else {
                              echo htmlspecialchars($value);
                          }
                          ?>
                        </td>
                      </tr>
                    <?php endforeach ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <?php $index++; ?>
        <?php endforeach ?>
      </div>

    <?php else: ?>
      <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        <?php echo __('No metadata has been extracted for this digital object yet. Click "Extract Metadata" to begin.'); ?>
      </div>
    <?php endif ?>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var extractBtn = document.getElementById('extractBtn');
  var deleteBtn = document.getElementById('deleteBtn');
  var digitalObjectId = <?php echo $digitalObjectId ?>;

  if (extractBtn) {
    extractBtn.addEventListener('click', function() {
      extractBtn.disabled = true;
      extractBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Extracting...';

      fetch('<?php echo url_for(['module' => 'metadataExtraction', 'action' => 'extract']) ?>?id=' + digitalObjectId, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          location.reload();
        } else {
          alert('Error: ' + (data.error || 'Unknown error'));
          extractBtn.disabled = false;
          extractBtn.innerHTML = '<i class="bi bi-download me-1"></i>Extract Metadata';
        }
      })
      .catch(err => {
        alert('Error: ' + err.message);
        extractBtn.disabled = false;
        extractBtn.innerHTML = '<i class="bi bi-download me-1"></i>Extract Metadata';
      });
    });
  }

  if (deleteBtn) {
    deleteBtn.addEventListener('click', function() {
      if (!confirm('<?php echo __('Are you sure you want to delete all extracted metadata?'); ?>')) {
        return;
      }

      deleteBtn.disabled = true;
      deleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Deleting...';

      fetch('<?php echo url_for(['module' => 'metadataExtraction', 'action' => 'delete']) ?>?id=' + digitalObjectId, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          location.reload();
        } else {
          alert('Error: ' + (data.error || 'Unknown error'));
          deleteBtn.disabled = false;
          deleteBtn.innerHTML = '<i class="bi bi-trash me-1"></i>Delete Metadata';
        }
      })
      .catch(err => {
        alert('Error: ' + err.message);
        deleteBtn.disabled = false;
        deleteBtn.innerHTML = '<i class="bi bi-trash me-1"></i>Delete Metadata';
      });
    });
  }
});
</script>

<?php end_slot() ?>
