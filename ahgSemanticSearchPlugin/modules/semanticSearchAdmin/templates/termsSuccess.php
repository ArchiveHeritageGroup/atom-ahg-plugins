<?php use_helper('Text'); ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">
            <a href="<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'index']); ?>" class="text-decoration-none text-muted">
                <i class="fas fa-brain me-2"></i><?php echo __('Semantic Search'); ?>
            </a>
            <i class="fas fa-chevron-right mx-2 small text-muted"></i>
            <?php echo __('Terms'); ?>
        </h1>
        <a href="<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'termAdd']); ?>" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i><?php echo __('Add Term'); ?>
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label"><?php echo __('Search'); ?></label>
                    <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($sf_request->getParameter('q', '')); ?>" placeholder="<?php echo __('Search terms...'); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo __('Source'); ?></label>
                    <select class="form-select" name="source">
                        <option value=""><?php echo __('All Sources'); ?></option>
                        <?php foreach ($sf_data->getRaw('sources') as $source): ?>
                        <option value="<?php echo $source->source; ?>" <?php echo $sf_request->getParameter('source') === $source->source ? 'selected' : ''; ?>>
                            <?php echo ucfirst($source->source); ?> (<?php echo number_format($source->count); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-secondary w-100">
                        <i class="fas fa-filter me-1"></i><?php echo __('Filter'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Terms Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>
                <?php echo __('Showing %1% - %2% of %3% terms', [
                    '%1%' => number_format(($currentPage - 1) * $perPage + 1),
                    '%2%' => number_format(min($currentPage * $perPage, $totalCount)),
                    '%3%' => number_format($totalCount)
                ]); ?>
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo __('Term'); ?></th>
                            <th><?php echo __('Source'); ?></th>
                            <th><?php echo __('Domain'); ?></th>
                            <th><?php echo __('Synonyms'); ?></th>
                            <th><?php echo __('Created'); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $terms = $sf_data->getRaw('terms'); ?>
                        <?php if ($terms && count($terms) > 0): ?>
                            <?php foreach ($terms as $term): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($term->term); ?></strong></td>
                                <td>
                                    <span class="badge bg-<?php echo $term->source === 'local' ? 'secondary' : ($term->source === 'wordnet' ? 'info' : 'dark'); ?>">
                                        <?php echo ucfirst($term->source); ?>
                                    </span>
                                </td>
                                <td><span class="text-muted"><?php echo $term->domain ?? '-'; ?></span></td>
                                <td>
                                    <span class="badge bg-success"><?php echo $term->synonym_count; ?></span>
                                </td>
                                <td class="small text-muted">
                                    <?php echo $term->created_at ? date('M j, Y', strtotime($term->created_at)) : '-'; ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'termView', 'id' => $term->id]); ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    <?php echo __('No terms found'); ?>
                                    <br>
                                    <a href="<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'termAdd']); ?>" class="btn btn-primary mt-3">
                                        <i class="fas fa-plus me-1"></i><?php echo __('Add your first term'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="card-footer">
            <nav aria-label="Term pagination">
                <ul class="pagination justify-content-center mb-0">
                    <?php
                    // Build base URL with current filters
                    $baseParams = ['module' => 'semanticSearchAdmin', 'action' => 'terms'];
                    if ($sf_request->getParameter('source')) {
                        $baseParams['source'] = $sf_request->getParameter('source');
                    }
                    if ($sf_request->getParameter('q')) {
                        $baseParams['q'] = $sf_request->getParameter('q');
                    }
                    ?>

                    <!-- Previous button -->
                    <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo url_for(array_merge($baseParams, ['page' => $currentPage - 1])); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>

                    <?php
                    // Show page numbers with ellipsis
                    $showPages = [];
                    $showPages[] = 1;

                    if ($currentPage > 3) {
                        $showPages[] = '...';
                    }

                    for ($i = max(2, $currentPage - 1); $i <= min($totalPages - 1, $currentPage + 1); $i++) {
                        if (!in_array($i, $showPages)) {
                            $showPages[] = $i;
                        }
                    }

                    if ($currentPage < $totalPages - 2) {
                        $showPages[] = '...';
                    }

                    if ($totalPages > 1) {
                        $showPages[] = $totalPages;
                    }

                    foreach ($showPages as $p):
                        if ($p === '...'):
                    ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php else: ?>
                        <li class="page-item <?php echo $p == $currentPage ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo url_for(array_merge($baseParams, ['page' => $p])); ?>"><?php echo $p; ?></a>
                        </li>
                    <?php
                        endif;
                    endforeach;
                    ?>

                    <!-- Next button -->
                    <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo url_for(array_merge($baseParams, ['page' => $currentPage + 1])); ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>
