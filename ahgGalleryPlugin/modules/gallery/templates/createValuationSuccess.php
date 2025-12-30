<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'dashboard']); ?>">Gallery</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'valuations', 'object_id' => $objectId]); ?>">Valuations</a></li>
        <li class="breadcrumb-item active">New Valuation</li>
    </ol>
</nav>
<h1 class="h2 mb-4"><i class="fas fa-plus text-primary me-2"></i>New Valuation<?php if ($object): ?> - <?php echo $object->title; ?><?php endif; ?></h1>
<div class="card">
    <div class="card-body">
        <form method="post">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3"><label class="form-label">Valuation Type *</label>
                        <select name="valuation_type" class="form-select" required>
                            <option value="insurance">Insurance</option>
                            <option value="market">Market Value</option>
                            <option value="replacement">Replacement</option>
                            <option value="auction_estimate">Auction Estimate</option>
                            <option value="probate">Probate</option>
                            <option value="donation">Donation</option>
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4"><label class="form-label">Currency</label>
                            <select name="currency" class="form-select">
                                <option value="ZAR">ZAR</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                <option value="GBP">GBP</option>
                            </select>
                        </div>
                        <div class="col-8"><label class="form-label">Value *</label><input type="number" name="value_amount" class="form-control" step="0.01" required></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6"><label class="form-label">Valuation Date *</label><input type="date" name="valuation_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div>
                        <div class="col-6"><label class="form-label">Valid Until</label><input type="date" name="valid_until" class="form-control"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3"><label class="form-label">Appraiser Name</label><input type="text" name="appraiser_name" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Credentials</label><input type="text" name="appraiser_credentials" class="form-control" placeholder="e.g., AAA, ASA, ISA"></div>
                    <div class="mb-3"><label class="form-label">Organization</label><input type="text" name="appraiser_organization" class="form-control"></div>
                </div>
            </div>
            <div class="mb-3"><label class="form-label">Methodology</label><textarea name="methodology" class="form-control" rows="2" placeholder="Valuation method used..."></textarea></div>
            <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
            <hr>
            <div class="d-flex justify-content-between">
                <a href="<?php echo url_for(['module' => 'gallery', 'action' => 'valuations', 'object_id' => $objectId]); ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Valuation</button>
            </div>
        </form>
    </div>
</div>
