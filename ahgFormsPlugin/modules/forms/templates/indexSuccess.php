<?php use_helper('Date') ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-wpforms me-2"></i>Form Templates</h1>
            <p class="text-muted">Manage configurable metadata entry forms</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'forms', 'action' => 'templateCreate']) ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> New Template
            </a>
            <a href="<?php echo url_for(['module' => 'forms', 'action' => 'templateImport']) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-upload me-1"></i> Import
            </a>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h4><?php echo array_sum((array) ($stats['templates_by_type'] ?? [0])) ?></h4>
                    <p class="mb-0">Total Templates</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h4><?php echo $stats['active_assignments'] ?? 0 ?></h4>
                    <p class="mb-0">Active Assignments</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h4><?php echo $stats['pending_drafts'] ?? 0 ?></h4>
                    <p class="mb-0">Pending Drafts</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <h4><?php echo array_sum((array) ($stats['submissions_30_days'] ?? [0])) ?></h4>
                    <p class="mb-0">Submissions (30 days)</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-list me-2"></i>Templates</h5>
                    <p class="card-text">Create and manage form templates with drag-drop field builder.</p>
                    <a href="<?php echo url_for(['module' => 'forms', 'action' => 'templates']) ?>" class="btn btn-outline-primary">Manage Templates</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-link me-2"></i>Assignments</h5>
                    <p class="card-text">Assign templates to repositories and description levels.</p>
                    <a href="<?php echo url_for(['module' => 'forms', 'action' => 'assignments']) ?>" class="btn btn-outline-primary">Manage Assignments</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-book me-2"></i>Library</h5>
                    <p class="card-text">Pre-built templates: ISAD-G, Dublin Core, Accession forms.</p>
                    <a href="<?php echo url_for(['module' => 'forms', 'action' => 'library']) ?>" class="btn btn-outline-primary">Browse Library</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Templates List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Form Templates</h5>
        </div>
        <div class="card-body p-0">
            <?php if ($templates->isEmpty()): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>No form templates found. Create one or import from the library.</p>
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
                                        <a href="<?php echo url_for(['module' => 'forms', 'action' => 'templateEdit', 'id' => $template->id]) ?>"
                                           class="btn btn-outline-secondary" title="Settings">
                                            <i class="fas fa-cog"></i>
                                        </a>
                                        <a href="<?php echo url_for(['module' => 'forms', 'action' => 'templateClone', 'id' => $template->id]) ?>"
                                           class="btn btn-outline-info" title="Clone">
                                            <i class="fas fa-copy"></i>
                                        </a>
                                        <a href="<?php echo url_for(['module' => 'forms', 'action' => 'templateExport', 'id' => $template->id]) ?>"
                                           class="btn btn-outline-success" title="Export JSON">
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
