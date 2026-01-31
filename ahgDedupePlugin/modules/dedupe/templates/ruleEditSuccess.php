<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'index']); ?>">Duplicate Detection</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'rules']); ?>">Rules</a></li>
                    <li class="breadcrumb-item active">Edit Rule</li>
                </ol>
            </nav>
            <h1><i class="fas fa-edit me-2"></i>Edit Detection Rule</h1>
        </div>
    </div>

    <form method="post">
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Rule Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Rule Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required
                                   value="<?php echo htmlspecialchars($rule->name); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="rule_type" class="form-label">Rule Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="rule_type" name="rule_type" required>
                                <?php foreach ($ruleTypes as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo $rule->rule_type === $value ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="threshold" class="form-label">Threshold (0.0 - 1.0) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="threshold" name="threshold"
                                           min="0" max="1" step="0.01" value="<?php echo $rule->threshold; ?>" required>
                                    <div class="form-text">Minimum similarity score to flag as duplicate</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="priority" class="form-label">Priority</label>
                                    <input type="number" class="form-control" id="priority" name="priority"
                                           value="<?php echo $rule->priority; ?>" min="1" max="1000">
                                    <div class="form-text">Higher priority rules run first</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="repository_id" class="form-label">Apply to Repository</label>
                            <select class="form-select" id="repository_id" name="repository_id">
                                <option value="">All Repositories (Global)</option>
                                <?php foreach ($repositories as $repo): ?>
                                    <option value="<?php echo $repo->id; ?>" <?php echo $rule->repository_id == $repo->id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($repo->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="config_json" class="form-label">Configuration (JSON)</label>
                            <textarea class="form-control font-monospace" id="config_json" name="config_json"
                                      rows="4"><?php echo htmlspecialchars($rule->config_json ?? ''); ?></textarea>
                            <div class="form-text">Optional rule-specific configuration in JSON format</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled" value="1"
                                           <?php echo $rule->is_enabled ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_enabled">Enabled</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_blocking" name="is_blocking" value="1"
                                           <?php echo $rule->is_blocking ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_blocking">Blocking</label>
                                    <div class="form-text">Block record save if duplicate found</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Update Rule
                    </button>
                    <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'rules']); ?>" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                    <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'ruleDelete', 'id' => $rule->id]); ?>"
                       class="btn btn-outline-danger ms-auto"
                       onclick="return confirm('Delete this rule?');">
                        <i class="fas fa-trash me-1"></i> Delete
                    </a>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Rule Info</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Created:</strong><br>
                            <?php echo $rule->created_at ? date('M j, Y H:i', strtotime($rule->created_at)) : '-'; ?>
                        </p>
                        <p class="mb-0"><strong>Last Updated:</strong><br>
                            <?php echo $rule->updated_at ? date('M j, Y H:i', strtotime($rule->updated_at)) : '-'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
