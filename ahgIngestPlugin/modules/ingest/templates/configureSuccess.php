<?php
$session = isset($session) ? $sf_data->getRaw('session') : null;
$repositories = $sf_data->getRaw('repositories') ?? [];
$classifications = $sf_data->getRaw('classifications') ?? [];
$defaults = $sf_data->getRaw('defaults') ?? [];

// Helper: get value from session (edit) or defaults (new) or fallback
$d = function($field, $fallback = '') use ($session, $defaults) {
    if ($session && isset($session->{$field})) return $session->{$field};
    $stripped = str_replace(['process_', 'output_', 'derivative_'], '', $field);
    $settingsKey = 'ingest_' . $stripped;
    if (isset($defaults[$settingsKey])) return ($defaults[$settingsKey] === 'true') ? 1 : (($defaults[$settingsKey] === 'false') ? 0 : $defaults[$settingsKey]);
    return $fallback;
};

$sectorVal = $session->sector ?? ($defaults['ingest_default_sector'] ?? 'archive');
$standardVal = $session->standard ?? ($defaults['ingest_default_standard'] ?? 'isadg');
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

<form method="post" action="<?php echo $session ? url_for('@ingest_configure?id=' . $session->id) : url_for('@ingest_new') ?>">

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
                                <?php foreach ([
                                    'isadg' => ['label' => 'ISAD(G)', 'sectors' => 'archive,library'],
                                    'dc'    => ['label' => 'Dublin Core', 'sectors' => 'archive,library,museum,gallery,dam'],
                                    'rad'   => ['label' => 'RAD', 'sectors' => 'archive'],
                                    'dacs'  => ['label' => 'DACS', 'sectors' => 'archive'],
                                    'mods'  => ['label' => 'MODS', 'sectors' => 'library'],
                                    'spectrum' => ['label' => 'SPECTRUM', 'sectors' => 'museum'],
                                    'cco'   => ['label' => 'CCO', 'sectors' => 'gallery'],
                                ] as $val => $info): ?>
                                    <option value="<?php echo $val ?>"
                                        data-sectors="<?php echo $info['sectors'] ?>"
                                        <?php echo $standardVal === $val ? 'selected' : '' ?>>
                                        <?php echo $info['label'] ?>
                                    </option>
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
                            <?php echo $d('output_create_records', 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="output_create_records"><?php echo __('Create AtoM records') ?></label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="output_generate_sip" name="output_generate_sip" value="1"
                            <?php echo $d('output_generate_sip', 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="output_generate_sip"><?php echo __('Generate SIP package') ?></label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="output_generate_aip" name="output_generate_aip" value="1"
                            <?php echo $d('output_generate_aip', 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="output_generate_aip"><?php echo __('Generate AIP package') ?></label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="output_generate_dip" name="output_generate_dip" value="1"
                            <?php echo $d('output_generate_dip', 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="output_generate_dip"><?php echo __('Generate DIP package') ?></label>
                    </div>

                    <hr>
                    <h6><?php echo __('Derivatives') ?></h6>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="derivative_thumbnails" name="derivative_thumbnails" value="1"
                            <?php echo $d('derivative_thumbnails', 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="derivative_thumbnails"><?php echo __('Generate thumbnails') ?></label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="derivative_reference" name="derivative_reference" value="1"
                            <?php echo $d('derivative_reference', 1) ? 'checked' : '' ?>>
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

    <!-- Full-width: Processing Options -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-brain me-2"></i><?php echo __('Processing Options') ?></h5>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3"><?php echo __('Select AI and processing actions to run on ingested records after commit.') ?></p>
            <div class="row">
                <div class="col-md-3 mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="process_virus_scan" name="process_virus_scan" value="1"
                            <?php echo $d('process_virus_scan', 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="process_virus_scan">
                            <i class="fas fa-shield-virus text-danger me-1"></i><?php echo __('Virus Scan') ?>
                        </label>
                    </div>
                    <small class="text-muted d-block ms-4"><?php echo __('ClamAV malware scan') ?></small>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="process_ocr" name="process_ocr" value="1"
                            <?php echo $d('process_ocr', 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="process_ocr">
                            <i class="fas fa-file-alt text-primary me-1"></i><?php echo __('OCR') ?>
                        </label>
                    </div>
                    <small class="text-muted d-block ms-4"><?php echo __('Tesseract text extraction') ?></small>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="process_ner" name="process_ner" value="1"
                            <?php echo $d('process_ner', 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="process_ner">
                            <i class="fas fa-tags text-success me-1"></i><?php echo __('NER') ?>
                        </label>
                    </div>
                    <small class="text-muted d-block ms-4"><?php echo __('Named entity extraction') ?></small>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="process_summarize" name="process_summarize" value="1"
                            <?php echo $d('process_summarize', 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="process_summarize">
                            <i class="fas fa-compress-alt text-warning me-1"></i><?php echo __('Summarize') ?>
                        </label>
                    </div>
                    <small class="text-muted d-block ms-4"><?php echo __('Auto-generate summaries') ?></small>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="process_spellcheck" name="process_spellcheck" value="1"
                            <?php echo $d('process_spellcheck', 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="process_spellcheck">
                            <i class="fas fa-spell-check text-info me-1"></i><?php echo __('Spell Check') ?>
                        </label>
                    </div>
                    <small class="text-muted d-block ms-4"><?php echo __('aspell grammar check') ?></small>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="process_format_id" name="process_format_id" value="1"
                            <?php echo $d('process_format_id', 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="process_format_id">
                            <i class="fas fa-fingerprint text-secondary me-1"></i><?php echo __('Format ID') ?>
                        </label>
                    </div>
                    <small class="text-muted d-block ms-4"><?php echo __('Siegfried PRONOM identification') ?></small>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="process_face_detect" name="process_face_detect" value="1"
                            <?php echo $d('process_face_detect', 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="process_face_detect">
                            <i class="fas fa-user-circle text-dark me-1"></i><?php echo __('Face Detection') ?>
                        </label>
                    </div>
                    <small class="text-muted d-block ms-4"><?php echo __('Detect & match faces') ?></small>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="process_translate" name="process_translate" value="1"
                            <?php echo $d('process_translate', 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="process_translate">
                            <i class="fas fa-language text-primary me-1"></i><?php echo __('Translate') ?>
                        </label>
                    </div>
                    <small class="text-muted d-block ms-4"><?php echo __('Argos offline translation') ?></small>
                </div>
            </div>

            <!-- Translation language row (shown when translate checked) -->
            <div id="translate-lang-panel" class="row mt-2" style="display:none;">
                <div class="col-md-4">
                    <label for="process_translate_lang" class="form-label"><?php echo __('Translate to') ?></label>
                    <select class="form-select form-select-sm" id="process_translate_lang" name="process_translate_lang">
                        <?php foreach (['af' => 'Afrikaans', 'en' => 'English', 'zu' => 'Zulu', 'xh' => 'Xhosa', 'fr' => 'French', 'de' => 'German', 'pt' => 'Portuguese', 'es' => 'Spanish'] as $code => $name): ?>
                            <option value="<?php echo $code ?>" <?php echo ($session->process_translate_lang ?? ($defaults['ingest_translate_to'] ?? 'af')) === $code ? 'selected' : '' ?>><?php echo $name ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>
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

    // Filter standards by sector
    var sectorSel = document.getElementById('sector');
    var standardSel = document.getElementById('standard');

    function filterStandards() {
        var sector = sectorSel.value;
        var currentVal = standardSel.value;
        var firstVisible = null;
        var currentVisible = false;

        for (var i = 0; i < standardSel.options.length; i++) {
            var opt = standardSel.options[i];
            var sectors = (opt.getAttribute('data-sectors') || '').split(',');
            var show = sectors.indexOf(sector) !== -1;
            opt.style.display = show ? '' : 'none';
            opt.disabled = !show;
            if (show && !firstVisible) firstVisible = opt.value;
            if (show && opt.value === currentVal) currentVisible = true;
        }

        if (!currentVisible && firstVisible) {
            standardSel.value = firstVisible;
        }
    }

    sectorSel.addEventListener('change', filterStandards);
    filterStandards();

    // Toggle translate language panel
    var translateChk = document.getElementById('process_translate');
    var translatePanel = document.getElementById('translate-lang-panel');
    function toggleTranslate() {
        translatePanel.style.display = translateChk.checked ? '' : 'none';
    }
    translateChk.addEventListener('change', toggleTranslate);
    toggleTranslate();

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
