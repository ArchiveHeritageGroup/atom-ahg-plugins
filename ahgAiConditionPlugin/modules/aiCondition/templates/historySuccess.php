<?php decorate_with('layout_2col.php') ?>

<?php
require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgAiConditionPlugin/lib/Helpers/AiConditionHelper.php';
use ahgAiConditionPlugin\Helpers\AiConditionHelper;
?>

<?php slot('sidebar') ?>
<div class="sidebar-content">
    <div class="card mb-3">
        <div class="card-header py-2"><h6 class="mb-0"><?php echo __('Object Info') ?></h6></div>
        <div class="card-body py-2 small">
            <strong><?php echo esc_entities($objectTitle) ?></strong>
            <div class="mt-2">
                <a href="/<?php echo esc_entities($slug) ?>" class="btn btn-sm btn-outline-primary w-100 mb-1">
                    <i class="fas fa-external-link-alt me-1"></i><?php echo __('View Object') ?>
                </a>
                <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'assess']) ?>?object_id=<?php echo $objectId ?>" class="btn btn-sm btn-success w-100">
                    <i class="fas fa-camera me-1"></i><?php echo __('New Assessment') ?>
                </a>
            </div>
        </div>
    </div>
</div>
<?php end_slot() ?>

<?php slot('title') ?>
<h1 class="h3 mb-0"><i class="fas fa-chart-line me-2"></i><?php echo __('Condition History') ?></h1>
<p class="text-muted small mb-3"><?php echo esc_entities($objectTitle) ?></p>
<?php end_slot() ?>

<?php slot('content') ?>
<?php if (empty($history)): ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    <?php echo __('No condition assessments found for this object.') ?>
    <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'assess']) ?>?object_id=<?php echo $objectId ?>" class="btn btn-sm btn-success ms-2">
        <?php echo __('Run assessment') ?>
    </a>
</div>
<?php else: ?>
<!-- Chart -->
<div class="card mb-3">
    <div class="card-body">
        <canvas id="historyChart" height="80"></canvas>
    </div>
</div>

<!-- History table -->
<div class="table-responsive">
    <table class="table table-sm table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th><?php echo __('Date') ?></th>
                <th class="text-center"><?php echo __('Score') ?></th>
                <th class="text-center"><?php echo __('Grade') ?></th>
                <th class="text-center"><?php echo __('Damages') ?></th>
                <th class="text-end"><?php echo __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_reverse($history) as $h): ?>
            <tr>
                <td class="small"><?php echo date('Y-m-d H:i', strtotime($h->assessed_at)) ?></td>
                <td class="text-center"><?php echo AiConditionHelper::scoreDisplay($h->score) ?></td>
                <td class="text-center"><?php echo AiConditionHelper::gradeBadge($h->condition_grade) ?></td>
                <td class="text-center"><span class="badge bg-secondary"><?php echo $h->damage_count ?></span></td>
                <td class="text-end">
                    <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'view', 'id' => $h->assessment_id]) ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
var historyData = <?php echo json_encode(array_map(function($h) {
    return ['date' => date('M d, Y', strtotime($h->assessed_at)), 'score' => (float) $h->score, 'damages' => (int) $h->damage_count];
}, $history)) ?>;

new Chart(document.getElementById('historyChart'), {
    type: 'line',
    data: {
        labels: historyData.map(function(h) { return h.date; }),
        datasets: [{
            label: 'Condition Score',
            data: historyData.map(function(h) { return h.score; }),
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13,110,253,0.1)',
            fill: true,
            tension: 0.3
        }, {
            label: 'Damage Count',
            data: historyData.map(function(h) { return h.damages; }),
            borderColor: '#dc3545',
            borderDash: [5,5],
            yAxisID: 'y1',
            fill: false
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { min: 0, max: 100, title: { display: true, text: 'Score' } },
            y1: { position: 'right', min: 0, title: { display: true, text: 'Damages' }, grid: { drawOnChartArea: false } }
        }
    }
});
</script>
<?php endif ?>
<?php end_slot() ?>
