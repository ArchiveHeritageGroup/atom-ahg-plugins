<?php
/**
 * Text Content Block Template
 */
$title = $config['title'] ?? '';
$content = $config['content'] ?? '';
$image = $config['image'] ?? '';
$imagePosition = $config['image_position'] ?? 'none';
$imageWidth = $config['image_width'] ?? '33%';

$hasImage = !empty($image) && $imagePosition !== 'none';
$colWidth = str_replace('%', '', $imageWidth);
$contentColWidth = 12 - (int)(12 * $colWidth / 100);
$imageColWidth = 12 - $contentColWidth;
?>

<div class="text-content-block">
  <?php if (!empty($title)): ?>
    <h2 class="h4 mb-3"><?php echo esc_entities($title) ?></h2>
  <?php endif ?>

  <?php if ($hasImage && in_array($imagePosition, ['left', 'right'])): ?>
    <div class="row align-items-center">
      <?php if ($imagePosition === 'left'): ?>
        <div class="col-md-<?php echo $imageColWidth ?>">
          <img src="<?php echo esc_entities($image) ?>" class="img-fluid rounded" alt="">
        </div>
      <?php endif ?>
      
      <div class="col-md-<?php echo $contentColWidth ?>">
        <div class="content-text">
          <?php echo $content ?>
        </div>
      </div>
      
      <?php if ($imagePosition === 'right'): ?>
        <div class="col-md-<?php echo $imageColWidth ?>">
          <img src="<?php echo esc_entities($image) ?>" class="img-fluid rounded" alt="">
        </div>
      <?php endif ?>
    </div>
  <?php elseif ($hasImage && $imagePosition === 'top'): ?>
    <img src="<?php echo esc_entities($image) ?>" class="img-fluid rounded mb-3" alt="">
    <div class="content-text">
      <?php echo $content ?>
    </div>
  <?php elseif ($hasImage && $imagePosition === 'bottom'): ?>
    <div class="content-text mb-3">
      <?php echo $content ?>
    </div>
    <img src="<?php echo esc_entities($image) ?>" class="img-fluid rounded" alt="">
  <?php else: ?>
    <div class="content-text">
      <?php echo $content ?>
    </div>
  <?php endif ?>
</div>
