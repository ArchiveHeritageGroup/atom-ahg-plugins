<?php decorate_with('layout_1col') ?>
<?php slot('title') ?>
  <h1><?php echo __('Request an artwork') ?></h1>
<?php end_slot() ?>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?><li><?php echo esc_specialchars($e) ?></li><?php endforeach ?>
    </ul>
  </div>
<?php endif ?>

<form method="post" id="artwork-request-form">
  <input type="hidden" name="_ahg_csrf_token" value="<?php echo sfConfig::get('csrf_token', '') ?>">

  <div class="card mb-3">
    <div class="card-header"><?php echo __('Works') ?></div>
    <div class="card-body">
      <p class="text-muted small">
        <?php echo __('Add works by identifier or record id. Availability is checked as you add them - a clash is a warning, not a refusal.') ?>
      </p>
      <div class="input-group mb-2">
        <input type="text" class="form-control" id="work-input" placeholder="<?php echo __('Record id') ?>">
        <button class="btn btn-outline-primary" type="button" id="add-work"><?php echo __('Add') ?></button>
      </div>
      <table class="table table-sm" id="work-table">
        <thead><tr>
          <th><?php echo __('Work') ?></th><th><?php echo __('Availability') ?></th><th></th>
        </tr></thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header"><?php echo __('When') ?></div>
    <div class="card-body row g-3">
      <div class="col-md-6">
        <label class="form-label" for="requested_from"><?php echo __('From') ?></label>
        <input type="date" class="form-control" id="requested_from" name="requested_from" required>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="requested_to"><?php echo __('To') ?></label>
        <input type="date" class="form-control" id="requested_to" name="requested_to" required>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header"><?php echo __('Where it will hang') ?></div>
    <div class="card-body row g-3">
      <div class="col-md-4">
        <label class="form-label" for="placement_building"><?php echo __('Building') ?></label>
        <input type="text" class="form-control" id="placement_building" name="placement_building">
      </div>
      <div class="col-md-2">
        <label class="form-label" for="placement_floor"><?php echo __('Floor') ?></label>
        <input type="text" class="form-control" id="placement_floor" name="placement_floor">
      </div>
      <div class="col-md-3">
        <label class="form-label" for="placement_room"><?php echo __('Room') ?></label>
        <input type="text" class="form-control" id="placement_room" name="placement_room">
      </div>
      <div class="col-md-3">
        <label class="form-label" for="placement_occupant"><?php echo __('Occupant') ?></label>
        <input type="text" class="form-control" id="placement_occupant" name="placement_occupant">
      </div>
      <div class="col-12">
        <label class="form-label" for="placement_notes"><?php echo __('Notes on the space') ?></label>
        <textarea class="form-control" id="placement_notes" name="placement_notes" rows="2"></textarea>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header"><?php echo __('Why') ?></div>
    <div class="card-body row g-3">
      <div class="col-md-4">
        <label class="form-label" for="department"><?php echo __('Department') ?></label>
        <input type="text" class="form-control" id="department" name="department">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="purpose"><?php echo __('Purpose') ?></label>
        <select class="form-select" id="purpose" name="purpose">
          <option value="office"><?php echo __('Office') ?></option>
          <option value="boardroom"><?php echo __('Boardroom') ?></option>
          <option value="shared workspace"><?php echo __('Shared workspace') ?></option>
          <option value="event"><?php echo __('Event') ?></option>
          <option value="other"><?php echo __('Other') ?></option>
        </select>
      </div>
      <div class="col-12">
        <label class="form-label" for="justification"><?php echo __('Justification') ?></label>
        <textarea class="form-control" id="justification" name="justification" rows="3"></textarea>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-primary"><?php echo __('Submit request') ?></button>
</form>

<?php
// Inline script carries the CSP nonce. An enforcing policy drops an unnonced
// <script> silently, which is the worst way for this to fail.
$n = sfConfig::get('csp_nonce', '');
?>
<script <?php echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : '' ?>>
(function () {
  var body = document.querySelector('#work-table tbody');
  var chosen = [];

  function availability(id, cell) {
    var from = document.getElementById('requested_from').value;
    var to   = document.getElementById('requested_to').value;
    if (!from || !to) { cell.textContent = '<?php echo __('set dates to check') ?>'; return; }
    fetch('<?php echo url_for(['module' => 'artworkRequest', 'action' => 'availability']) ?>?object_id=' +
          encodeURIComponent(id) + '&from=' + from + '&to=' + to)
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d.free) { cell.innerHTML = '<span class="text-success"><?php echo __('free') ?></span>'; return; }
        cell.innerHTML = '<span class="text-warning">' +
          d.conflicts.map(function (c) { return c.detail; }).join('; ') + '</span>';
      })
      .catch(function () { cell.textContent = '-'; });
  }

  function addWork(id) {
    if (!id || chosen.indexOf(id) !== -1) return;
    chosen.push(id);
    var tr = document.createElement('tr');
    tr.innerHTML = '<td>#' + id + '<input type="hidden" name="object_ids[]" value="' + id + '"></td>' +
                   '<td class="availability">...</td>' +
                   '<td><button type="button" class="btn btn-sm btn-outline-danger remove">&times;</button></td>';
    body.appendChild(tr);
    availability(id, tr.querySelector('.availability'));
    tr.querySelector('.remove').addEventListener('click', function () {
      chosen.splice(chosen.indexOf(id), 1);
      tr.remove();
    });
  }

  document.getElementById('add-work').addEventListener('click', function () {
    var f = document.getElementById('work-input');
    addWork(f.value.trim());
    f.value = '';
  });

  // Re-check everything when the dates move, since availability depends on them.
  ['requested_from', 'requested_to'].forEach(function (id) {
    document.getElementById(id).addEventListener('change', function () {
      body.querySelectorAll('tr').forEach(function (tr) {
        var input = tr.querySelector('input[name="object_ids[]"]');
        if (input) availability(input.value, tr.querySelector('.availability'));
      });
    });
  });
})();
</script>
