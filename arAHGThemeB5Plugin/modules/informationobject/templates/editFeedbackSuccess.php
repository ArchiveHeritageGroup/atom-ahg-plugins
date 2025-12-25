<?php decorate_with('layout_2col.php'); ?>

<?php slot('sidebar'); ?>
  <?php include_component('informationobject', 'contextMenu'); ?>
<?php end_slot(); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Feedback'); ?></h1>
  <span class="text-muted"><?php echo render_title($resource); ?></span>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php echo $form->renderGlobalErrors(); ?>
  <?php echo $form->renderFormTag(url_for([$resource, 'module' => 'informationobject', 'action' => 'editFeedback']), ['id' => 'feedbackForm']); ?>
  <?php echo $form->renderHiddenFields(); ?>

  <!-- Identification -->
  <div class="card mb-3">
    <div class="card-header bg-success text-white">
      <i class="fas fa-info-circle me-2"></i><?php echo __('Identification area'); ?>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6 mb-3">
          <?php echo $form->name
            ->label(__('Name of Collection/Item'))
            ->renderRow(['class' => 'form-control', 'readonly' => 'readonly']); ?>
        </div>
        <div class="col-md-6 mb-3">
          <?php echo $form->identifier
            ->label(__('Identifier'))
            ->renderRow(['class' => 'form-control', 'readonly' => 'readonly']); ?>
        </div>
      </div>
      <div class="row">
        <div class="col-md-6 mb-3">
          <?php echo $form->unique_identifier
            ->label(__('Unique Identifier'))
            ->renderRow(['class' => 'form-control', 'readonly' => 'readonly']); ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Feedback -->
  <div class="card mb-3">
    <div class="card-header bg-success text-white">
      <i class="fas fa-comment-alt me-2"></i><?php echo __('Feedback area'); ?>
    </div>
    <div class="card-body">
      <div class="mb-3">
        <?php echo $form->feed_type
          ->label(__('Feedback Type'))
          ->renderRow(['class' => 'form-select']); ?>
      </div>
      
      <div class="mb-3">
        <?php echo $form->remarks
          ->label(__('Remarks/Feedback/Comments'))
          ->renderRow(['class' => 'form-control', 'rows' => 5]); ?>
      </div>
    </div>
  </div>

  <!-- Contact Information -->
  <div class="card mb-3">
    <div class="card-header bg-success text-white">
      <i class="fas fa-user me-2"></i><?php echo __('Contact Information'); ?>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6 mb-3">
          <?php echo $form->feed_name
            ->label(__('Name'))
            ->renderRow(['class' => 'form-control']); ?>
        </div>
        <div class="col-md-6 mb-3">
          <?php echo $form->feed_surname
            ->label(__('Surname'))
            ->renderRow(['class' => 'form-control']); ?>
        </div>
      </div>
      
      <div class="row">
        <div class="col-md-6 mb-3">
          <?php echo $form->feed_phone
            ->label(__('Phone Number'))
            ->renderRow(['class' => 'form-control']); ?>
        </div>
        <div class="col-md-6 mb-3">
          <?php echo $form->feed_email
            ->label(__('e-Mail Address'))
            ->renderRow(['class' => 'form-control', 'type' => 'email']); ?>
        </div>
      </div>
      
      <div class="mb-3">
        <?php echo $form->feed_relationship
          ->label(__('Relationship to item'))
          ->renderRow(['class' => 'form-control']); ?>
      </div>
    </div>
  </div>

  <!-- Actions -->
  <section class="actions">
    <ul class="list-unstyled d-flex flex-wrap gap-2">
      <li><?php echo link_to(__('Cancel'), [$resource, 'module' => 'informationobject'], ['class' => 'btn atom-btn-outline-light']); ?></li>
      <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Submit Feedback'); ?>"></li>
    </ul>
  </section>

  </form>

<?php end_slot(); ?>
