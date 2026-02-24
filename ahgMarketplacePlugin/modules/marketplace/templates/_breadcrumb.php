<?php
/**
 * _breadcrumb.php - Bootstrap 5 breadcrumb component.
 *
 * Variables:
 *   $items (array) Array of ['label' => '...', 'url' => '...'] pairs.
 *          The last item should omit 'url' (rendered as active).
 */
$items = $items ?? [];
$count = count($items);
?>
<?php if ($count > 0): ?>
<nav aria-label="<?php echo __('Breadcrumb'); ?>" class="mkt-breadcrumb mb-3">
  <ol class="breadcrumb mb-0">
    <?php foreach ($items as $idx => $item): ?>
      <?php $isLast = ($idx === $count - 1); ?>
      <?php if ($isLast || empty($item['url'])): ?>
        <li class="breadcrumb-item active" aria-current="page"><?php echo esc_entities($item['label']); ?></li>
      <?php else: ?>
        <li class="breadcrumb-item"><a href="<?php echo $item['url']; ?>"><?php echo esc_entities($item['label']); ?></a></li>
      <?php endif; ?>
    <?php endforeach; ?>
  </ol>
</nav>
<?php endif; ?>
