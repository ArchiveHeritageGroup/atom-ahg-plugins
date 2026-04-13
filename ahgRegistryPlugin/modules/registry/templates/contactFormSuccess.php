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
          <p class="text-muted small mb-3"><?php echo __('Select all roles that apply to this contact. Roles are managed in Admin → Dropdowns → contact_role.'); ?></p>
          <?php
            $allRoles = \Illuminate\Database\Capsule\Manager::table('registry_dropdown')
              ->where('dropdown_group', 'contact_role')
              ->where('is_active', 1)
              ->orderBy('sort_order')
              ->orderBy('label')
              ->get()->all();
            $currentRoles = [];
            if (!empty($c->roles)) {
              $rawRoles = sfOutputEscaper::unescape($c->roles);
              $currentRoles = is_string($rawRoles) ? json_decode($rawRoles, true) : (array) $rawRoles;
              if (!is_array($currentRoles)) { $currentRoles = []; }
            }
          ?>
          <select id="cf-roles" name="roles[]" multiple class="form-select" data-choices placeholder="<?php echo __('Select roles...'); ?>">
            <?php foreach ($allRoles as $r): ?>
              <option value="<?php echo htmlspecialchars($r->value, ENT_QUOTES, 'UTF-8'); ?>"<?php echo in_array($r->value, $currentRoles) ? ' selected' : ''; ?>><?php echo htmlspecialchars($r->label, ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
          </select>
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
              <textarea class="form-control" id="cf-notes" name="notes" rows="3" placeholder="<?php echo __('e.g., Best time to reach, preferred language, areas of expertise'); ?>"><?php echo htmlspecialchars($c->notes ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
              <div class="form-text"><?php echo __('Internal notes about this contact — availability, preferences, responsibilities. Visible only to administrators unless the contact itself is publicly visible.'); ?></div>
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between">
        <div>
          <?php if (!empty($backUrl)): ?>
            <a href="<?php echo $backUrl; ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
          <?php endif; ?>
          <?php if ($contact && !empty($c->id)): ?>
            <button type="button" class="btn btn-outline-danger ms-2" onclick="if(confirm('<?php echo __('Are you sure you want to delete this contact?'); ?>')) { document.getElementById('delete-contact-form').submit(); }">
              <i class="fas fa-trash me-1"></i> <?php echo __('Delete'); ?>
            </button>
          <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> <?php echo $contact ? __('Save Changes') : __('Add Contact'); ?></button>
      </div>

    </form>

    <?php if ($contact && !empty($c->id)): ?>
    <form id="delete-contact-form" method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionContactDelete', 'id' => (int) $c->id]); ?>" style="display: none;">
    </form>
    <?php endif; ?>

  </div>
</div>

<?php $n = sfConfig::get('csp_nonce', ''); $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css">
<script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js" <?php echo $na; ?>></script>
<script <?php echo $na; ?>>
document.addEventListener('DOMContentLoaded', function () {
  var el = document.getElementById('cf-roles');
  if (el && typeof Choices !== 'undefined') {
    new Choices(el, {
      removeItemButton: true,
      shouldSort: false,
      placeholder: true,
      placeholderValue: el.getAttribute('placeholder') || '',
      searchPlaceholderValue: 'Search roles...'
    });
  }
});
</script>

<?php end_slot(); ?>
