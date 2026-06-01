<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Library Reports'); ?></h4>
    <ul class="list-unstyled">
        <li><a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'catalogue']); ?>"><i class="fas fa-book me-2"></i><?php echo __('Catalogue'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'creators']); ?>"><i class="fas fa-user-edit me-2"></i><?php echo __('Creators'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'subjects']); ?>"><i class="fas fa-tags me-2"></i><?php echo __('Subjects'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'index']); ?>"><i class="fas fa-chart-bar me-2"></i><?php echo __('Dashboard'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'counter']); ?>" class="text-primary fw-bold"><i class="fas fa-table me-2"></i><?php echo __('COUNTER Reports'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'sushiSettings']); ?>"><i class="fas fa-cloud me-2"></i><?php echo __('SUSHI Settings'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'frbrOverride']); ?>"><i class="fas fa-layer-group me-2"></i><?php echo __('FRBR Overrides'); ?></a></li>
    </ul>
    <hr>
    <a href="<?php echo url_for(['module' => 'reports', 'action' => 'index']); ?>#library" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back to Library'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-table"></i> <?php echo __('COUNTER R5 Reports'); ?></h1>
<p class="text-muted mb-0"><?php echo __('Generate and download SUSHI-compliant usage reports.'); ?></p>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="row">

    <!-- ── Report Builder ─────────────────────────────────────────── -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-cogs me-2"></i><?php echo __('Generate Report'); ?></h5>
            </div>
            <div class="card-body">
                <form method="get" action="<?php echo url_for(['module' => 'libraryReports', 'action' => 'counter']); ?>">

                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Report Type'); ?></label>
                        <select name="report_type" class="form-select">
                            <?php foreach ($reportTypes as $code => $label): ?>
                            <option value="<?php echo $code; ?>" <?php echo $selectedReport === $code ? 'selected' : ''; ?>>
                                <?php echo $code; ?> — <?php echo $label; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label small"><?php echo __('From'); ?></label>
                            <input type="date" name="begin_date" class="form-control form-control-sm"
                                   value="<?php echo $beginDate; ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label small"><?php echo __('To'); ?></label>
                            <input type="date" name="end_date" class="form-control form-control-sm"
                                   value="<?php echo $endDate; ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Format'); ?></label>
                        <div class="btn-group w-100" role="group">
                            <?php foreach ($formats as $code => $label): ?>
                            <input type="radio" class="btn-check" name="format" id="fmt_<?php echo $code; ?>"
                                   value="<?php echo $code; ?>" <?php echo $selectedFormat === $code ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-primary btn-sm" for="fmt_<?php echo $code; ?>">
                                <?php echo $label; ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small"><?php echo __('Quick Ranges'); ?></label>
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="?report_type=<?php echo $selectedReport; ?>&begin_date=<?php echo date('Y-01-01'); ?>&end_date=<?php echo date('Y-m-d'); ?>&format=<?php echo $selectedFormat; ?>" class="btn btn-xs btn-outline-secondary">YTD</a>
                            <a href="?report_type=<?php echo $selectedReport; ?>&begin_date=<?php echo date('Y-m-d', strtotime('-30 days')); ?>&end_date=<?php echo date('Y-m-d'); ?>&format=<?php echo $selectedFormat; ?>" class="btn btn-xs btn-outline-secondary">30d</a>
                            <a href="?report_type=<?php echo $selectedReport; ?>&begin_date=<?php echo date('Y-m-01'); ?>&end_date=<?php echo date('Y-m-d'); ?>&format=<?php echo $selectedFormat; ?>" class="btn btn-xs btn-outline-secondary">MTD</a>
                            <a href="?report_type=<?php echo $selectedReport; ?>&begin_date=<?php echo date('Y-01-01', strtotime('-1 year')); ?>&end_date=<?php echo date('Y-12-31', strtotime('-1 year')); ?>&format=<?php echo $selectedFormat; ?>" class="btn btn-xs btn-outline-secondary">PY</a>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="download" value="1" class="btn btn-success">
                            <i class="fas fa-download me-1"></i><?php echo __('Download'); ?>
                        </button>
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-eye me-1"></i><?php echo __('Preview'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Event Preview -->
        <div class="card mt-3">
            <div class="card-header">
                <strong><?php echo __('Usage Summary'); ?></strong>
            </div>
            <div class="card-body text-center">
                <h2 class="mb-1"><?php echo number_format($previewTotal); ?></h2>
                <small class="text-muted"><?php echo __('usage events in range'); ?></small>
            </div>
        </div>

        <!-- Last 30-day sparkline (CSS bar chart from events table) -->
        <div class="card mt-3">
            <div class="card-header">
                <strong><?php echo __('Last 30 Days Activity'); ?></strong>
            </div>
            <div class="card-body">
                <?php
                // Build a simple CSS bar chart from daily event counts
                // Fetched in the action and passed as $sparklineData
                $sparklineData = sfOutputEscaper::unescape($sparklineData ?? []);
                if (!empty($sparklineData)):
                    $maxVal = max(array_column($sparklineData, 'count') ?: [1]);
                    if ($maxVal <= 0) $maxVal = 1;
                ?>
                <div class="d-flex align-items-end gap-1" style="height: 60px;">
                    <?php foreach ($sparklineData as $day): ?>
                        <?php
                        $heightPct = round(($day['count'] / $maxVal) * 100);
                        $date = date('j', strtotime($day['date']));
                        $isToday = $day['date'] === date('Y-m-d');
                        ?>
                        <div class="flex-grow-1 text-center" title="<?php echo $day['date']; ?>: <?php echo $day['count']; ?> events">
                            <div style="height:<?php echo max(2, $heightPct); ?>px; background: <?php echo $isToday ? '#0d6efd' : '#adb5bd'; ?>; border-radius: 2px 2px 0 0;"></div>
                            <?php if ($date % 5 === 0 || $isToday): ?>
                            <small class="d-block text-muted" style="font-size:9px;"><?php echo $date; ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted small mb-0">No events recorded yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div><!-- /col-md-4 -->

    <!-- ── Report Info ───────────────────────────────────────────── -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo $reportTypes[$selectedReport] ?? $selectedReport; ?></h5>
                <span class="badge bg-dark"><?php echo $selectedReport; ?></span>
            </div>
            <div class="card-body">
                <?php if (!empty($reportDescription)): ?>
                <p class="text-muted small mb-3"><?php echo $reportDescription; ?></p>
                <?php endif; ?>

                <?php $reportData = sfOutputEscaper::unescape($reportData ?? []); ?>
                <?php if (!empty($reportData) && is_array($reportData)): ?>
                <!-- Data Table -->
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-sm table-hover">
                        <thead class="table-light sticky-top">
                            <tr>
                                <?php
                                $cols = array_keys((array) $reportData[0]);
                                foreach (array_slice($cols, 0, 8) as $col): ?>
                                <th class="small"><?php echo htmlspecialchars(str_replace('_', ' ', $col)); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportData as $row): ?>
                            <?php $r = (array) $row; ?>
                            <tr>
                                <?php foreach (array_slice($cols, 0, 8) as $col): ?>
                                <td class="small"><?php echo htmlspecialchars((string) ($r[$col] ?? '-')); ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-2 text-muted small">
                    <i class="fas fa-info-circle me-1"></i>
                    Showing first 8 columns. Download full report for all columns.
                </div>
                <?php elseif (isset($previewTotal) && $previewTotal === 0): ?>
                <div class="alert alert-secondary text-center py-5">
                    <i class="fas fa-inbox fa-2x mb-3"></i>
                    <p class="mb-0">No usage events recorded for this period.</p>
                    <small class="text-muted">Events are captured when patrons view or access items in the OPAC.</small>
                </div>
                <?php else: ?>
                <div class="alert alert-secondary text-center py-5">
                    <i class="fas fa-search fa-2x mb-3"></i>
                    <p class="mb-0">Select date range and click <strong>Preview</strong> to see report data.</p>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <div class="row">
                    <div class="col-md-6">
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            <?php echo date('j M Y', strtotime($beginDate)); ?> — <?php echo date('j M Y', strtotime($endDate)); ?>
                        </small>
                    </div>
                    <div class="col-md-6 text-end">
                        <a href="?report_type=<?php echo $selectedReport; ?>&begin_date=<?php echo $beginDate; ?>&end_date=<?php echo $endDate; ?>&format=<?php echo $selectedFormat; ?>&download=1" class="btn btn-success btn-sm">
                            <i class="fas fa-download me-1"></i><?php echo __('Download Full Report'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- COUNTER R5 Reference -->
        <div class="card mt-3">
            <div class="card-header">
                <strong><?php echo __('COUNTER Report Types'); ?></strong>
            </div>
            <div class="card-body">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr><th>Code</th><th>Name</th><th>Description</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><span class="badge bg-primary">TR_J1</span></td><td>Journal Article Requests</td><td>Total article requests by title (monthly breakdown)</td></tr>
                        <tr><td><span class="badge bg-warning text-dark">TR_J3</span></td><td>Journal Article Access Denied</td><td>Articles denied due to no subscription or usage limit exceeded</td></tr>
                        <tr><td><span class="badge bg-info">DR</span></td><td>Database Report</td><td>Usage aggregated by database (publisher platform)</td></tr>
                        <tr><td><span class="badge bg-success">PR</span></td><td>Platform Report</td><td>Platform-wide totals for all metrics</td></tr>
                        <tr><td><span class="badge bg-secondary">IR</span></td><td>Item Report</td><td>Per-item usage for books, articles, and other individual items</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div><!-- /col-md-8 -->

</div><!-- /row -->
<?php end_slot(); ?>