<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo $contact ? __('Edit Contact') : __('Add Contact'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Contacts'), 'url' => $backUrl ?? ''],
  ['label' => $contact ? __('Edit Contact') : __('Add Contact')],
]]); ?>

<div class="row justify-content-center">
  <div class="col-lg-8">

    <h1 class="h3 mb-4"><?php echo $contact ? __('Edit Contact') : __('Add Contact'); ?></h1>

    <?php if (isset($errors) && count($errors) > 0): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $err): ?>
            <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php $c = $contact; ?>

    <form method="post">

      <div class="card mb-4">
        <div class="card-header fw-semibold"><?php echo __('Contact Details'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="cf-first" class="form-label"><?php echo __('First Name'); ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="cf-first" name="first_name" value="<?php echo htmlspecialchars($c->first_name ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="col-md-6">
              <label for="cf-last" class="form-label"><?php echo __('Last Name'); ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="cf-last" name="last_name" value="<?php echo htmlspecialchars($c->last_name ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="col-md-6">
              <label for="cf-email" class="form-label"><?php echo __('Email'); ?></label>
              <input type="email" class="form-control" id="cf-email" name="email" value="<?php echo htmlspecialchars($c->email ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-3">
              <label for="cf-phone" class="form-label"><?php echo __('Phone'); ?></label>
              <input type="tel" class="form-control" id="cf-phone" name="phone" value="<?php echo htmlspecialchars($c->phone ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-3">
              <label for="cf-mobile" class="form-label"><?php echo __('Mobile'); ?></label>
              <input type="tel" class="form-control" id="cf-mobile" name="mobile" value="<?php echo htmlspecialchars($c->mobile ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-6">
              <label for="cf-title" class="form-label"><?php echo __('Job Title'); ?></label>
              <input type="text" class="form-control" id="cf-title" name="job_title" value="<?php echo htmlspecialchars($c->job_title ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('e.g., Archivist, Director, IT Manager'); ?>">
            </div>
            <div class="col-md-6">
              <label for="cf-dept" class="form-label"><?php echo __('Department'); ?></label>
              <input type="text" class="form-control" id="cf-dept" name="department" value="<?php echo htmlspecialchars($c->department ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header fw-semibold"><?php echo __('Roles'); ?></div>
        <div class="card-body">
          <p class="text-muted small mb-3"><?php echo __('Select all roles that apply to this contact.'); ?></p>
          <?php
            $allRoles = ['primary' => __('Primary Contact'), 'technical' => __('Technical'), 'billing' => __('Billing'), 'collections' => __('Collections'), 'director' => __('Director'), 'archivist' => __('Archivist'), 'it' => __('IT')];
            $currentRoles = [];
            if (!empty($c->roles)) {
              $rawRoles = sfOutputEscaper::unescape($c->roles);
              $currentRoles = is_string($rawRoles) ? json_decode($rawRoles, true) : (array) $rawRoles;
              if (!is_array($currentRoles)) { $currentRoles = []; }
            }
          ?>
          <div class="row">
            <?php foreach ($allRoles as $val => $label): ?>
            <div class="col-md-4">
              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="cf-role-<?php echo $val; ?>" name="roles[]" value="<?php echo $val; ?>"<?php echo in_array($val, $currentRoles) ? ' checked' : ''; ?>>
                <label class="form-check-label" for="cf-role-<?php echo $val; ?>"><?php echo $label; ?></label>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header fw-semibold"><?php echo __('Settings'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="cf-primary" name="is_primary" value="1"<?php echo !empty($c->is_primary) ? ' checked' : ''; ?>>
                <label class="form-check-label" for="cf-primary"><?php echo __('Is primary contact'); ?></label>
                <div class="form-text"><?php echo __('The main point of contact for this entity.'); ?></div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="cf-public" name="is_public" value="1"<?php echo (!$contact || !empty($c->is_public)) ? ' checked' : ''; ?>>
                <label class="form-check-label" for="cf-public"><?php echo __('Publicly visible'); ?></label>
                <div class="form-text"><?php echo __('If unchecked, this contact will only be visible to administrators.'); ?></div>
              </div>
            </div>
            <div class="col-12">
              <label for="cf-notes" class="form-label"><?php echo __('Notes'); ?></label>
              <textarea class="form-control" id="cf-notes" name="notes" rows="3"><?php echo htmlspecialchars($c->notes ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between">
        <?php if (!empty($backUrl)): ?>
          <a href="<?php echo $backUrl; ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
        <?php else: ?>
          <span></span>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> <?php echo $contact ? __('Save Changes') : __('Add Contact'); ?></button>
      </div>

    </form>

  </div>
</div>

<?php end_slot(); ?>
