<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0" aria-describedby="heading-label">
      <?php echo $isNew ? __('Add new archival description') : __('Edit archival description'); ?>
    </h1>
    <span class="small" id="heading-label">
      <?php echo __('MODS'); ?>
      <?php if (!$isNew) { ?>
        — <?php echo esc_specialchars($io['title'] ?: __('Untitled')); ?>
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

  <?php $rawIo = $sf_data->getRaw('io'); ?>
  <?php $rawLangChoices = $sf_data->getRaw('languageChoices'); ?>
  <?php $rawEventTypes = $sf_data->getRaw('eventTypes'); ?>
  <?php $rawLevels = $sf_data->getRaw('levels'); ?>
  <?php $rawModsResourceTypes = $sf_data->getRaw('modsResourceTypes'); ?>

  <form method="post" action="<?php echo $isNew ? url_for('@io_add_override') : url_for('@io_edit_override?slug=' . $rawIo['slug']); ?>" id="editForm">

    <?php echo $form->renderHiddenFields(); ?>
    <input type="hidden" name="parentId" value="<?php echo (int) $rawIo['parentId']; ?>">

    <div class="accordion mb-3" id="modsAccordion">

      <!-- Elements area -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="elements-heading">
          <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#elements-collapse" aria-expanded="true" aria-controls="elements-collapse">
            <?php echo __('Elements area'); ?>
          </button>
        </h2>
        <div id="elements-collapse" class="accordion-collapse collapse show" aria-labelledby="elements-heading">
          <div class="accordion-body">

            <!-- Identifier -->
            <div class="mb-3">
              <label for="identifier" class="form-label"><?php echo __('Identifier'); ?></label>
              <div class="input-group">
                <input type="text" class="form-control" id="identifier" name="identifier"
                       value="<?php echo esc_specialchars($rawIo['identifier']); ?>">
                <button type="button" class="btn btn-outline-secondary" id="generate-identifier"
                        data-url="<?php echo url_for('@io_generate_identifier'); ?>">
                  <i class="fas fa-cog me-1" aria-hidden="true"></i><?php echo __('Generate'); ?>
                </button>
              </div>
              <div class="form-text text-muted small">
                <?php echo __('Scheme: Archive Standard'); ?> <code>{REPO}/{FONDS}/{SEQ:4}</code>
              </div>
            </div>

            <!-- Alternative identifiers -->
            <div class="mb-3">
              <label class="form-label"><?php echo __('Alternative identifier(s)'); ?></label>
              <?php $rawAltIds = $rawIo['alternativeIdentifiers'] ?? []; ?>
              <table class="table table-sm" id="altids-table">
                <thead>
                  <tr>
                    <th><?php echo __('Label'); ?></th>
                    <th><?php echo __('Value'); ?></th>
                    <th style="width:80px"></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($rawAltIds)) { ?>
                    <?php foreach ($rawAltIds as $aiIdx => $ai) { ?>
                      <tr>
                        <td><input type="text" class="form-control form-control-sm" name="altIds[<?php echo $aiIdx; ?>][label]" value="<?php echo esc_specialchars($ai->label ?? ''); ?>" placeholder="<?php echo __('e.g. Former reference'); ?>"></td>
                        <td><input type="text" class="form-control form-control-sm" name="altIds[<?php echo $aiIdx; ?>][value]" value="<?php echo esc_specialchars($ai->value ?? ''); ?>"></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><?php echo __('Remove'); ?></button></td>
                      </tr>
                    <?php } ?>
                  <?php } ?>
                </tbody>
              </table>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="add-altid-row"><?php echo __('Add alternative identifier'); ?></button>
            </div>

            <!-- Title -->
            <div class="mb-3">
              <label for="title" class="form-label">
                <?php echo __('Title'); ?>
                <span class="form-required" title="<?php echo __('This is a mandatory field.'); ?>">*</span>
              </label>
              <input type="text" class="form-control" id="title" name="title"
                     value="<?php echo esc_specialchars($rawIo['title']); ?>" required>
            </div>

            <!-- Names and origin info (events table) -->
            <div class="mb-3">
              <label class="form-label"><?php echo __('Names and origin info'); ?></label>
              <?php $rawEvents = $rawIo['events']; ?>
              <table class="table table-sm" id="events-table">
                <thead>
                  <tr>
                    <th><?php echo __('Type'); ?></th>
                    <th><?php echo __('Actor'); ?></th>
                    <th><?php echo __('Date'); ?></th>
                    <th><?php echo __('Start'); ?></th>
                    <th><?php echo __('End'); ?></th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($rawEvents)) { ?>
                    <?php foreach ($rawEvents as $idx => $evt) { ?>
                      <tr>
                        <td>
                          <select class="form-select form-select-sm" name="events[<?php echo $idx; ?>][typeId]">
                            <option value=""><?php echo __('- Select -'); ?></option>
                            <?php foreach ($rawEventTypes as $et) { ?>
                              <option value="<?php echo $et->id; ?>" <?php echo ($et->id == ($evt->type_id ?? '')) ? 'selected' : ''; ?>>
                                <?php echo esc_specialchars($et->name ?? ''); ?>
                              </option>
                            <?php } ?>
                          </select>
                        </td>
                        <td>
                          <input type="text" class="form-control form-control-sm actor-autocomplete" name="events[<?php echo $idx; ?>][actorName]" value="<?php echo esc_specialchars($evt->actor_name ?? ''); ?>" placeholder="<?php echo __('Actor name'); ?>">
                          <input type="hidden" name="events[<?php echo $idx; ?>][actorId]" value="<?php echo (int) ($evt->actor_id ?? 0); ?>">
                        </td>
                        <td><input type="text" class="form-control form-control-sm" name="events[<?php echo $idx; ?>][date]" value="<?php echo esc_specialchars($evt->date ?? ''); ?>" placeholder="<?php echo __('e.g. ca. 1900'); ?>"></td>
                        <td><input type="date" class="form-control form-control-sm" name="events[<?php echo $idx; ?>][startDate]" value="<?php echo esc_specialchars($evt->start_date ?? ''); ?>"></td>
                        <td><input type="date" class="form-control form-control-sm" name="events[<?php echo $idx; ?>][endDate]" value="<?php echo esc_specialchars($evt->end_date ?? ''); ?>"></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><?php echo __('Remove'); ?></button></td>
                      </tr>
                    <?php } ?>
                  <?php } ?>
                </tbody>
              </table>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="add-event-row"><?php echo __('Add name'); ?></button>
            </div>

            <!-- Type of resource (MODS Resource Type) -->
            <div class="mb-3">
              <label class="form-label"><?php echo __('Type of resource'); ?></label>
              <?php $rawModsTypeAPs = $rawIo['modsResourceTypes'] ?? []; ?>
              <div id="modstype-list">
                <?php if (!empty($rawModsTypeAPs)) { ?>
                  <?php foreach ($rawModsTypeAPs as $mt) { ?>
                    <div class="input-group input-group-sm mb-1">
                      <input type="text" class="form-control" value="<?php echo esc_specialchars($mt->term_name ?? ''); ?>" readonly>
                      <input type="hidden" name="modsResourceTypeIds[]" value="<?php echo (int) $mt->term_id; ?>">
                      <button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __('Remove'); ?></button>
                    </div>
                  <?php } ?>
                <?php } ?>
              </div>
              <div class="input-group input-group-sm mt-1">
                <select class="form-select form-select-sm" id="modstype-select">
                  <option value=""><?php echo __('- Select type -'); ?></option>
                  <?php foreach ($rawModsResourceTypes as $mrt) { ?>
                    <option value="<?php echo $mrt->id; ?>"><?php echo esc_specialchars($mrt->name ?? ''); ?></option>
                  <?php } ?>
                </select>
                <button type="button" class="btn btn-outline-secondary" id="add-modstype-btn"><?php echo __('Add'); ?></button>
              </div>
              <div class="form-text text-muted small"><?php echo __('The nature or genre of the resource (MODS Resource Type).'); ?></div>
            </div>

            <!-- Level of description -->
            <div class="mb-3">
              <label for="levelOfDescriptionId" class="form-label">
                <?php echo __('Level of description'); ?>
                <span class="form-required" title="<?php echo __('This is a mandatory field.'); ?>">*</span>
              </label>
              <select class="form-select" id="levelOfDescriptionId" name="levelOfDescriptionId" required>
                <option value=""><?php echo __('- Select -'); ?></option>
                <?php foreach ($rawLevels as $level) { ?>
                  <option value="<?php echo $level->id; ?>"
                          <?php echo ($level->id == $rawIo['levelOfDescriptionId']) ? 'selected' : ''; ?>>
                    <?php echo esc_specialchars($level->name ?? ''); ?>
                  </option>
                <?php } ?>
              </select>
            </div>

            <!-- Add new child levels (edit only) -->
            <?php if (!$isNew) { ?>
              <div class="mb-3">
                <label class="form-label"><?php echo __('Add new child levels'); ?></label>
                <table class="table table-sm" id="childlevels-table">
                  <thead>
                    <tr>
                      <th><?php echo __('Identifier'); ?></th>
                      <th><?php echo __('Title'); ?></th>
                      <th style="width:80px"></th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="add-childlevel-row"><?php echo __('Add child level'); ?></button>
              </div>
            <?php } ?>

            <!-- Language -->
            <div class="mb-3">
              <label class="form-label"><?php echo __('Language'); ?></label>
              <?php $rawLangs = $rawIo['languages'] ?? []; ?>
              <div id="languages-list">
                <?php if (!empty($rawLangs)) { ?>
                  <?php foreach ($rawLangs as $langCode) { ?>
                    <div class="input-group input-group-sm mb-1">
                      <select class="form-select form-select-sm" name="languages[]">
                        <?php foreach ($rawLangChoices as $code => $name) { ?>
                          <option value="<?php echo $code; ?>" <?php echo ($code === $langCode) ? 'selected' : ''; ?>><?php echo esc_specialchars($name); ?></option>
                        <?php } ?>
                      </select>
                      <button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __('Remove'); ?></button>
                    </div>
                  <?php } ?>
                <?php } ?>
              </div>
              <button type="button" class="btn btn-sm btn-outline-secondary btn-add-lang-row" data-target="languages-list" data-name="languages[]"><?php echo __('Add language'); ?></button>
            </div>

            <!-- Subject access points -->
            <div class="mb-3">
              <label class="form-label"><?php echo __('Subject access points'); ?></label>
              <?php $rawSubjectAPs = $rawIo['subjectAccessPoints']; ?>
              <div id="subject-ap-list">
                <?php if (!empty($rawSubjectAPs)) { ?>
                  <?php foreach ($rawSubjectAPs as $sap) { ?>
                    <div class="input-group input-group-sm mb-1">
                      <input type="text" class="form-control" value="<?php echo esc_specialchars($sap->term_name ?? ''); ?>" readonly>
                      <input type="hidden" name="subjectAccessPointIds[]" value="<?php echo (int) $sap->term_id; ?>">
                      <button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __('Remove'); ?></button>
                    </div>
                  <?php } ?>
                <?php } ?>
              </div>
              <div class="input-group input-group-sm mt-1">
                <input type="text" class="form-control term-autocomplete-add" data-taxonomy="35" data-target="subject-ap-list" data-name="subjectAccessPointIds[]" placeholder="<?php echo __('Type to add subject...'); ?>">
              </div>
            </div>

            <!-- Place access points -->
            <div class="mb-3">
              <label class="form-label"><?php echo __('Place access points'); ?></label>
              <?php $rawPlaceAPs = $rawIo['placeAccessPoints']; ?>
              <div id="place-ap-list">
                <?php if (!empty($rawPlaceAPs)) { ?>
                  <?php foreach ($rawPlaceAPs as $pap) { ?>
                    <div class="input-group input-group-sm mb-1">
                      <input type="text" class="form-control" value="<?php echo esc_specialchars($pap->term_name ?? ''); ?>" readonly>
                      <input type="hidden" name="placeAccessPointIds[]" value="<?php echo (int) $pap->term_id; ?>">
                      <button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __('Remove'); ?></button>
                    </div>
                  <?php } ?>
                <?php } ?>
              </div>
              <div class="input-group input-group-sm mt-1">
                <input type="text" class="form-control term-autocomplete-add" data-taxonomy="42" data-target="place-ap-list" data-name="placeAccessPointIds[]" placeholder="<?php echo __('Type to add place...'); ?>">
              </div>
            </div>

            <!-- Name access points -->
            <div class="mb-3">
              <label class="form-label"><?php echo __('Name access points'); ?></label>
              <?php $rawNameAPs = $rawIo['nameAccessPoints']; ?>
              <div id="name-ap-list">
                <?php if (!empty($rawNameAPs)) { ?>
                  <?php foreach ($rawNameAPs as $nIdx => $nap) { ?>
                    <div class="input-group input-group-sm mb-1">
                      <input type="text" class="form-control" value="<?php echo esc_specialchars($nap->actor_name ?? ''); ?>" readonly>
                      <input type="hidden" name="nameAccessPoints[<?php echo $nIdx; ?>][actorId]" value="<?php echo (int) $nap->actor_id; ?>">
                      <input type="hidden" name="nameAccessPoints[<?php echo $nIdx; ?>][actorName]" value="<?php echo esc_specialchars($nap->actor_name ?? ''); ?>">
                      <button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __('Remove'); ?></button>
                    </div>
                  <?php } ?>
                <?php } ?>
              </div>
              <div class="input-group input-group-sm mt-1">
                <input type="text" class="form-control actor-autocomplete-add" data-target="name-ap-list" placeholder="<?php echo __('Type to add name...'); ?>">
              </div>
            </div>

            <!-- Access conditions -->
            <div class="mb-3">
              <label for="accessConditions" class="form-label"><?php echo __('Access conditions'); ?></label>
              <textarea class="form-control" id="accessConditions" name="accessConditions" rows="3"><?php echo esc_specialchars($rawIo['accessConditions']); ?></textarea>
              <div class="form-text text-muted small"><?php echo __('Information about restrictions on access to the resource.'); ?></div>
            </div>

            <!-- Repository -->
            <div class="mb-3">
              <label for="repositoryName" class="form-label"><?php echo __('Repository'); ?></label>
              <input type="text" class="form-control repository-autocomplete" id="repositoryName" name="repositoryName"
                     value="<?php echo esc_specialchars($rawIo['repositoryName'] ?? ''); ?>" placeholder="<?php echo __('Type to search repositories...'); ?>">
              <input type="hidden" id="repositoryId" name="repositoryId" value="<?php echo (int) ($rawIo['repositoryId'] ?? 0); ?>">
              <div class="form-text text-muted small"><?php echo __('The repository which holds the resource.'); ?></div>
            </div>

            <!-- Description / Scope and content -->
            <div class="mb-3">
              <label for="scopeAndContent" class="form-label"><?php echo __('Description'); ?></label>
              <textarea class="form-control" id="scopeAndContent" name="scopeAndContent" rows="4"><?php echo esc_specialchars($rawIo['scopeAndContent']); ?></textarea>
              <div class="form-text text-muted small"><?php echo __('An abstract or description of the resource scope and content.'); ?></div>
            </div>

          </div>
        </div>
      </div>

    </div>

    <!-- Admin area -->
    <div class="card mb-3">
      <div class="card-header"><h5 class="mb-0"><?php echo __('Administration area'); ?></h5></div>
      <div class="card-body">

        <?php if (!$isNew && $rawIo['parentTitle']) { ?>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Part of'); ?></label>
            <p class="form-control-plaintext">
              <a href="<?php echo url_for('/' . $rawIo['parentSlug']); ?>">
                <?php echo esc_specialchars($rawIo['parentTitle']); ?>
              </a>
            </p>
          </div>
        <?php } ?>

        <?php $rawPubStatuses = $sf_data->getRaw('publicationStatuses'); ?>
        <div class="mb-3">
          <label for="publicationStatusId" class="form-label"><?php echo __('Publication status'); ?></label>
          <select class="form-select" id="publicationStatusId" name="publicationStatusId">
            <?php foreach ($rawPubStatuses as $ps) { ?>
              <option value="<?php echo $ps->id; ?>"
                      <?php echo ($ps->id == $rawIo['publicationStatusId']) ? 'selected' : ''; ?>>
                <?php echo esc_specialchars($ps->name); ?>
              </option>
            <?php } ?>
          </select>
        </div>

        <!-- Display standard -->
        <?php $rawDisplayStandards = $sf_data->getRaw('displayStandards'); ?>
        <div class="mb-3">
          <label for="displayStandardId" class="form-label"><?php echo __('Display standard'); ?></label>
          <select class="form-select" id="displayStandardId" name="displayStandardId">
            <option value=""><?php echo __('- Use global default -'); ?></option>
            <?php foreach ($rawDisplayStandards as $ds) { ?>
              <option value="<?php echo $ds->id; ?>"
                      <?php echo ($ds->id == ($rawIo['displayStandardId'] ?? '')) ? 'selected' : ''; ?>>
                <?php echo esc_specialchars($ds->name ?? ''); ?>
              </option>
            <?php } ?>
          </select>
        </div>

        <!-- Source language (read-only) -->
        <div class="mb-3">
          <label class="form-label"><?php echo __('Source language'); ?></label>
          <p class="form-control-plaintext"><?php echo esc_specialchars($rawIo['sourceCulture'] ?? ''); ?></p>
        </div>

        <?php if (!$isNew && !empty($rawIo['updatedAt'])) { ?>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Last updated'); ?></label>
            <p class="form-control-plaintext"><?php echo esc_specialchars($rawIo['updatedAt']); ?></p>
          </div>
        <?php } ?>

      </div>
    </div>

    <ul class="actions mb-3 nav gap-2">
      <?php if (!$isNew) { ?>
        <li><?php echo link_to(__('Cancel'), '/' . $rawIo['slug'], ['class' => 'btn atom-btn-outline-light', 'role' => 'button']); ?></li>
        <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Save'); ?>"></li>
      <?php } else { ?>
        <li><?php echo link_to(__('Cancel'), '/', ['class' => 'btn atom-btn-outline-light', 'role' => 'button']); ?></li>
        <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Create'); ?>"></li>
      <?php } ?>
    </ul>

  </form>

<?php $n = sfConfig::get('csp_nonce', ''); $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>
<script <?php echo $na; ?>>
(function() {
  'use strict';

  // ── AJAX endpoint URLs ──────────────────────────────────────────────
  var ACTOR_AC_URL = '<?php echo url_for("@io_actor_autocomplete"); ?>';
  var REPO_AC_URL = '<?php echo url_for("@io_repository_autocomplete"); ?>';
  var TERM_AC_URL = '<?php echo url_for("@io_term_autocomplete"); ?>';

  // ── Utility ──────────────────────────────────────────────────────────
  function escHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  // ── Generic dropdown helper ─────────────────────────────────────────
  function showDropdown(input, results, onSelect) {
    var dropdown = document.createElement('div');
    dropdown.className = 'list-group position-absolute w-100 ac-dropdown';
    dropdown.style.zIndex = '1050';
    results.forEach(function(item) {
      var a = document.createElement('a');
      a.className = 'list-group-item list-group-item-action py-1 small';
      a.href = '#';
      a.textContent = item.name || '';
      a.addEventListener('click', function(e) {
        e.preventDefault();
        onSelect(item);
        removeDropdownsFor(input);
      });
      dropdown.appendChild(a);
    });
    input.parentNode.style.position = 'relative';
    removeDropdownsFor(input);
    input.parentNode.appendChild(dropdown);
  }

  function removeDropdownsFor(input) {
    var existing = input.parentNode.querySelectorAll('.ac-dropdown');
    existing.forEach(function(el) { el.remove(); });
  }

  // Global click to close dropdowns
  document.addEventListener('click', function(e) {
    if (!e.target.closest('.ac-dropdown') && !e.target.classList.contains('actor-autocomplete')
        && !e.target.classList.contains('actor-autocomplete-add')
        && !e.target.classList.contains('repository-autocomplete')
        && !e.target.classList.contains('term-autocomplete-add')) {
      document.querySelectorAll('.ac-dropdown').forEach(function(d) { d.remove(); });
    }
  });

  // ── Debounced fetch helper ──────────────────────────────────────────
  function setupAutocomplete(input, buildUrl, onSelect) {
    var timeout = null;
    input.addEventListener('input', function() {
      clearTimeout(timeout);
      var q = input.value.trim();
      if (q.length < 2) { removeDropdownsFor(input); return; }
      timeout = setTimeout(function() {
        fetch(buildUrl(q))
          .then(function(r) { return r.json(); })
          .then(function(results) {
            if (!results || !results.length) { removeDropdownsFor(input); return; }
            showDropdown(input, results, onSelect);
          })
          .catch(function() { removeDropdownsFor(input); });
      }, 300);
    });
  }

  // ── Generate identifier button ──────────────────────────────────────
  var genBtn = document.getElementById('generate-identifier');
  if (genBtn) {
    genBtn.addEventListener('click', function() {
      var repoId = document.getElementById('repositoryId').value || '0';
      var parentId = document.querySelector('input[name="parentId"]').value || '0';
      if (!repoId || repoId === '0') {
        alert('<?php echo __("Please select a repository first."); ?>');
        return;
      }
      var url = genBtn.getAttribute('data-url')
        + '?repositoryId=' + encodeURIComponent(repoId)
        + '&parentId=' + encodeURIComponent(parentId);
      genBtn.disabled = true;
      fetch(url)
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (data.error) { alert(data.error); }
          else if (data.identifier) { document.getElementById('identifier').value = data.identifier; }
          else { alert('<?php echo __("Could not generate identifier."); ?>'); }
        })
        .catch(function() { alert('<?php echo __("Failed to generate identifier."); ?>'); })
        .finally(function() { genBtn.disabled = false; });
    });
  }

  // ── Event rows (Names and origin info) ─────────────────────────────
  var eventsBody = document.querySelector('#events-table tbody');
  var addEventBtn = document.getElementById('add-event-row');
  var eventIdx = eventsBody ? eventsBody.querySelectorAll('tr').length : 0;

  var eventTypeOptions = '';
  var firstEvtSelect = document.querySelector('#events-table select');
  if (firstEvtSelect) {
    eventTypeOptions = firstEvtSelect.innerHTML;
  } else {
    eventTypeOptions = '<option value=""><?php echo __("- Select -"); ?></option>';
    <?php foreach ($rawEventTypes as $et) { ?>
    eventTypeOptions += '<option value="<?php echo $et->id; ?>"><?php echo esc_specialchars($et->name ?? ""); ?></option>';
    <?php } ?>
  }

  if (addEventBtn) {
    addEventBtn.addEventListener('click', function() {
      var tr = document.createElement('tr');
      tr.innerHTML =
        '<td><select class="form-select form-select-sm" name="events[' + eventIdx + '][typeId]">' + eventTypeOptions + '</select></td>' +
        '<td><input type="text" class="form-control form-control-sm actor-autocomplete" name="events[' + eventIdx + '][actorName]" placeholder="<?php echo __("Actor name"); ?>">' +
            '<input type="hidden" name="events[' + eventIdx + '][actorId]" value="0"></td>' +
        '<td><input type="text" class="form-control form-control-sm" name="events[' + eventIdx + '][date]" placeholder="<?php echo __("e.g. ca. 1900"); ?>"></td>' +
        '<td><input type="date" class="form-control form-control-sm" name="events[' + eventIdx + '][startDate]"></td>' +
        '<td><input type="date" class="form-control form-control-sm" name="events[' + eventIdx + '][endDate]"></td>' +
        '<td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><?php echo __("Remove"); ?></button></td>';
      eventsBody.appendChild(tr);
      initActorAutocomplete(tr.querySelector('.actor-autocomplete'));
      eventIdx++;
    });
  }

  // ── MODS Resource Type add ────────────────────────────────────────
  var modsTypeList = document.getElementById('modstype-list');
  var modsTypeSelect = document.getElementById('modstype-select');
  var addModsTypeBtn = document.getElementById('add-modstype-btn');

  if (addModsTypeBtn && modsTypeSelect) {
    addModsTypeBtn.addEventListener('click', function() {
      var opt = modsTypeSelect.options[modsTypeSelect.selectedIndex];
      if (!opt || !opt.value) return;
      var div = document.createElement('div');
      div.className = 'input-group input-group-sm mb-1';
      div.innerHTML =
        '<input type="text" class="form-control" value="' + escHtml(opt.text) + '" readonly>' +
        '<input type="hidden" name="modsResourceTypeIds[]" value="' + opt.value + '">' +
        '<button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __("Remove"); ?></button>';
      modsTypeList.appendChild(div);
      modsTypeSelect.selectedIndex = 0;
    });
  }

  // ── Alternative identifier rows ─────────────────────────────────────
  var altIdsBody = document.querySelector('#altids-table tbody');
  var addAltIdBtn = document.getElementById('add-altid-row');
  var altIdIdx = altIdsBody ? altIdsBody.querySelectorAll('tr').length : 0;

  if (addAltIdBtn) {
    addAltIdBtn.addEventListener('click', function() {
      var tr = document.createElement('tr');
      tr.innerHTML =
        '<td><input type="text" class="form-control form-control-sm" name="altIds[' + altIdIdx + '][label]" placeholder="<?php echo __("e.g. Former reference"); ?>"></td>' +
        '<td><input type="text" class="form-control form-control-sm" name="altIds[' + altIdIdx + '][value]"></td>' +
        '<td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><?php echo __("Remove"); ?></button></td>';
      altIdsBody.appendChild(tr);
      altIdIdx++;
    });
  }

  // ── Child level rows ────────────────────────────────────────────────
  var childBody = document.querySelector('#childlevels-table tbody');
  var addChildBtn = document.getElementById('add-childlevel-row');
  var childIdx = 0;

  if (addChildBtn) {
    addChildBtn.addEventListener('click', function() {
      var tr = document.createElement('tr');
      tr.innerHTML =
        '<td><input type="text" class="form-control form-control-sm" name="childLevels[' + childIdx + '][identifier]" placeholder="<?php echo __("Identifier"); ?>"></td>' +
        '<td><input type="text" class="form-control form-control-sm" name="childLevels[' + childIdx + '][title]" placeholder="<?php echo __("Title"); ?>"></td>' +
        '<td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><?php echo __("Remove"); ?></button></td>';
      childBody.appendChild(tr);
      childIdx++;
    });
  }

  // ── Language dropdown rows ──────────────────────────────────────────
  var langOptions = '';
  <?php foreach ($rawLangChoices as $code => $name) { ?>
  langOptions += '<option value="<?php echo $code; ?>"><?php echo esc_specialchars($name); ?></option>';
  <?php } ?>

  document.querySelectorAll('.btn-add-lang-row').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var list = document.getElementById(btn.getAttribute('data-target'));
      var div = document.createElement('div');
      div.className = 'input-group input-group-sm mb-1';
      div.innerHTML =
        '<select class="form-select form-select-sm" name="' + btn.getAttribute('data-name') + '">' + langOptions + '</select>' +
        '<button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __("Remove"); ?></button>';
      list.appendChild(div);
    });
  });

  // ── Remove row delegation ──────────────────────────────────────────
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-remove-row')) {
      var tr = e.target.closest('tr');
      if (tr) tr.remove();
    }
    if (e.target.classList.contains('btn-remove-ap')) {
      var group = e.target.closest('.input-group');
      var wrapper = group ? group.closest('.mb-1') : null;
      if (wrapper && wrapper.parentNode.id) {
        wrapper.remove();
      } else if (group) {
        group.remove();
      }
    }
  });

  // ── Actor autocomplete (events — inline) ────────────────────────────
  function initActorAutocomplete(input) {
    setupAutocomplete(input,
      function(q) { return ACTOR_AC_URL + '?query=' + encodeURIComponent(q) + '&limit=10'; },
      function(item) {
        input.value = item.name;
        var hiddenId = input.parentNode.querySelector('input[type=hidden]');
        if (hiddenId) hiddenId.value = item.id;
      }
    );
  }

  document.querySelectorAll('.actor-autocomplete').forEach(initActorAutocomplete);

  // ── Repository autocomplete ─────────────────────────────────────────
  var repoInput = document.getElementById('repositoryName');
  if (repoInput) {
    setupAutocomplete(repoInput,
      function(q) { return REPO_AC_URL + '?query=' + encodeURIComponent(q) + '&limit=10'; },
      function(item) {
        repoInput.value = item.name;
        document.getElementById('repositoryId').value = item.id;
      }
    );
  }

  // ── Name access point add (actor autocomplete) ──────────────────────
  var nameApIdx = document.querySelectorAll('#name-ap-list .input-group').length;

  document.querySelectorAll('.actor-autocomplete-add').forEach(function(input) {
    var targetId = input.getAttribute('data-target');

    setupAutocomplete(input,
      function(q) { return ACTOR_AC_URL + '?query=' + encodeURIComponent(q) + '&limit=10'; },
      function(item) {
        var list = document.getElementById(targetId);
        var div = document.createElement('div');
        div.className = 'input-group input-group-sm mb-1';
        div.innerHTML =
          '<input type="text" class="form-control" value="' + escHtml(item.name) + '" readonly>' +
          '<input type="hidden" name="nameAccessPoints[' + nameApIdx + '][actorId]" value="' + item.id + '">' +
          '<input type="hidden" name="nameAccessPoints[' + nameApIdx + '][actorName]" value="' + escHtml(item.name) + '">' +
          '<button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __("Remove"); ?></button>';
        list.appendChild(div);
        nameApIdx++;
        input.value = '';
      }
    );
  });

  // ── Term access point add (subject, place) ──────────────────────────
  document.querySelectorAll('.term-autocomplete-add').forEach(function(input) {
    var taxonomy = input.getAttribute('data-taxonomy');
    var targetId = input.getAttribute('data-target');
    var fieldName = input.getAttribute('data-name');

    setupAutocomplete(input,
      function(q) { return TERM_AC_URL + '?taxonomy=' + taxonomy + '&query=' + encodeURIComponent(q) + '&limit=10'; },
      function(item) {
        var list = document.getElementById(targetId);
        var div = document.createElement('div');
        div.className = 'input-group input-group-sm mb-1';
        div.innerHTML =
          '<input type="text" class="form-control" value="' + escHtml(item.name) + '" readonly>' +
          '<input type="hidden" name="' + fieldName + '" value="' + item.id + '">' +
          '<button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __("Remove"); ?></button>';
        list.appendChild(div);
        input.value = '';
      }
    );
  });

})();
</script>

<?php end_slot(); ?>
