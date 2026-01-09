<?php
/**
 * Browse Panels Block Template
 */
$panels = $data ?? $config['panels'] ?? [];
$title = $config['title'] ?? '';
$style = $config['style'] ?? 'cards';
$columns = $config['columns'] ?? 4;
$showCounts = $config['show_counts'] ?? true;
$colClass = 'col-6 col-md-' . (12 / $columns);
?>
<?php if (!empty($title)): ?>
  <h2 class="h5 mb-3"><?php echo esc_entities($title) ?></h2>
<?php endif ?>

<?php if ($style === 'list'): ?>
  <ul class="list-group list-group-flush">
    <?php foreach ($panels as $panel): ?>
      <li class="list-group-item px-0">
        <a href="<?php echo esc_entities($panel['url'] ?? '#') ?>" class="text-decoration-none">
          <?php if (!empty($panel['icon'])): ?>
            <i class="<?php echo strpos($panel['icon'], 'fa-') === 0 ? 'fas ' . $panel['icon'] : 'bi bi-' . $panel['icon'] ?> me-2"></i>
          <?php endif ?>
          <?php echo esc_entities($panel['label'] ?? $panel['title'] ?? '') ?>
          <?php if ($showCounts && isset($panel['count'])): ?>
            <span class="badge bg-secondary float-end"><?php echo number_format($panel['count']) ?></span>
          <?php endif ?>
        </a>
      </li>
    <?php endforeach ?>
  </ul>
<?php else: ?>
  <div class="browse-panels">
    <div class="row g-3">
      <?php foreach ($panels as $panel): ?>
        <div class="<?php echo $colClass ?>">
          <a href="<?php echo esc_entities($panel['url'] ?? '#') ?>"
             class="card h-100 text-decoration-none border-0 shadow-sm hover-lift">
            <div class="card-body text-center py-4">
              <i class="<?php echo strpos($panel['icon'] ?? '', 'fa-') === 0 ? 'fas ' . $panel['icon'] : 'bi bi-' . ($panel['icon'] ?? 'folder') ?> display-4 text-primary mb-3"></i>
              <h5 class="card-title mb-2"><?php echo esc_entities($panel['label'] ?? $panel['title'] ?? '') ?></h5>
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
<?php endif ?>
