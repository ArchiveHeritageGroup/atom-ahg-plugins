<?php
/*
 * CAAIS 1.0 profile - accession edit form (#199, #203). Port of Heratio's
 * partials/_caais-edit.blade.php; the field names (caais[...]) are the same,
 * so the two forms post the same shape.
 *
 * The repository link (CAAIS 1.1) is shown whenever the tables exist; the
 * rest only when the profile is switched on (ahg_settings
 * accession_caais_enabled). Saved by ahgAccessionManagePluginConfiguration::
 * saveCaaisProfile() after base AtoM saves the accession.
 *
 * Expects: accessionId (int|null - null on the add form).
 */
require_once dirname(__DIR__, 3).'/lib/Services/CaaisProfileService.php';

$caaisService = new \AhgAccessionManage\Services\CaaisProfileService();
if (!$caaisService->installed()) {
    return;
}

$caaisId = (int) ($accessionId ?? 0);
$caaisEnabled = $caaisService->enabled();
$cz = $caaisService->choices();
$caais = $caaisService->get($caaisId);
$caaisSources = $caaisId ? $caaisService->sources($caaisId) : [];
$caaisRepositories = $caaisService->repositoryOptions();

// A refused accession form comes back with what was typed, as Heratio's old()
// does; otherwise the stored profile. Sources are keyed by actor id in both.
$caaisRequest = sfContext::getInstance()->getRequest();
$caaisPosted = $caaisRequest->isMethod('post') ? $caaisRequest->getPostParameter('caais') : null;
if (is_array($caaisPosted)) {
    foreach (['repository_id', 'rules_or_conventions', 'sources', 'extents', 'languages', 'preservation', 'events'] as $k) {
        if (array_key_exists($k, $caaisPosted)) {
            $caais[$k] = $caaisPosted[$k];
        }
    }
}

$h = function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
$selected = function ($a, $b) {
    return (string) $a === (string) $b ? ' selected' : '';
};
$options = function (string $key, $current) use ($cz, $h, $selected) {
    $out = '<option value=""></option>';
    foreach ($cz[$key] as $code => $label) {
        $out .= '<option value="'.$h($code).'"'.$selected($current ?? '', $code).'>'.$h($label).'</option>';
    }

    return $out;
};
// The stored rows plus one empty row to type into.
$rowsFor = function (string $key, array $fields) use ($caais) {
    $rows = array_values(array_filter((array) ($caais[$key] ?? []), 'is_array'));
    $rows[] = array_fill_keys($fields, '');

    return $rows;
};

// CAAIS 5.1 makes the physical transfer (and, if it differs, the legal
// transfer) mandatory. Offer a row for each one not yet recorded, type
// already chosen; an untouched one is not saved (see clean()).
$caaisEventRows = $rowsFor('events', ['event_type', 'event_date', 'agent', 'note']);
$caaisHaveTypes = array_column((array) ($caais['events'] ?? []), 'event_type');
$caaisSuggested = [];
foreach ([\AhgAccessionManage\Services\CaaisProfileService::EVENT_PHYSICAL, \AhgAccessionManage\Services\CaaisProfileService::EVENT_LEGAL] as $mandatory) {
    if (!in_array($mandatory, $caaisHaveTypes, true) && isset($cz['event_type'][$mandatory])) {
        $caaisSuggested[] = ['event_type' => $mandatory, 'event_date' => '', 'agent' => '', 'note' => '', '_suggested' => 1];
    }
}
if ($caaisSuggested) {
    // Replace the trailing empty row: the suggestions are the rows to type into.
    array_pop($caaisEventRows);
    $caaisEventRows = array_merge($caaisEventRows, $caaisSuggested);
}
?>
      <div class="accordion-item">
        <h2 class="accordion-header" id="caais-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#caais-collapse" aria-expanded="false" aria-controls="caais-collapse">
            <?php echo $caaisEnabled ? __('Repository and CAAIS profile') : __('Repository'); ?>
          </button>
        </h2>
        <div id="caais-collapse" class="accordion-collapse collapse" aria-labelledby="caais-heading">
          <div class="accordion-body">

            <div class="mb-3">
              <label for="caais_repository_id" class="form-label"><?php echo __('Repository'); ?> <span class="badge bg-secondary ms-1"><?php echo __('Optional'); ?></span></label>
              <select name="caais[repository_id]" id="caais_repository_id" class="form-select">
                <option value=""></option>
                <?php foreach ($caaisRepositories as $rid => $rname) { ?>
                  <option value="<?php echo (int) $rid; ?>"<?php echo $selected($caais['repository_id'] ?? '', $rid); ?>><?php echo $h($rname); ?></option>
                <?php } ?>
              </select>
              <div class="form-text"><?php echo __('The institution that accepts legal responsibility for the accessioned material (CAAIS 1.1).'); ?></div>
            </div>

            <?php if ($caaisEnabled) { ?>
            <input type="hidden" name="caais[_profile]" value="1">

            <h3 class="fs-6 mt-4 mb-2"><?php echo __('Source confidentiality'); ?> <small class="text-muted">(CAAIS 2.1.6)</small></h3>
            <?php if (empty($caaisSources)) { ?>
              <p class="form-text"><?php echo __('Link a donor and save first; each linked source can then be marked confidential here.'); ?></p>
            <?php } else { ?>
              <div class="table-responsive mb-3">
                <table class="table table-bordered mb-0">
                  <thead><tr><th id="caais-src-name"><?php echo __('Source'); ?></th><th id="caais-src-conf" class="w-50"><?php echo __('Confidentiality'); ?></th></tr></thead>
                  <tbody>
                    <?php foreach ($caaisSources as $src) { ?>
                      <tr>
                        <td><?php echo $h($src['name']); ?></td>
                        <td>
                          <select name="caais[sources][<?php echo (int) $src['id']; ?>]" class="form-select form-select-sm" aria-labelledby="caais-src-conf">
                            <option value=""><?php echo __('None - may be shared'); ?></option>
                            <?php foreach ($cz['confidentiality'] as $code => $label) { ?>
                              <option value="<?php echo $h($code); ?>"<?php echo $selected($caais['sources'][$src['id']] ?? '', $code); ?>><?php echo $h($label); ?></option>
                            <?php } ?>
                          </select>
                        </td>
                      </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
            <?php } ?>

            <h3 class="fs-6 mt-4 mb-2"><?php echo __('Extent statements'); ?> <small class="text-muted">(CAAIS 3.2)</small></h3>
            <div class="table-responsive mb-2">
              <table class="table table-bordered mb-0 caais-repeat" data-caais-key="extents">
                <thead>
                  <tr>
                    <th id="caais-ext-type"><?php echo __('Extent type'); ?></th>
                    <th id="caais-ext-qty"><?php echo __('Quantity'); ?></th>
                    <th id="caais-ext-unit"><?php echo __('Unit'); ?></th>
                    <th id="caais-ext-content"><?php echo __('Content type'); ?></th>
                    <th id="caais-ext-carrier"><?php echo __('Carrier type'); ?></th>
                    <th id="caais-ext-formats"><?php echo __('Digital file formats'); ?></th>
                    <th id="caais-ext-note"><?php echo __('Note'); ?></th>
                    <th><span class="visually-hidden"><?php echo __('Actions'); ?></span></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($rowsFor('extents', ['extent_type', 'quantity', 'is_estimate', 'unit', 'content_type', 'carrier_type', 'digital_file_formats', 'note']) as $i => $row) { ?>
                    <tr>
                      <td><select name="caais[extents][<?php echo $i; ?>][extent_type]" class="form-select form-select-sm" aria-labelledby="caais-ext-type"><?php echo $options('extent_type', $row['extent_type'] ?? ''); ?></select></td>
                      <td>
                        <input type="number" step="any" min="0" name="caais[extents][<?php echo $i; ?>][quantity]" value="<?php echo $h(\AhgAccessionManage\Services\CaaisProfileService::formatQuantity($row['quantity'] ?? null)); ?>" class="form-control form-control-sm" aria-labelledby="caais-ext-qty">
                        <div class="form-check mt-1">
                          <input type="hidden" name="caais[extents][<?php echo $i; ?>][is_estimate]" value="0">
                          <input type="checkbox" class="form-check-input" name="caais[extents][<?php echo $i; ?>][is_estimate]" value="1" id="caais-ext-est-<?php echo $i; ?>"<?php echo empty($row['is_estimate']) ? '' : ' checked'; ?>>
                          <label class="form-check-label small" for="caais-ext-est-<?php echo $i; ?>"><?php echo __('ca. (estimate)'); ?></label>
                        </div>
                      </td>
                      <td><select name="caais[extents][<?php echo $i; ?>][unit]" class="form-select form-select-sm" aria-labelledby="caais-ext-unit"><?php echo $options('unit', $row['unit'] ?? ''); ?></select></td>
                      <td><select name="caais[extents][<?php echo $i; ?>][content_type]" class="form-select form-select-sm" aria-labelledby="caais-ext-content"><?php echo $options('content_type', $row['content_type'] ?? ''); ?></select></td>
                      <td><select name="caais[extents][<?php echo $i; ?>][carrier_type]" class="form-select form-select-sm" aria-labelledby="caais-ext-carrier"><?php echo $options('carrier_type', $row['carrier_type'] ?? ''); ?></select></td>
                      <td><input type="text" name="caais[extents][<?php echo $i; ?>][digital_file_formats]" value="<?php echo $h($row['digital_file_formats'] ?? ''); ?>" maxlength="1024" class="form-control form-control-sm" aria-labelledby="caais-ext-formats"></td>
                      <td><textarea name="caais[extents][<?php echo $i; ?>][note]" rows="1" class="form-control form-control-sm" aria-labelledby="caais-ext-note"><?php echo $h($row['note'] ?? ''); ?></textarea></td>
                      <td><button type="button" class="btn atom-btn-white caais-remove-row"><i class="fas fa-times" aria-hidden="true"></i><span class="visually-hidden"><?php echo __('Delete row'); ?></span></button></td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
            <div class="text-end mb-1"><button type="button" class="btn atom-btn-white caais-add-row" data-caais-target="extents"><i class="fas fa-plus me-1" aria-hidden="true"></i><?php echo __('Add extent statement'); ?></button></div>
            <div class="form-text mb-3"><?php echo __('Record at least the extent received. Add a row per material type if you work at that level of detail. The free-text Received extent units field above is exported as an extent note when no statement is recorded here.'); ?></div>

            <h3 class="fs-6 mt-4 mb-2"><?php echo __('Language of material'); ?> <small class="text-muted">(CAAIS 3.4)</small></h3>
            <div class="table-responsive mb-2">
              <table class="table table-bordered mb-0 caais-repeat" data-caais-key="languages">
                <thead><tr><th id="caais-lang" class="w-25"><?php echo __('Language'); ?></th><th id="caais-lang-note"><?php echo __('Statement'); ?></th><th><span class="visually-hidden"><?php echo __('Actions'); ?></span></th></tr></thead>
                <tbody>
                  <?php foreach ($rowsFor('languages', ['language', 'note']) as $i => $row) { ?>
                    <tr>
                      <td><select name="caais[languages][<?php echo $i; ?>][language]" class="form-select form-select-sm" aria-labelledby="caais-lang"><?php echo $options('language', $row['language'] ?? ''); ?></select></td>
                      <td><input type="text" name="caais[languages][<?php echo $i; ?>][note]" value="<?php echo $h($row['note'] ?? ''); ?>" maxlength="1024" class="form-control form-control-sm" placeholder="<?php echo __('e.g. with partial English translation'); ?>" aria-labelledby="caais-lang-note"></td>
                      <td><button type="button" class="btn atom-btn-white caais-remove-row"><i class="fas fa-times" aria-hidden="true"></i><span class="visually-hidden"><?php echo __('Delete row'); ?></span></button></td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
            <div class="text-end mb-3"><button type="button" class="btn atom-btn-white caais-add-row" data-caais-target="languages"><i class="fas fa-plus me-1" aria-hidden="true"></i><?php echo __('Add language'); ?></button></div>

            <h3 class="fs-6 mt-4 mb-2"><?php echo __('Preservation requirements'); ?> <small class="text-muted">(CAAIS 4.3)</small></h3>
            <div class="table-responsive mb-2">
              <table class="table table-bordered mb-0 caais-repeat" data-caais-key="preservation">
                <thead><tr><th id="caais-pres-type" class="w-25"><?php echo __('Type'); ?></th><th id="caais-pres-value"><?php echo __('Requirement'); ?></th><th id="caais-pres-note"><?php echo __('Note'); ?></th><th><span class="visually-hidden"><?php echo __('Actions'); ?></span></th></tr></thead>
                <tbody>
                  <?php foreach ($rowsFor('preservation', ['requirement_type', 'requirement_value', 'note']) as $i => $row) { ?>
                    <tr>
                      <td><select name="caais[preservation][<?php echo $i; ?>][requirement_type]" class="form-select form-select-sm" aria-labelledby="caais-pres-type"><?php echo $options('requirement_type', $row['requirement_type'] ?? ''); ?></select></td>
                      <td><textarea name="caais[preservation][<?php echo $i; ?>][requirement_value]" rows="1" class="form-control form-control-sm" aria-labelledby="caais-pres-value"><?php echo $h($row['requirement_value'] ?? ''); ?></textarea></td>
                      <td><textarea name="caais[preservation][<?php echo $i; ?>][note]" rows="1" class="form-control form-control-sm" aria-labelledby="caais-pres-note"><?php echo $h($row['note'] ?? ''); ?></textarea></td>
                      <td><button type="button" class="btn atom-btn-white caais-remove-row"><i class="fas fa-times" aria-hidden="true"></i><span class="visually-hidden"><?php echo __('Delete row'); ?></span></button></td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
            <div class="text-end mb-3"><button type="button" class="btn atom-btn-white caais-add-row" data-caais-target="preservation"><i class="fas fa-plus me-1" aria-hidden="true"></i><?php echo __('Add preservation requirement'); ?></button></div>

            <h3 class="fs-6 mt-4 mb-2"><?php echo __('Transfer and accessioning events'); ?> <small class="text-muted">(CAAIS 5.1)</small></h3>
            <div class="table-responsive mb-2">
              <table class="table table-bordered mb-0 caais-repeat" data-caais-key="events">
                <thead><tr><th id="caais-ev-type" class="w-25"><?php echo __('Event type'); ?></th><th id="caais-ev-date"><?php echo __('Date'); ?></th><th id="caais-ev-agent"><?php echo __('Agent'); ?></th><th id="caais-ev-note"><?php echo __('Note'); ?></th><th><span class="visually-hidden"><?php echo __('Actions'); ?></span></th></tr></thead>
                <tbody>
                  <?php foreach ($caaisEventRows as $i => $row) { ?>
                    <tr>
                      <td>
                        <select name="caais[events][<?php echo $i; ?>][event_type]" class="form-select form-select-sm" aria-labelledby="caais-ev-type"><?php echo $options('event_type', $row['event_type'] ?? ''); ?></select>
                        <?php if (!empty($row['_suggested'])) { ?>
                          <input type="hidden" name="caais[events][<?php echo $i; ?>][_suggested]" value="1">
                          <div class="form-text"><?php echo __('Mandatory - not yet recorded'); ?></div>
                        <?php } ?>
                      </td>
                      <td><input type="date" name="caais[events][<?php echo $i; ?>][event_date]" value="<?php echo $h($row['event_date'] ?? ''); ?>" class="form-control form-control-sm" aria-labelledby="caais-ev-date"></td>
                      <td><input type="text" name="caais[events][<?php echo $i; ?>][agent]" value="<?php echo $h($row['agent'] ?? ''); ?>" maxlength="255" class="form-control form-control-sm" aria-labelledby="caais-ev-agent"></td>
                      <td><textarea name="caais[events][<?php echo $i; ?>][note]" rows="1" class="form-control form-control-sm" aria-labelledby="caais-ev-note"><?php echo $h($row['note'] ?? ''); ?></textarea></td>
                      <td><button type="button" class="btn atom-btn-white caais-remove-row"><i class="fas fa-times" aria-hidden="true"></i><span class="visually-hidden"><?php echo __('Delete row'); ?></span></button></td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
            <div class="text-end mb-1"><button type="button" class="btn atom-btn-white caais-add-row" data-caais-target="events"><i class="fas fa-plus me-1" aria-hidden="true"></i><?php echo __('Add event'); ?></button></div>
            <div class="form-text mb-3"><?php echo __('CAAIS requires at least the date the material was physically transferred and, if different, the date legal control passed to the repository.'); ?></div>

            <div class="mb-3">
              <label for="caais_rules" class="form-label"><?php echo __('Rules or conventions'); ?> <small class="text-muted">(CAAIS 7.1)</small> <span class="badge bg-secondary ms-1"><?php echo __('Optional'); ?></span></label>
              <input type="text" name="caais[rules_or_conventions]" id="caais_rules" maxlength="1024" class="form-control"
                     value="<?php echo $h($caais['rules_or_conventions'] ?? ''); ?>" placeholder="<?php echo __('e.g. Canadian Archival Accession Information Standard 1.0'); ?>">
              <div class="form-text"><?php echo __('Creation and revision dates and agents (CAAIS 7.2) are recorded automatically on every save.'); ?></div>
            </div>
            <?php } ?>
          </div>
        </div>
      </div>

<?php if ($caaisEnabled) { ?>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function () {
  // Repeatable CAAIS rows: clone the last row, clear it, renumber its
  // caais[key][N] names. Removing the only row just clears it.
  document.querySelectorAll('.caais-add-row').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var tbody = document.querySelector('table.caais-repeat[data-caais-key="' + btn.dataset.caaisTarget + '"] tbody');
      var rows = tbody.querySelectorAll('tr');
      // A counter, not rows.length: after a delete, rows.length can repeat an index still in use.
      var next = parseInt(tbody.dataset.next || rows.length, 10);
      tbody.dataset.next = next + 1;
      var tr = rows[rows.length - 1].cloneNode(true);
      // A clone of a suggested mandatory-event row is an ordinary row.
      tr.querySelectorAll('input[name$="[_suggested]"], .form-text').forEach(function (el) { el.remove(); });
      tr.querySelectorAll('input, select, textarea').forEach(function (el) {
        el.name = el.name.replace(/\[(extents|languages|preservation|events)\]\[\d+\]/, '[$1][' + next + ']');
        if (el.id) { el.id = el.id.replace(/\d+$/, next); }
        if (el.type === 'checkbox') { el.checked = false; }
        else if (el.type !== 'hidden') { el.value = ''; }
      });
      tr.querySelectorAll('label[for]').forEach(function (l) { l.htmlFor = l.htmlFor.replace(/\d+$/, next); });
      tbody.appendChild(tr);
    });
  });
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.caais-remove-row');
    if (!btn) { return; }
    var tr = btn.closest('tr');
    if (tr.parentNode.querySelectorAll('tr').length > 1) { tr.remove(); return; }
    tr.querySelectorAll('input:not([type=hidden]), select, textarea').forEach(function (el) {
      if (el.type === 'checkbox') { el.checked = false; } else { el.value = ''; }
    });
  });
})();
</script>
<?php } ?>
