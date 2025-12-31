<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('GRAP 103 Financial Compliance Dashboard'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<?php
/**
 * Helper function to build CCO view URL
 * Uses slug-based routing pattern
 */
function cco_view_url($asset): string
{
    if (isset($asset->slug) && $asset->slug) {
        return url_for(['module' => 'cco', 'action' => 'index', 'slug' => $asset->slug]);
    }
    // Fallback to ID-based URL
    return url_for(['module' => 'cco', 'action' => 'index', 'id' => $asset->object_id]);
}

function cco_edit_url($asset): string
{
    if (isset($asset->slug) && $asset->slug) {
        return url_for(['module' => 'cco', 'action' => 'edit', 'slug' => $asset->slug]);
    }
    // Fallback to ID-based URL
    return url_for(['module' => 'cco', 'action' => 'edit', 'id' => $asset->object_id]);
}
?>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.grap-dashboard {
    padding: 20px;
}
.stat-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.stat-card h3 {
    margin-top: 0;
    color: #0066cc;
    border-bottom: 2px solid #0066cc;
    padding-bottom: 10px;
}
.stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}
.stat-box {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}
.stat-box.green {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}
.stat-box.orange {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}
.stat-box.blue {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}
.stat-box h4 {
    margin: 0 0 10px 0;
    font-size: 14px;
    opacity: 0.9;
}
.stat-box .value {
    font-size: 32px;
    font-weight: bold;
    margin: 10px 0;
}
.stat-box .subvalue {
    font-size: 14px;
    opacity: 0.8;
}
.progress-bar {
    background: #e0e0e0;
    height: 30px;
    border-radius: 15px;
    overflow: hidden;
    margin: 10px 0;
}
.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    transition: width 0.3s ease;
}
.progress-fill.green {
    background: linear-gradient(90deg, #11998e 0%, #38ef7d 100%);
}
.progress-fill.orange {
    background: linear-gradient(90deg, #f093fb 0%, #f5576c 100%);
}
.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}
.data-table th {
    background: #f5f5f5;
    padding: 12px;
    text-align: left;
    border-bottom: 2px solid #ddd;
    font-weight: 600;
}
.data-table td {
    padding: 10px 12px;
    border-bottom: 1px solid #eee;
}
.data-table tr:hover {
    background: #f9f9f9;
}
.alert-warning {
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 4px;
    padding: 15px;
    margin: 15px 0;
    color: #856404;
}
.alert-info {
    background: #d1ecf1;
    border: 1px solid #17a2b8;
    border-radius: 4px;
    padding: 15px;
    margin: 15px 0;
    color: #0c5460;
}
.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}
.badge-success { background: #28a745; color: white; }
.badge-warning { background: #ffc107; color: #000; }
.badge-danger { background: #dc3545; color: white; }
.badge-info { background: #17a2b8; color: white; }
</style>

<div class="grap-dashboard">

    <!-- Action Buttons -->
    <div style="margin-bottom: 20px;">
        <?php echo link_to(__('Export Full Report'), ['module' => 'cco', 'action' => 'grapExportReport'], ['class' => 'btn btn-primary']); ?>
        <?php echo link_to(__('Browse Objects'), ['module' => 'cco', 'action' => 'browse'], ['class' => 'btn btn-secondary']); ?>
    </div>

    <!-- Overview Statistics -->
    <div class="stat-grid">
        <div class="stat-box">
            <h4>Total Heritage Assets</h4>
            <div class="value"><?php echo number_format($stats['total_objects']); ?></div>
            <div class="subvalue">with GRAP data</div>
        </div>
        
        <div class="stat-box green">
            <h4>Recognised Assets</h4>
            <div class="value"><?php echo number_format($stats['recognised']); ?></div>
            <div class="subvalue">
                <?php echo $stats['total_objects'] > 0 ? round(($stats['recognised'] / $stats['total_objects']) * 100, 1) : 0; ?>% of total
            </div>
        </div>
        
        <div class="stat-box orange">
            <h4>Total Asset Value</h4>
            <div class="value">R <?php echo number_format($stats['total_value'], 0); ?></div>
            <div class="subvalue">Initial recognition value</div>
        </div>
        
        <div class="stat-box blue">
            <h4>Net Book Value</h4>
            <div class="value">R <?php echo number_format($stats['net_book_value'], 0); ?></div>
            <div class="subvalue">After depreciation</div>
        </div>
    </div>

    <!-- Recognition Status -->
    <div class="stat-card">
        <h3>Recognition Status (GRAP 103 para 7-21)</h3>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <h4>Recognised: <?php echo $stats['recognised']; ?></h4>
                <div class="progress-bar">
                    <?php 
                    $recognisedPct = $stats['total_objects'] > 0 ? 
                        ($stats['recognised'] / $stats['total_objects']) * 100 : 0;
                    ?>
                    <div class="progress-fill green" style="width: <?php echo $recognisedPct; ?>%">
                        <?php echo round($recognisedPct, 1); ?>%
                    </div>
                </div>
            </div>
            
            <div>
                <h4>Not Recognised: <?php echo $stats['not_recognised']; ?></h4>
                <div class="progress-bar">
                    <?php 
                    $notRecognisedPct = $stats['total_objects'] > 0 ? 
                        ($stats['not_recognised'] / $stats['total_objects']) * 100 : 0;
                    ?>
                    <div class="progress-fill orange" style="width: <?php echo $notRecognisedPct; ?>%">
                        <?php echo round($notRecognisedPct, 1); ?>%
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Asset Class Breakdown -->
    <div class="stat-card">
        <h3>Asset Classification</h3>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Asset Class</th>
                    <th>Count</th>
                    <th>Total Value</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $classLabels = [
                    'heritage_asset' => 'Heritage Asset',
                    'operational_asset' => 'Operational Asset',
                    'investment' => 'Investment'
                ];
                
                foreach ($assetClassBreakdown as $class => $data): 
                    if (empty($class)) continue;
                    $pct = $stats['total_objects'] > 0 ? ($data->count / $stats['total_objects']) * 100 : 0;
                ?>
                <tr>
                    <td><strong><?php echo isset($classLabels[$class]) ? $classLabels[$class] : htmlspecialchars($class); ?></strong></td>
                    <td><?php echo number_format($data->count); ?></td>
                    <td>R <?php echo number_format($data->total_value, 2); ?></td>
                    <td>
                        <div class="progress-bar" style="height: 20px;">
                            <div class="progress-fill" style="width: <?php echo $pct; ?>%">
                                <?php echo round($pct, 1); ?>%
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Depreciation Analysis -->
    <div class="stat-card">
        <h3>Depreciation Analysis (GRAP 103 para 22-28)</h3>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 20px;">
            <div style="text-align: center; padding: 15px; background: #f5f5f5; border-radius: 8px;">
                <div style="font-size: 24px; font-weight: bold; color: #667eea;">
                    R <?php echo number_format($stats['total_accumulated_depreciation'], 0); ?>
                </div>
                <div style="font-size: 14px; color: #666;">Total Accumulated Depreciation</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #f5f5f5; border-radius: 8px;">
                <div style="font-size: 24px; font-weight: bold; color: #11998e;">
                    R <?php echo number_format($stats['total_value'], 0); ?>
                </div>
                <div style="font-size: 14px; color: #666;">Gross Asset Value</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #f5f5f5; border-radius: 8px;">
                <div style="font-size: 24px; font-weight: bold; color: #f5576c;">
                    R <?php echo number_format($stats['net_book_value'], 0); ?>
                </div>
                <div style="font-size: 14px; color: #666;">Net Book Value</div>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Depreciation Policy</th>
                    <th>Count</th>
                    <th>Total Depreciation</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $depLabels = [
                    'not_depreciated' => 'Not Depreciated (Heritage Asset)',
                    'depreciated' => 'Depreciated (Operational Asset)'
                ];
                
                foreach ($depreciationStats as $policy => $data): 
                    if (empty($policy)) continue;
                ?>
                <tr>
                    <td><?php echo isset($depLabels[$policy]) ? $depLabels[$policy] : htmlspecialchars($policy); ?></td>
                    <td><?php echo number_format($data->count); ?></td>
                    <td>R <?php echo number_format($data->total_depreciation, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Revaluation Status -->
    <div class="stat-card">
        <h3>Revaluation Status (GRAP 103 para 29-39)</h3>
        
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px;">
            <div style="text-align: center; padding: 15px; background: #f5f5f5; border-radius: 8px;">
                <div style="font-size: 20px; font-weight: bold;">
                    <?php echo number_format($revaluationStats['with_revaluation']); ?>
                </div>
                <div style="font-size: 12px; color: #666;">With Revaluation</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #f5f5f5; border-radius: 8px;">
                <div style="font-size: 20px; font-weight: bold;">
                    <?php echo number_format($revaluationStats['without_revaluation']); ?>
                </div>
                <div style="font-size: 12px; color: #666;">No Revaluation</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #f5f5f5; border-radius: 8px;">
                <div style="font-size: 20px; font-weight: bold;">
                    <?php echo number_format($revaluationStats['revalued_last_year']); ?>
                </div>
                <div style="font-size: 12px; color: #666;">Revalued Last Year</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #f5f5f5; border-radius: 8px;">
                <div style="font-size: 20px; font-weight: bold;">
                    R <?php echo number_format($revaluationStats['total_revaluation_amount'], 0); ?>
                </div>
                <div style="font-size: 12px; color: #666;">Total Revaluation</div>
            </div>
        </div>
    </div>

    <!-- Insurance Coverage -->
    <div class="stat-card">
        <h3>Insurance Coverage Analysis</h3>
        
        <?php if ($insuranceStats['gap'] > 0): ?>
        <div class="alert-warning">
            <strong>⚠️ Insurance Gap Identified:</strong> 
            R <?php echo number_format($insuranceStats['gap'], 2); ?> under-insured across 
            <?php echo $insuranceStats['underinsured_count']; ?> asset(s)
        </div>
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-top: 20px;">
            <div style="text-align: center; padding: 15px; background: #f5f5f5; border-radius: 8px;">
                <div style="font-size: 20px; font-weight: bold; color: #dc3545;">
                    R <?php echo number_format($insuranceStats['total_required'], 0); ?>
                </div>
                <div style="font-size: 12px; color: #666;">Required Coverage</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #f5f5f5; border-radius: 8px;">
                <div style="font-size: 20px; font-weight: bold; color: #28a745;">
                    R <?php echo number_format($insuranceStats['total_actual'], 0); ?>
                </div>
                <div style="font-size: 12px; color: #666;">Actual Coverage</div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #f5f5f5; border-radius: 8px;">
                <div style="font-size: 20px; font-weight: bold; color: <?php echo $insuranceStats['gap'] > 0 ? '#dc3545' : '#28a745'; ?>;">
                    R <?php echo number_format(abs($insuranceStats['gap']), 0); ?>
                </div>
                <div style="font-size: 12px; color: #666;">
                    <?php echo $insuranceStats['gap'] > 0 ? 'Shortfall' : 'Surplus'; ?>
                </div>
            </div>
        </div>

        <div style="margin-top: 20px;">
            <div class="progress-bar" style="height: 40px;">
                <?php 
                $coveragePct = $insuranceStats['total_required'] > 0 ? 
                    ($insuranceStats['total_actual'] / $insuranceStats['total_required']) * 100 : 0;
                $coveragePct = min($coveragePct, 100);
                $colorClass = $coveragePct >= 90 ? 'green' : 'orange';
                ?>
                <div class="progress-fill <?php echo $colorClass; ?>" style="width: <?php echo $coveragePct; ?>%">
                    <?php echo round($coveragePct, 1); ?>% Coverage
                </div>
            </div>
        </div>
    </div>

    <!-- Top Valued Assets -->
    <div class="stat-card">
        <h3>Top 10 Most Valuable Assets</h3>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Asset Class</th>
                    <th>Initial Value</th>
                    <th>Depreciation</th>
                    <th>Net Book Value</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topValuedAssets as $asset): ?>
                <tr>
                    <td><?php echo htmlspecialchars($asset->title ?? 'Untitled'); ?></td>
                    <td>
                        <?php 
                        $classLabels = ['heritage_asset' => 'Heritage', 'operational_asset' => 'Operational'];
                        echo isset($classLabels[$asset->asset_class]) ? $classLabels[$asset->asset_class] : htmlspecialchars($asset->asset_class ?? '');
                        ?>
                    </td>
                    <td>R <?php echo number_format($asset->initial_recognition_value, 2); ?></td>
                    <td>R <?php echo number_format($asset->accumulated_depreciation ?? 0, 2); ?></td>
                    <td>
                        <strong>R <?php echo number_format(
                            $asset->initial_recognition_value - ($asset->accumulated_depreciation ?? 0), 2
                        ); ?></strong>
                    </td>
                    <td>
                        <a href="<?php echo cco_view_url($asset); ?>" class="btn btn-sm btn-primary">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Objects Needing Revaluation -->
    <?php if (count($needsRevaluation) > 0): ?>
    <div class="stat-card">
        <h3>⚠️ Assets Needing Revaluation (>3 years)</h3>
        
        <div class="alert-warning">
            <strong>Action Required:</strong> The following assets use the revaluation model 
            but have not been revalued in over 3 years.
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Last Revaluation</th>
                    <th>Current Value</th>
                    <th>Days Overdue</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($needsRevaluation as $asset): ?>
                <tr>
                    <td><?php echo htmlspecialchars($asset->title ?? 'Untitled'); ?></td>
                    <td>
                        <?php 
                        if ($asset->last_revaluation_date) {
                            echo htmlspecialchars($asset->last_revaluation_date);
                        } else {
                            echo '<span class="badge badge-danger">Never</span>';
                        }
                        ?>
                    </td>
                    <td>R <?php echo number_format($asset->initial_recognition_value, 2); ?></td>
                    <td>
                        <?php 
                        $lastDate = $asset->last_revaluation_date ? 
                            strtotime($asset->last_revaluation_date) : 
                            strtotime('-10 years');
                        $daysOverdue = floor((time() - $lastDate) / 86400) - (3 * 365);
                        ?>
                        <span class="badge badge-danger"><?php echo number_format($daysOverdue); ?> days</span>
                    </td>
                    <td>
                        <a href="<?php echo cco_edit_url($asset); ?>" class="btn btn-sm btn-warning">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Incomplete Records -->
    <?php if (count($incompleteRecords) > 0): ?>
    <div class="stat-card">
        <h3>📋 Incomplete GRAP Records</h3>
        
        <div class="alert-info">
            <strong>Data Quality:</strong> The following assets are missing required GRAP 103 fields.
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Recognition Status</th>
                    <th>Measurement Basis</th>
                    <th>Asset Class</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($incompleteRecords as $record): ?>
                <tr>
                    <td><?php echo htmlspecialchars($record->title ?? 'Untitled'); ?></td>
                    <td>
                        <?php if ($record->recognition_status): ?>
                            <span class="badge badge-success">✓</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Missing</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($record->measurement_basis): ?>
                            <span class="badge badge-success">✓</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Missing</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($record->asset_class): ?>
                            <span class="badge badge-success">✓</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Missing</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?php echo cco_edit_url($record); ?>" class="btn btn-sm btn-primary">Complete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div>

<?php end_slot(); ?>