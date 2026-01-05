<?php use_helper('Date'); ?>
<?php slot('title') ?><?php echo __('Movement Report') ?><?php end_slot() ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><i class="fas fa-exchange-alt me-2"></i><?php echo __('Movement Report') ?></h1>
            <button onclick="window.print()" class="btn btn-outline-secondary"><i class="fas fa-print me-1"></i><?php echo __('Print') ?></button>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="card mb-4 d-print-none">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label"><?php echo __('From') ?></label>
                    <input type="date" name="from" class="form-control" value="<?php echo $from ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo __('To') ?></label>
                    <input type="date" name="to" class="form-control" value="<?php echo $to ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary"><?php echo __('Filter') ?></button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-dark text-white"><?php echo __('Movements: %1% to %2%', ['%1%' => $from, '%2%' => $to]) ?></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo __('Date') ?></th>
                            <th><?php echo __('Identifier') ?></th>
                            <th><?php echo __('Title') ?></th>
                            <th><?php echo __('Type') ?></th>
                            <th><?php echo __('From') ?></th>
                            <th><?php echo __('To') ?></th>
                            <th><?php echo __('Authorized By') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movements as $m): ?>
                            <tr>
                                <td><?php echo $m->movement_date ?></td>
                                <td><?php echo esc_entities($m->identifier ?: '-') ?></td>
                                <td><?php echo esc_entities($m->title ?: '-') ?></td>
                                <td><span class="badge bg-info"><?php echo ucfirst(str_replace('_', ' ', $m->movement_type)) ?></span></td>
                                <td><?php echo esc_entities($m->from_location ?: '-') ?></td>
                                <td><?php echo esc_entities($m->to_location ?: '-') ?></td>
                                <td><?php echo esc_entities($m->authorized_by ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($movements)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4"><?php echo __('No movements in this period.') ?></td></tr>
                        <?php endif; ?>
                    </tbody>
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
