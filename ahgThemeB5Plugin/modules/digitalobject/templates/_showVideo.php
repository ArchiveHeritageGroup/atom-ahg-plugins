<?php use_helper('Text'); ?>
<?php // Rules moved out of style attributes: a CSP nonce covers <style>
      // elements and never an attribute, so under an enforcing policy every one
      // of these was dropped silently.
      $cspNonce = sfConfig::get('csp_nonce', ''); ?>
<style <?php echo $cspNonce ? preg_replace('/^nonce=/', 'nonce="', $cspNonce).'"' : ''; ?>>
  .themeb-max-height-500px-3b01 { max-height:500px; }
  .themeb-text-align-center-7023 { text-align: center; }
</style>
<?php $hasIiifPlayer = false; try { use_helper('Media'); $hasIiifPlayer = function_exists('render_media_player'); } catch (Exception $e) {} ?>

<?php if (QubitTerm::MASTER_ID == $usageType) { ?>

  <?php if (isset($link)) { ?>
    <?php echo image_tag($representation->getFullPath(), ['alt' => __($resource->getDigitalObjectAltText() ?: 'Original %1% not accessible', ['%1%' => sfConfig::get('app_ui_label_digitalobject')])]); ?>
  <?php } else { ?>
    <?php echo link_to(image_tag($representation->getFullPath(), ['alt' => __($resource->getDigitalObjectAltText() ?: 'Open original %1%', ['%1%' => sfConfig::get('app_ui_label_digitalobject')])]), $link); ?>
  <?php } ?>

<?php } elseif (QubitTerm::CHAPTERS_ID == $usageType) { ?>

  <?php // Chapters handled internally by player ?>

<?php } elseif (QubitTerm::SUBTITLES_ID == $usageType) { ?>

  <?php // Subtitles handled internally by player ?>

<?php } elseif (QubitTerm::REFERENCE_ID == $usageType) { ?>

  <?php if ($showMediaPlayer) { ?>

    <?php if ($hasIiifPlayer) { ?>
      <?php // ahgIiifPlugin enabled - use AhgMediaPlayer JS player ?>
      <?php echo render_media_player([
          'id' => $resource->id,
          'name' => $resource->name,
          'path' => $resource->path,
          'mimeType' => $resource->mimeType,
          'mediaTypeId' => $resource->mediaTypeId ?? null,
          'object_id' => $resource->object->id ?? $resource->objectId ?? 0,
      ]); ?>
    <?php } else { ?>
      <?php // Native HTML5 player (no ahgIiifPlugin) ?>
      <video controls class="w-100 themeb-max-height-500px-3b01"  preload="metadata">
        <source src="<?php echo public_path($representation->getFullPath()); ?>" type="<?php echo htmlspecialchars($resource->mimeType); ?>">
        Your browser does not support video playback.
      </video>
    <?php } ?>

  <?php } else { ?>
    <div class="themeb-text-align-center-7023">
      <?php echo image_tag($representation->getFullPath(), ['style' => 'border: #999 1px solid', 'alt' => '']); ?>
    </div>
  <?php } ?>

  <?php if (isset($link) && \AtomExtensions\Services\AclService::check($resource->object, 'readMaster')) { ?>
    <div class="mt-2">
      <?php echo link_to(__('Download video'), $link, ['class' => 'btn btn-sm btn-outline-secondary']); ?>
    </div>
  <?php } ?>

<?php } elseif (QubitTerm::THUMBNAIL_ID == $usageType) { ?>

  <?php if ($iconOnly) { ?>
    <?php if (isset($link)) { ?>
      <?php echo link_to(image_tag($representation->getFullPath(), ['alt' => __($resource->getDigitalObjectAltText() ?: 'Open original %1%', ['%1%' => sfConfig::get('app_ui_label_digitalobject')])]), $link); ?>
    <?php } else { ?>
      <?php echo image_tag($representation->getFullPath(), ['alt' => __($resource->getDigitalObjectAltText() ?: 'Original %1% not accessible', ['%1%' => sfConfig::get('app_ui_label_digitalobject')])]); ?>
    <?php } ?>
  <?php } else { ?>
    <div class="digitalObject">
      <div class="digitalObjectRep">
        <?php if (isset($link)) { ?>
          <?php echo link_to(image_tag($representation->getFullPath(), ['alt' => __($resource->getDigitalObjectAltText() ?: 'Open original %1%', ['%1%' => sfConfig::get('app_ui_label_digitalobject')])]), $link); ?>
        <?php } else { ?>
          <?php echo image_tag($representation->getFullPath(), ['alt' => __($resource->getDigitalObjectAltText() ?: 'Original %1% not accessible', ['%1%' => sfConfig::get('app_ui_label_digitalobject')])]); ?>
        <?php } ?>
      </div>
      <div class="digitalObjectDesc">
        <?php echo wrap_text($resource->name, 18); ?>
      </div>
    </div>
  <?php } ?>

<?php } ?>
