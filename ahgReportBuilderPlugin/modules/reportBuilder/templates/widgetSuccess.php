<?php
/**
 * Dashboard Widget Template
 *
 * Can be embedded in any page via:
 *   <?php echo get_component('reportBuilder', 'widget', ['id' => 5]) ?>
 */

if (!$widget) {
    return;
}

$config = json_decode($widget->config, true) ?: [];
?>
<div class="report-widget card h-100" data-widget-id="<?php echo $widget->id; ?>" data-widget-type="<?php echo $widget->widget_type; ?>">
    <div class="card-header py-2 d-flex justify-content-between align-items-center bg-white">
        <span class="small fw-bold">
            <?php echo htmlspecialchars($widget->title ?: $widget->report_name ?: 'Widget'); ?>
        </span>
        <?php if ($widget->custom_report_id): ?>
        <a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'preview', 'id' => $widget->custom_report_id]); ?>"
           class="btn btn-link btn-sm p-0 text-muted" title="<?php echo __('View full report'); ?>">
            <i class="bi bi-box-arrow-up-right"></i>
        </a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if ($widget->widget_type === 'stat' || $widget->widget_type === 'count'): ?>
            <!-- Stat/Count Widget -->
            <div class="text-center py-3">
                <h2 class="display-4 mb-0 text-primary">
                    <?php echo number_format($widgetData['value'] ?? 0); ?>
                </h2>
                <p class="text-muted small mb-0">
                    <?php echo htmlspecialchars($config['label'] ?? __('Total Records')); ?>
                </p>
            </div>

        <?php elseif ($widget->widget_type === 'chart'): ?>
            <!-- Chart Widget -->
            <canvas id="widget-chart-<?php echo $widget->id; ?>" height="150"></canvas>
            <script>
            (function() {
                const ctx = document.getElementById('widget-chart-<?php echo $widget->id; ?>');
                if (ctx && typeof Chart !== 'undefined') {
                    new Chart(ctx, {
                        type: '<?php echo $config['chartType'] ?? 'bar'; ?>',
                        data: {
                            labels: <?php echo json_encode($widgetData['labels'] ?? []); ?>,
                            datasets: [{
                                label: '<?php echo addslashes($config['label'] ?? 'Count'); ?>',
                                data: <?php echo json_encode($widgetData['data'] ?? []); ?>,
                                backgroundColor: 'rgba(13, 110, 253, 0.5)',
                                borderColor: 'rgba(13, 110, 253, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: true } }
                        }
                    });
                }
            })();
            </script>

        <?php elseif ($widget->widget_type === 'table'): ?>
            <!-- Table Widget -->
            <?php if (empty($widgetData['results'])): ?>
                <p class="text-muted text-center py-3"><?php echo __('No data available'); ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <?php foreach ($widgetData['columns'] as $col): ?>
                                <th class="small"><?php echo htmlspecialchars($widgetData['allColumns'][$col]['label'] ?? $col); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($widgetData['results'] as $row): ?>
                            <tr>
                                <?php foreach ($widgetData['columns'] as $col): ?>
                                <td class="small">
                                    <?php
                                    $value = $row->{$col} ?? '';
                                    if (strlen($value) > 30) {
                                        $value = substr($value, 0, 30) . '...';
                                    }
                                    echo htmlspecialchars($value);
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
