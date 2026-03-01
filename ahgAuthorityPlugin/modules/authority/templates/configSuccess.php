<?php decorate_with('layout_1col'); ?>

<?php
  $rawConfig = $sf_data->getRaw('config');
  $cfg = is_array($rawConfig) ? $rawConfig : [];

  function cfgVal($cfg, $key, $default = '') {
    return isset($cfg[$key]) ? ($cfg[$key]->config_value ?? $default) : $default;
  }
  function cfgChecked($cfg, $key) {
    return cfgVal($cfg, $key, '0') === '1' ? 'checked' : '';
  }
?>

<?php slot('title'); ?>
  <h1><i class="fas fa-cog me-2"></i><?php echo __('Authority Plugin Configuration'); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="<?php echo url_for('@ahg_authority_dashboard'); ?>"><?php echo __('Authority Dashboard'); ?></a>
      </li>
      <li class="breadcrumb-item active"><?php echo __('Configuration'); ?></li>
    </ol>
  </nav>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <form method="post" action="<?php echo url_for('@ahg_authority_config'); ?>">

    <!-- External Authority Sources -->
    <div class="card mb-3">
      <div class="card-header"><i class="fas fa-globe me-1"></i><?php echo __('External Authority Sources'); ?></div>
      <div class="card-body">
        <div class="row g-3">
          <?php
            $sources = [
              'wikidata' => 'Wikidata',
              'viaf' => 'VIAF',
              'ulan' => 'ULAN (Getty)',
              'lcnaf' => 'LCNAF (Library of Congress)',
              'isni' => 'ISNI',
            ];
            foreach ($sources as $key => $label):
          ?>
            <div class="col-md-4">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="<?php echo $key; ?>_enabled"
                       name="config[<?php echo $key; ?>_enabled]" value="1"
                       <?php echo cfgChecked($cfg, $key . '_enabled'); ?>>
                <label class="form-check-label" for="<?php echo $key; ?>_enabled"><?php echo $label; ?></label>
              </div>
            </div>
          <?php endforeach; ?>
          <div class="col-md-4">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="auto_verify_wikidata"
                     name="config[auto_verify_wikidata]" value="1"
                     <?php echo cfgChecked($cfg, 'auto_verify_wikidata'); ?>>
              <label class="form-check-label" for="auto_verify_wikidata">
                <?php echo __('Auto-verify Wikidata matches'); ?>
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Completeness & Quality -->
    <div class="card mb-3">
      <div class="card-header"><i class="fas fa-chart-bar me-1"></i><?php echo __('Completeness & Quality'); ?></div>
      <div class="card-body">
        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" id="completeness_auto_recalc"
                 name="config[completeness_auto_recalc]" value="1"
                 <?php echo cfgChecked($cfg, 'completeness_auto_recalc'); ?>>
          <label class="form-check-label" for="completeness_auto_recalc">
            <?php echo __('Auto-recalculate completeness scores'); ?>
          </label>
        </div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="hide_stubs_from_public"
                 name="config[hide_stubs_from_public]" value="1"
                 <?php echo cfgChecked($cfg, 'hide_stubs_from_public'); ?>>
          <label class="form-check-label" for="hide_stubs_from_public">
            <?php echo __('Hide stub records from public view'); ?>
          </label>
        </div>
      </div>
    </div>

    <!-- NER Pipeline -->
    <div class="card mb-3">
      <div class="card-header"><i class="fas fa-robot me-1"></i><?php echo __('NER Pipeline'); ?></div>
      <div class="card-body">
        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" id="ner_auto_stub_enabled"
                 name="config[ner_auto_stub_enabled]" value="1"
                 <?php echo cfgChecked($cfg, 'ner_auto_stub_enabled'); ?>>
          <label class="form-check-label" for="ner_auto_stub_enabled">
            <?php echo __('Auto-create stubs from NER entities'); ?>
          </label>
        </div>
        <div class="mb-3">
          <label class="form-label"><?php echo __('Minimum confidence threshold'); ?></label>
          <input type="number" name="config[ner_auto_stub_threshold]" class="form-control" style="max-width:200px"
                 value="<?php echo htmlspecialchars(cfgVal($cfg, 'ner_auto_stub_threshold', '0.85')); ?>"
                 min="0" max="1" step="0.05">
        </div>
      </div>
    </div>

    <!-- Merge / Dedup -->
    <div class="card mb-3">
      <div class="card-header"><i class="fas fa-clone me-1"></i><?php echo __('Merge & Deduplication'); ?></div>
      <div class="card-body">
        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" id="merge_require_approval"
                 name="config[merge_require_approval]" value="1"
                 <?php echo cfgChecked($cfg, 'merge_require_approval'); ?>>
          <label class="form-check-label" for="merge_require_approval">
            <?php echo __('Require approval for merge operations'); ?>
          </label>
        </div>
        <div class="mb-3">
          <label class="form-label"><?php echo __('Deduplication threshold (0-1)'); ?></label>
          <input type="number" name="config[dedup_threshold]" class="form-control" style="max-width:200px"
                 value="<?php echo htmlspecialchars(cfgVal($cfg, 'dedup_threshold', '0.80')); ?>"
                 min="0" max="1" step="0.05">
        </div>
      </div>
    </div>

    <!-- ISDF Functions -->
    <div class="card mb-3">
      <div class="card-header"><i class="fas fa-sitemap me-1"></i><?php echo __('ISDF Functions'); ?></div>
      <div class="card-body">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="function_linking_enabled"
                 name="config[function_linking_enabled]" value="1"
                 <?php echo cfgChecked($cfg, 'function_linking_enabled'); ?>>
          <label class="form-check-label" for="function_linking_enabled">
            <?php echo __('Enable structured function linking'); ?>
          </label>
        </div>
      </div>
    </div>

    <div class="mb-3">
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-save me-1"></i><?php echo __('Save configuration'); ?>
      </button>
      <a href="<?php echo url_for('@ahg_authority_dashboard'); ?>" class="btn btn-outline-secondary ms-2">
        <?php echo __('Cancel'); ?>
      </a>
    </div>
  </form>
<?php end_slot(); ?>
