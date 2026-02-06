<?php use_helper('Text'); ?>
<?php $settings = $sf_data->getRaw('settings'); ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">
            <a href="<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'index']); ?>" class="text-decoration-none text-muted">
                <i class="fas fa-brain me-2"></i><?php echo __('Semantic Search'); ?>
            </a>
            <i class="fas fa-chevron-right mx-2 small text-muted"></i>
            <?php echo __('Settings'); ?>
        </h1>
    </div>

    <?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo $sf_user->getFlash('success'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <form method="post" action="<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'config']); ?>">
        <div class="row">
            <!-- General Settings -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-cog me-2"></i><?php echo __('General Settings'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="semantic_search_enabled"
                                   name="settings[semantic_search_enabled]" value="1"
                                   <?php echo ($settings['semantic_search_enabled'] ?? false) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="semantic_search_enabled">
                                <strong><?php echo __('Enable Semantic Search'); ?></strong>
                            </label>
                            <div class="form-text"><?php echo __('When enabled, search queries will be expanded with synonyms.'); ?></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="semantic_expansion_limit"><?php echo __('Expansion Limit'); ?></label>
                            <input type="number" class="form-control" id="semantic_expansion_limit"
                                   name="settings[semantic_expansion_limit]"
                                   value="<?php echo $settings['semantic_expansion_limit'] ?? 5; ?>" min="1" max="20">
                            <div class="form-text"><?php echo __('Maximum number of synonyms per term.'); ?></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="semantic_min_weight"><?php echo __('Minimum Weight'); ?></label>
                            <input type="number" class="form-control" id="semantic_min_weight"
                                   name="settings[semantic_min_weight]"
                                   value="<?php echo $settings['semantic_min_weight'] ?? 0.6; ?>"
                                   min="0" max="1" step="0.1">
                            <div class="form-text"><?php echo __('Minimum relevance weight for synonyms (0.0 - 1.0).'); ?></div>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="semantic_show_expansion"
                                   name="settings[semantic_show_expansion]" value="1"
                                   <?php echo ($settings['semantic_show_expansion'] ?? true) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="semantic_show_expansion">
                                <?php echo __('Show Expansion Info'); ?>
                            </label>
                            <div class="form-text"><?php echo __('Display which synonyms were used on search results page.'); ?></div>
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="semantic_log_searches"
                                   name="settings[semantic_log_searches]" value="1"
                                   <?php echo ($settings['semantic_log_searches'] ?? true) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="semantic_log_searches">
                                <?php echo __('Log Searches'); ?>
                            </label>
                            <div class="form-text"><?php echo __('Keep a log of expanded searches for analysis.'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Sources -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-database me-2"></i><?php echo __('Data Sources'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="semantic_local_synonyms"
                                   name="settings[semantic_local_synonyms]" value="1"
                                   <?php echo ($settings['semantic_local_synonyms'] ?? true) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="semantic_local_synonyms">
                                <i class="fas fa-file-alt me-1 text-secondary"></i><?php echo __('Local Synonyms'); ?>
                            </label>
                            <div class="form-text"><?php echo __('Use locally defined archival, museum, and library terms.'); ?></div>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="semantic_wordnet_enabled"
                                   name="settings[semantic_wordnet_enabled]" value="1"
                                   <?php echo ($settings['semantic_wordnet_enabled'] ?? false) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="semantic_wordnet_enabled">
                                <i class="fas fa-cloud me-1 text-info"></i><?php echo __('WordNet (Datamuse API)'); ?>
                            </label>
                            <div class="form-text"><?php echo __('Fetch synonyms from WordNet via Datamuse API.'); ?></div>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="semantic_wikidata_enabled"
                                   name="settings[semantic_wikidata_enabled]" value="1"
                                   <?php echo ($settings['semantic_wikidata_enabled'] ?? false) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="semantic_wikidata_enabled">
                                <i class="fas fa-globe me-1 text-dark"></i><?php echo __('Wikidata'); ?>
                            </label>
                            <div class="form-text"><?php echo __('Fetch heritage and archival terms from Wikidata SPARQL.'); ?></div>
                        </div>

                        <hr>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="semantic_ollama_enabled"
                                   name="settings[semantic_ollama_enabled]" value="1"
                                   <?php echo ($settings['semantic_ollama_enabled'] ?? false) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="semantic_ollama_enabled">
                                <i class="fas fa-robot me-1 text-purple"></i><?php echo __('Ollama Embeddings'); ?>
                            </label>
                            <div class="form-text"><?php echo __('Use Ollama for vector embeddings and semantic similarity.'); ?></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="semantic_ollama_endpoint"><?php echo __('Ollama Endpoint'); ?></label>
                            <input type="url" class="form-control" id="semantic_ollama_endpoint"
                                   name="settings[semantic_ollama_endpoint]"
                                   value="<?php echo htmlspecialchars($settings['semantic_ollama_endpoint'] ?? 'http://localhost:11434'); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="semantic_ollama_model"><?php echo __('Ollama Model'); ?></label>
                            <select class="form-select" id="semantic_ollama_model" name="settings[semantic_ollama_model]">
                                <?php
                                $models = ['nomic-embed-text', 'mxbai-embed-large', 'all-minilm', 'snowflake-arctic-embed'];
                                $current = $settings['semantic_ollama_model'] ?? 'nomic-embed-text';
                                foreach ($models as $model): ?>
                                <option value="<?php echo $model; ?>" <?php echo $model === $current ? 'selected' : ''; ?>>
                                    <?php echo $model; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Elasticsearch Integration -->
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fab fa-searchengin me-2"></i><?php echo __('Elasticsearch Integration'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="semantic_es_synonyms_path"><?php echo __('Synonyms File Path'); ?></label>
                                    <input type="text" class="form-control" id="semantic_es_synonyms_path"
                                           name="settings[semantic_es_synonyms_path]"
                                           value="<?php echo htmlspecialchars($settings['semantic_es_synonyms_path'] ?? '/etc/elasticsearch/synonyms/ahg_synonyms.txt'); ?>">
                                    <div class="form-text"><?php echo __('Path where the Elasticsearch synonyms file will be exported.'); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('Export Synonyms'); ?></label>
                                    <div>
                                        <button type="button" class="btn btn-outline-primary sync-btn" data-type="elasticsearch">
                                            <i class="fas fa-file-export me-1"></i><?php echo __('Export to Elasticsearch'); ?>
                                        </button>
                                    </div>
                                    <div class="form-text"><?php echo __('Generate synonyms file for Elasticsearch. Requires ES restart to apply.'); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong><?php echo __('Note:'); ?></strong>
                            <?php echo __('After exporting synonyms, you need to restart Elasticsearch for changes to take effect. Add the synonyms filter to your index settings.'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'index']); ?>" class="btn btn-secondary">
                <i class="fas fa-times me-1"></i><?php echo __('Cancel'); ?>
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i><?php echo __('Save Settings'); ?>
            </button>
        </div>
    </form>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.sync-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var type = this.dataset.type;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Processing...';

            fetch('<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'runSync']); ?>?type=' + type, {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-file-export me-1"></i><?php echo __('Export to Elasticsearch'); ?>';
            })
            .catch(error => {
                alert('Error: ' + error.message);
                btn.disabled = false;
            });
        });
    });
});
</script>
