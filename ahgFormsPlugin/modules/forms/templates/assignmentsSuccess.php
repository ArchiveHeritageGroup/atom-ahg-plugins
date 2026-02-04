<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-link me-2"></i>Form Assignments</h1>
            <p class="text-muted">Assign form templates to repositories and description levels</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'forms', 'action' => 'assignmentCreate']) ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> New Assignment
            </a>
            <a href="<?php echo url_for(['module' => 'forms', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <?php if ($assignments->isEmpty()): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>No assignments found. Create one to specify which form templates are used where.</p>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Template</th>
                            <th>Type</th>
                            <th>Repository</th>
                            <th>Level</th>
                            <th>Priority</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $assignment): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($assignment->template_name) ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($assignment->form_type) ?></span>
                                </td>
                                <td>
                                    <?php if ($assignment->repository_name): ?>
                                        <?php echo htmlspecialchars($assignment->repository_name) ?>
                                    <?php else: ?>
                                        <span class="text-muted">All repositories</span>
                                    <?php endif ?>
                                </td>
                                <td>
                                    <?php if ($assignment->level_name): ?>
                                        <?php echo htmlspecialchars($assignment->level_name) ?>
                                    <?php else: ?>
                                        <span class="text-muted">All levels</span>
                                    <?php endif ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo $assignment->priority ?></span>
                                </td>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'forms', 'action' => 'assignmentDelete', 'id' => $assignment->id]) ?>"
                                       class="btn btn-outline-danger btn-sm"
                                       onclick="return confirm('Delete this assignment?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            <?php endif ?>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-body">
            <h5><i class="fas fa-info-circle me-2"></i>How Assignments Work</h5>
            <p class="mb-2">
                When editing a record, the system selects the best matching form template based on:
            </p>
            <ol class="mb-0">
                <li>Repository (if specified)</li>
                <li>Level of Description (if specified)</li>
                <li>Priority (higher number = higher priority)</li>
            </ol>
        </div>
    </div>
</div>
