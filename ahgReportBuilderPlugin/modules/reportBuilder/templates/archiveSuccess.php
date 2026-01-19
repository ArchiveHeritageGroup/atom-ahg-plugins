<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="bi bi-archive text-primary me-2"></i><?php echo __('Report Archive'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'index']); ?>"><?php echo __('Report Builder'); ?></a></li>
        <li class="breadcrumb-item active"><?php echo __('Archive'); ?></li>
    </ol>
</nav>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-file-earmark-zip me-2"></i><?php echo __('Generated Reports'); ?></span>
        <span class="badge bg-secondary"><?php echo count($archives); ?> <?php echo __('files'); ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th><?php echo __('Report'); ?></th>
                    <th><?php echo __('Format'); ?></th>
                    <th><?php echo __('Size'); ?></th>
                    <th><?php echo __('Generated'); ?></th>
                    <th><?php echo __('Source'); ?></th>
                    <th width="100"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($archives)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">
                        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                        <?php echo __('No archived reports yet.'); ?>
                        <br>
                        <small><?php echo __('Scheduled reports will appear here after they run.'); ?></small>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($archives as $archive): ?>
                    <tr>
                        <td>
                            <?php if ($archive->report_name): ?>
                                <strong><?php echo htmlspecialchars($archive->report_name); ?></strong>
                            <?php else: ?>
                                <span class="text-muted"><?php echo __('Deleted Report'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $formatBadges = [
                                'pdf' => 'danger',
                                'xlsx' => 'success',
                                'csv' => 'info',
                            ];
                            $badge = $formatBadges[$archive->file_format] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $badge; ?>"><?php echo strtoupper($archive->file_format); ?></span>
                        </td>
                        <td>
                            <?php
                            if ($archive->file_size) {
                                if ($archive->file_size < 1024) {
                                    echo $archive->file_size . ' B';
                                } elseif ($archive->file_size < 1048576) {
                                    echo round($archive->file_size / 1024, 1) . ' KB';
                                } else {
                                    echo round($archive->file_size / 1048576, 1) . ' MB';
                                }
                            } else {
                                echo '--';
                            }
                            ?>
                        </td>
                        <td>
                            <small><?php echo date('Y-m-d H:i', strtotime($archive->generated_at)); ?></small>
                        </td>
                        <td>
                            <?php if ($archive->schedule_id): ?>
                                <span class="badge bg-info"><i class="bi bi-clock me-1"></i><?php echo __('Scheduled'); ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="bi bi-person me-1"></i><?php echo __('Manual'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (file_exists($archive->file_path)): ?>
                            <a href="<?php echo $archive->file_path; ?>" class="btn btn-sm btn-outline-primary" download>
                                <i class="bi bi-download"></i>
                            </a>
                            <?php else: ?>
                            <span class="text-muted small"><?php echo __('File not found'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php end_slot() ?>
