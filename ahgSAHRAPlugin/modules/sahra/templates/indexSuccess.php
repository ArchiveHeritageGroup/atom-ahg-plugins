<?php use_helper('Date'); ?>
<?php $labels = \AhgSAHRA\Services\SahraPermitService::STATUS_LABELS; ?>

<div class="container-fluid">
  <div class="row mb-4">
    <div class="col">
      <h1><i class="fas fa-landmark me-2"></i>SAHRA Heritage Permits</h1>
      <p class="text-muted">National Heritage Resources Act, 1999 (Act 25 of 1999) - permit applications and lifecycle</p>
    </div>
    <div class="col-auto">
      <a href="<?php echo url_for('@sahra_queue'); ?>" class="btn btn-outline-primary"><i class="fas fa-inbox me-1"></i> SAHRA queue</a>
      <a href="<?php echo url_for('@sahra_permits'); ?>" class="btn btn-outline-secondary"><i class="fas fa-list me-1"></i> All permits</a>
      <a href="<?php echo url_for('@sahra_reports'); ?>" class="btn btn-outline-secondary"><i class="fas fa-file-alt me-1"></i> Reports</a>
      <a href="<?php echo url_for('@sahra_config'); ?>" class="btn btn-outline-secondary"><i class="fas fa-cog me-1"></i> Settings</a>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <?php
      $cards = [
          ['Awaiting endorsement', $stats['pending_supervisor'], 'warning', 'user-check', '@sahra_approvals'],
          ['Ready for SAHRA', $stats['ready_for_sahra'], 'info', 'paper-plane', '@sahra_queue'],
          ['With SAHRA', $stats['with_sahra'], 'primary', 'clock', '@sahra_review'],
          ['Active permits', $stats['active'], 'success', 'certificate', '@sahra_permits'],
          ['Expiring soon', $stats['expiring_soon'], 'danger', 'hourglass-half', '@sahra_permits'],
          ['Overdue reports', $stats['overdue_reports'], 'dark', 'file-alt', '@sahra_reports'],
      ];
      foreach ($cards as [$label, $val, $color, $icon, $route]):
    ?>
      <div class="col-6 col-md-4 col-xl-2">
        <a href="<?php echo url_for($route); ?>" class="text-decoration-none">
          <div class="card border-<?php echo $color; ?> h-100">
            <div class="card-body text-center">
              <i class="fas fa-<?php echo $icon; ?> fa-2x text-<?php echo $color; ?> mb-2"></i>
              <h3 class="mb-0"><?php echo (int) $val; ?></h3>
              <small class="text-muted"><?php echo $label; ?></small>
            </div>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="card-header bg-light"><strong>Recent applications</strong></div>
    <div class="card-body p-0">
      <?php if (empty($recent)): ?>
        <div class="p-4 text-center text-muted">No applications yet.</div>
      <?php else: ?>
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr><th>Ref</th><th>Project</th><th>Applicant</th><th>Section</th><th>Status</th><th>Submitted</th></tr>
          </thead>
          <tbody>
            <?php foreach ($recent as $p): ?>
              <tr>
                <td><a href="<?php echo url_for(['module' => 'sahra', 'action' => 'permitView', 'id' => $p->id]); ?>"><?php echo htmlspecialchars($p->application_ref); ?></a></td>
                <td><?php echo htmlspecialchars($p->project_title); ?></td>
                <td><?php echo htmlspecialchars($p->applicant_username ?? $p->applicant_name ?? '-'); ?></td>
                <td><small><?php echo htmlspecialchars(\AhgSAHRA\Services\SahraPermitService::SECTIONS[$p->nhra_section] ?? $p->nhra_section); ?></small></td>
                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($labels[$p->status] ?? $p->status); ?></span></td>
                <td><small><?php echo $p->created_at ? date('M j, Y', strtotime($p->created_at)) : '-'; ?></small></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>
