<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0" aria-describedby="heading-label">
      <?php echo $isNew ? __('Add new function') : __('Edit function'); ?>
    </h1>
    <?php if (!$isNew) { ?>
      <span class="small" id="heading-label">
        <?php echo esc_specialchars($func['authorizedFormOfName'] ?: __('Untitled')); ?>
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

  <form method="post" action="<?php echo $isNew ? url_for('@function_add_override') : url_for('@function_edit_override?slug=' . $func['slug']); ?>" id="editForm">

    <?php echo $form->renderHiddenFields(); ?>

    <div class="accordion mb-3">

      <div class="accordion-item">
        <h2 class="accordion-header" id="identity-heading">
          <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#identity-collapse" aria-expanded="true" aria-controls="identity-collapse">
            <?php echo __('Identity area'); ?>
          </button>
        </h2>
        <div id="identity-collapse" class="accordion-collapse collapse show" aria-labelledby="identity-heading">
          <div class="accordion-body">

            <?php $rawTypes = $sf_data->getRaw('functionTypes'); ?>
            <div class="mb-3">
              <label for="typeId" class="form-label"><?php echo __('Type'); ?></label>
              <select class="form-select" id="typeId" name="typeId">
                <option value=""><?php echo __('- Select -'); ?></option>
                <?php foreach ($rawTypes as $type) { ?>
                  <option value="<?php echo $type->id; ?>"
                          <?php echo ($type->id == $func['typeId']) ? 'selected' : ''; ?>>
                    <?php echo esc_specialchars($type->name ?? __('Type %1%', ['%1%' => $type->id])); ?>
                  </option>
                <?php } ?>
              </select>
            </div>

            <div class="mb-3">
              <label for="authorizedFormOfName" class="form-label">
                <?php echo __('Authorized form of name'); ?>
                <span class="form-required" title="<?php echo __('This is a mandatory field.'); ?>">*</span>
              </label>
              <input type="text" class="form-control" id="authorizedFormOfName" name="authorizedFormOfName"
                     value="<?php echo esc_specialchars($func['authorizedFormOfName']); ?>" required>
            </div>

            <div class="mb-3">
              <label for="classification" class="form-label"><?php echo __('Classification'); ?></label>
              <input type="text" class="form-control" id="classification" name="classification"
                     value="<?php echo esc_specialchars($func['classification']); ?>">
            </div>

            <div class="mb-3">
              <label for="dates" class="form-label"><?php echo __('Dates'); ?></label>
              <input type="text" class="form-control" id="dates" name="dates"
                     value="<?php echo esc_specialchars($func['dates']); ?>">
            </div>

            <div class="mb-3">
              <label for="description" class="form-label"><?php echo __('Description'); ?></label>
              <textarea class="form-control" id="description" name="description" rows="4"><?php echo esc_specialchars($func['description']); ?></textarea>
            </div>

          </div>
        </div>
      </div>

      <div class="accordion-item">
        <h2 class="accordion-header" id="context-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#context-collapse" aria-expanded="false" aria-controls="context-collapse">
            <?php echo __('Context area'); ?>
          </button>
        </h2>
        <div id="context-collapse" class="accordion-collapse collapse" aria-labelledby="context-heading">
          <div class="accordion-body">

            <div class="mb-3">
              <label for="history" class="form-label"><?php echo __('History'); ?></label>
              <textarea class="form-control" id="history" name="history" rows="4"><?php echo esc_specialchars($func['history']); ?></textarea>
            </div>

            <div class="mb-3">
              <label for="legislation" class="form-label"><?php echo __('Legislation'); ?></label>
              <textarea class="form-control" id="legislation" name="legislation" rows="4"><?php echo esc_specialchars($func['legislation']); ?></textarea>
            </div>

          </div>
        </div>
      </div>

      <div class="accordion-item">
        <h2 class="accordion-header" id="control-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#control-collapse" aria-expanded="false" aria-controls="control-collapse">
            <?php echo __('Control area'); ?>
          </button>
        </h2>
        <div id="control-collapse" class="accordion-collapse collapse" aria-labelledby="control-heading">
          <div class="accordion-body">

            <div class="mb-3">
              <label for="descriptionIdentifier" class="form-label"><?php echo __('Description identifier'); ?></label>
              <input type="text" class="form-control" id="descriptionIdentifier" name="descriptionIdentifier"
                     value="<?php echo esc_specialchars($func['descriptionIdentifier']); ?>">
            </div>

            <div class="mb-3">
              <label for="institutionIdentifier" class="form-label"><?php echo __('Institution identifier'); ?></label>
              <textarea class="form-control" id="institutionIdentifier" name="institutionIdentifier" rows="2"><?php echo esc_specialchars($func['institutionIdentifier']); ?></textarea>
            </div>

            <div class="mb-3">
              <label for="rules" class="form-label"><?php echo __('Rules and/or conventions used'); ?></label>
              <textarea class="form-control" id="rules" name="rules" rows="3"><?php echo esc_specialchars($func['rules']); ?></textarea>
            </div>

            <?php $rawStatuses = $sf_data->getRaw('descriptionStatuses'); ?>
            <div class="mb-3">
              <label for="descriptionStatusId" class="form-label"><?php echo __('Status'); ?></label>
              <select class="form-select" id="descriptionStatusId" name="descriptionStatusId">
                <option value=""><?php echo __('- Select -'); ?></option>
                <?php foreach ($rawStatuses as $status) { ?>
                  <option value="<?php echo $status->id; ?>"
                          <?php echo ($status->id == $func['descriptionStatusId']) ? 'selected' : ''; ?>>
                    <?php echo esc_specialchars($status->name ?? ''); ?>
                  </option>
                <?php } ?>
              </select>
            </div>

            <?php $rawDetails = $sf_data->getRaw('descriptionDetails'); ?>
            <div class="mb-3">
              <label for="descriptionDetailId" class="form-label"><?php echo __('Level of detail'); ?></label>
              <select class="form-select" id="descriptionDetailId" name="descriptionDetailId">
                <option value=""><?php echo __('- Select -'); ?></option>
                <?php foreach ($rawDetails as $detail) { ?>
                  <option value="<?php echo $detail->id; ?>"
                          <?php echo ($detail->id == $func['descriptionDetailId']) ? 'selected' : ''; ?>>
                    <?php echo esc_specialchars($detail->name ?? ''); ?>
                  </option>
                <?php } ?>
              </select>
            </div>

            <div class="mb-3">
              <label for="revisionHistory" class="form-label"><?php echo __('Dates of creation, revision and deletion'); ?></label>
              <textarea class="form-control" id="revisionHistory" name="revisionHistory" rows="3"><?php echo esc_specialchars($func['revisionHistory']); ?></textarea>
            </div>

            <div class="mb-3">
              <label for="sources" class="form-label"><?php echo __('Sources'); ?></label>
              <textarea class="form-control" id="sources" name="sources" rows="3"><?php echo esc_specialchars($func['sources']); ?></textarea>
            </div>

            <div class="mb-3">
              <label for="sourceStandard" class="form-label"><?php echo __('Source standard'); ?></label>
              <input type="text" class="form-control" id="sourceStandard" name="sourceStandard"
                     value="<?php echo esc_specialchars($func['sourceStandard']); ?>">
            </div>

          </div>
        </div>
      </div>

    </div>

    <ul class="actions mb-3 nav gap-2">
      <?php if (!$isNew) { ?>
        <li><?php echo link_to(__('Cancel'), '@function_view_override?slug=' . $func['slug'], ['class' => 'btn atom-btn-outline-light', 'role' => 'button']); ?></li>
        <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Save'); ?>"></li>
      <?php } else { ?>
        <li><?php echo link_to(__('Cancel'), '@function_browse_override', ['class' => 'btn atom-btn-outline-light', 'role' => 'button']); ?></li>
        <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Create'); ?>"></li>
      <?php } ?>
    </ul>

  </form>

<?php end_slot(); ?>
