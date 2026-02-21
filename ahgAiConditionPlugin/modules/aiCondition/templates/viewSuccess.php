<?php decorate_with('layout_2col.php') ?>

<?php
require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgAiConditionPlugin/lib/Helpers/AiConditionHelper.php';
?>

<?php slot('sidebar') ?>
<div class="sidebar-content">
    <div class="card mb-3">
        <div class="card-header py-2"><h6 class="mb-0"><?php echo __('Assessment Details') ?></h6></div>
        <div class="card-body py-2 small">
            <div class="mb-2">
                <strong><?php echo __('Score') ?></strong><br>
                <span class="fs-4"><?php echo \ahgAiConditionPlugin\Helpers\AiConditionHelper::scoreDisplay($assessment->overall_score) ?></span>
            </div>
            <div class="mb-2">
                <strong><?php echo __('Grade') ?></strong><br>
                <?php echo \ahgAiConditionPlugin\Helpers\AiConditionHelper::gradeBadge($assessment->condition_grade) ?>
            </div>
            <div class="mb-2">
                <strong><?php echo __('Damages') ?></strong><br>
                <span class="badge bg-secondary"><?php echo $assessment->damage_count ?></span>
            </div>
            <div class="mb-2">
                <strong><?php echo __('Date') ?></strong><br>
                <?php echo date('Y-m-d H:i', strtotime($assessment->created_at)) ?>
            </div>
            <div class="mb-2">
                <strong><?php echo __('Assessor') ?></strong><br>
                <?php echo esc_entities($assessment->assessor_name ?? 'System') ?>
            </div>
            <div class="mb-2">
                <strong><?php echo __('Status') ?></strong><br>
                <?php if ($assessment->is_confirmed): ?>
                <span class="badge bg-success"><i class="fas fa-check me-1"></i><?php echo __('Confirmed') ?></span>
                <?php else: ?>
                <span class="badge bg-warning text-dark"><?php echo __('Pending Review') ?></span>
                <?php endif ?>
            </div>
            <?php if ($assessment->processing_time_ms): ?>
            <div class="mb-2">
                <strong><?php echo __('Processing') ?></strong><br>
                <?php echo number_format($assessment->processing_time_ms) ?>ms
            </div>
            <?php endif ?>
            <hr>
            <?php if (!$assessment->is_confirmed): ?>
            <button type="button" class="btn btn-success btn-sm w-100 mb-2" onclick="confirmAssessment(<?php echo $assessment->id ?>)">
                <i class="fas fa-check me-1"></i><?php echo __('Confirm') ?>
            </button>
            <?php endif ?>
            <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm w-100">
                <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to List') ?>
            </a>
        </div>
    </div>
</div>
<?php end_slot() ?>

<?php slot('title') ?>
<h1 class="h3 mb-0">
    <i class="fas fa-eye me-2"></i>
    <?php echo __('Assessment') ?> #<?php echo $assessment->id ?>
    <?php if ($assessment->object_title): ?>
    — <?php echo esc_entities(mb_strimwidth($assessment->object_title, 0, 60, '...')) ?>
    <?php endif ?>
</h1>
<p class="text-muted small mb-3"><?php echo __('AI damage detection results') ?></p>
<?php end_slot() ?>

<?php slot('content') ?>
<div class="row">
    <!-- Left: Image with overlay -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header py-2">
                <h6 class="mb-0"><?php echo __('Damage Detection Overlay') ?></h6>
            </div>
            <div class="card-body text-center p-2">
                <?php if ($assessment->overlay_path && file_exists(sfConfig::get('sf_upload_dir') . '/' . $assessment->overlay_path)): ?>
                <img src="/uploads/<?php echo esc_entities($assessment->overlay_path) ?>" alt="Damage overlay" class="img-fluid rounded">
                <?php elseif ($assessment->image_path): ?>
                <div class="position-relative d-inline-block">
                    <img src="/uploads/<?php echo esc_entities($assessment->image_path) ?>" alt="Original" class="img-fluid rounded" id="baseImage">
                    <canvas id="overlayCanvas" class="position-absolute top-0 start-0" style="pointer-events:none"></canvas>
                </div>
                <?php else: ?>
                <div class="alert alert-secondary"><?php echo __('No image available') ?></div>
                <?php endif ?>
            </div>
        </div>
    </div>

    <!-- Right: Damage list -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header py-2">
                <h6 class="mb-0"><?php echo __('Detected Damages') ?> (<?php echo count($assessment->damages) ?>)</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($assessment->damages)): ?>
                <div class="p-3 text-center text-success">
                    <i class="fas fa-check-circle fa-2x mb-2"></i><br>
                    <?php echo __('No damage detected') ?>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($assessment->damages as $dmg): ?>
                    <div class="list-group-item py-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="badge" style="background-color:<?php echo \ahgAiConditionPlugin\Helpers\AiConditionHelper::damageColor($dmg->damage_type) ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $dmg->damage_type)) ?>
                                </span>
                                <?php echo \ahgAiConditionPlugin\Helpers\AiConditionHelper::severityBadge($dmg->severity) ?>
                            </div>
                            <div class="text-end small">
                                <?php echo \ahgAiConditionPlugin\Helpers\AiConditionHelper::confidenceBar($dmg->confidence) ?>
                            </div>
                        </div>
                        <?php if ($dmg->area_percent): ?>
                        <div class="small text-muted mt-1">
                            <?php echo __('Area') ?>: <?php echo number_format($dmg->area_percent, 1) ?>%
                            <?php if ($dmg->score_deduction): ?>
                            | <?php echo __('Deduction') ?>: -<?php echo number_format($dmg->score_deduction, 1) ?>
                            <?php endif ?>
                            <?php if ($dmg->location_zone): ?>
                            | <?php echo ucfirst(str_replace('_', ' ', $dmg->location_zone)) ?>
                            <?php endif ?>
                        </div>
                        <?php endif ?>
                        <?php if ($dmg->description): ?>
                        <div class="small text-muted mt-1"><?php echo esc_entities($dmg->description) ?></div>
                        <?php endif ?>
                    </div>
                    <?php endforeach ?>
                </div>
                <?php endif ?>
            </div>
        </div>

        <!-- Recommendations -->
        <?php if ($assessment->recommendations): ?>
        <div class="card mt-3">
            <div class="card-header py-2"><h6 class="mb-0"><?php echo __('Recommendations') ?></h6></div>
            <div class="card-body small">
                <?php echo nl2br(esc_entities($assessment->recommendations)) ?>
            </div>
        </div>
        <?php endif ?>
    </div>
</div>

<?php if ($assessment->image_path && !empty($assessment->damages)): ?>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
// Draw bounding boxes on canvas overlay
var baseImg = document.getElementById('baseImage');
if (baseImg) {
    baseImg.onload = function() {
        var canvas = document.getElementById('overlayCanvas');
        if (!canvas) return;
        canvas.width = baseImg.naturalWidth;
        canvas.height = baseImg.naturalHeight;
        canvas.style.width = baseImg.clientWidth + 'px';
        canvas.style.height = baseImg.clientHeight + 'px';
        var ctx = canvas.getContext('2d');

        var damages = <?php echo json_encode(array_map(function($d) {
            return [
                'type' => $d->damage_type,
                'x' => $d->bbox_x, 'y' => $d->bbox_y,
                'w' => $d->bbox_w, 'h' => $d->bbox_h,
                'color' => \ahgAiConditionPlugin\Helpers\AiConditionHelper::damageColor($d->damage_type),
                'conf' => round($d->confidence * 100),
            ];
        }, $assessment->damages)) ?>;

        damages.forEach(function(d) {
            if (d.x == null || d.y == null) return;
            ctx.strokeStyle = d.color;
            ctx.lineWidth = 3;
            ctx.strokeRect(d.x, d.y, d.w, d.h);
            ctx.fillStyle = d.color;
            ctx.globalAlpha = 0.15;
            ctx.fillRect(d.x, d.y, d.w, d.h);
            ctx.globalAlpha = 1;
            ctx.fillStyle = d.color;
            ctx.font = 'bold 14px sans-serif';
            ctx.fillText(d.type.replace('_',' ') + ' ' + d.conf + '%', d.x + 4, d.y - 4);
        });
    };
    if (baseImg.complete) baseImg.onload();
}

function confirmAssessment(id) {
    fetch('<?php echo url_for(['module' => 'aiCondition', 'action' => 'apiConfirm']) ?>?id=' + id, {method:'POST'})
    .then(function(r){return r.json()})
    .then(function(d){ if(d.success) location.reload(); });
}
</script>
<?php endif ?>
<?php end_slot() ?>
