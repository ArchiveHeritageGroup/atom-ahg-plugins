<?php
$vitalRecords = $sf_data->getRaw('vitalRecords') ?: [];
$overdueReviews = $sf_data->getRaw('overdueReviews') ?: [];
$declarations = $sf_data->getRaw('declarations') ?: [];
$certificates = $sf_data->getRaw('certificates') ?: [];
$retentionEvents = $sf_data->getRaw('retentionEvents') ?: [];
$eventTypes = $sf_data->getRaw('eventTypes') ?: [];
$recordsUrl = url_for(['module' => 'integrity', 'action' => 'records']);
?>

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-folder-open me-2"></i><?php echo __('Records Management'); ?></h1>
    <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('Dashboard'); ?>
    </a>
  </div>

  <!-- Vital records -->
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="fas fa-star me-2"></i><?php echo __('Vital Records'); ?> <span class="badge bg-secondary"><?php echo count($vitalRecords); ?></span></h5>
      <?php if (!empty($overdueReviews)): ?><span class="badge bg-danger"><?php echo count($overdueReviews); ?> <?php echo __('overdue review'); ?></span><?php endif; ?>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <thead><tr><th>IO</th><th><?php echo __('Title'); ?></th><th><?php echo __('Reason'); ?></th><th><?php echo __('Next review'); ?></th><th></th></tr></thead>
        <tbody>
          <?php if (empty($vitalRecords)): ?>
            <tr><td colspan="5" class="text-muted p-3"><?php echo __('No vital records flagged.'); ?></td></tr>
          <?php else: foreach ($vitalRecords as $vr): ?>
            <tr>
              <td><?php echo (int) $vr->information_object_id; ?></td>
              <td><?php echo htmlspecialchars((string) ($vr->record_title ?? '')); ?></td>
              <td><?php echo htmlspecialchars((string) ($vr->reason ?? '')); ?></td>
              <td><?php echo htmlspecialchars((string) ($vr->next_review_date ?? '')); ?></td>
              <td class="text-end">
                <form method="post" action="<?php echo $recordsUrl; ?>" class="d-inline">
                  <input type="hidden" name="vital_id" value="<?php echo (int) $vr->id; ?>">
                  <button class="btn btn-outline-success btn-sm" name="form_action" value="review_vital"><?php echo __('Mark reviewed'); ?></button>
                </form>
                <form method="post" action="<?php echo $recordsUrl; ?>" class="d-inline">
                  <input type="hidden" name="information_object_id" value="<?php echo (int) $vr->information_object_id; ?>">
                  <button class="btn btn-outline-danger btn-sm" name="form_action" value="unflag_vital"><?php echo __('Unflag'); ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
      <div class="p-3 border-top">
        <details>
          <summary class="btn btn-sm btn-outline-primary"><?php echo __('Flag a record as vital'); ?></summary>
          <form method="post" action="<?php echo $recordsUrl; ?>" class="row g-2 mt-2">
            <div class="col-md-3"><label class="form-label"><?php echo __('Information object ID'); ?></label><input type="number" name="information_object_id" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label"><?php echo __('Reason'); ?></label><input type="text" name="reason" class="form-control"></div>
            <div class="col-md-3"><label class="form-label"><?php echo __('Review cycle (days)'); ?></label><input type="number" name="review_cycle_days" class="form-control" value="365"></div>
            <div class="col-12"><button class="btn btn-primary btn-sm" name="form_action" value="flag_vital"><?php echo __('Flag vital'); ?></button></div>
          </form>
        </details>
      </div>
    </div>
  </div>

  <!-- Record declarations -->
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0"><i class="fas fa-gavel me-2"></i><?php echo __('Record Declarations'); ?> <span class="badge bg-secondary"><?php echo count($declarations); ?></span></h5></div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <thead><tr><th>IO</th><th><?php echo __('Title'); ?></th><th><?php echo __('Status'); ?></th><th><?php echo __('Declared'); ?></th><th></th></tr></thead>
        <tbody>
          <?php if (empty($declarations)): ?>
            <tr><td colspan="5" class="text-muted p-3"><?php echo __('No declarations.'); ?></td></tr>
          <?php else: foreach ($declarations as $d): ?>
            <tr>
              <td><?php echo (int) $d->information_object_id; ?></td>
              <td><?php echo htmlspecialchars((string) ($d->record_title ?? '')); ?></td>
              <td><span class="badge bg-<?php echo 'declared' === $d->status ? 'success' : ('pending_approval' === $d->status ? 'warning' : 'secondary'); ?>"><?php echo htmlspecialchars((string) $d->status); ?></span></td>
              <td><?php echo htmlspecialchars((string) ($d->declared_at ?? '')); ?></td>
              <td class="text-end">
                <?php if ('pending_approval' === $d->status): ?>
                <form method="post" action="<?php echo $recordsUrl; ?>" class="d-inline">
                  <input type="hidden" name="declaration_id" value="<?php echo (int) $d->id; ?>">
                  <button class="btn btn-outline-success btn-sm" name="form_action" value="approve_declaration"><?php echo __('Approve'); ?></button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
      <div class="p-3 border-top">
        <details>
          <summary class="btn btn-sm btn-outline-primary"><?php echo __('Declare a record'); ?></summary>
          <form method="post" action="<?php echo $recordsUrl; ?>" class="row g-2 mt-2">
            <div class="col-md-3"><label class="form-label"><?php echo __('Information object ID'); ?></label><input type="number" name="information_object_id" class="form-control" required></div>
            <div class="col-md-9"><label class="form-label"><?php echo __('Notes'); ?></label><input type="text" name="notes" class="form-control"></div>
            <div class="col-12"><button class="btn btn-primary btn-sm" name="form_action" value="declare_record"><?php echo __('Declare (pending approval)'); ?></button></div>
          </form>
        </details>
      </div>
    </div>
  </div>

  <!-- Destruction certificates -->
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0"><i class="fas fa-certificate me-2"></i><?php echo __('Destruction Certificates'); ?> <span class="badge bg-secondary"><?php echo count($certificates); ?></span></h5></div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <thead><tr><th><?php echo __('Number'); ?></th><th><?php echo __('Title'); ?></th><th><?php echo __('Date'); ?></th><th><?php echo __('Method'); ?></th><th><?php echo __('Authorised by'); ?></th></tr></thead>
        <tbody>
          <?php if (empty($certificates)): ?>
            <tr><td colspan="5" class="text-muted p-3"><?php echo __('No certificates issued.'); ?></td></tr>
          <?php else: foreach ($certificates as $c): ?>
            <tr>
              <td><?php echo htmlspecialchars((string) $c->certificate_number); ?></td>
              <td><?php echo htmlspecialchars((string) ($c->record_title ?? '')); ?></td>
              <td><?php echo htmlspecialchars((string) ($c->destruction_date ?? '')); ?></td>
              <td><?php echo htmlspecialchars((string) ($c->destruction_method ?? '')); ?></td>
              <td><?php echo (int) ($c->authorized_by ?? 0); ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
      <div class="p-3 border-top">
        <details>
          <summary class="btn btn-sm btn-outline-primary"><?php echo __('Issue a destruction certificate'); ?></summary>
          <form method="post" action="<?php echo $recordsUrl; ?>" class="row g-2 mt-2">
            <div class="col-md-3"><label class="form-label"><?php echo __('Information object ID'); ?></label><input type="number" name="information_object_id" class="form-control"></div>
            <div class="col-md-3"><label class="form-label"><?php echo __('Destruction date'); ?></label><input type="date" name="destruction_date" class="form-control" value="<?php echo date('Y-m-d'); ?>"></div>
            <div class="col-md-3"><label class="form-label"><?php echo __('Method'); ?></label>
              <select name="destruction_method" class="form-select">
                <?php foreach (['shredding', 'incineration', 'secure_wipe', 'pulping', 'other'] as $m): ?><option value="<?php echo $m; ?>"><?php echo ucfirst(str_replace('_', ' ', $m)); ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3"><label class="form-label"><?php echo __('Witness'); ?></label><input type="text" name="witness" class="form-control"></div>
            <div class="col-12"><button class="btn btn-primary btn-sm" name="form_action" value="generate_certificate"><?php echo __('Issue certificate'); ?></button></div>
          </form>
        </details>
      </div>
    </div>
  </div>

  <!-- Retention trigger events -->
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0"><i class="fas fa-bolt me-2"></i><?php echo __('Retention Trigger Events'); ?> <span class="badge bg-secondary"><?php echo count($retentionEvents); ?></span></h5></div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <thead><tr><th>IO</th><th><?php echo __('Title'); ?></th><th><?php echo __('Event'); ?></th><th><?php echo __('Date'); ?></th><th><?php echo __('Notes'); ?></th></tr></thead>
        <tbody>
          <?php if (empty($retentionEvents)): ?>
            <tr><td colspan="5" class="text-muted p-3"><?php echo __('No retention events recorded.'); ?></td></tr>
          <?php else: foreach ($retentionEvents as $e): ?>
            <tr>
              <td><?php echo (int) $e->information_object_id; ?></td>
              <td><?php echo htmlspecialchars((string) ($e->record_title ?? '')); ?></td>
              <td><?php echo htmlspecialchars((string) $e->event_type); ?></td>
              <td><?php echo htmlspecialchars((string) ($e->event_date ?? '')); ?></td>
              <td><?php echo htmlspecialchars((string) ($e->notes ?? '')); ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
      <div class="p-3 border-top">
        <details>
          <summary class="btn btn-sm btn-outline-primary"><?php echo __('Fire a retention event'); ?></summary>
          <form method="post" action="<?php echo $recordsUrl; ?>" class="row g-2 mt-2">
            <div class="col-md-3"><label class="form-label"><?php echo __('Information object ID'); ?></label><input type="number" name="information_object_id" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label"><?php echo __('Event type'); ?></label>
              <select name="event_type" class="form-select">
                <?php foreach ($eventTypes as $t): ?><option value="<?php echo htmlspecialchars($t); ?>"><?php echo ucfirst(str_replace('_', ' ', $t)); ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3"><label class="form-label"><?php echo __('Event date'); ?></label><input type="date" name="event_date" class="form-control" value="<?php echo date('Y-m-d'); ?>"></div>
            <div class="col-md-3"><label class="form-label"><?php echo __('Notes'); ?></label><input type="text" name="notes" class="form-control"></div>
            <div class="col-12"><button class="btn btn-primary btn-sm" name="form_action" value="fire_event"><?php echo __('Fire event'); ?></button></div>
          </form>
        </details>
      </div>
    </div>
  </div>
</main>
