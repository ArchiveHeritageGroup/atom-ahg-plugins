<?php decorate_with('layout_1col') ?>
<?php slot('title') ?>
  <h1><?php echo __('Artwork requests') ?> <span class="badge bg-secondary"><?php echo count($pending) ?></span></h1>
<?php end_slot() ?>

<p>
  <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for(['module' => 'artworkRequest', 'action' => 'placements']) ?>">
    <?php echo __('What is out on campus') ?>
  </a>
</p>

<?php if (!$pending): ?>
  <div class="alert alert-info"><?php echo __('Nothing waiting.') ?></div>
<?php endif ?>

<?php foreach ($pending as $r): ?>
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between">
      <span>
        <strong><?php echo esc_specialchars($r->request_number) ?></strong>
        - <?php echo esc_specialchars($r->requester_name) ?>
        <?php if ($r->department): ?><span class="text-muted">(<?php echo esc_specialchars($r->department) ?>)</span><?php endif ?>
      </span>
      <span class="text-muted"><?php echo esc_specialchars($r->requested_from) ?> to <?php echo esc_specialchars($r->requested_to) ?></span>
    </div>
    <div class="card-body">
      <dl class="row mb-3">
        <dt class="col-sm-2"><?php echo __('Placement') ?></dt>
        <dd class="col-sm-10">
          <?php echo esc_specialchars(trim($r->placement_building.' '.$r->placement_floor.' '.$r->placement_room)) ?>
          <?php if ($r->placement_occupant): ?> - <?php echo esc_specialchars($r->placement_occupant) ?><?php endif ?>
        </dd>
        <dt class="col-sm-2"><?php echo __('Purpose') ?></dt>
        <dd class="col-sm-10"><?php echo esc_specialchars($r->purpose) ?></dd>
        <?php if ($r->justification): ?>
          <dt class="col-sm-2"><?php echo __('Justification') ?></dt>
          <dd class="col-sm-10"><?php echo esc_specialchars($r->justification) ?></dd>
        <?php endif ?>
      </dl>

      <form method="post">
        <input type="hidden" name="_ahg_csrf_token" value="<?php echo sfConfig::get('csrf_token', '') ?>">
        <input type="hidden" name="request_id" value="<?php echo (int) $r->id ?>">

        <table class="table table-sm align-middle">
          <thead><tr>
            <th><?php echo __('Work') ?></th>
            <th><?php echo __('Clashes') ?></th>
            <th class="text-end"><?php echo __('Decision') ?></th>
          </tr></thead>
          <tbody>
          <?php foreach ($works[$r->id] as $w): ?>
            <tr>
              <td>
                <?php echo esc_specialchars($w->object_title ?: '#'.$w->information_object_id) ?>
                <?php if ($w->object_identifier): ?>
                  <span class="text-muted small"><?php echo esc_specialchars($w->object_identifier) ?></span>
                <?php endif ?>
              </td>
              <td class="small">
                <?php if ($w->conflict_note): ?>
                  <span class="text-warning"><?php echo esc_specialchars($w->conflict_note) ?></span>
                <?php else: ?>
                  <span class="text-success"><?php echo __('none') ?></span>
                <?php endif ?>
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm" role="group">
                  <input type="radio" class="btn-check" name="decision[<?php echo (int) $w->id ?>]" id="a<?php echo (int) $w->id ?>" value="approved" checked>
                  <label class="btn btn-outline-success" for="a<?php echo (int) $w->id ?>"><?php echo __('Approve') ?></label>
                  <input type="radio" class="btn-check" name="decision[<?php echo (int) $w->id ?>]" id="d<?php echo (int) $w->id ?>" value="declined">
                  <label class="btn btn-outline-danger" for="d<?php echo (int) $w->id ?>"><?php echo __('Decline') ?></label>
                </div>
              </td>
            </tr>
          <?php endforeach ?>
          </tbody>
        </table>

        <div class="row g-2 align-items-end">
          <div class="col-md-7">
            <label class="form-label" for="notes<?php echo (int) $r->id ?>"><?php echo __('Notes') ?></label>
            <textarea class="form-control" id="notes<?php echo (int) $r->id ?>" name="review_notes" rows="2"></textarea>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="chan<?php echo (int) $r->id ?>"><?php echo __('Decided') ?></label>
            <select class="form-select" id="chan<?php echo (int) $r->id ?>" name="decision_channel">
              <option value="system"><?php echo __('Here') ?></option>
              <option value="offline"><?php echo __('In conversation') ?></option>
            </select>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100"><?php echo __('Record') ?></button>
          </div>
        </div>
      </form>
    </div>
  </div>
<?php endforeach ?>
