<?php use_helper('Text'); ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2"><i class="fas fa-brain me-2"></i><?php echo __('Semantic Search'); ?></h1>
        <div class="d-flex gap-2">
            <a href="<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'config']); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-cog me-1"></i><?php echo __('Settings'); ?>
            </a>
        </div>
    </div>

    <!-- Status Banner -->
    <?php $enabled = $sf_data->getRaw('settings')['semantic_search_enabled'] ?? false; ?>
    <div class="alert <?php echo $enabled ? 'alert-success' : 'alert-warning'; ?> mb-4">
        <div class="d-flex align-items-center">
            <i class="fas <?php echo $enabled ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> fa-2x me-3"></i>
            <div>
                <strong><?php echo $enabled ? __('Semantic Search is Active') : __('Semantic Search is Disabled'); ?></strong>
                <p class="mb-0 small">
                    <?php echo $enabled
                        ? __('Search queries are being expanded with synonyms and related terms.')
                        : __('Enable semantic search in settings to expand search queries.'); ?>
                </p>
            </div>
            <?php if (!$enabled): ?>
            <a href="<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'config']); ?>" class="btn btn-warning ms-auto">
                <?php echo __('Enable Now'); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card h-100 border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted"><?php echo __('Total Terms'); ?></h6>
                            <h2 class="mb-0"><?php echo number_format($stats['terms']['total'] ?? 0); ?></h2>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-book fa-2x"></i>
                        </div>
                    </div>
                    <div class="mt-2 small">
                        <span class="badge bg-info"><?php echo $stats['terms']['local'] ?? 0; ?> local</span>
                        <span class="badge bg-secondary"><?php echo $stats['terms']['wordnet'] ?? 0; ?> WordNet</span>
                        <span class="badge bg-dark"><?php echo $stats['terms']['wikidata'] ?? 0; ?> Wikidata</span>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'terms']); ?>" class="text-primary">
                        <?php echo __('Browse terms'); ?> <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card h-100 border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted"><?php echo __('Total Synonyms'); ?></h6>
                            <h2 class="mb-0"><?php echo number_format($stats['synonyms']['total'] ?? 0); ?></h2>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-exchange-alt fa-2x"></i>
                        </div>
                    </div>
                    <div class="mt-2 small">
                        <span class="badge bg-success"><?php echo $stats['synonyms']['exact'] ?? 0; ?> exact</span>
                        <span class="badge bg-info"><?php echo $stats['synonyms']['related'] ?? 0; ?> related</span>
                        <span class="badge bg-warning text-dark"><?php echo $stats['synonyms']['broader'] ?? 0; ?> broader</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card h-100 border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted"><?php echo __('Embeddings'); ?></h6>
                            <h2 class="mb-0"><?php echo number_format($stats['embeddings'] ?? 0); ?></h2>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-vector-square fa-2x"></i>
                        </div>
                    </div>
                    <div class="mt-2 small text-muted">
                        <?php echo __('Vector embeddings for semantic similarity'); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card h-100 border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted"><?php echo __('Data Sources'); ?></h6>
                            <h2 class="mb-0">
                                <?php
                                $sources = 0;
                                if ($settings['semantic_local_synonyms'] ?? true) $sources++;
                                if ($settings['semantic_wordnet_enabled'] ?? false) $sources++;
                                if ($settings['semantic_wikidata_enabled'] ?? false) $sources++;
                                if ($settings['semantic_ollama_enabled'] ?? false) $sources++;
                                echo $sources;
                                ?> / 4
                            </h2>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-database fa-2x"></i>
                        </div>
                    </div>
                    <div class="mt-2 small">
                        <?php echo __('active sources'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions & Test -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i><?php echo __('Quick Actions'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <button class="btn btn-outline-primary w-100 sync-btn" data-type="local">
                                <i class="fas fa-file-import me-1"></i><?php echo __('Import Local'); ?>
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-outline-info w-100 sync-btn" data-type="wordnet">
                                <i class="fas fa-cloud-download-alt me-1"></i><?php echo __('Sync WordNet'); ?>
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-outline-success w-100 sync-btn" data-type="elasticsearch">
                                <i class="fas fa-file-export me-1"></i><?php echo __('Export to ES'); ?>
                            </button>
                        </div>
                        <div class="col-6">
                            <a href="<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'termAdd']); ?>" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-plus me-1"></i><?php echo __('Add Term'); ?>
                            </a>
                        </div>
                    </div>
                    <div id="sync-result" class="mt-3" style="display: none;">
                        <div class="alert mb-0" role="alert"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-flask me-2"></i><?php echo __('Test Query Expansion'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="input-group mb-3">
                        <input type="text" id="test-query" class="form-control" placeholder="<?php echo __('Enter a search term...'); ?>">
                        <button class="btn btn-primary" type="button" id="test-expand-btn">
                            <i class="fas fa-search me-1"></i><?php echo __('Expand'); ?>
                        </button>
                    </div>
                    <div id="expansion-result" style="display: none;">
                        <h6><?php echo __('Expansions:'); ?></h6>
                        <div id="expansion-terms"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Syncs & Search Logs -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-sync me-2"></i><?php echo __('Recent Syncs'); ?></h5>
                    <a href="<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'syncLogs']); ?>" class="btn btn-sm btn-outline-secondary">
                        <?php echo __('View All'); ?>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?php echo __('Source'); ?></th>
                                    <th><?php echo __('Status'); ?></th>
                                    <th><?php echo __('Terms'); ?></th>
                                    <th><?php echo __('Date'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $syncs = $sf_data->getRaw('recentSyncs'); ?>
                                <?php if ($syncs && count($syncs) > 0): ?>
                                    <?php foreach ($syncs as $sync): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-<?php echo $sync->source === 'wordnet' ? 'info' : ($sync->source === 'wikidata' ? 'dark' : 'secondary'); ?>">
                                                <?php echo ucfirst($sync->source); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($sync->status === 'completed'): ?>
                                                <span class="text-success"><i class="fas fa-check-circle"></i></span>
                                            <?php elseif ($sync->status === 'running'): ?>
                                                <span class="text-warning"><i class="fas fa-spinner fa-spin"></i></span>
                                            <?php else: ?>
                                                <span class="text-danger"><i class="fas fa-times-circle"></i></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo number_format(($sync->terms_added ?? 0) + ($sync->terms_updated ?? 0)); ?></td>
                                        <td class="small text-muted"><?php echo date('M j, H:i', strtotime($sync->started_at)); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                            <?php echo __('No sync history yet'); ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-search me-2"></i><?php echo __('Recent Searches'); ?></h5>
                    <a href="<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'searchLogs']); ?>" class="btn btn-sm btn-outline-secondary">
                        <?php echo __('View All'); ?>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?php echo __('Query'); ?></th>
                                    <th><?php echo __('Expanded'); ?></th>
                                    <th><?php echo __('Date'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $searches = $sf_data->getRaw('recentSearches'); ?>
                                <?php if ($searches && count($searches) > 0): ?>
                                    <?php foreach ($searches as $search): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($search->original_query); ?></code></td>
                                        <td>
                                            <?php if ($search->was_expanded): ?>
                                                <span class="text-success"><i class="fas fa-check"></i></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted"><?php echo date('M j, H:i', strtotime($search->created_at)); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                            <?php echo __('No search logs yet'); ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    // Sync buttons
    document.querySelectorAll('.sync-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var type = this.dataset.type;
            var resultDiv = document.getElementById('sync-result');
            var alertDiv = resultDiv.querySelector('.alert');

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Processing...';

            fetch('<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'runSync']); ?>?type=' + type, {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
            .then(response => response.json())
            .then(data => {
                resultDiv.style.display = 'block';
                alertDiv.className = 'alert mb-0 alert-' + (data.success ? 'success' : 'danger');
                alertDiv.innerHTML = data.message;
                btn.disabled = false;
                btn.innerHTML = btn.innerHTML.replace('<i class="fas fa-spinner fa-spin me-1"></i>Processing...', btn.innerHTML);
                location.reload();
            })
            .catch(error => {
                resultDiv.style.display = 'block';
                alertDiv.className = 'alert mb-0 alert-danger';
                alertDiv.innerHTML = 'Error: ' + error.message;
                btn.disabled = false;
            });
        });
    });

    // Test expansion
    document.getElementById('test-expand-btn').addEventListener('click', function() {
        var query = document.getElementById('test-query').value;
        if (!query) return;

        var resultDiv = document.getElementById('expansion-result');
        var termsDiv = document.getElementById('expansion-terms');

        fetch('<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'testExpand']); ?>?query=' + encodeURIComponent(query))
        .then(response => response.json())
        .then(data => {
            resultDiv.style.display = 'block';
            if (data.success && Object.keys(data.expansions).length > 0) {
                var html = '';
                for (var term in data.expansions) {
                    html += '<div class="mb-2"><strong>' + term + '</strong> &rarr; ';
                    html += data.expansions[term].map(function(s) {
                        return '<span class="badge bg-secondary">' + s + '</span>';
                    }).join(' ');
                    html += '</div>';
                }
                termsDiv.innerHTML = html;
            } else {
                termsDiv.innerHTML = '<div class="text-muted">No expansions found for this query.</div>';
            }
        });
    });

    // Allow Enter key for test
    document.getElementById('test-query').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            document.getElementById('test-expand-btn').click();
        }
    });
});
</script>
