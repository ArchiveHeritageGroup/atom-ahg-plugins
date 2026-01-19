<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="bi bi-arrow-repeat text-primary me-2"></i><?php echo __('Format Conversion'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>

<!-- Conversion Tools Status -->
<div class="row mb-4">
    <?php foreach ($tools as $name => $info): ?>
    <div class="col-md-3">
        <div class="card <?php echo $info['available'] ? 'border-success' : 'border-secondary'; ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0"><?php echo htmlspecialchars($name); ?></h6>
                    <?php if ($info['available']): ?>
                        <span class="badge bg-success">Available</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Not Installed</span>
                    <?php endif; ?>
                </div>
                <small class="text-muted">
                    <?php $formats = is_array($info['formats']) ? $info['formats'] : iterator_to_array($info['formats']); ?>
                    <?php echo implode(', ', array_slice($formats, 0, 5)); ?>
                    <?php if (count($formats) > 5): ?>...<?php endif; ?>
                </small>
                <?php if ($info['available'] && !empty($info['version'])): ?>
                <br><small class="text-success"><?php echo htmlspecialchars(substr($info['version'], 0, 30)); ?></small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Quick Actions -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i><?php echo __('Back to Dashboard'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'formats']); ?>" class="btn btn-outline-primary">
            <i class="bi bi-file-code me-1"></i><?php echo __('Format Registry'); ?>
        </a>
    </div>
    <div class="text-end">
        <span class="text-muted"><?php echo __('Pending conversions:'); ?></span>
        <span class="badge bg-<?php echo $pendingConversions > 0 ? 'warning' : 'success'; ?> ms-2">
            <?php echo number_format($pendingConversions); ?>
        </span>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><?php echo __('Completed'); ?></h6>
                        <h2 class="mb-0"><?php echo number_format($conversionStats['completed'] ?? 0); ?></h2>
                    </div>
                    <i class="bi bi-check-circle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><?php echo __('Processing'); ?></h6>
                        <h2 class="mb-0"><?php echo number_format($conversionStats['processing'] ?? 0); ?></h2>
                    </div>
                    <i class="bi bi-hourglass-split fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><?php echo __('Pending'); ?></h6>
                        <h2 class="mb-0"><?php echo number_format($conversionStats['pending'] ?? 0); ?></h2>
                    </div>
                    <i class="bi bi-clock fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><?php echo __('Failed'); ?></h6>
                        <h2 class="mb-0"><?php echo number_format($conversionStats['failed'] ?? 0); ?></h2>
                    </div>
                    <i class="bi bi-x-circle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CLI Commands -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-terminal me-2"></i><?php echo __('CLI Commands'); ?>
    </div>
    <div class="card-body">
        <p class="mb-2">Run format conversions from the command line:</p>
        <pre class="bg-dark text-light p-3 rounded mb-0"><code># Show available tools and statistics
php symfony preservation:convert --status

# Preview conversions (dry run)
php symfony preservation:convert --dry-run

# Convert specific object to TIFF
php symfony preservation:convert --object-id=123 --format=tiff

# Batch convert JPEG images to TIFF
php symfony preservation:convert --mime-type=image/jpeg --format=tiff --limit=50</code></pre>
    </div>
</div>

<!-- Supported Conversions -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-diagram-3 me-2"></i><?php echo __('Supported Conversions'); ?>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6><i class="bi bi-image me-2"></i>Images (ImageMagick)</h6>
                <ul class="small mb-3">
                    <li>JPEG, PNG, BMP, GIF &rarr; TIFF (uncompressed)</li>
                </ul>

                <h6><i class="bi bi-music-note-beamed me-2"></i>Audio (FFmpeg)</h6>
                <ul class="small mb-3">
                    <li>MP3, AAC, OGG &rarr; WAV (PCM)</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6><i class="bi bi-file-earmark-pdf me-2"></i>Documents</h6>
                <ul class="small mb-3">
                    <li>PDF &rarr; PDF/A (Ghostscript)</li>
                    <li>DOC, DOCX, XLS, PPT &rarr; PDF/A (LibreOffice)</li>
                </ul>

                <h6><i class="bi bi-film me-2"></i>Video (FFmpeg)</h6>
                <ul class="small mb-0">
                    <li>Various &rarr; MKV/FFV1 (lossless)</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Recent Conversions Table -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-list-ul me-2"></i><?php echo __('Recent Conversions'); ?>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th><?php echo __('File'); ?></th>
                    <th><?php echo __('Source'); ?></th>
                    <th><?php echo __('Target'); ?></th>
                    <th><?php echo __('Status'); ?></th>
                    <th><?php echo __('Tool'); ?></th>
                    <th><?php echo __('Created'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentConversions)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-3">
                        <?php echo __('No format conversions performed yet'); ?>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($recentConversions as $conv): ?>
                    <tr>
                        <td>
                            <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'object', 'id' => $conv->digital_object_id]); ?>">
                                <?php echo htmlspecialchars(substr($conv->filename ?? 'Unknown', 0, 30)); ?>
                            </a>
                        </td>
                        <td><small><?php echo htmlspecialchars($conv->source_format ?? '-'); ?></small></td>
                        <td><small><?php echo htmlspecialchars($conv->target_format ?? '-'); ?></small></td>
                        <td>
                            <?php if ($conv->status === 'completed'): ?>
                                <span class="badge bg-success">Completed</span>
                            <?php elseif ($conv->status === 'processing'): ?>
                                <span class="badge bg-info">Processing</span>
                            <?php elseif ($conv->status === 'pending'): ?>
                                <span class="badge bg-warning">Pending</span>
                            <?php elseif ($conv->status === 'failed'): ?>
                                <span class="badge bg-danger">Failed</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?php echo ucfirst($conv->status); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><small><?php echo htmlspecialchars($conv->conversion_tool ?? '-'); ?></small></td>
                        <td><small class="text-muted"><?php echo date('Y-m-d H:i', strtotime($conv->created_at)); ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php end_slot() ?>
