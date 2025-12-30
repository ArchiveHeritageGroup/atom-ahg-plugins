<nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
  <div class="position-sticky pt-3">
    <h6 class="sidebar-heading px-3 mt-1 mb-2 text-muted">
      <i class="fas fa-gavel me-1"></i> Rights Management
    </h6>
    <ul class="nav flex-column">
      <li class="nav-item">
        <a class="nav-link <?php echo ('index' === $active) ? 'active' : ''; ?>" 
           href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'index']); ?>">
          <i class="fas fa-tachometer-alt me-2"></i> Dashboard
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo ('embargoes' === $active) ? 'active' : ''; ?>" 
           href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'embargoes']); ?>">
          <i class="fas fa-clock me-2"></i> Embargoes
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo ('orphanWorks' === $active) ? 'active' : ''; ?>" 
           href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'orphanWorks']); ?>">
          <i class="fas fa-search me-2"></i> Orphan Works
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo ('tkLabels' === $active) ? 'active' : ''; ?>" 
           href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'tkLabels']); ?>">
          <i class="fas fa-tags me-2"></i> TK Labels
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo ('statements' === $active) ? 'active' : ''; ?>" 
           href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'statements']); ?>">
          <i class="fas fa-balance-scale me-2"></i> Statements & Licenses
        </a>
      </li>
    </ul>

    <h6 class="sidebar-heading px-3 mt-4 mb-2 text-muted">Reports</h6>
    <ul class="nav flex-column mb-2">
      <li class="nav-item">
        <a class="nav-link" href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'report', 'type' => 'summary']); ?>">
          <i class="fas fa-chart-bar me-2"></i> Summary
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'report', 'type' => 'embargoes', 'export' => 1]); ?>">
          <i class="fas fa-download me-2"></i> Export Embargoes
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'report', 'type' => 'tk_labels', 'export' => 1]); ?>">
          <i class="fas fa-download me-2"></i> Export TK Labels
        </a>
      </li>
    </ul>

    <h6 class="sidebar-heading px-3 mt-4 mb-2 text-muted">Actions</h6>
    <ul class="nav flex-column mb-2">
      <li class="nav-item">
        <a class="nav-link" href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'processExpired']); ?>"
           onclick="return confirm('Process all expired embargoes?');">
          <i class="fas fa-sync me-2"></i> Process Expired
        </a>
      </li>
    </ul>
  </div>
</nav>
