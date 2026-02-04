<?php use_helper('Date') ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-wpforms me-2"></i>Browse Form Templates</h1>
            <p class="text-muted">View and search available form templates</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'forms', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Form Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <?php foreach ($formTypes as $value => $label): ?>
                            <option value="<?php echo $value ?>" <?php echo $currentType === $value ? 'selected' : '' ?>>
                                <?php echo htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control"
                           placeholder="Search by name or description..."
                           value="<?php echo htmlspecialchars($currentSearch ?? '') ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i> Search
                    </button>
                    <a href="<?php echo url_for(['module' => 'forms', 'action' => 'browse']) ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <?php echo count($templates) ?> Template<?php echo count($templates) !== 1 ? 's' : '' ?> Found
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (count($templates) === 0): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>No form templates match your criteria.</p>
                    <?php if ($currentType || $currentSearch): ?>
                        <a href="<?php echo url_for(['module' => 'forms', 'action' => 'browse']) ?>" class="btn btn-outline-primary">
                            Clear Filters
                        </a>
                    <?php endif ?>
                </div>
            <?php else: ?>
                <div class="row g-4 p-4">
                    <?php foreach ($templates as $template): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <?php echo htmlspecialchars($template->name) ?>
                                        <?php if ($template->is_default): ?>
                                            <span class="badge bg-primary ms-1">Default</span>
                                        <?php endif ?>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <?php if ($template->description): ?>
                                        <p class="text-muted small mb-3"><?php echo htmlspecialchars($template->description) ?></p>
                                    <?php endif ?>

                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <span class="badge bg-info">
                                            <i class="fas fa-tag me-1"></i>
                                            <?php echo htmlspecialchars($formTypes[$template->form_type] ?? $template->form_type) ?>
                                        </span>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-th-list me-1"></i>
                                            <?php echo $template->field_count ?> field<?php echo $template->field_count !== 1 ? 's' : '' ?>
                                        </span>
                                        <?php if ($template->is_active): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Active
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">
                                                <i class="fas fa-pause me-1"></i>Inactive
                                            </span>
                                        <?php endif ?>
                                    </div>
                                </div>
                                <div class="card-footer bg-white">
                                    <div class="btn-group btn-group-sm w-100">
                                        <a href="<?php echo url_for(['module' => 'forms', 'action' => 'preview', 'id' => $template->id]) ?>"
                                           class="btn btn-outline-primary" title="Preview">
                                            <i class="fas fa-eye me-1"></i> Preview
                                        </a>
                                        <a href="<?php echo url_for(['module' => 'forms', 'action' => 'builder', 'id' => $template->id]) ?>"
                                           class="btn btn-outline-secondary" title="Edit">
                                            <i class="fas fa-edit me-1"></i> Edit
                                        </a>
                                        <a href="<?php echo url_for(['module' => 'forms', 'action' => 'templateClone', 'id' => $template->id]) ?>"
                                           class="btn btn-outline-info" title="Clone">
                                            <i class="fas fa-copy me-1"></i> Clone
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>
