<?php use_helper('Text'); ?>

<div class="container py-5">
    <h1 class="mb-4"><i class="fas fa-user-shield me-2"></i><?php echo __('Privacy & Data Protection'); ?></h1>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Your Rights -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-balance-scale me-2"></i><?php echo __('Your Privacy Rights'); ?></h5>
                </div>
                <div class="card-body">
                    <p><?php echo __('Under applicable data protection laws, you have the following rights regarding your personal information:'); ?></p>
                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item"><i class="fas fa-eye text-primary me-2"></i><strong><?php echo __('Right of Access'); ?></strong> - <?php echo __('Request access to your personal information'); ?></li>
                        <li class="list-group-item"><i class="fas fa-edit text-primary me-2"></i><strong><?php echo __('Right to Rectification'); ?></strong> - <?php echo __('Request correction of inaccurate information'); ?></li>
                        <li class="list-group-item"><i class="fas fa-trash text-primary me-2"></i><strong><?php echo __('Right to Erasure'); ?></strong> - <?php echo __('Request deletion of your personal information'); ?></li>
                        <li class="list-group-item"><i class="fas fa-hand-paper text-primary me-2"></i><strong><?php echo __('Right to Object'); ?></strong> - <?php echo __('Object to processing of your information'); ?></li>
                        <li class="list-group-item"><i class="fas fa-exchange-alt text-primary me-2"></i><strong><?php echo __('Right to Portability'); ?></strong> - <?php echo __('Receive your data in a portable format'); ?></li>
                    </ul>
                </div>
            </div>

            <!-- How We Use Your Data -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-database me-2"></i><?php echo __('How We Process Your Data'); ?></h5>
                </div>
                <div class="card-body">
                    <p><?php echo __('We collect and process personal information for the following purposes:'); ?></p>
                    <ul>
                        <li><?php echo __('Providing access to archival records and research services'); ?></li>
                        <li><?php echo __('Processing research requests and reading room bookings'); ?></li>
                        <li><?php echo __('Managing donor agreements and access restrictions'); ?></li>
                        <li><?php echo __('Compliance with legal and regulatory requirements'); ?></li>
                        <li><?php echo __('Improving our services and user experience'); ?></li>
                    </ul>
                    <p class="mb-0"><?php echo __('We process your data in accordance with applicable data protection laws including POPIA, NDPA, Kenya DPA, and GDPR where applicable.'); ?></p>
                </div>
            </div>

            <!-- Contact Information Officer -->
            <?php if (!empty($officers) && $officers->isNotEmpty()): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i><?php echo __('Privacy Officer'); ?></h5>
                </div>
                <div class="card-body">
                    <?php foreach ($officers as $officer): ?>
                    <div class="d-flex align-items-start mb-3">
                        <div class="flex-shrink-0">
                            <i class="fas fa-user-circle fa-3x text-secondary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1"><?php echo esc_entities($officer->name); ?></h6>
                            <?php if ($officer->title): ?>
                            <p class="text-muted mb-1"><?php echo esc_entities($officer->title); ?></p>
                            <?php endif; ?>
                            <p class="mb-0">
                                <a href="mailto:<?php echo $officer->email; ?>"><i class="fas fa-envelope me-1"></i><?php echo $officer->email; ?></a>
                                <?php if ($officer->phone): ?>
                                <br><i class="fas fa-phone me-1"></i><?php echo esc_entities($officer->phone); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar - Actions -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4 border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i><?php echo __('Take Action'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-3">
                        <a href="<?php echo url_for(['module' => 'ahgPrivacy', 'action' => 'dsarRequest']); ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-file-alt me-2"></i><?php echo __('Submit Data Request'); ?>
                        </a>
                        <a href="<?php echo url_for(['module' => 'ahgPrivacy', 'action' => 'complaint']); ?>" class="btn btn-warning btn-lg">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo __('Lodge Complaint'); ?>
                        </a>
                        <a href="<?php echo url_for(['module' => 'ahgPrivacy', 'action' => 'dsarStatus']); ?>" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-search me-2"></i><?php echo __('Check Request Status'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Types of Requests -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i><?php echo __('Request Types'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo __('Access Request'); ?>
                        <span class="badge bg-primary rounded-pill">DSAR</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo __('Correction Request'); ?>
                        <span class="badge bg-info rounded-pill">DSAR</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo __('Deletion Request'); ?>
                        <span class="badge bg-danger rounded-pill">DSAR</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo __('Privacy Complaint'); ?>
                        <span class="badge bg-warning text-dark rounded-pill">Complaint</span>
                    </li>
                </ul>
            </div>

            <!-- Supported Jurisdictions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-globe me-2"></i><?php echo __('Supported Jurisdictions'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><span class="fi fi-za me-2"></span>POPIA (South Africa)</li>
                    <li class="list-group-item"><span class="fi fi-ng me-2"></span>NDPA (Nigeria)</li>
                    <li class="list-group-item"><span class="fi fi-ke me-2"></span>Kenya DPA</li>
                    <li class="list-group-item"><span class="fi fi-eu me-2"></span>GDPR (European Union)</li>
                    <li class="list-group-item"><span class="fi fi-ca me-2"></span>PIPEDA (Canada)</li>
                    <li class="list-group-item"><span class="fi fi-us me-2"></span>CCPA (California)</li>
                </ul>
            </div>
        </div>
    </div>
</div>
