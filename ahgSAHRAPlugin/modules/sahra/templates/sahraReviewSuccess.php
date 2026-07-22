<?php use_helper('Date'); ?>

<div class="container">
  <h1 class="h3 mb-1"><i class="fas fa-stamp me-2"></i>SAHRA review queue</h1>
  <p class="text-muted">Applications lodged with SAHRA and awaiting a decision. As a SAHRA reviewer you can issue or decline each permit here.</p>

  <?php if (empty($permits)): ?>
    <div class="card"><div class="card-body text-center text-muted py-5">
      <i class="fas fa-inbox fa-3x mb-3"></i>
      <p>No applications awaiting SAHRA's decision.</p>
    </div></div>
  <?php else: ?>
    <div class="card"><div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr><th>Ref</th><th>Project</th><th>Applicant / institution</th><th>Section</th><th>Site</th><th>Lodged</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($permits as $p): ?>
            <tr>
              <td><?php echo htmlspecialchars($p->application_ref); ?><?php echo $p->sahra_reference ? '<br><small class="text-muted">' . htmlspecialchars($p->sahra_reference) . '</small>' : ''; ?></td>
              <td><?php echo htmlspecialchars($p->project_title); ?></td>
              <td><?php echo htmlspecialchars($p->applicant_name ?? $p->applicant_username ?? '-'); ?><?php echo $p->institution ? '<br><small class="text-muted">' . htmlspecialchars($p->institution) . '</small>' : ''; ?></td>
              <td><small><?php echo htmlspecialchars(\AhgSAHRA\Services\SahraPermitService::SECTIONS[$p->nhra_section] ?? $p->nhra_section); ?></small></td>
              <td><small><?php echo htmlspecialchars($p->site_name ?? '-'); ?></small></td>
              <td><small><?php echo $p->sahra_submitted_date ? date('M j, Y', strtotime($p->sahra_submitted_date)) : '-'; ?></small></td>
              <td><a href="<?php echo url_for(['module' => 'sahra', 'action' => 'permitView', 'id' => $p->id]); ?>" class="btn btn-sm btn-success"><i class="fas fa-gavel me-1"></i> Decide</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div></div>
  <?php endif; ?>
</div>
