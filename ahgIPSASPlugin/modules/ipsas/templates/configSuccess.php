<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'index']); ?>">IPSAS</a></li>
                    <li class="breadcrumb-item active">Configuration</li>
                </ol>
            </nav>
            <h1><i class="fas fa-cog me-2"></i>IPSAS Configuration</h1>
            <p class="text-muted">Configure heritage asset accounting settings</p>
        </div>
    </div>

    <?php if (isset($sf_user) && $sf_user->hasFlash('notice')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo $sf_user->getFlash('notice'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <form method="post" class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Organization</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Organization Name</label>
                            <input type="text" name="organization_name" class="form-control" value="<?php echo htmlspecialchars($config['organization_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Accounting Standard</label>
                            <select name="accounting_standard" class="form-select">
                                <option value="ipsas" <?php echo 'ipsas' === ($config['accounting_standard'] ?? '') ? 'selected' : ''; ?>>IPSAS</option>
                                <option value="grap" <?php echo 'grap' === ($config['accounting_standard'] ?? '') ? 'selected' : ''; ?>>SA GRAP</option>
                                <option value="ifrs" <?php echo 'ifrs' === ($config['accounting_standard'] ?? '') ? 'selected' : ''; ?>>IFRS</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Default Currency</label>
                            <select name="default_currency" class="form-select">
                                <option value="USD" <?php echo 'USD' === ($config['default_currency'] ?? 'USD') ? 'selected' : ''; ?>>USD</option>
                                <option value="ZWL" <?php echo 'ZWL' === ($config['default_currency'] ?? '') ? 'selected' : ''; ?>>ZWL</option>
                                <option value="EUR" <?php echo 'EUR' === ($config['default_currency'] ?? '') ? 'selected' : ''; ?>>EUR</option>
                                <option value="GBP" <?php echo 'GBP' === ($config['default_currency'] ?? '') ? 'selected' : ''; ?>>GBP</option>
                                <option value="ZAR" <?php echo 'ZAR' === ($config['default_currency'] ?? '') ? 'selected' : ''; ?>>ZAR</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Financial Year</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Financial Year Start</label>
                            <select name="financial_year_start" class="form-select">
                                <?php
                                $months = ['01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August', '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'];
                                foreach ($months as $num => $name):
                                ?>
                                <option value="<?php echo $num; ?>" <?php echo $num === ($config['financial_year_start'] ?? '01') ? 'selected' : ''; ?>><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Valuation Settings</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Depreciation Policy</label>
                            <select name="depreciation_policy" class="form-select">
                                <option value="none" <?php echo 'none' === ($config['depreciation_policy'] ?? 'none') ? 'selected' : ''; ?>>No Depreciation (Heritage)</option>
                                <option value="straight_line" <?php echo 'straight_line' === ($config['depreciation_policy'] ?? '') ? 'selected' : ''; ?>>Straight Line</option>
                                <option value="reducing_balance" <?php echo 'reducing_balance' === ($config['depreciation_policy'] ?? '') ? 'selected' : ''; ?>>Reducing Balance</option>
                            </select>
                            <small class="text-muted">Heritage assets typically not depreciated under IPSAS</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valuation Frequency (Years)</label>
                            <input type="number" name="valuation_frequency_years" class="form-control" min="1" max="10" value="<?php echo $config['valuation_frequency_years'] ?? 5; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nominal Value (for unvalued assets)</label>
                            <input type="number" name="nominal_value" class="form-control" step="0.01" min="0" value="<?php echo $config['nominal_value'] ?? 1; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Impairment Threshold (%)</label>
                            <input type="number" name="impairment_threshold_percent" class="form-control" min="0" max="100" value="<?php echo $config['impairment_threshold_percent'] ?? 10; ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Insurance</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Insurance Review Period (Months)</label>
                            <input type="number" name="insurance_review_months" class="form-control" min="1" max="24" value="<?php echo $config['insurance_review_months'] ?? 12; ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle"></i> IPSAS Heritage Assets</h6>
                <ul class="small mb-0">
                    <li>Heritage assets may be recognized at nominal value</li>
                    <li>Depreciation typically not applied</li>
                    <li>Regular impairment assessment required</li>
                    <li>Fair value revaluation every 3-5 years</li>
                </ul>
            </div>
            <div class="card">
                <div class="card-body d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>Save Configuration</button>
                    <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'index']); ?>" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>
