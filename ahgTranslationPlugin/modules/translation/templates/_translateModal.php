<?php use_helper('I18N') ?>

<?php
  $objectId = (int)$objectId;

  // Get settings from database
  use Illuminate\Database\Capsule\Manager as DB;
  $settings = [];
  try {
      $rows = DB::table('ahg_ner_settings')->get();
      foreach ($rows as $row) {
          $settings[$row->setting_key] = $row->setting_value;
      }
  } catch (Exception $e) {
      $settings = [];
  }

  // Target languages with culture codes (all 11 SA + international)
  $targetLanguages = array(
    'en' => ['name' => 'English', 'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture()],
    'af' => ['name' => 'Afrikaans', 'culture' => 'af'],
    'zu' => ['name' => 'isiZulu', 'culture' => 'zu'],
    'xh' => ['name' => 'isiXhosa', 'culture' => 'xh'],
    'st' => ['name' => 'Sesotho', 'culture' => 'st'],
    'tn' => ['name' => 'Setswana', 'culture' => 'tn'],
    'nso'=> ['name' => 'Sepedi (Northern Sotho)', 'culture' => 'nso'],
    'ts' => ['name' => 'Xitsonga', 'culture' => 'ts'],
    'ss' => ['name' => 'SiSwati', 'culture' => 'ss'],
    've' => ['name' => 'Tshivenda', 'culture' => 've'],
    'nr' => ['name' => 'isiNdebele', 'culture' => 'nr'],
    'nl' => ['name' => 'Dutch', 'culture' => 'nl'],
    'fr' => ['name' => 'French', 'culture' => 'fr'],
    'de' => ['name' => 'German', 'culture' => 'de'],
    'es' => ['name' => 'Spanish', 'culture' => 'es'],
    'pt' => ['name' => 'Portuguese', 'culture' => 'pt'],
    'sw' => ['name' => 'Swahili', 'culture' => 'sw'],
    'ar' => ['name' => 'Arabic', 'culture' => 'ar'],
  );

  // All translatable fields from information_object_i18n
  $allFields = array(
    'title' => 'Title',
    'alternate_title' => 'Alternate Title',
    'scope_and_content' => 'Scope and Content',
    'archival_history' => 'Archival History',
    'acquisition' => 'Acquisition',
    'arrangement' => 'Arrangement',
    'access_conditions' => 'Access Conditions',
    'reproduction_conditions' => 'Reproduction Conditions',
    'finding_aids' => 'Finding Aids',
    'related_units_of_description' => 'Related Units',
    'appraisal' => 'Appraisal',
    'accruals' => 'Accruals',
    'physical_characteristics' => 'Physical Characteristics',
    'location_of_originals' => 'Location of Originals',
    'location_of_copies' => 'Location of Copies',
    'extent_and_medium' => 'Extent and Medium',
    'sources' => 'Sources',
    'rules' => 'Rules',
    'revision_history' => 'Revision History',
  );

  // Get saved field selections from settings
  $selectedFields = json_decode($settings['translation_fields'] ?? '["title","scope_and_content"]', true) ?: ['title', 'scope_and_content'];
  $defaultTarget = $settings['translation_target_lang'] ?? 'af';
  $saveCultureDefault = ($settings['translation_save_culture'] ?? '1') === '1';
  $overwriteDefault = ($settings['translation_overwrite'] ?? '0') === '1';

  $userCulture = sfContext::getInstance()->getUser()->getCulture();
?>

<a href="#" data-bs-toggle="modal" data-bs-target="#ahgTranslateModal-<?php echo $objectId ?>">
  <i class="bi bi-translate me-1"></i><?php echo __('Translate') ?>
</a>

<div class="modal fade"
     id="ahgTranslateModal-<?php echo $objectId ?>"
     tabindex="-1"
     aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" data-object-id="<?php echo $objectId ?>">

      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title">
          <i class="fas fa-language me-2"></i><?php echo __('Translate Record') ?>
          <span class="ahg-step-indicator badge bg-light text-dark ms-2">Step 1: Select Fields</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?php echo __('Close') ?>"></button>
      </div>

      <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">

        <!-- STEP 1: Field Selection -->
        <div class="ahg-step-1">
          <!-- Language Selection -->
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label fw-bold"><?php echo __('Source Language') ?></label>
              <select class="form-select ahg-translate-source">
                <?php foreach ($targetLanguages as $code => $langData): ?>
                  <option value="<?php echo $code ?>" data-culture="<?php echo $langData['culture'] ?>"
                      <?php echo $code === $userCulture ? 'selected' : '' ?>>
                    <?php echo htmlspecialchars($langData['name'], ENT_QUOTES, 'UTF-8') ?> (<?php echo $langData['culture'] ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold"><?php echo __('Target Language') ?></label>
              <select class="form-select ahg-translate-target">
                <?php foreach ($targetLanguages as $code => $langData): ?>
                  <option value="<?php echo $code ?>" data-culture="<?php echo $langData['culture'] ?>"
                      <?php echo $code === $defaultTarget ? 'selected' : '' ?>>
                    <?php echo htmlspecialchars($langData['name'], ENT_QUOTES, 'UTF-8') ?> (<?php echo $langData['culture'] ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- Options -->
          <div class="row mb-3">
            <div class="col-md-6">
              <div class="form-check form-switch">
                <input class="form-check-input ahg-save-culture" type="checkbox" id="ahg-save-culture-<?php echo $objectId ?>"
                    <?php echo $saveCultureDefault ? 'checked' : '' ?>>
                <label class="form-check-label fw-bold" for="ahg-save-culture-<?php echo $objectId ?>">
                  <?php echo __('Save with AtoM culture code') ?>
                </label>
              </div>
              <small class="text-muted">Saves translation in target language's culture</small>
            </div>
            <div class="col-md-6">
              <div class="form-check form-switch">
                <input class="form-check-input ahg-overwrite" type="checkbox" id="ahg-overwrite-<?php echo $objectId ?>"
                    <?php echo $overwriteDefault ? 'checked' : '' ?>>
                <label class="form-check-label fw-bold" for="ahg-overwrite-<?php echo $objectId ?>">
                  <?php echo __('Overwrite existing') ?>
                </label>
              </div>
              <small class="text-muted">Overwrite if target field already has content</small>
            </div>
          </div>

          <hr>

          <!-- Fields Selection -->
          <div class="mb-2">
            <span class="fw-bold"><?php echo __('Fields to Translate') ?></span>
            <div class="float-end">
              <button type="button" class="btn btn-sm btn-outline-secondary ahg-select-all"><?php echo __('Select All') ?></button>
              <button type="button" class="btn btn-sm btn-outline-secondary ahg-deselect-all"><?php echo __('Deselect All') ?></button>
            </div>
          </div>

          <div class="row">
            <?php $i = 0; foreach ($allFields as $key => $label): ?>
              <?php if ($i % 10 === 0): ?><div class="col-md-6"><?php endif; ?>
              <div class="form-check">
                <input class="form-check-input ahg-translate-field"
                       type="checkbox"
                       value="<?php echo $key ?>"
                       data-label="<?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>"
                       id="ahg-translate-<?php echo $objectId ?>-<?php echo $key ?>"
                       <?php echo in_array($key, $selectedFields) ? 'checked' : '' ?>>
                <label class="form-check-label" for="ahg-translate-<?php echo $objectId ?>-<?php echo $key ?>">
                  <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </label>
              </div>
              <?php if ($i % 10 === 9 || $i === count($allFields) - 1): ?></div><?php endif; ?>
            <?php $i++; endforeach; ?>
          </div>

          <div class="alert alert-info py-2 mt-3 mb-0">
            <i class="fas fa-info-circle me-1"></i>
            <?php echo __('Click "Translate" to preview translations before saving.') ?>
          </div>
        </div>

        <!-- STEP 2: Review Translations -->
        <div class="ahg-step-2" style="display:none;">
          <div class="alert alert-warning py-2 mb-3">
            <i class="fas fa-eye me-1"></i>
            <strong><?php echo __('Review Translations') ?></strong> - Edit if needed, then click "Approve & Save" to apply.
          </div>

          <div class="ahg-translations-preview">
            <!-- Translations will be inserted here by JavaScript -->
          </div>
        </div>

        <!-- Status Messages -->
        <div class="mt-3">
          <div class="alert py-2 mb-0 ahg-translate-status" style="display:none; white-space:pre-wrap;"></div>
        </div>
      </div>

      <div class="modal-footer">
        <!-- Step 1 buttons -->
        <div class="ahg-step-1-buttons">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i><?php echo __('Close') ?>
          </button>
          <button type="button" class="btn btn-primary ahg-translate-run">
            <i class="fas fa-language me-1"></i><?php echo __('Translate') ?>
          </button>
        </div>

        <!-- Step 2 buttons -->
        <div class="ahg-step-2-buttons" style="display:none;">
          <button type="button" class="btn btn-outline-secondary ahg-back-to-step1">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back') ?>
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i><?php echo __('Cancel') ?>
          </button>
          <button type="button" class="btn btn-success ahg-approve-save">
            <i class="fas fa-check me-1"></i><?php echo __('Approve & Save') ?>
          </button>
        </div>
      </div>

    </div>
  </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function(){
  const modalEl = document.getElementById("ahgTranslateModal-<?php echo $objectId ?>");
  if (!modalEl) return;

  const content = modalEl.querySelector(".modal-content");
  const objectId = content.getAttribute("data-object-id");
  const statusEl = content.querySelector(".ahg-translate-status");
  const sourceSel = content.querySelector(".ahg-translate-source");
  const targetSel = content.querySelector(".ahg-translate-target");
  const saveCultureCb = content.querySelector(".ahg-save-culture");
  const overwriteCb = content.querySelector(".ahg-overwrite");
  const stepIndicator = content.querySelector(".ahg-step-indicator");

  const step1 = content.querySelector(".ahg-step-1");
  const step2 = content.querySelector(".ahg-step-2");
  const step1Buttons = content.querySelector(".ahg-step-1-buttons");
  const step2Buttons = content.querySelector(".ahg-step-2-buttons");
  const previewContainer = content.querySelector(".ahg-translations-preview");

  const btnTranslate = content.querySelector(".ahg-translate-run");
  const btnBack = content.querySelector(".ahg-back-to-step1");
  const btnApprove = content.querySelector(".ahg-approve-save");

  let translationResults = []; // Store results for approval

  // Select/Deselect all buttons
  content.querySelector(".ahg-select-all").addEventListener("click", () => {
    content.querySelectorAll(".ahg-translate-field").forEach(cb => cb.checked = true);
  });
  content.querySelector(".ahg-deselect-all").addEventListener("click", () => {
    content.querySelectorAll(".ahg-translate-field").forEach(cb => cb.checked = false);
  });

  function showStep(step) {
    if (step === 1) {
      step1.style.display = "block";
      step2.style.display = "none";
      step1Buttons.style.display = "block";
      step2Buttons.style.display = "none";
      stepIndicator.textContent = "Step 1: Select Fields";
    } else {
      step1.style.display = "none";
      step2.style.display = "block";
      step1Buttons.style.display = "none";
      step2Buttons.style.display = "block";
      stepIndicator.textContent = "Step 2: Review & Approve";
    }
  }

  function getSelectedFields() {
    return Array.from(content.querySelectorAll(".ahg-translate-field:checked"))
      .map(cb => ({ source: cb.value, label: cb.dataset.label }));
  }

  function showStatus(msg, type = "secondary") {
    statusEl.style.display = "block";
    statusEl.textContent = msg;
    statusEl.className = `alert py-2 mb-0 alert-${type}`;
  }

  function hideStatus() {
    statusEl.style.display = "none";
  }

  async function translateFieldPreview(sourceField, source, target) {
    const body = new URLSearchParams({
      field: sourceField,
      targetField: sourceField,
      source: source,
      target: target,
      apply: "0", // Don't apply yet, just translate
      saveCulture: "0",
      overwrite: "0"
    });

    const res = await fetch(`/translation/translate/${objectId}`, {
      method: "POST",
      headers: {"Content-Type":"application/x-www-form-urlencoded"},
      body
    });

    let json;
    try { json = await res.json(); } catch (e) { json = { ok:false, error:"Invalid JSON response" }; }
    return json;
  }

  function renderPreview(results) {
    let html = '<div class="accordion" id="translationAccordion-' + objectId + '">';

    results.forEach((r, idx) => {
      const statusBadge = r.ok
        ? '<span class="badge bg-success">OK</span>'
        : '<span class="badge bg-danger">Failed</span>';

      html += `
        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button" type="button"
                    data-bs-toggle="collapse" data-bs-target="#collapse-${objectId}-${idx}"
                    aria-expanded="true">
              ${statusBadge} <strong class="ms-2">${r.label}</strong>
            </button>
          </h2>
          <div id="collapse-${objectId}-${idx}" class="accordion-collapse collapse show">
            <div class="accordion-body">
              ${r.ok ? `
                <div class="row">
                  <div class="col-md-6">
                    <label class="form-label fw-bold text-muted">Source Text</label>
                    <div class="border rounded p-2 bg-light" style="max-height:150px;overflow-y:auto;">
                      ${escapeHtml(r.sourceText || '(empty)')}
                    </div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-bold text-success">
                      <i class="fas fa-arrow-right me-1"></i>Translation
                    </label>
                    <textarea class="form-control ahg-translated-text" data-field="${r.field}" data-draft-id="${r.draft_id}"
                              rows="4" style="max-height:150px;">${escapeHtml(r.translation || '')}</textarea>
                  </div>
                </div>
              ` : `
                <div class="alert alert-danger mb-0">
                  <i class="fas fa-exclamation-triangle me-1"></i>
                  ${escapeHtml(r.error || 'Translation failed')}
                </div>
              `}
            </div>
          </div>
        </div>
      `;
    });

    html += '</div>';
    previewContainer.innerHTML = html;
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  // Translate button click - Step 1 to Step 2
  btnTranslate.addEventListener("click", async () => {
    const source = sourceSel.value;
    const target = targetSel.value;
    const fields = getSelectedFields();

    if (!fields.length) {
      showStatus("Select at least one field.", "danger");
      return;
    }

    if (source === target) {
      showStatus("Source and target language must be different.", "danger");
      return;
    }

    btnTranslate.disabled = true;
    const targetName = targetSel.options[targetSel.selectedIndex].text;
    showStatus(`Translating ${fields.length} field(s) to ${targetName}...`, "info");

    translationResults = [];
    for (let i = 0; i < fields.length; i++) {
      const f = fields[i];
      showStatus(`Translating ${i+1}/${fields.length}: ${f.label}...`, "info");

      const result = await translateFieldPreview(f.source, source, target);
      translationResults.push({
        field: f.source,
        label: f.label,
        ok: result.ok,
        translation: result.translation || '',
        sourceText: result.source_text || '',
        draft_id: result.draft_id,
        error: result.error
      });
    }

    btnTranslate.disabled = false;

    const okCount = translationResults.filter(r => r.ok).length;
    if (okCount === 0) {
      showStatus("All translations failed. Check the OPUS-MT service.", "danger");
      return;
    }

    hideStatus();
    renderPreview(translationResults);
    showStep(2);
  });

  // Back button - Step 2 to Step 1
  btnBack.addEventListener("click", () => {
    showStep(1);
    hideStatus();
  });

  // Approve & Save button
  btnApprove.addEventListener("click", async () => {
    const saveCulture = saveCultureCb.checked;
    const overwrite = overwriteCb.checked;
    const target = targetSel.value;

    btnApprove.disabled = true;
    showStatus("Saving translations...", "info");

    let savedCount = 0;
    let failCount = 0;

    // Get edited translations from textareas
    const textareas = previewContainer.querySelectorAll(".ahg-translated-text");

    for (const textarea of textareas) {
      const draftId = textarea.dataset.draftId;
      const field = textarea.dataset.field;
      const editedText = textarea.value;

      if (!draftId) continue;

      // Apply the draft with possibly edited text
      const body = new URLSearchParams({
        draftId: draftId,
        overwrite: overwrite ? "1" : "0",
        saveCulture: saveCulture ? "1" : "0",
        targetCulture: target,
        editedText: editedText
      });

      try {
        const res = await fetch(`/translation/apply`, {
          method: "POST",
          headers: {"Content-Type":"application/x-www-form-urlencoded"},
          body
        });
        const json = await res.json();
        if (json.ok) {
          savedCount++;
        } else {
          failCount++;
        }
      } catch (e) {
        failCount++;
      }
    }

    btnApprove.disabled = false;

    if (failCount === 0) {
      showStatus(`Successfully saved ${savedCount} translation(s) with culture code "${target}".`, "success");
      // Optionally reload page after a delay
      setTimeout(() => { location.reload(); }, 2000);
    } else {
      showStatus(`Saved: ${savedCount}, Failed: ${failCount}`, failCount > 0 ? "warning" : "success");
    }
  });

  // Reset when modal is closed
  modalEl.addEventListener("hidden.bs.modal", () => {
    showStep(1);
    hideStatus();
    previewContainer.innerHTML = "";
    translationResults = [];
  });
})();
</script>
