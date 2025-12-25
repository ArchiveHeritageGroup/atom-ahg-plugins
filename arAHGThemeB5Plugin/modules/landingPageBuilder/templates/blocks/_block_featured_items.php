<?php
/**
 * Featured Items Block Template
 */
$items = $data ?? [];
$title = $config['title'] ?? 'Featured Collections';
$layout = $config['layout'] ?? 'carousel';
$autoRotate = $config['auto_rotate'] ?? true;
$interval = $config['interval'] ?? 5000;

$carouselId = 'featured-carousel-' . uniqid();
?>

<?php if (!empty($title)): ?>
  <h2 class="h4 mb-4"><?php echo esc_entities($title) ?></h2>
<?php endif ?>

<?php if (empty($items)): ?>
  <p class="text-muted">No featured items selected.</p>
<?php elseif ($layout === 'carousel'): ?>
  <div id="<?php echo $carouselId ?>" class="carousel slide" 
       <?php echo $autoRotate ? 'data-bs-ride="carousel"' : '' ?>>
    <div class="carousel-indicators">
      <?php foreach ($items as $index => $item): ?>
        <button type="button" data-bs-target="#<?php echo $carouselId ?>" 
                data-bs-slide-to="<?php echo $index ?>" 
                <?php echo $index === 0 ? 'class="active"' : '' ?>></button>
      <?php endforeach ?>
    </div>
    <div class="carousel-inner rounded">
      <?php foreach ($items as $index => $item): ?>
        <div class="carousel-item <?php echo $index === 0 ? 'active' : '' ?>" 
             data-bs-interval="<?php echo $interval ?>">
          <div class="bg-dark d-flex align-items-center justify-content-center" style="height: 300px;">
            <?php if (!empty($item->digital_object_id)): ?>
              <img src="<?php echo url_for(['module' => 'digitalobject', 'action' => 'thumbnail', 'slug' => $item->slug]) ?>" 
                   class="d-block h-100" style="object-fit: contain;" alt="">
            <?php else: ?>
              <i class="bi bi-archive text-white display-1"></i>
            <?php endif ?>
          </div>
          <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-75 rounded p-2">
            <h5 class="mb-1"><?php echo esc_entities($item->title ?? $item->slug) ?></h5>
            <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $item->slug]) ?>" 
               class="btn btn-sm btn-outline-light mt-2">View Details</a>
          </div>
        </div>
      <?php endforeach ?>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#<?php echo $carouselId ?>" data-bs-slide="prev">
      <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#<?php echo $carouselId ?>" data-bs-slide="next">
      <span class="carousel-control-next-icon"></span>
    </button>
  </div>
<?php else: ?>
  <div class="row g-4">
    <?php foreach ($items as $item): ?>
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-img-top bg-light" style="height: 150px;">
            <?php if (!empty($item->digital_object_id)): ?>
              <img src="<?php echo url_for(['module' => 'digitalobject', 'action' => 'thumbnail', 'slug' => $item->slug]) ?>" 
                   class="w-100 h-100" style="object-fit: cover;" alt="">
            <?php else: ?>
              <div class="d-flex align-items-center justify-content-center h-100">
                <i class="bi bi-archive text-muted display-4"></i>
              </div>
            <?php endif ?>
          </div>
          <div class="card-body">
            <h6 class="card-title">
              <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $item->slug]) ?>" 
                 class="text-decoration-none stretched-link">
                <?php echo esc_entities($item->title ?? $item->slug) ?>
              </a>
            </h6>
          </div>
        </div>
      </div>
    <?php endforeach ?>
  </div>
<?php endif ?>
