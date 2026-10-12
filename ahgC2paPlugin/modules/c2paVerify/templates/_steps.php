<?php
/*
 * Plain-language list of a manifest's steps (see ManifestPresenter::readAssertions).
 * Shared by the object page and the public check page.
 */
$steps = $sf_data->getRaw('steps');
$h = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<ol class="mb-0">
  <?php foreach ($steps as $step) { ?>
    <li class="mb-2">
      <i class="fas <?php echo \AhgC2pa\Services\ManifestPresenter::icon((string) ($step['kind'] ?? 'action')); ?> me-1 text-muted" aria-hidden="true"></i>
      <span class="fw-semibold"><?php echo $h($step['summary'] ?? ($step['label'] ?? '-')); ?></span>
      <?php if (!empty($step['software'])) { ?><span class="text-muted">- <?php echo __('via'); ?> <?php echo $h($step['software']); ?></span><?php } ?>
      <?php if (!empty($step['when'])) { ?><span class="text-muted small">(<?php echo $h($step['when']); ?>)</span><?php } ?>
      <?php if (!empty($step['params'])) { ?>
        <ul class="small text-muted mb-0">
          <?php foreach ($step['params'] as $k => $val) { ?>
            <li><span class="text-uppercase small"><?php echo $h($k); ?></span>: <code><?php echo $h($val); ?></code></li>
          <?php } ?>
        </ul>
      <?php } ?>
    </li>
  <?php } ?>
</ol>
