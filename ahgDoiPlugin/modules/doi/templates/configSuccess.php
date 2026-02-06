<?php use_helper('Date') ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-cog me-2"></i>DOI Configuration</h1>
            <p class="text-muted">Configure DataCite API credentials and minting settings</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'doi', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <?php if ($sf_user->hasFlash('notice')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $sf_user->getFlash('notice') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif ?>

    <form method="post" action="<?php echo url_for(['module' => 'doi', 'action' => 'configSave']) ?>">
        <div class="row">
            <div class="col-lg-8">
                <!-- DataCite Credentials -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">DataCite API Credentials</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Repository ID</label>
                            <input type="text" name="datacite_repo_id" class="form-control"
                                   value="<?php echo htmlspecialchars($config->datacite_repo_id ?? '') ?>"
                                   placeholder="e.g., INSTITUTION.REPOSITORY">
                            <div class="form-text">Your DataCite repository ID (format: PREFIX.SUFFIX)</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">DOI Prefix</label>
                            <input type="text" name="datacite_prefix" class="form-control"
                                   value="<?php echo htmlspecialchars($config->datacite_prefix ?? '') ?>"
                                   placeholder="e.g., 10.12345">
                            <div class="form-text">Your assigned DOI prefix</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="datacite_password" class="form-control"
                                   value="<?php echo htmlspecialchars($config->datacite_password ?? '') ?>">
                            <div class="form-text">DataCite API password</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">API URL</label>
                            <select name="datacite_url" class="form-select">
                                <option value="https://api.datacite.org" <?php echo ($config->datacite_url ?? '') === 'https://api.datacite.org' ? 'selected' : '' ?>>
                                    Production (https://api.datacite.org)
                                </option>
                                <option value="https://api.test.datacite.org" <?php echo ($config->datacite_url ?? '') === 'https://api.test.datacite.org' ? 'selected' : '' ?>>
                                    Test (https://api.test.datacite.org)
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Environment</label>
                            <select name="environment" class="form-select">
                                <option value="test" <?php echo ($config->environment ?? '') === 'test' ? 'selected' : '' ?>>Test</option>
                                <option value="production" <?php echo ($config->environment ?? '') === 'production' ? 'selected' : '' ?>>Production</option>
                            </select>
                            <div class="form-text">Use 'test' until ready for production DOIs</div>
                        </div>

                        <button type="button" id="test-connection" class="btn btn-outline-primary">
                            <i class="fas fa-plug me-1"></i> Test Connection
                        </button>
                        <span id="connection-result" class="ms-2"></span>
                    </div>
                </div>

                <!-- Minting Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Minting Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="auto_mint" value="1" class="form-check-input" id="auto_mint"
                                       <?php echo ($config->auto_mint ?? false) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="auto_mint">
                                    Auto-mint DOIs when records are published
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Auto-mint levels</label>
                            <?php
                            $autoMintLevels = json_decode($config->auto_mint_levels ?? '[]', true) ?: [];
                            $levels = ['Fonds', 'Collection', 'Series', 'File', 'Item'];
                            foreach ($levels as $level):
                            ?>
                                <div class="form-check">
                                    <input type="checkbox" name="auto_mint_levels[]" value="<?php echo $level ?>"
                                           class="form-check-input" id="level_<?php echo strtolower($level) ?>"
                                           <?php echo in_array($level, $autoMintLevels) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="level_<?php echo strtolower($level) ?>">
                                        <?php echo $level ?>
                                    </label>
                                </div>
                            <?php endforeach ?>
                            <div class="form-text">Only auto-mint for these levels of description</div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="require_digital_object" value="1" class="form-check-input" id="require_digital_object"
                                       <?php echo ($config->require_digital_object ?? false) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="require_digital_object">
                                    Only auto-mint if record has a digital object
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Metadata Defaults -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Metadata Defaults</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Default Publisher</label>
                            <input type="text" name="default_publisher" class="form-control"
                                   value="<?php echo htmlspecialchars($config->default_publisher ?? '') ?>"
                                   placeholder="e.g., The Archive and Heritage Group">
                            <div class="form-text">Used when no repository-specific publisher is set</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Default Resource Type</label>
                            <select name="default_resource_type" class="form-select">
                                <?php
                                $resourceTypes = [
                                    'Text' => 'Text',
                                    'Collection' => 'Collection',
                                    'Dataset' => 'Dataset',
                                    'Image' => 'Image',
                                    'Sound' => 'Sound',
                                    'Audiovisual' => 'Audiovisual',
                                    'PhysicalObject' => 'Physical Object',
                                    'Other' => 'Other',
                                ];
                                foreach ($resourceTypes as $value => $label):
                                ?>
                                    <option value="<?php echo $value ?>" <?php echo ($config->default_resource_type ?? 'Text') === $value ? 'selected' : '' ?>>
                                        <?php echo $label ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">DOI Suffix Pattern</label>
                            <input type="text" name="suffix_pattern" class="form-control"
                                   value="<?php echo htmlspecialchars($config->suffix_pattern ?? '{repository_code}/{year}/{object_id}') ?>">
                            <div class="form-text">
                                Available placeholders: {repository_code}, {year}, {month}, {object_id}, {slug}, {identifier}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Help -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Help</h5>
                    </div>
                    <div class="card-body">
                        <h6>Getting Started</h6>
                        <ol class="small">
                            <li>Register at <a href="https://doi.datacite.org/" target="_blank">DataCite Fabrica</a></li>
                            <li>Create a repository</li>
                            <li>Copy your repository ID and password</li>
                            <li>Enter credentials above</li>
                            <li>Test the connection</li>
                            <li>Start minting DOIs!</li>
                        </ol>

                        <h6 class="mt-3">Test Mode</h6>
                        <p class="small text-muted">
                            Use the test API URL while developing. Test DOIs are not resolvable but allow you to verify your integration.
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Configuration
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.getElementById('test-connection').addEventListener('click', function() {
    var btn = this;
    var result = document.getElementById('connection-result');

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Testing...';
    result.innerHTML = '';

    fetch('<?php echo url_for(['module' => 'doi', 'action' => 'configTest']) ?>')
        .then(function(response) { return response.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plug me-1"></i> Test Connection';

            if (data.success) {
                result.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i> ' + data.message + '</span>';
            } else {
                result.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i> ' + data.message + '</span>';
            }
        })
        .catch(function(error) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plug me-1"></i> Test Connection';
            result.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i> Error: ' + error.message + '</span>';
        });
});
</script>
