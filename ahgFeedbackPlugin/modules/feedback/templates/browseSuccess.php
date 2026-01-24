<?php decorate_with('layout_2col.php') ?>

<?php slot('title') ?>
<div class="d-flex align-items-center">
    <i class="fas fa-comments fa-lg text-primary me-3"></i>
    <div>
        <h1 class="h3 mb-0"><?php echo __('Feedback Management') ?></h1>
        <small class="text-muted"><?php echo __('Review and manage user feedback') ?></small>
    </div>
</div>
<?php end_slot() ?>

<?php slot('sidebar') ?>
<div class="card mb-3">
    <div class="card-header bg-primary text-white">
        <i class="fas fa-filter me-1"></i> <?php echo __('Filter') ?>
    </div>
    <div class="list-group list-group-flush">
        <a href="<?php echo url_for(['module' => 'feedback', 'action' => 'browse', 'filter' => 'all']) ?>" 
           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo ('all' === $filter || !$filter) ? 'active' : '' ?>">
            <?php echo __('All Feedback') ?>
            <span class="badge bg-secondary rounded-pill"><?php echo $totalCount ?></span>
        </a>
        <a href="<?php echo url_for(['module' => 'feedback', 'action' => 'browse', 'filter' => 'pending']) ?>" 
           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo ('pending' === $filter) ? 'active' : '' ?>">
            <?php echo __('Pending') ?>
            <span class="badge bg-warning text-dark rounded-pill"><?php echo $pendingCount ?></span>
        </a>
        <a href="<?php echo url_for(['module' => 'feedback', 'action' => 'browse', 'filter' => 'completed']) ?>" 
           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo ('completed' === $filter) ? 'active' : '' ?>">
            <?php echo __('Completed') ?>
            <span class="badge bg-success rounded-pill"><?php echo $completedCount ?></span>
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header bg-light">
        <i class="fas fa-plus me-1"></i> <?php echo __('Actions') ?>
    </div>
    <div class="card-body">
        <a href="<?php echo url_for(['module' => 'feedback', 'action' => 'general']) ?>" class="btn btn-outline-primary btn-sm w-100">
            <i class="fas fa-plus me-1"></i> <?php echo __('Add General Feedback') ?>
        </a>
    </div>
</div>
<?php end_slot() ?>

<?php slot('content') ?>
<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (count($feedbackItems) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:5%">#</th>
                        <th style="width:20%">
                            <a href="<?php echo url_for(['module' => 'feedback', 'action' => 'browse', 'filter' => $filter, 'sort' => ($sort === 'nameUp') ? 'nameDown' : 'nameUp']) ?>" class="text-decoration-none text-dark">
                                <?php echo __('Subject/Record') ?>
                                <?php if ('nameUp' === $sort): ?><i class="fas fa-sort-up ms-1"></i><?php elseif ('nameDown' === $sort): ?><i class="fas fa-sort-down ms-1"></i><?php endif; ?>
                            </a>
                        </th>
                        <th style="width:10%"><?php echo __('Type') ?></th>
                        <th style="width:25%"><?php echo __('Remarks') ?></th>
                        <th style="width:20%"><?php echo __('Contact') ?></th>
                        <th style="width:12%">
                            <a href="<?php echo url_for(['module' => 'feedback', 'action' => 'browse', 'filter' => $filter, 'sort' => ($sort === 'dateUp') ? 'dateDown' : 'dateUp']) ?>" class="text-decoration-none text-dark">
                                <?php echo __('Date') ?>
                                <?php if ('dateUp' === $sort): ?><i class="fas fa-sort-up ms-1"></i><?php elseif ('dateDown' === $sort): ?><i class="fas fa-sort-down ms-1"></i><?php endif; ?>
                            </a>
                        </th>
                        <th style="width:8%" class="text-center"><?php echo __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php $counter = ($currentPage - 1) * $limit; ?>
                <?php foreach ($feedbackItems as $item): ?>
                <?php $counter++; ?>
                <?php
                    $isPending = ($item->status_id == QubitTerm::PENDING_ID);
                    
                    $typeLabels = [
                        0 => ['label' => 'General', 'class' => 'bg-secondary'],
                        1 => ['label' => 'Error', 'class' => 'bg-danger'],
                        2 => ['label' => 'Suggestion', 'class' => 'bg-info'],
                        3 => ['label' => 'Correction', 'class' => 'bg-primary'],
                        4 => ['label' => 'Assistance', 'class' => 'bg-warning text-dark']
                    ];
                    $type = $typeLabels[$item->feed_type_id] ?? $typeLabels[0];
                    
                    $linkedObject = $item->object_id ? QubitInformationObject::getById($item->object_id) : null;
                ?>
                <tr class="<?php echo $isPending ? '' : 'table-light' ?>">
                    <td class="text-muted"><?php echo $counter ?></td>
                    <td>
                        <?php if ($linkedObject): ?>
                            <a href="<?php echo url_for([$linkedObject, 'module' => 'informationobject']) ?>" class="text-decoration-none">
                                <?php echo esc_entities(mb_strimwidth($linkedObject->getTitle(['cultureFallback' => true]), 0, 40, '...')) ?>
                            </a>
                        <?php else: ?>
                            <span class="fst-italic"><?php echo esc_entities($item->name) ?: __('General Feedback') ?></span>
                        <?php endif; ?>
                        <br>
                        <?php if ($isPending): ?>
                            <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i><?php echo __('Pending') ?></span>
                        <?php else: ?>
                            <span class="badge bg-success"><i class="fas fa-check me-1"></i><?php echo __('Completed') ?></span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?php echo $type['class'] ?>"><?php echo __($type['label']) ?></span></td>
                    <td>
                        <div class="text-truncate" style="max-width:200px" title="<?php echo esc_entities($item->remarks) ?>">
                            <?php echo esc_entities(mb_strimwidth($item->remarks, 0, 60, '...')) ?>
                        </div>
                    </td>
                    <td>
                        <strong><?php echo esc_entities($item->feed_name . ' ' . $item->feed_surname) ?></strong>
                        <?php if ($item->feed_email): ?>
                            <br><small class="text-muted"><i class="fas fa-envelope me-1"></i><?php echo esc_entities($item->feed_email) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <small><?php echo date('d M Y', strtotime($item->created_at)) ?></small>
                        <?php if ($item->completed_at): ?>
                            <br><small class="text-success"><i class="fas fa-check me-1"></i><?php echo date('d M Y', strtotime($item->completed_at)) ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <a href="<?php echo url_for(['module' => 'feedback', 'action' => 'edit', 'id' => $item->id]) ?>" 
                               class="btn btn-outline-primary" title="<?php echo __('Edit') ?>">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="<?php echo url_for(['module' => 'feedback', 'action' => 'delete', 'id' => $item->id]) ?>" 
                               class="btn btn-outline-danger" title="<?php echo __('Delete') ?>"
                               onclick="return confirm('<?php echo __('Are you sure?') ?>');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
            <h5 class="text-muted"><?php echo __('No feedback found') ?></h5>
            <p class="text-muted mb-0"><?php echo __('There are no feedback submissions matching your filter.') ?></p>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if ($totalPages > 1): ?>
    <div class="card-footer bg-white">
        <nav>
            <ul class="pagination justify-content-center mb-0">
                <?php if ($currentPage > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="<?php echo url_for(['module' => 'feedback', 'action' => 'browse', 'filter' => $filter, 'sort' => $sort, 'page' => $currentPage - 1]) ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for ($p = max(1, $currentPage - 2); $p <= min($totalPages, $currentPage + 2); $p++): ?>
                <li class="page-item <?php echo ($p == $currentPage) ? 'active' : '' ?>">
                    <a class="page-link" href="<?php echo url_for(['module' => 'feedback', 'action' => 'browse', 'filter' => $filter, 'sort' => $sort, 'page' => $p]) ?>"><?php echo $p ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($currentPage < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="<?php echo url_for(['module' => 'feedback', 'action' => 'browse', 'filter' => $filter, 'sort' => $sort, 'page' => $currentPage + 1]) ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <p class="text-center text-muted small mb-0"><?php echo __('Showing %1% of %2% results', ['%1%' => count($feedbackItems), '%2%' => $totalResults]) ?></p>
    </div>
    <?php endif; ?>
</div>
<?php end_slot() ?>
