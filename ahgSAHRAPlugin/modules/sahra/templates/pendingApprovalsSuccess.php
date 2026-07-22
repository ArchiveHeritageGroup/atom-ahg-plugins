<?php use_helper('Date'); ?>

<div class="container">
  <h1 class="h3 mb-1"><i class="fas fa-user-check me-2"></i>Applications awaiting my endorsement</h1>
  <p class="text-muted">As a supervising professor, review and endorse (or return) these SAHRA permit applications.</p>

  <?php if (empty($permits)): ?>
    <div class="card"><div class="card-body text-center text-muted py-5">
      <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
      <p>Nothing awaiting your endorsement.</p>
    </div></div>
  <?php else: ?>
    <div class="card"><div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr><th>Ref</th><th>Project</th><th>Researcher</th><th>Section</th><th>Site</th><th>Submitted</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($permits as $p): ?>
            <tr>
              <td><?php echo htmlspecialchars($p->application_ref); ?></td>
              <td><?php echo htmlspecialchars($p->project_title); ?></td>
              <td><?php echo htmlspecialchars($p->applicant_username ?? $p->applicant_name ?? '-'); ?></td>
              <td><small><?php echo htmlspecialchars(\AhgSAHRA\Services\SahraPermitService::SECTIONS[$p->nhra_section] ?? $p->nhra_section); ?></small></td>
              <td><small><?php echo htmlspecialchars($p->site_name ?? '-'); ?></small></td>
              <td><small><?php echo $p->created_at ? date('M j, Y', strtotime($p->created_at)) : '-'; ?></small></td>
              <td><a href="<?php echo url_for(['module' => 'sahra', 'action' => 'permitView', 'id' => $p->id]); ?>" class="btn btn-sm btn-primary"><i class="fas fa-gavel me-1"></i> Review</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div></div>
  <?php endif; ?>
</div>
