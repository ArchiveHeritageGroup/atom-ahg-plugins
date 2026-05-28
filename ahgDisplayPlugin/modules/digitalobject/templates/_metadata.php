<?php use_helper('Date'); ?>
<?php // For truncate_text helper used in embedded metadata section ?>
<?php
/**
 * AHG Display Plugin - Digital Object Metadata Override
 *
 * Blocks PDF downloads for non-authenticated users (GLAM/DAM wide policy)
 */

// Determine if master file is a PDF
$isPdf = isset($resource) && stripos($resource->mimeType ?? '', 'pdf') !== false;

// Override canAccessMasterFile for PDFs if user is not logged in
$canAccessMasterFileFinal = $canAccessMasterFile;
if ($isPdf && !$sf_user->isAuthenticated()) {
    $canAccessMasterFileFinal = false;
}

// Determine if this is an image digital object (for embedded metadata display)
$isImageObject = isset($resource) && $resource->mimeType
    && (strpos($resource->mimeType, 'image/') === 0);
?>

<section>

  <?php if ($relatedToIo) { ?>
    <?php echo link_to_if(SecurityPrivileges::editCredentials($sf_user, 'informationobject'), '<h2>'.__('%1% metadata', ['%1%' => sfConfig::get('app_ui_label_digitalobject')]).'</h2>', [$resource, 'module' => 'digitalobject', 'action' => 'edit'], ['title' => __('Edit %1%', ['%1%' => mb_strtolower(sfConfig::get('app_ui_label_digitalobject'))])]); ?>
  <?php } elseif ($relatedToActor) { ?>
    <?php echo link_to_if(SecurityPrivileges::editCredentials($sf_user, 'actor'), '<h2>'.__('%1% metadata', ['%1%' => sfConfig::get('app_ui_label_digitalobject')]).'</h2>', [$resource, 'module' => 'digitalobject', 'action' => 'edit'], ['title' => __('Edit %1%', ['%1%' => mb_strtolower(sfConfig::get('app_ui_label_digitalobject'))])]); ?>
  <?php } ?>

  <?php if ($showOriginalFileMetadata || $showPreservationCopyMetadata) { ?>

    <fieldset class="collapsible digital-object-metadata single">
      <legend><?php echo __('Preservation Copies'); ?></legend>

      <?php if ($showOriginalFileMetadata) { ?>

        <div class="digital-object-metadata-header">
          <h3><?php echo __('Original file'); ?> <i class="fa fa-archive<?php if (!$canAccessOriginalFile) { ?> inactive<?php } ?>" aria-hidden="true"></i></h3>
        </div>

        <div class="digital-object-metadata-body">
          <?php if ($showOriginalFileName) { ?>
            <?php echo render_show(__('Filename'), render_value($resource->object->originalFileName), ['fieldLabel' => 'originalFileName']); ?>
          <?php } ?>

          <?php if ($showOriginalFormatName) { ?>
            <?php echo render_show(__('Format name'), render_value($resource->object->formatName), ['fieldLabel' => 'originalFileFormatName']); ?>
          <?php } ?>

          <?php if ($showOriginalFormatVersion) { ?>
            <?php echo render_show(__('Format version'), render_value($resource->object->formatVersion), ['fieldLabel' => 'originalFileFormatVersion']); ?>
          <?php } ?>

          <?php if ($showOriginalFormatRegistryKey) { ?>
            <?php echo render_show(__('Format registry key'), render_value($resource->object->formatRegistryKey), ['fieldLabel' => 'originalFileFormatRegistryKey']); ?>
          <?php } ?>

          <?php if ($showOriginalFormatRegistryName) { ?>
            <?php echo render_show(__('Format registry name'), render_value($resource->object->formatRegistryName), ['fieldLabel' => 'originalFileFormatRegistryName']); ?>
          <?php } ?>

          <?php if ($showOriginalFileSize) { ?>
            <?php echo render_show(__('Filesize'), hr_filesize(intval((string) $resource->object->originalFileSize)), ['fieldLabel' => 'originalFileSize']); ?>
          <?php } ?>

          <?php if ($showOriginalFileIngestedAt) { ?>
            <?php echo render_show(__('Ingested'), format_date($originalFileIngestedAt, 'f'), ['fieldLabel' => 'originalFileIngestedAt']); ?>
          <?php } ?>

          <?php if ($showOriginalFilePermissions) { ?>
            <?php echo render_show(__('Permissions'), render_value($accessStatement), ['fieldLabel' => 'originalFilePermissions']); ?>
          <?php } ?>

          <?php if ($sf_user->isAuthenticated() && $relatedToIo) { ?>
            <?php if ($storageServicePluginEnabled) { ?>
              <?php include_partial(
                'arStorageService/aipDownload', ['resource' => $resource]
              ); ?>
            <?php } else { ?>
              <?php echo render_show(
                __('File UUID'),
                render_value($resource->object->objectUUID),
                ['fieldLabel' => 'objectUUID']
              ); ?>
              <?php echo render_show(
                __('AIP UUID'),
                render_value($resource->object->aipUUID),
                ['fieldLabel' => 'aipUUID']
              ); ?>
            <?php } ?>
          <?php } ?>

        </div>

      <?php } ?>

      <?php if ($showPreservationCopyMetadata) { ?>

        <div class="digital-object-metadata-header">
          <h3><?php echo __('Preservation copy'); ?> <i class="fa fa-archive<?php if (!$canAccessPreservationCopy) { ?> inactive<?php } ?>" aria-hidden="true"></i></h3>
        </div>

        <div class="digital-object-metadata-body">
          <?php if ($showPreservationCopyFileName) { ?>
            <?php echo render_show(__('Filename'), render_value($resource->object->preservationCopyFileName), ['fieldLabel' => 'preservationCopyFileName']); ?>
          <?php } ?>

          <?php if ($showPreservationCopyFileSize) { ?>
            <?php echo render_show(__('Filesize'), hr_filesize(intval((string) $resource->object->preservationCopyFileSize)), ['fieldLabel' => 'preservationCopyFileSize']); ?>
          <?php } ?>

          <?php if ($showPreservationCopyNormalizedAt) { ?>
            <?php echo render_show(__('Normalized'), format_date($preservationCopyNormalizedAt, 'f'), ['fieldLabel' => 'preservactionCopyNormalizedAt']); ?>
          <?php } ?>

          <?php if ($showPreservationCopyPermissions) { ?>
            <?php echo render_show(__('Permissions'), render_value($accessStatement), ['fieldLabel' => 'preservationCopyPermissions']); ?>
          <?php } ?>

        </div>

      <?php } ?>

    </fieldset>

  <?php } ?>

  <?php if ($showMasterFileMetadata || $showReferenceCopyMetadata || $showThumbnailCopyMetadata) { ?>

    <fieldset class="collapsible digital-object-metadata single">
      <legend><?php echo __('Access Copies'); ?></legend>

      <?php if ($showMasterFileMetadata) { ?>

        <div class="digital-object-metadata-header">
          <h3><?php echo __('Master file'); ?> <i class="fa fa-file<?php if (!$canAccessMasterFileFinal) { ?> inactive<?php } ?>" aria-hidden="true"></i></h3>
        </div>

        <div class="digital-object-metadata-body">
          <?php if ($showMasterFileGoogleMap) { ?>
            <div id="front-map" class="simple-map" data-key="<?php echo $googleMapsApiKey; ?>" data-latitude="<?php echo $latitude; ?>" data-longitude="<?php echo $longitude; ?>"></div>
          <?php } ?>

          <?php if ($showMasterFileGeolocation) { ?>
            <?php echo render_show(__('Latitude'), render_value($latitude), ['fieldLabel' => 'latitude']); ?>
            <?php echo render_show(__('Longitude'), render_value($longitude), ['fieldLabel' => 'longitude']); ?>
          <?php } ?>

          <?php if ($showMasterFileURL) { ?>
            <?php echo render_show(__('URL'), render_value($resource->path), ['fieldLabel' => 'url']); ?>
          <?php } ?>

          <?php if ($showMasterFileName) { ?>
            <?php if ($canAccessMasterFileFinal) { ?>
              <?php echo render_show(__('Filename'), link_to($resource->name, $resource->object->getDigitalObjectUrl(), ['target' => '_blank']), ['fieldLabel' => 'filename']); ?>
            <?php } else { ?>
              <?php echo render_show(__('Filename'), $resource->name, ['fieldLabel' => 'filename']); ?>
              <?php if ($isPdf && !$sf_user->isAuthenticated()) { ?>
                <div class="alert alert-info small mt-2">
                  <i class="fas fa-lock me-1"></i>
                  <?php echo __('Please log in to download this PDF file.'); ?>
                </div>
              <?php } ?>
            <?php } ?>
          <?php } ?>

          <?php if ($showMasterFileMediaType) { ?>
            <?php echo render_show(__('Media type'), render_value($resource->mediaType), ['fieldLabel' => 'mediaType']); ?>
          <?php } ?>

          <?php if ($showMasterFileMimeType) { ?>
            <?php echo render_show(__('Mime-type'), render_value($resource->mimeType), ['fieldLabel' => 'mimeType']); ?>
          <?php } ?>

          <?php if ($showMasterFileSize) { ?>
            <?php echo render_show(__('Filesize'), hr_filesize($resource->byteSize), ['fieldLabel' => 'filesize']); ?>
          <?php } ?>

          <?php if ($showMasterFileCreatedAt) { ?>
            <?php echo render_show(__('Uploaded'), format_date($resource->createdAt, 'f'), ['fieldLabel' => 'uploaded']); ?>
          <?php } ?>

          <?php if ($showMasterFilePermissions) { ?>
            <?php echo render_show(__('Permissions'), render_value($masterFileDenyReason), ['fieldLabel' => 'masterFilePermissions']); ?>
          <?php } ?>

        </div>

      <?php } ?>

      <?php if ($showReferenceCopyMetadata) { ?>

        <div class="digital-object-metadata-header">
          <h3><?php echo __('Reference copy'); ?> <i class="fa fa-file<?php if (!$canAccessReferenceCopy) { ?> inactive<?php } ?>" aria-hidden="true"></i></h3>
        </div>

        <div class="digital-object-metadata-body">
          <?php if ($showReferenceCopyFileName) { ?>
            <?php if ($canAccessReferenceCopy && $sf_user->isAuthenticated()) { ?>
              <?php echo render_show(__('Filename'), link_to($referenceCopy->name, $referenceCopy->getFullPath(), ['target' => '_blank']), ['fieldLabel' => 'referenceCopyFileName']); ?>
            <?php } else { ?>
              <?php echo render_show(__('Filename'), $referenceCopy->name, ['fieldLabel' => 'referenceCopyFileName']); ?>
            <?php } ?>
          <?php } ?>

          <?php if ($showReferenceCopyMediaType) { ?>
            <?php echo render_show(__('Media type'), render_value($referenceCopy->mediaType), ['fieldLabel' => 'referenceCopyFileName']); ?>
          <?php } ?>

          <?php if ($showReferenceCopyMimeType) { ?>
            <?php echo render_show(__('Mime-type'), render_value($referenceCopy->mimeType), ['fieldLabel' => 'referenceCopyMimeType']); ?>
          <?php } ?>

          <?php if ($showReferenceCopyFileSize) { ?>
            <?php echo render_show(__('Filesize'), hr_filesize($referenceCopy->byteSize), ['fieldLabel' => 'referenceCopyFileSize']); ?>
          <?php } ?>

          <?php if ($showReferenceCopyCreatedAt) { ?>
            <?php echo render_show(__('Uploaded'), format_date($referenceCopy->createdAt, 'f'), ['fieldLabel' => 'referenceCopyUploaded']); ?>
          <?php } ?>

          <?php if ($showReferenceCopyPermissions) { ?>
            <?php echo render_show(__('Permissions'), render_value($referenceCopyDenyReason), ['fieldLabel' => 'referenceCopyPermissions']); ?>
          <?php } ?>

        </div>

      <?php } ?>

      <?php if ($showThumbnailCopyMetadata) { ?>

        <div class="digital-object-metadata-header">
          <h3><?php echo __('Thumbnail copy'); ?> <i class="fa fa-file<?php if (!$canAccessThumbnailCopy) { ?> inactive<?php } ?>" aria-hidden="true"></i></h3>
        </div>

        <div class="digital-object-metadata-body">
          <?php if ($showThumbnailCopyFileName) { ?>
            <?php if ($canAccessThumbnailCopy) { ?>
              <?php echo render_show(__('Filename'), link_to($thumbnailCopy->name, $thumbnailCopy->getFullPath(), ['target' => '_blank']), ['fieldLabel' => 'thumbnailCopyFileName']); ?>
            <?php } else { ?>
              <?php echo render_show(__('Filename'), $thumbnailCopy->name, ['fieldLabel' => 'thumbnailCopyFileName']); ?>
            <?php } ?>
          <?php } ?>

          <?php if ($showThumbnailCopyMediaType) { ?>
            <?php echo render_show(__('Media type'), render_value($thumbnailCopy->mediaType), ['fieldLabel' => 'thumbnailCopyFileName']); ?>
          <?php } ?>

          <?php if ($showThumbnailCopyMimeType) { ?>
            <?php echo render_show(__('Mime-type'), render_value($thumbnailCopy->mimeType), ['fieldLabel' => 'thumbnailCopyMimeType']); ?>
          <?php } ?>

          <?php if ($showThumbnailCopyFileSize) { ?>
            <?php echo render_show(__('Filesize'), hr_filesize($thumbnailCopy->byteSize), ['fieldLabel' => 'thumbnailCopyFileSize']); ?>
          <?php } ?>

          <?php if ($showThumbnailCopyCreatedAt) { ?>
            <?php echo render_show(__('Uploaded'), format_date($thumbnailCopy->createdAt, 'f'), ['fieldLabel' => 'thumbnailCopyUploaded']); ?>
          <?php } ?>

          <?php if (!empty($thumbnailCopyDenyReason)) { ?>
            <?php echo render_show(__('Permissions'), render_value($thumbnailCopyDenyReason), ['fieldLabel' => 'thumbnailCopyPermissions']); ?>
          <?php } ?>

        </div>

      <?php } ?>

    </fieldset>

  <?php } ?>

  <?php
    // ========================================================================
    // Embedded Metadata Section (EXIF/IPTC/XMP) — #91 parity fix
    // Only shown for authenticated users viewing image/* digital objects
    // ========================================================================
    if ($isImageObject && $sf_user->isAuthenticated()) {
      $embeddedMetadata = null;
      $embeddedGpsData = null;
      $embeddedError = null;

      if ($resource && $resource->path && $resource->name) {
        $masterFilePath = sfConfig::get('sf_web_dir') . '/uploads/' . ltrim($resource->path, '/') . '/' . $resource->name;
        if (file_exists($masterFilePath)) {
          try {
            require_once sfConfig::get('sf_plugins_dir') . '/ahgMetadataExtractionPlugin/lib/Services/ahgUniversalMetadataExtractor.php';
            $extractor = new ahgUniversalMetadataExtractor($masterFilePath, $resource->mimeType);
            $embeddedMetadata = $extractor->extractAll();
            if (!empty($embeddedMetadata['gps'])) {
              $embeddedGpsData = $embeddedMetadata['gps'];
            }
          } catch (Exception $e) {
            $embeddedError = $e->getMessage();
          }
        }
      }

      $hasEmbeddedData = !empty($embeddedMetadata)
        && (
          isset($embeddedMetadata['exif'])
          || isset($embeddedMetadata['iptc'])
          || isset($embeddedMetadata['xmp'])
        );

      if ($hasEmbeddedData || $embeddedError) { ?>
      <fieldset class="collapsible digital-object-metadata single">
        <legend><?php echo __('Embedded Metadata'); ?></legend>

        <?php if ($embeddedError) { ?>
          <div class="alert alert-warning small mb-3">
            <i class="fa fa-exclamation-triangle me-1"></i>
            <?php echo __('Unable to extract embedded metadata: %1%', ['%1%' => $embeddedError]); ?>
          </div>
        <?php } ?>

        <?php if (!empty($embeddedMetadata['consolidated'])) { ?>
          <?php $cons = $embeddedMetadata['consolidated']; ?>

          <?php if (!empty($cons['title']) || !empty($cons['description']) || !empty($cons['creators']) || !empty($cons['copyright']) || !empty($cons['date_created'])) { ?>
            <div class="digital-object-metadata-body">
              <h4 class="mb-2 mt-2 text-muted"><?php echo __('Descriptive'); ?></h4>
              <?php if (!empty($cons['title'])) { ?>
                <?php echo render_show(__('Title'), render_value($cons['title']), ['fieldLabel' => 'embedded_title']); ?>
              <?php } ?>
              <?php if (!empty($cons['description'])) { ?>
                <?php echo render_show(__('Description'), render_value(\Text::limit($cons['description'], 300)), ['fieldLabel' => 'embedded_description']); ?>
              <?php } ?>
              <?php if (!empty($cons['creators'])) { ?>
                <?php foreach ((array)$cons['creators'] as $creator) { ?>
                  <?php echo render_show(__('Creator'), render_value($creator), ['fieldLabel' => 'embedded_creator']); ?>
                <?php } ?>
              <?php } ?>
              <?php if (!empty($cons['copyright'])) { ?>
                <?php echo render_show(__('Copyright'), render_value($cons['copyright']), ['fieldLabel' => 'embedded_copyright']); ?>
              <?php } ?>
              <?php if (!empty($cons['date_created'])) { ?>
                <?php echo render_show(__('Date Created'), render_value($cons['date_created']), ['fieldLabel' => 'embedded_date_created']); ?>
              <?php } ?>
              <?php if (!empty($cons['keywords'])) { ?>
                <div class="field" id="embedded_keywords">
                  <div class="field-label"><?php echo __('Keywords'); ?></div>
                  <div class="field-value">
                    <?php foreach ((array)$cons['keywords'] as $keyword) { ?>
                      <span class="badge bg-secondary me-1 mb-1"><?php echo esc_entities($keyword); ?></span>
                    <?php } ?>
                  </div>
                </div>
              <?php } ?>
              <?php
                $loc = $cons['location'] ?? [];
                $locParts = array_filter([
                  $loc['city'] ?? null,
                  $loc['state'] ?? null,
                  $loc['country'] ?? null,
                ]);
              ?>
              <?php if (!empty($locParts)) { ?>
                <div class="field" id="embedded_location">
                  <div class="field-label"><?php echo __('Location'); ?></div>
                  <div class="field-value"><?php echo esc_entities(implode(', ', $locParts)); ?></div>
                </div>
              <?php } ?>
            </div>
          <?php } ?>

          <?php if (!empty($cons['camera']) || !empty($cons['technical'])) { ?>
            <div class="digital-object-metadata-body">
              <h4 class="mb-2 mt-2 text-muted"><?php echo __('Technical'); ?></h4>
              <?php $cam = $cons['camera'] ?? []; ?>
              <?php if (!empty($cam['make']) || !empty($cam['model'])) { ?>
                <?php
                  $cameraStr = trim(($cam['make'] ?? '') . ' ' . ($cam['model'] ?? ''));
                ?>
                <?php echo render_show(__('Camera'), render_value($cameraStr), ['fieldLabel' => 'embedded_camera']); ?>
              <?php } ?>
              <?php if (!empty($cons['technical'])) { ?>
                <?php $tech = $cons['technical']; ?>
                <?php if (!empty($tech['exposure_time'])) { ?>
                  <?php echo render_show(__('Exposure'), render_value($tech['exposure_time']), ['fieldLabel' => 'embedded_exposure']); ?>
                <?php } ?>
                <?php if (!empty($tech['f_number'])) { ?>
                  <?php echo render_show(__('Aperture'), render_value('f/' . $tech['f_number']), ['fieldLabel' => 'embedded_aperture']); ?>
                <?php } ?>
                <?php if (!empty($tech['iso'])) { ?>
                  <?php echo render_show(__('ISO'), render_value($tech['iso']), ['fieldLabel' => 'embedded_iso']); ?>
                <?php } ?>
                <?php if (!empty($tech['focal_length'])) { ?>
                  <?php echo render_show(__('Focal Length'), render_value($tech['focal_length']), ['fieldLabel' => 'embedded_focal_length']); ?>
                <?php } ?>
              <?php } ?>
            </div>
          <?php } ?>

          <?php if (!empty($embeddedGpsData)) { ?>
            <div class="digital-object-metadata-body">
              <h4 class="mb-2 mt-2 text-muted"><?php echo __('GPS Coordinates'); ?></h4>
              <?php if (isset($embeddedGpsData['latitude'])) { ?>
                <?php echo render_show(__('Latitude'), render_value(sprintf('%.6f', $embeddedGpsData['latitude'])), ['fieldLabel' => 'embedded_latitude']); ?>
              <?php } ?>
              <?php if (isset($embeddedGpsData['longitude'])) { ?>
                <?php echo render_show(__('Longitude'), render_value(sprintf('%.6f', $embeddedGpsData['longitude'])), ['fieldLabel' => 'embedded_longitude']); ?>
              <?php } ?>
              <?php if (isset($embeddedGpsData['altitude'])) { ?>
                <?php echo render_show(__('Altitude'), render_value(sprintf('%.1f m', $embeddedGpsData['altitude'])), ['fieldLabel' => 'embedded_altitude']); ?>
              <?php } ?>
            </div>
          <?php } ?>

        <?php } elseif (!empty($embeddedMetadata)) { ?>
          <div class="digital-object-metadata-body">
            <p class="text-muted small"><?php echo __('Basic file metadata extracted.'); ?></p>
          </div>
        <?php } ?>
      </fieldset>
  <?php }
    }
  ?>

</section>
