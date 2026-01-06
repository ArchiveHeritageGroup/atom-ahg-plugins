<?php use_helper('Text'); ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm me-2">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="h2"><i class="fas fa-user-tie me-2"></i><?php echo __('Privacy Officers'); ?></span>
        </div>
        <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'officerAdd']); ?>" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i><?php echo __('Add Officer'); ?>
        </a>
    </div>

    <?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
    <?php endif; ?>

    <div class="row">
        <?php if ($officers->isEmpty()): ?>
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <?php echo __('No privacy officers configured. Add an Information Officer to comply with POPIA requirements.'); ?>
            </div>
        </div>
        <?php else: ?>
        <?php foreach ($officers as $officer): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <?php echo esc_entities($officer->name); ?>
                        <?php if (!$officer->is_active): ?>
                        <span class="badge bg-secondary"><?php echo __('Inactive'); ?></span>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($officer->title): ?>
                    <p class="text-muted mb-2"><?php echo esc_entities($officer->title); ?></p>
                    <?php endif; ?>
                    
                    <ul class="list-unstyled mb-0">
                        <li><i class="fas fa-envelope me-2 text-muted"></i><a href="mailto:<?php echo $officer->email; ?>"><?php echo $officer->email; ?></a></li>
                        <?php if ($officer->phone): ?>
                        <li><i class="fas fa-phone me-2 text-muted"></i><?php echo esc_entities($officer->phone); ?></li>
                        <?php endif; ?>
                        <li><i class="fas fa-globe me-2 text-muted"></i>
                            <?php
                            $jInfo = $jurisdictions[$officer->jurisdiction] ?? null;
                            echo $jInfo ? $jInfo['name'] : ucfirst($officer->jurisdiction);
                            ?>
                        </li>
                        <?php if ($officer->registration_number): ?>
                        <li><i class="fas fa-id-card me-2 text-muted"></i><?php echo __('Reg:'); ?> <?php echo esc_entities($officer->registration_number); ?></li>
                        <?php endif; ?>
                        <?php if ($officer->appointed_date): ?>
                        <li><i class="fas fa-calendar me-2 text-muted"></i><?php echo __('Appointed:'); ?> <?php echo $officer->appointed_date; ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'officerEdit', 'id' => $officer->id]); ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-edit me-1"></i><?php echo __('Edit'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Registration Info -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('Registration Requirements'); ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <h6><span class="fi fi-za me-2"></span><?php echo __('POPIA (South Africa)'); ?></h6>
                    <p class="small">Information Officer must be registered with the Information Regulator. Deputy Information Officers should also be designated.</p>
                </div>
                <div class="col-md-4">
                    <h6><span class="fi fi-ng me-2"></span><?php echo __('NDPA (Nigeria)'); ?></h6>
                    <p class="small">Data Protection Officer required for major data controllers. Registration with NDPC.</p>
                </div>
                <div class="col-md-4">
                    <h6><span class="fi fi-eu me-2"></span><?php echo __('GDPR (EU)'); ?></h6>
                    <p class="small">DPO required for public authorities and large-scale processing. Contact details must be published.</p>
                </div>
            </div>
        </div>
    </div>
</div>
