<?php use_helper('Text'); ?>
<?php $term = $sf_data->getRaw('term'); ?>
<?php $synonyms = $sf_data->getRaw('synonyms'); ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">
            <a href="<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'index']); ?>" class="text-decoration-none text-muted">
                <i class="fas fa-brain me-2"></i><?php echo __('Semantic Search'); ?>
            </a>
            <i class="fas fa-chevron-right mx-2 small text-muted"></i>
            <a href="<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'terms']); ?>" class="text-decoration-none text-muted">
                <?php echo __('Terms'); ?>
            </a>
            <i class="fas fa-chevron-right mx-2 small text-muted"></i>
            <code><?php echo htmlspecialchars($term->term); ?></code>
        </h1>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo __('Term Details'); ?></h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4"><?php echo __('Term'); ?></dt>
                        <dd class="col-sm-8"><strong><?php echo htmlspecialchars($term->term); ?></strong></dd>

                        <dt class="col-sm-4"><?php echo __('Source'); ?></dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-<?php echo $term->source === 'local' ? 'secondary' : ($term->source === 'wordnet' ? 'info' : 'dark'); ?>">
                                <?php echo ucfirst($term->source); ?>
                            </span>
                        </dd>

                        <dt class="col-sm-4"><?php echo __('Domain'); ?></dt>
                        <dd class="col-sm-8"><?php echo $term->domain ?? '-'; ?></dd>

                        <dt class="col-sm-4"><?php echo __('Created'); ?></dt>
                        <dd class="col-sm-8"><?php echo $term->created_at ? date('M j, Y H:i', strtotime($term->created_at)) : '-'; ?></dd>

                        <?php if ($term->external_id): ?>
                        <dt class="col-sm-4"><?php echo __('External ID'); ?></dt>
                        <dd class="col-sm-8"><code><?php echo htmlspecialchars($term->external_id); ?></code></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>

            <?php $embedding = $sf_data->getRaw('embedding'); ?>
            <?php if ($embedding): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-vector-square me-2"></i><?php echo __('Embedding'); ?></h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-2"><?php echo __('Model:'); ?> <code><?php echo $embedding->model; ?></code></p>
                    <p class="small text-muted mb-0"><?php echo __('Generated:'); ?> <?php echo date('M j, Y', strtotime($embedding->created_at)); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?php echo __('Synonyms'); ?> (<?php echo count($synonyms); ?>)</h5>
                </div>
                <div class="card-body p-0">
                    <?php if ($synonyms && count($synonyms) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?php echo __('Synonym'); ?></th>
                                    <th><?php echo __('Type'); ?></th>
                                    <th><?php echo __('Weight'); ?></th>
                                    <th><?php echo __('Source'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($synonyms as $syn): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($syn->synonym_text); ?></strong></td>
                                    <td>
                                        <?php
                                        $typeColors = [
                                            'exact' => 'success',
                                            'related' => 'info',
                                            'broader' => 'warning',
                                            'narrower' => 'secondary'
                                        ];
                                        $color = $typeColors[$syn->relationship_type] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $color; ?>"><?php echo $syn->relationship_type; ?></span>
                                    </td>
                                    <td>
                                        <div class="progress" style="width: 60px; height: 6px;">
                                            <div class="progress-bar" style="width: <?php echo ($syn->weight * 100); ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?php echo number_format($syn->weight, 2); ?></small>
                                    </td>
                                    <td><span class="text-muted small"><?php echo $syn->source; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                        <?php echo __('No synonyms defined for this term.'); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
