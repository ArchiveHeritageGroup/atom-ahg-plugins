<?php /* #145 — Email suppression list admin */ ?>
<div class="container-fluid px-4 py-3 email-suppressions">
  <div class="d-flex flex-wrap align-items-baseline mb-3 gap-2">
    <h1 class="mb-0 flex-grow-1"><i class="fas fa-envelope-circle-check me-2"></i><?php echo __('Email suppression list') ?></h1>
    <a href="<?php echo url_for(['module' => 'settings', 'action' => 'list']) ?>" class="btn btn-outline-secondary btn-sm"><?php echo __('Settings') ?></a>
  </div>

  <?php if ($sf_user->hasFlash('notice')): ?><div class="alert alert-success alert-dismissible fade show"><?php echo $sf_user->getFlash('notice') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif ?>
  <?php if ($sf_user->hasFlash('error')): ?><div class="alert alert-danger alert-dismissible fade show"><?php echo $sf_user->getFlash('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif ?>

  <p class="text-muted small">
    <?php echo __('Addresses here are blocked from outgoing mail. Bounces and spam complaints are added automatically by the provider webhook at') ?>
    <code>/email/bounce</code>.
  </p>

  <div class="row g-2 mb-3">
    <?php foreach (['total' => 'Total', 'bounce' => 'Bounces', 'complaint' => 'Complaints', 'manual' => 'Manual'] as $k => $label): ?>
      <div class="col-6 col-md-3">
        <div class="card text-center"><div class="card-body py-2">
          <div class="h4 mb-0"><?php echo (int) ($stats[$k] ?? 0) ?></div>
          <small class="text-muted"><?php echo __($label) ?></small>
        </div></div>
      </div>
    <?php endforeach ?>
  </div>

  <div class="row">
    <div class="col-lg-8">
      <form method="get" class="row g-2 mb-2">
        <div class="col"><input type="text" name="q" value="<?php echo esc_entities($search) ?>" class="form-control form-control-sm" placeholder="<?php echo __('Search address…') ?>"></div>
        <div class="col-auto">
          <select name="reason" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value=""><?php echo __('All reasons') ?></option>
            <?php foreach ($reasons as $rk => $rl): ?>
              <option value="<?php echo $rk ?>" <?php echo $reasonFilter === $rk ? 'selected' : '' ?>><?php echo __($rl) ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="col-auto"><button class="btn btn-sm btn-primary"><?php echo __('Filter') ?></button></div>
      </form>

      <div class="card"><div class="card-body p-0">
        <?php if (count($rows) === 0): ?>
          <div class="p-3 text-muted small"><?php echo __('No suppressed addresses.') ?></div>
        <?php else: ?>
          <div class="table-responsive"><table class="table table-hover table-sm mb-0 small align-middle">
            <thead class="table-light"><tr>
              <th><?php echo __('Address') ?></th><th><?php echo __('Reason') ?></th>
              <th><?php echo __('Type') ?></th><th><?php echo __('Count') ?></th>
              <th><?php echo __('Source') ?></th><th><?php echo __('Last event') ?></th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><code><?php echo esc_entities($r->email) ?></code></td>
                <td><span class="badge bg-<?php echo $r->reason === 'complaint' ? 'danger' : ($r->reason === 'manual' ? 'secondary' : 'warning text-dark') ?>"><?php echo esc_entities($r->reason) ?></span></td>
                <td><?php echo esc_entities($r->bounce_type) ?: '—' ?></td>
                <td><?php echo (int) $r->bounce_count ?></td>
                <td><small class="text-muted"><?php echo esc_entities($r->source) ?></small></td>
                <td><small class="text-muted"><?php echo esc_entities($r->last_event_at) ?></small></td>
                <td>
                  <form method="post" action="<?php echo url_for(['module' => 'emailDelivery', 'action' => 'remove']) ?>" class="d-inline" onsubmit="return confirm('<?php echo __('Remove this suppression?') ?>');">
                    <input type="hidden" name="email" value="<?php echo esc_entities($r->email) ?>">
                    <button class="btn btn-sm btn-outline-danger" title="<?php echo __('Remove') ?>"><i class="fas fa-trash"></i></button>
                  </form>
                </td>
              </tr>
            <?php endforeach ?>
            </tbody>
          </table></div>
        <?php endif ?>
      </div></div>
    </div>

    <div class="col-lg-4">
      <div class="card"><div class="card-header"><strong><?php echo __('Add manual suppression') ?></strong></div>
        <div class="card-body">
          <form method="post" action="<?php echo url_for(['module' => 'emailDelivery', 'action' => 'add']) ?>">
            <div class="mb-2"><label class="form-label small"><?php echo __('Email address') ?> <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control form-control-sm" required></div>
            <div class="mb-2"><label class="form-label small"><?php echo __('Reason') ?></label>
              <select name="reason" class="form-select form-select-sm">
                <?php foreach ($reasons as $rk => $rl): ?><option value="<?php echo $rk ?>"><?php echo __($rl) ?></option><?php endforeach ?>
              </select></div>
            <div class="mb-2"><label class="form-label small"><?php echo __('Note') ?></label>
              <input type="text" name="detail" class="form-control form-control-sm"></div>
            <button class="btn btn-sm btn-success w-100"><i class="fas fa-plus me-1"></i><?php echo __('Suppress') ?></button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
