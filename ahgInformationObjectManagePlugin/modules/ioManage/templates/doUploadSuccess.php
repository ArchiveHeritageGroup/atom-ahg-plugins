<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0">
      <?php echo __('Upload digital object'); ?>
    </h1>
    <span class="small">
      <?php echo esc_specialchars($io['title'] ?: __('Untitled')); ?>
    </span>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php $rawIo = $sf_data->getRaw('io'); ?>
  <?php $rawExistingDo = $sf_data->getRaw('existingDo'); ?>

  <?php if (!empty($errors)) { ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($sf_data->getRaw('errors') as $error) { ?>
          <li><?php echo $error; ?></li>
        <?php } ?>
      </ul>
    </div>
  <?php } ?>

  <div class="card mb-3">
    <div class="card-header">
      <h5 class="mb-0"><?php echo __('File upload'); ?></h5>
    </div>
    <div class="card-body">

      <?php if ($rawExistingDo) { ?>
        <div class="alert alert-info">
          <strong><?php echo __('A digital object already exists for this description:'); ?></strong>
          <br>
          <?php echo esc_specialchars($rawExistingDo['name']); ?>
          (<?php echo esc_specialchars($rawExistingDo['mimeType']); ?>)
        </div>
      <?php } ?>

      <form method="post" action="<?php echo url_for('@io_do_upload?io=' . rawurlencode($rawIo['slug'])); ?>" enctype="multipart/form-data">

        <?php echo $form->renderHiddenFields(); ?>

        <div class="mb-3">
          <label for="file" class="form-label">
            <?php echo __('Select file'); ?>
            <span class="form-required" title="<?php echo __('This is a mandatory field.'); ?>">*</span>
          </label>
          <input type="file" class="form-control" id="file" name="file"
                 accept="image/*,application/pdf,audio/*,video/*,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.xml,.json"
                 required>
          <div class="form-text text-muted small">
            <?php echo __('Accepted: images, PDFs, audio, video, office documents.'); ?>
            <?php echo __('Maximum file size: %1%', ['%1%' => $maxUploadSize]); ?>
          </div>
        </div>

        <?php if ($rawExistingDo) { ?>
          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="replace" name="replace" value="1">
            <label class="form-check-label" for="replace">
              <?php echo __('Replace existing digital object'); ?>
            </label>
            <div class="form-text text-muted small">
              <?php echo __('This will permanently delete the current digital object and all its derivatives.'); ?>
            </div>
          </div>
        <?php } ?>

        <ul class="actions mb-3 nav gap-2">
          <li>
            <?php echo link_to(
                __('Cancel'),
                '/' . $rawIo['slug'],
                ['class' => 'btn atom-btn-outline-light', 'role' => 'button']
            ); ?>
          </li>
          <li>
            <input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Upload'); ?>">
          </li>
        </ul>

      </form>

    </div>
  </div>

<?php end_slot(); ?>
