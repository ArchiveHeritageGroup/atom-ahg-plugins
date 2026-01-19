<?php use_helper('Date'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'loan', 'action' => 'index']); ?>">Loans</a></li>
    <li class="breadcrumb-item active">New Loan <?php echo $loanType === 'out' ? 'Out' : 'In'; ?></li>
  </ol>
</nav>

<div class="row">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">
        <h3 class="mb-0">
          <?php if ($loanType === 'out'): ?>
            <i class="fa-solid fa-arrow-right-from-bracket me-2"></i>New Loan Out
          <?php else: ?>
            <i class="fa-solid fa-arrow-right-to-bracket me-2"></i>New Loan In
          <?php endif; ?>
        </h3>
        <p class="text-muted mb-0">
          <?php if ($loanType === 'out'): ?>
            Lend objects from your collection to another institution
          <?php else: ?>
            Borrow objects from another institution
          <?php endif; ?>
        </p>
      </div>
      <form action="<?php echo url_for(['module' => 'loan', 'action' => 'add', 'type' => $loanType]); ?>" method="post">
        <div class="card-body">
          <!-- Basic Information -->
          <h5 class="mb-3">Basic Information</h5>
          <div class="row mb-3">
            <div class="col-md-12">
              <label class="form-label">Loan Title</label>
              <input type="text" name="title" class="form-control" placeholder="e.g., African Art Exhibition Loan 2025">
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Purpose <span class="text-danger">*</span></label>
              <select name="purpose" class="form-select" required>
                <?php foreach ($purposes as $key => $label): ?>
                  <option value="<?php echo $key; ?>" <?php echo ($exhibition && $key === 'exhibition') ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($label); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Brief description of the loan purpose and context"></textarea>
          </div>

          <hr>

          <!-- Partner Institution -->
          <h5 class="mb-3"><?php echo $loanType === 'out' ? 'Borrowing Institution' : 'Lending Institution'; ?></h5>
          <div class="row mb-3">
            <div class="col-md-12">
              <label class="form-label">Institution Name <span class="text-danger">*</span></label>
              <input type="text" name="partner_institution" class="form-control" required placeholder="e.g., National Museum of Art">
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Contact Person</label>
              <input type="text" name="partner_contact_name" class="form-control" placeholder="Full name">
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="partner_contact_email" class="form-control" placeholder="email@institution.org">
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Phone</label>
              <input type="text" name="partner_contact_phone" class="form-control" placeholder="+27 11 123 4567">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Address</label>
            <textarea name="partner_address" class="form-control" rows="2" placeholder="Physical address of the institution"></textarea>
          </div>

          <hr>

          <!-- Dates -->
          <h5 class="mb-3">Loan Period</h5>
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Start Date</label>
              <input type="date" name="start_date" class="form-control" value="<?php echo $exhibition ? ($exhibition['opening_date'] ?? '') : ''; ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">End Date</label>
              <input type="date" name="end_date" class="form-control" value="<?php echo $exhibition ? ($exhibition['closing_date'] ?? '') : ''; ?>">
            </div>
          </div>

          <hr>

          <!-- Insurance -->
          <h5 class="mb-3">Insurance & Valuation</h5>
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Insurance Type</label>
              <select name="insurance_type" class="form-select">
                <?php foreach ($insuranceTypes as $key => $label): ?>
                  <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($label); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Total Insurance Value</label>
              <div class="input-group">
                <select name="insurance_currency" class="form-select" style="max-width: 100px;">
                  <option value="ZAR" selected>ZAR</option>
                  <option value="USD">USD</option>
                  <option value="EUR">EUR</option>
                  <option value="GBP">GBP</option>
                </select>
                <input type="number" name="insurance_value" class="form-control" step="0.01" placeholder="0.00">
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Insurance Provider</label>
            <input type="text" name="insurance_provider" class="form-control" placeholder="Insurance company name">
          </div>

          <hr>

          <!-- Fees -->
          <h5 class="mb-3">Loan Fee</h5>
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Loan Fee (if applicable)</label>
              <div class="input-group">
                <span class="input-group-text">ZAR</span>
                <input type="number" name="loan_fee" class="form-control" step="0.01" placeholder="0.00">
              </div>
            </div>
          </div>

          <hr>

          <!-- Notes -->
          <div class="mb-3">
            <label class="form-label">Internal Notes</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Any internal notes or special instructions"></textarea>
          </div>
        </div>

        <div class="card-footer">
          <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-save me-1"></i> Create Loan
          </button>
          <a href="<?php echo url_for(['module' => 'loan', 'action' => 'index']); ?>" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <div class="col-md-4">
    <!-- Help Panel -->
    <div class="card mb-3">
      <div class="card-header">
        <h5 class="mb-0"><i class="fa-solid fa-info-circle me-2"></i>Loan Types</h5>
      </div>
      <div class="card-body">
        <p><strong>Loan Out:</strong> When you lend objects from your collection to another institution.</p>
        <p class="mb-0"><strong>Loan In:</strong> When you borrow objects from another institution for your displays or research.</p>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header">
        <h5 class="mb-0"><i class="fa-solid fa-shield-halved me-2"></i>Insurance Types</h5>
      </div>
      <div class="card-body small">
        <p><strong>Borrower (Wall-to-Wall):</strong> Borrower covers insurance from pickup to return.</p>
        <p><strong>Lender Coverage:</strong> Lender maintains their existing insurance.</p>
        <p><strong>Shared Coverage:</strong> Both parties share insurance responsibility.</p>
        <p><strong>Government Indemnity:</strong> State-provided indemnity program.</p>
        <p class="mb-0"><strong>Self-Insured:</strong> Institution assumes risk without external insurance.</p>
      </div>
    </div>

    <?php if ($exhibition): ?>
      <div class="card border-info">
        <div class="card-header bg-info text-white">
          <h5 class="mb-0"><i class="fa-solid fa-image me-2"></i>Linked Exhibition</h5>
        </div>
        <div class="card-body">
          <p class="mb-1"><strong><?php echo htmlspecialchars($exhibition['title']); ?></strong></p>
          <p class="small text-muted mb-0">
            <?php if (!empty($exhibition['opening_date'])): ?>
              <?php echo date('Y-m-d', strtotime($exhibition['opening_date'])); ?>
            <?php endif; ?>
            <?php if (!empty($exhibition['closing_date'])): ?>
              - <?php echo date('Y-m-d', strtotime($exhibition['closing_date'])); ?>
            <?php endif; ?>
          </p>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
