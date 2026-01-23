<?php use_helper('I18N') ?>

<?php
  $objectId = (int)$objectId;

  // Target languages: all 11 SA spoken languages + English + Dutch + Afrikaans.
  // You can trim this list; availability depends on your MT service.
  $targetLanguages = array(
    'en' => 'English',
    'af' => 'Afrikaans',
    'nl' => 'Dutch',
    'zu' => 'isiZulu',
    'xh' => 'isiXhosa',
    'st' => 'Sesotho',
    'tn' => 'Setswana',
    'nso'=> 'Sepedi (Northern Sotho)',
    'ts' => 'Xitsonga',
    'ss' => 'SiSwati',
    've' => 'Tshivenda',
    'nr' => 'isiNdebele',
  );

  // Field keys must match AhgTranslationRepository::allowedFields()
  $fieldLabels = array(
    'title' => 'Title',
    'scopeAndContent' => 'Scope and Content',
    'archivalHistory' => 'Archival History',
    'arrangement' => 'Arrangement',
    'findingAids' => 'Finding Aids',
  );

  $userCulture = sfContext::getInstance()->getUser()->getCulture();
?>

<button type="button"
        class="btn btn-sm btn-primary"
        data-bs-toggle="modal"
        data-bs-target="#ahgTranslateModal-<?php echo $objectId ?>">
  <?php echo __('Translate record') ?>
</button>

<div class="modal fade"
     id="ahgTranslateModal-<?php echo $objectId ?>"
     tabindex="-1"
     aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 560px;">
    <div class="modal-content" data-object-id="<?php echo $objectId ?>">

      <div class="modal-header" style="background:#c24a00;">
        <h5 class="modal-title" style="color:#0a8a0a;font-weight:700;">
          <?php echo __('Translate Record') ?>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?php echo __('Close') ?>"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label"><?php echo __('Target Language') ?></label>
          <select class="form-select ahg-translate-target">
            <?php foreach ($targetLanguages as $code => $label): ?>
              <option value="<?php echo $code ?>" <?php echo $code === 'en' ? 'selected' : '' ?>>
                <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-2" style="font-weight:700;">
          <?php echo __('Fields to Translate') ?>
        </div>

        <div class="ahg-translate-fields mb-3">
          <?php foreach ($fieldLabels as $key => $label): ?>
            <div class="form-check">
              <input class="form-check-input ahg-translate-field"
                     type="checkbox"
                     value="<?php echo $key ?>"
                     id="ahg-translate-<?php echo $objectId ?>-<?php echo $key ?>"
                     <?php echo in_array($key, array('title','scopeAndContent'), true) ? 'checked' : '' ?>>
              <label class="form-check-label"
                     for="ahg-translate-<?php echo $objectId ?>-<?php echo $key ?>">
                <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
              </label>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="text-muted" style="font-size:0.9rem;">
          <?php echo __('Translation uses a local MT service (offline). Language packs must be installed.') ?>
        </div>

        <div class="mt-3">
          <div class="alert alert-secondary py-2 mb-0 ahg-translate-status" style="display:none; white-space:pre-wrap;"></div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <?php echo __('Close') ?>
        </button>
        <button type="button" class="btn btn-success ahg-translate-run">
          <?php echo __('Translate') ?>
        </button>
      </div>

    </div>
  </div>
</div>

<script>
(function(){
  const modalEl = document.getElementById("ahgTranslateModal-<?php echo $objectId ?>");
  if (!modalEl) return;

  const content = modalEl.querySelector(".modal-content");
  const objectId = content.getAttribute("data-object-id");
  const btnRun = content.querySelector(".ahg-translate-run");
  const statusEl = content.querySelector(".ahg-translate-status");
  const targetSel = content.querySelector(".ahg-translate-target");

  function getSelectedFields() {
    return Array.from(content.querySelectorAll(".ahg-translate-field:checked"))
      .map(cb => cb.value);
  }

  function showStatus(msg) {
    statusEl.style.display = "block";
    statusEl.textContent = msg;
  }

  async function translateField(fieldKey, target) {
    const body = new URLSearchParams({
      field: fieldKey,
      source: "<?php echo htmlspecialchars($userCulture, ENT_QUOTES, 'UTF-8') ?>",
      target: target,
      apply: "1",
      overwrite: "0"
    });

    const res = await fetch(`/translation/translate/${objectId}`, {
      method: "POST",
      headers: {"Content-Type":"application/x-www-form-urlencoded"},
      body
    });

    let json;
    try { json = await res.json(); } catch (e) { json = { ok:false, error:"Invalid JSON response" }; }
    return { field: fieldKey, http: res.status, result: json };
  }

  btnRun.addEventListener("click", async () => {
    const target = targetSel.value;
    const fields = getSelectedFields();

    if (!fields.length) {
      showStatus("Select at least one field.");
      return;
    }

    btnRun.disabled = true;
    showStatus(`Translating to "${target}"...\n`);

    const results = [];
    for (let i=0; i<fields.length; i++) {
      const f = fields[i];
      showStatus(`Translating to "${target}"...\n${i+1}/${fields.length}: ${f}`);
      results.push(await translateField(f, target));
    }

    const okCount = results.filter(r => r.result && r.result.ok).length;
    const fail = results.filter(r => !r.result || !r.result.ok);

    let summary = `Done.\nOK: ${okCount}/${results.length}\n`;
    if (fail.length) {
      summary += `Failed:\n` + fail.map(r => `- ${r.field}: ${r.result?.error || 'error'}`).join("\n");
    } else {
      summary += "All fields translated and applied.";
    }
    showStatus(summary);
    btnRun.disabled = false;

    // Optional: reload to show applied English fields immediately
    // location.reload();
  });
})();
</script>
