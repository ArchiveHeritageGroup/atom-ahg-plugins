<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Copy Cataloguing (Z39.50)'); ?></h1>
<?php end_slot(); ?>

<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="alert alert-success"><?php echo $sf_user->getFlash('notice'); ?></div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<?php if (empty($sf_data->getRaw('targets'))): ?>
  <div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <?php echo __('No Z39.50 targets configured.'); ?>
    <a href="<?php echo url_for(['module' => 'z3950', 'action' => 'index']); ?>"><?php echo __('Manage targets'); ?></a>.
  </div>
<?php else: ?>

  <div class="card mb-4">
    <div class="card-body">
      <form method="get" action="<?php echo url_for(['module' => 'copyCataloguing', 'action' => 'index']); ?>">
        <div class="row g-3 align-items-end">
          <div class="col-md-4">
            <label class="form-label"><?php echo __('Z39.50 Target'); ?></label>
            <select name="target_id" class="form-select" required>
              <option value=""><?php echo __('— select target —'); ?></option>
              <?php foreach ($sf_data->getRaw('targets') as $t): ?>
                <?php $tid = is_array($t) ? ($t['id'] ?? 0) : $t->id; $tname = is_array($t) ? ($t['name'] ?? '') : $t->name; ?>
                <option value="<?php echo (int) $tid; ?>" <?php echo $targetId === (int) $tid ? 'selected' : ''; ?>><?php echo esc_entities($tname); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label"><?php echo __('Search (CCL, e.g. ti=appraisal or isbn=9780853239123)'); ?></label>
            <input type="text" name="query" class="form-control" minlength="2" maxlength="500" required value="<?php echo esc_entities($query); ?>">
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i><?php echo __('Search'); ?></button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <?php if (!empty($searchError)): ?>
    <div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i><?php echo esc_entities($searchError); ?></div>
  <?php elseif ($query !== '' && $targetId > 0): ?>

    <?php if (empty($sf_data->getRaw('records'))): ?>
      <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i><?php echo __('No records found for that query.'); ?></div>
    <?php else: ?>
      <p class="text-muted"><?php echo __('%1% record(s) found', ['%1%' => $recordCount]); ?></p>
      <div class="card">
        <div class="table-responsive">
          <table class="table table-striped table-hover mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Title'); ?></th>
                <th><?php echo __('Author'); ?></th>
                <th><?php echo __('ISBN/ISSN'); ?></th>
                <th><?php echo __('Publisher'); ?></th>
                <th><?php echo __('Date'); ?></th>
                <th class="text-end"><?php echo __('Action'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($sf_data->getRaw('records') as $r): ?>
                <tr>
                  <td><?php echo esc_entities($r['title']); ?></td>
                  <td><?php echo esc_entities($r['author']); ?></td>
                  <td><?php echo esc_entities($r['isbn'] ?? $r['issn'] ?? ''); ?></td>
                  <td><?php echo esc_entities($r['publisher'] ?? ''); ?></td>
                  <td><?php echo esc_entities($r['pub_date'] ?? ''); ?></td>
                  <td class="text-end">
                    <form method="post" action="<?php echo url_for(['module' => 'copyCataloguing', 'action' => 'import']); ?>">
                      <input type="hidden" name="marc_content" value="<?php echo esc_entities($r['marc_content']); ?>">
                      <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-download me-1"></i><?php echo __('Import'); ?></button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

  <?php endif; ?>
<?php endif; ?>
