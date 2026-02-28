<?php use_helper('Url'); ?>

<div class="container-fluid py-4">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo url_for('workflow/dashboard'); ?>">Workflow</a></li>
      <li class="breadcrumb-item"><a href="<?php echo url_for('workflow/admin'); ?>">Admin</a></li>
      <li class="breadcrumb-item active">Publish Gate Rules</li>
    </ol>
  </nav>

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="fas fa-shield-alt me-2"></i>Publish Gate Rules</h1>
    <a href="<?php echo url_for('workflow/admin/gates/0/edit'); ?>" class="btn btn-primary">
      <i class="fas fa-plus me-1"></i>Add Rule
    </a>
  </div>

  <p class="text-muted mb-4">
    Publish gate rules define the conditions that must be met before a record can be published.
    <strong>Blocker</strong> rules prevent publishing; <strong>Warning</strong> rules are advisory only.
  </p>

  <div class="card">
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 40px"></th>
            <th>Rule Name</th>
            <th>Type</th>
            <th>Severity</th>
            <th>Field</th>
            <th>Scope</th>
            <th>Order</th>
            <th style="width: 120px">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rules)): ?>
            <tr><td colspan="8" class="text-center text-muted py-3">No publish gate rules configured</td></tr>
          <?php else: ?>
            <?php foreach ($rules as $rule): ?>
              <tr class="<?php echo $rule->is_active ? '' : 'table-secondary'; ?>">
                <td class="text-center">
                  <?php if ($rule->is_active): ?>
                    <i class="fas fa-check-circle text-success" title="Active"></i>
                  <?php else: ?>
                    <i class="fas fa-pause-circle text-muted" title="Inactive"></i>
                  <?php endif; ?>
                </td>
                <td>
                  <strong><?php echo htmlspecialchars($rule->name); ?></strong>
                  <br><small class="text-muted"><?php echo htmlspecialchars($rule->error_message); ?></small>
                </td>
                <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($rule->rule_type); ?></span></td>
                <td>
                  <?php if ($rule->severity === 'blocker'): ?>
                    <span class="badge bg-danger">Blocker</span>
                  <?php else: ?>
                    <span class="badge bg-warning text-dark">Warning</span>
                  <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($rule->field_name ?? '—'); ?></td>
                <td>
                  <?php
                    $scope = [];
                    if ($rule->level_of_description_id) $scope[] = 'Level: ' . $rule->level_of_description_id;
                    if ($rule->repository_id) $scope[] = 'Repo: ' . $rule->repository_id;
                    if ($rule->material_type) $scope[] = 'Material: ' . $rule->material_type;
                    echo $scope ? htmlspecialchars(implode(', ', $scope)) : '<span class="text-muted">All records</span>';
                  ?>
                </td>
                <td><?php echo $rule->sort_order; ?></td>
                <td>
                  <a href="<?php echo url_for("workflow/admin/gates/{$rule->id}/edit"); ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                    <i class="fas fa-edit"></i>
                  </a>
                  <a href="<?php echo url_for("workflow/admin/gates/{$rule->id}/delete"); ?>" class="btn btn-sm btn-outline-danger" title="Delete"
                     onclick="return confirm('Delete this rule?')">
                    <i class="fas fa-trash"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
