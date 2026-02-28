<?php use_helper('Url'); ?>

<div class="container-fluid py-4">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo url_for('workflow/dashboard'); ?>">Workflow</a></li>
      <li class="breadcrumb-item"><a href="<?php echo url_for('workflow/admin'); ?>">Admin</a></li>
      <li class="breadcrumb-item"><a href="<?php echo url_for('workflow/admin/gates'); ?>">Publish Gates</a></li>
      <li class="breadcrumb-item active"><?php echo isset($rule) && $rule ? 'Edit Rule' : 'Add Rule'; ?></li>
    </ol>
  </nav>

  <h1 class="h3 mb-4">
    <i class="fas fa-<?php echo isset($rule) && $rule ? 'edit' : 'plus-circle'; ?> me-2"></i>
    <?php echo isset($rule) && $rule ? 'Edit Gate Rule' : 'Add Gate Rule'; ?>
  </h1>

  <form method="post" class="needs-validation" novalidate>
    <div class="card mb-4">
      <div class="card-header"><h5 class="card-title mb-0">Rule Definition</h5></div>
      <div class="card-body">

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label" for="name">Rule Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name" name="name" required
                   value="<?php echo htmlspecialchars($rule->name ?? ''); ?>"
                   placeholder="e.g., Title required for publication">
          </div>
          <div class="col-md-3">
            <label class="form-label" for="rule_type">Rule Type <span class="text-danger">*</span></label>
            <select class="form-select" id="rule_type" name="rule_type" required>
              <option value="">Select...</option>
              <?php foreach ($ruleTypes as $rt): ?>
                <option value="<?php echo htmlspecialchars($rt->code); ?>"
                  <?php echo (isset($rule) && $rule && $rule->rule_type === $rt->code) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($rt->label); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="severity">Severity <span class="text-danger">*</span></label>
            <select class="form-select" id="severity" name="severity" required>
              <option value="blocker" <?php echo (isset($rule) && $rule && $rule->severity === 'blocker') ? 'selected' : ''; ?>>Blocker</option>
              <option value="warning" <?php echo (isset($rule) && $rule && $rule->severity === 'warning') ? 'selected' : ''; ?>>Warning</option>
            </select>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label" for="error_message">Error Message <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="error_message" name="error_message" required
                   value="<?php echo htmlspecialchars($rule->error_message ?? ''); ?>"
                   placeholder="Message shown when check fails">
          </div>
          <div class="col-md-3">
            <label class="form-label" for="field_name">Field Name</label>
            <input type="text" class="form-control" id="field_name" name="field_name"
                   value="<?php echo htmlspecialchars($rule->field_name ?? ''); ?>"
                   placeholder="e.g., title, scope_and_content">
            <div class="form-text">For field_required / field_not_empty rules</div>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="sort_order">Sort Order</label>
            <input type="number" class="form-control" id="sort_order" name="sort_order"
                   value="<?php echo $rule->sort_order ?? 0; ?>">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label" for="rule_config">Rule Config (JSON)</label>
          <textarea class="form-control font-monospace" id="rule_config" name="rule_config" rows="3"
                    placeholder='{"sql": "SELECT COUNT(*) FROM ...", "fail_message": "Custom check failed"}'><?php echo htmlspecialchars($rule->rule_config ?? ''); ?></textarea>
          <div class="form-text">Optional JSON configuration for custom_sql rules</div>
        </div>

      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header"><h5 class="card-title mb-0">Scope (Optional Filters)</h5></div>
      <div class="card-body">
        <p class="text-muted">Leave blank to apply this rule to all records. Set filters to restrict which records this rule applies to.</p>

        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label" for="entity_type">Entity Type</label>
            <select class="form-select" id="entity_type" name="entity_type">
              <option value="information_object" <?php echo (isset($rule) && $rule && $rule->entity_type === 'information_object') ? 'selected' : ''; ?>>Information Object</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="level_of_description_id">Level of Description</label>
            <select class="form-select" id="level_of_description_id" name="level_of_description_id">
              <option value="">All levels</option>
              <?php foreach ($levels as $lv): ?>
                <option value="<?php echo $lv->id; ?>"
                  <?php echo (isset($rule) && $rule && $rule->level_of_description_id == $lv->id) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($lv->name); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="repository_id">Repository</label>
            <select class="form-select" id="repository_id" name="repository_id">
              <option value="">All repositories</option>
              <?php foreach ($repositories as $repo): ?>
                <option value="<?php echo $repo->id; ?>"
                  <?php echo (isset($rule) && $rule && $rule->repository_id == $repo->id) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($repo->name); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label" for="material_type">Material Type</label>
            <input type="text" class="form-control" id="material_type" name="material_type"
                   value="<?php echo htmlspecialchars($rule->material_type ?? ''); ?>"
                   placeholder="e.g., Photograph, Map">
          </div>
          <div class="col-md-4">
            <div class="form-check mt-4 pt-2">
              <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                     <?php echo (!isset($rule) || !$rule || $rule->is_active) ? 'checked' : ''; ?>>
              <label class="form-check-label" for="is_active">Rule is active</label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-between">
      <a href="<?php echo url_for('workflow/admin/gates'); ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Cancel
      </a>
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-save me-1"></i><?php echo isset($rule) && $rule ? 'Update Rule' : 'Create Rule'; ?>
      </button>
    </div>
  </form>
</div>
