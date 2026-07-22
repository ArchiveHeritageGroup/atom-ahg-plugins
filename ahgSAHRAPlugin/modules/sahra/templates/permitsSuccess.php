<?php use_helper('Date'); ?>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><i class="fas fa-list me-2"></i>All permit applications</h1>
    <a href="<?php echo url_for('@sahra_index'); ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-th-large me-1"></i> Dashboard</a>
  </div>

  <ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link <?php echo !$currentStatus ? 'active' : ''; ?>" href="<?php echo url_for('@sahra_permits'); ?>">All</a></li>
    <?php foreach ($statusLabels as $key => $lbl): ?>
      <li class="nav-item"><a class="nav-link <?php echo $currentStatus === $key ? 'active' : ''; ?>" href="<?php echo url_for('@sahra_permits') . '?status=' . $key; ?>"><?php echo htmlspecialchars($lbl); ?></a></li>
    <?php endforeach; ?>
  </ul>

  <div class="card"><div class="card-body p-0">
    <?php if (empty($permits)): ?>
      <div class="p-4 text-center text-muted">No permits in this view.</div>
    <?php else: ?>
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr><th>Ref</th><th>SAHRA no.</th><th>Project</th><th>Applicant</th><th>Supervisor</th><th>Section</th><th>Status</th><th>Submitted</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($permits as $p): ?>
            <tr>
              <td><?php echo htmlspecialchars($p->application_ref); ?></td>
              <td><?php echo htmlspecialchars($p->sahra_permit_number ?? '-'); ?></td>
              <td><?php echo htmlspecialchars($p->project_title); ?></td>
              <td><?php echo htmlspecialchars($p->applicant_username ?? $p->applicant_name ?? '-'); ?></td>
              <td><?php echo htmlspecialchars($p->supervisor_username ?? $p->supervisor_name ?? '-'); ?></td>
              <td><small><?php echo htmlspecialchars(\AhgSAHRA\Services\SahraPermitService::SECTIONS[$p->nhra_section] ?? $p->nhra_section); ?></small></td>
              <td><?php echo include_partial('sahra/statusBadge', ['status' => $p->status]); ?></td>
              <td><small><?php echo $p->created_at ? date('M j, Y', strtotime($p->created_at)) : '-'; ?></small></td>
              <td><a href="<?php echo url_for(['module' => 'sahra', 'action' => 'permitView', 'id' => $p->id]); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div></div>
</div>
