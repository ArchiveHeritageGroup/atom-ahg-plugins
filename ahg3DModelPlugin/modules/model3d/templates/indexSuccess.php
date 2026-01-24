<?php
/**
 * 3D Model Index Template - List all models
 */
$models = $sf_data->getRaw('models');
$total = $sf_data->getRaw('total');
$page = $sf_data->getRaw('page');
$totalPages = $sf_data->getRaw('totalPages');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><i class="fas fa-cubes me-2"></i>3D Models</h1>
        <p class="text-muted mb-0"><?php echo number_format($total) ?> models in the archive</p>
    </div>
    <div>
        <a href="<?php echo url_for(['module' => 'model3dSettings', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-cog me-1"></i>Settings
        </a>
    </div>
</div>

<?php if (empty($models)): ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="fas fa-cube fa-4x text-muted mb-3"></i>
        <h4>No 3D Models Yet</h4>
        <p class="text-muted">Upload 3D models from individual object pages.</p>
    </div>
</div>
<?php else: ?>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th width="60"></th>
                    <th>Model</th>
                    <th>Object</th>
                    <th>Format</th>
                    <th>Size</th>
                    <th>Status</th>
                    <th width="100">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($models as $model): ?>
                <tr>
                    <td>
                        <?php if ($model->thumbnail): ?>
                        <img src="/uploads/<?php echo $model->thumbnail ?>" alt="" class="rounded" style="width:50px;height:50px;object-fit:cover;">
                        <?php else: ?>
                        <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" style="width:50px;height:50px;">
                            <i class="fas fa-cube"></i>
                        </div>
                        <?php endif ?>
                    </td>
                    <td>
                        <a href="<?php echo url_for(['module' => 'model3d', 'action' => 'view', 'id' => $model->id]) ?>">
                            <strong><?php echo esc_entities($model->model_title ?: $model->original_filename) ?></strong>
                        </a>
                        <?php if ($model->is_primary): ?>
                        <span class="badge bg-primary ms-1">Primary</span>
                        <?php endif ?>
                        <?php if ($model->ar_enabled): ?>
                        <span class="badge bg-success ms-1">AR</span>
                        <?php endif ?>
                    </td>
                    <td>
                        <?php if ($model->object_slug): ?>
                        <a href="/index.php/<?php echo $model->object_slug ?>">
                            <?php echo esc_entities(mb_substr($model->object_title ?: 'Untitled', 0, 40)) ?>
                        </a>
                        <?php else: ?>
                        <span class="text-muted">-</span>
                        <?php endif ?>
                    </td>
                    <td>
                        <span class="badge bg-secondary"><?php echo strtoupper($model->format) ?></span>
                    </td>
                    <td>
                        <small><?php echo number_format($model->file_size / 1024 / 1024, 2) ?> MB</small>
                    </td>
                    <td>
                        <?php if ($model->is_public): ?>
                        <span class="text-success"><i class="fas fa-check-circle"></i> Public</span>
                        <?php else: ?>
                        <span class="text-warning"><i class="fas fa-eye-slash"></i> Hidden</span>
                        <?php endif ?>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="<?php echo url_for(['module' => 'model3d', 'action' => 'view', 'id' => $model->id]) ?>" 
                               class="btn btn-outline-primary" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="<?php echo url_for(['module' => 'model3d', 'action' => 'edit', 'id' => $model->id]) ?>" 
                               class="btn btn-outline-secondary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-3">
    <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
        <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'model3d', 'action' => 'index', 'page' => $page - 1]) ?>">
                &laquo; Previous
            </a>
        </li>
        <?php endif ?>
        
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <li class="page-item <?php echo $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'model3d', 'action' => 'index', 'page' => $i]) ?>">
                <?php echo $i ?>
            </a>
        </li>
        <?php endfor ?>
        
        <?php if ($page < $totalPages): ?>
        <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'model3d', 'action' => 'index', 'page' => $page + 1]) ?>">
                Next &raquo;
            </a>
        </li>
        <?php endif ?>
    </ul>
</nav>
<?php endif ?>

<?php endif ?>
