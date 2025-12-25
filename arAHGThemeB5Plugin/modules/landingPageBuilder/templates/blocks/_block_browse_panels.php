<?php
/**
 * Browse Panels Block Template
 */
$panels = $data ?? $config['panels'] ?? [];
$columns = $config['columns'] ?? 4;
$showCounts = $config['show_counts'] ?? true;

$colClass = 'col-6 col-md-' . (12 / $columns);
?>

<div class="browse-panels">
  <div class="row g-3">
    <?php foreach ($panels as $panel): ?>
      <div class="<?php echo $colClass ?>">
        <a href="<?php echo esc_entities($panel['url']) ?>" 
           class="card h-100 text-decoration-none border-0 shadow-sm hover-lift">
          <div class="card-body text-center py-4">
            <i class="bi <?php echo $panel['icon'] ?? 'bi-folder' ?> display-4 text-primary mb-3"></i>
            <h5 class="card-title mb-2"><?php echo esc_entities($panel['title']) ?></h5>
            <?php if ($showCounts && isset($panel['count'])): ?>
              <p class="card-text text-muted mb-0">
                <?php echo number_format($panel['count']) ?> records
              </p>
            <?php endif ?>
          </div>
        </a>
      </div>
    <?php endforeach ?>
  </div>
</div>
