<?php decorate_with('layout_1col'); ?>

<?php
  $rawConfig             = $sf_data->getRaw('config');
  $rawChecklistTemplates = $sf_data->getRaw('checklistTemplates');
  $rawAppraisalTemplates = $sf_data->getRaw('appraisalTemplates');

  $cfg          = is_array($rawConfig) ? $rawConfig : (array) $rawConfig;
  $templatesArr = is_array($rawChecklistTemplates) ? $rawChecklistTemplates : [];
  $appraisalArr = is_array($rawAppraisalTemplates) ? $rawAppraisalTemplates : [];
?>

<?php slot('title'); ?>
  <h1><i class="fas fa-cog me-2"></i><?php echo __('Accession V2 configuration'); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="<?php echo url_for('@accession_intake_queue'); ?>"><?php echo __('Intake queue'); ?></a>
      </li>
      <li class="breadcrumb-item active"><?php echo __('Configuration'); ?></li>
    </ol>
  </nav>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <form method="post" action="<?php echo url_for('@accession_intake_config'); ?>">
    <div class="card mb-3">
      <div class="card-header">
        <i class="fas fa-hashtag me-1"></i><?php echo __('Numbering'); ?>
      </div>
      <div class="card-body">
        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label"><?php echo __('Numbering mask'); ?></label>
            <input type="text" name="config[numbering_mask]" class="form-control"
                   value="<?php echo htmlspecialchars($cfg['numbering_mask'] ?? '{YEAR}-{SEQ:5}'); ?>"
                   placeholder="{YEAR}-{SEQ:5}">
            <div class="form-text">
              <?php echo __('Tokens: {YEAR}, {SEQ:N} (N = zero-padded digits), {REPO}, {MONTH}'); ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header">
        <i class="fas fa-sliders-h me-1"></i><?php echo __('Intake workflow'); ?>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" id="auto_assign_enabled"
                     name="config[auto_assign_enabled]" value="1"
                     <?php echo !empty($cfg['auto_assign_enabled']) ? 'checked' : ''; ?>>
              <label class="form-check-label" for="auto_assign_enabled">
                <?php echo __('Auto-assign to creator'); ?>
              </label>
              <div class="form-text">
                <?php echo __('Automatically assign new accessions to the user who creates them.'); ?>
              </div>
            </div>

            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" id="require_donor_agreement"
                     name="config[require_donor_agreement]" value="1"
                     <?php echo !empty($cfg['require_donor_agreement']) ? 'checked' : ''; ?>>
              <label class="form-check-label" for="require_donor_agreement">
                <?php echo __('Require donor agreement'); ?>
              </label>
              <div class="form-text">
                <?php echo __('Donor agreement must be attached before an accession can be submitted.'); ?>
              </div>
            </div>

            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" id="require_appraisal"
                     name="config[require_appraisal]" value="1"
                     <?php echo !empty($cfg['require_appraisal']) ? 'checked' : ''; ?>>
              <label class="form-check-label" for="require_appraisal">
                <?php echo __('Require appraisal before acceptance'); ?>
              </label>
              <div class="form-text">
                <?php echo __('An appraisal must be completed before an accession can be accepted.'); ?>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label"><?php echo __('Default priority'); ?></label>
              <?php $defaultPriority = $cfg['default_priority'] ?? 'normal'; ?>
              <select name="config[default_priority]" class="form-select">
                <option value="low"<?php echo $defaultPriority === 'low' ? ' selected' : ''; ?>><?php echo __('Low'); ?></option>
                <option value="normal"<?php echo $defaultPriority === 'normal' ? ' selected' : ''; ?>><?php echo __('Normal'); ?></option>
                <option value="high"<?php echo $defaultPriority === 'high' ? ' selected' : ''; ?>><?php echo __('High'); ?></option>
                <option value="urgent"<?php echo $defaultPriority === 'urgent' ? ' selected' : ''; ?>><?php echo __('Urgent'); ?></option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label"><?php echo __('Default intake checklist template'); ?></label>
              <?php $selectedChecklist = $cfg['intake_checklist_template_id'] ?? ''; ?>
              <select name="config[intake_checklist_template_id]" class="form-select">
                <option value=""><?php echo __('None'); ?></option>
                <?php foreach ($templatesArr as $tpl): ?>
                  <option value="<?php echo htmlspecialchars($tpl->id); ?>"<?php echo ($selectedChecklist == $tpl->id) ? ' selected' : ''; ?>>
                    <?php echo htmlspecialchars($tpl->name); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-text">
                <?php echo __('Automatically apply this checklist template to new accessions.'); ?>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label"><?php echo __('Default appraisal template'); ?></label>
              <?php $selectedAppraisal = $cfg['appraisal_template_id'] ?? ''; ?>
              <select name="config[appraisal_template_id]" class="form-select">
                <option value=""><?php echo __('None'); ?></option>
                <?php foreach ($appraisalArr as $tpl): ?>
                  <option value="<?php echo htmlspecialchars($tpl->id); ?>"<?php echo ($selectedAppraisal == $tpl->id) ? ' selected' : ''; ?>>
                    <?php echo htmlspecialchars($tpl->name); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-text">
                <?php echo __('Default template used when creating appraisals.'); ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header">
        <i class="fas fa-box me-1"></i><?php echo __('Containers & rights'); ?>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" id="allow_container_barcodes"
                     name="config[allow_container_barcodes]" value="1"
                     <?php echo !empty($cfg['allow_container_barcodes']) ? 'checked' : ''; ?>>
              <label class="form-check-label" for="allow_container_barcodes">
                <?php echo __('Allow container barcodes'); ?>
              </label>
              <div class="form-text">
                <?php echo __('Enable barcode scanning for container management.'); ?>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" id="rights_inheritance_enabled"
                     name="config[rights_inheritance_enabled]" value="1"
                     <?php echo !empty($cfg['rights_inheritance_enabled']) ? 'checked' : ''; ?>>
              <label class="form-check-label" for="rights_inheritance_enabled">
                <?php echo __('Enable rights inheritance'); ?>
              </label>
              <div class="form-text">
                <?php echo __('Allow rights statements to be inherited from parent records.'); ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="mb-3">
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-save me-1"></i><?php echo __('Save configuration'); ?>
      </button>
      <a href="<?php echo url_for('@accession_intake_queue'); ?>" class="btn btn-outline-secondary ms-2">
        <?php echo __('Cancel'); ?>
      </a>
    </div>
  </form>
<?php end_slot(); ?>
