<?php
/**
 * Holdings List Block Template
 */
$items = $data ?? [];
$title = $config['title'] ?? 'Our Holdings';
$showLevel = $config['show_level'] ?? true;
$showDates = $config['show_dates'] ?? true;
$showExtent = $config['show_extent'] ?? false;
?>

<?php if (!empty($title)): ?>
  <h2 class="h4 mb-4"><?php echo esc_entities($title) ?></h2>
<?php endif ?>

<?php if (empty($items)): ?>
  <p class="text-muted">No holdings available.</p>
<?php else: ?>
  <div class="list-group">
    <?php foreach ($items as $item): ?>
      <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $item->slug]) ?>" 
         class="list-group-item list-group-item-action">
        <div class="d-flex justify-content-between align-items-start">
          <div class="flex-grow-1">
            <h6 class="mb-1"><?php echo esc_entities($item->title ?? $item->slug) ?></h6>
            <?php if ($showLevel && !empty($item->level_of_description)): ?>
              <small class="text-muted">
                <i class="bi bi-diagram-3"></i>
                <?php echo esc_entities($item->level_of_description) ?>
              </small>
            <?php endif ?>
            <?php if ($showExtent && !empty($item->extent)): ?>
              <small class="text-muted ms-2">
                <i class="bi bi-box"></i>
                <?php echo esc_entities(truncate_text($item->extent, 50)) ?>
              </small>
            <?php endif ?>
          </div>
          <i class="bi bi-chevron-right text-muted"></i>
        </div>
      </a>
    <?php endforeach ?>
  </div>
<?php endif ?>
