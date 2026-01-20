<?php use_helper('Text'); ?>
<?php use_helper('AhgMedia'); ?>

<?php
// Media type IDs: VIDEO=137, AUDIO=135, IMAGE=136, OTHER=139
$isStreamableVideo = false;
$isStreamableAudio = false;
$mimeType = $resource->mimeType ?? '';
$mediaTypeId = $resource->mediaTypeId ?? null;

// Check for PII redaction (for PDFs)
$hasPiiRedaction = false;
$piiDownloadUrl = null;
$isPdf = (stripos($mimeType, 'pdf') !== false);
if ($isPdf && in_array('ahgPrivacyPlugin', sfProjectConfiguration::getActive()->getPlugins())) {
    require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/helper/PiiHelper.php';
    $objectId = $resource->object->id ?? ($resource->objectId ?? null);
    if ($objectId && function_exists('pii_has_redacted') && pii_has_redacted($objectId)) {
        $hasPiiRedaction = true;
        $piiDownloadUrl = url_for(['module' => 'privacyAdmin', 'action' => 'downloadPdf', 'id' => $objectId]);
    }
}

// Check if streamable using numeric IDs
if ($mediaTypeId == 137 && ahg_needs_streaming($resource)) {
    $isStreamableVideo = true;
} elseif ($mediaTypeId == 135 && ahg_needs_streaming($resource)) {
    $isStreamableAudio = true;
}
?>

<?php if ($isStreamableVideo): ?>
  <!-- Streaming Video Player for Legacy Format -->
  <div class="digital-object-block streaming-video-container">
    <div class="alert alert-info mb-2">
      <i class="fas fa-info-circle me-1"></i>
      <strong><?php echo ahg_get_format_name($mimeType); ?></strong> - Streaming via server transcoding (original file preserved)
    </div>
    <video controls preload="metadata" class="mw-100" style="max-height: 500px; background: #000;">
      <source src="/media/stream/<?php echo $resource->id; ?>" type="video/mp4">
      Your browser does not support video playback.
    </video>
    <?php if (isset($link) && $canReadMaster): ?>
      <div class="mt-2">
        <a href="<?php echo $link; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
          <i class="fas fa-download me-1"></i><?php echo __('Download original %1%', ['%1%' => strtoupper(pathinfo($resource->name, PATHINFO_EXTENSION))]); ?>
        </a>
      </div>
    <?php endif; ?>
  </div>

<?php elseif ($isStreamableAudio): ?>
  <!-- Streaming Audio Player for Legacy Format -->
  <div class="digital-object-block streaming-audio-container">
    <div class="alert alert-info mb-2">
      <i class="fas fa-info-circle me-1"></i>
      <strong><?php echo ahg_get_format_name($mimeType); ?></strong> - Streaming via server transcoding (original file preserved)
    </div>
    <audio controls preload="metadata" class="w-100">
      <source src="/media/stream/<?php echo $resource->id; ?>" type="audio/mpeg">
      Your browser does not support audio playback.
    </audio>
    <?php if (isset($link) && $canReadMaster): ?>
      <div class="mt-2">
        <a href="<?php echo $link; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
          <i class="fas fa-download me-1"></i><?php echo __('Download original %1%', ['%1%' => strtoupper(pathinfo($resource->name, PATHINFO_EXTENSION))]); ?>
        </a>
      </div>
    <?php endif; ?>
  </div>

<?php else: ?>
  <!-- Default Generic Icon -->
  <div class="digitalObject">
    <div class="digitalObjectRep">
      <?php if (isset($link) && $canReadMaster) { ?>
        <?php if ($hasPiiRedaction && $piiDownloadUrl): ?>
          <!-- PDF with PII redaction -->
          <?php echo link_to(image_tag($representation->getFullPath(), ['alt' => __('Open redacted %1%', ['%1%' => sfConfig::get('app_ui_label_digitalobject')]), 'class' => 'img-thumbnail']), $piiDownloadUrl, ['target' => '_blank']); ?>
        <?php else: ?>
          <?php echo link_to(image_tag($representation->getFullPath(), ['alt' => __($resource->getDigitalObjectAltText() ?: 'Open original %1%', ['%1%' => sfConfig::get('app_ui_label_digitalobject')]), 'class' => 'img-thumbnail']), $link, ['target' => '_blank']); ?>
        <?php endif; ?>
      <?php } else { ?>
        <?php echo image_tag($representation->getFullPath(), ['alt' => __($resource->getDigitalObjectAltText() ?: 'Original %1% not accessible', ['%1%' => sfConfig::get('app_ui_label_digitalobject')]), 'class' => 'img-thumbnail']); ?>
      <?php } ?>
    </div>
    <div class="digitalObjectDesc">
      <?php echo wrap_text($resource->name, 18); ?>
      <?php if ($hasPiiRedaction): ?>
        <div class="mt-1">
          <span class="badge bg-warning text-dark"><i class="fas fa-shield-alt me-1"></i><?php echo __('PII Redacted'); ?></span>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>
