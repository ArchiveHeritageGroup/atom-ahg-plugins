<?php
/**
 * Holdings List Block Template
 */
$items = $data ?? [];
$title = $config['title'] ?? 'Our Holdings';
$showLevel = $config['show_level'] ?? true;
$showDates = $config['show_dates'] ?? true;
$showExtent = $config['show_extent'] ?? false;
$showHits = $config['show_hits'] ?? false;
?>
<?php if (!empty($title)): ?>
  <h2 class="h5 mb-3"><?php echo esc_entities($title) ?></h2>
<?php endif ?>
<?php if (empty($items)): ?>
  <p class="text-muted">No holdings available.</p>
<?php else: ?>
  <ul class="list-group list-group-flush">
    <?php foreach ($items as $item): ?>
      <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
        <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $item->slug]) ?>" class="text-decoration-none text-truncate">
          <?php echo esc_entities($item->title ?? $item->slug) ?>
        </a>
        <?php if ($showHits && isset($item->hits)): ?>
          <small class="text-muted text-nowrap ms-2"><?php echo number_format($item->hits) ?> visits</small>
        <?php endif ?>
      </li>
    <?php endforeach ?>
  </ul>
<?php endif ?>
