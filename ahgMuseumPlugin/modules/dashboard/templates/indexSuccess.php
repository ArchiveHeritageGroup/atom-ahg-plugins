<?php use_helper('Text'); ?>

<h1 class="dashboard-title">Data Quality Dashboard</h1>

<?php 
// Convert Symfony escaped arrays to regular arrays
$fieldCompleteness = $sf_data->getRaw('analysis')['fieldCompleteness'] ?? [];
$categoryAverages = $sf_data->getRaw('analysis')['categoryAverages'] ?? [];
$gradeDistribution = $sf_data->getRaw('analysis')['gradeDistribution'] ?? [];
$missingFieldCounts = $sf_data->getRaw('analysis')['missingFieldCounts'] ?? [];
$worstRecords = $sf_data->getRaw('analysis')['worstRecords'] ?? [];
$overallGrade = $sf_data->getRaw('analysis')['overallGrade'] ?? ['grade' => 'N/A', 'label' => 'No Data', 'color' => '#95a5a6'];
$overallScore = $sf_data->getRaw('analysis')['overallScore'] ?? 0;
$analyzedRecords = $sf_data->getRaw('analysis')['analyzedRecords'] ?? 0;
$categoryLabels = $sf_data->getRaw('categoryLabels') ?? [];
?>

<style>
.dashboard-title {
    color: #1a5c4c;
    font-size: 28px;
    margin-bottom: 25px;
    font-weight: 600;
}

/* Summary Cards Row */
.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.summary-card {
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    color: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.summary-card.orange { background: #d35400; }
.summary-card.cyan { background: #17a2b8; }
.summary-card.yellow { background: #f1c40f; color: #333; }
.summary-card.green { background: #1a5c4c; }

.summary-card .value {
    font-size: 36px;
    font-weight: 700;
    line-height: 1.2;
}

.summary-card .label {
    font-size: 13px;
    margin-top: 5px;
    opacity: 0.9;
}

/* Secondary Stats Row */
.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.stat-box {
    background: #fff;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
}

.stat-box .value {
    font-size: 28px;
    font-weight: 700;
    color: #1a5c4c;
}

.stat-box .label {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

/* Section Panels */
.panel {
    background: #fff;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}

.panel-header {
    background: #1a5c4c;
    color: #fff;
    padding: 12px 20px;
    font-size: 16px;
    font-weight: 600;
}

.panel-body {
    padding: 20px;
}

/* Data Table */
.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th,
.data-table td {
    padding: 10px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.data-table th {
    font-weight: 600;
    color: #333;
    font-size: 13px;
}

.data-table tr:hover {
    background: #f8f9fa;
}

/* Progress Bars */
.progress-bar-container {
    display: flex;
    align-items: center;
    gap: 10px;
}

.progress-bar {
    flex: 1;
    height: 20px;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
}

.progress-bar .fill {
    height: 100%;
    border-radius: 10px;
    transition: width 0.3s;
}

.progress-bar .fill.excellent { background: #27ae60; }
.progress-bar .fill.good { background: #2ecc71; }
.progress-bar .fill.fair { background: #f39c12; }
.progress-bar .fill.poor { background: #e67e22; }
.progress-bar .fill.bad { background: #e74c3c; }

.progress-value {
    min-width: 45px;
    text-align: right;
    font-weight: 600;
}

/* Grade Badge */
.grade-badge {
    display: inline-block;
    width: 30px;
    height: 30px;
    line-height: 30px;
    text-align: center;
    border-radius: 50%;
    font-weight: 700;
    color: #fff;
    font-size: 14px;
}

/* Grid Layout */
.two-column {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
}

/* Filter Form */
.filter-form {
    background: #fff;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.filter-form label {
    font-weight: 600;
    color: #333;
}

.filter-form select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    min-width: 200px;
}

.filter-form button {
    background: #1a5c4c;
    color: #fff;
    border: none;
    padding: 8px 20px;
    border-radius: 4px;
    cursor: pointer;
}

.filter-form button:hover {
    background: #145043;
}

/* Record Link */
.record-link {
    color: #1a5c4c;
    text-decoration: none;
}

.record-link:hover {
    text-decoration: underline;
}
</style>

<!-- Filter Form -->
<form method="get" class="filter-form">
    <label><?php echo __('Repository'); ?>:</label>
    <select name="repository">
        <option value=""><?php echo __('All Repositories'); ?></option>
        <?php foreach ($sf_data->getRaw('repositories') as $id => $name): ?>
            <option value="<?php echo $id; ?>" <?php echo $repositoryId == $id ? 'selected' : ''; ?>>
                <?php echo esc_entities($name); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit"><?php echo __('Filter'); ?></button>
    <a href="?export=csv" class="btn" style="background:#17a2b8;color:#fff;padding:8px 15px;border-radius:4px;text-decoration:none;">
        <i class="fa fa-download"></i> <?php echo __('Export CSV'); ?>
    </a>
</form>

<!-- Summary Cards -->
<div class="summary-cards">
    <div class="summary-card orange">
        <div class="value"><?php echo $analyzedRecords; ?></div>
        <div class="label"><?php echo __('Records Analyzed'); ?></div>
    </div>
    <div class="summary-card cyan">
        <div class="value"><?php echo $overallScore; ?>%</div>
        <div class="label"><?php echo __('Overall Quality Score'); ?></div>
    </div>
    <div class="summary-card yellow">
        <div class="value"><?php echo $overallGrade['grade']; ?></div>
        <div class="label"><?php echo $overallGrade['label']; ?></div>
    </div>
    <div class="summary-card green">
        <div class="value"><?php echo ($gradeDistribution['A'] ?? 0) + ($gradeDistribution['B'] ?? 0); ?></div>
        <div class="label"><?php echo __('Good/Excellent Records'); ?></div>
    </div>
</div>

<!-- Grade Distribution -->
<div class="stats-row">
    <div class="stat-box">
        <div class="value" style="color:#27ae60;"><?php echo $gradeDistribution['A'] ?? 0; ?></div>
        <div class="label"><?php echo __('Grade A (Excellent)'); ?></div>
    </div>
    <div class="stat-box">
        <div class="value" style="color:#2ecc71;"><?php echo $gradeDistribution['B'] ?? 0; ?></div>
        <div class="label"><?php