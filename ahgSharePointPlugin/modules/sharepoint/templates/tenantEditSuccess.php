<?php
$tenant = $sf_data->getRaw('tenant');
$values = $sf_data->getRaw('values');
$errors = $sf_data->getRaw('errors');
$field = function (string $name, string $label, string $help = '') use ($values, $errors) {
    $invalid = isset($errors[$name]) ? ' is-invalid' : '';
    echo '<div class="mb-3">';
    echo '<label class="form-label" for="sp-'.$name.'">'.__($label).'</label>';
    echo '<input type="text" class="form-control'.$invalid.'" id="sp-'.$name.'" name="'.$name.'" value="'.esc_entities($values[$name]).'"'.($help ? ' aria-describedby="sp-'.$name.'-help"' : '').'>';
    if ($invalid) {
        echo '<div class="invalid-feedback">'.esc_entities(__($errors[$name])).'</div>';
    }
    if ($help) {
        echo '<div class="form-text" id="sp-'.$name.'-help">'.__($help).'</div>';
    }
    echo '</div>';
};
?>
<h1><?php echo $tenant ? __('Edit SharePoint tenant') : __('Add SharePoint tenant') ?></h1>

<form method="post" action="<?php echo url_for(['module' => 'sharepoint', 'action' => 'tenantEdit', 'id' => $tenant ? (int) $tenant->id : 0]) ?>">
    <div class="card mb-3">
        <div class="card-body">
            <?php $field('name', 'Name', 'A label for this tenant, for example the institution.') ?>
            <?php $field('tenant_id', 'Tenant ID', 'Directory (tenant) ID from the Azure app registration.') ?>
            <?php $field('client_id', 'Client ID', 'Application (client) ID from the Azure app registration.') ?>

            <div class="mb-3">
                <label class="form-label" for="sp-client_secret"><?php echo __('Client secret') ?></label>
                <input type="password" class="form-control<?php echo isset($errors['client_secret']) ? ' is-invalid' : '' ?>" id="sp-client_secret" name="client_secret" autocomplete="new-password" aria-describedby="sp-client_secret-help">
                <?php if (isset($errors['client_secret'])): ?>
                    <div class="invalid-feedback"><?php echo esc_entities(__($errors['client_secret'])) ?></div>
                <?php endif ?>
                <div class="form-text" id="sp-client_secret-help">
                    <?php echo $tenant && '' !== (string) $tenant->client_secret_ref
                        ? __('A secret is stored. Leave this blank to keep it, or enter a new one to replace it.')
                        : __('The secret value from the app registration. It is stored encrypted and never shown again.') ?>
                </div>
            </div>

            <?php $field('graph_endpoint', 'Graph endpoint') ?>
            <?php $field('default_site_id', 'Default site ID', 'Optional. Graph site identifier used when none is given.') ?>

            <div class="mb-3">
                <label class="form-label" for="sp-status"><?php echo __('Status') ?></label>
                <select class="form-select" id="sp-status" name="status">
                    <?php foreach (['active' => __('Active'), 'disabled' => __('Disabled')] as $value => $label): ?>
                        <option value="<?php echo $value ?>" <?php echo $values['status'] === $value ? 'selected' : '' ?>><?php echo $label ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary"><?php echo __('Save') ?></button>
    <a href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'tenants']) ?>" class="btn btn-outline-secondary"><?php echo __('Cancel') ?></a>
    <?php if ($tenant): ?>
        <button type="button" class="btn btn-outline-secondary" id="sp-test" data-url="<?php echo url_for(['module' => 'sharepoint', 'action' => 'tenantTest', 'id' => (int) $tenant->id]) ?>"><?php echo __('Test connection') ?></button>
        <div id="sp-test-result" class="mt-3" role="status" aria-live="polite"></div>
    <?php endif ?>
</form>

<?php if ($tenant): ?>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.getElementById('sp-test').addEventListener('click', function () {
  var out = document.getElementById('sp-test-result');
  out.className = 'mt-3 text-muted';
  out.textContent = <?php echo json_encode(__('Testing...')) ?>;
  fetch(this.dataset.url, { credentials: 'same-origin' })
    .then(function (r) { return r.json(); })
    .then(function (d) {
      out.className = 'mt-3 alert ' + ('ok' === d.status ? 'alert-success' : 'alert-danger');
      out.textContent = 'ok' === d.status
        ? <?php echo json_encode(__('Connected. Sites found: ')) ?> + (d.sites.length ? d.sites.join(', ') : '0')
        : <?php echo json_encode(__('Connection failed: ')) ?> + d.message;
    })
    .catch(function () {
      out.className = 'mt-3 alert alert-danger';
      out.textContent = <?php echo json_encode(__('The test could not be run.')) ?>;
    });
});
</script>
<?php endif ?>
