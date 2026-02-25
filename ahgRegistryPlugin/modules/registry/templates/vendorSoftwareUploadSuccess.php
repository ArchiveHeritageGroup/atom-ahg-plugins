<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Upload Software Package'); ?> - <?php echo htmlspecialchars($software->name ?? '', ENT_QUOTES, 'UTF-8'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php $n = sfConfig::get('csp_nonce', ''); $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('My Vendor Dashboard'), 'url' => url_for(['module' => 'registry', 'action' => 'myVendorDashboard'])],
  ['label' => __('Software'), 'url' => url_for(['module' => 'registry', 'action' => 'myVendorSoftware'])],
  ['label' => htmlspecialchars($software->name ?? '', ENT_QUOTES, 'UTF-8'), 'url' => url_for(['module' => 'registry', 'action' => 'myVendorSoftwareReleases', 'id' => $software->id])],
  ['label' => __('Upload Package')],
]]); ?>

<div class="row justify-content-center">
  <div class="col-lg-7">

    <h1 class="h3 mb-2"><?php echo __('Upload Software Package'); ?></h1>
    <p class="text-muted mb-4"><?php echo htmlspecialchars($software->name ?? '', ENT_QUOTES, 'UTF-8'); ?></p>

    <?php if (isset($errors) && count($errors) > 0): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $err): ?>
            <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorSoftwareUpload', 'id' => $software->id]); ?>" enctype="multipart/form-data">

      <div class="card mb-4">
        <div class="card-body">

          <!-- Drag-drop upload zone -->
          <div class="border border-2 border-dashed rounded p-5 text-center mb-3 position-relative" id="upload-drop-zone" style="min-height: 200px; cursor: pointer;">
            <div id="upload-preview">
              <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
              <h5><?php echo __('Drag and drop your package here'); ?></h5>
              <p class="text-muted mb-2"><?php echo __('or click to browse files'); ?></p>
              <div class="mt-3">
                <span class="badge bg-light text-dark border me-1">.zip</span>
                <span class="badge bg-light text-dark border me-1">.tar.gz</span>
                <span class="badge bg-light text-dark border me-1">.tgz</span>
                <span class="badge bg-light text-dark border me-1">.gz</span>
                <span class="badge bg-light text-dark border">.bz2</span>
              </div>
            </div>
            <input type="file" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" id="upload-file" name="package" accept=".zip,.tar.gz,.tgz,.gz,.bz2" style="cursor: pointer;">
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <div class="d-flex align-items-center text-muted small">
                <i class="fas fa-info-circle me-2"></i>
                <span><?php echo __('Allowed types: .zip, .tar.gz, .tgz, .gz, .bz2'); ?></span>
              </div>
            </div>
            <div class="col-md-6">
              <div class="d-flex align-items-center text-muted small">
                <i class="fas fa-weight-hanging me-2"></i>
                <span><?php echo __('Maximum file size: 100 MB'); ?></span>
              </div>
            </div>
          </div>

          <div class="alert alert-info mt-3 small mb-0">
            <i class="fas fa-shield-alt me-1"></i>
            <?php echo __('A SHA-256 checksum will be calculated automatically after upload for integrity verification.'); ?>
          </div>

        </div>
      </div>

      <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorSoftwareReleases', 'id' => $software->id]); ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
        <button type="submit" class="btn btn-success" id="upload-btn" disabled><i class="fas fa-upload me-1"></i> <?php echo __('Upload Package'); ?></button>
      </div>

    </form>

  </div>
</div>

<script <?php echo $na; ?>>
document.addEventListener('DOMContentLoaded', function() {
  var fileInput = document.getElementById('upload-file');
  var previewArea = document.getElementById('upload-preview');
  var dropZone = document.getElementById('upload-drop-zone');
  var uploadBtn = document.getElementById('upload-btn');

  function showFileInfo(file) {
    var sizeMB = (file.size / (1024 * 1024)).toFixed(2);
    previewArea.innerHTML = '<i class="fas fa-file-archive fa-3x text-success mb-2"></i>' +
      '<h5 class="mb-1">' + file.name + '</h5>' +
      '<p class="text-muted mb-0">' + sizeMB + ' MB</p>';
    uploadBtn.disabled = false;
  }

  if (fileInput) {
    fileInput.addEventListener('change', function(e) {
      if (e.target.files && e.target.files[0]) {
        showFileInfo(e.target.files[0]);
      }
    });
  }

  if (dropZone) {
    ['dragenter', 'dragover'].forEach(function(evt) {
      dropZone.addEventListener(evt, function(e) {
        e.preventDefault();
        dropZone.classList.add('border-primary');
        dropZone.classList.remove('border-dashed');
      });
    });
    ['dragleave', 'drop'].forEach(function(evt) {
      dropZone.addEventListener(evt, function(e) {
        e.preventDefault();
        dropZone.classList.remove('border-primary');
        dropZone.classList.add('border-dashed');
      });
    });
    dropZone.addEventListener('drop', function(e) {
      if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        showFileInfo(e.dataTransfer.files[0]);
      }
    });
  }
});
</script>

<style <?php echo $na; ?>>
.border-dashed { border-style: dashed !important; }
</style>

<?php end_slot(); ?>
