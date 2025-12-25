<?php decorate_with('layout_2col.php'); ?>

<?php slot('sidebar'); ?>
  <?php include_component('informationobject', 'contextMenu'); ?>
<?php end_slot(); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Request to Publish'); ?></h1>
  <span class="text-muted"><?php echo render_title($resource); ?></span>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php echo $form->renderGlobalErrors(); ?>
  <?php echo $form->renderFormTag(url_for([$resource, 'module' => 'informationobject', 'action' => 'editRequestToPublish']), ['id' => 'requestToPublishForm']); ?>
  <?php echo $form->renderHiddenFields(); ?>

  <div class="alert alert-info mb-4">
    <i class="fas fa-info-circle me-2"></i>
    <?php echo __('You are requesting permission to publish the following image/s:'); ?>
    <strong><?php echo render_title($resource); ?></strong>
  </div>

  <!-- Contact Information -->
  <div class="card mb-3">
    <div class="card-header bg-success text-white">
      <i class="fas fa-user me-2"></i><?php echo __('Contact Information'); ?>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6 mb-3">
          <?php echo $form->rtp_name
            ->label(__('Name'))
            ->renderRow(['class' => 'form-control']); ?>
        </div>
        <div class="col-md-6 mb-3">
          <?php echo $form->rtp_surname
            ->label(__('Surname'))
            ->renderRow(['class' => 'form-control']); ?>
        </div>
      </div>
      
      <div class="mb-3">
        <?php echo $form->rtp_institution
          ->label(__('Institution'))
          ->renderRow(['class' => 'form-control']); ?>
      </div>
      
      <div class="row">
        <div class="col-md-6 mb-3">
          <?php echo $form->rtp_phone
            ->label(__('Phone Number'))
            ->renderRow(['class' => 'form-control']); ?>
        </div>
        <div class="col-md-6 mb-3">
          <?php echo $form->rtp_email
            ->label(__('e-Mail Address'))
            ->renderRow(['class' => 'form-control', 'type' => 'email']); ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Request Details -->
  <div class="card mb-3">
    <div class="card-header bg-success text-white">
      <i class="fas fa-file-alt me-2"></i><?php echo __('Request Details'); ?>
    </div>
    <div class="card-body">
      <div class="mb-3">
        <?php echo $form->rtp_planned_use
          ->label(__('Planned use of Image'))
          ->renderRow(['class' => 'form-control', 'rows' => 3]); ?>
      </div>
      
      <div class="mb-3">
        <?php echo $form->rtp_need_image_by
          ->label(__('Need image by'))
          ->renderRow(['class' => 'form-control']); ?>
      </div>
      
      <div class="mb-3">
        <?php echo $form->rtp_motivation
          ->label(__('Motivation'))
          ->renderRow(['class' => 'form-control', 'rows' => 4]); ?>
      </div>
    </div>
  </div>

  <!-- Actions -->
  <section class="actions">
    <ul class="list-unstyled d-flex flex-wrap gap-2">
      <li><?php echo link_to(__('Cancel'), [$resource, 'module' => 'informationobject'], ['class' => 'btn atom-btn-outline-light']); ?></li>
      <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Submit Request'); ?>"></li>
    </ul>
  </section>

  </form>

<?php end_slot(); ?>
