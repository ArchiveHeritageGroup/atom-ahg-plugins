<?php use_helper('Date'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'loan', 'action' => 'index']); ?>">Loans</a></li>
    <li class="breadcrumb-item active"><?php echo htmlspecialchars($loan['loan_number']); ?></li>
  </ol>
</nav>

<?php
  $isLoanOut = $loan['loan_type'] === 'out';
  $isOverdue = !empty($loan['is_overdue']);
  $currentState = $loan['workflow']['current_state'] ?? 'pending';
?>

<div class="row">
  <div class="col-md-8">
    <!-- Loan Header -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h3 class="mb-0">
            <?php if ($isLoanOut): ?>
              <span class="badge bg-warning text-dark me-2"><i class="fa-solid fa-arrow-right-from-bracket"></i> Loan Out</span>
            <?php else: ?>
              <span class="badge bg-info me-2"><i class="fa-solid fa-arrow-right-to-bracket"></i> Loan In</span>
            <?php endif; ?>
            <?php echo htmlspecialchars($loan['loan_number']); ?>
          </h3>
          <?php if (!empty($loan['title'])): ?>
            <p class="text-muted mb-0"><?php echo htmlspecialchars($loan['title']); ?></p>
          <?php endif; ?>
        </div>
        <div>
          <?php
            $statusColors = [
              'pending' => 'secondary',
              'submitted' => 'info',
              'approved' => 'primary',
              'active' => 'success',
              'returned' => 'dark',
              'cancelled' => 'danger',
            ];
            $statusColor = $statusColors[$currentState] ?? 'secondary';
          ?>
          <span class="badge bg-<?php echo $statusColor; ?> fs-6"><?php echo ucfirst($currentState); ?></span>
          <?php if ($isOverdue): ?>
            <span class="badge bg-danger fs-6">Overdue</span>
          <?php endif; ?>
        </div>
      </div>

      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <h5><?php echo $isLoanOut ? 'Borrowing Institution' : 'Lending Institution'; ?></h5>
            <p class="mb-1"><strong><?php echo htmlspecialchars($loan['partner_institution']); ?></strong></p>
            <?php if (!empty($loan['partner_contact_name'])): ?>
              <p class="mb-1"><?php echo htmlspecialchars($loan['partner_contact_name']); ?></p>
            <?php endif; ?>
            <?php if (!empty($loan['partner_contact_email'])): ?>
              <p class="mb-1"><i class="fa-solid fa-envelope me-1"></i> <?php echo htmlspecialchars($loan['partner_contact_email']); ?></p>
            <?php endif; ?>
            <?php if (!empty($loan['partner_contact_phone'])): ?>
              <p class="mb-1"><i class="fa-solid fa-phone me-1"></i> <?php echo htmlspecialchars($loan['partner_contact_phone']); ?></p>
            <?php endif; ?>
            <?php if (!empty($loan['partner_address'])): ?>
              <p class="mb-0"><i class="fa-solid fa-location-dot me-1"></i> <?php echo nl2br(htmlspecialchars($loan['partner_address'])); ?></p>
            <?php endif; ?>
          </div>
          <div class="col-md-6">
            <h5>Loan Details</h5>
            <table class="table table-sm">
              <tr>
                <td class="text-muted">Purpose</td>
                <td><?php echo htmlspecialchars($purposes[$loan['purpose']] ?? $loan['purpose']); ?></td>
              </tr>
              <tr>
                <td class="text-muted">Request Date</td>
                <td><?php echo $loan['request_date'] ? date('Y-m-d', strtotime($loan['request_date'])) : '-'; ?></td>
              </tr>
              <tr>
                <td class="text-muted">Start Date</td>
                <td><?php echo $loan['start_date'] ? date('Y-m-d', strtotime($loan['start_date'])) : '-'; ?></td>
              </tr>
              <tr>
                <td class="text-muted">End Date</td>
                <td>
                  <?php echo $loan['end_date'] ? date('Y-m-d', strtotime($loan['end_date'])) : '-'; ?>
                  <?php if (isset($loan['days_remaining'])): ?>
                    <?php if ($loan['days_remaining'] > 0): ?>
                      <span class="badge bg-success"><?php echo $loan['days_remaining']; ?> days remaining</span>
                    <?php elseif ($loan['days_remaining'] < 0): ?>
                      <span class="badge bg-danger"><?php echo abs($loan['days_remaining']); ?> days overdue</span>
                    <?php else: ?>
                      <span class="badge bg-warning text-dark">Due today</span>
                    <?php endif; ?>
                  <?php endif; ?>
                </td>
              </tr>
              <?php if (!empty($loan['return_date'])): ?>
                <tr>
                  <td class="text-muted">Return Date</td>
                  <td><?php echo date('Y-m-d', strtotime($loan['return_date'])); ?></td>
                </tr>
              <?php endif; ?>
            </table>
          </div>
        </div>

        <?php if (!empty($loan['description'])): ?>
          <hr>
          <h5>Description</h5>
          <p><?php echo nl2br(htmlspecialchars($loan['description'])); ?></p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Objects -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fa-solid fa-cubes me-2"></i>Loan Objects</h5>
        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addObjectModal">
          <i class="fa-solid fa-plus"></i> Add Object
        </button>
      </div>
      <div class="card-body p-0">
        <?php if (empty($loan['objects'])): ?>
          <div class="p-4 text-center text-muted">
            <p>No objects added to this loan yet</p>
          </div>
        <?php else: ?>
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Identifier</th>
                <th>Title</th>
                <th>Insurance Value</th>
                <th>Special Requirements</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($loan['objects'] as $obj): ?>
                <tr>
                  <td>
                    <?php if (!empty($obj['information_object_id'])): ?>
                      <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'show', 'id' => $obj['information_object_id']]); ?>">
                        <?php echo htmlspecialchars($obj['identifier'] ?? $obj['object_identifier'] ?? '-'); ?>
                      </a>
                    <?php else: ?>
                      <?php echo htmlspecialchars($obj['object_identifier'] ?? '-'); ?>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($obj['io_title'] ?? $obj['object_title'] ?? '-'); ?></td>
                  <td>
                    <?php if (!empty($obj['insurance_value'])): ?>
                      R <?php echo number_format($obj['insurance_value'], 2); ?>
                    <?php else: ?>
                      -
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($obj['special_requirements'])): ?>
                      <small><?php echo htmlspecialchars(substr($obj['special_requirements'], 0, 50)); ?>...</small>
                    <?php else: ?>
                      -
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <form action="<?php echo url_for(['module' => 'loan', 'action' => 'removeObject', 'id' => $loan['id']]); ?>" method="post" style="display: inline;" onsubmit="return confirm('Remove this object from the loan?');">
                      <input type="hidden" name="object_id" value="<?php echo $obj['information_object_id']; ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove">
                        <i class="fa-solid fa-times"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr class="table-light">
                <td colspan="2"><strong>Total Insurance Value</strong></td>
                <td colspan="3">
                  <strong>R <?php echo number_format(array_sum(array_column(sfOutputEscaper::unescape($loan['objects']), 'insurance_value')), 2); ?></strong>
                </td>
              </tr>
            </tfoot>
          </table>
        <?php endif; ?>
      </div>
    </div>

    <!-- Documents -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fa-solid fa-file-alt me-2"></i>Documents</h5>
        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
          <i class="fa-solid fa-upload"></i> Upload Document
        </button>
      </div>
      <div class="card-body p-0">
        <?php if (empty($loan['documents'])): ?>
          <div class="p-4 text-center text-muted">
            <p>No documents uploaded yet</p>
          </div>
        <?php else: ?>
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Type</th>
                <th>File Name</th>
                <th>Description</th>
                <th>Uploaded</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($loan['documents'] as $doc): ?>
                <tr>
                  <td><span class="badge bg-secondary"><?php echo htmlspecialchars($doc['document_type']); ?></span></td>
                  <td><?php echo htmlspecialchars($doc['file_name']); ?></td>
                  <td><?php echo htmlspecialchars($doc['description'] ?? '-'); ?></td>
                  <td><?php echo date('Y-m-d', strtotime($doc['created_at'])); ?></td>
                  <td class="text-end">
                    <a href="<?php echo $doc['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                      <i class="fa-solid fa-download"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

    <!-- Notes -->
    <?php if (!empty($loan['notes'])): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fa-solid fa-sticky-note me-2"></i>Notes</h5>
        </div>
        <div class="card-body">
          <?php echo nl2br(htmlspecialchars($loan['notes'])); ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-md-4">
    <!-- Actions -->
    <div class="card mb-3">
      <div class="card-header">
        <h5 class="mb-0">Actions</h5>
      </div>
      <div class="list-group list-group-flush">
        <a href="<?php echo url_for(['module' => 'loan', 'action' => 'edit', 'id' => $loan['id']]); ?>" class="list-group-item list-group-item-action">
          <i class="fa-solid fa-edit me-2"></i> Edit Loan
        </a>
        <a href="<?php echo url_for(['module' => 'loan', 'action' => 'agreement', 'id' => $loan['id']]); ?>" class="list-group-item list-group-item-action" target="_blank">
          <i class="fa-solid fa-file-contract me-2"></i> View Agreement
        </a>
        <a href="<?php echo url_for(['module' => 'loan', 'action' => 'agreement', 'id' => $loan['id'], 'format' => 'pdf']); ?>" class="list-group-item list-group-item-action" target="_blank">
          <i class="fa-solid fa-file-pdf me-2"></i> Download PDF Agreement
        </a>
        <?php if (empty($loan['return_date'])): ?>
          <a href="#" class="list-group-item list-group-item-action" data-bs-toggle="modal" data-bs-target="#extendModal">
            <i class="fa-solid fa-calendar-plus me-2"></i> Extend Loan
          </a>
          <a href="#" class="list-group-item list-group-item-action text-success" data-bs-toggle="modal" data-bs-target="#returnModal">
            <i class="fa-solid fa-undo me-2"></i> Record Return
          </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Workflow Transitions -->
    <?php if (!empty($availableTransitions)): ?>
      <div class="card mb-3">
        <div class="card-header">
          <h5 class="mb-0">Workflow</h5>
        </div>
        <div class="card-body">
          <p class="text-muted small">Current state: <strong><?php echo ucfirst($currentState); ?></strong></p>
          <div class="d-grid gap-2">
            <?php foreach ($availableTransitions as $transition): ?>
              <button type="button" class="btn btn-outline-primary btn-sm transition-btn"
                      data-transition="<?php echo htmlspecialchars($transition['name']); ?>"
                      data-loan-id="<?php echo $loan['id']; ?>">
                <?php echo htmlspecialchars($transition['label'] ?? ucfirst($transition['name'])); ?>
              </button>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Insurance Details -->
    <div class="card mb-3">
      <div class="card-header">
        <h5 class="mb-0"><i class="fa-solid fa-shield-halved me-2"></i>Insurance</h5>
      </div>
      <div class="card-body">
        <table class="table table-sm mb-0">
          <tr>
            <td class="text-muted">Type</td>
            <td><?php echo htmlspecialchars($insuranceTypes[$loan['insurance_type']] ?? $loan['insurance_type'] ?? '-'); ?></td>
          </tr>
          <tr>
            <td class="text-muted">Value</td>
            <td>
              <?php if (!empty($loan['insurance_value'])): ?>
                <?php echo $loan['insurance_currency'] ?? 'ZAR'; ?> <?php echo number_format($loan['insurance_value'], 2); ?>
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
          </tr>
          <?php if (!empty($loan['insurance_provider'])): ?>
            <tr>
              <td class="text-muted">Provider</td>
              <td><?php echo htmlspecialchars($loan['insurance_provider']); ?></td>
            </tr>
          <?php endif; ?>
          <?php if (!empty($loan['insurance_policy_number'])): ?>
            <tr>
              <td class="text-muted">Policy #</td>
              <td><?php echo htmlspecialchars($loan['insurance_policy_number']); ?></td>
            </tr>
          <?php endif; ?>
        </table>
      </div>
    </div>

    <!-- Fees -->
    <?php if (!empty($loan['loan_fee'])): ?>
      <div class="card mb-3">
        <div class="card-header">
          <h5 class="mb-0"><i class="fa-solid fa-money-bill me-2"></i>Fees</h5>
        </div>
        <div class="card-body">
          <p class="mb-0">
            <strong><?php echo $loan['loan_fee_currency'] ?? 'ZAR'; ?> <?php echo number_format($loan['loan_fee'], 2); ?></strong>
          </p>
        </div>
      </div>
    <?php endif; ?>

    <!-- Timeline/History -->
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="fa-solid fa-history me-2"></i>History</h5>
      </div>
      <div class="card-body p-0">
        <ul class="list-group list-group-flush small">
          <li class="list-group-item">
            <strong>Created</strong><br>
            <?php echo date('Y-m-d H:i', strtotime($loan['created_at'])); ?>
          </li>
          <?php if (!empty($loan['approved_date'])): ?>
            <li class="list-group-item">
              <strong>Approved</strong><br>
              <?php echo date('Y-m-d H:i', strtotime($loan['approved_date'])); ?>
            </li>
          <?php endif; ?>
          <?php if (!empty($loan['start_date'])): ?>
            <li class="list-group-item">
              <strong>Start Date</strong><br>
              <?php echo date('Y-m-d', strtotime($loan['start_date'])); ?>
            </li>
          <?php endif; ?>
          <?php if (!empty($loan['return_date'])): ?>
            <li class="list-group-item text-success">
              <strong>Returned</strong><br>
              <?php echo date('Y-m-d', strtotime($loan['return_date'])); ?>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- Add Object Modal -->
<div class="modal fade" id="addObjectModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="<?php echo url_for(['module' => 'loan', 'action' => 'addObject', 'id' => $loan['id']]); ?>" method="post">
        <div class="modal-header">
          <h5 class="modal-title">Add Object to Loan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Search Object</label>
            <input type="text" class="form-control" id="objectSearch" placeholder="Search by title or identifier...">
            <input type="hidden" name="information_object_id" id="selectedObjectId">
            <input type="hidden" name="object_title" id="selectedObjectTitle">
            <input type="hidden" name="object_identifier" id="selectedObjectIdentifier">
            <div id="searchResults" class="list-group mt-2" style="max-height: 200px; overflow-y: auto;"></div>
          </div>
          <div class="mb-3">
            <label class="form-label">Insurance Value (ZAR)</label>
            <input type="number" name="insurance_value" class="form-control" step="0.01">
          </div>
          <div class="mb-3">
            <label class="form-label">Special Requirements</label>
            <textarea name="special_requirements" class="form-control" rows="2"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Display Requirements</label>
            <textarea name="display_requirements" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Object</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadDocModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="<?php echo url_for(['module' => 'loan', 'action' => 'uploadDocument', 'id' => $loan['id']]); ?>" method="post" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title">Upload Document</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Document Type</label>
            <select name="document_type" class="form-select" required>
              <option value="agreement">Loan Agreement</option>
              <option value="condition_report">Condition Report</option>
              <option value="insurance">Insurance Certificate</option>
              <option value="courier">Courier Documentation</option>
              <option value="correspondence">Correspondence</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">File</label>
            <input type="file" name="document" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Upload</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Extend Loan Modal -->
<div class="modal fade" id="extendModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="<?php echo url_for(['module' => 'loan', 'action' => 'extend', 'id' => $loan['id']]); ?>" method="post">
        <div class="modal-header">
          <h5 class="modal-title">Extend Loan Period</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Current end date: <strong><?php echo $loan['end_date'] ? date('Y-m-d', strtotime($loan['end_date'])) : 'Not set'; ?></strong></p>
          <div class="mb-3">
            <label class="form-label">New End Date</label>
            <input type="date" name="new_end_date" class="form-control" required value="<?php echo date('Y-m-d', strtotime($loan['end_date'] ?? 'now') + 30*24*60*60); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Reason for Extension</label>
            <textarea name="reason" class="form-control" rows="3" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Extend Loan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Record Return Modal -->
<div class="modal fade" id="returnModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="<?php echo url_for(['module' => 'loan', 'action' => 'return', 'id' => $loan['id']]); ?>" method="post">
        <div class="modal-header">
          <h5 class="modal-title">Record Return</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Return Date</label>
            <input type="date" name="return_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Condition notes, etc."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Record Return</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Object search
  const searchInput = document.getElementById('objectSearch');
  const resultsDiv = document.getElementById('searchResults');
  let searchTimeout;

  if (searchInput) {
    searchInput.addEventListener('input', function() {
      clearTimeout(searchTimeout);
      const query = this.value.trim();

      if (query.length < 2) {
        resultsDiv.innerHTML = '';
        return;
      }

      searchTimeout = setTimeout(function() {
        fetch('<?php echo url_for(['module' => 'loan', 'action' => 'searchObjects']); ?>?q=' + encodeURIComponent(query))
          .then(response => response.json())
          .then(data => {
            resultsDiv.innerHTML = '';
            if (data.objects && data.objects.length > 0) {
              data.objects.forEach(function(obj) {
                const item = document.createElement('a');
                item.href = '#';
                item.className = 'list-group-item list-group-item-action';
                item.innerHTML = '<strong>' + (obj.identifier || '-') + '</strong> - ' + (obj.title || 'Untitled');
                item.addEventListener('click', function(e) {
                  e.preventDefault();
                  document.getElementById('selectedObjectId').value = obj.id;
                  document.getElementById('selectedObjectTitle').value = obj.title || '';
                  document.getElementById('selectedObjectIdentifier').value = obj.identifier || '';
                  searchInput.value = (obj.identifier || '') + ' - ' + (obj.title || 'Untitled');
                  resultsDiv.innerHTML = '';
                });
                resultsDiv.appendChild(item);
              });
            } else {
              resultsDiv.innerHTML = '<div class="list-group-item text-muted">No objects found</div>';
            }
          });
      }, 300);
    });
  }

  // Workflow transitions
  document.querySelectorAll('.transition-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      const transition = this.dataset.transition;
      const loanId = this.dataset.loanId;
      const comment = prompt('Comment (optional):');

      fetch('<?php echo url_for(['module' => 'loan', 'action' => 'transition']); ?>?id=' + loanId, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'transition=' + encodeURIComponent(transition) + '&comment=' + encodeURIComponent(comment || '')
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          location.reload();
        } else {
          alert('Error: ' + (data.error || 'Unknown error'));
        }
      });
    });
  });
});
</script>
