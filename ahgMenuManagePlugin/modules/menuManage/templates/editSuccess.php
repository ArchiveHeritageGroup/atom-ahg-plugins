<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0" aria-describedby="heading-label">
      <?php echo $isNew ? __('Add menu') : __('Edit menu'); ?>
    </h1>
    <?php if (!$isNew) { ?>
      <span class="small" id="heading-label">
        <?php echo esc_specialchars($menuRecord['label'] ?: $menuRecord['name']); ?>
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
    $rawRecord = $sf_data->getRaw('menuRecord');
    $rawIsProtected = $sf_data->getRaw('isProtected');
    $rawIsNew = $sf_data->getRaw('isNew');
    $rawParentChoices = $sf_data->getRaw('parentChoices');
  ?>

  <form method="post" action="<?php echo $rawIsNew ? url_for('@menu_add') : url_for('@menu_edit?id=' . $rawRecord['id']); ?>" id="editForm">

    <?php echo $form->renderHiddenFields(); ?>

    <div class="accordion mb-3">
      <div class="accordion-item">
        <h2 class="accordion-header" id="menuInfo-heading">
          <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#menuInfo-collapse" aria-expanded="true" aria-controls="menuInfo-collapse">
            <?php echo __('Menu item details'); ?>
          </button>
        </h2>
        <div id="menuInfo-collapse" class="accordion-collapse collapse show" aria-labelledby="menuInfo-heading">
          <div class="accordion-body">

            <div class="mb-3">
              <label for="name" class="form-label">
                <?php echo __('Name'); ?>
                <span class="form-required" title="<?php echo __('This is a mandatory field.'); ?>">*</span>
              </label>
              <?php if ($rawIsProtected) { ?>
                <input type="text" class="form-control" id="name" name="name"
                       value="<?php echo esc_specialchars($rawRecord['name'] ?? ''); ?>" disabled>
                <input type="hidden" name="name" value="<?php echo esc_specialchars($rawRecord['name'] ?? ''); ?>">
                <div class="form-text"><?php echo __('This name is protected and cannot be changed.'); ?></div>
              <?php } else { ?>
                <input type="text" class="form-control" id="name" name="name"
                       value="<?php echo esc_specialchars($rawRecord['name'] ?? ''); ?>" required>
                <div class="form-text"><?php echo __('Internal identifier (e.g., mainMenu, browseArchivalDescriptions). Must be unique.'); ?></div>
              <?php } ?>
            </div>

            <div class="mb-3">
              <label for="label" class="form-label">
                <?php echo __('Label'); ?>
                <span class="form-required" title="<?php echo __('This is a mandatory field.'); ?>">*</span>
              </label>
              <input type="text" class="form-control" id="label" name="label"
                     value="<?php echo esc_specialchars($rawRecord['label'] ?? ''); ?>" required>
              <div class="form-text"><?php echo __('Display label shown to users.'); ?></div>
            </div>

            <div class="mb-3">
              <label for="path" class="form-label"><?php echo __('Path'); ?></label>
              <input type="text" class="form-control" id="path" name="path"
                     value="<?php echo esc_specialchars($rawRecord['path'] ?? ''); ?>">
              <div class="form-text"><?php echo __('URL path for this menu item (e.g., informationobject/browse). Leave empty for parent/grouping menus.'); ?></div>
            </div>

            <div class="mb-3">
              <label for="parent_id" class="form-label"><?php echo __('Parent'); ?></label>
              <select class="form-select" id="parent_id" name="parent_id">
                <?php foreach ($rawParentChoices as $choiceId => $choiceLabel) { ?>
                  <option value="<?php echo $choiceId; ?>"
                    <?php echo ((int) ($rawRecord['parentId'] ?? 1)) === (int) $choiceId ? 'selected' : ''; ?>>
                    <?php echo esc_specialchars($choiceLabel); ?>
                  </option>
                <?php } ?>
              </select>
              <div class="form-text"><?php echo __('Parent menu under which this item appears.'); ?></div>
            </div>

            <div class="mb-3">
              <label for="description" class="form-label"><?php echo __('Description'); ?></label>
              <textarea class="form-control" id="description" name="description" rows="3"><?php echo esc_specialchars($rawRecord['description'] ?? ''); ?></textarea>
              <div class="form-text"><?php echo __('Optional description for this menu item.'); ?></div>
            </div>

          </div>
        </div>
      </div>
    </div>

    <ul class="actions mb-3 nav gap-2">
      <li><?php echo link_to(__('Cancel'), '@menu_list', ['class' => 'btn atom-btn-outline-light', 'role' => 'button']); ?></li>
      <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo $rawIsNew ? __('Create') : __('Save'); ?>"></li>
    </ul>

  </form>

<?php end_slot(); ?>
