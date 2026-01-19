<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="bi bi-file-code text-primary me-2"></i><?php echo __('Format Registry'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>

<div class="d-flex justify-content-between mb-4">
    <p class="text-muted mb-0">File formats and their preservation risk assessment based on PRONOM registry.</p>
    <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i><?php echo __('Dashboard'); ?>
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th><?php echo __('Format'); ?></th>
                    <th><?php echo __('MIME Type'); ?></th>
                    <th><?php echo __('Extension'); ?></th>
                    <th><?php echo __('PUID'); ?></th>
                    <th><?php echo __('Risk'); ?></th>
                    <th><?php echo __('Action'); ?></th>
                    <th><?php echo __('Usage'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($formats as $format): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($format->format_name); ?></strong>
                        <?php if ($format->is_preservation_format): ?>
                            <span class="badge bg-success ms-1">Preservation</span>
                        <?php endif; ?>
                    </td>
                    <td><code><?php echo htmlspecialchars($format->mime_type); ?></code></td>
                    <td>.<?php echo htmlspecialchars($format->extension ?? '-'); ?></td>
                    <td><small><?php echo htmlspecialchars($format->puid ?? '-'); ?></small></td>
                    <td>
                        <?php
                        $riskClass = match ($format->risk_level) {
                            'low' => 'success',
                            'medium' => 'warning',
                            'high' => 'danger',
                            'critical' => 'danger',
                            default => 'secondary'
                        };
                        ?>
                        <span class="badge bg-<?php echo $riskClass; ?>"><?php echo ucfirst($format->risk_level); ?></span>
                    </td>
                    <td><small><?php echo ucfirst($format->preservation_action ?? 'none'); ?></small></td>
                    <td>
                        <?php
                        $count = $formatCounts[$format->id]->count ?? 0;
                        echo $count > 0 ? number_format($count) : '-';
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php end_slot() ?>
