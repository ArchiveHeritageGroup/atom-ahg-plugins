<?php decorate_with('layout_1col') ?>
<?php slot('title') ?>
  <h1><?php echo __('Artwork request approvers') ?></h1>
<?php end_slot() ?>

<p class="text-muted">
  <?php echo __('Approvers receive an email when a request is submitted, and see it in the review queue.') ?>
  <?php echo __('An approver with no department covers every request; one with a department covers only that department, alongside the general approvers.') ?>
</p>

<?php foreach ($errors as $e): ?>
  <div class="alert alert-danger"><?php echo esc_specialchars($e) ?></div>
<?php endforeach ?>

<?php if (!$approvers): ?>
  <div class="alert alert-warning">
    <?php echo __('No approvers are set. Requests will still be submitted and recorded, but nobody is notified by email.') ?>
  </div>
<?php else: ?>
  <table class="table table-sm align-middle">
    <thead><tr>
      <th><?php echo __('User') ?></th>
      <th><?php echo __('Department') ?></th>
      <th><?php echo __('Email') ?></th>
      <th><?php echo __('Notifications') ?></th>
      <th><?php echo __('Status') ?></th>
      <th></th>
    </tr></thead>
    <tbody>
    <?php foreach ($approvers as $a): ?>
      <tr class="<?php echo $a->active ? '' : 'text-muted' ?>">
        <td><?php echo esc_specialchars($a->username) ?></td>
        <td>
          <?php echo $a->department
            ? esc_specialchars($a->department)
            : '<span class="text-muted">'.__('All departments').'</span>' ?>
        </td>
        <td>
          <?php echo $a->email
            ? esc_specialchars($a->email)
            : '<span class="text-danger">'.__('no address - cannot be notified').'</span>' ?>
        </td>
        <td>
          <form method="post" class="d-inline">
            <input type="hidden" name="form_action" value="notifications">
            <input type="hidden" name="approver_id" value="<?php echo (int) $a->id ?>">
            <input type="hidden" name="on" value="<?php echo $a->email_notifications ? 0 : 1 ?>">
            <button class="btn btn-sm <?php echo $a->email_notifications ? 'btn-outline-success' : 'btn-outline-secondary' ?>" type="submit">
              <?php echo $a->email_notifications ? __('On') : __('Off') ?>
            </button>
          </form>
        </td>
        <td>
          <?php echo $a->active
            ? '<span class="badge bg-success">'.__('Active').'</span>'
            : '<span class="badge bg-secondary">'.__('Disabled').'</span>' ?>
        </td>
        <td class="text-end">
          <form method="post" class="d-inline">
            <input type="hidden" name="form_action" value="<?php echo $a->active ? 'deactivate' : 'activate' ?>">
            <input type="hidden" name="approver_id" value="<?php echo (int) $a->id ?>">
            <button class="btn btn-sm btn-outline-secondary" type="submit">
              <?php echo $a->active ? __('Disable') : __('Enable') ?>
            </button>
          </form>
          <form method="post" class="d-inline"
                data-ahg-confirm="<?php echo __('Remove this approver?') ?>">
            <input type="hidden" name="form_action" value="remove">
            <input type="hidden" name="approver_id" value="<?php echo (int) $a->id ?>">
            <button class="btn btn-sm btn-outline-danger" type="submit"><?php echo __('Remove') ?></button>
          </form>
        </td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table>
<?php endif ?>

<h2 class="h5 mt-4"><?php echo __('Add an approver') ?></h2>

<form method="post" class="row g-2 align-items-end">
  <input type="hidden" name="form_action" value="add">

  <div class="col-md-4">
    <label class="form-label" for="user_ref"><?php echo __('User') ?></label>
    <input class="form-control" id="user_ref" name="user_ref" list="ahg-ar-users"
           placeholder="<?php echo __('username or email') ?>" required>
    <datalist id="ahg-ar-users">
      <?php foreach ($candidates as $u): ?>
        <option value="<?php echo esc_specialchars($u->username) ?>">
          <?php echo esc_specialchars($u->email ?: '') ?>
        </option>
      <?php endforeach ?>
    </datalist>
  </div>

  <div class="col-md-4">
    <label class="form-label" for="department"><?php echo __('Department') ?></label>
    <input class="form-control" id="department" name="department" list="ahg-ar-departments"
           placeholder="<?php echo __('leave blank for all departments') ?>">
    <datalist id="ahg-ar-departments">
      <?php foreach ($departments as $d): ?>
        <option value="<?php echo esc_specialchars($d) ?>"></option>
      <?php endforeach ?>
    </datalist>
  </div>

  <div class="col-md-2 form-check ms-2">
    <input class="form-check-input" type="checkbox" id="email_notifications"
           name="email_notifications" value="1" checked>
    <label class="form-check-label" for="email_notifications"><?php echo __('Email them') ?></label>
  </div>

  <div class="col-md-1">
    <button class="btn btn-primary" type="submit"><?php echo __('Add') ?></button>
  </div>
</form>

<p class="mt-4">
  <a href="<?php echo url_for(['module' => 'artworkRequest', 'action' => 'review']) ?>">
    <?php echo __('Back to the review queue') ?>
  </a>
</p>
