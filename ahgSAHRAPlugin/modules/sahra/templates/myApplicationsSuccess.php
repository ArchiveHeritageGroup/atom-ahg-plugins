<?php use_helper('Date'); ?>

<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="h3 mb-0"><i class="fas fa-landmark me-2"></i>My permit applications</h1>
      <p class="text-muted mb-0">SAHRA / NHRA heritage permits</p>
    </div>
    <a href="<?php echo url_for('@sahra_apply'); ?>" class="btn btn-primary"><i class="fas fa-plus me-1"></i> New application</a>
  </div>

  <?php if (empty($permits)): ?>
    <div class="card"><div class="card-body text-center text-muted py-5">
      <i class="fas fa-folder-open fa-3x mb-3"></i>
      <p>You have not submitted any permit applications yet.</p>
      <a href="<?php echo url_for('@sahra_apply'); ?>" class="btn btn-primary">Apply now</a>
    </div></div>
  <?php else: ?>
    <div class="card"><div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr><th>Ref</th><th>Project</th><th>Section</th><th>Supervisor</th><th>Status</th><th>Submitted</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($permits as $p): ?>
            <tr>
              <td><?php echo htmlspecialchars($p->application_ref); ?></td>
              <td><?php echo htmlspecialchars($p->project_title); ?></td>
              <td><small><?php echo htmlspecialchars(\AhgSAHRA\Services\SahraPermitService::SECTIONS[$p->nhra_section] ?? $p->nhra_section); ?></small></td>
              <td><?php echo htmlspecialchars($p->supervisor_username ?? $p->supervisor_name ?? '-'); ?></td>
              <td><?php echo include_partial('sahra/statusBadge', ['status' => $p->status]); ?></td>
              <td><small><?php echo $p->created_at ? date('M j, Y', strtotime($p->created_at)) : '-'; ?></small></td>
              <td><a href="<?php echo url_for(['module' => 'sahra', 'action' => 'permitView', 'id' => $p->id]); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div></div>
  <?php endif; ?>
</div>
