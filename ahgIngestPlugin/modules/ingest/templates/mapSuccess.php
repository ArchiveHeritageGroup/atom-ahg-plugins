<?php
$session = $sf_data->getRaw('session');
$mappings = $sf_data->getRaw('mappings') ?? [];
$targetFields = $sf_data->getRaw('targetFields') ?? [];
$savedProfiles = $sf_data->getRaw('savedProfiles') ?? [];
$sampleRows = $sf_data->getRaw('sampleRows') ?? [];
?>

<h1><?php echo __('Map & Enrich') ?></h1>

<?php echo get_partial('default/breadcrumb', [
    'objects' => [
        ['title' => __('Admin'), 'url' => url_for(['module' => 'admin', 'action' => 'index'])],
        ['title' => __('Ingestion Manager'), 'url' => url_for(['module' => 'ingest', 'action' => 'index'])],
        ['title' => esc_entities($session->title ?: __('Session #' . $session->id))],
        ['title' => __('Map & Enrich')]
    ]
]) ?>

<!-- Wizard Progress -->
<div class="mb-4">
    <div class="d-flex justify-content-between text-center">
        <div class="flex-fill"><span class="badge bg-success rounded-pill">1</span><br><small class="text-muted"><?php echo __('Configure') ?></small></div>
        <div class="flex-fill"><span class="badge bg-success rounded-pill">2</span><br><small class="text-muted"><?php echo __('Upload') ?></small></div>
        <div class="flex-fill"><span class="badge bg-primary rounded-pill">3</span><br><small class="fw-bold"><?php echo __('Map') ?></small></div>
        <div class="flex-fill"><span class="badge bg-secondary rounded-pill">4</span><br><small class="text-muted"><?php echo __('Validate') ?></small></div>
        <div class="flex-fill"><span class="badge bg-secondary rounded-pill">5</span><br><small class="text-muted"><?php echo __('Preview') ?></small></div>
        <div class="flex-fill"><span class="badge bg-secondary rounded-pill">6</span><br><small class="text-muted"><?php echo __('Commit') ?></small></div>
    </div>
    <div class="progress mt-2" style="height: 4px;">
        <div class="progress-bar" style="width: 42%"></div>
    </div>
</div>

<?php
$isDirectoryImport = isset($isDirectoryImport) ? $sf_data->getRaw('isDirectoryImport') : false;
$rowCount = isset($rowCount) ? $sf_data->getRaw('rowCount') : 0;
$fieldGroups = isset($fieldGroups) ? $sf_data->getRaw('fieldGroups') : [];
$vocabularies = isset($vocabularies) ? $sf_data->getRaw('vocabularies') : [];
$requiredFields = isset($requiredFields) ? $sf_data->getRaw('requiredFields') : [];
?>

<?php if ($isDirectoryImport): ?>
<!-- ─── Directory Import: Metadata Entry ─── -->
<form method="post" action="<?php echo url_for(['module' => 'ingest', 'action' => 'map', 'id' => $session->id]) ?>">

    <div class="alert alert-info">
        <i class="fas fa-folder-open me-2"></i>
        <strong><?php echo $rowCount ?> <?php echo __('files') ?></strong> <?php echo __('found in directory. Enter metadata to apply to all records.') ?>
        <?php echo __('Individual titles are generated from filenames.') ?>
        <span class="text-danger ms-2">*</span> = <?php echo __('Required') ?>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Identifier Counter Option -->
            <div class="card mb-3 border-info">
                <div class="card-header bg-info bg-opacity-10">
                    <h6 class="mb-0"><i class="fas fa-sort-numeric-down me-2"></i><?php echo __('Identifier Counter / Suffix') ?></h6>
                </div>
                <div class="card-body">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="enable_counter" name="metadata[_enable_counter]" value="1">
                        <label class="form-check-label" for="enable_counter">
                            <strong><?php echo __('Add sequential counter to identifiers') ?></strong>
                        </label>
                    </div>
                    <div id="counter-options" style="display:none;">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label class="form-label small"><?php echo __('Prefix') ?></label>
                                <input type="text" class="form-control form-control-sm" name="metadata[_counter_prefix]"
                                       placeholder="e.g. OBJ-">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label small"><?php echo __('Start Number') ?></label>
                                <input type="number" class="form-control form-control-sm" name="metadata[_counter_start]"
                                       value="1" min="1">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label small"><?php echo __('Padding (digits)') ?></label>
                                <input type="number" class="form-control form-control-sm" name="metadata[_counter_padding]"
                                       value="4" min="1" max="10">
                            </div>
                        </div>
                        <small class="text-muted"><?php echo __('Preview:') ?> <code id="counter-preview">OBJ-0001, OBJ-0002, ...</code></small>
                    </div>
                </div>
            </div>

            <?php foreach ($fieldGroups as $groupKey => $group): ?>
            <div class="card mb-3">
                <div class="card-header bg-primary bg-opacity-10">
                    <h6 class="mb-0"><i class="fas fa-layer-group me-2"></i><?php echo __($group['label']) ?></h6>
                </div>
                <div class="card-body">
                    <?php foreach ($group['fields'] as $fieldName => $fieldDef):
                        $isRequired = !empty($fieldDef['required']);
                        $vocabKey = $fieldDef['vocabulary'] ?? null;
                        $vocabOptions = ($vocabKey && isset($vocabularies[$vocabKey])) ? $vocabularies[$vocabKey]['values'] : ($fieldDef['options'] ?? []);
                        $vocabLabel = ($vocabKey && isset($vocabularies[$vocabKey])) ? $vocabularies[$vocabKey]['label'] : '';
                    ?>
                        <div class="mb-3">
                            <label class="form-label" for="meta_<?php echo $fieldName ?>">
                                <?php echo __($fieldDef['label']) ?>
                                <?php if ($isRequired): ?><span class="text-danger">*</span><?php endif ?>
                                <?php if ($vocabLabel): ?><small class="text-muted ms-1">(<?php echo $vocabLabel ?>)</small><?php endif ?>
                            </label>
                            <?php if ($fieldDef['type'] === 'select' && !empty($vocabOptions)): ?>
                                <select class="form-select" id="meta_<?php echo $fieldName ?>" name="metadata[<?php echo $fieldName ?>]"
                                    <?php echo $isRequired ? 'required' : '' ?>>
                                    <option value=""><?php echo __('— Select —') ?></option>
                                    <?php foreach ($vocabOptions as $opt): ?>
                                        <option value="<?php echo esc_entities($opt) ?>"
                                            <?php echo ($fieldName === 'levelOfDescription' && $opt === 'Item') ? 'selected' : '' ?>>
                                            <?php echo __($opt) ?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
                            <?php elseif ($fieldDef['type'] === 'textarea'): ?>
                                <textarea class="form-control" id="meta_<?php echo $fieldName ?>" name="metadata[<?php echo $fieldName ?>]"
                                    rows="3" <?php echo $isRequired ? 'required' : '' ?>></textarea>
                            <?php else: ?>
                                <input type="text" class="form-control" id="meta_<?php echo $fieldName ?>" name="metadata[<?php echo $fieldName ?>]"
                                    <?php echo $isRequired ? 'required' : '' ?>>
                            <?php endif ?>
                            <?php if (!empty($fieldDef['help'])): ?>
                                <small class="text-muted"><?php echo __($fieldDef['help']) ?></small>
                            <?php endif ?>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
            <?php endforeach ?>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-eye me-2"></i><?php echo __('Files Preview') ?></h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($sampleRows)): ?>
                        <?php foreach ($sampleRows as $i => $sr): ?>
                            <div class="mb-2 p-2 border rounded">
                                <small class="text-muted"><?php echo __('Row') ?> <?php echo $sr->row_number ?></small>
                                <div><strong><?php echo esc_entities($sr->title ?: '—') ?></strong></div>
                                <?php if ($sr->digital_object_path): ?>
                                    <small class="text-muted"><i class="fas fa-file me-1"></i><?php echo basename($sr->digital_object_path) ?></small>
                                <?php endif ?>
                            </div>
                        <?php endforeach ?>
                        <?php if ($rowCount > 10): ?>
                            <p class="text-muted small mt-2 mb-0"><?php echo __('...and') ?> <?php echo $rowCount - 10 ?> <?php echo __('more files') ?></p>
                        <?php endif ?>
                    <?php else: ?>
                        <p class="text-muted mb-0"><?php echo __('No files found in directory') ?></p>
                    <?php endif ?>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h6><i class="fas fa-info-circle me-2"></i><?php echo __('About Directory Import') ?></h6>
                    <ul class="small text-muted mb-0">
                        <li><?php echo __('One record per file in the directory') ?></li>
                        <li><?php echo __('Titles auto-generated from filenames') ?></li>
                        <li><?php echo __('Metadata entered here applies to ALL records') ?></li>
                        <li><?php echo __('You can edit individual records after commit') ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'ingest', 'action' => 'upload', 'id' => $session->id]) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back') ?>
        </a>
        <button type="submit" class="btn btn-primary">
            <?php echo __('Apply Metadata & Validate') ?> <i class="fas fa-arrow-right ms-1"></i>
        </button>
    </div>
</form>

<?php else: ?>
<!-- ─── CSV Import: Column Mapping ─── -->

<?php if (!empty($savedProfiles)): ?>
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="post" action="<?php echo url_for(['module' => 'ingest', 'action' => 'map', 'id' => $session->id]) ?>" class="d-flex align-items-center">
            <input type="hidden" name="form_action" value="load_profile">
            <label class="form-label mb-0 me-2 text-nowrap"><?php echo __('Load saved profile:') ?></label>
            <select class="form-select form-select-sm me-2" name="mapping_profile_id" style="max-width: 300px;">
                <option value=""><?php echo __('— Select —') ?></option>
                <?php foreach ($savedProfiles as $p): ?>
                    <option value="<?php echo $p->id ?>"><?php echo esc_entities($p->name) ?> (<?php echo $p->target_type ?>)</option>
                <?php endforeach ?>
            </select>
            <button type="submit" class="btn btn-outline-secondary btn-sm"><?php echo __('Load') ?></button>
        </form>
    </div>
</div>
<?php endif ?>

<form method="post" action="<?php echo url_for(['module' => 'ingest', 'action' => 'map', 'id' => $session->id]) ?>">
    <input type="hidden" name="form_action" value="save">

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-columns me-2"></i><?php echo __('Column Mapping') ?></h5>
                    <span class="badge bg-info">
                        <?php echo count($mappings) ?> <?php echo __('columns') ?>
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 30%"><?php echo __('Source Column') ?></th>
                                    <th style="width: 30%"><?php echo __('Target Field') ?></th>
                                    <th style="width: 15%"><?php echo __('Default') ?></th>
                                    <th style="width: 15%"><?php echo __('Transform') ?></th>
                                    <th style="width: 10%"><?php echo __('Ignore') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mappings as $map): ?>
                                    <?php
                                    // Determine confidence color
                                    $confClass = 'bg-danger bg-opacity-10';
                                    if ($map->target_field) {
                                        $confClass = 'bg-success bg-opacity-10';
                                    }
                                    ?>
                                    <tr class="<?php echo $confClass ?>">
                                        <td>
                                            <code><?php echo esc_entities($map->source_column) ?></code>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm" name="target_field[<?php echo $map->id ?>]">
                                                <option value=""><?php echo __('— unmapped —') ?></option>
                                                <?php foreach ($targetFields as $tf): ?>
                                                    <option value="<?php echo $tf ?>"
                                                        <?php echo $map->target_field === $tf ? 'selected' : '' ?>>
                                                        <?php echo $tf ?>
                                                    </option>
                                                <?php endforeach ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm"
                                                   name="default_value[<?php echo $map->id ?>]"
                                                   value="<?php echo esc_entities($map->default_value ?? '') ?>"
                                                   placeholder="<?php echo __('default') ?>">
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm" name="transform[<?php echo $map->id ?>]">
                                                <option value=""><?php echo __('None') ?></option>
                                                <?php foreach (['trim', 'uppercase', 'lowercase', 'titlecase', 'date_iso', 'strip_html'] as $t): ?>
                                                    <option value="<?php echo $t ?>"
                                                        <?php echo ($map->transform ?? '') === $t ? 'selected' : '' ?>>
                                                        <?php echo $t ?>
                                                    </option>
                                                <?php endforeach ?>
                                            </select>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input"
                                                   name="is_ignored[<?php echo $map->id ?>]" value="1"
                                                   <?php echo $map->is_ignored ? 'checked' : '' ?>>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-eye me-2"></i><?php echo __('Data Preview') ?></h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($sampleRows)): ?>
                        <?php foreach ($sampleRows as $i => $sr): ?>
                            <?php $data = json_decode($sr->data, true) ?: []; ?>
                            <div class="mb-2 p-2 border rounded <?php echo $i === 0 ? 'border-primary' : '' ?>">
                                <small class="text-muted"><?php echo __('Row') ?> <?php echo $sr->row_number ?></small>
                                <div><strong><?php echo esc_entities($sr->title ?: '—') ?></strong></div>
                                <?php if ($sr->level_of_description): ?>
                                    <small class="badge bg-secondary"><?php echo $sr->level_of_description ?></small>
                                <?php endif ?>
                            </div>
                        <?php endforeach ?>
                    <?php else: ?>
                        <p class="text-muted mb-0"><?php echo __('No data rows found') ?></p>
                    <?php endif ?>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-link me-2"></i><?php echo __('Digital Object Matching') ?></h5>
                </div>
                <div class="card-body">
                    <select class="form-select form-select-sm" name="do_match_strategy">
                        <option value="filename"><?php echo __('Match by filename') ?></option>
                        <option value="legacyId"><?php echo __('Match by legacyId') ?></option>
                        <option value="title"><?php echo __('Match by title') ?></option>
                    </select>
                    <small class="text-muted"><?php echo __('How to match digital objects from ZIP to CSV rows') ?></small>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h6><?php echo __('Legend') ?></h6>
                    <div class="d-flex align-items-center mb-1">
                        <span class="d-inline-block me-2 rounded" style="width:16px;height:16px;background:rgba(25,135,84,0.1);border:1px solid rgba(25,135,84,0.3)"></span>
                        <small><?php echo __('Mapped (auto or manual)') ?></small>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="d-inline-block me-2 rounded" style="width:16px;height:16px;background:rgba(220,53,69,0.1);border:1px solid rgba(220,53,69,0.3)"></span>
                        <small><?php echo __('Unmapped (needs attention)') ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'ingest', 'action' => 'upload', 'id' => $session->id]) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back') ?>
        </a>
        <button type="submit" class="btn btn-primary">
            <?php echo __('Save Mappings & Validate') ?> <i class="fas fa-arrow-right ms-1"></i>
        </button>
    </div>
</form>
<?php endif ?>

<?php if ($isDirectoryImport): ?>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var cb = document.getElementById('enable_counter');
    var opts = document.getElementById('counter-options');
    if (!cb || !opts) return;

    cb.addEventListener('change', function() {
        opts.style.display = this.checked ? 'block' : 'none';
    });

    // Counter preview
    var prefixEl = opts.querySelector('input[name="metadata[_counter_prefix]"]');
    var startEl = opts.querySelector('input[name="metadata[_counter_start]"]');
    var padEl = opts.querySelector('input[name="metadata[_counter_padding]"]');
    var preview = document.getElementById('counter-preview');

    function updatePreview() {
        var prefix = prefixEl ? prefixEl.value : '';
        var start = parseInt(startEl ? startEl.value : 1) || 1;
        var pad = parseInt(padEl ? padEl.value : 4) || 4;
        var examples = [];
        for (var i = 0; i < 3; i++) {
            var num = String(start + i);
            while (num.length < pad) num = '0' + num;
            examples.push(prefix + num);
        }
        if (preview) preview.textContent = examples.join(', ') + ', ...';
    }

    if (prefixEl) prefixEl.addEventListener('input', updatePreview);
    if (startEl) startEl.addEventListener('input', updatePreview);
    if (padEl) padEl.addEventListener('input', updatePreview);
});
</script>
<?php endif ?>
