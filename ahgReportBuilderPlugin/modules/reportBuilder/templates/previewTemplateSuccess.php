<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="bi bi-eye text-primary me-2"></i><?php echo __('Template Preview'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php
$rawTemplate = $sf_data->getRaw('template');
$structureRaw = $rawTemplate->structure ?? [];
// Structure may be wrapped: {"sections": [...], "data_source": "..."} or flat array of sections
$structure = isset($structureRaw['sections']) ? $structureRaw['sections'] : $structureRaw;
$scopeLabels = [
    'system'      => ['label' => 'System',      'badge' => 'bg-dark'],
    'institution' => ['label' => 'Institution',  'badge' => 'bg-primary'],
    'user'        => ['label' => 'User',         'badge' => 'bg-info'],
];
$scopeMeta = $scopeLabels[$rawTemplate->scope ?? 'user'] ?? ['label' => 'User', 'badge' => 'bg-secondary'];
$sectionTypeIcons = [
    'narrative'  => 'bi-text-paragraph',
    'data_table' => 'bi-table',
    'chart'      => 'bi-bar-chart',
    'summary'    => 'bi-card-text',
    'image'      => 'bi-image',
    'header'     => 'bi-type-h1',
    'separator'  => 'bi-dash-lg',
];
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'index']); ?>"><?php echo __('Report Builder'); ?></a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'templates']); ?>"><?php echo __('Templates'); ?></a></li>
        <li class="breadcrumb-item active"><?php echo htmlspecialchars($rawTemplate->name); ?></li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><?php echo htmlspecialchars($rawTemplate->name); ?></h4>
        <?php if (!empty($rawTemplate->description)): ?>
        <p class="text-muted mb-0"><?php echo htmlspecialchars($rawTemplate->description); ?></p>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2">
        <span class="badge <?php echo $scopeMeta['badge']; ?> align-self-start"><?php echo $scopeMeta['label']; ?></span>
        <a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'create', 'template_id' => $rawTemplate->id]); ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i><?php echo __('Use This Template'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'templates']); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i><?php echo __('Back'); ?>
        </a>
    </div>
</div>

<!-- Template Metadata -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title text-muted mb-3"><i class="bi bi-info-circle me-1"></i><?php echo __('Template Details'); ?></h6>
                <table class="table table-sm mb-0">
                    <tr>
                        <td class="text-muted" style="width:140px"><?php echo __('Category'); ?></td>
                        <td><strong><?php echo htmlspecialchars($rawTemplate->category ?? 'Custom'); ?></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><?php echo __('Scope'); ?></td>
                        <td><span class="badge <?php echo $scopeMeta['badge']; ?>"><?php echo $scopeMeta['label']; ?></span></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><?php echo __('Data Source'); ?></td>
                        <td><strong><?php echo htmlspecialchars($structureRaw['data_source'] ?? 'N/A'); ?></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><?php echo __('Sections'); ?></td>
                        <td><strong><?php echo count($structure); ?></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><?php echo __('Created'); ?></td>
                        <td><?php echo htmlspecialchars($rawTemplate->created_at ?? ''); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title text-muted mb-3"><i class="bi bi-layers me-1"></i><?php echo __('Section Types'); ?></h6>
                <?php
                $typeCounts = [];
                foreach ($structure as $section) {
                    $type = $section['section_type'] ?? 'narrative';
                    $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
                }
                ?>
                <?php if (empty($typeCounts)): ?>
                <p class="text-muted mb-0"><?php echo __('No sections defined'); ?></p>
                <?php else: ?>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($typeCounts as $type => $count): ?>
                    <span class="badge bg-light text-dark border">
                        <i class="bi <?php echo $sectionTypeIcons[$type] ?? 'bi-square'; ?> me-1"></i>
                        <?php echo ucfirst(str_replace('_', ' ', $type)); ?> (<?php echo $count; ?>)
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Section Preview -->
<?php if (!empty($structure)): ?>
<h5 class="mb-3"><i class="bi bi-list-ol me-2"></i><?php echo __('Sections'); ?></h5>
<div class="list-group mb-4">
    <?php $sectionNum = 0; foreach ($structure as $section): $sectionNum++; ?>
    <?php
    $sType = $section['section_type'] ?? 'narrative';
    $sIcon = $sectionTypeIcons[$sType] ?? 'bi-square';
    $sTitle = $section['title'] ?? __('Untitled Section');
    $sContent = $section['content'] ?? '';
    $sVisible = ($section['is_visible'] ?? 1) ? true : false;
    $sClearance = $section['clearance_level'] ?? 0;
    ?>
    <div class="list-group-item <?php echo $sVisible ? '' : 'bg-light'; ?>">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <div class="d-flex align-items-center mb-1">
                    <span class="badge bg-secondary me-2"><?php echo $sectionNum; ?></span>
                    <i class="bi <?php echo $sIcon; ?> me-2 text-primary"></i>
                    <strong><?php echo htmlspecialchars($sTitle); ?></strong>
                    <?php if (!$sVisible): ?>
                    <span class="badge bg-warning text-dark ms-2"><i class="bi bi-eye-slash me-1"></i><?php echo __('Hidden'); ?></span>
                    <?php endif; ?>
                    <?php if ($sClearance > 0): ?>
                    <span class="badge bg-danger ms-2"><i class="bi bi-shield-lock me-1"></i>L<?php echo $sClearance; ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($sContent)): ?>
                <p class="mb-0 text-muted small ms-5"><?php echo htmlspecialchars(mb_substr(strip_tags($sContent), 0, 200)); ?><?php echo mb_strlen(strip_tags($sContent)) > 200 ? '...' : ''; ?></p>
                <?php endif; ?>
            </div>
            <span class="badge bg-light text-dark border"><?php echo ucfirst(str_replace('_', ' ', $sType)); ?></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="card">
    <div class="card-body text-center text-muted py-4">
        <i class="bi bi-layers fs-1 d-block mb-2"></i>
        <?php echo __('This template has no sections defined.'); ?>
    </div>
</div>
<?php endif; ?>
<?php end_slot() ?>
