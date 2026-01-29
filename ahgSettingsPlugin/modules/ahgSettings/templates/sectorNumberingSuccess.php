<?php decorate_with('layout_2col.php'); ?>

<?php slot('sidebar'); ?>
  <?php echo get_component('ahgSettings', 'menu'); ?>
<?php end_slot(); ?>

<?php slot('title'); ?>
  <h1><i class="fas fa-hashtag me-2"></i><?php echo __('Sector Numbering Schemes'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<div class="alert alert-info" role="alert">
  <i class="fas fa-info-circle me-2"></i>
  <?php echo __('Configure unique identifier numbering schemes per GLAM/DAM sector. Leave fields blank to inherit the global settings.'); ?>
  <br><small class="text-muted"><?php echo __('Note: Accession numbering uses a single global counter across all sectors.'); ?></small>
</div>

<!-- Global Reference Card -->
<div class="card mb-4">
  <div class="card-header bg-secondary text-white">
    <i class="fas fa-globe me-2"></i><?php echo __('Current Global Identifier Settings (Reference)'); ?>
  </div>
  <div class="card-body">
    <dl class="row small mb-0">
      <dt class="col-sm-3"><?php echo __('Mask Enabled'); ?></dt>
      <dd class="col-sm-3"><code><?php echo ($globalValues['identifier_mask_enabled'] ?? '0') ? __('Yes') : __('No'); ?></code></dd>
      <dt class="col-sm-3"><?php echo __('Mask'); ?></dt>
      <dd class="col-sm-3"><code><?php echo esc_entities($globalValues['identifier_mask'] ?? '-'); ?></code></dd>
      <dt class="col-sm-3"><?php echo __('Counter'); ?></dt>
      <dd class="col-sm-3"><code><?php echo esc_entities($globalValues['identifier_counter'] ?? '-'); ?></code></dd>
    </dl>
    <div class="text-end mt-2">
      <a href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'identifier']); ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-cog me-1"></i><?php echo __('Edit Global Settings'); ?>
      </a>
    </div>
  </div>
</div>

<?php echo $form->renderGlobalErrors(); ?>

<?php echo $form->renderFormTag(url_for(['module' => 'ahgSettings', 'action' => 'sectorNumbering'])); ?>
  <?php echo $form->renderHiddenFields(); ?>

  <?php
    // Get raw arrays from Symfony's output escaper if needed
    if (isset($sectors) && $sectors instanceof sfOutputEscaperArrayDecorator) {
        $sectors = $sf_data->getRaw('sectors');
    }
    $sectors = isset($sectors) && is_array($sectors) ? $sectors : [];

    // Default identifier mask patterns per sector
    $sectorDefaults = [
        'archive' => ['identifier_mask' => 'ARCH/%Y%/%04i%'],
        'museum'  => ['identifier_mask' => 'MUS.%Y%.%04i%'],
        'library' => ['identifier_mask' => 'LIB/%Y%/%04i%'],
        'gallery' => ['identifier_mask' => 'GAL.%Y%.%04i%'],
        'dam'     => ['identifier_mask' => 'DAM-%Y%-%06i%'],
    ];

    $fieldName = function ($sector, $key) {
        return 'sector_' . $sector . '__' . $key;
    };
  ?>

  <?php if (empty($sectors)): ?>
    <div class="alert alert-warning" role="alert">
      <i class="fas fa-exclamation-triangle me-2"></i>
      <?php echo __('No GLAM/DAM sectors detected. Enable sector plugins to configure numbering.'); ?>
    </div>
  <?php else: ?>

    <ul class="nav nav-tabs" role="tablist">
      <?php $i = 0;
foreach ($sectors as $code => $label): ?>
        <li class="nav-item" role="presentation">
          <button
            class="nav-link <?php echo $i === 0 ? 'active' : ''; ?>"
            id="sector-tab-<?php echo $code; ?>"
            data-bs-toggle="tab"
            data-bs-target="#sector-pane-<?php echo $code; ?>"
            type="button"
            role="tab"
            aria-controls="sector-pane-<?php echo $code; ?>"
            aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>"
          >
            <i class="fas fa-layer-group me-1"></i><?php echo __($label); ?>
          </button>
        </li>
      <?php $i++;
endforeach; ?>
    </ul>

    <div class="tab-content border border-top-0 rounded-bottom p-4 bg-white">
      <?php $i = 0;
foreach ($sectors as $code => $label): ?>
        <div
          class="tab-pane fade <?php echo $i === 0 ? 'show active' : ''; ?>"
          id="sector-pane-<?php echo $code; ?>"
          role="tabpanel"
          aria-labelledby="sector-tab-<?php echo $code; ?>"
        >
          <h4 class="mb-3">
            <span class="badge bg-primary"><?php echo __($label); ?></span>
            <?php echo __('Numbering Scheme'); ?>
          </h4>
          <p class="text-muted small mb-4">
            <?php echo __('Override global settings for the %1% sector. Empty fields inherit global values.', ['%1%' => __($label)]); ?>
          </p>

          <div class="card mb-3">
            <div class="card-header"><i class="fas fa-fingerprint me-2"></i><?php echo __('Identifier Numbering'); ?></div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-4">
                  <?php echo render_field($form[$fieldName($code, 'identifier_mask_enabled')]->label(__('Enable identifier mask'))); ?>
                </div>
                <div class="col-md-4">
                  <?php echo render_field($form[$fieldName($code, 'identifier_mask')]->label(__('Identifier mask pattern'))); ?>
                  <?php $idDefault = $sectorDefaults[$code]['identifier_mask'] ?? ''; ?>
                  <?php if ($idDefault): ?>
                    <div class="form-text">
                      <i class="fas fa-lightbulb text-warning me-1"></i>
                      <?php echo __('Suggested:'); ?> <code><?php echo esc_entities($idDefault); ?></code>
                      <button type="button" class="btn btn-sm btn-outline-primary ms-2 use-default-btn"
                              data-target="<?php echo $fieldName($code, 'identifier_mask'); ?>"
                              data-value="<?php echo esc_entities($idDefault); ?>">
                        <?php echo __('Use'); ?>
                      </button>
                    </div>
                  <?php endif; ?>
                </div>
                <div class="col-md-4">
                  <?php echo render_field($form[$fieldName($code, 'identifier_counter')]->label(__('Identifier counter')), null, ['type' => 'number']); ?>
                </div>
              </div>
            </div>
          </div>

        </div>
      <?php $i++;
endforeach; ?>
    </div>

  <?php endif; ?>

  <section class="actions mt-4">
    <input class="btn btn-success" type="submit" value="<?php echo __('Save'); ?>">
    <a href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'index']); ?>" class="btn btn-outline-secondary ms-2">
      <?php echo __('Cancel'); ?>
    </a>
  </section>

</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.use-default-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var targetName = this.dataset.target;
      var value = this.dataset.value;
      var input = document.querySelector('input[name="' + targetName + '"]');
      if (input) {
        input.value = value;
        input.focus();
      }
    });
  });
});
</script>

<?php end_slot(); ?>
