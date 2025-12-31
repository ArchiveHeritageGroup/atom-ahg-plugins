<?php use_helper('Date'); ?>

<?php slot('title'); ?>
<h1>
    <i class="fa fa-dashboard"></i>
    <?php echo __('GRAP 103 Heritage Assets Dashboard'); ?>
</h1>
<?php end_slot(); ?>

<div class="grap-dashboard">

    <!-- Filters -->
    <div class="filter-bar">
        <form method="get" class="filter-form">
            <div class="filter-group">
                <label><?php echo __('Repository'); ?></label>
                <select name="repository_id" class="form-control" onchange="this.form.submit()">
                    <option value=""><?php echo __('All Repositories'); ?></option>
                    <?php foreach ($repositories as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php echo $repositoryId == $id ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label><?php echo __('Financial Year'); ?></label>
                <select name="financial_year" class="form-control" onchange="this.form.submit()">
                    <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 5; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $financialYear == $y ? 'selected' : ''; ?>>
                            <?php echo $y; ?>/<?php echo $y + 1; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="filter-actions">
                <a href="<?php echo url_for(['module' => 'grap', 'action' => 'export']); ?>" class="btn btn-primary">
                    <i class="fa fa-download"></i> <?php echo __('Export Reports'); ?>
                </a>
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card total">
            <div class="card-icon"><i class="fa fa-archive"></i></div>
            <div class="card-content">
                <div class="card-value"><?php echo number_format($totals['total_assets']); ?></div>
                <div class="card-label"><?php echo __('Total Heritage Assets'); ?></div>
            </div>
        </div>
        
        <div class="summary-card recognised">
            <div class="card-icon"><i class="fa fa-check-circle"></i></div>
            <div class="card-content">
                <div class="card-value"><?php echo number_format($totals['recognised']); ?></div>
                <div class="card-label"><?php echo __('Recognised Assets'); ?></div>
                <div class="card-sub"><?php echo $totals['recognition_rate']; ?>% recognition rate</div>
            </div>
        </div>
        
        <div class="summary-card carrying">
            <div class="card-icon"><i class="fa fa-money"></i></div>
            <div class="card-content">
                <div class="card-value">R <?php echo number_format($totals['total_carrying_amount'], 0); ?></div>
                <div class="card-label"><?php echo __('Total Carrying Amount'); ?></div>
            </div>
        </div>
        
        <div class="summary-card impairment">
            <div class="card-icon"><i class="fa fa-exclamation-triangle"></i></div>
            <div class="card-content">
                <div class="card-value">R <?php echo number_format($totals['total_impairment'], 0); ?></div>
                <div class="card-label"><?php echo __('Accumulated Impairment'); ?></div>
            </div>
        </div>
        
        <div class="summary-card surplus">
            <div class="card-icon"><i class="fa fa-line-chart"></i></div>
            <div class="card-content">
                <div class="card-value">R <?php echo number_format($totals['total_surplus'], 0); ?></div>
                <div class="card-label"><?php echo __('Revaluation Surplus'); ?></div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="dashboard-grid">
        
        <!-- By Asset Class -->
        <div class="dashboard-section">
            <h3><i class="fa fa-pie-chart"></i> <?php echo __('By Asset Class'); ?></h3>
            
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th><?php echo __('Asset Class'); ?></th>
                        <th class="text-right"><?php echo __('Count'); ?></th>
                        <th class="text-right"><?php echo __('Carrying Amount'); ?></th>
                        <th class="text-right"><?php echo __('Impairment'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classSummary as $class => $data): ?>
                        <tr>
                            <td>
                                <span class="class-badge class-<?php echo $class; ?>">
                                    <?php echo $assetClasses[$class] ?? ucfirst(str_replace('_', ' ', $class)); ?>
                                </span>
                            </td>
                            <td class="text-right"><?php echo number_format($data['count']); ?></td>
                            <td class="text-right">R <?php echo number_format($data['carrying_amount'], 2); ?></td>
                            <td class="text-right">R <?php echo number_format($data['impairment'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="totals-row">
                        <th><?php echo __('Total'); ?></th>
                        <th class="text-right"><?php echo number_format($totals['total_assets']); ?></th>
                        <th class="text-right">R <?php echo number_format($totals['total_carrying_amount'], 2); ?></th>
                        <th class="text-right">R <?php echo number_format($totals['total_impairment'], 2); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- By Recognition Status -->
        <div class="dashboard-section">
            <h3><i class="fa fa-tasks"></i> <?php echo __('By Recognition Status'); ?></h3>
            
            <div class="status-breakdown">
                <?php foreach ($statusSummary as $status => $data): ?>
                    <?php $statusInfo = $statusLabels[$status] ?? ['label' => $status, 'color' => '#95a5a6']; ?>
                    <div class="status-row">
                        <div class="status-info">
                            <span class="status-dot" style="background-color: <?php echo $statusInfo['color']; ?>"></span>
                            <span class="status-name"><?php echo $statusInfo['label']; ?></span>
                        </div>
                        <div class="status-count"><?php echo number_format($data['count']); ?></div>
                        <div class="status-amount">R <?php echo number_format($data['carrying_amount'], 2); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Recognition Progress -->
            <div class="recognition-progress">
                <div class="progress-label">
                    <span><?php echo __('Recognition Rate'); ?></span>
                    <span class="progress-value"><?php echo $totals['recognition_rate']; ?>%</span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: <?php echo $totals['recognition_rate']; ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Compliance Overview -->
        <div class="dashboard-section">
            <h3><i class="fa fa-shield"></i> <?php echo __('Compliance Overview'); ?></h3>
            
            <div class="compliance-stats">
                <div class="compliance-item">
                    <div class="comp-label"><?php echo __('Asset Class Assigned'); ?></div>
                    <div class="comp-value">
                        <?php 
                        $classRate = ($complianceOverview['total_assets'] ?? 0) > 0 
                            ? round(($complianceOverview['has_class'] ?? 0) / $complianceOverview['total_assets'] * 100) 
                            : 0;
                        echo $classRate; ?>%
                    </div>
                    <div class="comp-bar">
                        <div class="comp-fill" style="width: <?php echo $classRate; ?>%"></div>
                    </div>
                </div>
                
                <div class="compliance-item">
                    <div class="comp-label"><?php echo __('Measurement Basis Set'); ?></div>
                    <div class="comp-value">
                        <?php 
                        $measureRate = ($complianceOverview['total_assets'] ?? 0) > 0 
                            ? round(($complianceOverview['has_measurement'] ?? 0) / $complianceOverview['total_assets'] * 100) 
                            : 0;
                        echo $measureRate; ?>%
                    </div>
                    <div class="comp-bar">
                        <div class="comp-fill" style="width: <?php echo $measureRate; ?>%"></div>
                    </div>
                </div>
                
                <div class="compliance-item">
                    <div class="comp-label"><?php echo __('Carrying Amount Recorded'); ?></div>
                    <div class="comp-value">
                        <?php 
                        $carryRate = ($complianceOverview['total_assets'] ?? 0) > 0 
                            ? round(($complianceOverview['has_carrying'] ?? 0) / $complianceOverview['total_assets'] * 100) 
                            : 0;
                        echo $carryRate; ?>%
                    </div>
                    <div class="comp-bar">
                        <div class="comp-fill" style="width: <?php echo $carryRate; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Actions -->
        <div class="dashboard-section">
            <h3><i class="fa fa-clock-o"></i> <?php echo __('Pending Actions'); ?></h3>
            
            <?php if (!empty($pendingRecognition)): ?>
            <div class="pending-group">
                <h4><?php echo __('Pending Recognition'); ?> (<?php echo count(sfOutputEscaper::unescape($pendingRecognition)); ?>)</h4>
                <div class="pending-list">
                    <?php foreach (array_slice(sfOutputEscaper::unescape($pendingRecognition), 0, 5) as $item): ?>
                        <a href="<?php echo url_for(['module' => 'grap', 'action' => 'edit', 'slug' => $item['slug']]); ?>" class="pending-item">
                            <span class="pending-id"><?php echo htmlspecialchars($item['identifier']); ?></span>
                            <span class="pending-title"><?php echo htmlspecialchars($item['title']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($pendingDerecognition)): ?>
            <div class="pending-group">
                <h4><?php echo __('Pending De-recognition'); ?> (<?php echo count(sfOutputEscaper::unescape($pendingDerecognition)); ?>)</h4>
                <div class="pending-list">
                    <?php foreach (array_slice(sfOutputEscaper::unescape($pendingDerecognition), 0, 5) as $item): ?>
                        <a href="<?php echo url_for(['module' => 'grap', 'action' => 'edit', 'slug' => $item['slug']]); ?>" class="pending-item derec">
                            <span class="pending-id"><?php echo htmlspecialchars($item['identifier']); ?></span>
                            <span class="pending-reason"><?php echo ucfirst(str_replace('_', ' ', $item['derecognition_reason'])); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (empty($pendingRecognition) && empty($pendingDerecognition)): ?>
                <p class="no-pending"><?php echo __('No pending actions.'); ?></p>
            <?php endif; ?>
        </div>

    </div>

    <!-- Quick Links -->
    <div class="quick-links">
        <h3><?php echo __('Quick Links'); ?></h3>
        <div class="link-grid">
            <a href="<?php echo url_for(['module' => 'grap', 'action' => 'export', 'type' => 'asset_register', 'download' => 1]); ?>" class="quick-link">
                <i class="fa fa-file-excel-o"></i>
                <span><?php echo __('NT Asset Register'); ?></span>
            </a>
            <a href="<?php echo url_for(['module' => 'grap', 'action' => 'export', 'type' => 'disclosure_note', 'download' => 1]); ?>" class="quick-link">
                <i class="fa fa-file-text-o"></i>
                <span><?php echo __('GRAP 103 Disclosure'); ?></span>
            </a>
            <a href="<?php echo url_for(['module' => 'grap', 'action' => 'export', 'type' => 'board_pack', 'download' => 1]); ?>" class="quick-link">
                <i class="fa fa-briefcase"></i>
                <span><?php echo __('Board Pack'); ?></span>
            </a>
            <a href="<?php echo url_for(['module' => 'grap', 'action' => 'export', 'type' => 'multi_year_trend', 'download' => 1]); ?>" class="quick-link">
                <i class="fa fa-line-chart"></i>
                <span><?php echo __('Trend Analysis'); ?></span>
            </a>
        </div>
    </div>

</div>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.grap-dashboard {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

/* Filter Bar */
.filter-bar {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.filter-form {
    display: flex;
    gap: 20px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.filter-group label {
    display: block;
    font-size: 12px;
    color: #7f8c8d;
    margin-bottom: 5px;
}

.filter-actions {
    flex-shrink: 0;
}

/* Summary Cards */
.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.summary-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: transform 0.2s, box-shadow 0.2s;
}

.summary-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}

.card-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #fff;
}

.summary-card.total .card-icon { background: linear-gradient(135deg, #667eea, #764ba2); }
.summary-card.recognised .card-icon { background: linear-gradient(135deg, #11998e, #38ef7d); }
.summary-card.carrying .card-icon { background: linear-gradient(135deg, #f093fb, #f5576c); }
.summary-card.impairment .card-icon { background: linear-gradient(135deg, #ff9a9e, #fecfef); }
.summary-card.surplus .card-icon { background: linear-gradient(135deg, #a8edea, #fed6e3); }

.card-value {
    font-size: 24px;
    font-weight: 700;
    color: #2c3e50;
}

.card-label {
    font-size: 13px;
    color: #7f8c8d;
}

.card-sub {
    font-size: 11px;
    color: #27ae60;
}

/* Dashboard Grid */
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 25px;
    margin-bottom: 30px;
}

@media (max-width: 1024px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}

.dashboard-section {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.dashboard-section h3 {
    margin: 0 0 20px 0;
    font-size: 16px;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 10px;
}

.dashboard-section h3 i {
    color: #3498db;
}

/* Table */
.table {
    width: 100%;
    border-collapse: collapse;
}

.table th, .table td {
    padding: 12px;
    border-bottom: 1px solid #f0f0f0;
}

.table th {
    font-size: 11px;
    text-transform: uppercase;
    color: #7f8c8d;
    font-weight: 600;
}

.totals-row {
    background: #f8f9fa;
}

.totals-row th {
    color: #2c3e50;
    font-size: 13px;
}

.class-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 12px;
    background: #ebf5fb;
    color: #2980b9;
}

/* Status Breakdown */
.status-breakdown {
    margin-bottom: 20px;
}

.status-row {
    display: flex;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.status-info {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.status-name {
    font-size: 13px;
    color: #2c3e50;
}

.status-count {
    width: 60px;
    text-align: right;
    font-weight: 600;
}

.status-amount {
    width: 120px;
    text-align: right;
    font-size: 13px;
    color: #7f8c8d;
}

/* Recognition Progress */
.recognition-progress {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #f0f0f0;
}

.progress-label {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    margin-bottom: 8px;
}

.progress-value {
    font-weight: 700;
    color: #27ae60;
}

.progress-bar-container {
    height: 10px;
    background: #ecf0f1;
    border-radius: 5px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #27ae60, #2ecc71);
    border-radius: 5px;
    transition: width 0.5s ease;
}

/* Compliance Stats */
.compliance-item {
    margin-bottom: 15px;
}

.comp-label {
    font-size: 13px;
    color: #7f8c8d;
    margin-bottom: 5px;
}

.comp-value {
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 5px;
}

.comp-bar {
    height: 6px;
    background: #ecf0f1;
    border-radius: 3px;
    overflow: hidden;
}

.comp-fill {
    height: 100%;
    background: #3498db;
    border-radius: 3px;
}

/* Pending Actions */
.pending-group {
    margin-bottom: 20px;
}

.pending-group h4 {
    font-size: 13px;
    color: #7f8c8d;
    margin-bottom: 10px;
}

.pending-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.pending-item {
    display: flex;
    gap: 10px;
    padding: 10px;
    background: #fef9e7;
    border-radius: 8px;
    text-decoration: none;
    transition: background 0.2s;
}

.pending-item:hover {
    background: #fcf3cf;
}

.pending-item.derec {
    background: #fdedec;
}

.pending-item.derec:hover {
    background: #fadbd8;
}

.pending-id {
    font-weight: 600;
    color: #2c3e50;
}

.pending-title, .pending-reason {
    color: #7f8c8d;
    font-size: 13px;
}

.no-pending {
    color: #95a5a6;
    font-style: italic;
}

/* Quick Links */
.quick-links {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.quick-links h3 {
    margin: 0 0 20px 0;
    font-size: 16px;
    color: #2c3e50;
}

.link-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
}

.quick-link {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
    text-decoration: none;
    color: #2c3e50;
    transition: all 0.2s;
}

.quick-link:hover {
    background: #3498db;
    color: #fff;
    transform: translateY(-3px);
}

.quick-link i {
    font-size: 28px;
}

.quick-link span {
    font-size: 13px;
    text-align: center;
}
</style>
