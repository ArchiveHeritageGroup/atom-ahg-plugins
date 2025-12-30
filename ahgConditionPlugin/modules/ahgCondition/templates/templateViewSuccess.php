<?php use_helper('Date') ?>

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage') ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="/condition/templates">Templates</a></li>
            <li class="breadcrumb-item active"><?php echo esc_entities($template->name) ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1>
                <?php if ($template->is_default): ?><i class="fas fa-star text-warning me-2"></i><?php endif ?>
                <?php echo esc_entities($template->name) ?>
            </h1>
            <p class="text-muted mb-0"><?php echo esc_entities($template->description) ?></p>
        </div>
        <div>
            <span class="badge bg-<?php echo $template->is_active ? 'success' : 'secondary' ?> me-2">
                <?php echo $template->is_active ? 'Active' : 'Inactive' ?>
            </span>
            <span class="badge bg-primary"><?php echo ucfirst(str_replace('_', ' ', $template->material_type)) ?></span>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Template Information</h5></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr><th width="40%">Code</th><td><code><?php echo esc_entities($template->code) ?></code></td></tr>
                        <tr><th>Material Type</th><td><?php echo ucfirst(str_replace('_', ' ', $template->material_type)) ?></td></tr>
                        <tr><th>Sections</th><td><?php echo count($template->sections) ?></td></tr>
                        <tr><th>Total Fields</th><td><?php $total = 0; foreach ($template->sections as $s) $total += count($s->fields); echo $total; ?></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <a href="/condition/template/<?php echo $template->id ?>/export" class="btn btn-outline-primary mb-2 w-100">
                        <i class="fas fa-download me-2"></i>Export as JSON
                    </a>
                    <a href="/condition/templates" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-arrow-left me-2"></i>Back to Templates
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0"><i class="fas fa-layer-group me-2"></i>Template Structure</h5></div>
        <div class="card-body p-0">
            <div class="accordion" id="templateStructure">
                <?php foreach ($template->sections as $index => $section): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#section<?php echo $section->id ?>">
                            <?php if ($section->is_required): ?><span class="badge bg-danger me-2">Required</span><?php endif ?>
                            <?php echo esc_entities($section->name) ?>
                            <span class="badge bg-secondary ms-2"><?php echo count($section->fields) ?> fields</span>
                        </button>
                    </h2>
                    <div id="section<?php echo $section->id ?>" class="accordion-collapse collapse <?php echo $index == 0 ? 'show' : '' ?>">
                        <div class="accordion-body">
                            <?php if ($section->description): ?>
                            <p class="text-muted mb-3"><?php echo esc_entities($section->description) ?></p>
                            <?php endif ?>
                            <table class="table table-sm table-striped">
                                <thead><tr><th>Field</th><th>Type</th><th>Required</th><th>Options</th></tr></thead>
                                <tbody>
                                    <?php foreach ($section->fields as $field): 
                                        $colors = ['text'=>'secondary','textarea'=>'secondary','select'=>'primary','multiselect'=>'info','radio'=>'warning','checkbox'=>'success','rating'=>'danger','date'=>'dark','number'=>'dark'];
                                    ?>
                                    <tr>
                                        <td><strong><?php echo esc_entities($field->field_label) ?></strong><br><small class="text-muted"><?php echo $field->field_name ?></small></td>
                                        <td><span class="badge bg-<?php echo $colors[$field->field_type] ?? 'secondary' ?>"><?php echo $field->field_type ?></span></td>
                                        <td><?php echo $field->is_required ? '<i class="fas fa-check text-success"></i>' : '-' ?></td>
                                        <td>
                                            <?php if ($field->options && is_array($field->options)): ?>
                                                <?php if (isset($field->options['min'])): ?>
                                                    <?php echo $field->options['min'] ?>-<?php echo $field->options['max'] ?>
                                                <?php else: ?>
                                                    <small><?php echo count($field->options) ?> options</small>
                                                <?php endif ?>
                                            <?php else: ?>-<?php endif ?>
                                        </td>
                                    </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endforeach ?>
            </div>
        </div>
    </div>
</div>
