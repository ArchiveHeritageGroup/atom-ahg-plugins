<?php
/**
 * Recent Items Block Template
 */
use_helper('Date');

$items = $data ?? [];
$title = $config['title'] ?? 'Recent Additions';
$showDate = $config['show_date'] ?? true;
$showThumbnail = $config['show_thumbnail'] ?? true;
$layout = $config['layout'] ?? 'grid';
$columns = $config['columns'] ?? 3;

$colClass = 'col-md-' . (12 / $columns);
?>

<?php if (!empty($title)): ?>
  <h2 class="h4 mb-4"><?php echo esc_entities($title) ?></h2>
<?php endif ?>

<?php if (empty($items)): ?>
  <p class="text-muted">No recent items found.</p>
<?php elseif ($layout === 'grid'): ?>
  <div class="row g-4">
    <?php foreach ($items as $item): ?>
      <?php 
      $itemSlug = is_object($item) ? ($item->slug ?? '') : ($item['slug'] ?? '');
      $itemTitle = is_object($item) ? ($item->title ?? $itemSlug) : ($item['title'] ?? $itemSlug);
      $itemDate = is_object($item) ? ($item->created_at ?? '') : ($item['created_at'] ?? '');
      $hasDigitalObject = is_object($item) ? !empty($item->has_digital_object) : !empty($item['has_digital_object']);
      ?>
      <div class="<?php echo $colClass ?>">
        <div class="card h-100 border-0 shadow-sm">
          <?php if ($showThumbnail): ?>
            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 150px; overflow: hidden;">
              <?php if ($hasDigitalObject && $itemSlug): ?>
                <?php 
                // Try to get thumbnail from digital object
                $thumbnailUrl = '/uploads/r/' . $itemSlug . '/conf/thumbnail.jpg';
                ?>
                <img src="<?php echo $thumbnailUrl ?>" 
                     class="w-100 h-100" 
                     style="object-fit: cover;" 
                     alt="<?php echo esc_entities($itemTitle) ?>"
                     onerror="this.parentElement.innerHTML='<div class=\'text-center text-muted\'><svg xmlns=\'http://www.w3.org/2000/svg\' width=\'48\' height=\'48\' fill=\'currentColor\' viewBox=\'0 0 16 16\'><path d=\'M4 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H4zm0 1h8a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1z\'/></svg></div>'">
              <?php else: ?>
                <div class="text-center text-muted">
                  <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M4 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H4zm0 1h8a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1z"/>
                  </svg>
                </div>
              <?php endif ?>
            </div>
          <?php endif ?>
          <div class="card-body">
            <h6 class="card-title mb-1">
              <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $itemSlug]) ?>" 
                 class="text-decoration-none stretched-link">
                <?php echo esc_entities($itemTitle) ?>
              </a>
            </h6>
            <?php if ($showDate && !empty($itemDate)): ?>
              <small class="text-muted">
                <?php echo format_date($itemDate, 'D') ?>
              </small>
            <?php endif ?>
          </div>
        </div>
      </div>
    <?php endforeach ?>
  </div>
<?php elseif ($layout === 'list'): ?>
  <ul class="list-group list-group-flush">
    <?php foreach ($items as $item): ?>
      <?php 
      $itemSlug = is_object($item) ? ($item->slug ?? '') : ($item['slug'] ?? '');
      $itemTitle = is_object($item) ? ($item->title ?? $itemSlug) : ($item['title'] ?? $itemSlug);
      $itemDate = is_object($item) ? ($item->created_at ?? '') : ($item['created_at'] ?? '');
      ?>
      <li class="list-group-item d-flex justify-content-between align-items-center px-0">
        <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $itemSlug]) ?>" 
           class="text-decoration-none">
          <?php echo esc_entities($itemTitle) ?>
        </a>
        <?php if ($showDate && !empty($itemDate)): ?>
          <small class="text-muted"><?php echo format_date($itemDate, 'D') ?></small>
        <?php endif ?>
      </li>
    <?php endforeach ?>
  </ul>
<?php endif ?>
