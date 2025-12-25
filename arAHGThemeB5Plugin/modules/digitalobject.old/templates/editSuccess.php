<?php decorate_with('layout_1col') ?>

<?php
$resourceData = $sf_data->getRaw('resourceData');
$parent = $sf_data->getRaw('parent');
$form = $sf_data->getRaw('form');
$referenceImage = $sf_data->getRaw('referenceImage');
$thumbnailImage = $sf_data->getRaw('thumbnailImage');
$chaptersTrack = $sf_data->getRaw('chaptersTrack');
$subtitlesTrack = $sf_data->getRaw('subtitlesTrack');
$subtitlesLanguage = $sf_data->getRaw('subtitlesLanguage') ?? null;
$maxUploadSize = $sf_data->getRaw('maxUploadSize');
$canThumbnail = $sf_data->getRaw('canThumbnail');
$showCompoundObjectToggle = $sf_data->getRaw('showCompoundObjectToggle');

$MEDIA_VIDEO = 138;
$MEDIA_AUDIO = 135;
$MEDIA_IMAGE = 136;

function getImgSrc($obj) {
    if (!$obj || !$obj->path || !$obj->name) return null;
    return rtrim($obj->path, '/') . '/' . $obj->name;
}

$masterSrc = getImgSrc($resourceData);
$refSrc = getImgSrc($referenceImage);
$thumbSrc = getImgSrc($thumbnailImage);

$mediaIcon = 'fa-file';
if ($resourceData->media_type_id == $MEDIA_VIDEO) $mediaIcon = 'fa-film';
elseif ($resourceData->media_type_id == $MEDIA_AUDIO) $mediaIcon = 'fa-music';
elseif ($resourceData->media_type_id == $MEDIA_IMAGE) $mediaIcon = 'fa-image';
?>

<?php slot('title') ?>
  <h1 class="multiline">
    <i class="fas <?php echo $mediaIcon ?> me-2"></i>
    <?php echo __('Edit %1%', ['%1%' => mb_strtolower(sfConfig::get('app_ui_label_digitalobject', 'digital object'))]) ?>
    <?php if ($parent && $parent->title): ?>
      <span class="sub"><?php echo esc_entities($parent->title) ?></span>
    <?php endif; ?>
  </h1>
<?php end_slot() ?>

<?php slot('content') ?>

  <?php echo $form->renderGlobalErrors() ?>

  <form method="post" enctype="multipart/form-data" action="<?php echo url_for(['module' => 'digitalobject', 'action' => 'edit', 'id' => $resourceData->id]) ?>">
    <?php echo $form->renderHiddenFields() ?>

    <div class="row">
      <div class="col-lg-8">
        
        <!-- Preview -->
        <?php if ($thumbSrc || $refSrc || $resourceData->media_type_id == $MEDIA_VIDEO || $resourceData->media_type_id == $MEDIA_AUDIO): ?>
        <div class="card mb-4">
          <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-eye me-2"></i><?php echo __('Preview') ?></h5>
          </div>
          <div class="card-body text-center bg-light" style="min-height: 200px;">
            <?php if ($resourceData->media_type_id == $MEDIA_IMAGE && ($refSrc || $thumbSrc)): ?>
              <img src="<?php echo $refSrc ?: $thumbSrc ?>" class="img-fluid rounded shadow" style="max-height: 400px;" alt="">
            <?php elseif (in_array($resourceData->media_type_id, [$MEDIA_VIDEO, $MEDIA_AUDIO])): ?>
              <?php echo get_component('digitalobject', 'show', ['resource' => QubitDigitalObject::getById($resourceData->id), 'usageType' => QubitTerm::REFERENCE_ID]) ?>
            <?php elseif ($thumbSrc): ?>
              <img src="<?php echo $thumbSrc ?>" class="img-fluid rounded" style="max-height: 200px;" alt="">
            <?php else: ?>
              <div class="py-5 text-muted">
                <i class="fas <?php echo $mediaIcon ?> fa-4x mb-3"></i>
                <p><?php echo __('No preview available') ?></p>
              </div>
            <?php endif; ?>
          </div>
          <?php if ($masterSrc): ?>
          <div class="card-footer text-center">
            <a href="<?php echo $masterSrc ?>" target="_blank" class="btn btn-sm btn-outline-primary">
              <i class="fas fa-external-link-alt me-1"></i><?php echo __('View Original') ?>
            </a>
            <a href="<?php echo $masterSrc ?>" download class="btn btn-sm btn-outline-secondary">
              <i class="fas fa-download me-1"></i><?php echo __('Download') ?>
            </a>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Master -->
        <div class="card mb-4">
          <div class="card-header"><h5 class="mb-0"><i class="fas fa-file me-2"></i><?php echo __('Master') ?></h5></div>
          <div class="card-body">
            <div class="row mb-3">
              <div class="col-md-6">
                <label class="form-label text-muted small mb-0"><?php echo __('Filename') ?></label>
                <p class="fw-bold mb-0"><?php echo esc_entities($resourceData->name) ?></p>
              </div>
              <div class="col-md-3">
                <label class="form-label text-muted small mb-0"><?php echo __('Filesize') ?></label>
                <p class="mb-0"><?php echo hr_filesize($resourceData->byte_size) ?></p>
              </div>
              <div class="col-md-3">
                <label class="form-label text-muted small mb-0"><?php echo __('MIME') ?></label>
                <p class="mb-0"><code><?php echo esc_entities($resourceData->mime_type) ?></code></p>
              </div>
            </div>
            
            <?php echo $form->mediaType->label(__('Media type'))->renderRow() ?>
            <?php echo $form->digitalObjectAltText->label(__('Alt text'))->renderRow() ?>

            <?php if ($showCompoundObjectToggle): ?>
              <?php echo $form->displayAsCompound->label(__('View children as compound?'))->renderRow() ?>
            <?php endif; ?>

            <div class="row">
              <div class="col-md-6"><?php echo $form->latitude->label(__('Latitude'))->renderRow() ?></div>
              <div class="col-md-6"><?php echo $form->longitude->label(__('Longitude'))->renderRow() ?></div>
            </div>

            <?php if (isset($form->file)): ?>
            <hr>
            <label class="form-label"><?php echo __('Replace master file') ?></label>
            <?php echo $form->file->render(['class' => 'form-control']) ?>
            <div class="form-text"><?php echo __('Select a new file to replace the existing master.') ?></div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Reference -->
        <div class="card mb-4">
          <div class="card-header"><h5 class="mb-0"><i class="fas fa-image me-2"></i><?php echo __('Reference representation') ?></h5></div>
          <div class="card-body">
            <?php if ($referenceImage): ?>
              <div class="row align-items-center">
                <?php if ($refSrc): ?>
                <div class="col-md-3 text-center mb-3 mb-md-0">
                  <img src="<?php echo $refSrc ?>" class="img-thumbnail" style="max-height: 120px;" alt="">
                </div>
                <?php endif; ?>
                <div class="col">
                  <p class="mb-1"><strong><?php echo __('Filename') ?>:</strong> <?php echo esc_entities($referenceImage->name) ?></p>
                  <p class="mb-2"><strong><?php echo __('Filesize') ?>:</strong> <?php echo hr_filesize($referenceImage->byte_size) ?></p>
                  <a href="<?php echo $refSrc ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-external-link-alt me-1"></i><?php echo __('View') ?></a>
                  <?php echo link_to('<i class="fas fa-trash me-1"></i>' . __('Delete'), ['module' => 'digitalobject', 'action' => 'delete', 'id' => $referenceImage->id], ['class' => 'btn btn-sm btn-outline-danger']) ?>
                </div>
              </div>
            <?php else: ?>
              <?php if (isset($form['repFile_reference'])): ?>
                <?php echo $form['repFile_reference']->label(__('Upload reference image'))->renderRow() ?>
              <?php endif; ?>
              <?php if ($canThumbnail && isset($form['generateDerivative_reference'])): ?>
                <?php echo $form['generateDerivative_reference']->label(__('Auto-generate from master'))->renderRow() ?>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Thumbnail -->
        <div class="card mb-4">
          <div class="card-header"><h5 class="mb-0"><i class="fas fa-th-large me-2"></i><?php echo __('Thumbnail representation') ?></h5></div>
          <div class="card-body">
            <?php if ($thumbnailImage): ?>
              <div class="row align-items-center">
                <?php if ($thumbSrc): ?>
                <div class="col-md-2 text-center mb-3 mb-md-0">
                  <img src="<?php echo $thumbSrc ?>" class="img-thumbnail" style="max-height: 80px;" alt="">
                </div>
                <?php endif; ?>
                <div class="col">
                  <p class="mb-1"><strong><?php echo __('Filename') ?>:</strong> <?php echo esc_entities($thumbnailImage->name) ?></p>
                  <p class="mb-2"><strong><?php echo __('Filesize') ?>:</strong> <?php echo hr_filesize($thumbnailImage->byte_size) ?></p>
                  <a href="<?php echo $thumbSrc ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-external-link-alt me-1"></i><?php echo __('View') ?></a>
                  <?php echo link_to('<i class="fas fa-trash me-1"></i>' . __('Delete'), ['module' => 'digitalobject', 'action' => 'delete', 'id' => $thumbnailImage->id], ['class' => 'btn btn-sm btn-outline-danger']) ?>
                </div>
              </div>
            <?php else: ?>
              <?php if (isset($form['repFile_thumbnail'])): ?>
                <?php echo $form['repFile_thumbnail']->label(__('Upload thumbnail image'))->renderRow() ?>
              <?php endif; ?>
              <?php if ($canThumbnail && isset($form['generateDerivative_thumbnail'])): ?>
                <?php echo $form['generateDerivative_thumbnail']->label(__('Auto-generate from master'))->renderRow() ?>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Video/Audio tracks -->
        <?php if (in_array($resourceData->media_type_id, [$MEDIA_VIDEO, $MEDIA_AUDIO])): ?>

          <?php if ($resourceData->media_type_id == $MEDIA_VIDEO): ?>
          <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-list-ol me-2"></i><?php echo __('Chapters') ?></h5></div>
            <div class="card-body">
              <?php if ($chaptersTrack): ?>
                <div class="d-flex justify-content-between align-items-center">
                  <span><strong><?php echo __('Filename') ?>:</strong> <?php echo esc_entities($chaptersTrack->name) ?></span>
                  <div>
                    <a href="<?php echo getImgSrc($chaptersTrack) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-external-link-alt"></i></a>
                    <?php echo link_to('<i class="fas fa-trash"></i>', ['module' => 'digitalobject', 'action' => 'delete', 'id' => $chaptersTrack->id], ['class' => 'btn btn-sm btn-outline-danger']) ?>
                  </div>
                </div>
              <?php elseif (isset($form['trackFile_chapters'])): ?>
                <?php echo $form['trackFile_chapters']->label(__('Upload chapters file (.vtt|.srt)'))->renderRow() ?>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

          <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-closed-captioning me-2"></i><?php echo __('Subtitles') ?></h5></div>
            <div class="card-body">
              <?php if ($subtitlesTrack): ?>
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <p class="mb-1"><strong><?php echo __('Filename') ?>:</strong> <?php echo esc_entities($subtitlesTrack->name) ?></p>
                    <?php if ($subtitlesLanguage): ?>
                      <p class="mb-0"><strong><?php echo __('Language') ?>:</strong> <?php echo esc_entities($subtitlesLanguage) ?></p>
                    <?php endif; ?>
                  </div>
                  <div>
                    <a href="<?php echo getImgSrc($subtitlesTrack) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-external-link-alt me-1"></i><?php echo __('View') ?></a>
                    <a href="<?php echo getImgSrc($subtitlesTrack) ?>" download class="btn btn-sm btn-outline-secondary"><i class="fas fa-download"></i></a>
                    <?php echo link_to('<i class="fas fa-trash"></i>', ['module' => 'digitalobject', 'action' => 'delete', 'id' => $subtitlesTrack->id], ['class' => 'btn btn-sm btn-outline-danger']) ?>
                  </div>
                </div>
              <?php elseif (isset($form['trackFile_subtitles'])): ?>
                <div class="row">
                  <div class="col-md-6"><?php echo $form['trackFile_subtitles']->label(__('Upload subtitles (.vtt|.srt)'))->renderRow() ?></div>
                  <div class="col-md-6"><?php if (isset($form['lang_subtitles'])) echo $form['lang_subtitles']->label(__('Language'))->renderRow() ?></div>
                </div>
              <?php endif; ?>
            </div>
          </div>

        <?php endif; ?>

      </div>

      <!-- Sidebar -->
      <div class="col-lg-4">
        <div class="card mb-4">
          <div class="card-header bg-info text-white"><h6 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('Info') ?></h6></div>
          <div class="card-body">
            <table class="table table-sm table-borderless mb-0">
              <tr><td class="text-muted"><?php echo __('ID') ?></td><td><strong><?php echo $resourceData->id ?></strong></td></tr>
              <tr><td class="text-muted"><?php echo __('Size') ?></td><td><?php echo hr_filesize($resourceData->byte_size) ?></td></tr>
              <?php if ($resourceData->checksum): ?>
              <tr><td class="text-muted"><?php echo __('Checksum') ?></td><td><code class="small"><?php echo substr($resourceData->checksum, 0, 12) ?>...</code></td></tr>
              <?php endif; ?>
            </table>
          </div>
        </div>

        <div class="card mb-4">
          <div class="card-header"><h6 class="mb-0"><i class="fas fa-layer-group me-2"></i><?php echo __('Derivatives') ?></h6></div>
          <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between"><?php echo __('Reference') ?><span class="badge bg-<?php echo $referenceImage ? 'success' : 'secondary' ?>"><i class="fas fa-<?php echo $referenceImage ? 'check' : 'minus' ?>"></i></span></li>
            <li class="list-group-item d-flex justify-content-between"><?php echo __('Thumbnail') ?><span class="badge bg-<?php echo $thumbnailImage ? 'success' : 'secondary' ?>"><i class="fas fa-<?php echo $thumbnailImage ? 'check' : 'minus' ?>"></i></span></li>
            <?php if (in_array($resourceData->media_type_id, [$MEDIA_VIDEO, $MEDIA_AUDIO])): ?>
            <li class="list-group-item d-flex justify-content-between"><?php echo __('Subtitles') ?><span class="badge bg-<?php echo $subtitlesTrack ? 'success' : 'secondary' ?>"><i class="fas fa-<?php echo $subtitlesTrack ? 'check' : 'minus' ?>"></i></span></li>
            <?php endif; ?>
          </ul>
        </div>

        <?php if ($parent && $parent->slug): ?>
        <div class="card">
          <div class="card-body text-center">
            <a href="<?php echo url_for(['module' => $parent->module, 'slug' => $parent->slug]) ?>" class="btn btn-outline-primary">
              <i class="fas fa-arrow-left me-2"></i><?php echo __('Back to record') ?>
            </a>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <section class="actions">
      <ul>
        <li><?php echo link_to(__('Delete'), ['module' => 'digitalobject', 'action' => 'delete', 'id' => $resourceData->id], ['class' => 'c-btn c-btn-delete']) ?></li>
        <li><?php echo link_to(__('Cancel'), ['module' => $parent->module, 'slug' => $parent->slug], ['class' => 'c-btn']) ?></li>
        <li><input class="c-btn c-btn-submit" type="submit" value="<?php echo __('Save') ?>"></li>
      </ul>
    </section>

  </form>

<?php end_slot() ?>
