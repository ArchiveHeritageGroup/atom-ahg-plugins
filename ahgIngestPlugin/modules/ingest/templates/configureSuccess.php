<?php
$session = isset($session) ? $sf_data->getRaw('session') : null;
$repositories = $sf_data->getRaw('repositories') ?? [];
$classifications = $sf_data->getRaw('classifications') ?? [];

$sectorVal = $session->sector ?? 'archive';
$standardVal = $session->standard ?? 'isadg';
$placementVal = $session->parent_placement ?? 'top_level';
?>

<h1><?php echo $session ? __('Edit Ingest Configuration') : __('New Ingest') ?></h1>

<?php echo get_partial('default/breadcrumb', [
    'objects' => [
        ['title' => __('Admin'), 'url' => url_for(['module' => 'admin', 'action' => 'index'])],
        ['title' => __('Ingestion Manager'), 'url' => url_for(['module' => 'ingest', 'action' => 'index'])],
        ['title' => $session ? __('Edit Configuration') : __('New Ingest')]
    ]
]) ?>

<!-- Wizard Progress -->
<div class="mb-4">
    <div class="d-flex justify-content-between text-center">
        <div class="flex-fill"><span class="badge bg-primary rounded-pill">1</span><br><small class="fw-bold"><?php echo __('Configure') ?></small></div>
        <div class="flex-fill"><span class="badge bg-secondary rounded-pill">2</span><br><small class="text-muted"><?php echo __('Upload') ?></small></div>
        <div class="flex-fill"><span class="badge bg-secondary rounded-pill">3</span><br><small class="text-muted"><?php echo __('Map') ?></small></div>
        <div class="flex-fill"><span class="badge bg-secondary rounded-pill">4</span><br><small class="text-muted"><?php echo __('Validate') ?></small></div>
        <div class="flex-fill"><span class="badge bg-secondary rounded-pill">5</span><br><small class="text-muted"><?php echo __('Preview') ?></small></div>
        <div class="flex-fill"><span class="badge bg-secondary rounded-pill">6</span><br><small class="text-muted"><?php echo __('Commit') ?></small></div>
    </div>
    <div class="progress mt-2" style="height: 4px;">
        <div class="progress-bar" style="width: 8%"></div>
    </div>
</div>

<form method="post" action="<?php echo url_for(['module' => 'ingest', 'action' => 'configure', 'id' => $session->id ?? '']) ?>">

    <div class="row">
        <!-- Left Column: Core Settings -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cog me-2"></i><?php echo __('Ingest Settings') ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="title" class="form-label"><?php echo __('Session Title') ?></label>
                        <input type="text" class="form-control" id="title" name="title"
                               value="<?php echo esc_entities($session->title ?? '') ?>"
                               placeholder="<?php echo __('e.g. Annual Report Collection 2024') ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="sector" class="form-label"><?php echo __('Sector') ?> <span class="text-danger">*</span></label>
                            <select class="form-select" id="sector" name="sector">
                                <?php foreach (['archive' => 'Archive', 'museum' => 'Museum', 'library' => 'Library', 'gallery' => 'Gallery', 'dam' => 'DAM'] as $val => $label): ?>
                                    <option value="<?php echo $val ?>" <?php echo $sectorVal === $val ? 'selected' : '' ?>><?php echo __($label) ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="standard" class="form-label"><?php echo __('Descriptive Standard') ?> <span class="text-danger">*</span></label>
                            <select class="form-select" id="standard" name="standard">
                                <?php foreach (['isadg' => 'ISAD(G)', 'dc' => 'Dublin Core', 'rad' => 'RAD', 'dacs' => 'DACS', 'spectrum' => 'SPECTRUM', 'cco' => 'CCO'] as $val => $label): ?>
                                    <option value="<?php echo $val ?>" <?php echo $standardVal === $val ? 'selected' : '' ?>><?php echo $label ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="repository_id" class="form-label"><?php echo __('Repository') ?></label>
                        <select class="form-select" id="repository_id" name="repository_id">
                            <option value=""><?php echo __('— Select repository —') ?></option>
                            <?php foreach ($repositories as $repo): ?>
                                <option value="<?php echo $repo->id ?>"
                                    <?php echo ($session->repository_id ?? '') == $repo->id ? 'selected' : '' ?>>
                                    <?php echo esc_entities($repo->name) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Parent Placement -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-sitemap me-2"></i><?php echo __('Hierarchy Placement') ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Where should imported records be placed?') ?></label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="parent_placement" id="placement_top"
                                   value="top_level" <?php echo $placementVal === 'top_level' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="placement_top"><?php echo __('Top-level (directly under root)') ?></label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="parent_placement" id="placement_existing"
                                   value="existing" <?php echo $placementVal === 'existing' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="placement_existing"><?php echo __('Under an existing record') ?></label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="parent_placement" id="placement_new"
                                   value="new" <?php echo $placementVal === 'new' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="placement_new"><?php echo __('Create a new parent record') ?></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="parent_placement" id="placement_csv"
                                   value="csv_hierarchy" <?php echo $placementVal === 'csv_hierarchy' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="placement_csv"><?php echo __('Use hierarchy from CSV (legacyId/parentId)') ?></label>
                        </div>
                    </div>

                    <!-- Existing parent search (shown when 'existing' selected) -->
                    <div id="existing-parent-panel" class="mb-3" style="display:none;">
                        <label for="parent_search" class="form-label"><?php echo __('Search for parent record') ?></label>
                        <input type="text" class="form-control" id="parent_search"
                               placeholder="<?php echo __('Type to search...') ?>" autocomplete="off">
                        <input type="hidden" name="parent_id" id="parent_id" value="<?php echo $session->parent_id ?? '' ?>">
                        <div id="parent_results" class="list-group mt-1"></div>
                    </div>

                    <!-- New parent fields (shown when 'new' selected) -->
                    <div id="new-parent-panel" style="display:none;">
                        <div class="mb-3">
                            <label for="new_parent_title" class="form-label"><?php echo __('New parent title') ?></label>
                            <input type="text" class="form-control" id="new_parent_title" name="new_parent_title"
                                   value="<?php echo esc_entities($session->new_parent_title ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="new_parent_level" class="form-label"><?php echo __('Level of description') ?></label>
                            <select class="form-select" id="new_parent_level" name="new_parent_level">
                                <?php foreach (['Fonds', 'Collection', 'Series', 'Subfonds'] as $lvl): ?>
                                    <option value="<?php echo $lvl ?>"
                                        <?php echo ($session->new_parent_level ?? 'Fonds') === $lvl ? 'selected' : '' ?>>
                                        <?php echo __($lvl) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Output Options -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cogs me-2"></i><?php echo __('Output Options') ?></h5>
                </div>
                <div class="card-body">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="output_create_records" name="output_create_records" value="1"
                            <?php echo ($session->output_create_records ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="output_create_records"><?php echo __('Create AtoM records') ?></label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="output_generate_sip" name="output_generate_sip" value="1"
                            <?php echo ($session->output_generate_sip ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="output_generate_sip"><?php echo __('Generate SIP package') ?></label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="output_generate_dip" name="output_generate_dip" value="1"
                            <?php echo ($session->output_generate_dip ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="output_generate_dip"><?php echo __('Generate DIP package') ?></label>
                    </div>

                    <hr>
                    <h6><?php echo __('Derivatives') ?></h6>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="derivative_thumbnails" name="derivative_thumbnails" value="1"
                            <?php echo ($session->derivative_thumbnails ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="derivative_thumbnails"><?php echo __('Generate thumbnails') ?></label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="derivative_reference" name="derivative_reference" value="1"
                            <?php echo ($session->derivative_reference ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="derivative_reference"><?php echo __('Generate reference images') ?></label>
                    </div>
                </div>
            </div>

            <?php if (!empty($classifications)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i><?php echo __('Security Classification') ?></h5>
                </div>
                <div class="card-body">
                    <select class="form-select" name="security_classification_id">
                        <option value=""><?php echo __('— None —') ?></option>
                        <?php foreach ($classifications as $cl): ?>
                            <option value="<?php echo $cl->id ?>"
                                <?php echo ($session->security_classification_id ?? '') == $cl->id ? 'selected' : '' ?>>
                                <?php echo esc_entities($cl->name) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                    <small class="text-muted"><?php echo __('Applied to all imported records after commit') ?></small>
                </div>
            </div>
            <?php endif ?>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'ingest', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Dashboard') ?>
        </a>
        <button type="submit" class="btn btn-primary">
            <?php echo __('Next: Upload Files') ?> <i class="fas fa-arrow-right ms-1"></i>
        </button>
    </div>

</form>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var radios = document.querySelectorAll('input[name="parent_placement"]');
    var existingPanel = document.getElementById('existing-parent-panel');
    var newPanel = document.getElementById('new-parent-panel');

    function togglePanels() {
        var val = document.querySelector('input[name="parent_placement"]:checked').value;
        existingPanel.style.display = val === 'existing' ? '' : 'none';
        newPanel.style.display = val === 'new' ? '' : 'none';
    }

    radios.forEach(function(r) { r.addEventListener('change', togglePanels); });
    togglePanels();

    // Parent search autocomplete
    var searchInput = document.getElementById('parent_search');
    var parentIdInput = document.getElementById('parent_id');
    var resultsDiv = document.getElementById('parent_results');
    var debounce;

    searchInput.addEventListener('input', function() {
        clearTimeout(debounce);
        var q = this.value.trim();
        if (q.length < 2) { resultsDiv.innerHTML = ''; return; }

        debounce = setTimeout(function() {
            fetch('<?php echo url_for(['module' => 'ingest', 'action' => 'searchParent']) ?>?q=' + encodeURIComponent(q), {
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                resultsDiv.innerHTML = '';
                data.forEach(function(item) {
                    var a = document.createElement('a');
                    a.className = 'list-group-item list-group-item-action';
                    a.href = '#';
                    a.textContent = (item.identifier ? '[' + item.identifier + '] ' : '') + item.title;
                    a.addEventListener('click', function(e) {
                        e.preventDefault();
                        parentIdInput.value = item.id;
                        searchInput.value = item.title;
                        resultsDiv.innerHTML = '';
                    });
                    resultsDiv.appendChild(a);
                });
            });
        }, 300);
    });
});
</script>
