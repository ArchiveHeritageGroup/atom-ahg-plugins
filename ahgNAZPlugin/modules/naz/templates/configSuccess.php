<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'naz', 'action' => 'index']); ?>">NAZ</a></li>
                    <li class="breadcrumb-item active">Configuration</li>
                </ol>
            </nav>
            <h1><i class="fas fa-cog me-2"></i>NAZ Configuration</h1>
        </div>
    </div>

    <?php if ($sf_user->hasFlash('notice')): ?>
        <div class="alert alert-success"><?php echo $sf_user->getFlash('notice'); ?></div>
    <?php endif; ?>

    <form method="post" class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Closure Period Settings</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Default Closure Period (years)</label>
                            <input type="number" name="closure_period_years" class="form-control" value="<?php echo htmlspecialchars($config['closure_period_years'] ?? '25'); ?>">
                            <small class="text-muted">Per Section 10 of the NAZ Act</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Research Permit Fees</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Foreign Researcher Fee (USD)</label>
                            <input type="number" name="foreign_permit_fee_usd" class="form-control" value="<?php echo htmlspecialchars($config['foreign_permit_fee_usd'] ?? '200'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Local Researcher Fee (USD)</label>
                            <input type="number" name="local_permit_fee_usd" class="form-control" value="<?php echo htmlspecialchars($config['local_permit_fee_usd'] ?? '0'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Permit Validity (months)</label>
                            <input type="number" name="permit_validity_months" class="form-control" value="<?php echo htmlspecialchars($config['permit_validity_months'] ?? '12'); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Contact Information</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Director Name</label>
                            <input type="text" name="director_name" class="form-control" value="<?php echo htmlspecialchars($config['director_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">NAZ Email</label>
                            <input type="email" name="naz_email" class="form-control" value="<?php echo htmlspecialchars($config['naz_email'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">NAZ Phone</label>
                            <input type="tel" name="naz_phone" class="form-control" value="<?php echo htmlspecialchars($config['naz_phone'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body d-grid">
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>Save</button>
                </div>
            </div>
        </div>
    </form>
</div>
