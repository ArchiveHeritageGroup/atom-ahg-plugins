<?php use_helper('I18N'); ?>
<div class="container-fluid mt-4">

  <a href="<?php echo url_for(['module' => 'reports', 'action' => 'index']); ?>#library" class="btn btn-outline-secondary btn-sm mb-3"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back to Library'); ?></a>

  <?php if (isset($message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?php echo esc_entities($message); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?php echo esc_entities($error); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="row">

    <!-- ── Left column: apply override ── -->
    <div class="col-md-5">
      <div class="card">
        <div class="card-header">
          <strong>FRBR Override</strong>
        </div>
        <div class="card-body">
          <!-- Search -->
          <form method="get" class="row g-2 mb-3">
            <div class="col-8">
              <input type="number" name="item_id" class="form-control"
                     placeholder="Item ID" value="<?php echo isset($itemId) ? (int)$itemId : ''; ?>">
            </div>
            <div class="col-4">
              <button type="submit" class="btn btn-outline-primary w-100">Look up</button>
            </div>
          </form>

          <?php if (isset($searchedItem) && $searchedItem): ?>
            <hr>
            <dl class="row mb-3">
              <dt class="col-sm-4">ID</dt>
              <dd class="col-sm-8"><?php echo $searchedItem->id; ?></dd>
              <dt class="col-sm-4">Title</dt>
              <dd class="col-sm-8"><?php echo esc_entities($searchedItem->title ?? '-'); ?></dd>
              <dt class="col-sm-4">ISBN</dt>
              <dd class="col-sm-8"><?php echo esc_entities($searchedItem->isbn ?? '-'); ?></dd>
              <dt class="col-sm-4">Work Key</dt>
              <dd class="col-sm-8">
                <code><?php echo esc_entities($searchedItem->frbr_work_key ?? 'null'); ?></code>
              </dd>
              <dt class="col-sm-4">Override Type</dt>
              <dd class="col-sm-8">
                <?php
                $type = $searchedItem->frbr_override_type ?? 'none';
                $badgeClass = match($type) {
                    'force_group' => 'bg-primary',
                    'force_split' => 'bg-warning text-dark',
                    default       => 'bg-secondary',
                };
                ?>
                <span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst(str_replace('_',' ',$type)); ?></span>
              </dd>
            </dl>

            <!-- Force Group -->
            <form method="post" class="mb-2">
              <input type="hidden" name="action" value="force_group">
              <input type="hidden" name="library_item_id" value="<?php echo $searchedItem->id; ?>">
              <div class="row g-2 mb-2">
                <div class="col-12">
                  <label class="form-label small">Target Work Key (force grouping into)</label>
                  <input type="text" name="target_work_key" class="form-control form-control-sm"
                         placeholder="paste work key or leave blank (auto-generates)">
                </div>
                <div class="col-12">
                  <label class="form-label small">Reason</label>
                  <input type="text" name="reason" class="form-control form-control-sm" placeholder="e.g. Reprint edition">
                </div>
              </div>
              <button type="submit" class="btn btn-primary btn-sm w-100">Force Group</button>
            </form>

            <!-- Force Split -->
            <form method="post" class="mb-2">
              <input type="hidden" name="action" value="force_split">
              <input type="hidden" name="library_item_id" value="<?php echo $searchedItem->id; ?>">
              <div class="row g-2 mb-2">
                <div class="col-12">
                  <label class="form-label small">Reason</label>
                  <input type="text" name="reason" class="form-control form-control-sm"
                         placeholder="e.g. Different work, not a reprint">
                </div>
              </div>
              <button type="submit" class="btn btn-warning btn-sm w-100">Force Split</button>
            </form>

            <?php if ($type !== 'none'): ?>
              <form method="post">
                <input type="hidden" name="action" value="clear">
                <input type="hidden" name="library_item_id" value="<?php echo $searchedItem->id; ?>">
                <button type="submit" class="btn btn-outline-secondary btn-sm w-100">Clear Override</button>
              </form>
            <?php endif; ?>

          <?php else: ?>
            <p class="text-muted small">Enter an item ID above to look it up and apply an override.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Work-key stats -->
      <div class="card mt-3">
        <div class="card-header"><strong>Work Key Coverage</strong></div>
        <div class="card-body">
          <?php if (isset($workKeyStats)): ?>
            <table class="table table-sm">
              <tr><td>Total items</td>      <td class="text-end"><?php echo number_format($workKeyStats['total']); ?></td></tr>
              <tr><td>Keyed</td>            <td class="text-end"><?php echo number_format($workKeyStats['keyed']); ?></td></tr>
              <tr><td>Unkeyed (no title)</td><td class="text-end"><?php echo number_format($workKeyStats['unkeyed']); ?></td></tr>
              <tr><td>Force-grouped</td>   <td class="text-end"><?php echo number_format($workKeyStats['grouped']); ?></td></tr>
              <tr><td>Force-split</td>      <td class="text-end"><?php echo number_format($workKeyStats['split']); ?></td></tr>
            </table>
            <?php $pct = $workKeyStats['total'] > 0
                ? round($workKeyStats['keyed'] / $workKeyStats['total'] * 100, 1)
                : 0; ?>
            <div class="progress" style="height: 6px;">
              <div class="progress-bar bg-success" role="progressbar"
                   style="width: <?php echo $pct; ?>%;" aria-valuenow="<?php echo $pct; ?>">
              </div>
            </div>
            <small class="text-muted"><?php echo $pct; ?>% keyed</small>
          <?php endif; ?>
        </div>
      </div>
    </div><!-- /col-md-5 -->

    <!-- ── Right column: override list ── -->
    <div class="col-md-7">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong>Active Overrides</strong>
          <span class="badge bg-dark"><?php echo isset($overrides) ? count($overrides) : 0; ?></span>
        </div>
        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
          <table class="table table-sm table-hover mb-0">
            <thead class="table-light sticky-top">
              <tr>
                <th>Item ID</th>
                <th>Title</th>
                <th>Type</th>
                <th>Target Key</th>
                <th>Reason</th>
                <th>Date</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($overrides)): ?>
                <?php foreach ($overrides as $row): ?>
                  <tr>
                    <td><a href="?item_id=<?php echo $row->library_item_id; ?>">
                        <?php echo $row->library_item_id; ?></a></td>
                    <td style="max-width: 150px;" class="text-truncate"
                        title="<?php echo esc_entities($row->title ?? ''); ?>">
                        <?php echo esc_entities($row->title ?? '-'); ?></td>
                    <td>
                      <?php
                      $t = $row->forced_split ? 'force_split' : 'force_group';
                      $bc = $t === 'force_split' ? 'bg-warning text-dark' : 'bg-primary';
                      ?>
                      <span class="badge <?php echo $bc; ?> small"><?php echo $t; ?></span>
                    </td>
                    <td style="max-width: 100px;" class="text-truncate">
                      <code class="small"><?php echo esc_entities($row->target_work_key ?? '-'); ?></code></td>
                    <td style="max-width: 120px;" class="text-truncate text-muted small"
                        title="<?php echo esc_entities($row->reason ?? ''); ?>">
                        <?php echo esc_entities($row->reason ?? '-'); ?></td>
                    <td class="small text-muted"><?php echo substr($row->created_at, 0, 10); ?></td>
                    <td>
                      <form method="post" class="d-inline">
                        <input type="hidden" name="action" value="clear">
                        <input type="hidden" name="library_item_id" value="<?php echo $row->library_item_id; ?>">
                        <button type="submit" class="btn btn-xs btn-outline-danger btn-sm"
                                onclick="return confirm('Clear this override?');">Clear</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="7" class="text-center text-muted p-3">No overrides active.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div><!-- /col-md-7 -->

  </div><!-- /row -->
</div>