<?php use_helper('Date') ?>

<div class="container-fluid px-4 py-3">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for('statistics/dashboard') ?>">Statistics</a></li>
            <li class="breadcrumb-item active">Settings</li>
        </ol>
    </nav>

    <h1 class="h3 mb-4"><i class="fas fa-cog me-2"></i>Statistics Settings</h1>

    <?php if ($sf_user->hasFlash('notice')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $sf_user->getFlash('notice') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Configuration</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="retention_days" class="form-label">Retention Period (days)</label>
                            <input type="number" class="form-control" id="retention_days" name="retention_days" value="<?php echo $config['retention_days'] ?>" min="1">
                            <small class="form-text text-muted">Raw events older than this will be deleted. Aggregated data is kept forever.</small>
                        </div>

                        <hr>
                        <h6>GeoIP Settings</h6>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="geoip_enabled" name="geoip_enabled" value="1" <?php echo $config['geoip_enabled'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="geoip_enabled">Enable GeoIP Lookup</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="geoip_database_path" class="form-label">GeoIP Database Path</label>
                            <input type="text" class="form-control" id="geoip_database_path" name="geoip_database_path" value="<?php echo esc_entities($config['geoip_database_path']) ?>">
                            <small class="form-text text-muted">Path to MaxMind GeoLite2-City.mmdb database</small>
                        </div>

                        <hr>
                        <h6>Privacy & Filtering</h6>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="bot_filtering_enabled" name="bot_filtering_enabled" value="1" <?php echo $config['bot_filtering_enabled'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="bot_filtering_enabled">Enable Bot Filtering</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="anonymize_ip" name="anonymize_ip" value="1" <?php echo $config['anonymize_ip'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="anonymize_ip">Anonymize IP Addresses</label>
                            </div>
                            <small class="form-text text-muted">Store hashed IPs instead of raw IP addresses</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="exclude_admin_views" name="exclude_admin_views" value="1" <?php echo $config['exclude_admin_views'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="exclude_admin_views">Exclude Admin Page Views</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save Settings
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Bot Filtering</h5>
                    <a href="<?php echo url_for('statistics/admin/bots') ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-robot me-1"></i>Manage Bot List
                    </a>
                </div>
                <div class="card-body">
                    <p class="text-muted">Configure which bots and crawlers to filter from statistics.</p>
                    <p><strong><?php echo $dbStats['bot_patterns'] ?></strong> bot patterns configured</p>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-database me-1"></i>Database Statistics</h6>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Raw Events</span>
                        <strong><?php echo number_format($dbStats['raw_events']) ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Daily Aggregates</span>
                        <strong><?php echo number_format($dbStats['daily_aggregates']) ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Monthly Aggregates</span>
                        <strong><?php echo number_format($dbStats['monthly_aggregates']) ?></strong>
                    </li>
                </ul>
            </div>

            <div class="card bg-light">
                <div class="card-body">
                    <h6><i class="fas fa-terminal me-1"></i>CLI Commands</h6>
                    <p class="small text-muted mb-2">Run these via cron for scheduled processing:</p>
                    <code class="d-block small mb-2">php symfony statistics:aggregate --all</code>
                    <code class="d-block small mb-2">php symfony statistics:report --type=summary</code>
                </div>
            </div>
        </div>
    </div>
</div>
