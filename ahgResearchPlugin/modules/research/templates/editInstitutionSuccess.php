<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php slot('title') ?>
<h1><i class="fas fa-university text-primary me-2"></i><?php echo isset($institution) && $institution ? __('Edit Institution') : __('Add Institution'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>"><?php echo __('Research'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'institutions']); ?>"><?php echo __('Institutions'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo isset($institution) && $institution ? htmlspecialchars($institution->name) : __('Add'); ?></li>
  </ol>
</nav>

<div class="row">
  <div class="col-md-8">
    <div class="card">
      <div class="card-body">
        <form method="post">
          <input type="hidden" name="form_action" value="<?php echo isset($institution) && $institution ? 'update' : 'create'; ?>">

          <div class="mb-3">
            <label class="form-label"><?php echo __('Institution Name'); ?> *</label>
            <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars(isset($institution) ? ($institution->name ?? '') : ''); ?>" placeholder="<?php echo __('e.g., National Archives of South Africa'); ?>">
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label"><?php echo __('Code'); ?> *</label>
              <input type="text" name="code" class="form-control" required value="<?php echo htmlspecialchars(isset($institution) ? ($institution->code ?? '') : ''); ?>" placeholder="<?php echo __('e.g., NASA'); ?>">
              <div class="form-text"><?php echo __('Short unique identifier code for the institution.'); ?></div>
            </div>
            <div class="col-md-6">
              <label class="form-label"><?php echo __('Website URL'); ?></label>
              <input type="url" name="url" class="form-control" value="<?php echo htmlspecialchars(isset($institution) ? ($institution->url ?? '') : ''); ?>" placeholder="https://">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Description'); ?></label>
            <textarea name="description" class="form-control" rows="3" placeholder="<?php echo __('Brief description of the institution...'); ?>"><?php echo htmlspecialchars(isset($institution) ? ($institution->description ?? '') : ''); ?></textarea>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label"><?php echo __('Contact Name'); ?></label>
              <input type="text" name="contact_name" class="form-control" value="<?php echo htmlspecialchars(isset($institution) ? ($institution->contact_name ?? '') : ''); ?>" placeholder="<?php echo __('Contact person name'); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label"><?php echo __('Contact Email'); ?></label>
              <input type="email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars(isset($institution) ? ($institution->contact_email ?? '') : ''); ?>" placeholder="<?php echo __('contact@institution.org'); ?>">
            </div>
          </div>

          <div class="mb-4">
            <div class="form-check form-switch">
              <input type="checkbox" name="is_active" value="1" class="form-check-input" id="isActive" <?php echo (!isset($institution) || ($institution->is_active ?? 1)) ? 'checked' : ''; ?>>
              <label class="form-check-label" for="isActive"><?php echo __('Active'); ?></label>
              <div class="form-text"><?php echo __('Inactive institutions will not appear in share dropdowns.'); ?></div>
            </div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i><?php echo isset($institution) && $institution ? __('Save Changes') : __('Create Institution'); ?></button>
            <a href="<?php echo url_for(['module' => 'research', 'action' => 'institutions']); ?>" class="btn btn-secondary"><?php echo __('Cancel'); ?></a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php if (isset($institution) && $institution): ?>
  <div class="col-md-4">
    <div class="card">
      <div class="card-header"><h6 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('Details'); ?></h6></div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('ID'); ?></span>
          <span><?php echo $institution->id; ?></span>
        </li>
        <?php if (!empty($institution->created_at)): ?>
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('Created'); ?></span>
          <span><?php echo date('M j, Y', strtotime($institution->created_at)); ?></span>
        </li>
        <?php endif; ?>
        <?php if (!empty($institution->updated_at)): ?>
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted"><?php echo __('Updated'); ?></span>
          <span><?php echo date('M j, Y', strtotime($institution->updated_at)); ?></span>
        </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php end_slot() ?>
