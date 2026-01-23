<?php
/**
 * Browse Panels Block Template
 */
$panels = $data ?? $config['panels'] ?? [];
$title = $config['title'] ?? '';
$style = $config['style'] ?? 'list';
$columns = $config['columns'] ?? 1;
$showCounts = $config['show_counts'] ?? true;
$colClass = 'col-md-' . (12 / max(1, (int)$columns));
?>
<div class="browse-panels-container border rounded">
  <?php if (!empty($title)): ?>
    <div class="bg-light border-bottom px-3 py-2">
      <h5 class="mb-0 fw-bold"><?php echo esc_entities($title) ?></h5>
    </div>
  <?php endif ?>

  <?php if ($style === 'cards'): ?>
    <div class="p-3">
      <div class="row g-3">
        <?php foreach ($panels as $panel): ?>
          <div class="<?php echo $colClass ?>">
            <a href="<?php echo esc_entities($panel['url'] ?? '#') ?>"
               class="card h-100 text-decoration-none border-0 shadow-sm hover-lift">
              <div class="card-body text-center py-4">
                <?php if (!empty($panel['icon'])): ?>
                  <i class="<?php echo strpos($panel['icon'] ?? '', 'fa-') === 0 ? 'fas ' . $panel['icon'] : 'bi bi-' . ($panel['icon'] ?? 'folder') ?> display-4 text-primary mb-3"></i>
                <?php endif ?>
                <h5 class="card-title mb-2"><?php echo esc_entities($panel['label'] ?? $panel['title'] ?? '') ?></h5>
                <?php if ($showCounts && isset($panel['count'])): ?>
                  <p class="card-text text-muted mb-0"><?php echo number_format($panel['count']) ?> records</p>
                <?php endif ?>
              </div>
            </a>
          </div>
        <?php endforeach ?>
      </div>
    </div>
  <?php else: ?>
    <div class="browse-panels-list">
      <?php foreach ($panels as $panel): ?>
        <a href="<?php echo esc_entities($panel['url'] ?? '#') ?>" 
           class="d-block text-decoration-none py-2 px-3 border-bottom browse-panel-item">
          <?php echo esc_entities($panel['label'] ?? $panel['title'] ?? '') ?>
          <?php if ($showCounts && isset($panel['count'])): ?>
            <span class="text-muted">(<?php echo number_format($panel['count']) ?>)</span>
          <?php endif ?>
        </a>
      <?php endforeach ?>
    </div>
  <?php endif ?>
</div>

<style>
.browse-panel-item {
  color: #176442;
  transition: background-color 0.15s ease;
}
.browse-panel-item:hover {
  background-color: #f8f9fa;
  color: #134e32;
}
.browse-panels-list a:last-child {
  border-bottom: none !important;
}
</style>
