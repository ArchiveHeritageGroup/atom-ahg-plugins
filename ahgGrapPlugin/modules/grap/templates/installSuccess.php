<?php slot('title'); ?>
<h1>
    <i class="fa fa-cogs"></i>
    <?php echo __('ahgGrapPlugin Installation'); ?>
</h1>
<?php end_slot(); ?>

<div class="grap-install-page">

    <!-- Flash Messages -->
    <?php if ($sf_user->hasFlash('notice')): ?>
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php echo $sf_user->getFlash('notice'); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($sf_user->hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php echo $sf_user->getFlash('error'); ?>
        </div>
    <?php endif; ?>

    <!-- Status Overview -->
    <div class="status-overview">
        <h2>
            <?php if ($isInstalled): ?>
                <i class="fa fa-check-circle text-success"></i> <?php echo __('Plugin Installed'); ?>
            <?php else: ?>
                <i class="fa fa-times-circle text-danger"></i> <?php echo __('Plugin Not Installed'); ?>
            <?php endif; ?>
        </h2>
    </div>

    <!-- Database Tables -->
    <div class="install-section">
        <h3><i class="fa fa-database"></i> <?php echo __('Database Tables'); ?></h3>
        
        <table class="table table-status">
            <thead>
                <tr>
                    <th><?php echo __('Table'); ?></th>
                    <th><?php echo __('Status'); ?></th>
                    <th><?php echo __('Records'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($status as $table => $info): ?>
                    <tr>
                        <td><code><?php echo $table; ?></code></td>
                        <td>
                            <?php if ($info['exists']): ?>
                                <span class="badge badge-success"><i class="fa fa-check"></i> Exists</span>
                            <?php else: ?>
                                <span class="badge badge-danger"><i class="fa fa-times"></i> Missing</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($info['exists']): ?>
                                <?php echo number_format($info['count']); ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Actions -->
    <div class="install-actions">
        
        <?php if (!$isInstalled): ?>
        <!-- Install -->
        <div class="action-card install">
            <div class="card-icon"><i class="fa fa-download"></i></div>
            <div class="card-content">
                <h4><?php echo __('Install Plugin'); ?></h4>
                <p><?php echo __('Create database tables for GRAP 103 heritage asset accounting.'); ?></p>
            </div>
            <form method="post">
                <input type="hidden" name="action_type" value="install">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fa fa-check"></i> <?php echo __('Install Now'); ?>
                </button>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($isInstalled): ?>
        <!-- Migrate -->
        <div class="action-card migrate">
            <div class="card-icon"><i class="fa fa-exchange"></i></div>
            <div class="card-content">
                <h4><?php echo __('Migrate Existing Data'); ?></h4>
                <p><?php echo __('Import valuation data from existing AtoM properties into GRAP records.'); ?></p>
            </div>
            <form method="post">
                <input type="hidden" name="action_type" value="migrate">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-arrow-right"></i> <?php echo __('Migrate'); ?>
                </button>
            </form>
        </div>

        <!-- Create Snapshot -->
        <div class="action-card snapshot">
            <div class="card-icon"><i class="fa fa-camera"></i></div>
            <div class="card-content">
                <h4><?php echo __('Create Financial Year Snapshot'); ?></h4>
                <p><?php echo __('Capture current asset values for trend reporting.'); ?></p>
                <div class="snapshot-options">
                    <form method="post" class="snapshot-form">
                        <input type="hidden" name="action_type" value="snapshot">
                        <div class="form-row">
                            <div class="form-group">
                                <label><?php echo __('Financial Year End'); ?></label>
                                <input type="date" name="financial_year_end" class="form-control" 
                                       value="<?php echo date('Y'); ?>-03-31">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-info">
                            <i class="fa fa-camera"></i> <?php echo __('Create Snapshot'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Uninstall -->
        <div class="action-card uninstall">
            <div class="card-icon"><i class="fa fa-trash"></i></div>
            <div class="card-content">
                <h4><?php echo __('Uninstall Plugin'); ?></h4>
                <p><?php echo __('Remove all GRAP database tables. This action cannot be undone!'); ?></p>
            </div>
            <form method="post" onsubmit="return confirm('Are you sure? This will delete ALL GRAP data!');">
                <input type="hidden" name="action_type" value="uninstall">
                <button type="submit" class="btn btn-danger">
                    <i class="fa fa-trash"></i> <?php echo __('Uninstall'); ?>
                </button>
            </form>
        </div>
        <?php endif; ?>

    </div>

    <!-- Navigation -->
    <?php if ($isInstalled): ?>
    <div class="install-nav">
        <h3><?php echo __('Quick Links'); ?></h3>
        <div class="nav-links">
            <a href="<?php echo url_for(['module' => 'grap', 'action' => 'dashboard']); ?>" class="nav-link">
                <i class="fa fa-dashboard"></i> <?php echo __('Dashboard'); ?>
            </a>
            <a href="<?php echo url_for(['module' => 'grap', 'action' => 'export']); ?>" class="nav-link">
                <i class="fa fa-download"></i> <?php echo __('Export Reports'); ?>
            </a>
        </div>
    </div>
    <?php endif; ?>

</div>

<style>
.grap-install-page {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}

.status-overview {
    text-align: center;
    padding: 40px;
    background: #fff;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.status-overview h2 {
    margin: 0;
    font-size: 24px;
}

.status-overview h2 i {
    margin-right: 10px;
}

.text-success { color: #27ae60; }
.text-danger { color: #e74c3c; }

/* Install Section */
.install-section {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.install-section h3 {
    margin: 0 0 20px 0;
    font-size: 16px;
    color: #2c3e50;
}

.install-section h3 i {
    margin-right: 10px;
    color: #3498db;
}

.table-status {
    width: 100%;
    border-collapse: collapse;
}

.table-status th,
.table-status td {
    padding: 12px;
    border-bottom: 1px solid #f0f0f0;
}

.table-status th {
    text-align: left;
    font-size: 11px;
    text-transform: uppercase;
    color: #7f8c8d;
}

.badge {
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.badge-success { background: #d5f5e3; color: #27ae60; }
.badge-danger { background: #fadbd8; color: #e74c3c; }

/* Action Cards */
.install-actions {
    display: grid;
    gap: 20px;
    margin-bottom: 30px;
}

.action-card {
    display: flex;
    align-items: center;
    gap: 20px;
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.action-card.install {
    border: 2px solid #27ae60;
    background: linear-gradient(to right, #fff, #d5f5e3);
}

.action-card.uninstall {
    border: 2px solid #e74c3c;
}

.card-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.action-card.install .card-icon { background: #d5f5e3; color: #27ae60; }
.action-card.migrate .card-icon { background: #ebf5fb; color: #3498db; }
.action-card.snapshot .card-icon { background: #d6eaf8; color: #2980b9; }
.action-card.uninstall .card-icon { background: #fadbd8; color: #e74c3c; }

.card-content {
    flex: 1;
}

.card-content h4 {
    margin: 0 0 5px 0;
    font-size: 16px;
    color: #2c3e50;
}

.card-content p {
    margin: 0;
    font-size: 13px;
    color: #7f8c8d;
}

.snapshot-options {
    margin-top: 15px;
}

.snapshot-form {
    display: flex;
    gap: 15px;
    align-items: flex-end;
}

.snapshot-form .form-group {
    margin: 0;
}

.snapshot-form label {
    display: block;
    font-size: 12px;
    margin-bottom: 5px;
}

/* Navigation */
.install-nav {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.install-nav h3 {
    margin: 0 0 20px 0;
    font-size: 16px;
    color: #2c3e50;
}

.nav-links {
    display: flex;
    gap: 15px;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px 25px;
    background: #f8f9fa;
    border-radius: 8px;
    text-decoration: none;
    color: #2c3e50;
    transition: all 0.2s;
}

.nav-link:hover {
    background: #3498db;
    color: #fff;
}

.nav-link i {
    font-size: 18px;
}
</style>
