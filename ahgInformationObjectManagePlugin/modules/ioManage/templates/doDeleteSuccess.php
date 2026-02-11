<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Delete digital object'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php $rawDo = $sf_data->getRaw('digitalObject'); ?>

  <?php if (!empty($errors)) { ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($sf_data->getRaw('errors') as $error) { ?>
          <li><?php echo $error; ?></li>
        <?php } ?>
      </ul>
    </div>
  <?php } ?>

  <div class="alert alert-warning">
    <h5 class="alert-heading">
      <i class="fas fa-exclamation-triangle me-1" aria-hidden="true"></i>
      <?php echo __('Are you sure you want to delete this digital object?'); ?>
    </h5>
    <p class="mb-0"><?php echo __('This action cannot be undone. The file and all derivatives will be permanently removed.'); ?></p>
  </div>

  <div class="card mb-3">
    <div class="card-header">
      <h5 class="mb-0"><?php echo __('Digital object details'); ?></h5>
    </div>
    <div class="card-body">

      <div class="row mb-2">
        <div class="col-sm-4 fw-bold"><?php echo __('Filename'); ?></div>
        <div class="col-sm-8"><?php echo esc_specialchars($rawDo['name']); ?></div>
      </div>

      <div class="row mb-2">
        <div class="col-sm-4 fw-bold"><?php echo __('MIME type'); ?></div>
        <div class="col-sm-8"><?php echo esc_specialchars($rawDo['mimeType']); ?></div>
      </div>

      <div class="row mb-2">
        <div class="col-sm-4 fw-bold"><?php echo __('File size'); ?></div>
        <div class="col-sm-8"><?php echo esc_specialchars($fileSizeFormatted); ?></div>
      </div>

      <?php if ($derivativeCount > 0) { ?>
        <div class="row mb-2">
          <div class="col-sm-4 fw-bold"><?php echo __('Derivatives'); ?></div>
          <div class="col-sm-8">
            <span class="badge bg-secondary">
              <?php echo $derivativeCount; ?> <?php echo __('derivative(s) will also be deleted'); ?>
            </span>
          </div>
        </div>
      <?php } ?>

    </div>
  </div>

  <?php echo $form->renderGlobalErrors(); ?>

  <form method="post" action="<?php echo url_for('@io_do_delete?id=' . $rawDo['id']); ?>">

    <?php echo $form->renderHiddenFields(); ?>
    <input type="hidden" name="sf_method" value="delete">

    <ul class="actions mb-3 nav gap-2">
      <li>
        <?php
        $cancelUrl = $ioSlug ? '/' . $ioSlug : '/';
        echo link_to(
            __('Cancel'),
            $cancelUrl,
            ['class' => 'btn atom-btn-outline-light', 'role' => 'button']
        );
        ?>
      </li>
      <li>
        <input class="btn atom-btn-outline-danger" type="submit" value="<?php echo __('Delete'); ?>">
      </li>
    </ul>

  </form>

<?php end_slot(); ?>
