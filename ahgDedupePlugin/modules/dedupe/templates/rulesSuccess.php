<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'index']); ?>">Duplicate Detection</a></li>
                    <li class="breadcrumb-item active">Detection Rules</li>
                </ol>
            </nav>
            <h1><i class="fas fa-cog me-2"></i>Detection Rules</h1>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'ruleCreate']); ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Add Rule
            </a>
        </div>
    </div>

    <?php if ($sf_user->hasFlash('notice')): ?>
        <div class="alert alert-success"><?php echo $sf_user->getFlash('notice'); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body p-0">
            <?php if ($rules->isEmpty()): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-cog fa-3x mb-3"></i>
                    <p>No detection rules configured.</p>
                    <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'ruleCreate']); ?>" class="btn btn-primary">
                        Create Your First Rule
                    </a>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Priority</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Threshold</th>
                            <th>Repository</th>
                            <th>Status</th>
                            <th>Blocking</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rules as $rule): ?>
                            <tr class="<?php echo !$rule->is_enabled ? 'table-secondary' : ''; ?>">
                                <td>
                                    <span class="badge bg-secondary"><?php echo $rule->priority; ?></span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($rule->name); ?></strong>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $rule->rule_type))); ?></small>
                                </td>
                                <td>
                                    <?php echo number_format($rule->threshold * 100, 0); ?>%
                                </td>
                                <td>
                                    <?php if ($rule->repository_name): ?>
                                        <small><?php echo htmlspecialchars($rule->repository_name); ?></small>
                                    <?php else: ?>
                                        <span class="badge bg-info">Global</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($rule->is_enabled): ?>
                                        <span class="badge bg-success">Enabled</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Disabled</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($rule->is_blocking): ?>
                                        <span class="badge bg-danger">Yes</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'ruleEdit', 'id' => $rule->id]); ?>"
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'ruleDelete', 'id' => $rule->id]); ?>"
                                           class="btn btn-outline-danger" title="Delete"
                                           onclick="return confirm('Delete this rule?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>About Detection Rules</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Rule Types</h6>
                    <ul class="list-unstyled">
                        <li><strong>Title Similarity:</strong> Compares titles using Levenshtein distance</li>
                        <li><strong>Identifier Exact:</strong> Matches identical identifiers</li>
                        <li><strong>Identifier Fuzzy:</strong> Matches similar identifiers (Jaro-Winkler)</li>
                        <li><strong>Date + Creator:</strong> Matches records with same date range and creator</li>
                        <li><strong>Checksum:</strong> Matches identical files by hash</li>
                        <li><strong>Combined:</strong> Weighted combination of multiple factors</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>Settings</h6>
                    <ul class="list-unstyled">
                        <li><strong>Priority:</strong> Higher priority rules run first</li>
                        <li><strong>Threshold:</strong> Minimum similarity score to flag as duplicate</li>
                        <li><strong>Blocking:</strong> If enabled, prevents saving when duplicate found</li>
                        <li><strong>Repository:</strong> Apply rule only to specific repository, or globally</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
