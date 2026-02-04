<?php
/**
 * Divider Block Template
 */
$style = $config['style'] ?? 'line';
$width = $config['width'] ?? '100%';
$color = $config['color'] ?? '#dee2e6';
$marginY = $config['margin_y'] ?? '3';

$borderStyle = 'solid';
if ($style === 'dashed') {
    $borderStyle = 'dashed';
} elseif ($style === 'dotted') {
    $borderStyle = 'dotted';
} elseif ($style === 'none') {
    $borderStyle = 'none';
}
?>

<?php if ($style === 'gradient'): ?>
  <div class="my-<?php echo $marginY ?>" style="height: 3px; width: <?php echo $width ?>; margin-left: auto; margin-right: auto; background: linear-gradient(90deg, transparent, <?php echo $color ?>, transparent);"></div>
<?php elseif ($style !== 'none'): ?>
  <hr class="my-<?php echo $marginY ?>" style="width: <?php echo $width ?>; margin-left: auto; margin-right: auto; border: 0; height: 3px; background-color: <?php echo $color ?>; opacity: 1;">
<?php endif ?>
