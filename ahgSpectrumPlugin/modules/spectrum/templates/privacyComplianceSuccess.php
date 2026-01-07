<?php use_helper('Text'); ?>

<div class="row">
    <div class="col-md-12">
        <h1 class="h3 mb-4">
            <i class="fas fa-user-shield me-2"></i>
            <?php echo __('Privacy Compliance (POPIA/PAIA/GDPR)'); ?>
        </h1>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <h6 class="text-muted"><?php echo __('Compliance Score'); ?></h6>
                <div class="display-4 text-<?php echo ($complianceScore ?? 0) >= 80 ? 'success' : 'warning'; ?>">
                    <?php echo $complianceScore ?? 0; ?>%
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-primary h-100">
            <div class="card-body">
                <h4><?php echo $ropaCount ?? 0; ?></h4>
                <small><?php echo __('Processing Activities'); ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning h-100">
            <div class="card-body">
                <h4><?php echo $dsarStats['pending'] ?? 0; ?></h4>
                <small><?php echo __('Pending DSARs'); ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-danger h-100">
            <div class="card-body">
                <h4><?php echo $breachStats['open'] ?? 0; ?></h4>
                <small><?php echo __('Open Breaches'); ?></small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="btn-group mb-4">
            <a href="/admin/privacy/ropa" class="btn btn-outline-primary"><?php echo __('ROPA'); ?></a>
            <a href="/admin/privacy/dsar" class="btn btn-outline-warning"><?php echo __('DSARs'); ?></a>
            <a href="/admin/privacy/breaches" class="btn btn-outline-danger"><?php echo __('Breaches'); ?></a>
        </div>
    </div>
</div>
