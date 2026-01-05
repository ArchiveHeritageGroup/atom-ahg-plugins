<?php use_helper('Date'); ?>
<?php slot('title') ?><?php echo __('Heritage Asset') ?> - <?php echo esc_entities($asset->object_identifier) ?><?php end_slot() ?>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1"><?php echo esc_entities($asset->object_identifier ?: 'Heritage Asset') ?></h1>
                <p class="text-muted mb-0"><?php echo esc_entities($asset->object_title ?? '') ?></p>
            </div>
            <div class="btn-group">
                <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'edit', 'id' => $asset->id]) ?>" class="btn btn-warning">
                    <i class="fas fa-edit me-1"></i><?php echo __('Edit') ?>
                </a>
                <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'browse']) ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i><?php echo __('Back') ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h6 class="text-white-50"><?php echo __('Carrying Amount') ?></h6>
                    <h3 class="mb-0"><?php echo number_format($asset->current_carrying_amount, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted"><?php echo __('Status') ?></h6>
                    <?php
                    $statusColors = ['recognised' => 'success', 'not_recognised' => 'secondary', 'pending' => 'warning', 'derecognised' => 'danger'];
                    $color = $statusColors[$asset->recognition_status] ?? 'secondary';
                    ?>
                    <h4><span class="badge bg-<?php echo $color ?>"><?php echo ucfirst(str_replace('_', ' ', $asset->recognition_status)) ?></span></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted"><?php echo __('Standard') ?></h6>
                    <h4 class="mb-0"><?php echo esc_entities($asset->standard_code ?: 'Not Set') ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted"><?php echo __('Asset Class') ?></h6>
                    <h5 class="mb-0"><?php echo esc_entities($asset->class_name ?: 'Unclassified') ?></h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs" id="assetTabs" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#details"><?php echo __('Details') ?></a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#valuations"><?php echo __('Valuations') ?> <span class="badge bg-secondary"><?php echo count($valuations) ?></span></a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#impairments"><?php echo __('Impairments') ?> <span class="badge bg-secondary"><?php echo count($impairments) ?></span></a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#movements"><?php echo __('Movements') ?> <span class="badge bg-secondary"><?php echo count($movements) ?></span></a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#journals"><?php echo __('Journal Entries') ?> <span class="badge bg-secondary"><?php echo count($journals) ?></span></a></li>
    </ul>

    <div class="tab-content border border-top-0 rounded-bottom p-4 bg-white">
        <!-- Details Tab -->
        <div class="tab-pane fade show active" id="details">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="border-bottom pb-2 mb-3"><?php echo __('Recognition & Measurement') ?></h5>
                    <dl class="row">
                        <dt class="col-sm-5"><?php echo __('Recognition Date') ?></dt>
                        <dd class="col-sm-7"><?php echo $asset->recognition_date ? format_date($asset->recognition_date, 'D') : '-' ?></dd>
                        <dt class="col-sm-5"><?php echo __('Measurement Basis') ?></dt>
                        <dd class="col-sm-7"><?php echo ucfirst($asset->measurement_basis ?: '-') ?></dd>
                        <dt class="col-sm-5"><?php echo __('Acquisition Method') ?></dt>
                        <dd class="col-sm-7"><?php echo ucfirst($asset->acquisition_method ?: '-') ?></dd>
                        <dt class="col-sm-5"><?php echo __('Acquisition Date') ?></dt>
                        <dd class="col-sm-7"><?php echo $asset->acquisition_date ? format_date($asset->acquisition_date, 'D') : '-' ?></dd>
                        <dt class="col-sm-5"><?php echo __('Acquisition Cost') ?></dt>
                        <dd class="col-sm-7"><?php echo number_format($asset->acquisition_cost, 2) ?></dd>
                        <dt class="col-sm-5"><?php echo __('Fair Value at Acquisition') ?></dt>
                        <dd class="col-sm-7"><?php echo $asset->fair_value_at_acquisition ? number_format($asset->fair_value_at_acquisition, 2) : '-' ?></dd>
                    </dl>

                    <h5 class="border-bottom pb-2 mb-3 mt-4"><?php echo __('Current Values') ?></h5>
                    <dl class="row">
                        <dt class="col-sm-5"><?php echo __('Initial Carrying Amount') ?></dt>
                        <dd class="col-sm-7"><?php echo number_format($asset->initial_carrying_amount, 2) ?></dd>
                        <dt class="col-sm-5"><?php echo __('Current Carrying Amount') ?></dt>
                        <dd class="col-sm-7 fw-bold text-primary"><?php echo number_format($asset->current_carrying_amount, 2) ?></dd>
                        <dt class="col-sm-5"><?php echo __('Accumulated Depreciation') ?></dt>
                        <dd class="col-sm-7"><?php echo number_format($asset->accumulated_depreciation, 2) ?></dd>
                        <dt class="col-sm-5"><?php echo __('Revaluation Surplus') ?></dt>
                        <dd class="col-sm-7"><?php echo number_format($asset->revaluation_surplus, 2) ?></dd>
                        <dt class="col-sm-5"><?php echo __('Impairment Loss') ?></dt>
                        <dd class="col-sm-7"><?php echo number_format($asset->impairment_loss, 2) ?></dd>
                    </dl>
                </div>

                <div class="col-md-6">
                    <h5 class="border-bottom pb-2 mb-3"><?php echo __('Heritage Information') ?></h5>
                    <dl class="row">
                        <dt class="col-sm-5"><?php echo __('Significance') ?></dt>
                        <dd class="col-sm-7"><?php echo ucfirst($asset->heritage_significance ?: '-') ?></dd>
                        <dt class="col-sm-5"><?php echo __('Location') ?></dt>
                        <dd class="col-sm-7"><?php echo esc_entities($asset->current_location ?: '-') ?></dd>
                        <dt class="col-sm-5"><?php echo __('Condition') ?></dt>
                        <dd class="col-sm-7"><?php echo ucfirst($asset->condition_rating ?: '-') ?></dd>
                        <dt class="col-sm-5"><?php echo __('Donor') ?></dt>
                        <dd class="col-sm-7"><?php echo esc_entities($asset->donor_name ?: '-') ?></dd>
                    </dl>

                    <h5 class="border-bottom pb-2 mb-3 mt-4"><?php echo __('Insurance') ?></h5>
                    <dl class="row">
                        <dt class="col-sm-5"><?php echo __('Insurance Required') ?></dt>
                        <dd class="col-sm-7"><?php echo $asset->insurance_required ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?></dd>
                        <dt class="col-sm-5"><?php echo __('Insurance Value') ?></dt>
                        <dd class="col-sm-7"><?php echo $asset->insurance_value ? number_format($asset->insurance_value, 2) : '-' ?></dd>
                        <dt class="col-sm-5"><?php echo __('Policy Number') ?></dt>
                        <dd class="col-sm-7"><?php echo esc_entities($asset->insurance_policy_number ?: '-') ?></dd>
                        <dt class="col-sm-5"><?php echo __('Provider') ?></dt>
                        <dd class="col-sm-7"><?php echo esc_entities($asset->insurance_provider ?: '-') ?></dd>
                        <dt class="col-sm-5"><?php echo __('Expiry Date') ?></dt>
                        <dd class="col-sm-7"><?php echo $asset->insurance_expiry_date ? format_date($asset->insurance_expiry_date, 'D') : '-' ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Valuations Tab -->
        <div class="tab-pane fade" id="valuations">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><?php echo __('Valuation History') ?></h5>
                <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'addValuation', 'id' => $asset->id]) ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i><?php echo __('Add Valuation') ?>
                </a>
            </div>
            <?php if (!empty($valuations)): ?>
                <table class="table table-striped">
                    <thead><tr><th><?php echo __('Date') ?></th><th><?php echo __('Method') ?></th><th class="text-end"><?php echo __('Previous') ?></th><th class="text-end"><?php echo __('New Value') ?></th><th class="text-end"><?php echo __('Change') ?></th><th><?php echo __('Valuer') ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($valuations as $v): ?>
                            <tr>
                                <td><?php echo format_date($v->valuation_date, 'D') ?></td>
                                <td><?php echo ucfirst($v->valuation_method ?: '-') ?></td>
                                <td class="text-end"><?php echo number_format($v->previous_value, 2) ?></td>
                                <td class="text-end fw-bold"><?php echo number_format($v->new_value, 2) ?></td>
                                <td class="text-end <?php echo $v->valuation_change >= 0 ? 'text-success' : 'text-danger' ?>"><?php echo ($v->valuation_change >= 0 ? '+' : '') . number_format($v->valuation_change, 2) ?></td>
                                <td><?php echo esc_entities($v->valuer_name ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted text-center py-4"><?php echo __('No valuation history recorded.') ?></p>
            <?php endif; ?>
        </div>

        <!-- Impairments Tab -->
        <div class="tab-pane fade" id="impairments">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><?php echo __('Impairment Assessments') ?></h5>
                <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'addImpairment', 'id' => $asset->id]) ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i><?php echo __('Add Assessment') ?>
                </a>
            </div>
            <?php if (!empty($impairments)): ?>
                <table class="table table-striped">
                    <thead><tr><th><?php echo __('Date') ?></th><th><?php echo __('Identified') ?></th><th class="text-end"><?php echo __('Before') ?></th><th class="text-end"><?php echo __('Loss') ?></th><th class="text-end"><?php echo __('After') ?></th><th><?php echo __('Assessor') ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($impairments as $imp): ?>
                            <tr>
                                <td><?php echo format_date($imp->assessment_date, 'D') ?></td>
                                <td><?php echo $imp->impairment_identified ? '<span class="badge bg-danger">Yes</span>' : '<span class="badge bg-success">No</span>' ?></td>
                                <td class="text-end"><?php echo number_format($imp->carrying_amount_before, 2) ?></td>
                                <td class="text-end text-danger"><?php echo $imp->impairment_loss ? number_format($imp->impairment_loss, 2) : '-' ?></td>
                                <td class="text-end"><?php echo $imp->carrying_amount_after ? number_format($imp->carrying_amount_after, 2) : '-' ?></td>
                                <td><?php echo esc_entities($imp->assessor_name ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted text-center py-4"><?php echo __('No impairment assessments recorded.') ?></p>
            <?php endif; ?>
        </div>

        <!-- Movements Tab -->
        <div class="tab-pane fade" id="movements">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><?php echo __('Movement Register') ?></h5>
                <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'addMovement', 'id' => $asset->id]) ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i><?php echo __('Add Movement') ?>
                </a>
            </div>
            <?php if (!empty($movements)): ?>
                <table class="table table-striped">
                    <thead><tr><th><?php echo __('Date') ?></th><th><?php echo __('Type') ?></th><th><?php echo __('From') ?></th><th><?php echo __('To') ?></th><th><?php echo __('Condition') ?></th><th><?php echo __('Authorized By') ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($movements as $m): ?>
                            <tr>
                                <td><?php echo format_date($m->movement_date, 'D') ?></td>
                                <td><span class="badge bg-info"><?php echo ucfirst(str_replace('_', ' ', $m->movement_type)) ?></span></td>
                                <td><?php echo esc_entities($m->from_location ?: '-') ?></td>
                                <td><?php echo esc_entities($m->to_location ?: '-') ?></td>
                                <td><?php echo ucfirst($m->condition_on_departure ?: '-') ?></td>
                                <td><?php echo esc_entities($m->authorized_by ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted text-center py-4"><?php echo __('No movements recorded.') ?></p>
            <?php endif; ?>
        </div>

        <!-- Journals Tab -->
        <div class="tab-pane fade" id="journals">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><?php echo __('Journal Entries') ?></h5>
                <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'addJournal', 'id' => $asset->id]) ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i><?php echo __('Add Journal') ?>
                </a>
            </div>
            <?php if (!empty($journals)): ?>
                <table class="table table-striped">
                    <thead><tr><th><?php echo __('Date') ?></th><th><?php echo __('Number') ?></th><th><?php echo __('Type') ?></th><th><?php echo __('Debit') ?></th><th><?php echo __('Credit') ?></th><th class="text-end"><?php echo __('Amount') ?></th><th><?php echo __('Posted') ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($journals as $j): ?>
                            <tr>
                                <td><?php echo format_date($j->journal_date, 'D') ?></td>
                                <td><?php echo esc_entities($j->journal_number ?: '-') ?></td>
                                <td><span class="badge bg-secondary"><?php echo ucfirst($j->journal_type) ?></span></td>
                                <td><?php echo esc_entities($j->debit_account) ?></td>
                                <td><?php echo esc_entities($j->credit_account) ?></td>
                                <td class="text-end fw-bold"><?php echo number_format($j->debit_amount, 2) ?></td>
                                <td><?php echo $j->posted ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-warning">No</span>' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted text-center py-4"><?php echo __('No journal entries recorded.') ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
