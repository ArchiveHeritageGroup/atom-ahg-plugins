<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Footer Settings'); ?> — Admin<?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('Footer')],
]]); ?>

<h1 class="h3 mb-4"><i class="fas fa-shoe-prints me-2"></i><?php echo __('Footer Settings'); ?></h1>

<?php if (!empty($saved)): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-1"></i> <?php echo __('Footer settings saved successfully.'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo __('Close'); ?>"></button>
  </div>
<?php endif; ?>

<?php if (isset($errors) && count($errors) > 0): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-1"></i>
    <?php foreach ($errors as $err): ?>
      <div><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo __('Close'); ?>"></button>
  </div>
<?php endif; ?>

<?php $fs = $footerSettings; ?>
<?php $cols = $footerColumns; ?>

<form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminFooter']); ?>">

  <!-- Description -->
  <div class="card mb-4">
    <div class="card-header fw-semibold">
      <i class="fas fa-align-left me-1"></i> <?php echo __('Footer Description'); ?>
    </div>
    <div class="card-body">
      <div class="mb-0">
        <label for="footer_description" class="form-label fw-semibold"><?php echo __('Description text (left column)'); ?></label>
        <textarea class="form-control" id="footer_description" name="footer_description" rows="3"><?php echo htmlspecialchars($fs['footer_description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        <div class="form-text"><?php echo __('Displayed in the left column of the footer.'); ?></div>
      </div>
    </div>
  </div>

  <!-- Link Columns -->
  <div class="card mb-4">
    <div class="card-header fw-semibold">
      <i class="fas fa-columns me-1"></i> <?php echo __('Footer Link Columns'); ?>
    </div>
    <div class="card-body">
      <p class="text-muted small mb-3"><?php echo __('Configure up to 4 link columns. Each column has a title and a list of links.'); ?></p>

      <?php for ($i = 0; $i < 4; ++$i): ?>
        <?php $col = $cols[$i] ?? ['title' => '', 'links' => []]; ?>
        <?php $colLinks = $col['links'] ?? []; ?>

        <div class="border rounded p-3 mb-3 column-block" data-col-index="<?php echo $i; ?>">
          <h6 class="fw-semibold mb-3"><?php echo __('Column'); ?> <?php echo $i + 1; ?></h6>

          <div class="mb-3">
            <label for="col_<?php echo $i; ?>_title" class="form-label fw-semibold"><?php echo __('Column title'); ?></label>
            <input type="text" class="form-control" id="col_<?php echo $i; ?>_title" name="col_<?php echo $i; ?>_title" value="<?php echo htmlspecialchars($col['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('e.g. Directory, Community...'); ?>">
          </div>

          <div class="links-container" id="links_<?php echo $i; ?>">
            <label class="form-label fw-semibold"><?php echo __('Links'); ?></label>

            <?php if (count($colLinks) > 0): ?>
              <?php foreach ($colLinks as $link): ?>
                <div class="row g-2 mb-2 link-row">
                  <div class="col-5">
                    <input type="text" class="form-control form-control-sm" name="col_<?php echo $i; ?>_label[]" value="<?php echo htmlspecialchars($link['label'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('Label'); ?>">
                  </div>
                  <div class="col-5">
                    <input type="text" class="form-control form-control-sm" name="col_<?php echo $i; ?>_url[]" value="<?php echo htmlspecialchars($link['url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('URL'); ?>">
                  </div>
                  <div class="col-2">
                    <button type="button" class="btn btn-outline-danger btn-sm w-100 btn-remove-link" title="<?php echo __('Remove'); ?>">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="row g-2 mb-2 link-row">
                <div class="col-5">
                  <input type="text" class="form-control form-control-sm" name="col_<?php echo $i; ?>_label[]" value="" placeholder="<?php echo __('Label'); ?>">
                </div>
                <div class="col-5">
                  <input type="text" class="form-control form-control-sm" name="col_<?php echo $i; ?>_url[]" value="" placeholder="<?php echo __('URL'); ?>">
                </div>
                <div class="col-2">
                  <button type="button" class="btn btn-outline-danger btn-sm w-100 btn-remove-link" title="<?php echo __('Remove'); ?>">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <button type="button" class="btn btn-outline-primary btn-sm btn-add-link" data-col="<?php echo $i; ?>">
            <i class="fas fa-plus me-1"></i> <?php echo __('Add Link'); ?>
          </button>
        </div>
      <?php endfor; ?>
    </div>
  </div>

  <!-- Copyright -->
  <div class="card mb-4">
    <div class="card-header fw-semibold">
      <i class="fas fa-copyright me-1"></i> <?php echo __('Copyright Text'); ?>
    </div>
    <div class="card-body">
      <div class="mb-0">
        <label for="footer_copyright" class="form-label fw-semibold"><?php echo __('Copyright line'); ?></label>
        <input type="text" class="form-control" id="footer_copyright" name="footer_copyright" value="<?php echo htmlspecialchars($fs['footer_copyright'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <div class="form-text"><?php echo __('Use {year} as a placeholder for the current year. Basic HTML like &lt;a&gt; tags is allowed.'); ?></div>
      </div>
    </div>
  </div>

  <!-- Buttons -->
  <div class="d-flex justify-content-end gap-2 mb-4">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminDashboard']); ?>" class="btn btn-secondary">
      <?php echo __('Cancel'); ?>
    </a>
    <button type="submit" class="btn btn-primary">
      <i class="fas fa-save me-1"></i> <?php echo __('Save Footer Settings'); ?>
    </button>
  </div>

</form>

<?php $n = sfConfig::get('csp_nonce', ''); $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>
<script <?php echo $na; ?>>
document.addEventListener('DOMContentLoaded', function() {
  // Add link row
  document.querySelectorAll('.btn-add-link').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var colIdx = this.getAttribute('data-col');
      var container = document.getElementById('links_' + colIdx);
      var row = document.createElement('div');
      row.className = 'row g-2 mb-2 link-row';
      row.innerHTML =
        '<div class="col-5">' +
        '  <input type="text" class="form-control form-control-sm" name="col_' + colIdx + '_label[]" value="" placeholder="<?php echo __('Label'); ?>">' +
        '</div>' +
        '<div class="col-5">' +
        '  <input type="text" class="form-control form-control-sm" name="col_' + colIdx + '_url[]" value="" placeholder="<?php echo __('URL'); ?>">' +
        '</div>' +
        '<div class="col-2">' +
        '  <button type="button" class="btn btn-outline-danger btn-sm w-100 btn-remove-link" title="<?php echo __('Remove'); ?>"><i class="fas fa-times"></i></button>' +
        '</div>';
      container.appendChild(row);

      // Bind remove event on new button
      row.querySelector('.btn-remove-link').addEventListener('click', function() {
        this.closest('.link-row').remove();
      });
    });
  });

  // Remove link row
  document.querySelectorAll('.btn-remove-link').forEach(function(btn) {
    btn.addEventListener('click', function() {
      this.closest('.link-row').remove();
    });
  });
});
</script>

<?php end_slot(); ?>
