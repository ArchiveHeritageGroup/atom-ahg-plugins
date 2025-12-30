<?php
// Unescape arrays from Symfony output escaper
$data = sfOutputEscaper::unescape($data ?? []);
$reportType = $reportType ?? 'summary';
?>

<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'staticpage', 'action' => 'static', 'id' => 'homepage']); ?>"><?php echo __('Home'); ?></a></li>
                <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'index']); ?>"><?php echo __('Rights Management'); ?></a></li>
                <li class="breadcrumb-item active"><?php echo __('Report'); ?></li>
            </ol>
        </nav>
        <div>
            <a href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'report', 'type' => $reportType, 'export' => 'csv']); ?>" class="btn btn-outline-success btn-sm me-2">
                <i class="fas fa-file-csv me-1"></i><?php echo __('Export CSV'); ?>
            </a>
            <a href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i><?php echo __('Back'); ?>
            </a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">
                <i class="fas fa-chart-bar me-2"></i>
                <?php 
                switch ($reportType) {
                    case 'embargoes': echo __('Embargoes Report'); break;
                    case 'orphan_works': echo __('Orphan Works Report'); break;
                    case 'tk_labels': echo __('TK Labels Report'); break;
                    default: echo __('Rights Summary Report');
                }
                ?>
            </h4>
        </div>
        
        <div class="card-body">
            <!-- Report Type Tabs -->
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link <?php echo $reportType === 'summary' ? 'active' : ''; ?>" href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'report', 'type' => 'summary']); ?>">
                        <?php echo __('Summary'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $reportType === 'embargoes' ? 'active' : ''; ?>" href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'report', 'type' => 'embargoes']); ?>">
                        <?php echo __('Embargoes'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $reportType === 'orphan_works' ? 'active' : ''; ?>" href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'report', 'type' => 'orphan_works']); ?>">
                        <?php echo __('Orphan Works'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $reportType === 'tk_labels' ? 'active' : ''; ?>" href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'report', 'type' => 'tk_labels']); ?>">
                        <?php echo __('TK Labels'); ?>
                    </a>
                </li>
            </ul>
            
            <?php if ($reportType === 'summary'): ?>
            <!-- Summary Statistics -->
            <?php if (is_array($data)): ?>
            <div class="row">
                <?php foreach ($data as $key => $value): ?>
                <?php if (!is_array($value)): ?>
                <div class="col-md-3 mb-3">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <h3 class="mb-0"><?php echo is_numeric($value) ? number_format($value) : $value; ?></h3>
                            <small class="text-muted"><?php echo __(ucwords(str_replace('_', ' ', $key))); ?></small>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <!-- Data Table -->
            <?php if (empty($data) || (is_object($data) && $data->isEmpty())): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i><?php echo __('No data found for this report.'); ?>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <?php 
                            $firstRow = is_array($data) ? reset($data) : $data->first();
                            if ($firstRow):
                                $firstRow = (array) $firstRow;
                                foreach (array_keys($firstRow) as $col): 
                            ?>
                            <th><?php echo __(ucwords(str_replace('_', ' ', $col))); ?></th>
                            <?php endforeach; endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $row): ?>
                        <tr>
                            <?php foreach ((array) $row as $col => $value): ?>
                            <td>
                                <?php
                                if (in_array($col, ['created_at', 'updated_at', 'start_date', 'end_date', 'expiry_date'])) {
                                    echo $value ? date('Y-m-d', strtotime($value)) : '-';
                                } elseif ($col === 'object_title' && isset($row->object_id)) {
                                    echo '<a href="' . url_for(['module' => 'informationobject', 'slug' => $row->slug ?? $row->object_id]) . '">' . htmlspecialchars($value ?? 'Untitled') . '</a>';
                                } elseif (in_array($col, ['status', 'category', 'code'])) {
                                    echo '<span class="badge bg-secondary">' . htmlspecialchars($value ?? '') . '</span>';
                                } else {
                                    echo htmlspecialchars($value ?? '-');
                                }
                                ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
