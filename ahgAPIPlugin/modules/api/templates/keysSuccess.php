<?php decorate_with('layout_1col'); ?>
<?php
$keys = $sf_data->getRaw('keys');
$scopes = $sf_data->getRaw('scopes');
$newKey = $sf_data->getRaw('newKey');
$error = $sf_data->getRaw('error');
$issued = $sf_data->getRaw('issued');
$canIssue = $sf_data->getRaw('canIssue');
$limits = function ($k) {
    $parts = [];
    if ((int) $k->rate_limit > 0) {
        $parts[] = __('%1% an hour', ['%1%' => (int) $k->rate_limit]);
    }
    if (!empty($k->daily_limit)) {
        $parts[] = __('%1% a day', ['%1%' => (int) $k->daily_limit]);
    }

    return $parts ? implode(', ', $parts) : __('None');
};
$expiry = fn ($k) => $k->expires_at ? esc_entities(substr((string) $k->expires_at, 0, 10)).($k->expires_at < date('Y-m-d H:i:s') ? ' <span class="badge bg-secondary">'.__('expired').'</span>' : '') : __('Never');
?>

<?php slot('title'); ?>
  <h1><?php echo __('API keys'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <p><?php echo __('Keys for the REST API (v2) and GraphQL. Send a key in the X-API-Key header. A key can do no more than you can: it sees what you may see, and only within its scopes.'); ?>
    <a href="/api/v2/docs"><?php echo __('API documentation'); ?></a></p>

  <?php if ($newKey) { ?>
    <div class="alert alert-success" id="new-key" role="status">
      <p class="mb-2"><strong><?php echo $newKey['for'] ? __('New key "%1%" for %2%:', ['%1%' => esc_entities($newKey['name']), '%2%' => esc_entities($newKey['for'])]) : __('Your new key "%1%":', ['%1%' => esc_entities($newKey['name'])]); ?></strong></p>
      <p class="mb-2"><code class="fs-5" id="new-key-value"><?php echo esc_entities($newKey['api_key']); ?></code></p>
      <p class="mb-0"><?php echo __('Copy it now and keep it somewhere safe. It is shown only once; if you lose it, revoke it and create another.'); ?></p>
    </div>
  <?php } ?>
  <?php if ($error) { ?>
    <div class="alert alert-danger" role="alert"><?php echo __($error); ?></div>
  <?php } ?>

  <section class="card mb-4">
    <h2 class="card-header h5"><?php echo __('Create a key'); ?></h2>
    <div class="card-body">
      <form method="post" action="<?php echo url_for(['module' => 'api', 'action' => 'keys']); ?>" id="key-create">
        <div class="mb-3">
          <label for="key-name" class="form-label"><?php echo __('Name'); ?></label>
          <input type="text" class="form-control" id="key-name" name="name" maxlength="100" required>
          <div class="form-text"><?php echo __('What the key is for, for example "Website catalogue feed".'); ?></div>
        </div>
        <fieldset class="mb-3">
          <legend class="form-label fs-6"><?php echo __('Scopes'); ?></legend>
          <?php foreach ($scopes as $value => $label) { ?>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="scopes[]" value="<?php echo $value; ?>" id="scope-<?php echo $value; ?>" <?php echo 'read' === $value ? 'checked' : ''; ?>>
              <label class="form-check-label" for="scope-<?php echo $value; ?>"><?php echo __($label); ?> <code><?php echo $value; ?></code></label>
            </div>
          <?php } ?>
          <div class="form-text"><?php echo __('Give a key only the scopes it needs. A read-only key cannot change anything.'); ?></div>
        </fieldset>
        <div class="row g-3 mb-3">
          <div class="col-sm-6">
            <label for="key-expires" class="form-label"><?php echo __('Expires (optional)'); ?></label>
            <input type="date" class="form-control" id="key-expires" name="expires_at" min="<?php echo date('Y-m-d', strtotime('tomorrow')); ?>">
            <div class="form-text"><?php echo __('After this day the key stops working.'); ?></div>
          </div>
          <div class="col-sm-6">
            <label for="key-daily" class="form-label"><?php echo __('Requests a day (optional)'); ?></label>
            <input type="number" class="form-control" id="key-daily" name="daily_limit" min="1" step="1">
            <div class="form-text"><?php echo __('Leave empty for no daily cap. Every key also has an hourly limit.'); ?></div>
          </div>
        </div>
        <?php if ($canIssue) { ?>
          <div class="mb-3">
            <label for="key-for" class="form-label"><?php echo __('Issue to another user (administrators, optional)'); ?></label>
            <input type="text" class="form-control" id="key-for" name="issue_for" maxlength="255" placeholder="<?php echo __('username or email'); ?>">
            <div class="form-text"><?php echo __('The key acts as that user, with that user\'s groups. For a read-only key for testing, issue it to a user with no groups and give it only the read scope.'); ?></div>
          </div>
        <?php } ?>
        <button type="submit" class="btn atom-btn-outline-success"><?php echo __('Create key'); ?></button>
      </form>
    </div>
  </section>

  <section class="card">
    <h2 class="card-header h5"><?php echo __('Your keys'); ?></h2>
    <?php if (!$keys) { ?>
      <div class="card-body"><?php echo __('You have no API keys yet.'); ?></div>
    <?php } else { ?>
      <table class="table table-bordered table-sm mb-0" id="key-list">
        <thead><tr><th><?php echo __('Name'); ?></th><th><?php echo __('Starts with'); ?></th><th><?php echo __('Scopes'); ?></th><th><?php echo __('Created'); ?></th><th><?php echo __('Last used'); ?></th><th><?php echo __('Expires'); ?></th><th><?php echo __('Limits'); ?></th><th><span class="visually-hidden"><?php echo __('Actions'); ?></span></th></tr></thead>
        <tbody>
          <?php foreach ($keys as $k) { ?>
            <tr>
              <td><?php echo esc_entities($k->name); ?></td>
              <td><code><?php echo esc_entities($k->api_key_prefix); ?>...</code></td>
              <td><?php echo esc_entities(implode(', ', (array) json_decode($k->scopes ?? '[]', true))); ?></td>
              <td><?php echo esc_entities(substr((string) $k->created_at, 0, 16)); ?></td>
              <td><?php echo $k->last_used_at ? esc_entities(substr((string) $k->last_used_at, 0, 16)) : __('Never'); ?></td>
              <td><?php echo $expiry($k); ?></td>
              <td><?php echo $limits($k); ?></td>
              <td>
                <form method="post" action="<?php echo url_for(['module' => 'api', 'action' => 'keys']); ?>" class="d-inline">
                  <input type="hidden" name="form_action" value="revoke">
                  <input type="hidden" name="key_id" value="<?php echo (int) $k->id; ?>">
                  <button type="submit" class="btn btn-sm atom-btn-outline-danger" aria-label="<?php echo __('Revoke %1%', ['%1%' => esc_entities($k->name)]); ?>"><?php echo __('Revoke'); ?></button>
                </form>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    <?php } ?>
  </section>

  <?php if ($canIssue && $issued) { ?>
    <section class="card mt-4">
      <h2 class="card-header h5"><?php echo __('Keys you issued to others'); ?></h2>
      <table class="table table-bordered table-sm mb-0" id="issued-key-list">
        <thead><tr><th><?php echo __('User'); ?></th><th><?php echo __('Name'); ?></th><th><?php echo __('Starts with'); ?></th><th><?php echo __('Scopes'); ?></th><th><?php echo __('Last used'); ?></th><th><?php echo __('Expires'); ?></th><th><?php echo __('Limits'); ?></th><th><span class="visually-hidden"><?php echo __('Actions'); ?></span></th></tr></thead>
        <tbody>
          <?php foreach ($issued as $k) { ?>
            <tr>
              <td><?php echo esc_entities((string) $k->username); ?></td>
              <td><?php echo esc_entities($k->name); ?></td>
              <td><code><?php echo esc_entities($k->api_key_prefix); ?>...</code></td>
              <td><?php echo esc_entities(implode(', ', (array) json_decode($k->scopes ?? '[]', true))); ?></td>
              <td><?php echo $k->last_used_at ? esc_entities(substr((string) $k->last_used_at, 0, 16)) : __('Never'); ?></td>
              <td><?php echo $expiry($k); ?></td>
              <td><?php echo $limits($k); ?></td>
              <td>
                <form method="post" action="<?php echo url_for(['module' => 'api', 'action' => 'keys']); ?>" class="d-inline">
                  <input type="hidden" name="form_action" value="revoke">
                  <input type="hidden" name="key_id" value="<?php echo (int) $k->id; ?>">
                  <button type="submit" class="btn btn-sm atom-btn-outline-danger" aria-label="<?php echo __('Revoke %1%', ['%1%' => esc_entities($k->name)]); ?>"><?php echo __('Revoke'); ?></button>
                </form>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </section>
  <?php } ?>
<?php end_slot(); ?>
