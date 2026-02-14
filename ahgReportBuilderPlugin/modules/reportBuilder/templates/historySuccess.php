<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="bi bi-clock-history text-primary me-2"></i><?php echo __('Version History'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php
$rawReport = $sf_data->getRaw('report');
$rawVersions = $sf_data->getRaw('versions');
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'index']); ?>"><?php echo __('Report Builder'); ?></a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'preview', 'id' => $rawReport->id]); ?>"><?php echo htmlspecialchars($rawReport->name); ?></a></li>
        <li class="breadcrumb-item active"><?php echo __('Version History'); ?></li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><?php echo htmlspecialchars($rawReport->name); ?></h4>
        <small class="text-muted"><?php echo __('Viewing version history'); ?></small>
    </div>
    <div>
        <a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'edit', 'id' => $rawReport->id]); ?>" class="btn btn-outline-primary">
            <i class="bi bi-pencil me-1"></i><?php echo __('Back to Editor'); ?>
        </a>
    </div>
</div>

<?php if (empty($rawVersions)): ?>
<div class="card">
    <div class="card-body text-center text-muted py-5">
        <i class="bi bi-clock-history fs-1 d-block mb-3"></i>
        <?php echo __('No version history available for this report.'); ?>
    </div>
</div>
<?php else: ?>
<!-- Current Version Highlight -->
<?php $currentVersion = $rawVersions[0] ?? null; ?>
<?php if ($currentVersion): ?>
<div class="card border-primary mb-4">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-star-fill me-1"></i>
            <?php echo __('Current Version'); ?> - v<?php echo htmlspecialchars($currentVersion->version_number ?? '1'); ?>
        </span>
        <span class="badge bg-white text-primary"><?php echo __('Active'); ?></span>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <?php if (!empty($currentVersion->summary)): ?>
                <p class="mb-1"><?php echo htmlspecialchars($currentVersion->summary); ?></p>
                <?php endif; ?>
                <small class="text-muted">
                    <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($currentVersion->user_name ?? __('Unknown')); ?>
                    <span class="mx-2">|</span>
                    <i class="bi bi-calendar me-1"></i><?php echo date('Y-m-d H:i', strtotime($currentVersion->created_at)); ?>
                </small>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Version Timeline -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-list-ol me-1"></i><?php echo __('All Versions'); ?>
        <span class="badge bg-secondary ms-2"><?php echo count($rawVersions); ?></span>
    </div>
    <div class="card-body p-0">
        <div class="list-group list-group-flush">
            <?php foreach ($rawVersions as $index => $version): ?>
            <?php $isCurrent = ($index === 0); ?>
            <div class="list-group-item <?php echo $isCurrent ? 'bg-light' : ''; ?>">
                <div class="d-flex">
                    <!-- Timeline indicator -->
                    <div class="me-3 text-center" style="width:40px;">
                        <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto <?php echo $isCurrent ? 'bg-primary text-white' : 'bg-light border'; ?>"
                             style="width:32px;height:32px;">
                            <small class="fw-bold"><?php echo htmlspecialchars($version->version_number ?? (count($rawVersions) - $index)); ?></small>
                        </div>
                        <?php if ($index < count($rawVersions) - 1): ?>
                        <div class="border-start mx-auto" style="width:0;height:20px;margin-top:4px;"></div>
                        <?php endif; ?>
                    </div>
                    <!-- Version details -->
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong class="small">
                                    v<?php echo htmlspecialchars($version->version_number ?? (count($rawVersions) - $index)); ?>
                                    <?php if ($isCurrent): ?>
                                    <span class="badge bg-primary ms-1"><?php echo __('Current'); ?></span>
                                    <?php endif; ?>
                                </strong>
                                <?php if (!empty($version->summary)): ?>
                                <p class="mb-1 small"><?php echo htmlspecialchars($version->summary); ?></p>
                                <?php endif; ?>
                                <small class="text-muted">
                                    <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($version->user_name ?? __('Unknown')); ?>
                                    <span class="mx-1">-</span>
                                    <i class="bi bi-calendar me-1"></i><?php echo date('Y-m-d H:i', strtotime($version->created_at)); ?>
                                </small>
                            </div>
                            <?php if (!$isCurrent): ?>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-warning btn-restore-version"
                                        data-version-id="<?php echo $version->id; ?>"
                                        data-version-number="<?php echo htmlspecialchars($version->version_number ?? (count($rawVersions) - $index)); ?>">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i><?php echo __('Restore'); ?>
                                </button>
                                <a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'compareVersions', 'id' => $rawReport->id, 'version_id' => $version->id]); ?>"
                                   class="btn btn-outline-secondary">
                                    <i class="bi bi-arrows-angle-expand me-1"></i><?php echo __('Compare'); ?>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Restore Confirmation Modal -->
<div class="modal fade" id="restoreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-counterclockwise me-2"></i><?php echo __('Restore Version'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <?php echo __('Are you sure you want to restore version'); ?> <strong id="restoreVersionLabel"></strong>?
                </div>
                <p class="text-muted small"><?php echo __('The current version will be saved to history before restoring. This action can be undone by restoring the previous version.'); ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
                <form id="restoreForm" method="post" action="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'restoreVersion']); ?>" style="display:inline;">
                    <input type="hidden" name="report_id" value="<?php echo $rawReport->id; ?>">
                    <input type="hidden" name="version_id" id="restoreVersionId" value="">
                    <button type="submit" class="btn btn-warning"><i class="bi bi-arrow-counterclockwise me-1"></i><?php echo __('Restore'); ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    // Restore version buttons
    document.querySelectorAll('.btn-restore-version').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var versionId = this.dataset.versionId;
            var versionNumber = this.dataset.versionNumber;
            document.getElementById('restoreVersionId').value = versionId;
            document.getElementById('restoreVersionLabel').textContent = 'v' + versionNumber;
            var modal = new bootstrap.Modal(document.getElementById('restoreModal'));
            modal.show();
        });
    });
});
</script>
<?php end_slot() ?>
