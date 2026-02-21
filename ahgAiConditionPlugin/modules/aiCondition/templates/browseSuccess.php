<?php decorate_with('layout_2col.php') ?>

<?php
require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgAiConditionPlugin/lib/Helpers/AiConditionHelper.php';
?>

<?php slot('sidebar') ?>
<div class="sidebar-content">
    <div class="card mb-3">
        <div class="card-header bg-success text-white py-2">
            <h6 class="mb-0"><i class="fas fa-robot me-1"></i><?php echo __('AI Condition') ?></h6>
        </div>
        <div class="card-body py-2">
            <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'assess']) ?>" class="btn btn-success btn-sm w-100 mb-2">
                <i class="fas fa-camera me-1"></i><?php echo __('New Assessment') ?>
            </a>
            <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'bulk']) ?>" class="btn btn-outline-primary btn-sm w-100 mb-2">
                <i class="fas fa-layer-group me-1"></i><?php echo __('Bulk Scan') ?>
            </a>
            <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm w-100">
                <i class="fas fa-cog me-1"></i><?php echo __('Settings') ?>
            </a>
        </div>
    </div>
    <!-- Stats -->
    <div class="card mb-3">
        <div class="card-header py-2"><h6 class="mb-0"><?php echo __('Statistics') ?></h6></div>
        <div class="card-body py-2 small">
            <div class="d-flex justify-content-between mb-1">
                <span><?php echo __('Total Assessments') ?></span>
                <strong><?php echo $stats['total'] ?? 0 ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span><?php echo __('Confirmed') ?></span>
                <strong class="text-success"><?php echo $stats['confirmed'] ?? 0 ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span><?php echo __('Pending Review') ?></span>
                <strong class="text-warning"><?php echo $stats['pending'] ?? 0 ?></strong>
            </div>
            <div class="d-flex justify-content-between">
                <span><?php echo __('Avg Score') ?></span>
                <strong><?php echo $stats['avg_score'] ?? '--' ?></strong>
            </div>
        </div>
    </div>
</div>
<?php end_slot() ?>

<?php slot('title') ?>
<h1 class="h3 mb-0"><i class="fas fa-robot me-2"></i><?php echo __('AI Condition Assessments') ?></h1>
<p class="text-muted small mb-3"><?php echo __('Browse AI-powered damage detection results') ?></p>
<?php end_slot() ?>

<?php slot('content') ?>
<!-- Filters -->
<div class="d-flex gap-2 mb-3 flex-wrap">
    <form method="get" action="<?php echo url_for(['module' => 'aiCondition', 'action' => 'browse']) ?>" class="d-flex gap-2 flex-wrap">
        <input type="text" name="q" class="form-control form-control-sm" style="width:200px" placeholder="<?php echo __('Search...') ?>" value="<?php echo esc_entities($filters['search'] ?? '') ?>">
        <select name="grade" class="form-select form-select-sm" style="width:150px">
            <option value=""><?php echo __('All grades') ?></option>
            <?php foreach (['excellent','good','fair','poor','critical'] as $g): ?>
            <option value="<?php echo $g ?>" <?php echo ($filters['condition_grade'] ?? '') === $g ? 'selected' : '' ?>><?php echo ucfirst($g) ?></option>
            <?php endforeach ?>
        </select>
        <select name="confirmed" class="form-select form-select-sm" style="width:150px">
            <option value=""><?php echo __('All status') ?></option>
            <option value="1" <?php echo ($filters['is_confirmed'] ?? '') === '1' ? 'selected' : '' ?>><?php echo __('Confirmed') ?></option>
            <option value="0" <?php echo ($filters['is_confirmed'] ?? '') === '0' ? 'selected' : '' ?>><?php echo __('Pending') ?></option>
        </select>
        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i></button>
    </form>
</div>

<?php if (empty($assessments)): ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    <?php echo __('No assessments found.') ?>
    <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'assess']) ?>" class="btn btn-sm btn-success ms-2">
        <?php echo __('Run first assessment') ?>
    </a>
</div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-hover table-sm align-middle">
        <thead class="table-light">
            <tr>
                <th><?php echo __('Date') ?></th>
                <th><?php echo __('Object') ?></th>
                <th class="text-center"><?php echo __('Score') ?></th>
                <th class="text-center"><?php echo __('Grade') ?></th>
                <th class="text-center"><?php echo __('Damages') ?></th>
                <th class="text-center"><?php echo __('Source') ?></th>
                <th class="text-center"><?php echo __('Status') ?></th>
                <th class="text-end"><?php echo __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($assessments as $a): ?>
            <tr>
                <td class="small"><?php echo date('Y-m-d H:i', strtotime($a->created_at)) ?></td>
                <td>
                    <?php if ($a->object_title): ?>
                    <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'view', 'id' => $a->id]) ?>"><?php echo esc_entities(mb_strimwidth($a->object_title, 0, 50, '...')) ?></a>
                    <?php else: ?>
                    <span class="text-muted"><?php echo __('Standalone') ?></span>
                    <?php endif ?>
                </td>
                <td class="text-center"><?php echo \ahgAiConditionPlugin\Helpers\AiConditionHelper::scoreDisplay($a->overall_score) ?></td>
                <td class="text-center"><?php echo \ahgAiConditionPlugin\Helpers\AiConditionHelper::gradeBadge($a->condition_grade) ?></td>
                <td class="text-center"><span class="badge bg-secondary"><?php echo $a->damage_count ?></span></td>
                <td class="text-center"><span class="badge bg-light text-dark"><?php echo ucfirst($a->source) ?></span></td>
                <td class="text-center">
                    <?php if ($a->is_confirmed): ?>
                    <span class="badge bg-success"><i class="fas fa-check me-1"></i><?php echo __('Confirmed') ?></span>
                    <?php else: ?>
                    <span class="badge bg-warning text-dark"><?php echo __('Pending') ?></span>
                    <?php endif ?>
                </td>
                <td class="text-end">
                    <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'view', 'id' => $a->id]) ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('View') ?>">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>

<?php if ($pages > 1): ?>
<nav>
    <ul class="pagination pagination-sm justify-content-center">
        <?php for ($p = 1; $p <= $pages; $p++): ?>
        <li class="page-item <?php echo $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'browse', 'page' => $p]) ?>"><?php echo $p ?></a>
        </li>
        <?php endfor ?>
    </ul>
</nav>
<?php endif ?>
<?php endif ?>
<?php end_slot() ?>
