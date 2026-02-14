<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="bi bi-layout-text-window-reverse text-primary me-2"></i><?php echo __('Report Templates'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php
$rawTemplates = $sf_data->getRaw('templates');
$categories = [
    'NARSSA'    => ['icon' => 'bi-shield-lock',      'color' => 'primary'],
    'GRAP 103'  => ['icon' => 'bi-calculator',        'color' => 'success'],
    'Accession' => ['icon' => 'bi-box-seam',          'color' => 'info'],
    'Condition'  => ['icon' => 'bi-clipboard2-pulse',  'color' => 'warning'],
    'Custom'    => ['icon' => 'bi-pencil-square',     'color' => 'secondary'],
];
$scopeLabels = [
    'system'      => ['label' => 'System',      'badge' => 'bg-dark'],
    'institution' => ['label' => 'Institution',  'badge' => 'bg-primary'],
    'user'        => ['label' => 'User',         'badge' => 'bg-info'],
];
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'index']); ?>"><?php echo __('Report Builder'); ?></a></li>
        <li class="breadcrumb-item active"><?php echo __('Templates'); ?></li>
    </ol>
</nav>

<?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo $sf_user->getFlash('success'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?php echo $sf_user->getFlash('error'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Action Bar -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i><?php echo __('Report Builder'); ?>
        </a>
    </div>
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTemplateModal">
            <i class="bi bi-plus-lg me-1"></i><?php echo __('Create Template'); ?>
        </button>
    </div>
</div>

<?php if (empty($rawTemplates)): ?>
<div class="card">
    <div class="card-body text-center text-muted py-5">
        <i class="bi bi-layout-text-window-reverse fs-1 d-block mb-3"></i>
        <?php echo __('No templates available.'); ?>
        <br>
        <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#createTemplateModal">
            <i class="bi bi-plus-lg me-1"></i><?php echo __('Create Your First Template'); ?>
        </button>
    </div>
</div>
<?php else: ?>
    <?php
    // Group templates by category
    $grouped = [];
    foreach ($rawTemplates as $tpl) {
        $cat = $tpl->category ?? 'Custom';
        if (!isset($grouped[$cat])) {
            $grouped[$cat] = [];
        }
        $grouped[$cat][] = $tpl;
    }
    ?>
    <?php foreach ($grouped as $category => $categoryTemplates): ?>
    <?php
    $catMeta = $categories[$category] ?? ['icon' => 'bi-folder', 'color' => 'secondary'];
    ?>
    <div class="mb-4">
        <h5 class="mb-3">
            <i class="bi <?php echo $catMeta['icon']; ?> text-<?php echo $catMeta['color']; ?> me-2"></i><?php echo htmlspecialchars($category); ?>
            <span class="badge bg-<?php echo $catMeta['color']; ?> ms-2"><?php echo count($categoryTemplates); ?></span>
        </h5>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
            <?php foreach ($categoryTemplates as $tpl): ?>
            <?php
            $scope = $tpl->scope ?? 'user';
            $scopeMeta = $scopeLabels[$scope] ?? ['label' => ucfirst($scope), 'badge' => 'bg-secondary'];
            $isSystem = ($scope === 'system');
            $sectionCount = isset($tpl->sections) ? (is_array($tpl->sections) ? count($tpl->sections) : $tpl->sections) : 0;
            ?>
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="card-title mb-0"><?php echo htmlspecialchars($tpl->name); ?></h6>
                            <span class="badge <?php echo $scopeMeta['badge']; ?>"><?php echo $scopeMeta['label']; ?></span>
                        </div>
                        <?php if (!empty($tpl->description)): ?>
                        <p class="card-text small text-muted mb-2"><?php echo htmlspecialchars($tpl->description); ?></p>
                        <?php endif; ?>
                        <div class="mb-3">
                            <small class="text-muted">
                                <i class="bi bi-layers me-1"></i><?php echo $sectionCount; ?> <?php echo __('sections'); ?>
                            </small>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top-0 pt-0">
                        <div class="d-flex gap-2">
                            <a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'create', 'template_id' => $tpl->id]); ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-circle me-1"></i><?php echo __('Use Template'); ?>
                            </a>
                            <a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'previewTemplate', 'id' => $tpl->id]); ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye me-1"></i><?php echo __('Preview'); ?>
                            </a>
                            <?php if (!$isSystem): ?>
                            <a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'editTemplate', 'id' => $tpl->id]); ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil me-1"></i><?php echo __('Edit'); ?>
                            </a>
                            <a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'deleteTemplate', 'id' => $tpl->id, 'confirm' => 1]); ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('<?php echo __('Are you sure you want to delete this template?'); ?>');">
                                <i class="bi bi-trash me-1"></i><?php echo __('Delete'); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Create Template Modal -->
<div class="modal fade" id="createTemplateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'createTemplate']); ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i><?php echo __('Create Template'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="templateName" class="form-label"><?php echo __('Template Name'); ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="templateName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="templateDescription" class="form-label"><?php echo __('Description'); ?></label>
                        <textarea class="form-control" id="templateDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="templateCategory" class="form-label"><?php echo __('Category'); ?></label>
                        <select class="form-select" id="templateCategory" name="category">
                            <?php foreach (array_keys($categories) as $cat): ?>
                            <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="templateScope" class="form-label"><?php echo __('Scope'); ?></label>
                        <select class="form-select" id="templateScope" name="scope">
                            <option value="user"><?php echo __('User (private)'); ?></option>
                            <option value="institution"><?php echo __('Institution (shared)'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i><?php echo __('Create'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus template name input when modal opens
    var createModal = document.getElementById('createTemplateModal');
    if (createModal) {
        createModal.addEventListener('shown.bs.modal', function() {
            document.getElementById('templateName').focus();
        });
    }
});
</script>
<?php end_slot() ?>
