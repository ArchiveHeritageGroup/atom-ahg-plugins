<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1>
    <?php
    $typeIcon = ['sip' => 'box-arrow-in-right', 'aip' => 'safe', 'dip' => 'box-arrow-right'][$package->package_type] ?? 'archive';
    $typeClass = ['sip' => 'info', 'aip' => 'success', 'dip' => 'warning'][$package->package_type] ?? 'secondary';
    ?>
    <i class="bi bi-<?php echo $typeIcon; ?> text-<?php echo $typeClass; ?> me-2"></i>
    <?php echo htmlspecialchars($package->name); ?>
</h1>
<?php end_slot() ?>

<?php slot('content') ?>

<!-- Navigation -->
<div class="mb-4">
    <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'packages']); ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i><?php echo __('Back to Packages'); ?>
    </a>
    <?php if ('draft' === $package->status): ?>
    <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'packageEdit', 'id' => $package->id]); ?>" class="btn btn-outline-primary ms-2">
        <i class="bi bi-pencil me-1"></i><?php echo __('Edit'); ?>
    </a>
    <?php endif; ?>
    <?php if ($package->export_path): ?>
    <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'packageDownload', 'id' => $package->id]); ?>" class="btn btn-success ms-2">
        <i class="bi bi-download me-1"></i><?php echo __('Download Export'); ?>
    </a>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Package Details -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-archive me-2"></i><?php echo __('Package Details'); ?></span>
                <?php
                $statusClass = [
                    'draft' => 'secondary',
                    'building' => 'warning',
                    'complete' => 'info',
                    'validated' => 'primary',
                    'exported' => 'success',
                    'error' => 'danger'
                ][$package->status] ?? 'secondary';
                ?>
                <span class="badge bg-<?php echo $statusClass; ?> fs-6"><?php echo ucfirst($package->status); ?></span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl>
                            <dt><?php echo __('UUID'); ?></dt>
                            <dd><code><?php echo $package->uuid; ?></code></dd>

                            <dt><?php echo __('Package Type'); ?></dt>
                            <dd>
                                <span class="badge bg-<?php echo $typeClass; ?>">
                                    <?php echo strtoupper($package->package_type); ?>
                                </span>
                                <?php
                                $typeLabels = ['sip' => 'Submission Information Package', 'aip' => 'Archival Information Package', 'dip' => 'Dissemination Information Package'];
                                echo ' - ' . ($typeLabels[$package->package_type] ?? '');
                                ?>
                            </dd>

                            <dt><?php echo __('Package Format'); ?></dt>
                            <dd><?php echo ucfirst($package->package_format); ?> (<?php echo strtoupper($package->manifest_algorithm); ?>)</dd>

                            <?php if ($package->description): ?>
                            <dt><?php echo __('Description'); ?></dt>
                            <dd><?php echo nl2br(htmlspecialchars($package->description)); ?></dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl>
                            <dt><?php echo __('Objects'); ?></dt>
                            <dd><?php echo number_format($package->object_count); ?> files</dd>

                            <dt><?php echo __('Total Size'); ?></dt>
                            <dd><?php echo $package->total_size ? formatBytes($package->total_size) : '-'; ?></dd>

                            <?php if ($package->package_checksum): ?>
                            <dt><?php echo __('Package Checksum'); ?></dt>
                            <dd><code class="small"><?php echo $package->package_checksum; ?></code></dd>
                            <?php endif; ?>

                            <?php if ($package->originator): ?>
                            <dt><?php echo __('Originator'); ?></dt>
                            <dd><?php echo htmlspecialchars($package->originator); ?></dd>
                            <?php endif; ?>

                            <?php if ($package->submission_agreement): ?>
                            <dt><?php echo __('Submission Agreement'); ?></dt>
                            <dd><?php echo htmlspecialchars($package->submission_agreement); ?></dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Package Objects -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-files me-2"></i><?php echo __('Package Objects'); ?></span>
                <span class="badge bg-primary"><?php echo count($objects); ?></span>
            </div>
            <?php if (!empty($objects)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th><?php echo __('File'); ?></th>
                            <th><?php echo __('Format'); ?></th>
                            <th><?php echo __('Size'); ?></th>
                            <th><?php echo __('Checksum'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($objects as $i => $obj): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($obj->file_name); ?></strong>
                                <br>
                                <small class="text-muted"><?php echo $obj->relative_path; ?></small>
                                <?php if ($obj->information_object_title): ?>
                                <br>
                                <small class="text-info"><?php echo htmlspecialchars($obj->information_object_title); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($obj->puid): ?>
                                <span class="badge bg-info"><?php echo $obj->puid; ?></span>
                                <?php endif; ?>
                                <br>
                                <small class="text-muted"><?php echo $obj->mime_type ?? 'Unknown'; ?></small>
                            </td>
                            <td><?php echo $obj->file_size ? formatBytes($obj->file_size) : '-'; ?></td>
                            <td>
                                <?php if ($obj->checksum_value): ?>
                                <code class="small"><?php echo substr($obj->checksum_value, 0, 12); ?>...</code>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="card-body text-center text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                <?php echo __('No objects in this package'); ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Package Events -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-clock-history me-2"></i><?php echo __('Package Events'); ?>
            </div>
            <?php if (!empty($events)): ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo __('Event'); ?></th>
                            <th><?php echo __('Detail'); ?></th>
                            <th><?php echo __('Outcome'); ?></th>
                            <th><?php echo __('Time'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                        <tr>
                            <td>
                                <span class="badge bg-secondary"><?php echo $event->event_type; ?></span>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($event->event_detail ?? '-'); ?>
                                <?php if ($event->event_outcome_detail): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($event->event_outcome_detail, 0, 100)); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $outcomeClass = ['success' => 'success', 'failure' => 'danger', 'warning' => 'warning'][$event->event_outcome] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $outcomeClass; ?>"><?php echo $event->event_outcome; ?></span>
                            </td>
                            <td>
                                <small><?php echo date('Y-m-d H:i:s', strtotime($event->event_datetime)); ?></small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="card-body text-center text-muted">
                <?php echo __('No events recorded'); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Timeline -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-calendar3 me-2"></i><?php echo __('Timeline'); ?>
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                    <span><?php echo __('Created'); ?></span>
                    <span><?php echo date('Y-m-d H:i', strtotime($package->created_at)); ?></span>
                </li>
                <?php if ($package->created_by): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span><?php echo __('Created By'); ?></span>
                    <span><?php echo htmlspecialchars($package->created_by); ?></span>
                </li>
                <?php endif; ?>
                <?php if ($package->built_at): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span><?php echo __('Built'); ?></span>
                    <span class="text-success"><?php echo date('Y-m-d H:i', strtotime($package->built_at)); ?></span>
                </li>
                <?php endif; ?>
                <?php if ($package->validated_at): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span><?php echo __('Validated'); ?></span>
                    <span class="text-primary"><?php echo date('Y-m-d H:i', strtotime($package->validated_at)); ?></span>
                </li>
                <?php endif; ?>
                <?php if ($package->exported_at): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span><?php echo __('Exported'); ?></span>
                    <span class="text-success"><?php echo date('Y-m-d H:i', strtotime($package->exported_at)); ?></span>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- File Paths -->
        <?php if ($package->source_path || $package->export_path): ?>
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-folder me-2"></i><?php echo __('File Paths'); ?>
            </div>
            <div class="card-body">
                <?php if ($package->source_path): ?>
                <dt><?php echo __('Source Path'); ?></dt>
                <dd><code class="small"><?php echo $package->source_path; ?></code></dd>
                <?php endif; ?>

                <?php if ($package->export_path): ?>
                <dt><?php echo __('Export Path'); ?></dt>
                <dd><code class="small"><?php echo $package->export_path; ?></code></dd>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Related Packages -->
        <?php if ($parentPackage || !empty($childPackages)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-diagram-3 me-2"></i><?php echo __('Related Packages'); ?>
            </div>
            <ul class="list-group list-group-flush">
                <?php if ($parentPackage): ?>
                <li class="list-group-item">
                    <small class="text-muted d-block"><?php echo __('Parent Package'); ?></small>
                    <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'packageView', 'id' => $parentPackage->id]); ?>">
                        <i class="bi bi-arrow-up-circle me-1"></i>
                        <?php echo htmlspecialchars($parentPackage->name); ?>
                    </a>
                    <span class="badge bg-<?php echo ['sip' => 'info', 'aip' => 'success', 'dip' => 'warning'][$parentPackage->package_type] ?? 'secondary'; ?> ms-2">
                        <?php echo strtoupper($parentPackage->package_type); ?>
                    </span>
                </li>
                <?php endif; ?>

                <?php foreach ($childPackages as $child): ?>
                <li class="list-group-item">
                    <small class="text-muted d-block"><?php echo __('Derived Package'); ?></small>
                    <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'packageView', 'id' => $child->id]); ?>">
                        <i class="bi bi-arrow-down-circle me-1"></i>
                        <?php echo htmlspecialchars($child->name); ?>
                    </a>
                    <span class="badge bg-<?php echo ['sip' => 'info', 'aip' => 'success', 'dip' => 'warning'][$child->package_type] ?? 'secondary'; ?> ms-2">
                        <?php echo strtoupper($child->package_type); ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- BagIt Structure (if built) -->
        <?php if ($package->source_path && is_dir($package->source_path)): ?>
        <div class="card">
            <div class="card-header">
                <i class="bi bi-file-earmark-code me-2"></i><?php echo __('BagIt Structure'); ?>
            </div>
            <div class="card-body">
                <pre class="mb-0 small bg-light p-2 rounded"><?php
                echo htmlspecialchars($package->uuid) . "/\n";
                echo "  bagit.txt\n";
                echo "  bag-info.txt\n";
                echo "  manifest-" . $package->manifest_algorithm . ".txt\n";
                echo "  tagmanifest-" . $package->manifest_algorithm . ".txt\n";
                echo "  data/\n";
                foreach (array_slice($objects, 0, 3) as $obj) {
                    echo "    " . basename($obj->relative_path) . "\n";
                }
                if (count($objects) > 3) {
                    echo "    ... (" . (count($objects) - 3) . " more files)\n";
                }
                ?></pre>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
function formatBytes($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' bytes';
}
?>

<?php end_slot() ?>
