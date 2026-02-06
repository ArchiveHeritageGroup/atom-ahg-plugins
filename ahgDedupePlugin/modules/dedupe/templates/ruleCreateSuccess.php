<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'index']); ?>">Duplicate Detection</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'rules']); ?>">Rules</a></li>
                    <li class="breadcrumb-item active">Create Rule</li>
                </ol>
            </nav>
            <h1><i class="fas fa-plus me-2"></i>Create Detection Rule</h1>
        </div>
    </div>

    <form method="post">
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Rule Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Rule Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required
                                   placeholder="e.g., Title Similarity Check">
                        </div>

                        <div class="mb-3">
                            <label for="rule_type" class="form-label">Rule Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="rule_type" name="rule_type" required>
                                <option value="">-- Select Type --</option>
                                <?php foreach ($ruleTypes as $value => $label): ?>
                                    <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="threshold" class="form-label">Threshold (0.0 - 1.0) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="threshold" name="threshold"
                                           min="0" max="1" step="0.01" value="0.80" required>
                                    <div class="form-text">Minimum similarity score to flag as duplicate</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="priority" class="form-label">Priority</label>
                                    <input type="number" class="form-control" id="priority" name="priority"
                                           value="100" min="1" max="1000">
                                    <div class="form-text">Higher priority rules run first</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="repository_id" class="form-label">Apply to Repository</label>
                            <select class="form-select" id="repository_id" name="repository_id">
                                <option value="">All Repositories (Global)</option>
                                <?php foreach ($repositories as $repo): ?>
                                    <option value="<?php echo $repo->id; ?>">
                                        <?php echo htmlspecialchars($repo->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="config_json" class="form-label">Configuration (JSON)</label>
                            <textarea class="form-control font-monospace" id="config_json" name="config_json"
                                      rows="4" placeholder='{"algorithm": "levenshtein", "normalize": true}'></textarea>
                            <div class="form-text">Optional rule-specific configuration in JSON format</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled" value="1" checked>
                                    <label class="form-check-label" for="is_enabled">Enabled</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_blocking" name="is_blocking" value="1">
                                    <label class="form-check-label" for="is_blocking">Blocking</label>
                                    <div class="form-text">Block record save if duplicate found</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Create Rule
                    </button>
                    <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'rules']); ?>" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Rule Type Help</h5>
                    </div>
                    <div class="card-body" id="ruleHelp">
                        <p class="text-muted">Select a rule type to see configuration options.</p>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-code me-2"></i>Example Configs</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Title Similarity:</strong></p>
                        <pre class="bg-light p-2 rounded small">{
  "algorithm": "levenshtein",
  "normalize": true,
  "ignore_case": true,
  "min_length": 10
}</pre>

                        <p class="mt-3"><strong>Combined Analysis:</strong></p>
                        <pre class="bg-light p-2 rounded small">{
  "weights": {
    "title": 0.4,
    "identifier": 0.3,
    "date": 0.15,
    "creator": 0.15
  }
}</pre>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var ruleType = document.getElementById('rule_type');
    var helpDiv = document.getElementById('ruleHelp');

    var helpTexts = {
        title_similarity: '<strong>Title Similarity</strong><br>Compares record titles using string distance algorithms (Levenshtein, Jaro-Winkler).<br><br>Recommended threshold: 0.85',
        identifier_exact: '<strong>Identifier Exact Match</strong><br>Flags records with identical identifiers.<br><br>Recommended threshold: 1.00',
        identifier_fuzzy: '<strong>Identifier Fuzzy Match</strong><br>Finds similar identifiers using fuzzy matching.<br><br>Recommended threshold: 0.90',
        date_creator: '<strong>Date + Creator Match</strong><br>Matches records with overlapping date ranges and similar creators.<br><br>Recommended threshold: 0.90',
        checksum: '<strong>File Checksum</strong><br>Detects identical digital objects by comparing file checksums.<br><br>Recommended threshold: 1.00',
        combined: '<strong>Combined Analysis</strong><br>Uses weighted combination of multiple factors for comprehensive duplicate detection.<br><br>Recommended threshold: 0.75',
        custom: '<strong>Custom Rule</strong><br>Define custom matching logic via configuration JSON.'
    };

    ruleType.addEventListener('change', function() {
        if (helpTexts[this.value]) {
            helpDiv.innerHTML = helpTexts[this.value];
        } else {
            helpDiv.innerHTML = '<p class="text-muted">Select a rule type to see configuration options.</p>';
        }
    });
});
</script>
