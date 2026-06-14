<?php use_helper('Date'); ?>

<?php
  $reports = [
      'closures'  => ['Closure Period Report',   'Closure periods and expiration dates.',       'fa-lock',         'primary'],
      'permits'   => ['Research Permits Report',  'Permits, fees and researcher statistics.',    'fa-id-card',      'success'],
      'transfers' => ['Transfers Report',         'Records transfers and accession statistics.', 'fa-truck',        'info'],
      'schedules' => ['Records Schedules Report',  'Retention/disposal schedules by agency.',     'fa-calendar-alt', 'warning'],
      'protected' => ['Protected Records Report',  'Protected records and protection status.',    'fa-shield-alt',   'danger'],
      'audit'     => ['Audit Log Report',         'Recent compliance actions across NAZ.',        'fa-history',      'secondary'],
  ];
  $skip = ['old_value', 'new_value', 'collections_access', 'ip_address', 'source_culture', 'serial_number'];
  $rawRows = isset($rows) ? $rows : [];
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'naz', 'action' => 'index']); ?>">NAZ</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'naz', 'action' => 'reports']); ?>">Reports</a></li>
                    <?php if ($reportType !== 'summary'): ?>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($reportTitle); ?></li>
                    <?php else: ?>
                        <li class="breadcrumb-item active">Overview</li>
                    <?php endif; ?>
                </ol>
            </nav>
            <h1><i class="fas fa-file-alt me-2"></i>NAZ Compliance Reports</h1>
        </div>
    </div>

    <?php if ($reportType === 'summary'): ?>
        <div class="row">
            <?php foreach ($reports as $key => $r): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas <?php echo $r[2]; ?> fa-3x text-<?php echo $r[3]; ?> mb-3"></i>
                            <h5><?php echo htmlspecialchars($r[0]); ?></h5>
                            <p class="text-muted small"><?php echo htmlspecialchars($r[1]); ?></p>
                            <a class="btn btn-outline-primary" href="<?php echo url_for(['module' => 'naz', 'action' => 'reports']); ?>?type=<?php echo $key; ?>">
                                <i class="fas fa-table me-1"></i> Generate
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0"><?php echo htmlspecialchars($reportTitle); ?> <span class="badge bg-secondary"><?php echo count($rawRows); ?></span></h4>
            <a href="<?php echo url_for(['module' => 'naz', 'action' => 'reports']); ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> All reports
            </a>
        </div>

        <?php if (empty($rawRows)): ?>
            <div class="alert alert-info">No records found for this report.</div>
        <?php else: ?>
            <?php $cols = array_values(array_filter(array_keys((array) $rawRows[0]), function ($k) use ($skip) { return !in_array($k, $skip, true); })); ?>
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead>
                        <tr><?php foreach ($cols as $c): ?><th><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $c))); ?></th><?php endforeach; ?></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rawRows as $row): ?>
                            <?php $row = (array) $row; ?>
                            <tr><?php foreach ($cols as $c): ?><td><?php echo htmlspecialchars((string) ($row[$c] ?? '')); ?></td><?php endforeach; ?></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
