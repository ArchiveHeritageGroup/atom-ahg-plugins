<?php
$scan = $sf_data->getRaw('scan');
$draft = $sf_data->getRaw('draft');
$screen = $sf_data->getRaw('screen');
$autoRopa = $sf_data->getRaw('autoRopa');
$proposals = $sf_data->getRaw('proposals') ?: [];
$dpia = $sf_data->getRaw('dpia');
$slugs = $sf_data->getRaw('slugs') ?: [];
$url = url_for(['module' => 'privacyAdmin', 'action' => 'autopilot']);
$e = fn ($v) => esc_entities((string) $v);
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h1 class="h3 mb-0"><i class="fas fa-wand-magic-sparkles me-2"></i><?php echo __('Compliance autopilot') ?></h1>
    <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm"><?php echo __('Privacy dashboard') ?></a>
  </div>
  <p class="text-muted"><?php echo __('Scans the catalogue for personal data and drafts a processing activity (ROPA), retention proposals and, where the screen says one is needed, a DPIA. Every draft waits for the data protection officer. Opening this page only reads; the buttons write.') ?></p>

  <div class="row mb-3">
    <div class="col-md-4 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase"><?php echo __('Descriptions scanned') ?></div><div class="display-6"><?php echo (int) $scan['scanned'] ?></div></div></div></div>
    <div class="col-md-4 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase"><?php echo __('With personal data') ?></div><div class="display-6"><?php echo (int) $scan['records_with_pii'] ?></div></div></div></div>
    <div class="col-md-4 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase"><?php echo __('Demonstration records left out') ?></div><div class="display-6"><?php echo (int) $scan['synthetic_excluded'] ?></div><div class="small text-muted"><?php echo __('Titled [SYNTHETIC] or marked as a demonstration record') ?></div></div></div></div>
  </div>

  <div class="card mb-4">
    <div class="card-header"><h2 class="h5 mb-0"><?php echo __('1. Personal data found') ?></h2></div>
    <div class="card-body p-0 table-responsive">
      <table class="table table-sm mb-0 align-middle">
        <thead class="table-light"><tr><th><?php echo __('Category') ?></th><th class="text-end"><?php echo __('Findings') ?></th><th class="text-end"><?php echo __('Records') ?></th><th><?php echo __('Examples') ?></th></tr></thead>
        <tbody>
        <?php if (empty($scan['categories'])): ?>
          <tr><td colspan="4" class="text-muted p-3"><?php echo __('No personal data found in the scanned descriptions.') ?></td></tr>
        <?php else: foreach ($scan['categories'] as $c): ?>
          <tr>
            <td><?php echo $e($c['label']) ?></td>
            <td class="text-end"><?php echo (int) $c['count'] ?></td>
            <td class="text-end"><?php echo (int) $c['records'] ?></td>
            <td class="small"><?php foreach ($c['samples'] as $id): ?><?php if (isset($slugs[$id])): ?><a class="me-2" href="/<?php echo $e($slugs[$id]) ?>" target="_blank">#<?php echo (int) $id ?></a><?php else: ?><span class="me-2">#<?php echo (int) $id ?></span><?php endif ?><?php endforeach ?></td>
          </tr>
        <?php endforeach ?>
        <?php endif ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer small text-muted"><?php echo __('Identifiers that fail their checksum (ID and card numbers) are not counted.') ?></div>
  </div>

  <div class="card mb-4">
    <div class="card-header"><h2 class="h5 mb-0"><?php echo __('2. Processing activity (ROPA) draft') ?></h2></div>
    <div class="card-body">
      <?php if ($autoRopa): ?>
        <div class="alert alert-info py-2"><?php echo __('Already created from an earlier draft:') ?> <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'ropaView', 'id' => $autoRopa->id]) ?>"><?php echo $e($autoRopa->name) ?></a> (<?php echo $e($autoRopa->status) ?>)</div>
      <?php endif ?>
      <form method="post" action="<?php echo $url ?>">
        <?php echo \AtomFramework\Services\CsrfService::renderHiddenField() ?>
        <input type="hidden" name="form_action" value="create_ropa">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label" for="ap-name"><?php echo __('Name') ?></label><input class="form-control" id="ap-name" name="name" maxlength="255" value="<?php echo $e($draft['name']) ?>"></div>
          <div class="col-md-6"><label class="form-label" for="ap-basis"><?php echo __('Lawful basis') ?></label><input class="form-control" id="ap-basis" name="lawful_basis" maxlength="100" value="<?php echo $e($draft['lawful_basis']) ?>"></div>
          <div class="col-12"><label class="form-label" for="ap-purpose"><?php echo __('Purpose') ?></label><textarea class="form-control" id="ap-purpose" name="purpose" rows="2"><?php echo $e($draft['purpose']) ?></textarea></div>
          <div class="col-md-6"><label class="form-label" for="ap-cats"><?php echo __('Categories of personal data (one per line)') ?></label><textarea class="form-control" id="ap-cats" name="data_categories" rows="4"><?php echo $e(implode("\n", $draft['data_categories'])) ?></textarea></div>
          <div class="col-md-6"><label class="form-label" for="ap-subjects"><?php echo __('Data subjects') ?></label><textarea class="form-control" id="ap-subjects" name="data_subjects" rows="4"><?php echo $e($draft['data_subjects']) ?></textarea></div>
          <div class="col-md-6"><label class="form-label" for="ap-recipients"><?php echo __('Recipients') ?></label><input class="form-control" id="ap-recipients" name="recipients" value="<?php echo $e($draft['recipients']) ?>"></div>
          <div class="col-md-6"><label class="form-label" for="ap-retention"><?php echo __('Retention period') ?></label><input class="form-control" id="ap-retention" name="retention_period" maxlength="100" value="<?php echo $e($draft['retention_period']) ?>"></div>
          <div class="col-12"><label class="form-label" for="ap-security"><?php echo __('Security measures') ?></label><textarea class="form-control" id="ap-security" name="security_measures" rows="2"><?php echo $e($draft['security_measures']) ?></textarea></div>
        </div>
        <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
          <span class="small <?php echo $screen['high_risk'] ? 'text-danger' : 'text-muted' ?>"><?php echo $e($screen['note']) ?></span>
          <button type="submit" class="btn btn-success"><?php echo __('Create draft processing activity') ?></button>
        </div>
      </form>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h2 class="h5 mb-0"><?php echo __('3. Retention proposals') ?></h2>
      <form method="post" action="<?php echo $url ?>" class="m-0"><?php echo \AtomFramework\Services\CsrfService::renderHiddenField() ?><input type="hidden" name="form_action" value="draft_retention"><button type="submit" class="btn btn-primary btn-sm"><?php echo $proposals ? __('Redraft from a new scan') : __('Draft retention proposals') ?></button></form>
    </div>
    <div class="card-body p-0 table-responsive">
      <table class="table table-sm mb-0 align-middle">
        <thead class="table-light"><tr><th><?php echo __('Category') ?></th><th class="text-end"><?php echo __('Records') ?></th><th><?php echo __('Retention period') ?></th><th><?php echo __('Basis') ?></th><th><?php echo __('Disposal') ?></th><th><?php echo __('Status') ?></th><th></th></tr></thead>
        <tbody>
        <?php if (!$proposals): ?>
          <tr><td colspan="7" class="text-muted p-3"><?php echo __('No proposals yet. Drafting asks the AI gateway for a period, basis and disposal action per category, grounded in the category names only.') ?></td></tr>
        <?php else: foreach ($proposals as $p): ?>
          <tr>
            <td><?php echo $e($p->category_label) ?><?php if ($p->rationale): ?><div class="small text-muted"><?php echo $e($p->rationale) ?></div><?php endif ?></td>
            <td class="text-end"><?php echo (int) $p->records_affected ?></td>
            <td><?php echo $e($p->retention_period) ?></td>
            <td class="small"><?php echo $e($p->legal_basis) ?></td>
            <td><?php echo $e($p->disposal_action) ?></td>
            <td><span class="badge <?php echo 'accepted' === $p->status ? 'bg-success' : 'bg-warning text-dark' ?>"><?php echo 'accepted' === $p->status ? __('Accepted') : __('Proposed') ?></span><div class="small text-muted"><?php echo 'llm' === $p->source ? __('AI gateway') : __('Fixed wording') ?></div></td>
            <td class="text-end"><?php if ('proposed' === $p->status): ?><form method="post" action="<?php echo $url ?>" class="m-0"><?php echo \AtomFramework\Services\CsrfService::renderHiddenField() ?><input type="hidden" name="form_action" value="accept_retention"><input type="hidden" name="id" value="<?php echo (int) $p->id ?>"><button type="submit" class="btn btn-sm btn-outline-success"><?php echo __('Accept') ?></button></form><?php endif ?></td>
          </tr>
        <?php endforeach ?>
        <?php endif ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h2 class="h5 mb-0"><?php echo __('4. Data protection impact assessment') ?></h2>
      <form method="post" action="<?php echo $url ?>" class="m-0"><?php echo \AtomFramework\Services\CsrfService::renderHiddenField() ?><input type="hidden" name="form_action" value="draft_dpia"><button type="submit" class="btn btn-primary btn-sm"><?php echo __('Screen and draft') ?></button></form>
    </div>
    <div class="card-body">
      <p class="<?php echo $screen['high_risk'] ? 'text-danger' : 'text-muted' ?> mb-2"><?php echo $e($screen['note']) ?></p>
      <p class="small text-muted"><?php echo __('The screen looks for special category data, profiling or systematic monitoring, biometric or genetic data, and transfers abroad without safeguards. It decides whether a DPIA is needed; the AI gateway only words the draft.') ?></p>
      <?php if ($dpia): ?>
        <div class="border rounded p-3">
          <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
            <strong><?php echo $e($dpia->name) ?></strong>
            <span class="badge bg-secondary"><?php echo $e($dpia->status) ?></span>
          </div>
          <dl class="row small mb-2">
            <dt class="col-sm-3"><?php echo __('Description') ?></dt><dd class="col-sm-9"><?php echo $e($dpia->description) ?></dd>
            <dt class="col-sm-3"><?php echo __('Risks') ?></dt><dd class="col-sm-9"><?php echo $e($dpia->risks_to_subjects) ?></dd>
            <dt class="col-sm-3"><?php echo __('Measures') ?></dt><dd class="col-sm-9"><?php echo $e($dpia->measures_to_mitigate) ?></dd>
          </dl>
          <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'dpiaForm', 'id' => $dpia->id]) ?>"><?php echo __('Open in the DPIA register') ?></a>
            <?php if ('draft' === $dpia->status): ?><form method="post" action="<?php echo $url ?>" class="m-0"><?php echo \AtomFramework\Services\CsrfService::renderHiddenField() ?><input type="hidden" name="form_action" value="accept_dpia"><input type="hidden" name="id" value="<?php echo (int) $dpia->id ?>"><button type="submit" class="btn btn-sm btn-success"><?php echo __('Accept and move to review') ?></button></form><?php endif ?>
          </div>
        </div>
      <?php endif ?>
    </div>
  </div>
</div>
