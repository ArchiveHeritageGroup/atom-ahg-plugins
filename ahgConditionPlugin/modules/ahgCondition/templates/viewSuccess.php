<?php use_helper('Date') ?>

<?php
// Condition rating colors
$ratingColors = [
    'excellent' => 'success',
    'good' => 'info', 
    'fair' => 'warning',
    'poor' => 'danger',
    'unacceptable' => 'dark'
];
$ratingColor = $ratingColors[$check->overall_condition] ?? 'secondary';
?>

<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage') ?>">Home</a></li>
            <?php if ($check->slug): ?>
            <li class="breadcrumb-item"><a href="/<?php echo $check->slug ?>"><?php echo esc_entities($check->object_title ?: 'Object') ?></a></li>
            <li class="breadcrumb-item"><a href="/<?php echo $check->slug ?>/condition">Condition Reports</a></li>
            <?php endif ?>
            <li class="breadcrumb-item active">Check #<?php echo $check->id ?></li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1>
                <i class="fas fa-clipboard-check text-success me-2"></i>
                Condition Check #<?php echo $check->id ?>
            </h1>
            <p class="text-muted mb-0">
                <?php echo esc_entities($check->object_title) ?>
                <?php if ($check->condition_check_reference): ?>
                 — Ref: <?php echo esc_entities($check->condition_check_reference) ?>
                <?php endif ?>
            </p>
        </div>
        <div class="text-end">
            <span class="badge bg-<?php echo $ratingColor ?> fs-5 mb-2">
                <?php echo ucfirst($check->overall_condition ?: 'Not rated') ?>
            </span>
            <br>
            <?php if ($check->slug): ?>
            <a href="/<?php echo $check->slug ?>/ahgSpectrumPlugin/edit?procedure=condition&procedure_id=<?php echo $check->id ?>" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-edit me-1"></i> Edit
            </a>
            <?php endif ?>
            <a href="/condition/check/<?php echo $check->id ?>/export" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-file-export me-1"></i> Export
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Basic Info Card -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Check Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Check Date</th>
                                    <td><?php echo $check->check_date ? date('d M Y', strtotime($check->check_date)) : '-' ?></td>
                                </tr>
                                <tr>
                                    <th>Checked By</th>
                                    <td><?php echo esc_entities($check->checked_by ?: $checkedByUser ?: '-') ?></td>
                                </tr>
                                <tr>
                                    <th>Reason</th>
                                    <td><?php echo esc_entities($check->check_reason ?: '-') ?></td>
                                </tr>
                                <tr>
                                    <th>Completeness</th>
                                    <td><?php echo ucfirst($check->completeness ?: '-') ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Overall Condition</th>
                                    <td><span class="badge bg-<?php echo $ratingColor ?>"><?php echo ucfirst($check->overall_condition ?: 'Not rated') ?></span></td>
                                </tr>
                                <tr>
                                    <th>Treatment Priority</th>
                                    <td>
                                        <?php 
                                        $priorityColors = ['urgent' => 'danger', 'high' => 'warning', 'medium' => 'info', 'low' => 'secondary'];
                                        $pColor = $priorityColors[$check->treatment_priority] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $pColor ?>"><?php echo ucfirst($check->treatment_priority ?: 'None') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Next Check</th>
                                    <td>
                                        <?php if ($check->next_check_date): ?>
                                            <?php 
                                            $nextDate = strtotime($check->next_check_date);
                                            $isOverdue = $nextDate < time();
                                            ?>
                                            <span class="<?php echo $isOverdue ? 'text-danger fw-bold' : '' ?>">
                                                <?php echo date('d M Y', $nextDate) ?>
                                                <?php if ($isOverdue): ?> <i class="fas fa-exclamation-triangle"></i><?php endif ?>
                                            </span>
                                        <?php else: ?>
                                            -
                                        <?php endif ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Material Type</th>
                                    <td><?php echo ucfirst($check->material_type ?: 'General') ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Template Assessment Data -->
            <?php if ($template && !empty($fieldsBySection)): ?>
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-clipboard-list me-2"></i>
                        <?php echo esc_entities($template->name) ?>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="accordion" id="assessmentSections">
                        <?php foreach ($fieldsBySection as $sectionId => $sectionData): 
                            $section = $sectionData['section'];
                            $fields = $sectionData['fields'];
                            $hasValues = false;
                            foreach ($fields as $f) {
                                if (!empty($f['value'])) { $hasValues = true; break; }
                            }
                        ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button <?php echo $hasValues ? '' : 'collapsed' ?>" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#section<?php echo $sectionId ?>">
                                    <?php echo esc_entities($section->name) ?>
                                    <?php if ($section->is_required): ?>
                                    <span class="badge bg-danger ms-2">Required</span>
                                    <?php endif ?>
                                    <?php if (!$hasValues): ?>
                                    <span class="badge bg-secondary ms-2">No data</span>
                                    <?php endif ?>
                                </button>
                            </h2>
                            <div id="section<?php echo $sectionId ?>" class="accordion-collapse collapse <?php echo $hasValues ? 'show' : '' ?>">
                                <div class="accordion-body">
                                    <?php if ($section->description): ?>
                                    <p class="text-muted small mb-3"><?php echo esc_entities($section->description) ?></p>
                                    <?php endif ?>
                                    
                                    <table class="table table-sm mb-0">
                                        <?php foreach ($fields as $fieldData): 
                                            $field = $fieldData['field'];
                                            $value = $fieldData['value'];
                                            $displayValue = '-';
                                            
                                            if (!empty($value)) {
                                                if (is_array($value) || $value instanceof sfOutputEscaperArrayDecorator) {
                                                    $value = $value instanceof sfOutputEscaperArrayDecorator ? $value->getRawValue() : $value;
                                                    $displayValue = implode(', ', $value);
                                                } elseif ($field->field_type === 'rating') {
                                                    $labels = $field->options['labels'] ?? [];
                                                    $idx = (int)$value - ($field->options['min'] ?? 1);
                                                    $displayValue = $labels[$idx] ?? $value;
                                                    
                                                    // Add visual rating
                                                    $ratingClass = ['danger', 'warning', 'info', 'primary', 'success'][$idx] ?? 'secondary';
                                                    $displayValue = '<span class="badge bg-' . $ratingClass . '">' . esc_entities($displayValue) . '</span>';
                                                } else {
                                                    $displayValue = esc_entities($value);
                                                }
                                            }
                                        ?>
                                        <tr>
                                            <th width="40%" class="<?php echo empty($value) ? 'text-muted' : '' ?>">
                                                <?php echo esc_entities($field->field_label) ?>
                                                <?php if ($field->is_required): ?><span class="text-danger">*</span><?php endif ?>
                                            </th>
                                            <td><?php echo $displayValue ?></td>
                                        </tr>
                                        <?php endforeach ?>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>
            <?php elseif (!$template): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No assessment template was used for this condition check.
            </div>
            <?php endif ?>

            <!-- Notes Section -->
            <?php if ($check->condition_description || $check->hazards_noted || $check->recommendations || $check->condition_note): ?>
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Notes & Recommendations</h5>
                </div>
                <div class="card-body">
                    <?php if ($check->condition_description): ?>
                    <div class="mb-3">
                        <strong>Condition Description:</strong>
                        <p class="mb-0"><?php echo nl2br(esc_entities($check->condition_description)) ?></p>
                    </div>
                    <?php endif ?>
                    
                    <?php if ($check->hazards_noted): ?>
                    <div class="mb-3">
                        <strong class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Hazards Noted:</strong>
                        <p class="mb-0"><?php echo nl2br(esc_entities($check->hazards_noted)) ?></p>
                    </div>
                    <?php endif ?>
                    
                    <?php if ($check->recommendations): ?>
                    <div class="mb-3">
                        <strong class="text-primary"><i class="fas fa-tools me-1"></i>Treatment Recommendations:</strong>
                        <p class="mb-0"><?php echo nl2br(esc_entities($check->recommendations)) ?></p>
                    </div>
                    <?php endif ?>
                    
                    <?php if ($check->condition_note): ?>
                    <div class="mb-0">
                        <strong>Additional Notes:</strong>
                        <p class="mb-0"><?php echo nl2br(esc_entities($check->condition_note)) ?></p>
                    </div>
                    <?php endif ?>
                </div>
            </div>
            <?php endif ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Photos Card -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-camera me-2"></i>Photos</h5>
                    <span class="badge bg-light text-dark"><?php echo count($photos ?? []) ?></span>
                </div>
                <div class="card-body">
                    <?php if (!empty($photos ?? [])): ?>
                    <div class="row g-2">
                        <?php foreach (($photos ?? []) as $photo): 
                            $annotations = json_decode($photo->annotation_data ?: '[]', true);
                            $annotationCount = count($annotations ?? []);
                        ?>
                        <div class="col-6">
                            <a href="/condition/photo/<?php echo $photo->id ?>/annotate" class="d-block position-relative">
                                <img src="/uploads/condition_photos/<?php echo $photo->thumbnail ?: $photo->filename ?>" 
                                     class="img-fluid rounded" alt="<?php echo esc_entities($photo->caption ?: 'Condition photo') ?>">
                                <?php if ($annotationCount > 0): ?>
                                <span class="position-absolute top-0 end-0 badge bg-primary m-1"><?php echo $annotationCount ?></span>
                                <?php endif ?>
                            </a>
                            <small class="text-muted d-block text-truncate"><?php echo esc_entities($photo->caption ?: $photo->original_name) ?></small>
                        </div>
                        <?php endforeach ?>
                    </div>
                    <a href="/condition/check/<?php echo $check->id ?>/photos" class="btn btn-outline-info btn-sm w-100 mt-3">
                        <i class="fas fa-images me-1"></i> View All Photos
                    </a>
                    <?php else: ?>
                    <p class="text-muted mb-0">No photos attached.</p>
                    <a href="/condition/check/<?php echo $check->id ?>/photos" class="btn btn-outline-info btn-sm w-100 mt-2">
                        <i class="fas fa-upload me-1"></i> Add Photos
                    </a>
                    <?php endif ?>
                </div>
            </div>

            <!-- Actions Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Actions</h5>
                </div>
                <div class="card-body d-grid gap-2">
                    <?php if ($check->slug): ?>
                    <a href="/<?php echo $check->slug ?>/ahgSpectrumPlugin/edit?procedure=condition&procedure_id=<?php echo $check->id ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i> Edit Condition Check
                    </a>
                    <?php endif ?>
                    <a href="/condition/check/<?php echo $check->id ?>/photos" class="btn btn-info">
                        <i class="fas fa-camera me-1"></i> Manage Photos
                    </a>
                    <a href="/condition/check/<?php echo $check->id ?>/export" class="btn btn-outline-secondary">
                        <i class="fas fa-file-pdf me-1"></i> Export Report
                    </a>
                    <hr>
                    <?php if ($check->slug): ?>
                    <a href="/<?php echo $check->slug ?>/condition" class="btn btn-outline-dark">
                        <i class="fas fa-arrow-left me-1"></i> Back to Condition History
                    </a>
                    <a href="/<?php echo $check->slug ?>" class="btn btn-outline-dark">
                        <i class="fas fa-archive me-1"></i> View Object
                    </a>
                    <?php endif ?>
                </div>
            </div>

            <!-- Metadata Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Record Info</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <th>Created</th>
                            <td><?php echo $check->created_at ? date('d M Y H:i', strtotime($check->created_at)) : '-' ?></td>
                        </tr>
                        <tr>
                            <th>Updated</th>
                            <td><?php echo $check->updated_at ? date('d M Y H:i', strtotime($check->updated_at)) : '-' ?></td>
                        </tr>
                        <tr>
                            <th>Template</th>
                            <td>
                                <?php if ($template): ?>
                                <a href="/condition/template/<?php echo $template->id ?>/view"><?php echo esc_entities($template->name) ?></a>
                                <?php else: ?>
                                -
                                <?php endif ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .btn, .breadcrumb, .card-header .badge { display: none !important; }
    .card { break-inside: avoid; }
    .accordion-collapse { display: block !important; }
    .accordion-button::after { display: none; }
}
</style>
