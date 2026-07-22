<?php use_helper('Date'); ?>

<div class="container">
  <h1 class="h3 mb-3"><i class="fas fa-file-alt me-2"></i>Overdue SAHRA reports</h1>
  <p class="text-muted">Permit reporting obligations past their due date.</p>

  <?php if (empty($overdue)): ?>
    <div class="card"><div class="card-body text-center text-muted py-5">
      <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
      <p>No overdue reports.</p>
    </div></div>
  <?php else: ?>
    <div class="card"><div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Permit</th><th>Project</th><th>Report type</th><th>Due</th><th>Days late</th></tr></thead>
        <tbody>
          <?php foreach ($overdue as $r): ?>
            <tr class="table-danger">
              <td><?php echo htmlspecialchars($r->application_ref); ?></td>
              <td><?php echo htmlspecialchars($r->project_title); ?></td>
              <td><?php echo ucfirst(htmlspecialchars($r->report_type)); ?></td>
              <td><?php echo $r->due_date ? date('j M Y', strtotime($r->due_date)) : '-'; ?></td>
              <td><?php echo $r->due_date ? max(0, (int) floor((time() - strtotime($r->due_date)) / 86400)) : '-'; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div></div>
  <?php endif; ?>
</div>
