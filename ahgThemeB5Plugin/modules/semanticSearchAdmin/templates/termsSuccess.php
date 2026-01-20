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
    </div>
</div>
