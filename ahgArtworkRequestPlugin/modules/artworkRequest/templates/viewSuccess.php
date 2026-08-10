<?php decorate_with('layout_1col') ?>
<?php slot('title') ?>
  <h1><?php echo esc_specialchars($request_row->request_number) ?>
    <span class="badge bg-secondary"><?php echo esc_specialchars($request_row->status) ?></span></h1>
<?php end_slot() ?>

<div class="card mb-3">
  <div class="card-body">
    <dl class="row mb-0">
      <dt class="col-sm-3"><?php echo __('Requested by') ?></dt>
      <dd class="col-sm-9"><?php echo esc_specialchars($request_row->requester_name) ?>
        <?php if ($request_row->department): ?>(<?php echo esc_specialchars($request_row->department) ?>)<?php endif ?></dd>
      <dt class="col-sm-3"><?php echo __('Period') ?></dt>
      <dd class="col-sm-9"><?php echo esc_specialchars($request_row->requested_from) ?> to <?php echo esc_specialchars($request_row->requested_to) ?></dd>
      <dt class="col-sm-3"><?php echo __('Placement') ?></dt>
      <dd class="col-sm-9">
        <?php echo esc_specialchars(trim($request_row->placement_building.' '.$request_row->placement_floor.' '.$request_row->placement_room)) ?>
        <?php if ($request_row->placement_occupant): ?> - <?php echo esc_specialchars($request_row->placement_occupant) ?><?php endif ?>
      </dd>
      <dt class="col-sm-3"><?php echo __('Purpose') ?></dt>
      <dd class="col-sm-9"><?php echo esc_specialchars($request_row->purpose) ?></dd>
      <?php if ($request_row->justification): ?>
        <dt class="col-sm-3"><?php echo __('Justification') ?></dt>
        <dd class="col-sm-9"><?php echo esc_specialchars($request_row->justification) ?></dd>
      <?php endif ?>
      <?php if ($request_row->reviewed_at): ?>
        <dt class="col-sm-3"><?php echo __('Decided') ?></dt>
        <dd class="col-sm-9">
          <?php echo esc_specialchars($request_row->reviewed_at) ?>
          <?php if ('offline' === $request_row->decision_channel): ?>
            <span class="text-muted">(<?php echo __('agreed in conversation, recorded here') ?>)</span>
          <?php endif ?>
          <?php if ($request_row->review_notes): ?>
            <div><?php echo esc_specialchars($request_row->review_notes) ?></div>
          <?php endif ?>
        </dd>
      <?php endif ?>
    </dl>
  </div>
</div>

<div class="card mb-3">
  <div class="card-header"><?php echo __('Works') ?></div>
  <table class="table table-sm mb-0">
    <tbody>
    <?php foreach ($works as $w): ?>
      <tr>
        <td><?php echo esc_specialchars($w->object_title ?: '#'.$w->information_object_id) ?></td>
        <td><span class="badge bg-secondary"><?php echo esc_specialchars($w->status) ?></span></td>
        <td class="small text-warning"><?php echo esc_specialchars($w->conflict_note ?: '') ?></td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table>
</div>

<?php if ($canReview && 'approved' === $request_row->status && !$request_row->loan_id): ?>
  <div class="card mb-3">
    <div class="card-body">
      <a class="btn btn-primary" href="<?php echo url_for(['module' => 'artworkRequest', 'action' => 'createLoan', 'id' => $request_row->id]) ?>">
        <?php echo __('Create loan record') ?>
      </a>
      <div class="form-text">
        <?php echo __('Hands the approved works to the loan register, where condition at issue and at return are recorded. Approving decides whether a work may hang somewhere; this is the moment it moves.') ?>
      </div>
    </div>
  </div>
<?php elseif ($request_row->loan_id): ?>
  <div class="alert alert-success"><?php echo __('Loan record created.') ?></div>
<?php endif ?>

<div class="card">
  <div class="card-header"><?php echo __('History') ?></div>
  <table class="table table-sm mb-0">
    <tbody>
    <?php foreach ($log as $l): ?>
      <tr>
        <td class="text-muted small"><?php echo esc_specialchars($l->created_at) ?></td>
        <td><?php echo esc_specialchars($l->event) ?></td>
        <td><?php echo esc_specialchars($l->actor_name ?: '') ?></td>
        <td class="small"><?php echo esc_specialchars($l->detail ?: '') ?></td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table>
</div>
