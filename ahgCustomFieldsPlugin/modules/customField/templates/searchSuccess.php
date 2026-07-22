<?php decorate_with('layout_1col') ?>

<?php
// AtoM runs with escaping_strategy on, so action variables arrive wrapped in
// sfOutputEscaper decorators - arrays become sfOutputEscaperArrayDecorator and
// array_map()/is_array() fail on them. Unwrap the ones used as real PHP values.
$fields         = $sf_data->getRaw('fields');
$service        = $sf_data->getRaw('service');
$termTaxonomies = $sf_data->getRaw('termTaxonomies');
$filters        = (array) $sf_data->getRaw('filters');
$termIds        = array_map('strval', (array) $sf_data->getRaw('termIds'));
$keywords       = (string) $sf_data->getRaw('keywords');
$results        = $sf_data->getRaw('results');
$total          = (int) $sf_data->getRaw('total');
$submitted      = (bool) $sf_data->getRaw('submitted');
?>

<?php slot('title') ?>
  <h1><?php echo __('Find search') ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>

<p class="text-muted">
  <?php echo __('Filter records on the values recorded in Additional Fields. Terms such as phase or context type are inherited, so a filter set on a context also returns the finds within it.') ?>
</p>

<form method="get" action="<?php echo url_for(['module' => 'customField', 'action' => 'search']) ?>" class="mb-4">

  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label class="form-label" for="cf-q"><?php echo __('Keywords') ?></label>
      <input type="text" class="form-control" id="cf-q" name="q"
             value="<?php echo htmlspecialchars((string) $keywords, ENT_QUOTES, 'UTF-8') ?>"
             placeholder="<?php echo __('Title, identifier or extent') ?>">
    </div>

    <?php foreach ($termTaxonomies as $tax): ?>
      <?php
        $terms = \Illuminate\Database\Capsule\Manager::table('term as t')
            ->join('term_i18n as ti', function ($j) { $j->on('ti.id', '=', 't.id')->where('ti.culture', '=', 'en'); })
            ->where('t.taxonomy_id', $tax->id)
            ->orderBy('ti.name')->select('t.id', 'ti.name')->get();
        if ($terms->isEmpty()) { continue; }
      ?>
      <div class="col-md-3">
        <label class="form-label" for="term-<?php echo (int) $tax->id ?>"><?php echo htmlspecialchars($tax->name, ENT_QUOTES, 'UTF-8') ?></label>
        <select class="form-select" id="term-<?php echo (int) $tax->id ?>" name="term[<?php echo (int) $tax->id ?>]">
          <option value=""><?php echo __('Any') ?></option>
          <?php foreach ($terms as $t): ?>
            <option value="<?php echo (int) $t->id ?>"<?php echo in_array((string) $t->id, $termIds, true) ? ' selected' : '' ?>>
              <?php echo htmlspecialchars($t->name, ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach ?>
        </select>
      </div>
    <?php endforeach ?>
  </div>

  <?php // Filter controls are generated from the field definitions, so a new
        // field added in the admin UI appears here with no code change. ?>
  <?php $group = null ?>
  <div class="row g-3">
    <?php foreach ($fields as $f): ?>
      <?php if ($group !== $f->field_group): ?>
        <?php $group = $f->field_group ?>
        <div class="col-12"><h6 class="text-muted mt-2 mb-0"><?php echo htmlspecialchars((string) $group, ENT_QUOTES, 'UTF-8') ?></h6></div>
      <?php endif ?>

      <?php if ('number' === $f->field_type): ?>
        <div class="col-md-3">
          <label class="form-label"><?php echo htmlspecialchars($f->field_label, ENT_QUOTES, 'UTF-8') ?></label>
          <div class="input-group">
            <span class="input-group-text"><?php echo __('min') ?></span>
            <input type="number" step="any" class="form-control" name="cf[<?php echo $f->field_key ?>_min]"
                   value="<?php echo htmlspecialchars((string) ($filters[$f->field_key . '_min'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <span class="input-group-text"><?php echo __('max') ?></span>
            <input type="number" step="any" class="form-control" name="cf[<?php echo $f->field_key ?>_max]"
                   value="<?php echo htmlspecialchars((string) ($filters[$f->field_key . '_max'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
          </div>
        </div>
      <?php elseif ('dropdown' === $f->field_type): ?>
        <div class="col-md-3">
          <label class="form-label" for="cf-<?php echo $f->field_key ?>"><?php echo htmlspecialchars($f->field_label, ENT_QUOTES, 'UTF-8') ?></label>
          <select class="form-select" id="cf-<?php echo $f->field_key ?>" name="cf[<?php echo $f->field_key ?>]">
            <option value=""><?php echo __('Any') ?></option>
            <?php foreach ($service->getDropdownOptions($f->dropdown_taxonomy) as $o): ?>
              <option value="<?php echo htmlspecialchars($o->code, ENT_QUOTES, 'UTF-8') ?>"<?php echo (($filters[$f->field_key] ?? '') === $o->code) ? ' selected' : '' ?>>
                <?php echo htmlspecialchars($o->label, ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach ?>
          </select>
        </div>
      <?php else: ?>
        <div class="col-md-3">
          <label class="form-label" for="cf-<?php echo $f->field_key ?>"><?php echo htmlspecialchars($f->field_label, ENT_QUOTES, 'UTF-8') ?></label>
          <input type="text" class="form-control" id="cf-<?php echo $f->field_key ?>" name="cf[<?php echo $f->field_key ?>]"
                 value="<?php echo htmlspecialchars((string) ($filters[$f->field_key] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
      <?php endif ?>
    <?php endforeach ?>
  </div>

  <div class="mt-3">
    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> <?php echo __('Search') ?></button>
    <a class="btn btn-outline-secondary" href="<?php echo url_for(['module' => 'customField', 'action' => 'search']) ?>"><?php echo __('Clear') ?></a>
  </div>
</form>

<?php if ($submitted): ?>
  <hr>
  <p><strong><?php echo $total ?></strong> <?php echo __('result(s)') ?></p>

  <?php if (empty($results)): ?>
    <div class="alert alert-light border"><?php echo __('No records match these filters.') ?></div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle">
        <thead>
          <tr>
            <th><?php echo __('Identifier') ?></th>
            <th><?php echo __('Title') ?></th>
            <th><?php echo __('Level') ?></th>
            <th><?php echo __('Extent and medium') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($results as $r): ?>
            <tr>
              <td class="text-nowrap"><?php echo htmlspecialchars((string) $r->identifier, ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                <?php if ($r->slug): ?>
                  <a href="<?php echo url_for('/' . $r->slug) ?>"><?php echo htmlspecialchars((string) $r->title, ENT_QUOTES, 'UTF-8') ?></a>
                <?php else: ?>
                  <?php echo htmlspecialchars((string) $r->title, ENT_QUOTES, 'UTF-8') ?>
                <?php endif ?>
              </td>
              <td class="text-nowrap"><?php echo htmlspecialchars((string) $r->level, ENT_QUOTES, 'UTF-8') ?></td>
              <td class="small text-muted"><?php echo htmlspecialchars(mb_substr((string) $r->extent_and_medium, 0, 120), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  <?php endif ?>
<?php endif ?>

<?php end_slot() ?>
