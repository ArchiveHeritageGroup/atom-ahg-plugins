<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'index']); ?>">IPSAS</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'assets']); ?>">Assets</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'assetView', 'id' => $asset->id]); ?>"><?php echo htmlspecialchars($asset->asset_number ?? 'Asset'); ?></a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
            <h1><i class="fas fa-edit me-2"></i>Edit Asset</h1>
        </div>
    </div>

    <form method="post" class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Asset Details</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($asset->title ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($asset->description ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($asset->location ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?php echo 'active' === $asset->status ? 'selected' : ''; ?>>Active</option>
                                <option value="on_loan" <?php echo 'on_loan' === $asset->status ? 'selected' : ''; ?>>On Loan</option>
                                <option value="in_storage" <?php echo 'in_storage' === $asset->status ? 'selected' : ''; ?>>In Storage</option>
                                <option value="under_conservation" <?php echo 'under_conservation' === $asset->status ? 'selected' : ''; ?>>Under Conservation</option>
                                <option value="disposed" <?php echo 'disposed' === $asset->status ? 'selected' : ''; ?>>Disposed</option>
                                <option value="lost" <?php echo 'lost' === $asset->status ? 'selected' : ''; ?>>Lost</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Condition & Risk</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Condition Rating</label>
                            <select name="condition_rating" class="form-select">
                                <option value="excellent" <?php echo 'excellent' === $asset->condition_rating ? 'selected' : ''; ?>>Excellent</option>
                                <option value="good" <?php echo 'good' === $asset->condition_rating ? 'selected' : ''; ?>>Good</option>
                                <option value="fair" <?php echo 'fair' === $asset->condition_rating ? 'selected' : ''; ?>>Fair</option>
                                <option value="poor" <?php echo 'poor' === $asset->condition_rating ? 'selected' : ''; ?>>Poor</option>
                                <option value="critical" <?php echo 'critical' === $asset->condition_rating ? 'selected' : ''; ?>>Critical</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Risk Level</label>
                            <select name="risk_level" class="form-select">
                                <option value="">Not Assessed</option>
                                <option value="low" <?php echo 'low' === ($asset->risk_level ?? '') ? 'selected' : ''; ?>>Low</option>
                                <option value="medium" <?php echo 'medium' === ($asset->risk_level ?? '') ? 'selected' : ''; ?>>Medium</option>
                                <option value="high" <?php echo 'high' === ($asset->risk_level ?? '') ? 'selected' : ''; ?>>High</option>
                                <option value="critical" <?php echo 'critical' === ($asset->risk_level ?? '') ? 'selected' : ''; ?>>Critical</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Risk Notes</label>
                            <textarea name="risk_notes" class="form-control" rows="2"><?php echo htmlspecialchars($asset->risk_notes ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>Save Changes</button>
                    <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'assetView', 'id' => $asset->id]); ?>" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>
