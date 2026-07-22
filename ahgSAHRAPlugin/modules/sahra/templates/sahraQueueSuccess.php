<?php use_helper('Date'); ?>

<div class="container">
  <h1 class="h3 mb-1"><i class="fas fa-paper-plane me-2"></i>Ready to lodge with SAHRA</h1>
  <p class="text-muted">Applications endorsed by the supervisor and ready to be submitted to SAHRA / the provincial authority.</p>

  <?php if (empty($permits)): ?>
    <div class="card"><div class="card-body text-center text-muted py-5">
      <i class="fas fa-inbox fa-3x mb-3"></i>
      <p>Nothing waiting to be lodged with SAHRA.</p>
    </div></div>
  <?php else: ?>
    <div class="card"><div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr><th>Ref</th><th>Project</th><th>Applicant</th><th>Authority</th><th>Section</th><th>Endorsed</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($permits as $p): ?>
            <tr>
              <td><?php echo htmlspecialchars($p->application_ref); ?></td>
              <td><?php echo htmlspecialchars($p->project_title); ?></td>
              <td><?php echo htmlspecialchars($p->applicant_username ?? $p->applicant_name ?? '-'); ?></td>
              <td><small><?php echo htmlspecialchars($p->issuing_authority); ?></small></td>
              <td><small><?php echo htmlspecialchars(\AhgSAHRA\Services\SahraPermitService::SECTIONS[$p->nhra_section] ?? $p->nhra_section); ?></small></td>
              <td><small><?php echo $p->supervisor_decision_date ? date('M j, Y', strtotime($p->supervisor_decision_date)) : '-'; ?></small></td>
              <td><a href="<?php echo url_for(['module' => 'sahra', 'action' => 'permitView', 'id' => $p->id]); ?>" class="btn btn-sm btn-primary"><i class="fas fa-paper-plane me-1"></i> Lodge</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div></div>
  <?php endif; ?>
</div>
