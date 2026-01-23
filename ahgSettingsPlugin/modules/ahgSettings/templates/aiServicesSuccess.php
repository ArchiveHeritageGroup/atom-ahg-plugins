<?php slot('title', __('AI Services Settings')); ?>

<?php
// Decode JSON arrays for checkboxes
$entityTypes = json_decode($settings['ner_entity_types'] ?? '[]', true) ?: [];
$spellcheckFields = json_decode($settings['spellcheck_fields'] ?? '[]', true) ?: [];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="mb-0"><i class="fas fa-brain text-primary"></i> AI Services Settings</h1>
    <a href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to AHG Settings') ?>
    </a>
</div>

<?php if ($sf_user->hasFlash('notice')): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i><?php echo $sf_user->getFlash('notice') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($sf_user->hasFlash('error')): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo $sf_user->getFlash('error') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="post" action="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'aiServices']) ?>">

<!-- General Settings -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-cog me-2"></i>General Settings</h5>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <label class="col-sm-3 col-form-label">Processing Mode</label>
            <div class="col-sm-9">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="processing_mode" id="mode_hybrid" value="hybrid"
                        <?php echo ($settings['processing_mode'] ?? 'job') === 'hybrid' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="mode_hybrid">
                        <strong>Hybrid</strong> - Interactive for small docs, background for large
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="processing_mode" id="mode_job" value="job"
                        <?php echo ($settings['processing_mode'] ?? 'job') === 'job' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="mode_job">
                        <strong>Background Job</strong> - Always queue via Gearman (recommended for production)
                    </label>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <label class="col-sm-3 col-form-label">API Endpoint</label>
            <div class="col-sm-6">
                <input type="text" class="form-control" name="api_url" 
                    value="<?php echo htmlspecialchars($settings['api_url'] ?? '') ?>">
                <small class="text-muted">URL of the AI service (e.g., http://localhost:5004/ai/v1)</small>
            </div>
            <div class="col-sm-3">
                <button type="button" class="btn btn-outline-info" onclick="testConnection()">
                    <i class="fas fa-plug me-1"></i>Test Connection
                </button>
            </div>
        </div>

        <div class="row mb-3">
            <label class="col-sm-3 col-form-label">API Key</label>
            <div class="col-sm-6">
                <input type="password" class="form-control" name="api_key" id="api_key"
                    value="<?php echo htmlspecialchars($settings['api_key'] ?? '') ?>">
            </div>
            <div class="col-sm-3">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="togglePassword()">
                    <i class="fas fa-eye"></i> Show
                </button>
            </div>
        </div>

        <div class="row mb-3">
            <label class="col-sm-3 col-form-label">Timeout (seconds)</label>
            <div class="col-sm-3">
                <input type="number" class="form-control" name="api_timeout" min="10" max="300"
                    value="<?php echo htmlspecialchars($settings['api_timeout'] ?? '60') ?>">
            </div>
        </div>
    </div>
</div>

<!-- NER Settings -->
<div class="card mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-user-tag me-2"></i>Named Entity Recognition (NER)</h5>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-sm-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="ner_enabled" id="ner_enabled" value="1"
                        <?php echo ($settings['ner_enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="ner_enabled">Enable NER</label>
                </div>
            </div>
            <div class="col-sm-9">
                <small class="text-muted">Extract people, organizations, places, and dates from records</small>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-sm-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="auto_extract_on_upload" id="auto_extract" value="1"
                        <?php echo ($settings['auto_extract_on_upload'] ?? '0') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="auto_extract">Auto-extract on upload</label>
                </div>
            </div>
            <div class="col-sm-9">
                <small class="text-muted">Automatically queue NER job when documents are uploaded</small>
            </div>
        </div>

        <div class="row mb-3">
            <label class="col-sm-3 col-form-label">Entity Types</label>
            <div class="col-sm-9">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="entity_PERSON" id="entity_person" value="1"
                        <?php echo in_array('PERSON', $entityTypes) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="entity_person"><i class="fas fa-user me-1"></i>People</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="entity_ORG" id="entity_org" value="1"
                        <?php echo in_array('ORG', $entityTypes) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="entity_org"><i class="fas fa-building me-1"></i>Organizations</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="entity_GPE" id="entity_gpe" value="1"
                        <?php echo in_array('GPE', $entityTypes) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="entity_gpe"><i class="fas fa-map-marker-alt me-1"></i>Places</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="entity_DATE" id="entity_date" value="1"
                        <?php echo in_array('DATE', $entityTypes) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="entity_date"><i class="fas fa-calendar me-1"></i>Dates</label>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Summarization Settings -->
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Summarization</h5>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-sm-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="summarizer_enabled" id="summarizer_enabled" value="1"
                        <?php echo ($settings['summarizer_enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="summarizer_enabled">Enable Summarization</label>
                </div>
            </div>
            <div class="col-sm-9">
                <small class="text-muted">Generate summaries from document content</small>
            </div>
        </div>

        <div class="row mb-3">
            <label class="col-sm-3 col-form-label">Save Summary To</label>
            <div class="col-sm-6">
                <select class="form-select" name="summary_field">
                    <?php foreach ($summaryFields as $value => $label): ?>
                    <option value="<?php echo $value ?>" <?php echo ($settings['summary_field'] ?? 'scopeAndContent') === $value ? 'selected' : '' ?>>
                        <?php echo __($label) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Which ISAD(G) field to populate with the generated summary</small>
            </div>
        </div>

        <div class="row mb-3">
            <label class="col-sm-3 col-form-label">Summary Length</label>
            <div class="col-sm-3">
                <div class="input-group">
                    <span class="input-group-text">Min</span>
                    <input type="number" class="form-control" name="summarizer_min_length" min="50" max="500"
                        value="<?php echo htmlspecialchars($settings['summarizer_min_length'] ?? '100') ?>">
                </div>
            </div>
            <div class="col-sm-3">
                <div class="input-group">
                    <span class="input-group-text">Max</span>
                    <input type="number" class="form-control" name="summarizer_max_length" min="100" max="2000"
                        value="<?php echo htmlspecialchars($settings['summarizer_max_length'] ?? '500') ?>">
                </div>
            </div>
            <div class="col-sm-3">
                <small class="text-muted">Characters</small>
            </div>
        </div>
    </div>
</div>

<!-- Spell Check Settings -->
<div class="card mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0"><i class="fas fa-spell-check me-2"></i>Spell Check</h5>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-sm-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="spellcheck_enabled" id="spellcheck_enabled" value="1"
                        <?php echo ($settings['spellcheck_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="spellcheck_enabled">Enable Spell Check</label>
                </div>
            </div>
            <div class="col-sm-9">
                <small class="text-muted">Check spelling in metadata fields</small>
            </div>
        </div>

        <div class="row mb-3">
            <label class="col-sm-3 col-form-label">Language</label>
            <div class="col-sm-6">
                <select class="form-select" name="spellcheck_language">
                    <?php foreach ($spellcheckLanguages as $code => $label): ?>
                    <option value="<?php echo $code ?>" <?php echo ($settings['spellcheck_language'] ?? 'en_ZA') === $code ? 'selected' : '' ?>>
                        <?php echo $label ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <label class="col-sm-3 col-form-label">Check Fields</label>
            <div class="col-sm-9">
                <?php foreach ($spellcheckFields as $field => $label): ?>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="spellcheck_field_<?php echo $field ?>" 
                        id="spellcheck_<?php echo $field ?>" value="1"
                        <?php echo in_array($field, $spellcheckFields) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="spellcheck_<?php echo $field ?>"><?php echo __($label) ?></label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Save Button -->
<div class="d-flex justify-content-end gap-2 mb-4">
    <a href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
        <i class="fas fa-times me-1"></i>Cancel
    </a>
    <button type="submit" class="btn btn-primary btn-lg">
        <i class="fas fa-save me-1"></i>Save Settings
    </button>
</div>

</form>

<!-- Test Connection Result -->
<div id="connectionResult" class="alert d-none mb-4"></div>

<script>
function togglePassword() {
    const field = document.getElementById('api_key');
    const btn = event.target.closest('button');
    if (field.type === 'password') {
        field.type = 'text';
        btn.innerHTML = '<i class="fas fa-eye-slash"></i> Hide';
    } else {
        field.type = 'password';
        btn.innerHTML = '<i class="fas fa-eye"></i> Show';
    }
}

function testConnection() {
    const url = document.querySelector('input[name="api_url"]').value;
    const resultDiv = document.getElementById('connectionResult');
    
    resultDiv.className = 'alert alert-info';
    resultDiv.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Testing connection...';
    resultDiv.classList.remove('d-none');
    
    fetch(url + '/health')
        .then(response => response.json())
        .then(data => {
            resultDiv.className = 'alert alert-success';
            resultDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i><strong>Connection successful!</strong><br>' +
                'NER Model: ' + (data.ner_model || 'N/A') + '<br>' +
                'Summarizer: ' + (data.summarizer_model || 'N/A');
        })
        .catch(error => {
            resultDiv.className = 'alert alert-danger';
            resultDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i><strong>Connection failed!</strong><br>' +
                'Error: ' + error.message + '<br>' +
                'Make sure the AI service is running at: ' + url;
        });
}
</script>
