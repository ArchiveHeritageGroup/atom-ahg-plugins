<?php use_helper('Date'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'loan', 'action' => 'index']); ?>">Loans</a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'loan', 'action' => 'show', 'id' => $loan['id']]); ?>"><?php echo htmlspecialchars($loan['loan_number']); ?></a></li>
    <li class="breadcrumb-item active">Edit</li>
  </ol>
</nav>

<?php $isLoanOut = $loan['loan_type'] === 'out'; ?>

<div class="row">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">
        <h3 class="mb-0">
          <?php if ($isLoanOut): ?>
            <span class="badge bg-warning text-dark me-2"><i class="fa-solid fa-arrow-right-from-bracket"></i> Loan Out</span>
          <?php else: ?>
            <span class="badge bg-info me-2"><i class="fa-solid fa-arrow-right-to-bracket"></i> Loan In</span>
          <?php endif; ?>
          Edit <?php echo htmlspecialchars($loan['loan_number']); ?>
        </h3>
      </div>
      <form action="<?php echo url_for(['module' => 'loan', 'action' => 'edit', 'id' => $loan['id']]); ?>" method="post">
        <div class="card-body">
          <!-- Basic Information -->
          <h5 class="mb-3">Basic Information</h5>
          <div class="row mb-3">
            <div class="col-md-12">
              <label class="form-label">Loan Title</label>
              <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($loan['title'] ?? ''); ?>">
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Purpose <span class="text-danger">*</span></label>
              <select name="purpose" class="form-select" required>
                <?php foreach ($purposes as $key => $label): ?>
                  <option value="<?php echo $key; ?>" <?php echo ($loan['purpose'] ?? '') === $key ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($label); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($loan['description'] ?? ''); ?></textarea>
          </div>

          <hr>

          <!-- Partner Institution -->
          <h5 class="mb-3"><?php echo $isLoanOut ? 'Borrowing Institution' : 'Lending Institution'; ?></h5>
          <div class="row mb-3">
            <div class="col-md-12">
              <label class="form-label">Institution Name <span class="text-danger">*</span></label>
              <input type="text" name="partner_institution" class="form-control" required value="<?php echo htmlspecialchars($loan['partner_institution'] ?? ''); ?>">
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Contact Person</label>
              <input type="text" name="partner_contact_name" class="form-control" value="<?php echo htmlspecialchars($loan['partner_contact_name'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="partner_contact_email" class="form-control" value="<?php echo htmlspecialchars($loan['partner_contact_email'] ?? ''); ?>">
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Phone</label>
              <input type="text" name="partner_contact_phone" class="form-control" value="<?php echo htmlspecialchars($loan['partner_contact_phone'] ?? ''); ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Address</label>
            <textarea name="partner_address" class="form-control" rows="2"><?php echo htmlspecialchars($loan['partner_address'] ?? ''); ?></textarea>
          </div>

          <hr>

          <!-- Dates -->
          <h5 class="mb-3">Loan Period</h5>
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Start Date</label>
              <input type="date" name="start_date" class="form-control" value="<?php echo $loan['start_date'] ? date('Y-m-d', strtotime($loan['start_date'])) : ''; ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">End Date</label>
              <input type="date" name="end_date" class="form-control" value="<?php echo $loan['end_date'] ? date('Y-m-d', strtotime($loan['end_date'])) : ''; ?>">
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
                  <option value="<?php echo $key; ?>" <?php echo ($loan['insurance_type'] ?? '') === $key ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($label); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Total Insurance Value</label>
              <div class="input-group">
                <select name="insurance_currency" class="form-select" style="max-width: 100px;">
                  <option value="ZAR" <?php echo ($loan['insurance_currency'] ?? 'ZAR') === 'ZAR' ? 'selected' : ''; ?>>ZAR</option>
                  <option value="USD" <?php echo ($loan['insurance_currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD</option>
                  <option value="EUR" <?php echo ($loan['insurance_currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR</option>
                  <option value="GBP" <?php echo ($loan['insurance_currency'] ?? '') === 'GBP' ? 'selected' : ''; ?>>GBP</option>
                </select>
                <input type="number" name="insurance_value" class="form-control" step="0.01" value="<?php echo $loan['insurance_value'] ?? ''; ?>">
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Insurance Provider</label>
            <input type="text" name="insurance_provider" class="form-control" value="<?php echo htmlspecialchars($loan['insurance_provider'] ?? ''); ?>">
          </div>

          <hr>

          <!-- Fees -->
          <h5 class="mb-3">Loan Fee</h5>
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Loan Fee (if applicable)</label>
              <div class="input-group">
                <span class="input-group-text">ZAR</span>
                <input type="number" name="loan_fee" class="form-control" step="0.01" value="<?php echo $loan['loan_fee'] ?? ''; ?>">
              </div>
            </div>
          </div>

          <hr>

          <!-- Notes -->
          <div class="mb-3">
            <label class="form-label">Internal Notes</label>
            <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($loan['notes'] ?? ''); ?></textarea>
          </div>
        </div>

        <div class="card-footer">
          <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-save me-1"></i> Save Changes
          </button>
          <a href="<?php echo url_for(['module' => 'loan', 'action' => 'show', 'id' => $loan['id']]); ?>" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <div class="col-md-4">
    <!-- Loan Info -->
    <div class="card mb-3">
      <div class="card-header">
        <h5 class="mb-0"><i class="fa-solid fa-info-circle me-2"></i>Loan Info</h5>
      </div>
      <div class="card-body">
        <table class="table table-sm mb-0">
          <tr>
            <td class="text-muted">Loan Number</td>
            <td><strong><?php echo htmlspecialchars($loan['loan_number']); ?></strong></td>
          </tr>
          <tr>
            <td class="text-muted">Type</td>
            <td><?php echo $isLoanOut ? 'Loan Out' : 'Loan In'; ?></td>
          </tr>
          <tr>
            <td class="text-muted">Created</td>
            <td><?php echo date('Y-m-d', strtotime($loan['created_at'])); ?></td>
          </tr>
          <tr>
            <td class="text-muted">Objects</td>
            <td><?php echo count($loan['objects'] ?? []); ?></td>
          </tr>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Actions</h5>
      </div>
      <div class="list-group list-group-flush">
        <a href="<?php echo url_for(['module' => 'loan', 'action' => 'show', 'id' => $loan['id']]); ?>" class="list-group-item list-group-item-action">
          <i class="fa-solid fa-eye me-2"></i> View Loan Details
        </a>
        <a href="<?php echo url_for(['module' => 'loan', 'action' => 'index']); ?>" class="list-group-item list-group-item-action">
          <i class="fa-solid fa-list me-2"></i> Back to Loans List
        </a>
      </div>
    </div>
  </div>
</div>
