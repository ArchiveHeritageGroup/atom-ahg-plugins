<?php decorate_with('layout_1col'); ?>
<?php
$v = $sf_data->getRaw('values');
$labels = $sf_data->getRaw('labels');
$records = $sf_data->getRaw('records');
$slugs = $sf_data->getRaw('slugs');
$val = function ($k) use ($v) { return isset($v[$k]) && is_string($v[$k]) ? $v[$k] : ''; };
$langs = isset($v['languages']) && is_array($v['languages']) ? $v['languages'] : [];
?>

<?php slot('title'); ?>
  <h1><?php echo __('Batch edit %1% description(s)', ['%1%' => count($records)]); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <?php foreach ($sf_data->getRaw('errors') as $error) { ?>
    <div class="alert alert-danger"><?php echo esc_entities($error); ?></div>
  <?php } ?>
  <?php if ($tooMany) { ?>
    <div class="alert alert-warning"><?php echo __('Only the first %1% clipboard descriptions are used in one batch.', ['%1%' => $maxRecords]); ?></div>
  <?php } ?>
  <?php if (count($sf_data->getRaw('missing'))) { ?>
    <div class="alert alert-warning"><?php echo __('%1% clipboard entries are not archival descriptions any more and were left out.', ['%1%' => count($sf_data->getRaw('missing'))]); ?></div>
  <?php } ?>

  <?php if ($records) { ?>
  <p><?php echo __('Fill in only what should change. Empty fields are left alone. Access points, dates and creators are added; existing ones are never removed. Nothing is saved until you have seen the preview.'); ?></p>

  <form method="post" action="<?php echo url_for(['module' => 'batchEdit', 'action' => 'batch']); ?>">
    <?php echo get_partial('batchEdit/carry', ['slugs' => $slugs, 'values' => []]); ?>
    <input type="hidden" name="step" value="preview">

    <div class="accordion mb-3">
      <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#be-fields" aria-expanded="true"><?php echo __('Fields'); ?></button></h2>
        <div id="be-fields" class="accordion-collapse collapse show">
          <div class="accordion-body row g-3">
            <?php foreach (['levelId' => 'level', 'repositoryId' => 'repository', 'pubStatusId' => 'status'] as $key => $list) { ?>
              <div class="col-md-4">
                <label class="form-label" for="be-<?php echo $key; ?>"><?php echo esc_entities($sf_data->getRaw('fieldLabels')[$key]); ?></label>
                <select class="form-select" id="be-<?php echo $key; ?>" name="<?php echo $key; ?>">
                  <option value=""><?php echo __('- no change -'); ?></option>
                  <?php foreach ($labels[$list] as $id => $name) { ?>
                    <option value="<?php echo (int) $id; ?>" <?php echo (string) $id === $val($key) ? 'selected' : ''; ?>><?php echo esc_entities($name); ?></option>
                  <?php } ?>
                </select>
              </div>
            <?php } ?>
            <div class="col-md-6">
              <label class="form-label" for="be-access"><?php echo __('Conditions governing access'); ?></label>
              <textarea class="form-control" id="be-access" name="accessConditions" rows="3"><?php echo esc_entities($val('accessConditions')); ?></textarea>
              <div class="form-text"><?php echo __('Replaces the current text.'); ?></div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="be-repro"><?php echo __('Conditions governing reproduction'); ?></label>
              <textarea class="form-control" id="be-repro" name="reproductionConditions" rows="3"><?php echo esc_entities($val('reproductionConditions')); ?></textarea>
              <div class="form-text"><?php echo __('Replaces the current text.'); ?></div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="be-languages"><?php echo __('Language(s) of material'); ?></label>
              <select class="form-select" id="be-languages" name="languages[]" multiple size="6">
                <?php foreach ($labels['language'] as $code => $name) { ?>
                  <option value="<?php echo esc_entities($code); ?>" <?php echo in_array($code, $langs, true) ? 'selected' : ''; ?>><?php echo esc_entities($name); ?></option>
                <?php } ?>
              </select>
            </div>
            <div class="col-md-6">
              <span class="form-label d-block"><?php echo __('Languages chosen are'); ?></span>
              <div class="form-check"><input class="form-check-input" type="radio" name="languagesMode" id="be-lang-add" value="add" <?php echo 'replace' !== $val('languagesMode') ? 'checked' : ''; ?>><label class="form-check-label" for="be-lang-add"><?php echo __('added to the current languages'); ?></label></div>
              <div class="form-check"><input class="form-check-input" type="radio" name="languagesMode" id="be-lang-replace" value="replace" <?php echo 'replace' === $val('languagesMode') ? 'checked' : ''; ?>><label class="form-check-label" for="be-lang-replace"><?php echo __('used instead of the current languages'); ?></label></div>
            </div>
          </div>
        </div>
      </div>

      <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#be-points" aria-expanded="true"><?php echo __('Add access points and creators'); ?></button></h2>
        <div id="be-points" class="accordion-collapse collapse show">
          <div class="accordion-body row g-3">
            <p class="mb-0"><?php echo __('One name per line, exactly as it appears in the taxonomy or authority record. Nothing new is created; a name that does not match exactly one existing entry stops the preview.'); ?></p>
            <?php foreach (['subject', 'place', 'genre', 'creators'] as $key) { ?>
              <div class="col-md-3">
                <label class="form-label" for="be-<?php echo $key; ?>"><?php echo esc_entities($sf_data->getRaw('fieldLabels')[$key]); ?></label>
                <textarea class="form-control" id="be-<?php echo $key; ?>" name="<?php echo $key; ?>" rows="4"><?php echo esc_entities($val($key)); ?></textarea>
              </div>
            <?php } ?>
          </div>
        </div>
      </div>

      <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#be-date" aria-expanded="true"><?php echo __('Add a date'); ?></button></h2>
        <div id="be-date" class="accordion-collapse collapse show">
          <div class="accordion-body row g-3">
            <div class="col-md-3">
              <label class="form-label" for="be-eventType"><?php echo __('Type'); ?></label>
              <select class="form-select" id="be-eventType" name="eventTypeId">
                <?php $sel = $val('eventTypeId') ?: (string) $eventTypeDefault; ?>
                <?php foreach ($labels['eventType'] as $id => $name) { ?>
                  <option value="<?php echo (int) $id; ?>" <?php echo (string) $id === $sel ? 'selected' : ''; ?>><?php echo esc_entities($name); ?></option>
                <?php } ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label" for="be-eventDate"><?php echo __('Date (display)'); ?></label>
              <input class="form-control" id="be-eventDate" name="eventDate" value="<?php echo esc_entities($val('eventDate')); ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label" for="be-eventStart"><?php echo __('Start'); ?></label>
              <input class="form-control" id="be-eventStart" name="eventStart" placeholder="YYYY-MM-DD" value="<?php echo esc_entities($val('eventStart')); ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label" for="be-eventEnd"><?php echo __('End'); ?></label>
              <input class="form-control" id="be-eventEnd" name="eventEnd" placeholder="YYYY-MM-DD" value="<?php echo esc_entities($val('eventEnd')); ?>">
            </div>
            <div class="form-text"><?php echo __('A year or a year and month is completed to the first day for the start and the last day for the end.'); ?></div>
          </div>
        </div>
      </div>

      <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#be-rename" aria-expanded="true"><?php echo __('Rename titles'); ?></button></h2>
        <div id="be-rename" class="accordion-collapse collapse show">
          <div class="accordion-body row g-3">
            <div class="col-md-3">
              <label class="form-label" for="be-find"><?php echo __('Find'); ?></label>
              <input class="form-control" id="be-find" name="renameFind" value="<?php echo esc_entities($val('renameFind')); ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label" for="be-replace"><?php echo __('Replace with'); ?></label>
              <input class="form-control" id="be-replace" name="renameReplace" value="<?php echo esc_entities($val('renameReplace')); ?>">
              <div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="renameCi" id="be-ci" value="1" <?php echo $val('renameCi') ? 'checked' : ''; ?>><label class="form-check-label" for="be-ci"><?php echo __('Ignore case'); ?></label></div>
            </div>
            <div class="col-md-3">
              <label class="form-label" for="be-prefix"><?php echo __('Add prefix'); ?></label>
              <input class="form-control" id="be-prefix" name="renamePrefix" value="<?php echo esc_entities($val('renamePrefix')); ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label" for="be-suffix"><?php echo __('Add suffix'); ?></label>
              <input class="form-control" id="be-suffix" name="renameSuffix" value="<?php echo esc_entities($val('renameSuffix')); ?>">
            </div>
            <div class="form-text"><?php echo __('Plain text, not a pattern. Spaces count: type "Box 1 - " as a prefix to get a separator.'); ?></div>
          </div>
        </div>
      </div>
    </div>

    <ul class="actions mb-3 nav gap-2">
      <li><a class="btn atom-btn-outline-light" href="<?php echo url_for(['module' => 'clipboard', 'action' => 'view']); ?>"><?php echo __('Cancel'); ?></a></li>
      <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Preview'); ?>"></li>
    </ul>
  </form>

  <h2 class="h5"><?php echo __('Selected descriptions'); ?></h2>
  <ul>
    <?php foreach ($records as $r) { ?>
      <li><a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $r['slug']]); ?>"><?php echo esc_entities($r['title'] ?: $r['slug']); ?></a></li>
    <?php } ?>
  </ul>
  <?php } ?>
<?php end_slot(); ?>
