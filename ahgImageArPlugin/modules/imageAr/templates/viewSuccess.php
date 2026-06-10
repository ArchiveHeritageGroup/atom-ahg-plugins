<?php
/* #147 — 2D image AR viewer. */
$n = sfConfig::get('csp_nonce', '');
$nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : '';
?>
<div class="container py-4 image-ar">
  <div class="d-flex flex-wrap align-items-baseline mb-3 gap-2">
    <h1 class="h3 mb-0 flex-grow-1"><i class="fas fa-cube me-2"></i><?php echo esc_entities($item['title']) ?></h1>
    <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'id' => $item['io_id']]) ?>" class="btn btn-outline-secondary btn-sm"><?php echo __('Back to record') ?></a>
  </div>

  <div id="ar-unsupported" class="alert alert-info d-none">
    <i class="fas fa-info-circle me-1"></i>
    <?php echo __('Augmented reality is not available on this device/browser. AR needs a WebXR-capable mobile browser (Chrome on Android, or an AR-capable headset). The image is shown below.') ?>
  </div>

  <div class="row g-4 align-items-start">
    <div class="col-md-7">
      <div class="border rounded bg-light p-2 text-center">
        <img id="ar-image" src="<?php echo esc_entities($item['image_url']) ?>" alt="<?php echo esc_entities($item['title']) ?>" class="img-fluid" style="max-height:70vh">
      </div>
    </div>
    <div class="col-md-5">
      <div class="card">
        <div class="card-body">
          <h2 class="h5"><?php echo __('View in your space') ?></h2>
          <p class="text-muted small"><?php echo __('Point your phone at a wall or the floor, then tap to place this image at life size. Pinch is not needed — it is placed to scale.') ?></p>
          <button id="ar-enter" class="btn btn-primary d-none"><i class="fas fa-mobile-screen me-1"></i><?php echo __('Enter AR') ?></button>
          <div id="ar-status" class="small text-muted mt-2"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- DOM overlay shown while in the immersive AR session -->
  <div id="ar-overlay" style="display:none">
    <button id="ar-exit" type="button">✕</button>
    <div id="ar-hint"><?php echo __('Move your phone to find a surface, then tap to place.') ?></div>
  </div>
</div>

<script <?php echo $nonceAttr ?> type="application/json" id="ar-data"><?php echo json_encode(['url' => $item['image_url'], 'title' => $item['title']], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script <?php echo $nonceAttr ?> type="importmap">
{ "imports": { "three": "https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.module.js" } }
</script>
<script <?php echo $nonceAttr ?> type="module" src="/plugins/ahgImageArPlugin/web/js/image-ar.js"></script>
