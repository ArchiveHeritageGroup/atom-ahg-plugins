<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'index']); ?>">Duplicate Detection</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'browse']); ?>">Browse</a></li>
                    <li class="breadcrumb-item active">Compare</li>
                </ol>
            </nav>
            <h1><i class="fas fa-columns me-2"></i>Compare Records</h1>
        </div>
        <div class="col-auto">
            <?php if ('merged' !== $detection->status): ?>
                <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'merge', 'id' => $detection->id]); ?>" class="btn btn-success">
                    <i class="fas fa-compress-arrows-alt me-1"></i> Merge Records
                </a>
                <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'dismiss', 'id' => $detection->id]); ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i> Dismiss
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detection Info -->
    <div class="alert alert-info mb-4">
        <div class="row">
            <div class="col-md-3">
                <strong>Similarity Score:</strong>
                <?php
                $score = $detection->similarity_score * 100;
                $colorClass = $score >= 90 ? 'danger' : ($score >= 75 ? 'warning' : 'info');
                ?>
                <span class="badge bg-<?php echo $colorClass; ?> fs-6"><?php echo number_format($score, 1); ?>%</span>
            </div>
            <div class="col-md-3">
                <strong>Detection Method:</strong>
                <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $detection->detection_method))); ?>
            </div>
            <div class="col-md-3">
                <strong>Status:</strong>
                <?php
                $statusColors = ['pending' => 'warning', 'confirmed' => 'info', 'dismissed' => 'secondary', 'merged' => 'success'];
                ?>
                <span class="badge bg-<?php echo $statusColors[$detection->status] ?? 'secondary'; ?>">
                    <?php echo $detection->status; ?>
                </span>
            </div>
            <div class="col-md-3">
                <strong>Detected:</strong>
                <?php echo $detection->detected_at ? date('M j, Y H:i', strtotime($detection->detected_at)) : '-'; ?>
            </div>
        </div>
    </div>

    <!-- Side-by-side Comparison -->
    <div class="row">
        <!-- Record A -->
        <div class="col-md-6">
            <div class="card h-100 border-primary">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Record A</h5>
                    <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $recordA->slug]); ?>"
                       class="btn btn-sm btn-light" target="_blank">
                        <i class="fas fa-external-link-alt"></i> View
                    </a>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <?php foreach ($comparison as $field): ?>
                            <tr class="<?php echo $field['match'] ? '' : 'table-warning'; ?>">
                                <th style="width: 30%;"><?php echo $field['label']; ?></th>
                                <td>
                                    <?php if (!empty($field['value_a'])): ?>
                                        <?php echo nl2br(htmlspecialchars(mb_substr($field['value_a'], 0, 500))); ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>

        <!-- Record B -->
        <div class="col-md-6">
            <div class="card h-100 border-success">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Record B</h5>
                    <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $recordB->slug]); ?>"
                       class="btn btn-sm btn-light" target="_blank">
                        <i class="fas fa-external-link-alt"></i> View
                    </a>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <?php foreach ($comparison as $field): ?>
                            <tr class="<?php echo $field['match'] ? '' : 'table-warning'; ?>">
                                <th style="width: 30%;"><?php echo $field['label']; ?></th>
                                <td>
                                    <?php if (!empty($field['value_b'])): ?>
                                        <?php echo nl2br(htmlspecialchars(mb_substr($field['value_b'], 0, 500))); ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Field Comparison Legend -->
    <div class="mt-4">
        <div class="alert alert-secondary">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Legend:</strong>
            <span class="badge bg-warning text-dark">Highlighted rows</span> indicate differing values between records.
        </div>
    </div>

    <!-- Detection Details -->
    <?php if ($detection->detection_details): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-microscope me-2"></i>Detection Details</h5>
            </div>
            <div class="card-body">
                <pre class="mb-0"><?php echo htmlspecialchars(json_encode(json_decode($detection->detection_details), JSON_PRETTY_PRINT)); ?></pre>
            </div>
        </div>
    <?php endif; ?>
</div>
