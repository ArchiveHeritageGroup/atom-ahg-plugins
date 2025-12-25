<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="reportsMenuDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-chart-bar"></i>
        <span class="d-none d-lg-inline ms-1"><?php echo __('Reports'); ?></span>
    </a>
    <ul class="dropdown-menu dropdown-menu-end mega-menu" aria-labelledby="reportsMenuDropdown">
        <li class="mega-menu-content">
            <div class="row">
                <!-- Reports Column -->
                <div class="col-md-4">
                    <h6 class="dropdown-header"><i class="fas fa-file-alt me-2"></i><?php echo __('Reports'); ?></h6>
                    <a class="dropdown-item" href="<?php echo url_for(['module' => 'reports', 'action' => 'descriptions']); ?>">
                        <i class="fas fa-archive me-2"></i><?php echo __('Archival Descriptions'); ?>
                    </a>
                    <a class="dropdown-item" href="<?php echo url_for(['module' => 'reports', 'action' => 'authorities']); ?>">
                        <i class="fas fa-users me-2"></i><?php echo __('Authority Records'); ?>
                    </a>
                    <a class="dropdown-item" href="<?php echo url_for(['module' => 'reports', 'action' => 'repositories']); ?>">
                        <i class="fas fa-building me-2"></i><?php echo __('Repositories'); ?>
                    </a>
                    <a class="dropdown-item" href="<?php echo url_for(['module' => 'reports', 'action' => 'accessions']); ?>">
                        <i class="fas fa-inbox me-2"></i><?php echo __('Accessions'); ?>
                    </a>
                    <div class="dropdown-divider"></div>
                    <h6 class="dropdown-header"><i class="fas fa-clipboard-check me-2"></i><?php echo __('Audit'); ?></h6>
                    <a class="dropdown-item" href="<?php echo url_for(['module' => 'dashboard', 'action' => 'index']); ?>">
                        <i class="fas fa-chart-line me-2"></i><?php echo __('Data Quality'); ?>
                    </a>
                </div>

                <!-- Dashboards Column -->
                <div class="col-md-4">
                    <h6 class="dropdown-header"><i class="fas fa-tachometer-alt me-2"></i><?php echo __('Dashboards'); ?></h6>
                    <a class="dropdown-item" href="<?php echo url_for(['module' => 'reports', 'action' => 'index']); ?>">
                        <i class="fas fa-desktop me-2"></i><?php echo __('System Overview'); ?>
                    </a>
                    <a class="dropdown-item" href="<?php echo url_for(['module' => 'spectrum', 'action' => 'dashboard']); ?>">
                        <i class="fas fa-layer-group me-2"></i><?php echo __('Collections Management'); ?>
                    </a>
                    <a class="dropdown-item" href="<?php echo url_for(['module' => 'spectrum', 'action' => 'grapDashboard']); ?>">
                        <i class="fas fa-balance-scale me-2"></i><?php echo __('GRAP 103'); ?>
                    </a>
                    <a class="dropdown-item" href="<?php echo url_for(['module' => 'dashboard', 'action' => 'index']); ?>">
                        <i class="fas fa-chart-bar me-2"></i><?php echo __('Data Quality'); ?>
                    </a>
                </div>

                <!-- Export/Import Column -->
                <div class="col-md-4">
                    <h6 class="dropdown-header"><i class="fas fa-download me-2"></i><?php echo __('Export'); ?></h6>
                    <a class="dropdown-item" href="<?php echo url_for(['module' => 'export', 'action' => 'archival']); ?>">
                        <i class="fas fa-file-export me-2"></i><?php echo __('Full Export'); ?>
                    </a>
                    <a class="dropdown-item" href="<?php echo url_for(['module' => 'export', 'action' => 'csv']); ?>">
                        <i class="fas fa-file-csv me-2"></i><?php echo __('CSV (ISAD-G)'); ?>
                    </a>
                    <a class="dropdown-item" href="<?php echo url_for(['module' => 'export', 'action' => 'ead']); ?>">
                        <i class="fas fa-file-code me-2"></i><?php echo __('EAD'); ?>
                    </a>
                    <a class="dropdown-item" href="<?php echo url_for(['module' => 'cidoc', 'action' => 'export']); ?>">
                        <i class="fas fa-project-diagram me-2"></i><?php echo __('CIDOC-CRM'); ?>
                    </a>
                    <div class="dropdown-divider"></div>
                    <h6 class="dropdown-header"><i class="fas fa-upload me-2"></i><?php echo __('Import'); ?></h6>
                    <a class="dropdown-item" href="<?php echo url_for(['module' => 'object', 'action' => 'importSelect']); ?>">
                        <i class="fas fa-file-import me-2"></i><?php echo __('Import Data'); ?>
                    </a>
                </div>
            </div>
        </li>
    </ul>
</li>
