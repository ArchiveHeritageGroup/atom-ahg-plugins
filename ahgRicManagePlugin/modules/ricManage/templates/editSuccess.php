<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0" aria-describedby="heading-label">
      <?php echo $isNew ? __('Add new archival description') : __('Edit archival description'); ?>
    </h1>
    <span class="small" id="heading-label">
      <?php echo __('Records in Context (RiC)'); ?>
      <?php if (!$isNew) { ?>
        - <?php echo esc_specialchars($io['title'] ?: __('Untitled')); ?>
      <?php } ?>
    </span>
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
  $rawIo = $sf_data->getRaw('io');
  $rawLevels = $sf_data->getRaw('levels');
  $rawPubStatuses = $sf_data->getRaw('publicationStatuses');
  $rawDisplayStandards = $sf_data->getRaw('displayStandards');
  $rawRicMeta = $sf_data->getRaw('ricMeta');
  $rawEntityTypes = $sf_data->getRaw('ricEntityTypes');
  $rawPropFields = $sf_data->getRaw('ricPropFields');
  ?>

  <form method="post" action="<?php echo $isNew ? url_for('@io_add_override') : url_for('@io_edit_override?slug=' . $rawIo['slug']); ?>" id="editForm">

    <?php echo $form->renderHiddenFields(); ?>
    <input type="hidden" name="parentId" value="<?php echo (int) ($rawIo['parentId'] ?? 0); ?>">

    <div class="accordion mb-3" id="ricAccordion">

      <!-- Identity area -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="ric-identity-heading">
          <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#ric-identity-collapse" aria-expanded="true" aria-controls="ric-identity-collapse">
            <?php echo __('Identity area'); ?>
          </button>
        </h2>
        <div id="ric-identity-collapse" class="accordion-collapse collapse show" aria-labelledby="ric-identity-heading">
          <div class="accordion-body">

            <div class="mb-3">
              <label for="identifier" class="form-label"><?php echo __('Identifier'); ?></label>
              <input type="text" class="form-control" id="identifier" name="identifier"
                     value="<?php echo esc_specialchars($rawIo['identifier'] ?? ''); ?>">
            </div>

            <div class="mb-3">
              <label for="title" class="form-label">
                <?php echo __('Title'); ?>
                <span class="form-required" title="<?php echo __('This is a mandatory field.'); ?>">*</span>
              </label>
              <input type="text" class="form-control" id="title" name="title"
                     value="<?php echo esc_specialchars($rawIo['title'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
              <label for="levelOfDescriptionId" class="form-label">
                <?php echo __('Level of description'); ?>
                <span class="form-required" title="<?php echo __('This is a mandatory field.'); ?>">*</span>
              </label>
              <select class="form-select" id="levelOfDescriptionId" name="levelOfDescriptionId" required>
                <option value=""><?php echo __('- Select -'); ?></option>
                <?php foreach ($rawLevels as $level) { ?>
                  <option value="<?php echo $level->id; ?>" <?php echo ($level->id == ($rawIo['levelOfDescriptionId'] ?? '')) ? 'selected' : ''; ?>>
                    <?php echo esc_specialchars($level->name ?? ''); ?>
                  </option>
                <?php } ?>
              </select>
            </div>

            <div class="mb-3">
              <label for="extentAndMedium" class="form-label"><?php echo __('Extent and medium'); ?></label>
              <textarea class="form-control" id="extentAndMedium" name="extentAndMedium" rows="2"><?php echo esc_specialchars($rawIo['extentAndMedium'] ?? ''); ?></textarea>
            </div>

            <div class="mb-3">
              <label for="repositoryName" class="form-label"><?php echo __('Repository'); ?></label>
              <input type="text" class="form-control repository-autocomplete" id="repositoryName" name="repositoryName"
                     value="<?php echo esc_specialchars($rawIo['repositoryName'] ?? ''); ?>" placeholder="<?php echo __('Type to search repositories...'); ?>">
              <input type="hidden" id="repositoryId" name="repositoryId" value="<?php echo (int) ($rawIo['repositoryId'] ?? 0); ?>">
            </div>

          </div>
        </div>
      </div>

      <!-- Records in Context (RiC) area -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="ric-rico-heading">
          <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#ric-rico-collapse" aria-expanded="true" aria-controls="ric-rico-collapse">
            <?php echo __('Records in Context (RiC)'); ?>
          </button>
        </h2>
        <div id="ric-rico-collapse" class="accordion-collapse collapse show" aria-labelledby="ric-rico-heading">
          <div class="accordion-body">

            <div class="mb-3">
              <label for="ricEntityType" class="form-label"><?php echo __('RiC-O entity type'); ?></label>
              <select class="form-select" id="ricEntityType" name="ricEntityType">
                <?php foreach ($rawEntityTypes as $val => $label) { ?>
                  <option value="<?php echo esc_specialchars($val); ?>" <?php echo ($val === ($rawRicMeta['entity_type'] ?? 'Record')) ? 'selected' : ''; ?>>
                    <?php echo esc_specialchars($label); ?>
                  </option>
                <?php } ?>
              </select>
              <div class="form-text text-muted small"><?php echo __('How this record is typed in the Records in Context ontology.'); ?></div>
            </div>

            <?php foreach ($rawPropFields as $key => $label) { ?>
              <div class="mb-3">
                <label for="ricProps_<?php echo esc_specialchars($key); ?>" class="form-label"><?php echo esc_specialchars(__($label)); ?></label>
                <textarea class="form-control" id="ricProps_<?php echo esc_specialchars($key); ?>" name="ricProps[<?php echo esc_specialchars($key); ?>]" rows="2"><?php echo esc_specialchars($rawRicMeta['properties'][$key] ?? ''); ?></textarea>
              </div>
            <?php } ?>

          </div>
        </div>
      </div>

      <!-- Content area -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="ric-content-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ric-content-collapse" aria-expanded="false" aria-controls="ric-content-collapse">
            <?php echo __('Content and access'); ?>
          </button>
        </h2>
        <div id="ric-content-collapse" class="accordion-collapse collapse" aria-labelledby="ric-content-heading">
          <div class="accordion-body">

            <div class="mb-3">
              <label for="scopeAndContent" class="form-label"><?php echo __('Scope and content'); ?></label>
              <textarea class="form-control" id="scopeAndContent" name="scopeAndContent" rows="4"><?php echo esc_specialchars($rawIo['scopeAndContent'] ?? ''); ?></textarea>
            </div>

            <div class="mb-3">
              <label for="accessConditions" class="form-label"><?php echo __('Conditions governing access'); ?></label>
              <textarea class="form-control" id="accessConditions" name="accessConditions" rows="3"><?php echo esc_specialchars($rawIo['accessConditions'] ?? ''); ?></textarea>
            </div>

          </div>
        </div>
      </div>

      <!-- Settings -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="ric-settings-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ric-settings-collapse" aria-expanded="false" aria-controls="ric-settings-collapse">
            <?php echo __('Settings'); ?>
          </button>
        </h2>
        <div id="ric-settings-collapse" class="accordion-collapse collapse" aria-labelledby="ric-settings-heading">
          <div class="accordion-body">

            <div class="mb-3">
              <label for="publicationStatusId" class="form-label"><?php echo __('Publication status'); ?></label>
              <select class="form-select" id="publicationStatusId" name="publicationStatusId">
                <?php foreach ($rawPubStatuses as $ps) { ?>
                  <option value="<?php echo $ps->id; ?>" <?php echo ($ps->id == ($rawIo['publicationStatusId'] ?? '')) ? 'selected' : ''; ?>>
                    <?php echo esc_specialchars($ps->name); ?>
                  </option>
                <?php } ?>
              </select>
            </div>

            <div class="mb-3">
              <label for="displayStandardId" class="form-label"><?php echo __('Display standard'); ?></label>
              <select class="form-select" id="displayStandardId" name="displayStandardId">
                <option value=""><?php echo __('- Use global default -'); ?></option>
                <?php foreach ($rawDisplayStandards as $ds) { ?>
                  <option value="<?php echo $ds->id; ?>" <?php echo ($ds->id == ($rawIo['displayStandardId'] ?? '')) ? 'selected' : ''; ?>>
                    <?php echo esc_specialchars($ds->name ?? ''); ?>
                  </option>
                <?php } ?>
              </select>
            </div>

          </div>
        </div>
      </div>

    </div>

    <ul class="actions mb-3 nav gap-2">
      <?php if (!$isNew) { ?>
        <li><?php echo link_to(__('Cancel'), '/' . $rawIo['slug'], ['class' => 'btn atom-btn-outline-light', 'role' => 'button']); ?></li>
      <?php } ?>
      <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Save'); ?>"></li>
    </ul>

  </form>

<script src="/plugins/ahgInformationObjectManagePlugin/web/js/standard-switch.js"></script>

<?php end_slot(); ?>
