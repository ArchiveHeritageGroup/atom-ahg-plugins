<?php
/**
 * _imageGallery.php - Image gallery with main display, thumbnails, and lightbox.
 *
 * Variables:
 *   $images (array) Array of image objects, each with: file_path, caption, is_primary
 */
$primaryImage = null;
$thumbs = [];
if (!empty($images)) {
    foreach ($images as $img) {
        if (!empty($img->is_primary)) {
            $primaryImage = $img;
        }
        $thumbs[] = $img;
    }
    if (!$primaryImage && count($thumbs) > 0) {
        $primaryImage = $thumbs[0];
    }
}
$galleryId = 'mkt-gallery-' . mt_rand(1000, 9999);
?>
<div class="mkt-gallery" id="<?php echo $galleryId; ?>">

  <!-- Main image -->
  <div class="mkt-gallery-main border rounded overflow-hidden bg-light text-center mb-2" data-bs-toggle="modal" data-bs-target="#<?php echo $galleryId; ?>-modal" role="button">
    <?php if ($primaryImage): ?>
      <img src="<?php echo esc_entities($primaryImage->file_path); ?>" alt="<?php echo esc_entities($primaryImage->caption ?? ''); ?>" class="img-fluid" id="<?php echo $galleryId; ?>-main-img">
    <?php else: ?>
      <div class="d-flex align-items-center justify-content-center py-5">
        <i class="fas fa-image fa-5x text-muted"></i>
      </div>
    <?php endif; ?>
  </div>

  <!-- Thumbnail strip -->
  <?php if (count($thumbs) > 1): ?>
    <div class="mkt-gallery-thumbs d-flex flex-nowrap gap-2 overflow-auto pb-1">
      <?php foreach ($thumbs as $idx => $thumb): ?>
        <div class="mkt-gallery-thumb border rounded overflow-hidden flex-shrink-0"
             data-src="<?php echo esc_entities($thumb->file_path); ?>"
             data-gallery="<?php echo $galleryId; ?>"
             role="button">
          <img src="<?php echo esc_entities($thumb->file_path); ?>"
               alt="<?php echo esc_entities($thumb->caption ?? __('Image %1%', ['%1%' => $idx + 1])); ?>"
               class="w-100 h-100" style="object-fit: cover;">
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Lightbox modal -->
<?php if ($primaryImage): ?>
  <div class="modal fade" id="<?php echo $galleryId; ?>-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content bg-dark border-0">
        <div class="modal-header border-0 pb-0">
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?php echo __('Close'); ?>"></button>
        </div>
        <div class="modal-body text-center p-2">
          <img src="<?php echo esc_entities($primaryImage->file_path); ?>" alt="" class="img-fluid" id="<?php echo $galleryId; ?>-lightbox-img" style="max-height: 80vh;">
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function() {
  var gid = <?php echo json_encode($galleryId); ?>;

  function init() {
    var container = document.getElementById(gid);
    if (!container) return;

    container.querySelectorAll('.mkt-gallery-thumb').forEach(function(thumb) {
      thumb.addEventListener('click', function() {
        var src = this.getAttribute('data-src');
        var mainImg = document.getElementById(gid + '-main-img');
        var lbImg = document.getElementById(gid + '-lightbox-img');
        if (mainImg) mainImg.src = src;
        if (lbImg) lbImg.src = src;
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
</script>
