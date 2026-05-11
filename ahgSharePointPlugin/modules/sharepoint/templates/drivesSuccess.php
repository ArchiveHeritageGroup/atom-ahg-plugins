<?php
$drives = $sf_data->getRaw('drives');
$tenants = $sf_data->getRaw('tenants');
?>
<h1><?php echo __('SharePoint Drives') ?></h1>

<?php if ($sf_user->hasFlash('notice')): ?>
<div class="alert alert-success alert-dismissible fade show"><?php echo $sf_user->getFlash('notice') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif ?>
<?php if ($sf_user->hasFlash('error')): ?>
<div class="alert alert-danger alert-dismissible fade show"><?php echo $sf_user->getFlash('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif ?>

<p>
    <a href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'driveRegister']) ?>" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i><?php echo __('Register drive') ?>
    </a>
    <a href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'tenants']) ?>" class="btn btn-outline-secondary"><?php echo __('Tenants') ?></a>
    <a href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'rules']) ?>" class="btn btn-outline-secondary"><?php echo __('Rules') ?></a>
    <a href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'mappings']) ?>" class="btn btn-outline-secondary"><?php echo __('Mapping templates') ?></a>
</p>

<?php if (empty($tenants) || count($tenants) === 0): ?>
    <div class="alert alert-warning"><?php echo __('No SharePoint tenants configured yet. Configure a tenant first at /sharepoint/tenants.') ?></div>
<?php endif ?>

<table class="table table-striped">
    <thead>
        <tr>
            <th><?php echo __('Tenant') ?></th>
            <th><?php echo __('Site') ?></th>
            <th><?php echo __('Drive') ?></th>
            <th><?php echo __('Sector') ?></th>
            <th><?php echo __('Default placement') ?></th>
            <th><?php echo __('Ingest enabled') ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($drives as $d): ?>
        <tr>
            <td><?php echo esc_entities($d->tenant_name ?? '?') ?></td>
            <td>
                <strong><?php echo esc_entities($d->site_title ?: '?') ?></strong><br>
                <small><a href="<?php echo esc_entities($d->site_url) ?>" target="_blank"><?php echo esc_entities($d->site_url) ?></a></small>
            </td>
            <td>
                <strong><?php echo esc_entities($d->drive_name ?: '?') ?></strong><br>
                <small class="text-muted"><code><?php echo esc_entities(substr($d->drive_id, 0, 40)) ?>…</code></small>
            </td>
            <td><?php echo esc_entities($d->sector) ?></td>
            <td><?php echo esc_entities(str_replace('_', ' ', $d->default_parent_placement)) ?></td>
            <td>
                <?php if ($d->ingest_enabled): ?>
                    <span class="badge bg-success"><?php echo __('Yes') ?></span>
                <?php else: ?>
                    <span class="badge bg-secondary"><?php echo __('No') ?></span>
                <?php endif ?>
            </td>
            <td class="text-end">
                <a href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'mappings', 'drive_id' => $d->id]) ?>" class="btn btn-sm btn-outline-secondary"><?php echo __('Mappings') ?></a>
                <a href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'driveDelete', 'id' => $d->id]) ?>" class="btn btn-sm btn-outline-danger" data-bs-confirm="<?php echo __('Delete this drive?') ?>"><?php echo __('Delete') ?></a>
            </td>
        </tr>
    <?php endforeach ?>
    <?php if (count($drives) === 0): ?>
        <tr><td colspan="7" class="text-center text-muted"><?php echo __('No drives registered yet.') ?></td></tr>
    <?php endif ?>
    </tbody>
</table>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.querySelectorAll('a[data-bs-confirm]').forEach(function (a) {
    a.addEventListener('click', function (e) { if (!confirm(a.dataset.bsConfirm)) e.preventDefault(); });
});
</script>
