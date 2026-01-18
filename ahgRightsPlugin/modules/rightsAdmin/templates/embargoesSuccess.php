<?php echo get_partial('header', ['title' => 'Embargo Management']); ?>

<div class="container-fluid">
  <div class="row">
    <?php include_partial('rightsAdmin/sidebar', ['active' => 'embargoes']); ?>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-clock me-2"></i>Embargo Management</h1>
        <div class="btn-toolbar">
          <a href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'embargoEdit']); ?>" 
             class="btn btn-sm btn-primary">
            <i class="fas fa-plus me-1"></i> New Embargo
          </a>
        </div>
      </div>

      <!-- Status Filter -->
      <div class="card mb-4">
        <div class="card-body py-2">
          <div class="btn-group" role="group">
            <a href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'embargoes', 'status' => 'active']); ?>" 
               class="btn btn-<?php echo 'active' === $status ? 'primary' : 'outline-primary'; ?>">Active</a>
            <a href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'embargoes', 'status' => 'lifted']); ?>" 
               class="btn btn-<?php echo 'lifted' === $status ? 'success' : 'outline-success'; ?>">Lifted</a>
            <a href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'embargoes', 'status' => 'expired']); ?>" 
               class="btn btn-<?php echo 'expired' === $status ? 'secondary' : 'outline-secondary'; ?>">Expired</a>
            <a href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'embargoes', 'status' => 'all']); ?>" 
               class="btn btn-<?php echo 'all' === $status ? 'dark' : 'outline-dark'; ?>">All</a>
          </div>
        </div>
      </div>

      <!-- Embargoes Table -->
      <div class="card">
        <div class="card-body p-0">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Object</th>
                <th>Type</th>
                <th>Reason</th>
                <th>Start</th>
                <th>End</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($embargoes as $embargo): ?>
              <tr>
                <td>
                  <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $embargo->slug]); ?>">
                    <?php echo esc_entities($embargo->object_title ?: 'ID: '.$embargo->object_id); ?>
                  </a>
                </td>
                <td>
                  <span class="badge bg-<?php 
                    echo match($embargo->embargo_type) {
                      'full' => 'danger',
                      'metadata_only' => 'warning',
                      'digital_only' => 'info',
                      'partial' => 'secondary',
                      default => 'light'
                    };
                  ?>"><?php echo esc_entities(ucfirst(str_replace('_', ' ', $embargo->embargo_type))); ?></span>
                </td>
                <td><?php echo esc_entities(ucfirst(str_replace('_', ' ', $embargo->reason))); ?></td>
                <td><?php echo date('d M Y', strtotime($embargo->start_date)); ?></td>
                <td>
                  <?php if ($embargo->end_date): ?>
                    <?php 
                    $endDate = strtotime($embargo->end_date);
                    $daysLeft = floor(($endDate - time()) / 86400);
                    ?>
                    <span class="<?php echo $daysLeft <= 30 ? 'text-danger fw-bold' : ''; ?>">
                      <?php echo date('d M Y', $endDate); ?>
                    </span>
                    <?php if ($daysLeft > 0 && $daysLeft <= 30): ?>
                      <br><small class="text-danger"><?php echo $daysLeft; ?> days left</small>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="text-muted">Indefinite</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge bg-<?php 
                    echo match($embargo->status) {
                      'active' => 'warning',
                      'lifted' => 'success',
                      'expired' => 'secondary',
                      'extended' => 'info',
                      default => 'light'
                    };
                  ?>"><?php echo esc_entities(ucfirst($embargo->status)); ?></span>
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <a href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'embargoEdit', 'id' => $embargo->id]); ?>" 
                       class="btn btn-outline-secondary" title="Edit">
                      <i class="fas fa-edit"></i>
                    </a>
                    <?php if ('active' === $embargo->status): ?>
                    <button type="button" class="btn btn-outline-success" title="Lift" 
                            data-bs-toggle="modal" data-bs-target="#liftModal<?php echo $embargo->id; ?>">
                      <i class="fas fa-unlock"></i>
                    </button>
                    <button type="button" class="btn btn-outline-warning" title="Extend"
                            data-bs-toggle="modal" data-bs-target="#extendModal<?php echo $embargo->id; ?>">
                      <i class="fas fa-calendar-plus"></i>
                    </button>
                    <?php endif; ?>
                  </div>

                  <!-- Lift Modal -->
                  <div class="modal fade" id="liftModal<?php echo $embargo->id; ?>" tabindex="-1">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <form action="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'embargoLift', 'id' => $embargo->id]); ?>" method="post">
                          <div class="modal-header">
                            <h5 class="modal-title">Lift Embargo</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                          </div>
                          <div class="modal-body">
                            <p>Are you sure you want to lift this embargo?</p>
                            <div class="mb-3">
                              <label class="form-label">Reason for lifting</label>
                              <textarea name="lift_reason" class="form-control" rows="3"></textarea>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">Lift Embargo</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>

                  <!-- Extend Modal -->
                  <div class="modal fade" id="extendModal<?php echo $embargo->id; ?>" tabindex="-1">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <form action="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'embargoExtend', 'id' => $embargo->id]); ?>" method="post">
                          <div class="modal-header">
                            <h5 class="modal-title">Extend Embargo</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                          </div>
                          <div class="modal-body">
                            <div class="mb-3">
                              <label class="form-label">New End Date</label>
                              <input type="date" name="new_end_date" class="form-control" required 
                                     min="<?php echo date('Y-m-d'); ?>"
                                     value="<?php echo $embargo->end_date ? date('Y-m-d', strtotime($embargo->end_date.' +1 year')) : date('Y-m-d', strtotime('+1 year')); ?>">
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Reason for extension</label>
                              <textarea name="extend_reason" class="form-control" rows="3"></textarea>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning">Extend Embargo</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (count($embargoes) === 0): ?>
              <tr>
                <td colspan="7" class="text-center text-muted py-4">No embargoes found.</td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </main>
  </div>
</div>
