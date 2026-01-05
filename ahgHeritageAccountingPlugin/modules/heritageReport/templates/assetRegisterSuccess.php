<?php use_helper('Date'); ?>
<?php slot('title') ?><?php echo __('Asset Register Report') ?><?php end_slot() ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><i class="fas fa-list me-2"></i><?php echo __('Asset Register Report') ?></h1>
            <button onclick="window.print()" class="btn btn-outline-secondary"><i class="fas fa-print me-1"></i><?php echo __('Print') ?></button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4 d-print-none">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label"><?php echo __('Standard') ?></label>
                    <select name="standard_id" class="form-select">
                        <option value=""><?php echo __('All Standards') ?></option>
                        <?php foreach ($standards as $s): ?>
                            <option value="<?php echo $s->id ?>" <?php echo ($filters['standard_id'] ?? '') == $s->id ? 'selected' : '' ?>><?php echo esc_entities($s->code) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo __('Class') ?></label>
                    <select name="class_id" class="form-select">
                        <option value=""><?php echo __('All Classes') ?></option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?php echo $c->id ?>" <?php echo ($filters['class_id'] ?? '') == $c->id ? 'selected' : '' ?>><?php echo esc_entities($c->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo __('Status') ?></label>
                    <select name="status" class="form-select">
                        <option value=""><?php echo __('All') ?></option>
                        <option value="recognised" <?php echo ($filters['recognition_status'] ?? '') == 'recognised' ? 'selected' : '' ?>><?php echo __('Recognised') ?></option>
                        <option value="not_recognised" <?php echo ($filters['recognition_status'] ?? '') == 'not_recognised' ? 'selected' : '' ?>><?php echo __('Not Recognised') ?></option>
                        <option value="pending" <?php echo ($filters['recognition_status'] ?? '') == 'pending' ? 'selected' : '' ?>><?php echo __('Pending') ?></option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary"><?php echo __('Filter') ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <span><?php echo __('Total: %1% assets', ['%1%' => count($assets)]) ?></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo __('Identifier') ?></th>
                            <th><?php echo __('Title') ?></th>
                            <th><?php echo __('Class') ?></th>
                            <th><?php echo __('Status') ?></th>
                            <th><?php echo __('Basis') ?></th>
                            <th class="text-end"><?php echo __('Acquisition Cost') ?></th>
                            <th class="text-end"><?php echo __('Carrying Amount') ?></th>
                            <th><?php echo __('Acquisition Date') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $totalCarrying = 0; $totalAcquisition = 0; ?>
                        <?php foreach ($assets as $asset): ?>
                            <?php $totalCarrying += $asset->current_carrying_amount; $totalAcquisition += $asset->acquisition_cost; ?>
                            <tr>
                                <td><?php echo esc_entities($asset->object_identifier ?: '-') ?></td>
                                <td><?php echo esc_entities($asset->object_title ?: '-') ?></td>
                                <td><?php echo esc_entities($asset->class_name ?: '-') ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $asset->recognition_status)) ?></td>
                                <td><?php echo ucfirst($asset->measurement_basis ?: '-') ?></td>
                                <td class="text-end"><?php echo number_format($asset->acquisition_cost, 2) ?></td>
                                <td class="text-end"><?php echo number_format($asset->current_carrying_amount, 2) ?></td>
                                <td><?php echo $asset->acquisition_date ?: '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-secondary fw-bold">
                        <tr>
                            <td colspan="5" class="text-end"><?php echo __('Totals:') ?></td>
                            <td class="text-end"><?php echo number_format($totalAcquisition, 2) ?></td>
                            <td class="text-end"><?php echo number_format($totalCarrying, 2) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-print-none">
        <a href="<?php echo url_for(['module' => 'heritageReport', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Reports') ?>
        </a>
    </div>
</div>
