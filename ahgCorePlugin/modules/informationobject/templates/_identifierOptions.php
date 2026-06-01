<?php if (isset($mask)) { ?>
  <input
    name="usingMask"
    id="using-identifier-mask"
    type="hidden"
    value="<?php echo $mask; ?>">
<?php } ?>

<div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
  <?php if (empty($hideAltIdButton)) { ?>
    <button
      class="btn atom-btn-white text-wrap<?php echo 0 < count($alternativeIdentifiers) ? '' : ' collapsed'; ?>"
      type="button"
      data-bs-toggle="collapse"
      data-bs-target="#alternative-identifiers-table"
      aria-expanded="<?php echo 0 < count($alternativeIdentifiers) ? 'true' : 'false'; ?>"
      aria-controls="alternative-identifiers-table">
      <i class="fas fa-plus me-1" aria-hidden="true"></i>
      <?php echo __('Add alternative identifier(s)'); ?>
    </button>
  <?php } ?>

  <?php if (empty($hideGenerateButton)) { ?>
    <button
      class="btn atom-btn-white text-wrap"
      id="generate-identifier"
      type="button"
      data-generate-identifier-url="<?php echo url_for([
          'module' => 'informationobject',
          'action' => 'generateIdentifier',
      ]); ?>">
      <i class="fas fa-cog me-1" aria-hidden="true"></i>
      <?php echo __('Generate identifier'); ?>
    </button>
  <?php } ?>
</div>

<?php if (empty($hideAltIdButton)) { ?>
  <div
    id="alternative-identifiers-table"
    class="collapse<?php echo 0 < count($alternativeIdentifiers) ? ' show' : ''; ?>">
    <h3 class="fs-6 mb-2">
      <?php echo __('Alternative identifier(s)'); ?>
    </h3>

    <div class="table-responsive mb-2">
      <table class="table table-bordered mb-0 multi-row">
        <thead class="table-light">
          <tr>
            <th id="alt-identifiers-label-head" class="w-50">
              <?php echo __('Label'); ?>
            </th>
            <th id="alt-identifiers-identifier-head" class="w-50"> 
              <?php echo __('Identifier'); ?>
            </th>
            <th>
              <span class="visually-hidden"><?php echo __('Delete'); ?></span>
            </th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 0;
          foreach ($alternativeIdentifiers as $item) { ?>
            <?php $form->getWidgetSchema()->setNameFormat("alternativeIdentifiers[{$i}][%s]");
            ++$i; ?>

            <tr class="related_obj_<?php echo $item->id; ?>">
              <td>
                <input
                  type="hidden"
                  name="<?php echo $form->getWidgetSchema()->generateName('id'); ?>"
                  value="<?php echo $item->id; ?>">
                <?php $form->setDefault('label', $item->name); ?>
                <?php echo render_field($form->label, null, [
                    'aria-labelledby' => 'alt-identifiers-label-head',
                    'aria-describedby' => 'alt-identifiers-table-help',
                    'onlyInputs' => true,
                ]); ?>
              </td>
              <td>
                <?php $form->setDefault('identifier', $item->getValue(['sourceCulture' => true])); ?>
                <?php echo render_field($form->identifier, null, [
                    'aria-labelledby' => 'alt-identifiers-identifier-head',
                    'aria-describedby' => 'alt-identifiers-table-help',
                    'onlyInputs' => true,
                ]); ?>
              </td>
              <td>
                <button type="button" class="multi-row-delete btn atom-btn-white">
                  <i class="fas fa-times" aria-hidden="true"></i>
                  <span class="visually-hidden"><?php echo __('Delete row'); ?></span>
                </button>
              </td>
            </tr>
          <?php } ?>

          <?php $form->getWidgetSchema()->setNameFormat("alternativeIdentifiers[{$i}][%s]"); ?>

          <tr>
            <td>
              <?php $form->setDefault('label', ''); ?>
              <?php echo render_field($form->label, null, [
                  'aria-labelledby' => 'alt-identifiers-label-head',
                  'aria-describedby' => 'alt-identifiers-table-help',
                  'onlyInputs' => true,
              ]); ?>
            </td>
            <td>
              <?php $form->setDefault('identifier', ''); ?>
              <?php echo render_field($form->identifier, null, [
                  'aria-labelledby' => 'alt-identifiers-identifier-head',
                  'aria-describedby' => 'alt-identifiers-table-help',
                  'onlyInputs' => true,
              ]); ?>
            </td>
            <td>
              <button type="button" class="multi-row-delete btn atom-btn-white">
                <i class="fas fa-times" aria-hidden="true"></i>
                <span class="visually-hidden"><?php echo __('Delete row'); ?></span>
              </button>
            </td>
          </tr>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="3">
              <button type="button" class="multi-row-add btn atom-btn-white">
                <i class="fas fa-plus me-1" aria-hidden="true"></i>
                <?php echo __('Add new'); ?>
              </button>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>

    <div class="form-text mb-3" id="alt-identifiers-table-help">
      <?php echo __(
          '<strong>Label:</strong> Enter a name for the alternative identifier field that indicates its purpose and usage.<br/><strong>Identifier:</strong> Enter a legacy reference code, alternative identifier, or any other alpha-numeric string associated with the record.'
      ); ?>
    </div>
  </div>
<?php } ?>

<?php // Shared across every sector edit form (ISAD/RAD/DC/MODS/DACS/CCO) that
      // includes this partial. Browsers heuristically treat the blank, mandatory
      // id="identifier" field as a username/login and autofill it with the saved
      // account email. Disable autofill and scrub any email-shaped value the
      // browser injects (immediately + shortly after load, to catch late fill). ?>
<script <?php $n = sfConfig::get('csp_nonce', '');
echo $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : ''; ?>>
(function () {
  var input = document.getElementById('identifier')
    || document.querySelector('input[name="identifier"]');
  if (!input) { return; }
  input.setAttribute('autocomplete', 'off');
  input.setAttribute('autocorrect', 'off');
  input.setAttribute('autocapitalize', 'off');
  input.setAttribute('spellcheck', 'false');
  var isEmail = function (v) { return /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test((v || '').trim()); };
  var scrub = function () { if (isEmail(input.value)) { input.value = ''; } };
  scrub();
  setTimeout(scrub, 250);
  setTimeout(scrub, 800);
})();
</script>
