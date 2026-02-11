<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0" aria-describedby="heading-label">
      <?php echo $isNew ? __('Add static page') : __('Edit static page'); ?>
    </h1>
    <?php if (!$isNew) { ?>
      <span class="small" id="heading-label">
        <?php echo esc_specialchars($pageRecord['title']); ?>
      </span>
    <?php } ?>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php if (!empty($errors)) { ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($sf_data->getRaw('errors') as $error) { ?>
          <li><?php echo $error; ?></li>
        <?php } ?>
      </ul>
    </div>
  <?php } ?>

  <?php
    $rawRecord = $sf_data->getRaw('pageRecord');
    $rawIsProtected = $sf_data->getRaw('isProtected');
  ?>

  <form method="post" action="<?php echo $isNew ? url_for('@staticpage_add') : url_for('@staticpage_edit?id=' . $rawRecord['id']); ?>" id="editForm">

    <?php echo $form->renderHiddenFields(); ?>

    <div class="accordion mb-3">
      <div class="accordion-item">
        <h2 class="accordion-header" id="pageInfo-heading">
          <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#pageInfo-collapse" aria-expanded="true" aria-controls="pageInfo-collapse">
            <?php echo __('Page content'); ?>
          </button>
        </h2>
        <div id="pageInfo-collapse" class="accordion-collapse collapse show" aria-labelledby="pageInfo-heading">
          <div class="accordion-body">

            <div class="mb-3">
              <label for="title" class="form-label">
                <?php echo __('Title'); ?>
                <span class="form-required" title="<?php echo __('This is a mandatory field.'); ?>">*</span>
              </label>
              <input type="text" class="form-control" id="title" name="title"
                     value="<?php echo esc_specialchars($rawRecord['title'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
              <label for="slug" class="form-label"><?php echo __('Slug'); ?></label>
              <?php if ($rawIsProtected) { ?>
                <input type="text" class="form-control" id="slug" name="slug"
                       value="<?php echo esc_specialchars($rawRecord['slug'] ?? ''); ?>" readonly>
                <div class="form-text"><?php echo __('This slug is protected and cannot be changed.'); ?></div>
              <?php } else { ?>
                <input type="text" class="form-control" id="slug" name="slug"
                       value="<?php echo esc_specialchars($rawRecord['slug'] ?? ''); ?>">
                <div class="form-text"><?php echo __('URL-friendly identifier. Leave blank to auto-generate from title.'); ?></div>
              <?php } ?>
            </div>

            <div class="mb-3">
              <label for="content" class="form-label"><?php echo __('Content'); ?></label>
              <textarea class="form-control" id="content" name="content" rows="20"><?php echo esc_specialchars($rawRecord['content'] ?? ''); ?></textarea>
              <div class="form-text"><?php echo __('HTML content is allowed.'); ?></div>
            </div>

          </div>
        </div>
      </div>
    </div>

    <ul class="actions mb-3 nav gap-2">
      <li><?php echo link_to(__('Cancel'), '@staticpage_list', ['class' => 'btn atom-btn-outline-light', 'role' => 'button']); ?></li>
      <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo $isNew ? __('Create') : __('Save'); ?>"></li>
    </ul>

  </form>

<?php end_slot(); ?>
