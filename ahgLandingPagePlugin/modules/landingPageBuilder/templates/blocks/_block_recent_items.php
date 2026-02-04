<?php
/**
 * Recent Items Block Template
 */
use_helper('Date');

$items = $data ?? [];
$title = $config['title'] ?? 'Recent Additions';
$showDate = $config['show_date'] ?? true;
$showThumbnail = $config['show_thumbnail'] ?? true;
$layout = $config['layout'] ?? 'scroll';
$columns = $config['columns'] ?? 3;
$scrollable = ($layout === 'scroll') || ($config['scrollable'] ?? false);

$colClass = 'col-md-' . (12 / $columns);
?>

<?php if (!empty($title)): ?>
  <h2 class="h4 mb-4"><?php echo esc_entities($title) ?></h2>
<?php endif ?>

<?php if (empty($items)): ?>
  <p class="text-muted">No recent items found.</p>
<?php elseif ($scrollable): ?>
  <div class="recent-items-scroll d-flex overflow-auto pb-3" style="gap: 1rem; scroll-snap-type: x mandatory;">
    <?php foreach ($items as $item): ?>
      <?php
      $itemSlug = is_object($item) ? ($item->slug ?? '') : ($item['slug'] ?? '');
      $itemTitle = is_object($item) ? ($item->title ?? $itemSlug) : ($item['title'] ?? $itemSlug);
      $itemDate = is_object($item) ? ($item->created_at ?? '') : ($item['created_at'] ?? '');
      $thumbnailUrl = is_object($item) ? ($item->thumbnail_url ?? null) : ($item['thumbnail_url'] ?? null);
      ?>
      <div class="flex-shrink-0" style="width: 220px; scroll-snap-align: start;">
        <div class="card h-100 border-0 shadow-sm">
          <?php if ($showThumbnail): ?>
            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 150px; overflow: hidden;">
              <?php if ($thumbnailUrl): ?>
                <img src="<?php echo esc_entities($thumbnailUrl) ?>"
                     class="w-100 h-100"
                     style="object-fit: cover;"
                     alt="<?php echo esc_entities($itemTitle) ?>"
                     onerror="this.parentElement.innerHTML='<div class=\'text-center text-muted\'><i class=\'bi bi-image fs-1\'></i></div>'">
              <?php else: ?>
                <div class="text-center text-muted">
                  <i class="bi bi-file-earmark fs-1"></i>
                </div>
              <?php endif ?>
            </div>
          <?php endif ?>
          <div class="card-body p-2">
            <h6 class="card-title mb-1 small">
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
<?php elseif ($layout === 'grid'): ?>
  <div class="row g-4">
    <?php foreach ($items as $item): ?>
      <?php
      $itemSlug = is_object($item) ? ($item->slug ?? '') : ($item['slug'] ?? '');
      $itemTitle = is_object($item) ? ($item->title ?? $itemSlug) : ($item['title'] ?? $itemSlug);
      $itemDate = is_object($item) ? ($item->created_at ?? '') : ($item['created_at'] ?? '');
      $thumbnailUrl = is_object($item) ? ($item->thumbnail_url ?? null) : ($item['thumbnail_url'] ?? null);
      ?>
      <div class="<?php echo $colClass ?>">
        <div class="card h-100 border-0 shadow-sm">
          <?php if ($showThumbnail): ?>
            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 150px; overflow: hidden;">
              <?php if ($thumbnailUrl): ?>
                <img src="<?php echo esc_entities($thumbnailUrl) ?>"
                     class="w-100 h-100"
                     style="object-fit: cover;"
                     alt="<?php echo esc_entities($itemTitle) ?>"
                     onerror="this.parentElement.innerHTML='<div class=\'text-center text-muted\'><i class=\'bi bi-image fs-1\'></i></div>'">
              <?php else: ?>
                <div class="text-center text-muted">
                  <i class="bi bi-file-earmark fs-1"></i>
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
