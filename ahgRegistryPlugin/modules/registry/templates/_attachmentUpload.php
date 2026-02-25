<?php
  $n = sfConfig::get('csp_nonce', '');
  $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : '';

  $name = $fieldName ?? 'attachment';
  $maxSizeMb = $maxSize ?? 10;
  $allowedTypes = $allowedFileTypes ?? 'PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, ZIP';
?>

<div class="attachment-upload-zone border rounded-3 p-4 text-center bg-light position-relative" id="upload-zone-<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
  <div class="upload-zone-content">
    <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
    <p class="mb-1 fw-semibold"><?php echo __('Click or drag files here'); ?></p>
    <small class="text-muted d-block">
      <?php echo __('Allowed types: %1%', ['%1%' => htmlspecialchars($allowedTypes, ENT_QUOTES, 'UTF-8')]); ?>
    </small>
    <small class="text-muted d-block">
      <?php echo __('Max size: %1% MB', ['%1%' => (int) $maxSizeMb]); ?>
    </small>
  </div>
  <input type="file" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" name="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" id="file-input-<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" style="cursor: pointer;">
</div>

<script <?php echo $na; ?>>
(function() {
  var fieldName = <?php echo json_encode($name); ?>;
  var zone = document.getElementById('upload-zone-' + fieldName);
  var input = document.getElementById('file-input-' + fieldName);

  if (!zone || !input) { return; }

  var activeClass = 'border-primary bg-white';
  var defaultClass = 'bg-light';

  zone.addEventListener('dragover', function(e) {
    e.preventDefault();
    e.stopPropagation();
    zone.classList.remove('bg-light');
    zone.classList.add('border-primary', 'bg-white');
  });

  zone.addEventListener('dragleave', function(e) {
    e.preventDefault();
    e.stopPropagation();
    zone.classList.remove('border-primary', 'bg-white');
    zone.classList.add('bg-light');
  });

  zone.addEventListener('drop', function(e) {
    e.preventDefault();
    e.stopPropagation();
    zone.classList.remove('border-primary', 'bg-white');
    zone.classList.add('bg-light');
    if (e.dataTransfer && e.dataTransfer.files.length > 0) {
      input.files = e.dataTransfer.files;
      var content = zone.querySelector('.upload-zone-content');
      if (content) {
        var fileNames = [];
        for (var i = 0; i < e.dataTransfer.files.length; i++) {
          fileNames.push(e.dataTransfer.files[i].name);
        }
        content.innerHTML = '<i class="fas fa-file fa-2x text-success mb-2"></i>' +
          '<p class="mb-0 fw-semibold">' + fileNames.join(', ') + '</p>';
      }
    }
  });

  input.addEventListener('change', function() {
    if (input.files && input.files.length > 0) {
      var content = zone.querySelector('.upload-zone-content');
      if (content) {
        var fileNames = [];
        for (var i = 0; i < input.files.length; i++) {
          fileNames.push(input.files[i].name);
        }
        content.innerHTML = '<i class="fas fa-file fa-2x text-success mb-2"></i>' +
          '<p class="mb-0 fw-semibold">' + fileNames.join(', ') + '</p>';
      }
    }
  });
})();
</script>
