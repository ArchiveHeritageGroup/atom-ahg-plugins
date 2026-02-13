<?php
$sessions = $sf_data->getRaw('sessions') ?? [];
?>

<?php echo get_component('default', 'updateCheck') ?>

<h1><?php echo __('Ingestion Manager') ?></h1>

<?php echo get_partial('default/breadcrumb', [
    'objects' => [
        ['title' => __('Admin'), 'url' => url_for(['module' => 'admin', 'action' => 'index'])],
        ['title' => __('Ingestion Manager')]
    ]
]) ?>

<?php if ($sf_user->hasFlash('notice')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo $sf_user->getFlash('notice') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif ?>

<?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?php echo $sf_user->getFlash('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0"><?php echo __('Manage batch imports of records and digital objects') ?></p>
    <div>
        <div class="btn-group me-2">
            <a href="<?php echo url_for(['module' => 'ingest', 'action' => 'downloadTemplate', 'sector' => 'archive']) ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-download me-1"></i><?php echo __('CSV Template') ?>
            </a>
        </div>
        <a href="<?php echo url_for(['module' => 'ingest', 'action' => 'configure']) ?>" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i><?php echo __('New Ingest') ?>
        </a>
    </div>
</div>

<?php if (empty($sessions)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h5 class="text-muted"><?php echo __('No ingest sessions yet') ?></h5>
            <p class="text-muted"><?php echo __('Start a new ingest to batch-import records and digital objects') ?></p>
            <a href="<?php echo url_for(['module' => 'ingest', 'action' => 'configure']) ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i><?php echo __('New Ingest') ?>
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th><?php echo __('ID') ?></th>
                    <th><?php echo __('Title') ?></th>
                    <th><?php echo __('Sector') ?></th>
                    <th><?php echo __('Status') ?></th>
                    <?php if ($sf_user->isAdministrator()): ?>
                        <th><?php echo __('User') ?></th>
                    <?php endif ?>
                    <th><?php echo __('Updated') ?></th>
                    <th><?php echo __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessions as $s): ?>
                    <?php
                    $statusClass = 'secondary';
                    $statusLabel = $s->status ?? 'unknown';
                    switch ($statusLabel) {
                        case 'completed': $statusClass = 'success'; break;
                        case 'failed': $statusClass = 'danger'; break;
                        case 'cancelled': $statusClass = 'warning'; break;
                        case 'commit': $statusClass = 'info'; break;
                        default: $statusClass = 'primary';
                    }
                    ?>
                    <tr>
                        <td><?php echo $s->id ?></td>
                        <td>
                            <strong><?php echo esc_entities($s->title ?: __('Untitled session')) ?></strong>
                        </td>
                        <td><span class="badge bg-secondary"><?php echo ucfirst($s->sector) ?></span></td>
                        <td><span class="badge bg-<?php echo $statusClass ?>"><?php echo ucfirst($statusLabel) ?></span></td>
                        <?php if ($sf_user->isAdministrator()): ?>
                            <td><?php echo esc_entities($s->user_name ?? '') ?></td>
                        <?php endif ?>
                        <td><?php echo date('Y-m-d H:i', strtotime($s->updated_at)) ?></td>
                        <td>
                            <?php if (in_array($statusLabel, ['configure', 'upload', 'map', 'validate', 'preview'])): ?>
                                <a href="<?php echo url_for(['module' => 'ingest', 'action' => $statusLabel, 'id' => $s->id]) ?>"
                                   class="btn btn-sm btn-outline-primary" title="<?php echo __('Resume') ?>">
                                    <i class="fas fa-play"></i>
                                </a>
                            <?php endif ?>
                            <?php if ($statusLabel === 'completed'): ?>
                                <a href="<?php echo url_for(['module' => 'ingest', 'action' => 'commit', 'id' => $s->id]) ?>"
                                   class="btn btn-sm btn-outline-success" title="<?php echo __('View Report') ?>">
                                    <i class="fas fa-chart-bar"></i>
                                </a>
                                <a href="<?php echo url_for(['module' => 'ingest', 'action' => 'downloadManifest', 'id' => $s->id]) ?>"
                                   class="btn btn-sm btn-outline-secondary" title="<?php echo __('Download Manifest') ?>">
                                    <i class="fas fa-file-csv"></i>
                                </a>
                            <?php endif ?>
                            <?php if (in_array($statusLabel, ['configure', 'upload', 'map', 'validate', 'preview'])): ?>
                                <a href="<?php echo url_for(['module' => 'ingest', 'action' => 'cancel', 'id' => $s->id]) ?>"
                                   class="btn btn-sm btn-outline-danger" title="<?php echo __('Cancel') ?>"
                                   onclick="return confirm('<?php echo __('Cancel this ingest session?') ?>')">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif ?>
                            <?php if ($statusLabel === 'completed'): ?>
                                <a href="<?php echo url_for(['module' => 'ingest', 'action' => 'rollback', 'id' => $s->id]) ?>"
                                   class="btn btn-sm btn-outline-danger" title="<?php echo __('Rollback') ?>"
                                   onclick="return confirm('<?php echo __('This will DELETE all records created by this ingest. Are you sure?') ?>')">
                                    <i class="fas fa-undo"></i>
                                </a>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
<?php endif ?>
