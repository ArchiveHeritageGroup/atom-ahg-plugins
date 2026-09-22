<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo render_title($title); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php // The layout already shows the 'notice' flash; showing it here too printed it twice. ?>
  <?php if ($error) { ?>
    <div class="alert alert-danger" role="alert"><?php echo esc_entities($error); ?></div>
  <?php } ?>

  <form method="post" action="<?php echo url_for(['module' => 'search', 'action' => 'globalReplace']); ?>">
    <input type="hidden" name="_csrf_token" value="<?php echo $form->getCSRFToken(); ?>">

    <div class="accordion mb-3">
      <div class="accordion-item">
        <h2 class="accordion-header"><span class="accordion-button"><?php echo __('Find and replace'); ?></span></h2>
        <div class="accordion-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="column"><?php echo __('Field'); ?></label>
              <select class="form-select" id="column" name="column">
                <?php foreach ($columns as $key => $def) { ?>
                  <option value="<?php echo esc_entities($key); ?>"<?php echo $key === $column ? ' selected' : ''; ?>><?php echo esc_entities($def['label']); ?></option>
                <?php } ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="collection"><?php echo __('Limit to top-level description'); ?></label>
              <select class="form-select" id="collection" name="collection">
                <option value="0"><?php echo __('All descriptions'); ?></option>
                <?php foreach ($collections as $id => $label) { ?>
                  <option value="<?php echo (int) $id; ?>"<?php echo (int) $id === $collection ? ' selected' : ''; ?>><?php echo esc_entities($label); ?></option>
                <?php } ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="pattern"><?php echo __('Find'); ?></label>
              <input class="form-control" type="text" id="pattern" name="pattern" value="<?php echo esc_entities($pattern); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="replacement"><?php echo __('Replace with'); ?></label>
              <input class="form-control" type="text" id="replacement" name="replacement" value="<?php echo esc_entities($replacement); ?>">
            </div>
            <div class="col-12">
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="caseSensitive" name="caseSensitive" value="1"<?php echo $caseSensitive ? ' checked' : ''; ?>>
                <label class="form-check-label" for="caseSensitive"><?php echo __('Case sensitive'); ?></label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="allowRegex" name="allowRegex" value="1"<?php echo $allowRegex ? ' checked' : ''; ?>>
                <label class="form-check-label" for="allowRegex"><?php echo __('Regular expression'); ?></label>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <?php if ($sf_request->isMethod('post') && !$error) { ?>
      <?php if (0 === count($matches)) { // escaped arrays are objects, never falsy ?>
        <div class="alert alert-info" role="alert"><?php echo __('No descriptions contain that text in the chosen field.'); ?></div>
      <?php } else { ?>
        <h2 class="h5"><?php echo __('%1% description(s) will change', ['%1%' => count($matches)]); ?></h2>
        <?php if ($tooMany) { ?>
          <div class="alert alert-warning" role="alert"><?php echo __('That is more than %1% records. Narrow the search, or limit it to one top-level description.', ['%1%' => SearchGlobalReplaceAction::MAX_RECORDS]); ?></div>
        <?php } ?>
        <div class="table-responsive mb-3">
          <table class="table table-bordered table-sm">
            <thead><tr><th><?php echo __('Description'); ?></th><th><?php echo __('Before'); ?></th><th><?php echo __('After'); ?></th></tr></thead>
            <tbody>
              <?php foreach (array_slice($sf_data->getRaw('matches'), 0, 100) as $m) { // raw: every cell below is escaped explicitly ?>
                <tr>
                  <td><?php echo $m['slug'] ? link_to(esc_entities($m['title'] ?: $m['slug']), '@slug?slug='.$m['slug']) : esc_entities((string) $m['title']); ?></td>
                  <td><?php echo esc_entities(mb_strimwidth($m['before'], 0, 160, '...')); ?></td>
                  <td><?php echo esc_entities(mb_strimwidth($m['after'], 0, 160, '...')); ?></td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
        <?php if (!$tooMany) { ?>
          <div class="alert alert-warning" role="alert"><?php echo __('This permanently changes the descriptions listed above. It cannot be undone.'); ?></div>
        <?php } ?>
      <?php } ?>
    <?php } ?>

    <section class="actions mb-3">
      <button class="btn atom-btn-outline-light" type="submit" name="preview" value="1"><?php echo __('Preview'); ?></button>
      <?php if ($sf_request->isMethod('post') && !$error && count($matches) > 0 && !$tooMany) { ?>
        <button class="btn atom-btn-outline-danger" type="submit" name="confirm" value="1"><?php echo __('Replace in %1% description(s)', ['%1%' => count($matches)]); ?></button>
      <?php } ?>
    </section>
  </form>

<?php end_slot(); ?>
