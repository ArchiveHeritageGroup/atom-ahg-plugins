<?php use_helper('Date') ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-list me-2"></i>Form Templates</h1>
            <p class="text-muted">Manage all form templates</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'forms', 'action' => 'templateCreate']) ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> New Template
            </a>
            <a href="<?php echo url_for(['module' => 'forms', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <!-- Filter by Type -->
    <div class="row mb-3">
        <div class="col-md-4">
            <select class="form-select" onchange="window.location.href='<?php echo url_for(['module' => 'forms', 'action' => 'templates']) ?>?type=' + this.value">
                <option value="">All Types</option>
                <option value="information_object" <?php echo $currentType === 'information_object' ? 'selected' : '' ?>>Information Object</option>
                <option value="actor" <?php echo $currentType === 'actor' ? 'selected' : '' ?>>Authority Record</option>
                <option value="repository" <?php echo $currentType === 'repository' ? 'selected' : '' ?>>Repository</option>
                <option value="accession" <?php echo $currentType === 'accession' ? 'selected' : '' ?>>Accession</option>
            </select>
        </div>
    </div>

    <!-- Templates List -->
    <div class="card">
        <div class="card-body p-0">
            <?php if ($templates->isEmpty()): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>No form templates found.</p>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Fields</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($templates as $template): ?>
                            <?php
                            $fieldCount = \Illuminate\Database\Capsule\Manager::table('ahg_form_field')
                                ->where('template_id', $template->id)
                                ->count();
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($template->name) ?></strong>
                                    <?php if ($template->is_system): ?>
                                        <span class="badge bg-secondary">System</span>
                                    <?php endif ?>
                                    <?php if ($template->is_default): ?>
                                        <span class="badge bg-primary">Default</span>
                                    <?php endif ?>
                                    <?php if ($template->description): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($template->description) ?></small>
                                    <?php endif ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($template->form_type) ?></span>
                                </td>
                                <td><?php echo $fieldCount ?></td>
                                <td>
                                    <?php if ($template->is_active): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Inactive</span>
                                    <?php endif ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo url_for(['module' => 'forms', 'action' => 'builder', 'id' => $template->id]) ?>"
                                           class="btn btn-outline-primary" title="Edit Fields">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo url_for(['module' => 'forms', 'action' => 'templateClone', 'id' => $template->id]) ?>"
                                           class="btn btn-outline-info" title="Clone">
                                            <i class="fas fa-copy"></i>
                                        </a>
                                        <a href="<?php echo url_for(['module' => 'forms', 'action' => 'templateExport', 'id' => $template->id]) ?>"
                                           class="btn btn-outline-success" title="Export">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <?php if (!$template->is_system): ?>
                                            <a href="<?php echo url_for(['module' => 'forms', 'action' => 'templateDelete', 'id' => $template->id]) ?>"
                                               class="btn btn-outline-danger" title="Delete"
                                               onclick="return confirm('Delete this template?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            <?php endif ?>
        </div>
    </div>
</div>
