<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Gallery Browse'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<div class="container-fluid">
  <div class="row mb-4">
    <div class="col">
      <p class="text-muted"><?php echo __('Showing %1% gallery objects', ['%1%' => $total]); ?></p>
    </div>
  </div>

  <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4">
    <?php foreach ($items as $item): ?>
      <div class="col">
        <div class="card h-100 shadow-sm">
          <a href="<?php echo url_for(['module' => 'gallery', 'action' => 'index', 'slug' => $item->slug]); ?>">
            <?php
              $thumbUrl = null;
              $is3D = false;
              
              // Check for thumbnail derivative
              if (!empty($item->thumb_path) && !empty($item->thumb_name)) {
                  $thumbUrl = $item->thumb_path . $item->thumb_name;
              }
              // Fallback to master for images
              elseif (!empty($item->master_path) && !empty($item->master_name)) {
                  $ext = strtolower(pathinfo($item->master_name, PATHINFO_EXTENSION));
                  if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                      $thumbUrl = $item->master_path . $item->master_name;
                  }
                  $is3D = in_array($ext, ['glb', 'gltf', 'obj', 'stl', 'fbx', 'ply', 'dae']);
              }
            ?>
            <?php if ($thumbUrl): ?>
              <img src="<?php echo esc_entities($thumbUrl); ?>" 
                   class="card-img-top" 
                   alt="<?php echo esc_entities($item->title ?? ''); ?>"
                   style="height: 180px; object-fit: cover;">
            <?php elseif ($is3D): ?>
              <div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height: 180px;">
                <i class="fas fa-cube fa-4x text-primary"></i>
              </div>
            <?php else: ?>
              <div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height: 180px;">
                <i class="fas fa-image fa-4x text-muted"></i>
              </div>
            <?php endif; ?>
          </a>
          <div class="card-body">
            <h6 class="card-title text-truncate">
              <a href="<?php echo url_for(['module' => 'gallery', 'action' => 'index', 'slug' => $item->slug]); ?>" class="text-decoration-none">
                <?php echo esc_entities($item->title ?? $item->slug); ?>
              </a>
            </h6>
            <?php if (!empty($item->identifier)): ?>
              <p class="card-text small text-muted mb-0"><?php echo esc_entities($item->identifier); ?></p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($totalPages > 1): ?>
    <nav class="mt-4">
      <ul class="pagination justify-content-center">
        <?php if ($currentPage > 1): ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'gallery', 'action' => 'browse', 'page' => $currentPage - 1]); ?>">
              <i class="fas fa-chevron-left"></i>
            </a>
          </li>
        <?php endif; ?>
        
        <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
          <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'gallery', 'action' => 'browse', 'page' => $i]); ?>">
              <?php echo $i; ?>
            </a>
          </li>
        <?php endfor; ?>
        
        <?php if ($currentPage < $totalPages): ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'gallery', 'action' => 'browse', 'page' => $currentPage + 1]); ?>">
              <i class="fas fa-chevron-right"></i>
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </nav>
  <?php endif; ?>
</div>

<?php end_slot(); ?>
