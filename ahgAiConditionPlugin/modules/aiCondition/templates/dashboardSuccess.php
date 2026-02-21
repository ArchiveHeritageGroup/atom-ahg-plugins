<?php decorate_with('layout_2col.php') ?>

<?php slot('sidebar') ?>
<div class="sidebar-content">
    <div class="card mb-3">
        <div class="card-header bg-success text-white py-2">
            <h6 class="mb-0"><i class="fas fa-robot me-1"></i><?php echo __('AI Condition') ?></h6>
        </div>
        <div class="card-body py-2">
            <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'assess']) ?>" class="btn btn-success btn-sm w-100 mb-2">
                <i class="fas fa-camera me-1"></i><?php echo __('New AI Assessment') ?>
            </a>
            <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'manualAssess']) ?>" class="btn btn-primary btn-sm w-100 mb-2">
                <i class="fas fa-clipboard-check me-1"></i><?php echo __('Manual Assessment') ?>
            </a>
            <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'bulk']) ?>" class="btn btn-outline-primary btn-sm w-100 mb-2">
                <i class="fas fa-layer-group me-1"></i><?php echo __('Bulk Scan') ?>
            </a>
            <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'browse']) ?>" class="btn btn-outline-secondary btn-sm w-100 mb-2">
                <i class="fas fa-list me-1"></i><?php echo __('Browse Assessments') ?>
            </a>
            <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'training']) ?>" class="btn btn-outline-info btn-sm w-100 mb-2">
                <i class="fas fa-brain me-1"></i><?php echo __('Model Training') ?>
            </a>
            <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm w-100">
                <i class="fas fa-cog me-1"></i><?php echo __('Settings') ?>
            </a>
        </div>
    </div>
</div>
<?php end_slot() ?>

<?php slot('title') ?>
<h1 class="h3 mb-0"><i class="fas fa-tachometer-alt me-2"></i><?php echo __('AI Condition Dashboard') ?></h1>
<p class="text-muted small mb-3"><?php echo __('Assessment statistics, grade distribution, and trends') ?></p>
<?php end_slot() ?>

<?php slot('content') ?>

<!-- Summary Cards Row -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center border-success">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-success"><?php echo $stats['total'] ?? 0 ?></div>
                <small class="text-muted"><?php echo __('Total Assessments') ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center border-primary">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-primary"><?php echo $stats['confirmed'] ?? 0 ?></div>
                <small class="text-muted"><?php echo __('Confirmed') ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center border-warning">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-warning"><?php echo $stats['pending'] ?? 0 ?></div>
                <small class="text-muted"><?php echo __('Pending Review') ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center border-info">
            <div class="card-body py-3">
                <?php
                $avgScore = $stats['avg_score'] ?? 0;
                $scoreColor = $avgScore >= 80 ? 'success' : ($avgScore >= 60 ? 'info' : ($avgScore >= 40 ? 'warning' : 'danger'));
                ?>
                <div class="fs-2 fw-bold text-<?php echo $scoreColor ?>"><?php echo $avgScore ?></div>
                <small class="text-muted"><?php echo __('Average Score') ?></small>
            </div>
        </div>
    </div>
</div>

<!-- Grade Distribution + Source Breakdown -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header py-2"><h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i><?php echo __('Grade Distribution') ?></h6></div>
            <div class="card-body">
                <?php
                $gradeColors = ['excellent' => 'success', 'good' => 'info', 'fair' => 'warning', 'poor' => 'danger', 'critical' => 'dark'];
                $gradeIcons = ['excellent' => 'fa-check-circle', 'good' => 'fa-thumbs-up', 'fair' => 'fa-exclamation-triangle', 'poor' => 'fa-times-circle', 'critical' => 'fa-skull-crossbones'];
                $byGrade = $stats['by_grade'] ?? [];
                $totalAssessments = max(1, $stats['total'] ?? 1);
                foreach (['excellent', 'good', 'fair', 'poor', 'critical'] as $grade):
                    $count = $byGrade[$grade] ?? 0;
                    $pct = round(($count / $totalAssessments) * 100);
                    $color = $gradeColors[$grade] ?? 'secondary';
                    $icon = $gradeIcons[$grade] ?? 'fa-question';
                ?>
                <div class="d-flex align-items-center mb-3">
                    <span class="badge bg-<?php echo $color ?> me-2" style="min-width:100px">
                        <i class="fas <?php echo $icon ?> me-1"></i><?php echo ucfirst($grade) ?>
                    </span>
                    <div class="progress flex-grow-1" style="height:12px">
                        <div class="progress-bar bg-<?php echo $color ?>" style="width:<?php echo $pct ?>%"></div>
                    </div>
                    <span class="ms-2 fw-bold" style="min-width:40px"><?php echo $count ?></span>
                    <span class="ms-1 text-muted small">(<?php echo $pct ?>%)</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header py-2"><h6 class="mb-0"><i class="fas fa-tags me-2"></i><?php echo __('Assessment Sources') ?></h6></div>
            <div class="card-body">
                <?php
                $sourceColors = [
                    'manual' => 'secondary', 'manual_entry' => 'dark', 'bulk' => 'primary',
                    'auto' => 'success', 'api' => 'info',
                ];
                $sourceIcons = [
                    'manual' => 'fa-camera', 'manual_entry' => 'fa-clipboard-check', 'bulk' => 'fa-layer-group',
                    'auto' => 'fa-magic', 'api' => 'fa-plug',
                ];
                if (empty($sourceBreakdown)): ?>
                    <p class="text-muted text-center py-3"><?php echo __('No assessments yet.') ?></p>
                <?php else:
                    foreach ($sourceBreakdown as $src):
                        $srcPct = round(($src->count / $totalAssessments) * 100);
                        $srcColor = $sourceColors[$src->source] ?? 'secondary';
                        $srcIcon = $sourceIcons[$src->source] ?? 'fa-question';
                ?>
                <div class="d-flex align-items-center mb-3">
                    <span class="badge bg-<?php echo $srcColor ?> me-2" style="min-width:120px">
                        <i class="fas <?php echo $srcIcon ?> me-1"></i><?php echo ucfirst(str_replace('_', ' ', $src->source)) ?>
                    </span>
                    <div class="progress flex-grow-1" style="height:12px">
                        <div class="progress-bar bg-<?php echo $srcColor ?>" style="width:<?php echo $srcPct ?>%"></div>
                    </div>
                    <span class="ms-2 fw-bold" style="min-width:40px"><?php echo $src->count ?></span>
                </div>
                <?php endforeach;
                endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Top Damages + Monthly Trend -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header py-2"><h6 class="mb-0"><i class="fas fa-bug me-2"></i><?php echo __('Top Damage Types') ?></h6></div>
            <div class="card-body">
                <?php if (empty($topDamages)): ?>
                    <p class="text-muted text-center py-3"><?php echo __('No damage data yet.') ?></p>
                <?php else:
                    $maxDamage = 1;
                    foreach ($topDamages as $_td) { $maxDamage = max($maxDamage, $_td->count); }
                    $damageColors = [
                        'tear' => '#dc3545', 'stain' => '#fd7e14', 'foxing' => '#ffc107', 'fading' => '#6c757d',
                        'water_damage' => '#0dcaf0', 'mold' => '#198754', 'pest_damage' => '#6f42c1',
                        'abrasion' => '#adb5bd', 'brittleness' => '#495057', 'loss' => '#212529',
                        'discoloration' => '#e0a800', 'warping' => '#20c997', 'cracking' => '#d63384',
                        'delamination' => '#0d6efd', 'corrosion' => '#795548',
                    ];
                    foreach ($topDamages as $dmg):
                        $dmgPct = round(($dmg->count / $maxDamage) * 100);
                        $dmgColor = $damageColors[$dmg->damage_type] ?? '#6c757d';
                ?>
                <div class="d-flex align-items-center mb-2">
                    <span class="badge me-2" style="min-width:110px;background:<?php echo $dmgColor ?>"><?php echo ucfirst(str_replace('_', ' ', $dmg->damage_type)) ?></span>
                    <div class="progress flex-grow-1" style="height:10px">
                        <div class="progress-bar" style="width:<?php echo $dmgPct ?>%;background:<?php echo $dmgColor ?>"></div>
                    </div>
                    <span class="ms-2 small fw-bold"><?php echo $dmg->count ?></span>
                </div>
                <?php endforeach;
                endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header py-2"><h6 class="mb-0"><i class="fas fa-chart-line me-2"></i><?php echo __('Monthly Trend (12 months)') ?></h6></div>
            <div class="card-body">
                <?php if (empty($monthlyTrend)): ?>
                    <p class="text-muted text-center py-3"><?php echo __('No trend data yet.') ?></p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr><th><?php echo __('Month') ?></th><th class="text-center"><?php echo __('Assessments') ?></th><th class="text-center"><?php echo __('Avg Score') ?></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthlyTrend as $m): ?>
                            <tr>
                                <td><?php echo $m->month ?></td>
                                <td class="text-center"><span class="badge bg-success"><?php echo $m->total ?></span></td>
                                <td class="text-center">
                                    <?php
                                    $ms = round($m->avg_score ?? 0, 1);
                                    $mc = $ms >= 80 ? 'success' : ($ms >= 60 ? 'info' : ($ms >= 40 ? 'warning' : 'danger'));
                                    ?>
                                    <span class="fw-bold text-<?php echo $mc ?>"><?php echo $ms ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Assessments -->
<div class="card">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="fas fa-clock me-2"></i><?php echo __('Recent Assessments') ?></h6>
        <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'browse']) ?>" class="btn btn-sm btn-outline-success"><?php echo __('View All') ?></a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recentAssessments)): ?>
        <div class="p-3 text-center text-muted"><?php echo __('No assessments yet.') ?></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?php echo __('Object') ?></th>
                        <th class="text-center"><?php echo __('Score') ?></th>
                        <th class="text-center"><?php echo __('Grade') ?></th>
                        <th class="text-center"><?php echo __('Damages') ?></th>
                        <th><?php echo __('Source') ?></th>
                        <th class="text-center"><?php echo __('Confirmed') ?></th>
                        <th><?php echo __('Date') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentAssessments as $a): ?>
                    <tr>
                        <td>
                            <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'view', 'id' => $a->id]) ?>">
                                <?php echo esc_entities($a->object_title ?? 'Assessment #' . $a->id) ?>
                            </a>
                        </td>
                        <td class="text-center"><?php echo \ahgAiConditionPlugin\Helpers\AiConditionHelper::scoreDisplay($a->overall_score) ?></td>
                        <td class="text-center"><?php echo \ahgAiConditionPlugin\Helpers\AiConditionHelper::gradeBadge($a->condition_grade) ?></td>
                        <td class="text-center"><span class="badge bg-secondary"><?php echo $a->damage_count ?? 0 ?></span></td>
                        <td><span class="small"><?php echo ucfirst(str_replace('_', ' ', $a->source ?? 'manual')) ?></span></td>
                        <td class="text-center">
                            <?php if ($a->is_confirmed): ?>
                            <i class="fas fa-check-circle text-success"></i>
                            <?php else: ?>
                            <i class="fas fa-clock text-warning"></i>
                            <?php endif ?>
                        </td>
                        <td class="small text-muted"><?php echo date('d M Y', strtotime($a->created_at)) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php end_slot() ?>
