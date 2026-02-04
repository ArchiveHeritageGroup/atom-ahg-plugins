<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'index']); ?>">IPSAS</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'assets']); ?>">Assets</a></li>
                    <li class="breadcrumb-item active">New Asset</li>
                </ol>
            </nav>
            <h1><i class="fas fa-plus-circle me-2"></i>Register Heritage Asset</h1>
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
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select">
                                <option value="">Select...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat->id; ?>"><?php echo htmlspecialchars($cat->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Acquisition</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Acquisition Date</label>
                            <input type="date" name="acquisition_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Acquisition Method</label>
                            <select name="acquisition_method" class="form-select">
                                <option value="purchase">Purchase</option>
                                <option value="donation">Donation</option>
                                <option value="bequest">Bequest</option>
                                <option value="transfer">Transfer</option>
                                <option value="exchange">Exchange</option>
                                <option value="found">Found/Discovered</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Acquisition Source</label>
                            <input type="text" name="acquisition_source" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Acquisition Cost</label>
                            <input type="number" name="acquisition_cost" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Currency</label>
                            <select name="acquisition_currency" class="form-select">
                                <option value="USD">USD</option>
                                <option value="ZWL">ZWL</option>
                                <option value="EUR">EUR</option>
                                <option value="GBP">GBP</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Valuation</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Valuation Basis</label>
                            <select name="valuation_basis" class="form-select">
                                <option value="historical_cost">Historical Cost</option>
                                <option value="fair_value">Fair Value</option>
                                <option value="nominal">Nominal Value</option>
                                <option value="not_recognized">Not Recognized</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Current Value</label>
                            <input type="number" name="current_value" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Condition Rating</label>
                            <select name="condition_rating" class="form-select">
                                <option value="excellent">Excellent</option>
                                <option value="good">Good</option>
                                <option value="fair">Fair</option>
                                <option value="poor">Poor</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle"></i> IPSAS Compliance</h6>
                <p class="small mb-0">Assets are recognized under IPSAS 17/31. Choose appropriate valuation basis based on asset type and available information.</p>
            </div>
            <div class="card">
                <div class="card-body d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>Register Asset</button>
                    <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'assets']); ?>" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>
