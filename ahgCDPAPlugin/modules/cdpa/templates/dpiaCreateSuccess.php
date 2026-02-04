<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'index']); ?>">CDPA</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'dpia']); ?>">DPIA</a></li>
                    <li class="breadcrumb-item active">New DPIA</li>
                </ol>
            </nav>
            <h1><i class="fas fa-clipboard-check me-2"></i>New Data Protection Impact Assessment</h1>
        </div>
    </div>

    <form method="post" class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Assessment Details</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">DPIA Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Related Processing Activity</label>
                            <select name="processing_activity_id" class="form-select">
                                <option value="">None / New Activity</option>
                                <?php foreach ($activities as $a): ?>
                                    <option value="<?php echo $a->id; ?>"><?php echo htmlspecialchars($a->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Assessor Name <span class="text-danger">*</span></label>
                            <input type="text" name="assessor_name" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="4" required placeholder="Describe the processing activity and why a DPIA is needed..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Necessity & Proportionality</h5></div>
                <div class="card-body">
                    <label class="form-label">Assessment of necessity and proportionality <span class="text-danger">*</span></label>
                    <textarea name="necessity_assessment" class="form-control" rows="4" required placeholder="Why is this processing necessary? Is it proportionate to the purpose?"></textarea>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Risk Assessment</h5></div>
                <div class="card-body">
                    <label class="form-label">Overall Risk Level <span class="text-danger">*</span></label>
                    <select name="risk_level" class="form-select" required>
                        <option value="">Select...</option>
                        <option value="low">Low - Minimal impact on data subjects</option>
                        <option value="medium">Medium - Moderate impact, manageable with controls</option>
                        <option value="high">High - Significant impact, requires strong controls</option>
                        <option value="critical">Critical - Severe impact, may require POTRAZ consultation</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle me-1"></i> When is DPIA Required?</h6>
                <ul class="small mb-0">
                    <li>Large-scale processing</li>
                    <li>Systematic monitoring</li>
                    <li>Automated decisions with legal effects</li>
                    <li>Special categories of data</li>
                    <li>Cross-border transfers</li>
                    <li>Innovative technologies</li>
                </ul>
            </div>

            <div class="card">
                <div class="card-body d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>Create DPIA
                    </button>
                    <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'dpia']); ?>" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
