<?php
/**
 * Multi-angle gallery component for 3D objects.
 *
 * Displays 6 Blender renders (front, back, left, right, top, detail) as
 * a thumbnail row with lightbox modal. Purely additive — no errors if
 * renders don't exist.
 *
 * Include from 3D viewer template:
 *   <?php include_partial('model3d/multiAngleGallery', ['digitalObjectId' => $doId]) ?>
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */

use Illuminate\Database\Capsule\Manager as DB;

$views = ['front', 'back', 'left', 'right', 'top', 'detail'];
$doId = $digitalObjectId ?? 0;

if (!$doId) {
    return;
}

$digitalObject = DB::table('digital_object')->where('id', $doId)->first();
if (!$digitalObject) {
    return;
}

$rootDir = sfConfig::get('sf_root_dir', sfConfig::get('sf_web_dir', '/usr/share/nginx/archive'));
$masterDir = dirname($rootDir . $digitalObject->path . $digitalObject->name);
$multiAngleDir = $masterDir . '/multiangle';

if (!is_dir($multiAngleDir)) {
    return;
}

// Collect existing renders
$renders = [];
foreach ($views as $view) {
    $png = $multiAngleDir . '/' . $view . '.png';
    if (file_exists($png) && filesize($png) > 500) {
        // Build web-accessible URL from uploads path
        $webPath = str_replace($rootDir, '', $png);
        $renders[$view] = $webPath;
    }
}

if (empty($renders)) {
    return;
}

$galleryId = 'multiangle-gallery-' . $doId;
$n = sfConfig::get('csp_nonce', '');
$nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : '';
?>

<div class="card mt-3" id="<?php echo $galleryId; ?>">
  <div class="card-header">
    <h5 class="mb-0">
      <i class="fas fa-cube me-2"></i>Multi-Angle Views
    </h5>
  </div>
  <div class="card-body">
    <div class="row g-2">
      <?php foreach ($renders as $view => $webPath): ?>
      <div class="col-4 col-md-2 text-center">
        <a href="<?php echo htmlspecialchars($webPath); ?>"
           data-bs-toggle="modal"
           data-bs-target="#<?php echo $galleryId; ?>-modal"
           data-view="<?php echo $view; ?>"
           class="multiangle-thumb">
          <img src="<?php echo htmlspecialchars($webPath); ?>"
               alt="<?php echo ucfirst($view); ?> view"
               class="img-fluid rounded border"
               style="max-height: 120px; cursor: pointer;">
        </a>
        <small class="d-block text-muted mt-1"><?php echo ucfirst($view); ?></small>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Lightbox Modal -->
<div class="modal fade" id="<?php echo $galleryId; ?>-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="<?php echo $galleryId; ?>-title">View</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img id="<?php echo $galleryId; ?>-img" src="" alt="" class="img-fluid">
      </div>
    </div>
  </div>
</div>

<script <?php echo $nonceAttr; ?>>
(function() {
  var gallery = document.getElementById('<?php echo $galleryId; ?>');
  if (!gallery) return;
  gallery.querySelectorAll('.multiangle-thumb').forEach(function(link) {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      var src = this.getAttribute('href');
      var view = this.getAttribute('data-view');
      var img = document.getElementById('<?php echo $galleryId; ?>-img');
      var title = document.getElementById('<?php echo $galleryId; ?>-title');
      if (img) img.src = src;
      if (title) title.textContent = view.charAt(0).toUpperCase() + view.slice(1) + ' View';
    });
  });
})();
</script>
