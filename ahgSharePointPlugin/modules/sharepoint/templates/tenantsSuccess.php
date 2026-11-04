<?php
$tenants = $sf_data->getRaw('tenants');
?>
<h1><?php echo __('SharePoint Tenants') ?></h1>

<p>
    <a href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'drives']) ?>" class="btn btn-outline-secondary"><?php echo __('Drives') ?></a>
    <a href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'rules']) ?>" class="btn btn-outline-secondary"><?php echo __('Rules') ?></a>
    <a href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'mappings']) ?>" class="btn btn-outline-secondary"><?php echo __('Mapping templates') ?></a>
</p>

<?php if (empty($tenants)): ?>
    <div class="alert alert-warning"><?php echo __('No SharePoint tenants configured yet.') ?></div>
<?php else: ?>
<table class="table table-striped">
    <thead>
        <tr>
            <th><?php echo __('Name') ?></th>
            <th><?php echo __('Tenant ID') ?></th>
            <th><?php echo __('Client ID') ?></th>
            <th><?php echo __('Status') ?></th>
            <th><?php echo __('Last token') ?></th>
            <th><?php echo __('Last error') ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($tenants as $t): ?>
        <tr>
            <td><strong><?php echo esc_entities($t->name) ?></strong></td>
            <td><code><?php echo esc_entities($t->tenant_id) ?></code></td>
            <td><code><?php echo esc_entities($t->client_id) ?></code></td>
            <td>
                <?php if ('active' === $t->status): ?>
                    <span class="badge bg-success"><?php echo esc_entities($t->status) ?></span>
                <?php else: ?>
                    <span class="badge bg-secondary"><?php echo esc_entities($t->status ?: '?') ?></span>
                <?php endif ?>
            </td>
            <td><?php echo esc_entities($t->last_token_at ?: '-') ?></td>
            <td><small class="text-danger"><?php echo esc_entities(mb_strimwidth((string) $t->last_error, 0, 120, '...')) ?></small></td>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>
<?php endif ?>

<?php // ponytail: list only. Adding and editing a tenant (with its write-only client secret via SharePointTenantRepository::persistSecret()) is tenantEdit, still a stub. ?>
