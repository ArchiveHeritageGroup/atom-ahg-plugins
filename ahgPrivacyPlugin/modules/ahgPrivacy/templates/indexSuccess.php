<?php use_helper('Text'); ?>

<div class="container py-4">
    <h1 class="h2 mb-4"><i class="fas fa-user-shield me-2"></i><?php echo __('Privacy Notice'); ?></h1>

    <div class="card mb-4">
        <div class="card-body">
            <h5><?php echo __('Your Privacy Rights'); ?></h5>
            <p><?php echo __('Under the Protection of Personal Information Act (POPIA), you have the following rights:'); ?></p>
            
            <ul class="list-group list-group-flush mb-4">
                <li class="list-group-item"><i class="fas fa-eye text-primary me-2"></i><strong><?php echo __('Right of Access'); ?></strong> - <?php echo __('Request access to your personal information'); ?></li>
                <li class="list-group-item"><i class="fas fa-edit text-primary me-2"></i><strong><?php echo __('Right to Rectification'); ?></strong> - <?php echo __('Correct inaccurate personal information'); ?></li>
                <li class="list-group-item"><i class="fas fa-trash text-primary me-2"></i><strong><?php echo __('Right to Erasure'); ?></strong> - <?php echo __('Request deletion of your personal information'); ?></li>
                <li class="list-group-item"><i class="fas fa-hand-paper text-primary me-2"></i><strong><?php echo __('Right to Object'); ?></strong> - <?php echo __('Object to processing of your personal information'); ?></li>
            </ul>

            <div class="d-grid gap-2 d-md-flex">
                <a href="<?php echo url_for(['module' => 'ahgPrivacy', 'action' => 'dsarRequest']); ?>" class="btn btn-primary">
                    <i class="fas fa-file-alt me-2"></i><?php echo __('Submit a Request'); ?>
                </a>
                <a href="<?php echo url_for(['module' => 'ahgPrivacy', 'action' => 'dsarStatus']); ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-search me-2"></i><?php echo __('Check Request Status'); ?>
                </a>
            </div>
        </div>
    </div>

    <?php if (!empty($officers)): ?>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i><?php echo __('Information Officer'); ?></h5>
        </div>
        <div class="card-body">
            <?php foreach ($officers as $officer): ?>
            <p class="mb-1"><strong><?php echo esc_entities($officer->name); ?></strong></p>
            <?php if ($officer->email): ?>
            <p class="mb-1"><i class="fas fa-envelope me-2"></i><a href="mailto:<?php echo $officer->email; ?>"><?php echo $officer->email; ?></a></p>
            <?php endif; ?>
            <?php if ($officer->phone): ?>
            <p class="mb-1"><i class="fas fa-phone me-2"></i><?php echo esc_entities($officer->phone); ?></p>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
