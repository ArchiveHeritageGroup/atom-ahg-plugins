<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'index']); ?>">NMMZ</a></li>
                    <li class="breadcrumb-item active">Configuration</li>
                </ol>
            </nav>
            <h1><i class="fas fa-cog me-2"></i>NMMZ Configuration</h1>
            <p class="text-muted">Configure plugin settings for NMMZ Act compliance</p>
        </div>
    </div>

    <?php if ($sf_user->hasFlash('notice')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $sf_user->getFlash('notice'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="row">
            <div class="col-lg-8">
                <!-- Antiquity Settings -->
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Antiquity Settings</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Minimum Age for Antiquity (Years)</label>
                                <input type="number" name="antiquity_age_years" class="form-control"
                                       value="<?php echo htmlspecialchars($config['antiquity_age_years'] ?? '100'); ?>" min="1">
                                <small class="text-muted">Objects older than this are classified as antiquities under the Act</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Export Permit Settings -->
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Export Permit Settings</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Permit Application Fee (USD)</label>
                                <input type="number" name="export_permit_fee_usd" class="form-control"
                                       value="<?php echo htmlspecialchars($config['export_permit_fee_usd'] ?? ''); ?>" min="0" step="0.01">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Permit Validity Period (Days)</label>
                                <input type="number" name="export_permit_validity_days" class="form-control"
                                       value="<?php echo htmlspecialchars($config['export_permit_validity_days'] ?? '90'); ?>" min="1">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- NMMZ Contact -->
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">NMMZ Contact Information</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Contact Email</label>
                                <input type="email" name="nmmz_contact_email" class="form-control"
                                       value="<?php echo htmlspecialchars($config['nmmz_contact_email'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Phone</label>
                                <input type="tel" name="nmmz_contact_phone" class="form-control"
                                       value="<?php echo htmlspecialchars($config['nmmz_contact_phone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Director Name</label>
                                <input type="text" name="director_name" class="form-control"
                                       value="<?php echo htmlspecialchars($config['director_name'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-body d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Save Configuration
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5 class="mb-0">About</h5></div>
                    <div class="card-body">
                        <p class="small text-muted mb-2">
                            <strong>NMMZ Plugin</strong><br>
                            National Museums and Monuments of Zimbabwe Act [Chapter 25:11] compliance module.
                        </p>
                        <p class="small text-muted mb-0">
                            <strong>Version:</strong> 1.0.0<br>
                            <strong>Author:</strong> The Archive and Heritage Group
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
