<?php /* Spectrum Phase C1 — collection-wide compliance dashboard (PSIS port) */ ?>
<?php $n = sfConfig::get('csp_nonce', ''); $nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>
<style <?php echo $nonceAttr; ?>>
  .spectrum-heatmap-row:hover { background: #f8f9fa; }
  .status-cell { text-align: center; min-width: 80px; font-variant-numeric: tabular-nums; }
  .status-cell .badge { width: 100%; }
  .swatch-not_started { background: #e9ecef; color: #495057; }
  .swatch-in_progress { background: #cfe2ff; color: #084298; }
  .swatch-completed   { background: #d1e7dd; color: #0f5132; }
  .swatch-overdue     { background: #f8d7da; color: #842029; font-weight: 600; }
  .swatch-rejected    { background: #fff3cd; color: #664d03; }
  .progress { height: 8px; }
</style>

<div class="container-fluid px-4 py-3 spectrum compliance-dashboard">
  <div class="d-flex flex-wrap align-items-baseline mb-3 gap-2">
    <h1 class="mb-0 flex-grow-1"><i class="fas fa-university me-2"></i><?php echo __('Spectrum compliance dashboard') ?></h1>
    <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'spectrumExportCsv', 'overdue_days' => $overdueDays]) ?>" class="btn btn-outline-success">
      <i class="fas fa-file-csv me-1"></i><?php echo __('Export CSV') ?>
    </a>
    <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'spectrumChain']) ?>" class="btn btn-outline-primary">
      <i class="fas fa-link me-1"></i><?php echo __('Chain rules') ?>
    </a>
    <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'admin']) ?>" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('Workflows') ?>
    </a>
  </div>

  <form method="get" action="<?php echo url_for(['module' => 'workflow', 'action' => 'spectrumDashboard']) ?>" class="d-flex gap-2 align-items-end mb-3">
    <div style="max-width: 14rem;">
      <label for="overdue_days" class="form-label small mb-1"><?php echo __('Overdue threshold (days)') ?></label>
      <input type="number" name="overdue_days" id="overdue_days" class="form-control form-control-sm" min="1" max="3650" value="<?php echo (int) $overdueDays ?>">
    </div>
    <button type="submit" class="btn btn-sm btn-outline-primary"><?php echo __('Apply') ?></button>
  </form>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th><?php echo __('Spectrum procedure') ?></th>
              <th class="status-cell"><?php echo __('Not started') ?></th>
              <th class="status-cell"><?php echo __('In progress') ?></th>
              <th class="status-cell"><?php echo __('Completed') ?></th>
              <th class="status-cell"><?php echo __('Overdue') ?></th>
              <th class="status-cell"><?php echo __('Rejected') ?></th>
              <th style="min-width: 150px;"><?php echo __('Completion') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($heatmap as $code => $row): ?>
              <tr class="spectrum-heatmap-row">
                <td><strong><?php echo esc_entities($row['label']) ?></strong><br><small class="text-muted"><?php echo esc_entities($code) ?></small></td>
                <td class="status-cell"><span class="badge swatch-not_started"><?php echo $row['totals']['not_started'] ?></span></td>
                <td class="status-cell"><span class="badge swatch-in_progress"><?php echo $row['totals']['in_progress'] ?></span></td>
                <td class="status-cell"><span class="badge swatch-completed"><?php echo $row['totals']['completed'] ?></span></td>
                <td class="status-cell"><span class="badge swatch-overdue"><?php echo $row['totals']['overdue'] ?></span></td>
                <td class="status-cell"><span class="badge swatch-rejected"><?php echo $row['totals']['rejected'] ?></span></td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1">
                      <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $row['percent_completed'] ?>%" aria-valuenow="<?php echo $row['percent_completed'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <small class="text-muted" style="min-width: 3rem; text-align: right;"><?php echo $row['percent_completed'] ?>%</small>
                  </div>
                </td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
