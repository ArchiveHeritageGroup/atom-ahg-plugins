<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($report->name ?? 'Shared Report'); ?> - AtoM Report Builder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"
          <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet"
          <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
    <style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
        body { background-color: #f8f9fa; }
        .report-header { background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); }
        @media print {
            .no-print { display: none !important; }
            body { background-color: #fff; }
        }
    </style>
</head>
<body>
<?php
$rawReport = isset($sf_data) ? $sf_data->getRaw('report') : $report;
$rawSections = isset($sf_data) ? $sf_data->getRaw('sections') : (isset($sections) ? $sections : []);
$rawReportData = isset($sf_data) ? $sf_data->getRaw('reportData') : (isset($reportData) ? $reportData : []);
$rawAllColumns = isset($sf_data) ? $sf_data->getRaw('allColumns') : (isset($allColumns) ? $allColumns : []);
$rawShare = isset($sf_data) ? $sf_data->getRaw('share') : (isset($share) ? $share : null);

// Check if share is expired or inactive
$isExpired = false;
$isInactive = false;
if ($rawShare) {
    if (!empty($rawShare->expires_at) && strtotime($rawShare->expires_at) < time()) {
        $isExpired = true;
    }
    if (isset($rawShare->is_active) && !$rawShare->is_active) {
        $isInactive = true;
    }
}
?>

<?php if ($isExpired || $isInactive): ?>
<!-- Expired / Inactive Share -->
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card text-center">
                <div class="card-body py-5">
                    <i class="bi bi-shield-lock fs-1 text-muted d-block mb-3"></i>
                    <h4 class="text-muted"><?php echo __('This shared report is no longer available'); ?></h4>
                    <p class="text-muted mb-0">
                        <?php if ($isExpired): ?>
                            <?php echo __('The share link has expired.'); ?>
                        <?php else: ?>
                            <?php echo __('The share link has been deactivated.'); ?>
                        <?php endif; ?>
                    </p>
                    <p class="text-muted small mt-3"><?php echo __('Please contact the report owner for access.'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<!-- Report Header -->
<div class="report-header text-white py-4 mb-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h2 class="mb-1"><i class="bi bi-file-earmark-bar-graph me-2"></i><?php echo htmlspecialchars($rawReport->name); ?></h2>
                <?php if (!empty($rawReport->description)): ?>
                <p class="mb-0 opacity-75"><?php echo htmlspecialchars($rawReport->description); ?></p>
                <?php endif; ?>
                <small class="opacity-50">
                    <?php echo __('Generated:'); ?> <?php echo date('Y-m-d H:i'); ?>
                </small>
            </div>
            <div class="no-print">
                <button class="btn btn-outline-light btn-sm" onclick="window.print();">
                    <i class="bi bi-printer me-1"></i><?php echo __('Print'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Report Content -->
<div class="container pb-5">
    <?php if (empty($rawSections)): ?>
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
            <?php echo __('This report has no content sections.'); ?>
        </div>
    </div>
    <?php else: ?>
        <?php foreach ($rawSections as $section): ?>
        <?php
        $sectionType = $section->type ?? $section->section_type ?? 'narrative';
        $sectionTitle = $section->title ?? '';
        ?>
        <div class="card mb-4">
            <?php if (!empty($sectionTitle)): ?>
            <div class="card-header bg-white">
                <h5 class="mb-0"><?php echo htmlspecialchars($sectionTitle); ?></h5>
            </div>
            <?php endif; ?>
            <div class="card-body">
                <?php if ($sectionType === 'narrative'): ?>
                    <!-- Narrative Section: render as raw HTML -->
                    <div class="report-narrative">
                        <?php echo $section->content ?? ''; ?>
                    </div>

                <?php elseif ($sectionType === 'table'): ?>
                    <!-- Table Section -->
                    <?php
                    $sectionData = isset($rawReportData[$section->id]) ? $rawReportData[$section->id] : [];
                    $sectionColumns = is_array($section->columns) ? $section->columns : ($rawReport->columns ?? []);
                    ?>
                    <?php if (!empty($sectionData)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <?php foreach ($sectionColumns as $col): ?>
                                    <th class="text-nowrap small"><?php echo $rawAllColumns[$col]['label'] ?? $col; ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sectionData as $row): ?>
                                <tr>
                                    <?php foreach ($sectionColumns as $col): ?>
                                    <td class="small">
                                        <?php
                                        $value = is_object($row) ? ($row->{$col} ?? '') : ($row[$col] ?? '');
                                        $colType = $rawAllColumns[$col]['type'] ?? 'string';
                                        if ($colType === 'datetime' && $value) {
                                            echo date('Y-m-d H:i', strtotime($value));
                                        } elseif ($colType === 'date' && $value) {
                                            echo date('Y-m-d', strtotime($value));
                                        } elseif ($colType === 'boolean') {
                                            echo $value ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-x-circle text-muted"></i>';
                                        } elseif ($colType === 'text' && strlen($value) > 150) {
                                            echo htmlspecialchars(substr($value, 0, 150)) . '...';
                                        } else {
                                            echo htmlspecialchars($value);
                                        }
                                        ?>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted text-center mb-0"><?php echo __('No data available for this section.'); ?></p>
                    <?php endif; ?>

                <?php elseif ($sectionType === 'chart'): ?>
                    <!-- Chart Section -->
                    <div class="text-center">
                        <canvas id="sharedChart_<?php echo $section->id; ?>" height="300"></canvas>
                    </div>

                <?php elseif ($sectionType === 'stat' || $sectionType === 'statistic'): ?>
                    <!-- Statistic Section -->
                    <?php $statValue = isset($rawReportData[$section->id]) ? $rawReportData[$section->id] : '--'; ?>
                    <div class="text-center py-3">
                        <h1 class="display-4 mb-0"><?php echo is_numeric($statValue) ? number_format($statValue) : htmlspecialchars($statValue); ?></h1>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($section->label ?? __('Total Records')); ?></p>
                    </div>

                <?php elseif ($sectionType === 'image'): ?>
                    <!-- Image Section -->
                    <?php if (!empty($section->image_url)): ?>
                    <div class="text-center">
                        <img src="<?php echo htmlspecialchars($section->image_url); ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($sectionTitle); ?>" style="max-height:500px;">
                    </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- Unknown Section Type -->
                    <p class="text-muted"><?php echo htmlspecialchars($section->content ?? ''); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Footer -->
<footer class="py-4 bg-white border-top text-center">
    <div class="container">
        <small class="text-muted">
            <i class="bi bi-bar-chart-line me-1"></i><?php echo __('Powered by AtoM Report Builder'); ?>
        </small>
    </div>
</footer>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
        <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>></script>
<?php
// Render charts if Chart.js data is available
$chartSections = [];
if (!empty($rawSections)) {
    foreach ($rawSections as $s) {
        $sType = $s->type ?? $s->section_type ?? '';
        if ($sType === 'chart' && isset($rawReportData[$s->id])) {
            $chartSections[$s->id] = $rawReportData[$s->id];
        }
    }
}
if (!empty($chartSections)):
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"
        <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>></script>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var chartData = <?php echo json_encode($chartSections); ?>;
    var colors = [
        'rgba(13, 110, 253, 0.7)', 'rgba(25, 135, 84, 0.7)', 'rgba(220, 53, 69, 0.7)',
        'rgba(255, 193, 7, 0.7)', 'rgba(13, 202, 240, 0.7)', 'rgba(111, 66, 193, 0.7)',
        'rgba(253, 126, 20, 0.7)', 'rgba(108, 117, 125, 0.7)'
    ];

    for (var sectionId in chartData) {
        var canvas = document.getElementById('sharedChart_' + sectionId);
        if (!canvas || typeof Chart === 'undefined') continue;

        var data = chartData[sectionId];
        var chartType = data.type || 'bar';
        var labels = data.labels || [];
        var values = data.values || data.data || [];

        new Chart(canvas, {
            type: chartType === 'horizontalBar' ? 'bar' : (chartType === 'area' ? 'line' : chartType),
            data: {
                labels: labels,
                datasets: [{
                    label: data.label || 'Count',
                    data: values,
                    backgroundColor: colors,
                    borderColor: colors.map(function(c) { return c.replace(/[\d.]+\)$/, '1)'); }),
                    borderWidth: 1,
                    fill: chartType === 'area'
                }]
            },
            options: {
                responsive: true,
                indexAxis: chartType === 'horizontalBar' ? 'y' : 'x',
                plugins: {
                    legend: { display: (chartType === 'pie' || chartType === 'doughnut') },
                    title: { display: !!data.title, text: data.title || '' }
                },
                scales: (chartType === 'pie' || chartType === 'doughnut') ? {} : {
                    y: { beginAtZero: true }
                }
            }
        });
    }
});
</script>
<?php endif; ?>
</body>
</html>
