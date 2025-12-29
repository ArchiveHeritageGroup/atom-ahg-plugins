<?php slot('title'); ?>
<h1>
    <i class="fa fa-download"></i>
    <?php echo __('GRAP 103 Export Reports'); ?>
</h1>
<?php end_slot(); ?>

<div class="grap-export-page">

    <div class="page-intro">
        <p>
            <?php echo __('Generate National Treasury compliant reports, GRAP 103 disclosures, and management reports for heritage assets.'); ?>
        </p>
    </div>

    <!-- Filters -->
    <div class="export-filters">
        <form id="export-form" method="get">
            <div class="filter-row">
                <div class="filter-group">
                    <label><?php echo __('Repository'); ?></label>
                    <select name="repository_id" class="form-control">
                        <option value=""><?php echo __('All Repositories'); ?></option>
                        <?php foreach ($repositories as $id => $name): ?>
                            <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label><?php echo __('Financial Year End'); ?></label>
                    <select name="financial_year" class="form-control">
                        <?php foreach ($financialYears as $year => $label): ?>
                            <option value="<?php echo $year; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- Export Cards -->
    <div class="export-grid">
        
        <!-- National Treasury Reports -->
        <div class="export-section">
            <h3><i class="fa fa-bank"></i> <?php echo __('National Treasury Reports'); ?></h3>
            
            <div class="export-card featured">
                <div class="card-icon"><i class="fa fa-file-excel-o"></i></div>
                <div class="card-content">
                    <h4><?php echo $exportTypes['asset_register']['name']; ?></h4>
                    <p><?php echo $exportTypes['asset_register']['description']; ?></p>
                    <div class="card-meta">
                        <span class="format-badge csv">CSV</span>
                        <span class="compliance-badge">NT Compliant</span>
                    </div>
                </div>
                <a href="#" class="btn btn-primary export-btn" data-type="asset_register">
                    <i class="fa fa-download"></i> <?php echo __('Export'); ?>
                </a>
            </div>

            <div class="export-card">
                <div class="card-icon"><i class="fa fa-file-text-o"></i></div>
                <div class="card-content">
                    <h4><?php echo $exportTypes['disclosure_note']['name']; ?></h4>
                    <p><?php echo $exportTypes['disclosure_note']['description']; ?></p>
                    <div class="card-meta">
                        <span class="format-badge csv">CSV</span>
                        <span class="compliance-badge">GRAP 103</span>
                    </div>
                </div>
                <a href="#" class="btn btn-primary export-btn" data-type="disclosure_note">
                    <i class="fa fa-download"></i> <?php echo __('Export'); ?>
                </a>
            </div>
        </div>

        <!-- Financial Schedules -->
        <div class="export-section">
            <h3><i class="fa fa-calculator"></i> <?php echo __('Financial Schedules'); ?></h3>
            
            <div class="export-card">
                <div class="card-icon"><i class="fa fa-minus-circle"></i></div>
                <div class="card-content">
                    <h4><?php echo $exportTypes['impairment_schedule']['name']; ?></h4>
                    <p><?php echo $exportTypes['impairment_schedule']['description']; ?></p>
                    <div class="card-meta">
                        <span class="format-badge csv">CSV</span>
                    </div>
                </div>
                <a href="#" class="btn btn-outline-primary export-btn" data-type="impairment_schedule">
                    <i class="fa fa-download"></i> <?php echo __('Export'); ?>
                </a>
            </div>

            <div class="export-card">
                <div class="card-icon"><i class="fa fa-sign-out"></i></div>
                <div class="card-content">
                    <h4><?php echo $exportTypes['derecognition_schedule']['name']; ?></h4>
                    <p><?php echo $exportTypes['derecognition_schedule']['description']; ?></p>
                    <div class="card-meta">
                        <span class="format-badge csv">CSV</span>
                    </div>
                </div>
                <a href="#" class="btn btn-outline-primary export-btn" data-type="derecognition_schedule">
                    <i class="fa fa-download"></i> <?php echo __('Export'); ?>
                </a>
            </div>

            <div class="export-card">
                <div class="card-icon"><i class="fa fa-line-chart"></i></div>
                <div class="card-content">
                    <h4><?php echo $exportTypes['revaluation_schedule']['name']; ?></h4>
                    <p><?php echo $exportTypes['revaluation_schedule']['description']; ?></p>
                    <div class="card-meta">
                        <span class="format-badge csv">CSV</span>
                    </div>
                </div>
                <a href="#" class="btn btn-outline-primary export-btn" data-type="revaluation_schedule">
                    <i class="fa fa-download"></i> <?php echo __('Export'); ?>
                </a>
            </div>
        </div>

        <!-- Management Reports -->
        <div class="export-section">
            <h3><i class="fa fa-briefcase"></i> <?php echo __('Management Reports'); ?></h3>
            
            <div class="export-card featured">
                <div class="card-icon"><i class="fa fa-file-pdf-o"></i></div>
                <div class="card-content">
                    <h4><?php echo $exportTypes['board_pack']['name']; ?></h4>
                    <p><?php echo $exportTypes['board_pack']['description']; ?></p>
                    <div class="card-meta">
                        <span class="format-badge pdf">PDF</span>
                        <span class="compliance-badge">Board Ready</span>
                    </div>
                </div>
                <a href="#" class="btn btn-primary export-btn" data-type="board_pack">
                    <i class="fa fa-download"></i> <?php echo __('Export'); ?>
                </a>
            </div>

            <div class="export-card">
                <div class="card-icon"><i class="fa fa-area-chart"></i></div>
                <div class="card-content">
                    <h4><?php echo $exportTypes['multi_year_trend']['name']; ?></h4>
                    <p><?php echo $exportTypes['multi_year_trend']['description']; ?></p>
                    <div class="card-meta">
                        <span class="format-badge csv">CSV</span>
                    </div>
                    <div class="extra-options">
                        <label><?php echo __('Years'); ?>:</label>
                        <select name="years" id="trend-years" class="form-control form-control-sm">
                            <option value="3">3 Years</option>
                            <option value="5" selected>5 Years</option>
                            <option value="10">10 Years</option>
                        </select>
                    </div>
                </div>
                <a href="#" class="btn btn-outline-primary export-btn" data-type="multi_year_trend">
                    <i class="fa fa-download"></i> <?php echo __('Export'); ?>
                </a>
            </div>
        </div>

    </div>

    <!-- Quick Reference -->
    <div class="quick-reference">
        <h3><?php echo __('Report Reference'); ?></h3>
        <table class="reference-table">
            <thead>
                <tr>
                    <th><?php echo __('Report'); ?></th>
                    <th><?php echo __('Purpose'); ?></th>
                    <th><?php echo __('Frequency'); ?></th>
                    <th><?php echo __('Recipients'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>NT Asset Register</strong></td>
                    <td>PFMA compliance, asset management</td>
                    <td>Annual / Audit</td>
                    <td>National Treasury, AG-SA</td>
                </tr>
                <tr>
                    <td><strong>GRAP 103 Disclosure</strong></td>
                    <td>Annual Financial Statements note</td>
                    <td>Annual</td>
                    <td>CFO, External Auditors</td>
                </tr>
                <tr>
                    <td><strong>Board Pack</strong></td>
                    <td>Executive oversight</td>
                    <td>Quarterly / Annual</td>
                    <td>Board, Accounting Officer</td>
                </tr>
                <tr>
                    <td><strong>Impairment Schedule</strong></td>
                    <td>Track value reductions</td>
                    <td>As needed / Annual</td>
                    <td>Finance, Audit Committee</td>
                </tr>
                <tr>
                    <td><strong>De-recognition Schedule</strong></td>
                    <td>Asset disposals, NARSSA compliance</td>
                    <td>As needed / Annual</td>
                    <td>NARSSA, Audit Committee</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Back Link -->
    <div class="page-actions">
        <a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'grapDashboard']); ?>" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left"></i> <?php echo __('Back to Dashboard'); ?>
        </a>
    </div>

</div>

<script>
document.querySelectorAll('.export-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        
        var type = this.getAttribute('data-type');
        var form = document.getElementById('export-form');
        var repositoryId = form.querySelector('[name="repository_id"]').value;
        var financialYear = form.querySelector('[name="financial_year"]').value;
        
        var url = '<?php echo url_for(['module' => 'grap', 'action' => 'export']); ?>';
        url += '?type=' + type + '&download=1';
        url += '&repository_id=' + repositoryId;
        url += '&financial_year=' + financialYear;
        
        if (type === 'multi_year_trend') {
            var years = document.getElementById('trend-years').value;
            url += '&years=' + years;
        }
        
        window.location.href = url;
    });
});
</script>

<style>
.grap-export-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.page-intro {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
}

.page-intro p {
    margin: 0;
    font-size: 16px;
    opacity: 0.95;
}

/* Filters */
.export-filters {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.filter-row {
    display: flex;
    gap: 20px;
}

.filter-group {
    flex: 1;
}

.filter-group label {
    display: block;
    font-size: 12px;
    color: #7f8c8d;
    margin-bottom: 5px;
}

/* Export Grid */
.export-grid {
    display: grid;
    gap: 30px;
    margin-bottom: 30px;
}

.export-section {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.export-section h3 {
    margin: 0 0 20px 0;
    font-size: 16px;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 10px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.export-section h3 i {
    color: #3498db;
}

/* Export Card */
.export-card {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
    margin-bottom: 15px;
    transition: all 0.2s;
}

.export-card:last-child {
    margin-bottom: 0;
}

.export-card:hover {
    background: #f0f4f8;
    transform: translateX(5px);
}

.export-card.featured {
    background: linear-gradient(135deg, #e8f4fd, #d6eaf8);
    border: 2px solid #3498db;
}

.card-icon {
    width: 60px;
    height: 60px;
    background: #fff;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #3498db;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.card-content {
    flex: 1;
}

.card-content h4 {
    margin: 0 0 5px 0;
    font-size: 15px;
    color: #2c3e50;
}

.card-content p {
    margin: 0 0 10px 0;
    font-size: 13px;
    color: #7f8c8d;
}

.card-meta {
    display: flex;
    gap: 10px;
}

.format-badge {
    font-size: 10px;
    padding: 3px 8px;
    border-radius: 4px;
    font-weight: 600;
    text-transform: uppercase;
}

.format-badge.csv {
    background: #d5f5e3;
    color: #27ae60;
}

.format-badge.pdf {
    background: #fadbd8;
    color: #c0392b;
}

.compliance-badge {
    font-size: 10px;
    padding: 3px 8px;
    border-radius: 4px;
    background: #ebf5fb;
    color: #2980b9;
    font-weight: 600;
}

.extra-options {
    margin-top: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.extra-options label {
    font-size: 12px;
    color: #7f8c8d;
}

.extra-options .form-control-sm {
    width: auto;
    padding: 4px 8px;
    font-size: 12px;
}

.export-btn {
    flex-shrink: 0;
    white-space: nowrap;
}

/* Quick Reference */
.quick-reference {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.quick-reference h3 {
    margin: 0 0 20px 0;
    font-size: 16px;
    color: #2c3e50;
}

.reference-table {
    width: 100%;
    border-collapse: collapse;
}

.reference-table th,
.reference-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #f0f0f0;
}

.reference-table th {
    font-size: 11px;
    text-transform: uppercase;
    color: #7f8c8d;
    font-weight: 600;
}

.reference-table td {
    font-size: 13px;
}

/* Page Actions */
.page-actions {
    text-align: center;
}
</style>
