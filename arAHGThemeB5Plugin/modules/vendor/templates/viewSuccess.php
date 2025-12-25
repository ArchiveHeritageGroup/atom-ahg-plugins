<?php 
// Get raw data to avoid Symfony escaping issues
$statsRaw = isset($sf_data) ? $sf_data->getRaw('stats') : $stats;
$contactsRaw = isset($sf_data) ? $sf_data->getRaw('contacts') : $contacts;
$servicesRaw = isset($sf_data) ? $sf_data->getRaw('services') : $services;
$transactionsRaw = isset($sf_data) ? $sf_data->getRaw('transactions') : $transactions;
$vendorRaw = isset($sf_data) ? $sf_data->getRaw('vendor') : $vendor;

// Convert stats to array if it's an object
if (is_object($statsRaw)) {
    $statsRaw = (array) $statsRaw;
}
?>
<?php echo get_partial('header', ['title' => $vendorRaw->name]); ?>

<div class="container-fluid px-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'vendor', 'action' => 'index']); ?>">Vendor Management</a></li>
            <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'vendor', 'action' => 'list']); ?>">Vendors</a></li>
            <li class="breadcrumb-item active"><?php echo esc_entities($vendorRaw->name); ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">
            <i class="fas fa-building me-2"></i><?php echo esc_entities($vendorRaw->name); ?>
            <?php 
            $statusColors = ['active' => 'success', 'inactive' => 'secondary', 'suspended' => 'danger', 'pending_approval' => 'warning'];
            ?>
            <span class="badge bg-<?php echo $statusColors[$vendorRaw->status] ?? 'secondary'; ?> ms-2">
                <?php echo ucfirst(str_replace('_', ' ', $vendorRaw->status)); ?>
            </span>
        </h1>
        <div>
            <a href="<?php echo url_for(['module' => 'vendor', 'action' => 'edit', 'slug' => $vendorRaw->slug]); ?>" class="btn btn-primary">
                <i class="fas fa-edit me-1"></i>Edit
            </a>
            <a href="<?php echo url_for(['module' => 'vendor', 'action' => 'addTransaction', 'vendor' => $vendorRaw->slug]); ?>" class="btn btn-success">
                <i class="fas fa-plus me-1"></i>New Transaction
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Vendor Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-info-circle me-2"></i>Vendor Details
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Vendor Code</th>
                                    <td><code><?php echo esc_entities($vendorRaw->vendor_code); ?></code></td>
                                </tr>
                                <tr>
                                    <th>Type</th>
                                    <td><?php echo ucfirst($vendorRaw->vendor_type); ?></td>
                                </tr>
                                <tr>
                                    <th>Registration #</th>
                                    <td><?php echo esc_entities($vendorRaw->registration_number ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <th>VAT Number</th>
                                    <td><?php echo esc_entities($vendorRaw->vat_number ?? '-'); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Phone</th>
                                    <td><?php echo esc_entities($vendorRaw->phone ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <th>Alt. Phone</th>
                                    <td><?php echo esc_entities($vendorRaw->phone_alt ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td>
                                        <?php if ($vendorRaw->email): ?>
                                        <a href="mailto:<?php echo $vendorRaw->email; ?>"><?php echo esc_entities($vendorRaw->email); ?></a>
                                        <?php else: ?>-<?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Website</th>
                                    <td>
                                        <?php if ($vendorRaw->website): ?>
                                        <a href="<?php echo $vendorRaw->website; ?>" target="_blank"><?php echo esc_entities($vendorRaw->website); ?></a>
                                        <?php else: ?>-<?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <?php if ($vendorRaw->street_address): ?>
                    <div class="mt-3">
                        <strong>Address:</strong><br>
                        <?php echo nl2br(esc_entities($vendorRaw->street_address)); ?><br>
                        <?php echo esc_entities($vendorRaw->city ?? ''); ?><?php if (!empty($vendorRaw->province)): ?>, <?php echo esc_entities($vendorRaw->province); ?><?php endif; ?>
                        <?php if (!empty($vendorRaw->postal_code)): ?> <?php echo esc_entities($vendorRaw->postal_code); ?><?php endif; ?><br>
                        <?php echo esc_entities($vendorRaw->country ?? ''); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Services -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-tools me-2"></i>Services Provided
                </div>
                <div class="card-body">
                    <?php if ($servicesRaw && (is_array($servicesRaw) ? count($servicesRaw) > 0 : $servicesRaw->count() > 0)): ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($servicesRaw as $service): ?>
                        <span class="badge bg-primary"><?php echo esc_entities(is_object($service) ? $service->name : $service['name']); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted mb-0">No services assigned</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Contacts -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-users me-2"></i>Contacts</span>
                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addContactModal">
                        <i class="fas fa-plus me-1"></i>Add Contact
                    </button>
                </div>
                <div class="card-body p-0">
                    <?php 
                    $contactCount = 0;
                    if ($contactsRaw) {
                        $contactCount = is_array($contactsRaw) ? count($contactsRaw) : $contactsRaw->count();
                    }
                    ?>
                    <?php if ($contactCount > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Primary</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contactsRaw as $contact): ?>
                                <tr>
                                    <td><strong><?php echo esc_entities($contact->name ?? $contact->contact_name ?? ''); ?></strong></td>
                                    <td><?php echo esc_entities($contact->position ?? '-'); ?></td>
                                    <td><?php echo esc_entities($contact->phone ?? $contact->mobile ?? '-'); ?></td>
                                    <td>
                                        <?php if (!empty($contact->email)): ?>
                                        <a href="mailto:<?php echo $contact->email; ?>"><?php echo esc_entities($contact->email); ?></a>
                                        <?php else: ?>-<?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($contact->is_primary)): ?>
                                        <span class="badge bg-success">Primary</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo url_for(['module' => 'vendor', 'action' => 'deleteContact', 'slug' => $vendorRaw->slug, 'contact_id' => $contact->id]); ?>" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Delete this contact?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-users fa-2x mb-2"></i>
                        <p class="mb-0">No contacts added yet</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-exchange-alt me-2"></i>Recent Transactions</span>
                    <a href="<?php echo url_for(['module' => 'vendor', 'action' => 'transactions']) . '?vendor_id=' . $vendorRaw->id; ?>" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php 
                    $transCount = 0;
                    if ($transactionsRaw) {
                        $transCount = is_array($transactionsRaw) ? count($transactionsRaw) : $transactionsRaw->count();
                    }
                    ?>
                    <?php if ($transCount > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Transaction #</th>
                                    <th>Service</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $transItems = is_array($transactionsRaw) ? array_slice($transactionsRaw, 0, 5) : $transactionsRaw->take(5);
                                foreach ($transItems as $trans): 
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo url_for(['module' => 'vendor', 'action' => 'viewTransaction', 'id' => $trans->id]); ?>">
                                            <?php echo esc_entities($trans->transaction_number); ?>
                                        </a>
                                    </td>
                                    <td><?php echo esc_entities($trans->service_name ?? '-'); ?></td>
                                    <td><?php echo date('d M Y', strtotime($trans->request_date)); ?></td>
                                    <td>
                                        <?php 
                                        $statusBadges = [
                                            'pending' => 'warning',
                                            'in_progress' => 'info', 
                                            'completed' => 'success',
                                            'cancelled' => 'secondary'
                                        ];
                                        $badgeClass = $statusBadges[$trans->status] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $badgeClass; ?>"><?php echo ucfirst(str_replace('_', ' ', $trans->status)); ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($trans->actual_cost)): ?>
                                        R<?php echo number_format($trans->actual_cost, 2); ?>
                                        <?php elseif (!empty($trans->estimated_cost)): ?>
                                        <span class="text-muted">~R<?php echo number_format($trans->estimated_cost, 2); ?></span>
                                        <?php else: ?>-<?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-exchange-alt fa-2x mb-2"></i>
                        <p class="mb-0">No transactions yet</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Stats -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-2"></i>Statistics
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="h3 mb-0"><?php echo $statsRaw['total_transactions'] ?? 0; ?></div>
                            <small class="text-muted">Total Transactions</small>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="h3 mb-0"><?php echo $statsRaw['active_transactions'] ?? 0; ?></div>
                            <small class="text-muted">Active</small>
                        </div>
                        <div class="col-6">
                            <div class="h3 mb-0">R<?php echo number_format($statsRaw['total_spent'] ?? 0, 0); ?></div>
                            <small class="text-muted">Total Spent</small>
                        </div>
                        <div class="col-6">
                            <div class="h3 mb-0"><?php echo $statsRaw['avg_rating'] ?? '-'; ?></div>
                            <small class="text-muted">Avg Rating</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Insurance -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-shield-alt me-2"></i>Insurance
                </div>
                <div class="card-body">
                    <?php if (!empty($vendorRaw->has_insurance)): ?>
                    <table class="table table-sm mb-0">
                        <tr>
                            <th>Provider</th>
                            <td><?php echo esc_entities($vendorRaw->insurance_provider ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Policy #</th>
                            <td><?php echo esc_entities($vendorRaw->insurance_policy_number ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Expiry</th>
                            <td>
                                <?php if (!empty($vendorRaw->insurance_expiry_date)): ?>
                                    <?php $expired = strtotime($vendorRaw->insurance_expiry_date) < time(); ?>
                                    <span class="<?php echo $expired ? 'text-danger' : ''; ?>">
                                        <?php echo date('d M Y', strtotime($vendorRaw->insurance_expiry_date)); ?>
                                        <?php if ($expired): ?><span class="badge bg-danger ms-1">Expired</span><?php endif; ?>
                                    </span>
                                <?php else: ?>-<?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Coverage</th>
                            <td>
                                <?php if (!empty($vendorRaw->insurance_coverage_amount)): ?>
                                R<?php echo number_format($vendorRaw->insurance_coverage_amount, 2); ?>
                                <?php else: ?>-<?php endif; ?>
                            </td>
                        </tr>
                    </table>
                    <?php else: ?>
                    <p class="text-muted mb-0"><i class="fas fa-exclamation-triangle text-warning me-1"></i>No insurance on file</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Banking -->
            <?php if (!empty($vendorRaw->bank_name)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-university me-2"></i>Banking Details
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <th>Bank</th>
                            <td><?php echo esc_entities($vendorRaw->bank_name); ?></td>
                        </tr>
                        <tr>
                            <th>Branch</th>
                            <td><?php echo esc_entities($vendorRaw->bank_branch ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Account #</th>
                            <td><?php echo esc_entities($vendorRaw->bank_account_number ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Branch Code</th>
                            <td><?php echo esc_entities($vendorRaw->bank_branch_code ?? '-'); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Notes -->
            <?php if (!empty($vendorRaw->notes)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-sticky-note me-2"></i>Notes
                </div>
                <div class="card-body">
                    <?php echo nl2br(esc_entities($vendorRaw->notes)); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Meta -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-info me-2"></i>Record Info
                </div>
                <div class="card-body">
                    <small class="text-muted">
                        Created: <?php echo date('d M Y H:i', strtotime($vendorRaw->created_at)); ?><br>
                        Updated: <?php echo date('d M Y H:i', strtotime($vendorRaw->updated_at)); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Contact Modal -->
<div class="modal fade" id="addContactModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?php echo url_for(['module' => 'vendor', 'action' => 'addContact', 'slug' => $vendorRaw->slug]); ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Add Contact</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="contact_name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Position</label>
                            <input type="text" name="position" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="contact_phone" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mobile</label>
                            <input type="text" name="mobile" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="contact_email" class="form-control">
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_primary" value="1" class="form-check-input" id="isPrimary">
                        <label class="form-check-label" for="isPrimary">Primary Contact</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="contact_notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Contact</button>
                </div>
            </form>
        </div>
    </div>
</div>
