<?php
/**
 * RiC display panel (informationobject view), loaded via extension.json.
 *
 * Shows only for records whose descriptive standard is Records in Context (RiC)
 * or that already have RiC metadata. Renders the RiC-O entity type, record-centric
 * RiC properties and typed RiC relations, with inline editing for editors.
 *
 * Available: $resource (QubitInformationObject), $context (string).
 */

use Illuminate\Database\Capsule\Manager as DB;

$objectId = isset($resource->id) ? (int) $resource->id : 0;
if ($objectId <= 0) {
    return;
}

// Gate: only RiC-standard records (display_standard term code 'ric') or records
// that already carry RiC metadata. Every other record view shows no RiC panel.
$isRic = false;
try {
    $code = DB::table('information_object as io')
        ->join('term as t', 't.id', '=', 'io.display_standard_id')
        ->where('io.id', $objectId)
        ->value('t.code');
    $isRic = ('ric' === $code)
        || (DB::table('ric_record_meta')->where('object_id', $objectId)->exists());
} catch (\Throwable $e) {
    return;
}
if (!$isRic) {
    return;
}

$culture = sfContext::getInstance()->getUser()->getCulture() ?: 'en';
$svc = new \AhgRicManage\Services\RicManageService();
$meta = $svc->getRecordMeta($objectId);
$relations = $svc->getTypedRelations($objectId, $culture);
$__ricUser = sfContext::getInstance()->getUser();
$canEdit = $__ricUser->isAuthenticated()
    && ($__ricUser->hasGroup(\AtomExtensions\Constants\AclConstants::ADMINISTRATOR_ID)
        || $__ricUser->hasGroup(\AtomExtensions\Constants\AclConstants::EDITOR_ID));
// RiC-O relation types (for the editor-only add-relation form).
$relationTypes = $canEdit ? $svc->getRelationTypes() : [];

$entityTypes = \AhgRicManage\Services\RicManageService::ENTITY_TYPES;
$propFields = \AhgRicManage\Services\RicManageService::PROPERTY_FIELDS;
// RiC-O relations derived from the record's access points / repository.
$ricHolder = $svc->getRepositoryName($objectId, $culture);
$ricSubjects = $svc->getAccessPointNames($objectId, \AhgRicManage\Services\RicManageService::TAXONOMY_SUBJECT, $culture);
$ricPlaces = $svc->getAccessPointNames($objectId, \AhgRicManage\Services\RicManageService::TAXONOMY_PLACE, $culture);
$ricGenres = $svc->getAccessPointNames($objectId, \AhgRicManage\Services\RicManageService::TAXONOMY_GENRE, $culture);
$ricNames = $svc->getNameAccessPointNames($objectId, $culture);
$esc = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$ricExplorerOn = in_array('ahgRicExplorerPlugin', (array) sfConfig::get('sf_enabled_modules', []), true)
    || function_exists('ahg_ric_explorer_link'); // best-effort
$nonce = sfConfig::get('csp_nonce', '');
$nonceAttr = $nonce ? preg_replace('/^nonce=/', 'nonce="', $nonce) . '"' : '';
?>
<section id="ricArea" class="border-bottom" data-ric-object-id="<?php echo $objectId; ?>">
    <?php if (function_exists('render_b5_section_heading')) {
        echo render_b5_section_heading(__('Records in Context (RiC)'));
    } else { ?>
        <h2 class="h5 mt-4 mb-3"><?php echo $esc(__('Records in Context (RiC)')); ?></h2>
    <?php } ?>

    <div id="ric-view">
        <div class="row mb-2">
            <div class="col-md-3 fw-bold"><?php echo $esc(__('Entity type')); ?></div>
            <div class="col-md-9" id="ric-view-entity-type">
                <?php echo $esc($entityTypes[$meta['entity_type']] ?? $meta['entity_type']); ?>
            </div>
        </div>
        <?php foreach ($propFields as $key => $label): ?>
            <?php if ('' !== $meta['properties'][$key]): ?>
                <div class="row mb-2">
                    <div class="col-md-3 fw-bold"><?php echo $esc(__($label)); ?></div>
                    <div class="col-md-9"><?php echo nl2br($esc($meta['properties'][$key])); ?></div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if (!empty($ricHolder)): ?>
            <div class="row mb-2">
                <div class="col-md-3 fw-bold"><?php echo $esc(__('Holder')); ?> <span class="badge bg-light text-dark">rico:hasOrHadHolder</span></div>
                <div class="col-md-9"><?php echo $esc($ricHolder); ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($ricSubjects)): ?>
            <div class="row mb-2">
                <div class="col-md-3 fw-bold"><?php echo $esc(__('Subjects')); ?> <span class="badge bg-light text-dark">rico:hasOrHadSubject</span></div>
                <div class="col-md-9"><?php echo $esc(implode(', ', $ricSubjects)); ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($ricPlaces)): ?>
            <div class="row mb-2">
                <div class="col-md-3 fw-bold"><?php echo $esc(__('Places')); ?> <span class="badge bg-light text-dark">rico:hasOrHadSpatialCoverage</span></div>
                <div class="col-md-9"><?php echo $esc(implode(', ', $ricPlaces)); ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($ricGenres)): ?>
            <div class="row mb-2">
                <div class="col-md-3 fw-bold"><?php echo $esc(__('Genres')); ?> <span class="badge bg-light text-dark">rico:hasDocumentaryFormType</span></div>
                <div class="col-md-9"><?php echo $esc(implode(', ', $ricGenres)); ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($ricNames)): ?>
            <div class="row mb-2">
                <div class="col-md-3 fw-bold"><?php echo $esc(__('Name access points')); ?> <span class="badge bg-light text-dark">rico:isAssociatedWith</span></div>
                <div class="col-md-9"><?php echo $esc(implode(', ', $ricNames)); ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($relations) || $canEdit): ?>
            <div class="row mb-2">
                <div class="col-md-3 fw-bold"><?php echo $esc(__('Typed RiC relations')); ?></div>
                <div class="col-md-9">
                    <ul class="list-unstyled mb-0" id="ric-relations-list">
                        <?php foreach ($relations as $rel): ?>
                            <li class="mb-1" data-relation-id="<?php echo (int) $rel['relation_id']; ?>">
                                <span class="badge bg-secondary"><?php echo $esc($rel['predicate']); ?></span>
                                <?php if (!empty($rel['target_slug'])): ?>
                                    <a href="<?php echo $esc('/index.php/' . $rel['target_slug']); ?>"><?php echo $esc($rel['target_title']); ?></a>
                                <?php else: ?>
                                    <?php echo $esc($rel['target_title']); ?>
                                <?php endif; ?>
                                <?php if ('incoming' === $rel['direction']): ?><span class="text-muted small">(<?php echo $esc(__('incoming')); ?>)</span><?php endif; ?>
                                <?php if ($canEdit): ?>
                                    <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-1 ric-rel-del" data-relation-id="<?php echo (int) $rel['relation_id']; ?>" title="<?php echo $esc(__('Remove relation')); ?>"><i class="fas fa-times" aria-hidden="true"></i></button>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($canEdit): ?>
                        <div class="ric-rel-add border-top mt-2 pt-2">
                            <div class="row g-1 align-items-start">
                                <div class="col-sm-4">
                                    <select class="form-select form-select-sm" id="ric-rel-type" aria-label="<?php echo $esc(__('RiC relation type')); ?>">
                                        <?php foreach ($relationTypes as $rt): ?>
                                            <option value="<?php echo $esc($rt['code']); ?>"><?php echo $esc($rt['label']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-sm-5 position-relative">
                                    <input type="text" class="form-control form-control-sm" id="ric-rel-target" autocomplete="off" placeholder="<?php echo $esc(__('Search for a target record...')); ?>">
                                    <input type="hidden" id="ric-rel-target-id" value="">
                                </div>
                                <div class="col-sm-3">
                                    <button type="button" class="btn btn-sm btn-outline-success w-100" id="ric-rel-add-btn"><i class="fas fa-plus me-1" aria-hidden="true"></i><?php echo $esc(__('Add')); ?></button>
                                </div>
                            </div>
                            <div class="small text-danger mt-1 d-none" id="ric-rel-error"></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="mt-2">
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo $esc(url_for('@ric_export?objectId=' . $objectId)); ?>" target="_blank" rel="noopener">
                <?php echo $esc(__('Export RiC-O (JSON-LD)')); ?>
            </a>
            <?php if ($ricExplorerOn): ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?php echo $esc('/index.php/ricExplorer/knowledge-graph?focus=' . $objectId); ?>">
                    <?php echo $esc(__('View in graph')); ?>
                </a>
            <?php endif; ?>
            <?php if ($canEdit): ?>
                <button type="button" class="btn btn-sm btn-outline-primary" id="ric-edit-btn"><?php echo $esc(__('Edit RiC')); ?></button>
                <a class="btn btn-sm btn-outline-success" href="<?php echo $esc('/index.php/informationobject/add?standard=ric&parent=' . $objectId); ?>">
                    <i class="fas fa-plus me-1" aria-hidden="true"></i><?php echo $esc(__('Create RiC child')); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($canEdit): ?>
        <form id="ric-edit-form" class="d-none mt-2">
            <input type="hidden" name="object_id" value="<?php echo $objectId; ?>">
            <div class="mb-2">
                <label class="form-label fw-bold" for="ric-entity-type"><?php echo $esc(__('Entity type')); ?></label>
                <select class="form-select form-select-sm" id="ric-entity-type" name="entity_type">
                    <?php foreach ($entityTypes as $val => $label): ?>
                        <option value="<?php echo $esc($val); ?>" <?php echo $val === $meta['entity_type'] ? 'selected' : ''; ?>><?php echo $esc($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php foreach ($propFields as $key => $label): ?>
                <div class="mb-2">
                    <label class="form-label fw-bold" for="ric-prop-<?php echo $esc($key); ?>"><?php echo $esc(__($label)); ?></label>
                    <textarea class="form-control form-control-sm" rows="2" id="ric-prop-<?php echo $esc($key); ?>" name="properties[<?php echo $esc($key); ?>]"><?php echo $esc($meta['properties'][$key]); ?></textarea>
                </div>
            <?php endforeach; ?>
            <div class="mt-2">
                <button type="submit" class="btn btn-sm btn-primary"><?php echo $esc(__('Save')); ?></button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="ric-cancel-btn"><?php echo $esc(__('Cancel')); ?></button>
                <span id="ric-save-status" class="ms-2 small text-muted"></span>
            </div>
        </form>

        <script <?php echo $nonceAttr; ?>>
        (function () {
            var section = document.getElementById('ricArea');
            if (!section) { return; }
            var editBtn = document.getElementById('ric-edit-btn');
            var cancelBtn = document.getElementById('ric-cancel-btn');
            var view = document.getElementById('ric-view');
            var form = document.getElementById('ric-edit-form');
            var status = document.getElementById('ric-save-status');
            if (editBtn) { editBtn.addEventListener('click', function () { view.classList.add('d-none'); form.classList.remove('d-none'); }); }
            if (cancelBtn) { cancelBtn.addEventListener('click', function () { form.classList.add('d-none'); view.classList.remove('d-none'); }); }
            if (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    status.textContent = '<?php echo $esc(__('Saving...')); ?>';
                    // Theme CSRF shim adds X-CSRF-TOKEN to same-origin fetch automatically.
                    fetch('<?php echo $esc(url_for('@ric_save')); ?>', {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: new FormData(form)
                    }).then(function (r) { return r.json(); }).then(function (d) {
                        if (d && d.success) { window.location.reload(); }
                        else { status.textContent = (d && d.error) ? d.error : '<?php echo $esc(__('Save failed')); ?>'; }
                    }).catch(function () { status.textContent = '<?php echo $esc(__('Save failed')); ?>'; });
                });
            }

            // --- Typed RiC relations: target search, add, delete (v1.1) ---
            var relObjId = <?php echo (int) $objectId; ?>;
            var relTarget = document.getElementById('ric-rel-target');
            var relTargetId = document.getElementById('ric-rel-target-id');
            var relType = document.getElementById('ric-rel-type');
            var relAddBtn = document.getElementById('ric-rel-add-btn');
            var relError = document.getElementById('ric-rel-error');
            function relErr(m) { if (relError) { relError.textContent = m || ''; relError.classList.toggle('d-none', !m); } }

            if (relTarget) {
                var acTimer = null, acBox = null;
                var closeAc = function () { if (acBox) { acBox.remove(); acBox = null; } };
                relTarget.addEventListener('input', function () {
                    relTargetId.value = '';
                    clearTimeout(acTimer);
                    var q = relTarget.value.trim();
                    if (q.length < 2) { closeAc(); return; }
                    acTimer = setTimeout(function () {
                        fetch('<?php echo $esc(url_for('@ric_search_targets')); ?>?object_id=' + relObjId + '&q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                            .then(function (r) { return r.json(); })
                            .then(function (d) {
                                closeAc();
                                if (!d || !d.results || !d.results.length) { return; }
                                acBox = document.createElement('div');
                                acBox.className = 'list-group position-absolute w-100 shadow-sm';
                                acBox.style.zIndex = '1050';
                                d.results.forEach(function (it) {
                                    var a = document.createElement('a');
                                    a.href = '#';
                                    a.className = 'list-group-item list-group-item-action py-1 small';
                                    a.textContent = it.title;
                                    a.addEventListener('click', function (e) {
                                        e.preventDefault();
                                        relTarget.value = it.title;
                                        relTargetId.value = it.id;
                                        closeAc();
                                    });
                                    acBox.appendChild(a);
                                });
                                relTarget.parentNode.appendChild(acBox);
                            }).catch(closeAc);
                    }, 300);
                });
                document.addEventListener('click', function (e) { if (acBox && !relTarget.parentNode.contains(e.target)) { closeAc(); } });
            }

            if (relAddBtn) {
                relAddBtn.addEventListener('click', function () {
                    relErr('');
                    if (!relTargetId.value) { relErr('<?php echo $esc(__('Choose a target record from the list.')); ?>'); return; }
                    var fd = new FormData();
                    fd.append('object_id', relObjId);
                    fd.append('target_id', relTargetId.value);
                    fd.append('relation_type', relType.value);
                    relAddBtn.disabled = true;
                    fetch('<?php echo $esc(url_for('@ric_save_relation')); ?>', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
                        .then(function (r) { return r.json(); })
                        .then(function (d) {
                            if (d && d.success) { window.location.reload(); }
                            else { relAddBtn.disabled = false; relErr((d && d.error) ? d.error : '<?php echo $esc(__('Add failed')); ?>'); }
                        }).catch(function () { relAddBtn.disabled = false; relErr('<?php echo $esc(__('Add failed')); ?>'); });
                });
            }

            document.querySelectorAll('.ric-rel-del').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!window.confirm('<?php echo $esc(__('Remove this RiC relation?')); ?>')) { return; }
                    var fd = new FormData();
                    fd.append('relation_id', btn.getAttribute('data-relation-id'));
                    fetch('<?php echo $esc(url_for('@ric_delete_relation')); ?>', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
                        .then(function (r) { return r.json(); })
                        .then(function (d) { if (d && d.success) { window.location.reload(); } })
                        .catch(function () {});
                });
            });
        })();
        </script>
    <?php endif; ?>
</section>
