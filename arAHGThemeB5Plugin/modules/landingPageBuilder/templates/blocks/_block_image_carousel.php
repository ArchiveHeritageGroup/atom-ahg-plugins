<?php
/**
 * Image Carousel Block Template
 */
$title = $config['title'] ?? '';
$images = $config['images'] ?? [];
$height = $config['height'] ?? '400px';
$showIndicators = $config['show_indicators'] ?? true;
$showControls = $config['show_controls'] ?? true;
$autoPlay = $config['auto_play'] ?? true;
$interval = $config['interval'] ?? 5000;

$carouselId = 'image-carousel-' . uniqid();
?>

<?php if (!empty($title)): ?>
  <h2 class="h4 mb-4"><?php echo esc_entities($title) ?></h2>
<?php endif ?>

<?php if (empty($images)): ?>
  <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: <?php echo $height ?>;">
    <span class="text-muted">No images configured</span>
  </div>
<?php else: ?>
  <div id="<?php echo $carouselId ?>" class="carousel slide" 
       <?php echo $autoPlay ? 'data-bs-ride="carousel"' : '' ?>>
    
    <?php if ($showIndicators && count($images) > 1): ?>
      <div class="carousel-indicators">
        <?php foreach ($images as $index => $image): ?>
          <button type="button" data-bs-target="#<?php echo $carouselId ?>" 
                  data-bs-slide-to="<?php echo $index ?>" 
                  <?php echo $index === 0 ? 'class="active"' : '' ?>></button>
        <?php endforeach ?>
      </div>
    <?php endif ?>
    
    <div class="carousel-inner rounded">
      <?php foreach ($images as $index => $image): ?>
        <div class="carousel-item <?php echo $index === 0 ? 'active' : '' ?>" 
             data-bs-interval="<?php echo $interval ?>">
          <img src="<?php echo esc_entities(is_array($image) ? ($image['url'] ?? '') : $image) ?>" 
               class="d-block w-100" 
               style="height: <?php echo $height ?>; object-fit: cover;"
               alt="<?php echo esc_entities(is_array($image) ? ($image['alt'] ?? '') : '') ?>">
          <?php if (is_array($image) && !empty($image['caption'])): ?>
            <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-2">
              <p class="mb-0"><?php echo esc_entities($image['caption']) ?></p>
            </div>
          <?php endif ?>
        </div>
      <?php endforeach ?>
    </div>
    
    <?php if ($showControls && count($images) > 1): ?>
      <button class="carousel-control-prev" type="button" data-bs-target="#<?php echo $carouselId ?>" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#<?php echo $carouselId ?>" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
      </button>
    <?php endif ?>
  </div>
<?php endif ?>
