<?php use_helper('Date') ?>

<div class="container-fluid px-4 py-3">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
      <i class="bi bi-check2-circle me-2"></i>Consent Records
    </h1>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>Consent Records</span>
      <span class="badge bg-secondary"><?php echo count($records ?? []) ?> records</span>
    </div>
    <div class="card-body p-0">
      <?php if (empty($records)): ?>
        <div class="text-center text-muted py-5">
          <i class="bi bi-inbox display-4 d-block mb-3"></i>
          <p class="mb-0">No consent records found</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover table-striped mb-0">
            <thead class="table-light">
              <tr>
                <th>Data Subject</th>
                <th>Purpose</th>
                <th>Consent Given</th>
                <th>Consent Date</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($records as $record): ?>
                <tr>
                  <td><?php echo esc_entities($record->data_subject_id ?? '') ?></td>
                  <td><?php echo esc_entities($record->purpose ?? '') ?></td>
                  <td>
                    <?php if ($record->consent_given ?? false): ?>
                      <span class="badge bg-success">Yes</span>
                    <?php else: ?>
                      <span class="badge bg-danger">No</span>
                    <?php endif ?>
                  </td>
                  <td><?php echo $record->consent_date ?? '-' ?></td>
                  <td>
                    <span class="badge bg-<?php echo ($record->status ?? 'active') === 'active' ? 'success' : 'secondary' ?>">
                      <?php echo ucfirst($record->status ?? 'active') ?>
                    </span>
                  </td>
                  <td>
                    <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'consentView', 'id' => $record->id]) ?>" 
                       class="btn btn-sm btn-outline-primary" title="View">
                      <i class="bi bi-eye"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
      <?php endif ?>
    </div>
  </div>
</div>
