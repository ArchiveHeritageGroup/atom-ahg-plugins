<?php
/**
 * 1 Column Row Block Template
 */
$content = $config['content'] ?? '';
$minHeight = $config['min_height'] ?? 'auto';
$verticalAlign = $config['vertical_align'] ?? 'top';
$bgImage = $config['background_image'] ?? '';

$alignClass = match($verticalAlign) {
    'center' => 'align-items-center',
    'bottom' => 'align-items-end',
    default => 'align-items-start'
};

$style = '';
if ($minHeight !== 'auto') {
    $style .= "min-height: {$minHeight};";
}
if (!empty($bgImage)) {
    $style .= "background: url('{$bgImage}') center/cover no-repeat;";
}
?>

<div class="row-1-col d-flex <?php echo $alignClass ?>" style="<?php echo $style ?>">
  <div class="w-100">
    <?php if (!empty($content)): ?>
      <div class="content-area"><?php echo $content ?></div>
    <?php else: ?>
      <p class="text-muted text-center py-4">Add content to this row</p>
    <?php endif ?>
  </div>
</div>
