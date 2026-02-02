<?php decorate_with('layout_2col.php'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <section class="sidebar-section">
        <h4><?php echo __('AI Job Queue'); ?></h4>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="<?php echo url_for(['module' => 'ai', 'action' => 'batch']); ?>">
                    <i class="fas fa-tasks me-2"></i><?php echo __('Job Queue'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo url_for(['module' => 'ai', 'action' => 'review']); ?>">
                    <i class="fas fa-user-tag me-2"></i><?php echo __('NER Review'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo url_for(['module' => 'ai', 'action' => 'suggestReview']); ?>">
                    <i class="fas fa-robot me-2"></i><?php echo __('Description Review'); ?>
                </a>
            </li>
        </ul>
    </section>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>
        <i class="fas fa-tasks me-2"></i>
        <?php echo __('AI Job Queue'); ?>
    </h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBatchModal">
        <i class="fas fa-plus me-1"></i><?php echo __('New Batch'); ?>
    </button>
</div>
<?php end_slot(); ?>

<?php slot('content'); ?>

<style>
.batch-card {
    border-left: 4px solid #6c757d;
    transition: all 0.2s ease;
}
.batch-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.batch-card.status-pending { border-left-color: #ffc107; }
.batch-card.status-running { border-left-color: #0d6efd; }
.batch-card.status-completed { border-left-color: #198754; }
.batch-card.status-failed { border-left-color: #dc3545; }
.batch-card.status-paused { border-left-color: #fd7e14; }
.batch-card.status-cancelled { border-left-color: #6c757d; }
.progress-thin { height: 8px; }
.task-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}
</style>

<!-- Stats Cards -->
<div class="row mb-4">
    <?php
    $statusCounts = ['pending' => 0, 'running' => 0, 'completed' => 0, 'failed' => 0];
    foreach ($batches as $b) {
        if (isset($statusCounts[$b->status])) {
            $statusCounts[$b->status]++;
        }
    }
    ?>
    <div class="col-md-3">
        <div class="card text-bg-warning">
            <div class="card-body">
                <h3 class="mb-0"><?php echo $statusCounts['pending']; ?></h3>
                <small><?php echo __('Pending'); ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-primary">
            <div class="card-body">
                <h3 class="mb-0"><?php echo $statusCounts['running']; ?></h3>
                <small><?php echo __('Running'); ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-success">
            <div class="card-body">
                <h3 class="mb-0"><?php echo $statusCounts['completed']; ?></h3>
                <small><?php echo __('Completed'); ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-danger">
            <div class="card-body">
                <h3 class="mb-0"><?php echo $statusCounts['failed']; ?></h3>
                <small><?php echo __('Failed'); ?></small>
            </div>
        </div>
    </div>
</div>

<!-- Batch List -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-list me-2"></i><?php echo __('Batch Jobs'); ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($batches)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p><?php echo __('No batch jobs yet. Click "New Batch" to create one.'); ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($batches as $batch): ?>
                <?php
                $taskTypesArr = json_decode($batch->task_types, true) ?: [];
                $statusClass = 'status-' . $batch->status;
                ?>
                <div class="batch-card card mb-0 border-0 border-bottom <?php echo $statusClass; ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h5 class="mb-1">
                                    <a href="<?php echo url_for(['module' => 'ai', 'action' => 'batchView', 'id' => $batch->id]); ?>">
                                        <?php echo htmlspecialchars($batch->name); ?>
                                    </a>
                                    <span class="badge bg-<?php
                                        echo match($batch->status) {
                                            'pending' => 'warning',
                                            'running' => 'primary',
                                            'completed' => 'success',
                                            'failed' => 'danger',
                                            'paused' => 'secondary',
                                            'cancelled' => 'dark',
                                            default => 'secondary'
                                        };
                                    ?> ms-2"><?php echo ucfirst($batch->status); ?></span>
                                </h5>
                                <div class="mb-2">
                                    <?php foreach ($taskTypesArr as $tt): ?>
                                        <?php if (isset($taskTypes[$tt])): ?>
                                            <span class="badge bg-light text-dark task-badge me-1">
                                                <i class="fas <?php echo $taskTypes[$tt]['icon']; ?> me-1"></i>
                                                <?php echo $taskTypes[$tt]['label']; ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                                <div class="progress progress-thin mb-2">
                                    <div class="progress-bar bg-<?php echo $batch->failed_items > 0 ? 'danger' : 'success'; ?>"
                                         style="width: <?php echo $batch->progress_percent; ?>%"></div>
                                </div>
                                <small class="text-muted">
                                    <?php echo $batch->completed_items; ?>/<?php echo $batch->total_items; ?> completed
                                    <?php if ($batch->failed_items > 0): ?>
                                        <span class="text-danger">(<?php echo $batch->failed_items; ?> failed)</span>
                                    <?php endif; ?>
                                    &bull;
                                    <?php echo date('M j, Y g:i A', strtotime($batch->created_at)); ?>
                                </small>
                            </div>
                            <div class="btn-group">
                                <?php if ($batch->status === 'pending'): ?>
                                    <button class="btn btn-sm btn-success" onclick="batchAction(<?php echo $batch->id; ?>, 'start')">
                                        <i class="fas fa-play"></i>
                                    </button>
                                <?php elseif ($batch->status === 'running'): ?>
                                    <button class="btn btn-sm btn-warning" onclick="batchAction(<?php echo $batch->id; ?>, 'pause')">
                                        <i class="fas fa-pause"></i>
                                    </button>
                                <?php elseif ($batch->status === 'paused'): ?>
                                    <button class="btn btn-sm btn-success" onclick="batchAction(<?php echo $batch->id; ?>, 'resume')">
                                        <i class="fas fa-play"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if ($batch->failed_items > 0): ?>
                                    <button class="btn btn-sm btn-outline-warning" onclick="batchAction(<?php echo $batch->id; ?>, 'retry')" title="Retry failed">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                <?php endif; ?>
                                <a href="<?php echo url_for(['module' => 'ai', 'action' => 'batchView', 'id' => $batch->id]); ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Create Batch Modal -->
<div class="modal fade" id="createBatchModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i><?php echo __('Create Batch Job'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createBatchForm">
                <div class="modal-body">
                    <!-- Basic Info -->
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Batch Name'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g., NER for Collection X">
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Description'); ?></label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Optional description..."></textarea>
                    </div>

                    <!-- Task Types -->
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Task Types'); ?> <span class="text-danger">*</span></label>
                        <div class="row">
                            <?php foreach ($taskTypes as $key => $type): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="task_types[]" value="<?php echo $key; ?>" id="task-<?php echo $key; ?>">
                                        <label class="form-check-label" for="task-<?php echo $key; ?>">
                                            <i class="fas <?php echo $type['icon']; ?> me-1"></i>
                                            <?php echo $type['label']; ?>
                                            <small class="text-muted d-block"><?php echo $type['description']; ?></small>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Object Selection -->
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Select Objects'); ?></label>
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-repository" type="button"><?php echo __('By Repository'); ?></button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-ids" type="button"><?php echo __('By IDs'); ?></button>
                            </li>
                        </ul>
                        <div class="tab-content border border-top-0 p-3">
                            <div class="tab-pane fade show active" id="tab-repository">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label"><?php echo __('Repository'); ?></label>
                                        <select name="repository_id" class="form-select">
                                            <option value=""><?php echo __('-- Select Repository --'); ?></option>
                                            <?php foreach ($repositories as $repo): ?>
                                                <option value="<?php echo $repo->id; ?>"><?php echo htmlspecialchars($repo->name); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><?php echo __('Limit'); ?></label>
                                        <input type="number" name="limit" class="form-control" value="100" min="1" max="10000">
                                    </div>
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="empty_scope_only" id="empty_scope_only">
                                    <label class="form-check-label" for="empty_scope_only">
                                        <?php echo __('Only records with empty scope_and_content'); ?>
                                    </label>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="tab-ids">
                                <label class="form-label"><?php echo __('Object IDs (comma-separated)'); ?></label>
                                <textarea name="object_ids" class="form-control" rows="3" placeholder="12345, 12346, 12347"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Options -->
                    <div class="mb-3">
                        <a class="text-decoration-none" data-bs-toggle="collapse" href="#advancedOptions">
                            <i class="fas fa-cog me-1"></i><?php echo __('Advanced Options'); ?>
                        </a>
                        <div class="collapse mt-2" id="advancedOptions">
                            <div class="card card-body bg-light">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label"><?php echo __('Max Concurrent'); ?></label>
                                        <input type="number" name="max_concurrent" class="form-control" value="5" min="1" max="20">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label"><?php echo __('Delay (ms)'); ?></label>
                                        <input type="number" name="delay_between_ms" class="form-control" value="1000" min="0" max="60000">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label"><?php echo __('Max Retries'); ?></label>
                                        <input type="number" name="max_retries" class="form-control" value="3" min="0" max="10">
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-4">
                                        <label class="form-label"><?php echo __('Priority'); ?></label>
                                        <select name="priority" class="form-select">
                                            <option value="1">1 - Highest</option>
                                            <option value="3">3 - High</option>
                                            <option value="5" selected>5 - Normal</option>
                                            <option value="7">7 - Low</option>
                                            <option value="10">10 - Lowest</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="auto_start" id="auto_start" checked>
                        <label class="form-check-label" for="auto_start">
                            <?php echo __('Start immediately after creation'); ?>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i><?php echo __('Create Batch'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function batchAction(batchId, action) {
    fetch('/ai/batch/' + batchId + '/action', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: action })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Action failed');
        }
    });
}

document.getElementById('createBatchForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = {};

    // Handle checkboxes for task_types
    data.task_types = [];
    formData.getAll('task_types[]').forEach(v => data.task_types.push(v));

    // Other fields
    ['name', 'description', 'repository_id', 'limit', 'object_ids',
     'max_concurrent', 'delay_between_ms', 'max_retries', 'priority'].forEach(field => {
        if (formData.get(field)) {
            data[field] = formData.get(field);
        }
    });

    data.empty_scope_only = formData.get('empty_scope_only') ? true : false;
    data.auto_start = formData.get('auto_start') ? true : false;

    if (data.task_types.length === 0) {
        alert('Please select at least one task type');
        return;
    }

    fetch('/ai/batch/create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            window.location.href = '/ai/batch/' + result.batch_id;
        } else {
            alert(result.error || 'Failed to create batch');
        }
    });
});
</script>

<?php end_slot(); ?>
