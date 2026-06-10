<?php
/* AI Cataloguer (#149) — full-record draft review + apply. */
$hasDraft = is_array($draft) && !empty($draft);
$genUrl = url_for(['module' => 'ai', 'action' => 'catalog', 'id' => $objectId]);
$applyUrl = url_for(['module' => 'ai', 'action' => 'catalogApply', 'id' => $objectId]);
$entityBuckets = ['persons' => 'People', 'organizations' => 'Organisations', 'places' => 'Places', 'dates' => 'Dates'];
?>
<div class="container-fluid px-4 py-3 ai-cataloguer">
  <div class="d-flex flex-wrap align-items-baseline mb-3 gap-2">
    <h1 class="h3 mb-0 flex-grow-1"><i class="fas fa-wand-magic-sparkles me-2"></i><?php echo __('AI Cataloguer') ?></h1>
    <?php if (!empty($recordSlug)): ?>
      <a href="/<?php echo esc_entities($recordSlug) ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i><?php echo __('Back to record') ?></a>
    <?php endif ?>
  </div>
  <p class="text-muted mb-3">
    <?php echo __('Drafting for') ?>: <strong><?php echo esc_entities($recordTitle) ?></strong>
    <?php if (!empty($model)): ?><span class="badge bg-light text-dark ms-2"><?php echo esc_entities($model) ?></span><?php endif ?>
  </p>

  <?php if ($sf_user->hasFlash('notice')): ?><div class="alert alert-success alert-dismissible fade show"><?php echo $sf_user->getFlash('notice') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif ?>
  <?php if ($sf_user->hasFlash('error')): ?><div class="alert alert-danger alert-dismissible fade show"><?php echo $sf_user->getFlash('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif ?>
  <?php if (!empty($error)): ?><div class="alert alert-warning"><?php echo esc_entities($error) ?></div><?php endif ?>

  <div class="alert alert-info small">
    <i class="fas fa-circle-info me-1"></i>
    <?php echo __('The cataloguer drafts strictly from the record’s existing fields, OCR/transcribed text, embedded technical metadata and extracted entities — it does not invent facts. Review each field and apply only what you approve.') ?>
  </div>

  <div class="d-flex gap-2 mb-3">
    <form method="post" action="<?php echo $genUrl ?>" class="d-inline">
      <input type="hidden" name="generate" value="1">
      <button class="btn btn-primary"><i class="fas fa-wand-magic-sparkles me-1"></i><?php echo $hasDraft ? __('Regenerate draft') : __('Generate AI draft') ?></button>
    </form>
  </div>

  <?php if (!$hasDraft): ?>
    <div class="card"><div class="card-body text-muted"><?php echo __('No draft yet. Click “Generate AI draft” to produce one from the record’s sources.') ?></div></div>
  <?php else: ?>
    <form method="post" action="<?php echo $applyUrl ?>">
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong><?php echo __('Descriptive fields') ?></strong>
          <small class="text-muted"><?php echo __('Tick the fields to write to the record') ?></small>
        </div>
        <div class="table-responsive"><table class="table table-sm align-middle mb-0">
          <thead class="table-light"><tr>
            <th style="width:42px"></th><th style="width:160px"><?php echo __('Field') ?></th>
            <th><?php echo __('AI draft') ?></th><th class="text-muted"><?php echo __('Current value') ?></th>
          </tr></thead>
          <tbody>
          <?php foreach ($fields as $key => $label): ?>
            <?php $val = trim((string) ($draft[$key] ?? '')); if ($val === '') { continue; } ?>
            <?php $cur = trim((string) ($current[$key] ?? '')); $preCheck = ($cur === ''); ?>
            <tr>
              <td><input class="form-check-input" type="checkbox" name="apply[<?php echo $key ?>]" value="1" <?php echo $preCheck ? 'checked' : '' ?>></td>
              <td><strong><?php echo __($label) ?></strong><?php if ($cur !== ''): ?><br><span class="badge bg-warning text-dark"><?php echo __('overwrites') ?></span><?php endif ?></td>
              <td><?php echo nl2br(esc_entities($val)) ?></td>
              <td class="text-muted small"><?php echo $cur !== '' ? nl2br(esc_entities(mb_substr($cur, 0, 400))) : '<em>'.__('empty').'</em>' ?></td>
            </tr>
          <?php endforeach ?>
          </tbody>
        </table></div>
        <div class="card-footer text-end">
          <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i><?php echo __('Apply selected to record') ?></button>
        </div>
      </div>
    </form>

    <div class="row">
      <?php
      $aux = [
          __('Level of description') => $draft['level_of_description'] ?? '',
          __('Dates') => trim(($draft['date_display'] ?? '').' '.((($draft['date_start'] ?? '') || ($draft['date_end'] ?? '')) ? '('.($draft['date_start'] ?? '?').'–'.($draft['date_end'] ?? '?').')' : '')),
      ];
      ?>
      <div class="col-lg-6">
        <div class="card mb-3"><div class="card-header"><strong><?php echo __('Suggested dating & level') ?></strong></div>
          <div class="card-body small">
            <?php foreach ($aux as $label => $v): if (trim((string) $v) === '') { continue; } ?>
              <div class="mb-1"><span class="text-muted"><?php echo $label ?>:</span> <?php echo esc_entities($v) ?></div>
            <?php endforeach ?>
            <?php foreach (['languages' => __('Languages')] as $k => $label): if (empty($draft[$k])) { continue; } ?>
              <div class="mb-1"><span class="text-muted"><?php echo $label ?>:</span> <?php echo esc_entities(implode(', ', (array) $draft[$k])) ?></div>
            <?php endforeach ?>
            <p class="text-muted mt-2 mb-0"><em><?php echo __('Dates and level are shown for reference — set them on the record’s edit form.') ?></em></p>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card mb-3"><div class="card-header"><strong><?php echo __('Suggested access points') ?></strong></div>
          <div class="card-body small">
            <?php foreach (['creators' => __('Creators'), 'subjects' => __('Subjects'), 'places' => __('Places')] as $k => $label): if (empty($draft[$k])) { continue; } ?>
              <div class="mb-2"><span class="text-muted"><?php echo $label ?>:</span>
                <?php foreach ((array) $draft[$k] as $t): ?><span class="badge bg-secondary me-1"><?php echo esc_entities($t) ?></span><?php endforeach ?>
              </div>
            <?php endforeach ?>
            <?php $anyEnt = false; foreach ($entities as $vv) { if (!empty($vv)) { $anyEnt = true; } } ?>
            <?php if ($anyEnt): ?>
              <hr><div class="text-muted mb-1"><?php echo __('Already-extracted entities (review in') ?> <a href="<?php echo url_for(['module' => 'ai', 'action' => 'review']) ?>">NER</a>):</div>
              <?php foreach ($entityBuckets as $bk => $bl): if (empty($entities[$bk])) { continue; } ?>
                <div class="mb-1"><span class="text-muted"><?php echo $bl ?>:</span>
                  <?php foreach ($entities[$bk] as $t): ?><span class="badge bg-light text-dark border me-1"><?php echo esc_entities($t) ?></span><?php endforeach ?>
                </div>
              <?php endforeach ?>
            <?php endif ?>
            <p class="text-muted mt-2 mb-0"><em><?php echo __('Access points are created and linked via the NER review workflow.') ?></em></p>
          </div>
        </div>
      </div>
    </div>
  <?php endif ?>
</div>
