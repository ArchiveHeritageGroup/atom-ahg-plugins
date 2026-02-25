<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Add Client Relationship'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('My Vendor Dashboard'), 'url' => url_for(['module' => 'registry', 'action' => 'myVendorDashboard'])],
  ['label' => __('Clients'), 'url' => url_for(['module' => 'registry', 'action' => 'myVendorClients'])],
  ['label' => __('Add Client')],
]]); ?>

<div class="row justify-content-center">
  <div class="col-lg-7">

    <h1 class="h3 mb-4"><?php echo __('Add Client Relationship'); ?></h1>

    <?php if (isset($errors) && count($errors) > 0): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $err): ?>
            <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorClientAdd']); ?>">

      <div class="card mb-4">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-12">
              <label for="vcf-inst" class="form-label"><?php echo __('Institution'); ?> <span class="text-danger">*</span></label>
              <select class="form-select" id="vcf-inst" name="institution_id" required>
                <option value=""><?php echo __('-- Select Institution --'); ?></option>
                <?php if (!empty($institutions)):
                  $selInst = (int) $sf_request->getParameter('institution_id', 0);
                  foreach ($institutions as $inst): ?>
                    <option value="<?php echo (int) $inst->id; ?>"<?php echo $selInst === (int) $inst->id ? ' selected' : ''; ?>><?php echo htmlspecialchars($inst->name, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; endif; ?>
              </select>
              <div class="form-text"><?php echo __('Only institutions registered in the directory are listed. If the institution is not listed, they need to register first.'); ?></div>
            </div>
            <div class="col-md-6">
              <label for="vcf-reltype" class="form-label"><?php echo __('Relationship Type'); ?></label>
              <select class="form-select" id="vcf-reltype" name="relationship_type">
                <?php
                  $relTypes = [
                    'developer' => __('Developer'), 'hosting' => __('Hosting'),
                    'maintenance' => __('Maintenance'), 'consulting' => __('Consulting'),
                    'digitization' => __('Digitization'), 'training' => __('Training'),
                    'integration' => __('Integration'),
                  ];
                  $selRel = $sf_request->getParameter('relationship_type', 'developer');
                  foreach ($relTypes as $val => $label): ?>
                    <option value="<?php echo $val; ?>"<?php echo $selRel === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label for="vcf-start" class="form-label"><?php echo __('Start Date'); ?></label>
              <input type="date" class="form-control" id="vcf-start" name="start_date" value="<?php echo htmlspecialchars($sf_request->getParameter('start_date', ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-12">
              <label for="vcf-svc" class="form-label"><?php echo __('Service Description'); ?></label>
              <textarea class="form-control" id="vcf-svc" name="service_description" rows="3" placeholder="<?php echo __('Describe the services provided to this institution...'); ?>"><?php echo htmlspecialchars($sf_request->getParameter('service_description', ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="vcf-public" name="is_public" value="1" checked>
                <label class="form-check-label" for="vcf-public"><?php echo __('Publicly visible'); ?></label>
                <div class="form-text"><?php echo __('If checked, this relationship will be visible on both the vendor and institution profiles.'); ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorClients']); ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-1"></i> <?php echo __('Add Client'); ?></button>
      </div>

    </form>

  </div>
</div>

<?php end_slot(); ?>
