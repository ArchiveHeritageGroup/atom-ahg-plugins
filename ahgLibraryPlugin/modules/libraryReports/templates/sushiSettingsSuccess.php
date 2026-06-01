<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Library Reports'); ?></h4>
    <ul class="list-unstyled">
        <li><a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'catalogue']); ?>"><i class="fas fa-book me-2"></i><?php echo __('Catalogue'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'creators']); ?>"><i class="fas fa-user-edit me-2"></i><?php echo __('Creators'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'subjects']); ?>"><i class="fas fa-tags me-2"></i><?php echo __('Subjects'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'index']); ?>"><i class="fas fa-chart-bar me-2"></i><?php echo __('Dashboard'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'counter']); ?>"><i class="fas fa-table me-2"></i><?php echo __('COUNTER Reports'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'sushiSettings']); ?>" class="text-primary fw-bold"><i class="fas fa-cloud me-2"></i><?php echo __('SUSHI Settings'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'frbrOverride']); ?>"><i class="fas fa-layer-group me-2"></i><?php echo __('FRBR Overrides'); ?></a></li>
    </ul>
    <hr>
    <a href="<?php echo url_for(['module' => 'reports', 'action' => 'index']); ?>#library" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back to Library'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-cloud"></i> <?php echo __('SUSHI Endpoint Settings'); ?></h1>
<p class="text-muted mb-0"><?php echo __('Configure your COUNTER SUSHI 5.0 harvest endpoint and credentials.'); ?></p>
<?php end_slot(); ?>

<?php slot('content'); ?>

<?php if (isset($message)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo esc_entities($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($testResult)): ?>
<div class="alert alert-<?php echo $testResult['ok'] ? 'success' : 'danger'; ?> alert-dismissible fade show">
    <strong><?php echo $testResult['ok'] ? 'Connection OK' : 'Connection Failed'; ?>:</strong>
    <?php echo esc_entities($testResult['message']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">

    <!-- ── Settings Form ────────────────────────────────────────── -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-cogs me-2"></i><?php echo __('SUSHI Credentials'); ?></h5>
            </div>
            <div class="card-body">
                <form method="post">

                    <div class="mb-3">
                        <label class="form-label"><?php echo __('SUSHI Endpoint URL'); ?></label>
                        <input type="url" name="sushi_url" class="form-control"
                               placeholder="https://your-vendor.com/sushi"
                               value="<?php echo esc_specialchars($settings['sushi_url'] ?? ''); ?>">
                        <small class="text-muted">The SUSHI harvest URL for your content provider.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo __('API Key'); ?></label>
                        <input type="password" name="sushi_api_key" class="form-control"
                               placeholder="Leave blank to keep current"
                               value="">
                        <small class="text-muted">Stored encrypted. Enter new value to update.</small>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Requestor ID'); ?></label>
                        <input type="text" name="sushi_requestor_id" class="form-control"
                               placeholder="institutional identifier"
                               value="<?php echo esc_specialchars($settings['sushi_requestor_id'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Customer ID'); ?></label>
                        <input type="text" name="sushi_customer_id" class="form-control"
                               placeholder="customer account number"
                               value="<?php echo esc_specialchars($settings['sushi_customer_id'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Requestor Name'); ?></label>
                        <input type="text" name="sushi_requestor_name" class="form-control"
                               value="<?php echo esc_specialchars($settings['sushi_requestor_name'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Requestor Email'); ?></label>
                        <input type="email" name="sushi_requestor_email" class="form-control"
                               value="<?php echo esc_specialchars($settings['sushi_requestor_email'] ?? ''); ?>">
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i><?php echo __('Save Settings'); ?>
                        </button>
                    </div>
                </form>

                <!-- Test Connection -->
                <hr>
                <form method="post" action="<?php echo url_for(['module' => 'libraryReports', 'action' => 'sushiSettings']); ?>">
                    <input type="hidden" name="test_connection" value="1">
                    <button type="submit" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-bolt me-1"></i><?php echo __('Test Connection'); ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- SUSHI endpoint info -->
        <div class="card mt-3">
            <div class="card-header">
                <strong><?php echo __('Local SUSHI Endpoint'); ?></strong>
            </div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-5">SUSHI URL</dt>
                    <dd class="col-7"><code>/sushi/counter5</code></dd>
                    <dt class="col-5">Method</dt>
                    <dd class="col-7"><span class="badge bg-success">POST</span></dd>
                    <dt class="col-5">Version</dt>
                    <dd class="col-7">SUSHI 5.0 (COUNTER R5)</dd>
                    <dt class="col-5">CORS</dt>
                    <dd class="col-7"><span class="badge bg-info">Open (*)</span></dd>
                </dl>
            </div>
        </div>
    </div><!-- /col-md-5 -->

    <!-- ── Access Log ─────────────────────────────────────────── -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><?php echo __('SUSHI Harvest Access Log'); ?></strong>
                <span class="badge bg-dark"><?php echo count($accessLog ?? []); ?></span>
            </div>
            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>Timestamp</th>
                            <th>Institution</th>
                            <th>Report</th>
                            <th>Period</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($accessLog)): ?>
                            <?php foreach ($accessLog as $log): ?>
                            <tr>
                                <td class="small text-muted"><?php echo substr($log->created_at, 0, 16); ?></td>
                                <td class="small"><?php echo esc_specialchars($log->institution_id ?? '-'); ?></td>
                                <td><code class="small"><?php echo esc_specialchars($log->report_type ?? '-'); ?></code></td>
                                <td class="small"><?php echo esc_specialchars($log->period_begin ?? '-'); ?> — <?php echo esc_specialchars($log->period_end ?? '-'); ?></td>
                                <td>
                                    <?php
                                    $ok = ($log->status_code ?? 0) >= 200 && ($log->status_code ?? 0) < 300;
                                    $warn = ($log->status_code ?? 0) >= 400;
                                    $badge = $ok ? 'bg-success' : ($warn ? 'bg-danger' : 'bg-warning');
                                    ?>
                                    <span class="badge <?php echo $badge; ?>">
                                        <?php echo $log->status_code ?? 'ERR'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted p-4">No SUSHI harvest requests recorded yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Subscribed Reports -->
        <div class="card mt-3">
            <div class="card-header">
                <strong><?php echo __('Active Subscriptions'); ?></strong>
            </div>
            <ul class="list-group list-group-flush">
                <?php
                $subscriptions = [
                    ['code' => 'TR_J1', 'name' => 'Journal Article Requests', 'desc' => 'Monthly article requests by journal title'],
                    ['code' => 'DR', 'name' => 'Database Report', 'desc' => 'Usage by database / publisher platform'],
                    ['code' => 'PR', 'name' => 'Platform Report', 'desc' => 'Platform-wide totals'],
                    ['code' => 'IR', 'name' => 'Item Report', 'desc' => 'Per-item usage (books, articles, etc.)'],
                    ['code' => 'TR_J3', 'name' => 'Journal Article Access Denied', 'desc' => 'Access denied count by journal'],
                ];
                foreach ($subscriptions as $sub):
                    $enabled = str_contains($settings['counter_report_types'] ?? '', $sub['code']);
                ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-<?php echo $enabled ? 'success' : 'secondary'; ?> me-2"><?php echo $sub['code']; ?></span>
                        <strong><?php echo $sub['name']; ?></strong>
                        <br><small class="text-muted"><?php echo $sub['desc']; ?></small>
                    </div>
                    <?php if ($enabled): ?>
                    <i class="fas fa-check-circle text-success"></i>
                    <?php else: ?>
                    <i class="fas fa-times-circle text-muted"></i>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div><!-- /col-md-7 -->

</div><!-- /row -->
<?php end_slot(); ?>