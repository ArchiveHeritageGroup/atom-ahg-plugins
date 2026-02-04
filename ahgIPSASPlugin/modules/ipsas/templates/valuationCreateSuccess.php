<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'index']); ?>">IPSAS</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'valuations']); ?>">Valuations</a></li>
                    <li class="breadcrumb-item active">New Valuation</li>
                </ol>
            </nav>
            <h1><i class="fas fa-calculator me-2"></i>Record Valuation</h1>
        </div>
    </div>

    <form method="post" class="row g-4">
        <div class="col-lg-8">
            <?php if (isset($asset)): ?>
            <input type="hidden" name="asset_id" value="<?php echo $asset->id; ?>">
            <div class="alert alert-info">
                <strong>Asset:</strong> <?php echo htmlspecialchars($asset->title); ?>
                (<?php echo htmlspecialchars($asset->asset_number); ?>)
            </div>
            <?php else: ?>
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Select Asset</h5></div>
                <div class="card-body">
                    <label class="form-label">Asset <span class="text-danger">*</span></label>
                    <input type="number" name="asset_id" class="form-control" required placeholder="Enter Asset ID">
                </div>
            </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Valuation Details</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Valuation Date <span class="text-danger">*</span></label>
                            <input type="date" name="valuation_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valuation Type</label>
                            <select name="valuation_type" class="form-select">
                                <option value="initial">Initial</option>
                                <option value="revaluation">Revaluation</option>
                                <option value="impairment">Impairment</option>
                                <option value="reversal">Reversal</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valuation Basis</label>
                            <select name="valuation_basis" class="form-select">
                                <option value="historical_cost">Historical Cost</option>
                                <option value="fair_value">Fair Value</option>
                                <option value="nominal">Nominal Value</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valuation Method</label>
                            <select name="valuation_method" class="form-select">
                                <option value="market_comparison">Market Comparison</option>
                                <option value="income_approach">Income Approach</option>
                                <option value="cost_approach">Cost Approach</option>
                                <option value="expert_opinion">Expert Opinion</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Previous Value</label>
                            <input type="number" name="previous_value" class="form-control" step="0.01" min="0" value="<?php echo isset($asset) ? $asset->current_value : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">New Value <span class="text-danger">*</span></label>
                            <input type="number" name="new_value" class="form-control" step="0.01" min="0" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Valuer Information</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Valuer Name</label>
                            <input type="text" name="valuer_name" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valuer Type</label>
                            <select name="valuer_type" class="form-select">
                                <option value="internal">Internal</option>
                                <option value="external">External</option>
                                <option value="government">Government</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Qualifications</label>
                            <input type="text" name="valuer_qualification" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Market Evidence</label>
                            <textarea name="market_evidence" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Documentation Reference</label>
                            <input type="text" name="documentation_ref" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle"></i> IPSAS Valuation</h6>
                <p class="small mb-0">Record valuation changes as required by IPSAS 17/31. Ensure proper documentation and valuer credentials for audit compliance.</p>
            </div>
            <div class="card">
                <div class="card-body d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>Record Valuation</button>
                    <a href="<?php echo url_for(['module' => 'ipsas', 'action' => 'valuations']); ?>" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>
