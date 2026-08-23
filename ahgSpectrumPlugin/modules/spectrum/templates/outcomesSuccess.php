<?php decorate_with('layout_1col') ?>
<?php slot('title') ?>
  <h1><?php echo __('Procedure outcomes awaiting review') ?></h1>
<?php end_slot() ?>

<?php
// A <style> element carrying the CSP nonce, rather than an inline style
// attribute. A nonce covers elements and never attributes, so an attribute is
// dropped on any site running the enforcing header - the control would simply
// render full width with nothing to say why.
echo ahg_style_block('.ahg-outcome-note { width: 14rem; }');
?>

<p class="text-muted">
  <?php echo __('When a procedure reaches a state that produces something - a valuation approved, a work disposed of - it records what it would write and stops there. Nothing below has been posted. Accepting is what writes it.') ?>
</p>

<?php if (!$proposals): ?>
  <div class="alert alert-info"><?php echo __('Nothing is waiting.') ?></div>
<?php else: ?>
  <table class="table align-middle">
    <thead>
      <tr>
        <th><?php echo __('Record') ?></th>
        <th><?php echo __('Procedure') ?></th>
        <th><?php echo __('Proposed') ?></th>
        <th><?php echo __('Raised') ?></th>
        <th class="text-end"><?php echo __('Decision') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($proposals as $p): ?>
      <tr>
        <td><?php echo esc_specialchars($titles[$p->record_id] ?? ('#'.$p->record_id)) ?></td>
        <td>
          <span class="text-muted small"><?php echo esc_specialchars(ucwords(str_replace('_', ' ', (string) $p->procedure_type))) ?></span>
        </td>
        <td><?php echo esc_specialchars((string) $p->summary) ?></td>
        <td class="text-muted small"><?php echo esc_specialchars(substr((string) $p->proposed_at, 0, 16)) ?></td>
        <td class="text-end">
          <form method="post" class="d-inline"
                action="<?php echo url_for(['module' => 'spectrum', 'action' => 'outcomeDecide', 'id' => $p->id]) ?>">
            <input type="hidden" name="_ahg_csrf_token" value="<?php echo htmlspecialchars(class_exists('\AtomFramework\Services\CsrfService') ? \AtomFramework\Services\CsrfService::generateToken() : '', ENT_QUOTES) ?>">
            <input type="text" class="form-control form-control-sm d-inline-block ahg-outcome-note"
                   name="note" placeholder="<?php echo __('Note (optional)') ?>">
            <button class="btn btn-sm btn-success" name="decision" value="accept"
                    data-ahg-confirm="<?php echo __('Write this to the record it belongs to?') ?>">
              <?php echo __('Accept') ?>
            </button>
            <button class="btn btn-sm btn-outline-secondary" name="decision" value="reject">
              <?php echo __('Reject') ?>
            </button>
          </form>
        </td>
      </tr>
      <?php endforeach ?>
    </tbody>
  </table>
<?php endif ?>

<?php if ($decided): ?>
  <h2 class="h5 mt-4"><?php echo __('Recently decided') ?></h2>
  <table class="table table-sm">
    <thead>
      <tr>
        <th><?php echo __('Record') ?></th>
        <th><?php echo __('Proposed') ?></th>
        <th><?php echo __('Outcome') ?></th>
        <th><?php echo __('Decided') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($decided as $d): ?>
      <tr class="<?php echo 'failed' === $d->status ? 'table-danger' : '' ?>">
        <td><?php echo esc_specialchars($titles[$d->record_id] ?? ('#'.$d->record_id)) ?></td>
        <td class="small"><?php echo esc_specialchars((string) $d->summary) ?></td>
        <td>
          <?php
          $badge = ['accepted' => 'bg-success', 'rejected' => 'bg-secondary', 'failed' => 'bg-danger'];
          ?>
          <span class="badge <?php echo $badge[$d->status] ?? 'bg-light text-dark' ?>">
            <?php echo esc_specialchars(ucfirst((string) $d->status)) ?>
          </span>
          <?php if ($d->result_note): ?>
            <div class="small text-muted"><?php echo esc_specialchars((string) $d->result_note) ?></div>
          <?php endif ?>
          <?php if ($d->decision_note): ?>
            <div class="small fst-italic"><?php echo esc_specialchars((string) $d->decision_note) ?></div>
          <?php endif ?>
        </td>
        <td class="text-muted small"><?php echo esc_specialchars(substr((string) $d->decided_at, 0, 16)) ?></td>
      </tr>
      <?php endforeach ?>
    </tbody>
  </table>
<?php endif ?>
