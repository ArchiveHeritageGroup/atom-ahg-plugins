<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'forms', 'action' => 'index']); ?>">Form Templates</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'forms', 'action' => 'builder', 'id' => $template->id]); ?>">Builder</a></li>
                    <li class="breadcrumb-item active">Preview</li>
                </ol>
            </nav>
            <h1><i class="fas fa-eye me-2"></i>Preview: <?php echo htmlspecialchars($template->name); ?></h1>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'forms', 'action' => 'builder', 'id' => $template->id]); ?>" class="btn btn-outline-primary">
                <i class="fas fa-edit me-1"></i> Back to Builder
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Form Preview</h5>
                </div>
                <div class="card-body">
                    <?php if ($fields->isEmpty()): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            This template has no fields yet. Add fields in the builder.
                        </div>
                    <?php else: ?>
                        <form>
                            <?php foreach ($fields as $field): ?>
                                <div class="mb-3">
                                    <?php if ($field->field_type === 'heading'): ?>
                                        <h5 class="border-bottom pb-2 mt-4"><?php echo htmlspecialchars($field->label); ?></h5>
                                    <?php elseif ($field->field_type === 'divider'): ?>
                                        <hr class="my-4">
                                    <?php else: ?>
                                        <label class="form-label">
                                            <?php echo htmlspecialchars($field->label); ?>
                                            <?php if ($field->is_required): ?>
                                                <span class="text-danger">*</span>
                                            <?php endif; ?>
                                        </label>

                                        <?php if ($field->field_type === 'text'): ?>
                                            <input type="text" class="form-control"
                                                   placeholder="<?php echo htmlspecialchars($field->placeholder ?? ''); ?>"
                                                   value="<?php echo htmlspecialchars($field->default_value ?? ''); ?>"
                                                   <?php echo $field->is_readonly ? 'readonly' : ''; ?>>

                                        <?php elseif ($field->field_type === 'textarea'): ?>
                                            <textarea class="form-control" rows="4"
                                                      placeholder="<?php echo htmlspecialchars($field->placeholder ?? ''); ?>"
                                                      <?php echo $field->is_readonly ? 'readonly' : ''; ?>><?php echo htmlspecialchars($field->default_value ?? ''); ?></textarea>

                                        <?php elseif ($field->field_type === 'select'): ?>
                                            <select class="form-select" <?php echo $field->is_readonly ? 'disabled' : ''; ?>>
                                                <option value="">-- Select --</option>
                                                <option>Option 1</option>
                                                <option>Option 2</option>
                                                <option>Option 3</option>
                                            </select>

                                        <?php elseif ($field->field_type === 'date'): ?>
                                            <input type="date" class="form-control"
                                                   value="<?php echo htmlspecialchars($field->default_value ?? ''); ?>"
                                                   <?php echo $field->is_readonly ? 'readonly' : ''; ?>>

                                        <?php elseif ($field->field_type === 'date_range'): ?>
                                            <div class="row">
                                                <div class="col-6">
                                                    <input type="date" class="form-control" placeholder="Start date">
                                                </div>
                                                <div class="col-6">
                                                    <input type="date" class="form-control" placeholder="End date">
                                                </div>
                                            </div>

                                        <?php elseif ($field->field_type === 'checkbox'): ?>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="field_<?php echo $field->id; ?>">
                                                <label class="form-check-label" for="field_<?php echo $field->id; ?>">
                                                    <?php echo htmlspecialchars($field->help_text ?? 'Check this option'); ?>
                                                </label>
                                            </div>

                                        <?php elseif ($field->field_type === 'file'): ?>
                                            <input type="file" class="form-control">

                                        <?php elseif ($field->field_type === 'autocomplete' || $field->field_type === 'taxonomy' || $field->field_type === 'actor'): ?>
                                            <input type="text" class="form-control"
                                                   placeholder="Start typing to search...">

                                        <?php else: ?>
                                            <input type="text" class="form-control"
                                                   placeholder="<?php echo htmlspecialchars($field->placeholder ?? ''); ?>">
                                        <?php endif; ?>

                                        <?php if ($field->help_text && $field->field_type !== 'checkbox'): ?>
                                            <div class="form-text"><?php echo htmlspecialchars($field->help_text); ?></div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>

                            <div class="mt-4">
                                <button type="button" class="btn btn-primary" disabled>
                                    <i class="fas fa-save me-1"></i> Save (Preview Only)
                                </button>
                                <button type="button" class="btn btn-outline-secondary" disabled>
                                    Cancel
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Template Info</h5>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($template->name); ?></p>
                    <p><strong>Type:</strong> <span class="badge bg-info"><?php echo htmlspecialchars($template->form_type); ?></span></p>
                    <p><strong>Fields:</strong> <?php echo $fields->count(); ?></p>
                    <p><strong>Status:</strong>
                        <?php if ($template->is_active): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-warning">Inactive</span>
                        <?php endif; ?>
                    </p>
                    <?php if ($template->description): ?>
                        <p><strong>Description:</strong><br><?php echo htmlspecialchars($template->description); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Field Summary</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <?php
                    $requiredCount = 0;
                    foreach ($fields as $f) {
                        if ($f->is_required) {
                            ++$requiredCount;
                        }
                    }
                    ?>
                    <li class="list-group-item d-flex justify-content-between">
                        Total Fields
                        <span class="badge bg-primary"><?php echo $fields->count(); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        Required Fields
                        <span class="badge bg-danger"><?php echo $requiredCount; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        Optional Fields
                        <span class="badge bg-secondary"><?php echo $fields->count() - $requiredCount; ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
