<?php
/**
 * Identifier Generator Component Template
 *
 * Shows auto-generated identifier with option to override.
 */
$info = $numberingInfo;
$isNew = empty($currentIdentifier);
?>

<?php if ($info['enabled'] && $info['auto_generate']): ?>
<div class="identifier-generator mb-3" id="identifier-generator-<?php echo $fieldName; ?>">
  <div class="alert alert-info py-2 mb-2">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <i class="fas fa-magic me-2"></i>
        <strong><?php echo __('Auto-generated identifier'); ?>:</strong>
        <code id="generated-identifier" class="ms-2"><?php echo esc_entities($info['next_reference']); ?></code>
      </div>
      <?php if ($info['allow_override']): ?>
      <button type="button" class="btn btn-sm btn-outline-primary" id="use-generated-btn" title="<?php echo __('Use this identifier'); ?>">
        <i class="fas fa-check me-1"></i><?php echo __('Use'); ?>
      </button>
      <?php endif; ?>
    </div>
    <small class="text-muted d-block mt-1">
      <?php echo __('Scheme'); ?>: <?php echo esc_entities($info['scheme_name']); ?>
      (<?php echo esc_entities($info['pattern']); ?>)
    </small>
  </div>

  <?php if (!$info['allow_override']): ?>
  <input type="hidden" name="<?php echo $fieldName; ?>" id="<?php echo $fieldName; ?>-input" value="<?php echo esc_entities($info['next_reference']); ?>">
  <div class="form-control bg-light" readonly><?php echo esc_entities($info['next_reference']); ?></div>
  <small class="text-muted"><?php echo __('Identifier is auto-generated and cannot be changed.'); ?></small>
  <?php endif; ?>
</div>

<script <?php $n = sfConfig::get('csp_nonce', '');
echo $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : ''; ?>>
(function() {
  var useBtn = document.getElementById('use-generated-btn');
  var generatedId = document.getElementById('generated-identifier');

  if (useBtn && generatedId) {
    useBtn.addEventListener('click', function() {
      // Find the identifier input field
      var input = document.querySelector('input[name="<?php echo $fieldName; ?>"]') ||
                  document.getElementById('<?php echo $fieldName; ?>') ||
                  document.querySelector('[name="identifier"]');

      if (input) {
        input.value = generatedId.textContent;
        input.focus();

        // Visual feedback
        useBtn.innerHTML = '<i class="fas fa-check me-1"></i><?php echo __('Applied'); ?>';
        useBtn.classList.remove('btn-outline-primary');
        useBtn.classList.add('btn-success');

        setTimeout(function() {
          useBtn.innerHTML = '<i class="fas fa-check me-1"></i><?php echo __('Use'); ?>';
          useBtn.classList.remove('btn-success');
          useBtn.classList.add('btn-outline-primary');
        }, 2000);
      }
    });
  }
})();
</script>
<?php endif; ?>

<?php if (!$info['enabled'] || !$info['auto_generate']): ?>
<!-- Numbering scheme not active for this sector -->
<?php endif; ?>
