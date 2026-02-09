<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0" aria-describedby="heading-label">
      <?php echo $isNew ? __('Add new user') : __('Edit user'); ?>
    </h1>
    <?php if (!$isNew) { ?>
      <span class="small" id="heading-label">
        <?php echo esc_specialchars($userRecord['username']); ?>
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

  <form method="post" action="<?php echo $isNew ? url_for('@user_add_override') : url_for('@user_edit_override?slug=' . $userRecord['slug']); ?>" id="editForm">

    <?php echo $form->renderHiddenFields(); ?>

    <div class="accordion mb-3">
      <div class="accordion-item">
        <h2 class="accordion-header" id="basicInfo-heading">
          <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#basicInfo-collapse" aria-expanded="true" aria-controls="basicInfo-collapse">
            <?php echo __('Basic info'); ?>
          </button>
        </h2>
        <div id="basicInfo-collapse" class="accordion-collapse collapse show" aria-labelledby="basicInfo-heading">
          <div class="accordion-body">
            <div class="mb-3">
              <label for="username" class="form-label">
                <?php echo __('Username'); ?>
                <span class="form-required" title="<?php echo __('This is a mandatory field.'); ?>">*</span>
              </label>
              <input type="text" class="form-control" id="username" name="username"
                     value="<?php echo esc_specialchars($userRecord['username']); ?>" required>
            </div>

            <div class="mb-3">
              <label for="email" class="form-label">
                <?php echo __('Email'); ?>
                <span class="form-required" title="<?php echo __('This is a mandatory field.'); ?>">*</span>
              </label>
              <input type="email" class="form-control" id="email" name="email"
                     value="<?php echo esc_specialchars($userRecord['email']); ?>" required>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="password" class="form-label">
                  <?php echo __('Password'); ?>
                  <?php if ($isNew) { ?>
                    <span class="form-required" title="<?php echo __('This is a mandatory field.'); ?>">*</span>
                  <?php } ?>
                </label>
                <input type="password" class="form-control" id="password" name="password"
                       <?php echo $isNew ? 'required' : ''; ?>>
                <?php if (!$isNew) { ?>
                  <div class="form-text"><?php echo __('Leave blank to keep current password.'); ?></div>
                <?php } ?>
              </div>
              <div class="col-md-6 mb-3">
                <label for="confirmPassword" class="form-label"><?php echo __('Confirm password'); ?></label>
                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword">
              </div>
            </div>

            <div class="mb-3">
              <label for="active" class="form-label"><?php echo __('Active'); ?></label>
              <select class="form-select" id="active" name="active">
                <option value="1" <?php echo $userRecord['active'] ? 'selected' : ''; ?>><?php echo __('Active'); ?></option>
                <option value="0" <?php echo !$userRecord['active'] ? 'selected' : ''; ?>><?php echo __('Inactive'); ?></option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="accordion-item">
        <h2 class="accordion-header" id="accessControl-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#accessControl-collapse" aria-expanded="false" aria-controls="accessControl-collapse">
            <?php echo __('Access control'); ?>
          </button>
        </h2>
        <div id="accessControl-collapse" class="accordion-collapse collapse" aria-labelledby="accessControl-heading">
          <div class="accordion-body">
            <?php
              $rawGroups = $sf_data->getRaw('assignableGroups');
              $rawRecord = $sf_data->getRaw('userRecord');
              $currentGroupIds = array_map(function ($g) { return (int) $g->id; }, $rawRecord['groups']);
            ?>
            <?php foreach ($rawGroups as $group) { ?>
              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="groups[]"
                       value="<?php echo $group->id; ?>"
                       id="group_<?php echo $group->id; ?>"
                       <?php echo in_array((int) $group->id, $currentGroupIds) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="group_<?php echo $group->id; ?>">
                  <?php echo esc_specialchars($group->name ?? __('Group %1%', ['%1%' => $group->id])); ?>
                </label>
              </div>
            <?php } ?>
            <?php if (empty($rawGroups)) { ?>
              <p class="text-muted mb-0"><?php echo __('No assignable groups found.'); ?></p>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>

    <ul class="actions mb-3 nav gap-2">
      <?php if (!$isNew) { ?>
        <li><?php echo link_to(__('Cancel'), '@user_view_override?slug=' . $userRecord['slug'], ['class' => 'btn atom-btn-outline-light', 'role' => 'button']); ?></li>
        <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Save'); ?>"></li>
      <?php } else { ?>
        <li><?php echo link_to(__('Cancel'), '@user_list_override', ['class' => 'btn atom-btn-outline-light', 'role' => 'button']); ?></li>
        <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Create'); ?>"></li>
      <?php } ?>
    </ul>

  </form>

<?php end_slot(); ?>
