<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0" aria-describedby="heading-label">
      <?php echo $isNew ? __('Add new archival description') : __('Edit archival description'); ?>
    </h1>
    <span class="small" id="heading-label">
      <?php echo __('Records in Context (RiC)'); ?>
      <?php if (!$isNew) { ?>
        - <?php echo esc_specialchars($io['title'] ?: __('Untitled')); ?>
      <?php } ?>
    </span>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php if (!empty($errors)) { ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($sf_data->getRaw('errors') as $error) { ?>
          <li><?php echo $error; ?></li>
        <?php } ?>
      </ul>
    </div>
  <?php } ?>

  <?php
  $rawIo = $sf_data->getRaw('io');
  $rawLevels = $sf_data->getRaw('levels');
  $rawPubStatuses = $sf_data->getRaw('publicationStatuses');
  $rawDisplayStandards = $sf_data->getRaw('displayStandards');
  $rawRicMeta = $sf_data->getRaw('ricMeta');
  $rawEntityTypes = $sf_data->getRaw('ricEntityTypes');
  $rawPropFields = $sf_data->getRaw('ricPropFields');
  ?>

  <form method="post" action="<?php echo $isNew ? url_for('@io_add_override') : url_for('@io_edit_override?slug=' . $rawIo['slug']); ?>" id="editForm">

    <?php echo $form->renderHiddenFields(); ?>
    <input type="hidden" name="parentId" value="<?php echo (int) ($rawIo['parentId'] ?? 0); ?>">

    <div class="accordion mb-3" id="ricAccordion">

      <!-- Identity area -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="ric-identity-heading">
          <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#ric-identity-collapse" aria-expanded="true" aria-controls="ric-identity-collapse">
            <?php echo __('Identity area'); ?>
          </button>
        </h2>
        <div id="ric-identity-collapse" class="accordion-collapse collapse show" aria-labelledby="ric-identity-heading">
          <div class="accordion-body">

            <div class="mb-3">
              <label for="identifier" class="form-label"><?php echo __('Identifier'); ?></label>
              <input type="text" class="form-control" id="identifier" name="identifier"
                     value="<?php echo esc_specialchars($rawIo['identifier'] ?? ''); ?>">
            </div>

            <div class="mb-3">
              <label for="title" class="form-label">
                <?php echo __('Title'); ?>
                <span class="form-required" title="<?php echo __('This is a mandatory field.'); ?>">*</span>
              </label>
              <input type="text" class="form-control" id="title" name="title"
                     value="<?php echo esc_specialchars($rawIo['title'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
              <label for="levelOfDescriptionId" class="form-label">
                <?php echo __('Level of description'); ?>
                <span class="form-required" title="<?php echo __('This is a mandatory field.'); ?>">*</span>
              </label>
              <select class="form-select" id="levelOfDescriptionId" name="levelOfDescriptionId" required>
                <option value=""><?php echo __('- Select -'); ?></option>
                <?php foreach ($rawLevels as $level) { ?>
                  <option value="<?php echo $level->id; ?>" <?php echo ($level->id == ($rawIo['levelOfDescriptionId'] ?? '')) ? 'selected' : ''; ?>>
                    <?php echo esc_specialchars($level->name ?? ''); ?>
                  </option>
                <?php } ?>
              </select>
            </div>

            <div class="mb-3">
              <label for="extentAndMedium" class="form-label"><?php echo __('Extent and medium'); ?></label>
              <textarea class="form-control" id="extentAndMedium" name="extentAndMedium" rows="2"><?php echo esc_specialchars($rawIo['extentAndMedium'] ?? ''); ?></textarea>
            </div>

            <div class="mb-3">
              <label for="repositoryName" class="form-label"><?php echo __('Repository'); ?></label>
              <input type="text" class="form-control repository-autocomplete" id="repositoryName" name="repositoryName"
                     value="<?php echo esc_specialchars($rawIo['repositoryName'] ?? ''); ?>" placeholder="<?php echo __('Type to search repositories...'); ?>">
              <input type="hidden" id="repositoryId" name="repositoryId" value="<?php echo (int) ($rawIo['repositoryId'] ?? 0); ?>">
            </div>

          </div>
        </div>
      </div>

      <!-- Records in Context (RiC) area -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="ric-rico-heading">
          <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#ric-rico-collapse" aria-expanded="true" aria-controls="ric-rico-collapse">
            <?php echo __('Records in Context (RiC)'); ?>
          </button>
        </h2>
        <div id="ric-rico-collapse" class="accordion-collapse collapse show" aria-labelledby="ric-rico-heading">
          <div class="accordion-body">

            <div class="mb-3">
              <label for="ricEntityType" class="form-label"><?php echo __('RiC-O entity type'); ?></label>
              <select class="form-select" id="ricEntityType" name="ricEntityType">
                <?php foreach ($rawEntityTypes as $val => $label) { ?>
                  <option value="<?php echo esc_specialchars($val); ?>" <?php echo ($val === ($rawRicMeta['entity_type'] ?? 'Record')) ? 'selected' : ''; ?>>
                    <?php echo esc_specialchars($label); ?>
                  </option>
                <?php } ?>
              </select>
              <div class="form-text text-muted small"><?php echo __('How this record is typed in the Records in Context ontology.'); ?></div>
            </div>

            <?php foreach ($rawPropFields as $key => $label) { ?>
              <div class="mb-3">
                <label for="ricProps_<?php echo esc_specialchars($key); ?>" class="form-label"><?php echo esc_specialchars(__($label)); ?></label>
                <textarea class="form-control" id="ricProps_<?php echo esc_specialchars($key); ?>" name="ricProps[<?php echo esc_specialchars($key); ?>]" rows="2"><?php echo esc_specialchars($rawRicMeta['properties'][$key] ?? ''); ?></textarea>
              </div>
            <?php } ?>

          </div>
        </div>
      </div>

      <!-- RiC relations / access points -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="ric-ap-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ric-ap-collapse" aria-expanded="false" aria-controls="ric-ap-collapse">
            <?php echo __('RiC relations'); ?>
          </button>
        </h2>
        <div id="ric-ap-collapse" class="accordion-collapse collapse" aria-labelledby="ric-ap-heading">
          <div class="accordion-body">

            <div class="mb-3">
              <label class="form-label"><?php echo __('Subjects'); ?> <span class="badge bg-light text-dark">rico:hasOrHadSubject</span></label>
              <?php $rawSubjectAPs = $rawIo['subjectAccessPoints'] ?? []; ?>
              <div id="ric-subject-ap-list">
                <?php foreach ($rawSubjectAPs as $sap) { ?>
                  <div class="input-group input-group-sm mb-1">
                    <input type="text" class="form-control" value="<?php echo esc_specialchars($sap->term_name ?? ''); ?>" readonly>
                    <input type="hidden" name="subjectAccessPointIds[]" value="<?php echo (int) ($sap->term_id ?? 0); ?>">
                    <button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __('Remove'); ?></button>
                  </div>
                <?php } ?>
              </div>
              <div class="input-group input-group-sm mt-1">
                <input type="text" class="form-control term-autocomplete-add" data-taxonomy="35" data-target="ric-subject-ap-list" data-name="subjectAccessPointIds[]" placeholder="<?php echo __('Type to add subject...'); ?>">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label"><?php echo __('Places'); ?> <span class="badge bg-light text-dark">rico:hasOrHadSpatialCoverage</span></label>
              <?php $rawPlaceAPs = $rawIo['placeAccessPoints'] ?? []; ?>
              <div id="ric-place-ap-list">
                <?php foreach ($rawPlaceAPs as $pap) { ?>
                  <div class="input-group input-group-sm mb-1">
                    <input type="text" class="form-control" value="<?php echo esc_specialchars($pap->term_name ?? ''); ?>" readonly>
                    <input type="hidden" name="placeAccessPointIds[]" value="<?php echo (int) ($pap->term_id ?? 0); ?>">
                    <button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __('Remove'); ?></button>
                  </div>
                <?php } ?>
              </div>
              <div class="input-group input-group-sm mt-1">
                <input type="text" class="form-control term-autocomplete-add" data-taxonomy="42" data-target="ric-place-ap-list" data-name="placeAccessPointIds[]" placeholder="<?php echo __('Type to add place...'); ?>">
              </div>
            </div>

            <p class="form-text text-muted small mb-0">
              <?php echo __('The holder (rico:hasOrHadHolder) is the Repository set in the Identity area.'); ?>
            </p>

          </div>
        </div>
      </div>

      <!-- Content area -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="ric-content-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ric-content-collapse" aria-expanded="false" aria-controls="ric-content-collapse">
            <?php echo __('Content and access'); ?>
          </button>
        </h2>
        <div id="ric-content-collapse" class="accordion-collapse collapse" aria-labelledby="ric-content-heading">
          <div class="accordion-body">

            <div class="mb-3">
              <label for="scopeAndContent" class="form-label"><?php echo __('Scope and content'); ?></label>
              <textarea class="form-control" id="scopeAndContent" name="scopeAndContent" rows="4"><?php echo esc_specialchars($rawIo['scopeAndContent'] ?? ''); ?></textarea>
            </div>

            <div class="mb-3">
              <label for="accessConditions" class="form-label"><?php echo __('Conditions governing access'); ?></label>
              <textarea class="form-control" id="accessConditions" name="accessConditions" rows="3"><?php echo esc_specialchars($rawIo['accessConditions'] ?? ''); ?></textarea>
            </div>

          </div>
        </div>
      </div>

      <!-- Settings -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="ric-settings-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ric-settings-collapse" aria-expanded="false" aria-controls="ric-settings-collapse">
            <?php echo __('Settings'); ?>
          </button>
        </h2>
        <div id="ric-settings-collapse" class="accordion-collapse collapse" aria-labelledby="ric-settings-heading">
          <div class="accordion-body">

            <div class="mb-3">
              <label for="publicationStatusId" class="form-label"><?php echo __('Publication status'); ?></label>
              <select class="form-select" id="publicationStatusId" name="publicationStatusId">
                <?php foreach ($rawPubStatuses as $ps) { ?>
                  <option value="<?php echo $ps->id; ?>" <?php echo ($ps->id == ($rawIo['publicationStatusId'] ?? '')) ? 'selected' : ''; ?>>
                    <?php echo esc_specialchars($ps->name); ?>
                  </option>
                <?php } ?>
              </select>
            </div>

            <div class="mb-3">
              <label for="displayStandardId" class="form-label"><?php echo __('Display standard'); ?></label>
              <select class="form-select" id="displayStandardId" name="displayStandardId">
                <option value=""><?php echo __('- Use global default -'); ?></option>
                <?php foreach ($rawDisplayStandards as $ds) { ?>
                  <option value="<?php echo $ds->id; ?>" <?php echo ($ds->id == ($rawIo['displayStandardId'] ?? '')) ? 'selected' : ''; ?>>
                    <?php echo esc_specialchars($ds->name ?? ''); ?>
                  </option>
                <?php } ?>
              </select>
            </div>

          </div>
        </div>
      </div>

    </div>

    <ul class="actions mb-3 nav gap-2">
      <?php if (!$isNew) { ?>
        <li><?php echo link_to(__('Cancel'), '/' . $rawIo['slug'], ['class' => 'btn atom-btn-outline-light', 'role' => 'button']); ?></li>
      <?php } ?>
      <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Save'); ?>"></li>
    </ul>

  </form>

<?php $n = sfConfig::get('csp_nonce', ''); ?>
<script <?php echo $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : ''; ?>>
(function () {
  var TERM_AC_URL = '<?php echo url_for('@io_term_autocomplete'); ?>';
  var REPO_AC_URL = '<?php echo url_for('@io_repository_autocomplete'); ?>';

  function escHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function removeDropdowns() {
    document.querySelectorAll('.ric-ac-dropdown').forEach(function (d) { d.remove(); });
  }
  function showDropdown(input, results, onSelect) {
    removeDropdowns();
    var dd = document.createElement('div');
    dd.className = 'list-group position-absolute ric-ac-dropdown';
    dd.style.zIndex = 1080;
    dd.style.width = input.offsetWidth + 'px';
    results.forEach(function (item) {
      var a = document.createElement('button');
      a.type = 'button';
      a.className = 'list-group-item list-group-item-action py-1';
      a.textContent = item.name;
      a.addEventListener('click', function () { onSelect(item); removeDropdowns(); });
      dd.appendChild(a);
    });
    input.parentNode.style.position = 'relative';
    input.parentNode.appendChild(dd);
  }
  function setupAutocomplete(input, buildUrl, onSelect) {
    var t = null;
    input.addEventListener('input', function () {
      clearTimeout(t);
      var q = input.value.trim();
      if (q.length < 2) { removeDropdowns(); return; }
      t = setTimeout(function () {
        fetch(buildUrl(q)).then(function (r) { return r.json(); }).then(function (res) {
          if (res && res.length) { showDropdown(input, res, onSelect); } else { removeDropdowns(); }
        }).catch(removeDropdowns);
      }, 300);
    });
  }
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.ric-ac-dropdown')
        && !e.target.classList.contains('term-autocomplete-add')
        && !e.target.classList.contains('repository-autocomplete')) {
      removeDropdowns();
    }
  });

  // Repository -> rico:hasOrHadHolder
  var repo = document.getElementById('repositoryName');
  if (repo) {
    setupAutocomplete(repo,
      function (q) { return REPO_AC_URL + '?query=' + encodeURIComponent(q) + '&limit=10'; },
      function (item) {
        repo.value = item.name;
        var h = document.getElementById('repositoryId'); if (h) { h.value = item.id; }
      });
  }

  // Subject (35) / place (42) access points -> rico:hasOrHadSubject / spatial
  document.querySelectorAll('.term-autocomplete-add').forEach(function (input) {
    var tax = input.getAttribute('data-taxonomy');
    var target = input.getAttribute('data-target');
    var name = input.getAttribute('data-name');
    setupAutocomplete(input,
      function (q) { return TERM_AC_URL + '?taxonomy=' + tax + '&query=' + encodeURIComponent(q) + '&limit=10'; },
      function (item) {
        var list = document.getElementById(target);
        if (!list) { return; }
        var row = document.createElement('div');
        row.className = 'input-group input-group-sm mb-1';
        row.innerHTML = '<input type="text" class="form-control" value="' + escHtml(item.name) + '" readonly>'
          + '<input type="hidden" name="' + name + '" value="' + item.id + '">'
          + '<button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __('Remove'); ?></button>';
        list.appendChild(row);
        input.value = '';
      });
  });

  // Remove an access-point row
  document.addEventListener('click', function (e) {
    if (e.target.classList.contains('btn-remove-ap')) {
      var g = e.target.closest('.input-group');
      if (g) { g.remove(); }
    }
  });
})();
</script>

<script src="/plugins/ahgInformationObjectManagePlugin/web/js/standard-switch.js"></script>

<?php end_slot(); ?>
