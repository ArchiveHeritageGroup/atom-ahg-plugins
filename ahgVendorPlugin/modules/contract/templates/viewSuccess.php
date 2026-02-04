<?php use_helper('Date') ?>

<div class="container-xxl py-4">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-start mb-4">
    <div>
      <?php if (!empty($contract->logo_path)): ?>
      <img src="/uploads<?php echo esc_entities($contract->logo_path) ?>" alt="Logo" class="mb-3" style="max-height: 80px;">
      <?php endif; ?>
      <h1 class="h2 mb-1"><?php echo esc_entities($contract->title) ?></h1>
      <p class="text-muted mb-0">
        <span class="badge" style="background-color: <?php echo $contract->type_color ?>"><?php echo esc_entities($contract->contract_type_name) ?></span>
        <span class="ms-2"><?php echo esc_entities($contract->contract_number) ?></span>
      </p>
    </div>
    <div class="btn-group">
      <a href="<?php echo url_for(['module' => 'contract', 'action' => 'edit', 'id' => $contract->id]) ?>" class="btn btn-primary">
        <i class="fas fa-edit me-1"></i> <?php echo __('Edit') ?>
      </a>
      <a href="<?php echo url_for(['module' => 'contract', 'action' => 'browse']) ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back') ?>
      </a>
    </div>
  </div>

  <div class="row">
    <!-- Main Content -->
    <div class="col-lg-8">
      <!-- Status & Dates -->
      <div class="card mb-4">
        <div class="card-body">
          <div class="row text-center">
            <div class="col-md-3">
              <small class="text-muted d-block"><?php echo __('Status') ?></small>
              <?php
              $statusColors = [
                'draft' => 'secondary',
                'pending_review' => 'warning',
                'pending_signature' => 'info',
                'active' => 'success',
                'suspended' => 'warning',
                'expired' => 'danger',
                'terminated' => 'dark',
                'renewed' => 'primary'
              ];
              $color = $statusColors[$contract->status] ?? 'secondary';
              ?>
              <span class="badge bg-<?php echo $color ?> fs-6"><?php echo __(ucwords(str_replace('_', ' ', $contract->status))) ?></span>
            </div>
            <div class="col-md-3">
              <small class="text-muted d-block"><?php echo __('Effective') ?></small>
              <strong><?php echo $contract->effective_date ? date('d M Y', strtotime($contract->effective_date)) : '-' ?></strong>
            </div>
            <div class="col-md-3">
              <small class="text-muted d-block"><?php echo __('Expiry') ?></small>
              <?php if ($contract->expiry_date): ?>
                <?php
                $expiry = strtotime($contract->expiry_date);
                $daysUntil = (int)(($expiry - time()) / 86400);
                $expiryClass = $daysUntil < 0 ? 'text-danger' : ($daysUntil < 30 ? 'text-warning' : 'text-success');
                ?>
                <strong class="<?php echo $expiryClass ?>"><?php echo date('d M Y', $expiry) ?></strong>
                <?php if ($daysUntil > 0): ?>
                <small class="d-block text-muted"><?php echo sprintf(__('%d days remaining'), $daysUntil) ?></small>
                <?php elseif ($daysUntil < 0): ?>
                <small class="d-block text-danger"><?php echo sprintf(__('Expired %d days ago'), abs($daysUntil)) ?></small>
                <?php endif; ?>
              <?php else: ?>
                <strong>-</strong>
              <?php endif; ?>
            </div>
            <div class="col-md-3">
              <small class="text-muted d-block"><?php echo __('Risk Level') ?></small>
              <?php
              $riskColors = ['low' => 'success', 'medium' => 'warning', 'high' => 'orange', 'critical' => 'danger'];
              $riskColor = $riskColors[$contract->risk_level] ?? 'secondary';
              ?>
              <span class="badge bg-<?php echo $riskColor ?>"><?php echo __(ucfirst($contract->risk_level ?? 'low')) ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Counterparty -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0"><?php echo __('Counterparty') ?></h5>
        </div>
        <div class="card-body">
          <h5><?php echo esc_entities($contract->counterparty_name) ?></h5>
          <?php if ($contract->counterparty_type): ?>
          <span class="badge bg-secondary"><?php echo __(ucfirst($contract->counterparty_type)) ?></span>
          <?php endif; ?>
          <?php if ($contract->vendor_name): ?>
          <p class="mt-2 mb-1">
            <i class="fas fa-link text-muted me-1"></i>
            <a href="<?php echo url_for(['module' => 'vendor', 'action' => 'view', 'id' => $contract->vendor_id]) ?>">
              <?php echo esc_entities($contract->vendor_name) ?>
            </a>
          </p>
          <?php endif; ?>
          <?php if ($contract->counterparty_contact): ?>
          <p class="mt-2 mb-1 text-muted"><?php echo nl2br(esc_entities($contract->counterparty_contact)) ?></p>
          <?php endif; ?>
          <?php if ($contract->counterparty_representative): ?>
          <p class="mb-0">
            <strong><?php echo __('Representative:') ?></strong>
            <?php echo esc_entities($contract->counterparty_representative) ?>
            <?php if ($contract->counterparty_representative_title): ?>
            <span class="text-muted">(<?php echo esc_entities($contract->counterparty_representative_title) ?>)</span>
            <?php endif; ?>
          </p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Scope & Terms -->
      <?php if ($contract->scope_of_work || $contract->deliverables || $contract->general_terms || $contract->special_conditions): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0"><?php echo __('Terms & Conditions') ?></h5>
        </div>
        <div class="card-body">
          <?php if ($contract->scope_of_work): ?>
          <h6><?php echo __('Scope of Work') ?></h6>
          <p><?php echo nl2br(esc_entities($contract->scope_of_work)) ?></p>
          <?php endif; ?>

          <?php if ($contract->deliverables): ?>
          <h6><?php echo __('Deliverables') ?></h6>
          <p><?php echo nl2br(esc_entities($contract->deliverables)) ?></p>
          <?php endif; ?>

          <?php if ($contract->general_terms): ?>
          <h6><?php echo __('General Terms') ?></h6>
          <p><?php echo nl2br(esc_entities($contract->general_terms)) ?></p>
          <?php endif; ?>

          <?php if ($contract->special_conditions): ?>
          <h6><?php echo __('Special Conditions') ?></h6>
          <p><?php echo nl2br(esc_entities($contract->special_conditions)) ?></p>
          <?php endif; ?>

          <?php if ($contract->ip_terms): ?>
          <h6><?php echo __('Intellectual Property') ?></h6>
          <p><?php echo nl2br(esc_entities($contract->ip_terms)) ?></p>
          <?php endif; ?>

          <?php if ($contract->confidentiality_terms): ?>
          <h6><?php echo __('Confidentiality') ?></h6>
          <p><?php echo nl2br(esc_entities($contract->confidentiality_terms)) ?></p>
          <?php endif; ?>

          <?php if ($contract->governing_law): ?>
          <p class="mb-0"><strong><?php echo __('Governing Law:') ?></strong> <?php echo esc_entities($contract->governing_law) ?></p>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Description -->
      <?php if ($contract->description): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0"><?php echo __('Description') ?></h5>
        </div>
        <div class="card-body">
          <?php echo nl2br(esc_entities($contract->description)) ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Documents -->
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0"><?php echo __('Documents') ?></h5>
        </div>
        <div class="card-body">
          <?php if (empty($documents)): ?>
          <p class="text-muted mb-0"><?php echo __('No documents uploaded') ?></p>
          <?php else: ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($documents as $doc): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <div>
                <i class="fas fa-file-pdf text-danger me-2"></i>
                <?php echo esc_entities($doc->title ?: $doc->original_filename) ?>
                <span class="badge bg-secondary ms-2"><?php echo __(ucwords(str_replace('_', ' ', $doc->document_type))) ?></span>
                <?php if ($doc->is_signed): ?>
                <span class="badge bg-success ms-1"><?php echo __('Signed') ?></span>
                <?php endif; ?>
              </div>
              <a href="/uploads<?php echo esc_entities($doc->file_path) ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                <i class="fas fa-download"></i>
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
      <!-- Financial -->
      <?php if ($contract->has_financial_terms): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0"><?php echo __('Financial') ?></h5>
        </div>
        <div class="card-body">
          <?php if ($contract->contract_value): ?>
          <p class="h4 mb-1"><?php echo $contract->currency ?> <?php echo number_format($contract->contract_value, 2) ?></p>
          <?php endif; ?>
          <?php if ($contract->payment_terms): ?>
          <p class="text-muted mb-0"><?php echo esc_entities($contract->payment_terms) ?></p>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Reminders -->
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0"><?php echo __('Reminders') ?></h5>
        </div>
        <div class="card-body">
          <?php if (empty($reminders)): ?>
          <p class="text-muted mb-0"><?php echo __('No reminders set') ?></p>
          <?php else: ?>
          <?php foreach ($reminders as $reminder): ?>
          <div class="d-flex mb-2">
            <?php
            $isOverdue = strtotime($reminder->reminder_date) < time() && $reminder->status === 'active';
            $priorityColors = ['low' => 'secondary', 'normal' => 'primary', 'high' => 'warning', 'urgent' => 'danger'];
            ?>
            <div class="flex-shrink-0">
              <span class="badge bg-<?php echo $priorityColors[$reminder->priority] ?? 'secondary' ?>"><?php echo date('d M', strtotime($reminder->reminder_date)) ?></span>
            </div>
            <div class="ms-2 <?php echo $isOverdue ? 'text-danger' : '' ?>">
              <?php echo esc_entities($reminder->subject) ?>
            </div>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Our Organization -->
      <?php if ($contract->our_representative): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0"><?php echo __('Our Representative') ?></h5>
        </div>
        <div class="card-body">
          <p class="mb-0">
            <?php echo esc_entities($contract->our_representative) ?>
            <?php if ($contract->our_representative_title): ?>
            <br><small class="text-muted"><?php echo esc_entities($contract->our_representative_title) ?></small>
            <?php endif; ?>
          </p>
        </div>
      </div>
      <?php endif; ?>

      <!-- Internal Notes -->
      <?php if ($contract->internal_notes): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0"><?php echo __('Internal Notes') ?></h5>
        </div>
        <div class="card-body">
          <p class="mb-0"><?php echo nl2br(esc_entities($contract->internal_notes)) ?></p>
        </div>
      </div>
      <?php endif; ?>

      <!-- Metadata -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0"><?php echo __('Metadata') ?></h5>
        </div>
        <div class="card-body">
          <table class="table table-sm mb-0">
            <tr>
              <td class="text-muted"><?php echo __('Created') ?></td>
              <td><?php echo $contract->created_at ? date('d M Y H:i', strtotime($contract->created_at)) : '-' ?></td>
            </tr>
            <tr>
              <td class="text-muted"><?php echo __('Updated') ?></td>
              <td><?php echo $contract->updated_at ? date('d M Y H:i', strtotime($contract->updated_at)) : '-' ?></td>
            </tr>
            <?php if ($contract->auto_renew): ?>
            <tr>
              <td class="text-muted"><?php echo __('Auto-renew') ?></td>
              <td><span class="badge bg-info"><?php echo __('Yes') ?></span></td>
            </tr>
            <?php endif; ?>
            <?php if ($contract->is_template): ?>
            <tr>
              <td class="text-muted"><?php echo __('Template') ?></td>
              <td><span class="badge bg-secondary"><?php echo __('Yes') ?></span></td>
            </tr>
            <?php endif; ?>
          </table>
        </div>
      </div>

      <!-- Actions -->
      <div class="card">
        <div class="card-body">
          <div class="d-grid gap-2">
            <a href="<?php echo url_for(['module' => 'contract', 'action' => 'edit', 'id' => $contract->id]) ?>" class="btn btn-primary">
              <i class="fas fa-edit me-1"></i> <?php echo __('Edit Contract') ?>
            </a>
            <form method="post" action="<?php echo url_for(['module' => 'contract', 'action' => 'delete', 'id' => $contract->id]) ?>" onsubmit="return confirm('<?php echo __('Are you sure you want to delete this contract?') ?>');">
              <button type="submit" class="btn btn-outline-danger w-100">
                <i class="fas fa-trash me-1"></i> <?php echo __('Delete') ?>
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
