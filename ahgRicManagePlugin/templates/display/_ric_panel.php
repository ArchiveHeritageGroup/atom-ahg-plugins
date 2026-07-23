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

        <?php if (!empty($relations)): ?>
            <div class="row mb-2">
                <div class="col-md-3 fw-bold"><?php echo $esc(__('Typed RiC relations')); ?></div>
                <div class="col-md-9">
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($relations as $rel): ?>
                            <li>
                                <span class="badge bg-secondary"><?php echo $esc($rel['predicate']); ?></span>
                                <?php if (!empty($rel['target_slug'])): ?>
                                    <a href="<?php echo $esc('/index.php/' . $rel['target_slug']); ?>"><?php echo $esc($rel['target_title']); ?></a>
                                <?php else: ?>
                                    <?php echo $esc($rel['target_title']); ?>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
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
        })();
        </script>
    <?php endif; ?>
</section>
