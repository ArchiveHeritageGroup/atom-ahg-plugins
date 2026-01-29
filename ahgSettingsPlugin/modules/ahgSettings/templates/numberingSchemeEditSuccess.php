<?php decorate_with('layout_2col.php'); ?>

<?php slot('sidebar'); ?>
  <?php echo get_component('ahgSettings', 'menu'); ?>
<?php end_slot(); ?>

<?php slot('title'); ?>
  <h1>
    <i class="fas fa-hashtag me-2"></i>
    <?php echo $isNew ? __('Add Numbering Scheme') : __('Edit Numbering Scheme'); ?>
  </h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<?php echo $form->renderGlobalErrors(); ?>

<?php echo $form->renderFormTag(url_for(['module' => 'ahgSettings', 'action' => 'numberingSchemeEdit', 'id' => $schemeId])); ?>
  <?php echo $form->renderHiddenFields(); ?>

  <div class="row">
    <div class="col-md-8">
      <!-- Basic Info -->
      <div class="card mb-4">
        <div class="card-header"><i class="fas fa-info-circle me-2"></i><?php echo __('Basic Information'); ?></div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Name'); ?> <span class="text-danger">*</span></label>
                <?php echo $form['name']->render(); ?>
                <?php echo $form['name']->renderError(); ?>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Sector'); ?> <span class="text-danger">*</span></label>
                <?php echo $form['sector']->render(); ?>
                <?php echo $form['sector']->renderError(); ?>
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Description'); ?></label>
            <?php echo $form['description']->render(); ?>
          </div>
        </div>
      </div>

      <!-- Pattern Builder -->
      <div class="card mb-4">
        <div class="card-header"><i class="fas fa-code me-2"></i><?php echo __('Pattern Builder'); ?></div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Pattern'); ?> <span class="text-danger">*</span></label>
            <?php echo $form['pattern']->render(); ?>
            <?php echo $form['pattern']->renderError(); ?>
            <div class="form-text"><?php echo __('Use tokens below to build your pattern'); ?></div>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Insert Token'); ?></label>
            <div class="d-flex flex-wrap gap-1">
              <?php
              $quickTokens = [
                  '{SEQ:4}' => 'SEQ:4',
                  '{SEQ:5}' => 'SEQ:5',
                  '{SEQ:6}' => 'SEQ:6',
                  '{YEAR}' => 'YEAR',
                  '{YY}' => 'YY',
                  '{MONTH}' => 'MONTH',
                  '{PREFIX}' => 'PREFIX',
                  '{REPO}' => 'REPO',
                  '{FONDS}' => 'FONDS',
                  '{TYPE}' => 'TYPE',
                  '{UUID}' => 'UUID',
              ];
              foreach ($quickTokens as $token => $label): ?>
                <button type="button" class="btn btn-sm btn-outline-secondary insert-token" data-token="<?php echo $token; ?>">
                  <?php echo $label; ?>
                </button>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="alert alert-success mb-0">
            <strong><i class="fas fa-eye me-1"></i><?php echo __('Preview'); ?>:</strong>
            <code id="pattern-preview" class="ms-2">-</code>
            <div id="pattern-preview-more" class="mt-1 small"></div>
          </div>
        </div>
      </div>

      <!-- Sequence Settings -->
      <div class="card mb-4">
        <div class="card-header"><i class="fas fa-sort-numeric-up me-2"></i><?php echo __('Sequence Settings'); ?></div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Current Sequence'); ?></label>
                <?php echo $form['current_sequence']->render(); ?>
                <div class="form-text"><?php echo __('Next number will be this + 1'); ?></div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Reset Sequence'); ?></label>
                <?php echo $form['sequence_reset']->render(); ?>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3 mt-4">
                <div class="form-check">
                  <?php echo $form['fill_gaps']->render(['class' => 'form-check-input']); ?>
                  <label class="form-check-label"><?php echo __('Fill gaps (reuse deleted numbers)'); ?></label>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Validation -->
      <div class="card mb-4">
        <div class="card-header"><i class="fas fa-check-circle me-2"></i><?php echo __('Validation'); ?></div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Validation Regex'); ?></label>
                <?php echo $form['validation_regex']->render(); ?>
                <div class="form-text"><?php echo __('Optional regex to validate manual entries'); ?></div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3 mt-4">
                <div class="form-check">
                  <?php echo $form['allow_manual_override']->render(['class' => 'form-check-input']); ?>
                  <label class="form-check-label"><?php echo __('Allow manual entry (users can override auto-generated)'); ?></label>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <!-- Status -->
      <div class="card mb-4">
        <div class="card-header"><i class="fas fa-toggle-on me-2"></i><?php echo __('Status'); ?></div>
        <div class="card-body">
          <div class="form-check mb-2">
            <?php echo $form['is_active']->render(['class' => 'form-check-input']); ?>
            <label class="form-check-label"><?php echo __('Active'); ?></label>
          </div>
          <div class="form-check mb-2">
            <?php echo $form['is_default']->render(['class' => 'form-check-input']); ?>
            <label class="form-check-label"><?php echo __('Default for this sector'); ?></label>
          </div>
          <div class="form-check">
            <?php echo $form['auto_generate']->render(['class' => 'form-check-input']); ?>
            <label class="form-check-label"><?php echo __('Auto-generate on record creation'); ?></label>
          </div>
        </div>
      </div>

      <!-- Token Reference -->
      <div class="card">
        <div class="card-header"><i class="fas fa-book me-2"></i><?php echo __('Token Reference'); ?></div>
        <div class="card-body p-0">
          <table class="table table-sm mb-0">
            <?php foreach ($tokens as $token => $desc): ?>
            <tr>
              <td><code class="small"><?php echo esc_entities($token); ?></code></td>
              <td class="small text-muted"><?php echo esc_entities($desc); ?></td>
            </tr>
            <?php endforeach; ?>
          </table>
        </div>
      </div>
    </div>
  </div>

  <section class="actions mt-4">
    <input class="btn btn-success" type="submit" value="<?php echo __('Save'); ?>">
    <a href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'numberingSchemes']); ?>" class="btn btn-outline-secondary ms-2">
      <?php echo __('Cancel'); ?>
    </a>
  </section>

</form>

<script <?php $n = sfConfig::get('csp_nonce', '');
echo $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  var patternInput = document.getElementById('pattern-input');
  var preview = document.getElementById('pattern-preview');
  var previewMore = document.getElementById('pattern-preview-more');

  // Insert token buttons
  document.querySelectorAll('.insert-token').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var token = this.dataset.token;
      var pos = patternInput.selectionStart;
      var val = patternInput.value;
      patternInput.value = val.slice(0, pos) + token + val.slice(pos);
      patternInput.focus();
      patternInput.selectionStart = patternInput.selectionEnd = pos + token.length;
      updatePreview();
    });
  });

  // Live preview
  function updatePreview() {
    var pattern = patternInput.value;
    if (!pattern) {
      preview.textContent = '-';
      previewMore.innerHTML = '';
      return;
    }

    // Client-side preview simulation
    var year = new Date().getFullYear();
    var yy = String(year).slice(-2);
    var month = String(new Date().getMonth() + 1).padStart(2, '0');
    var day = String(new Date().getDate()).padStart(2, '0');
    var seq = 1;

    var result = pattern
      .replace(/\{SEQ:(\d+)\}/g, function(m, n) { return String(seq).padStart(parseInt(n), '0'); })
      .replace(/\{SEQ\}/g, seq)
      .replace(/\{YEAR\}/g, year)
      .replace(/\{YY\}/g, yy)
      .replace(/\{MONTH\}/g, month)
      .replace(/\{DAY\}/g, day)
      .replace(/\{PREFIX\}/g, 'PREFIX')
      .replace(/\{REPO\}/g, 'REPO')
      .replace(/\{FONDS\}/g, 'A')
      .replace(/\{SERIES\}/g, '1')
      .replace(/\{COLLECTION\}/g, 'COLL')
      .replace(/\{DEPT\}/g, 'DEPT')
      .replace(/\{TYPE\}/g, 'DOC')
      .replace(/\{PROJECT\}/g, 'PROJ')
      .replace(/\{ITEM\}/g, '1')
      .replace(/\{UUID\}/g, 'a1b2c3d4')
      .replace(/\{RANDOM:\d+\}/g, 'X7K9M');

    preview.textContent = result;

    // Show next few
    var more = [];
    for (var i = 2; i <= 3; i++) {
      var r = pattern
        .replace(/\{SEQ:(\d+)\}/g, function(m, n) { return String(i).padStart(parseInt(n), '0'); })
        .replace(/\{SEQ\}/g, i)
        .replace(/\{YEAR\}/g, year)
        .replace(/\{YY\}/g, yy)
        .replace(/\{MONTH\}/g, month)
        .replace(/\{DAY\}/g, day)
        .replace(/\{PREFIX\}/g, 'PREFIX')
        .replace(/\{REPO\}/g, 'REPO')
        .replace(/\{FONDS\}/g, 'A')
        .replace(/\{SERIES\}/g, '1')
        .replace(/\{COLLECTION\}/g, 'COLL')
        .replace(/\{DEPT\}/g, 'DEPT')
        .replace(/\{TYPE\}/g, 'DOC')
        .replace(/\{PROJECT\}/g, 'PROJ')
        .replace(/\{ITEM\}/g, '1')
        .replace(/\{UUID\}/g, 'b2c3d4e5')
        .replace(/\{RANDOM:\d+\}/g, 'Y8L0N');
      more.push('<code>' + r + '</code>');
    }
    previewMore.innerHTML = more.join(', ') + ', ...';
  }

  patternInput.addEventListener('input', updatePreview);
  updatePreview();
});
</script>

<?php end_slot(); ?>
