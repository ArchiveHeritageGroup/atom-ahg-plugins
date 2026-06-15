<?php
$do = $sf_data->getRaw('digitalObject');
$altMap = $sf_data->getRaw('altMap') ?: [];
$languages = $sf_data->getRaw('languages') ?: ['en'];
$recordTitle = $sf_data->getRaw('recordTitle');
$recordSlug = $sf_data->getRaw('recordSlug');
?>
<div class="container-fluid py-3 accessibility-altedit" style="max-width: 900px;">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="fas fa-universal-access me-2"></i><?php echo __('Edit alternative text') ?></h1>
    <a href="<?php echo url_for(['module' => 'accessibility', 'action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i><?php echo __('Back to coverage') ?></a>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <dl class="row mb-0">
        <dt class="col-sm-3"><?php echo __('Record') ?></dt>
        <dd class="col-sm-9"><?php if ($recordSlug): ?><a href="/<?php echo esc_entities((string) $recordSlug) ?>" target="_blank"><?php echo esc_entities((string) ($recordTitle ?: ('#' . $do->object_id))) ?></a><?php else: ?><?php echo esc_entities((string) ($recordTitle ?: ('#' . $do->object_id))) ?><?php endif ?></dd>
        <dt class="col-sm-3"><?php echo __('Filename') ?></dt>
        <dd class="col-sm-9 text-muted"><?php echo esc_entities((string) $do->name) ?></dd>
      </dl>
    </div>
  </div>

  <div class="alert alert-info small">
    <i class="fas fa-info-circle me-1"></i>
    <?php echo __('Describe what the image conveys for users who cannot see it. Be concise and specific; do not start with “image of”. Leave blank if the image is purely decorative.') ?>
  </div>

  <form method="post" action="<?php echo url_for(['module' => 'accessibility', 'action' => 'save']) ?>">
    <input type="hidden" name="digital_object_id" value="<?php echo (int) $do->id ?>">
    <?php foreach ($languages as $lang): ?>
      <div class="mb-3">
        <label class="form-label" for="alt_<?php echo esc_entities($lang) ?>"><?php echo __('Alt text') ?> <span class="badge bg-secondary text-uppercase"><?php echo esc_entities($lang) ?></span></label>
        <textarea class="form-control" id="alt_<?php echo esc_entities($lang) ?>" name="alt[<?php echo esc_entities($lang) ?>]" rows="3" maxlength="1000"><?php echo esc_entities((string) ($altMap[$lang] ?? '')) ?></textarea>
      </div>
    <?php endforeach ?>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i><?php echo __('Save') ?></button>
      <a href="<?php echo url_for(['module' => 'accessibility', 'action' => 'index']) ?>" class="btn btn-outline-secondary"><?php echo __('Cancel') ?></a>
    </div>
  </form>
</div>
