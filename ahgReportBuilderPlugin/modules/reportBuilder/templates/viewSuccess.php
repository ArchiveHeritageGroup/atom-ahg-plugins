<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="bi bi-file-earmark-bar-graph text-primary me-2"></i><?php echo htmlspecialchars($report->name); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<!-- Report Header -->
<div class="mb-4">
    <h4 class="mb-1"><?php echo htmlspecialchars($report->name); ?></h4>
    <?php if ($report->description): ?>
    <p class="text-muted mb-0"><?php echo htmlspecialchars($report->description); ?></p>
    <?php endif; ?>
    <small class="text-muted">
        <?php echo __('Generated:'); ?> <?php echo date('Y-m-d H:i'); ?> |
        <?php echo __('Total:'); ?> <?php echo number_format($results['total']); ?> <?php echo __('records'); ?>
    </small>
</div>

<!-- Results Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0">
            <thead class="table-light">
                <tr>
                    <?php foreach ($report->columns as $col): ?>
                    <th class="text-nowrap"><?php echo $allColumns[$col]['label'] ?? $col; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($results['results'])): ?>
                <tr>
                    <td colspan="<?php echo count($report->columns); ?>" class="text-center text-muted py-5">
                        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                        <?php echo __('No data found.'); ?>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($results['results'] as $row): ?>
                    <tr>
                        <?php foreach ($report->columns as $col): ?>
                        <td>
                            <?php
                            $value = $row->{$col} ?? '';
                            $colType = $allColumns[$col]['type'] ?? 'string';

                            if ($colType === 'datetime' && $value) {
                                echo date('Y-m-d H:i', strtotime($value));
                            } elseif ($colType === 'date' && $value) {
                                echo date('Y-m-d', strtotime($value));
                            } elseif ($colType === 'boolean') {
                                echo $value ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-x-circle text-muted"></i>';
                            } elseif ($colType === 'text' && strlen($value) > 100) {
                                echo htmlspecialchars(substr($value, 0, 100)) . '...';
                            } else {
                                echo htmlspecialchars($value);
                            }
                            ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($results['pages'] > 1): ?>
    <div class="card-footer">
        <nav aria-label="Report pagination">
            <ul class="pagination justify-content-center mb-0">
                <?php if ($results['page'] > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $results['page'] - 1; ?>&limit=<?php echo $results['limit']; ?>">&laquo; <?php echo __('Previous'); ?></a>
                </li>
                <?php endif; ?>

                <li class="page-item disabled">
                    <span class="page-link"><?php echo __('Page'); ?> <?php echo $results['page']; ?> <?php echo __('of'); ?> <?php echo $results['pages']; ?></span>
                </li>

                <?php if ($results['page'] < $results['pages']): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $results['page'] + 1; ?>&limit=<?php echo $results['limit']; ?>"><?php echo __('Next'); ?> &raquo;</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>
<?php end_slot() ?>
