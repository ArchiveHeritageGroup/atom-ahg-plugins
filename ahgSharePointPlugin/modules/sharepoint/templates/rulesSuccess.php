<?php
$rules = $sf_data->getRaw('rules');
$drives = $sf_data->getRaw('drives');
?>
<h1><?php echo __('SharePoint Auto-Ingest Rules') ?></h1>

<?php if ($sf_user->hasFlash('notice')): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?php echo $sf_user->getFlash('notice') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif ?>
<?php if ($sf_user->hasFlash('error')): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <?php echo $sf_user->getFlash('error') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif ?>

<p>
    <a href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'ruleEdit']) ?>" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i><?php echo __('New rule') ?>
    </a>
    <a href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'mappings']) ?>" class="btn btn-outline-secondary">
        <?php echo __('Mapping templates') ?>
    </a>
</p>

<?php if (empty($drives) || count($drives) === 0): ?>
    <div class="alert alert-info">
        <?php echo __('Register a SharePoint drive before creating rules.') ?>
        <a href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'drives']) ?>"><?php echo __('Drives') ?></a>.
    </div>
<?php endif ?>

<table class="table table-striped">
    <thead>
        <tr>
            <th><?php echo __('Name') ?></th>
            <th><?php echo __('Drive') ?></th>
            <th><?php echo __('Folder / Pattern') ?></th>
            <th><?php echo __('Schedule') ?></th>
            <th><?php echo __('Enabled') ?></th>
            <th><?php echo __('Last run') ?></th>
            <th><?php echo __('Ingested') ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rules as $r): ?>
        <tr>
            <td><strong><?php echo esc_entities($r->name) ?></strong></td>
            <td>
                <small><?php echo esc_entities($r->site_title ?: '?') ?></small><br>
                <code><?php echo esc_entities($r->drive_name ?: '?') ?></code>
            </td>
            <td>
                <code><?php echo esc_entities($r->folder_path ?: '/') ?></code><br>
                <small class="text-muted"><?php echo esc_entities($r->file_pattern ?: '*') ?></small>
            </td>
            <td><code><?php echo esc_entities($r->schedule_cron) ?></code></td>
            <td>
                <?php if ($r->is_enabled): ?>
                    <span class="badge bg-success"><?php echo __('Enabled') ?></span>
                <?php else: ?>
                    <span class="badge bg-secondary"><?php echo __('Disabled') ?></span>
                <?php endif ?>
            </td>
            <td>
                <?php if ($r->last_run_at): ?>
                    <small><?php echo esc_entities($r->last_run_at) ?></small><br>
                    <span class="badge bg-<?php echo ($r->last_run_status === 'ok' || $r->last_run_status === 'dry_run') ? 'success' : (($r->last_run_status === 'error') ? 'danger' : 'secondary') ?>">
                        <?php echo esc_entities($r->last_run_status) ?>
                    </span>
                <?php else: ?>
                    <span class="text-muted"><?php echo __('never') ?></span>
                <?php endif ?>
            </td>
            <td><?php echo (int) $r->items_ingested ?></td>
            <td class="text-end">
                <a href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'ruleEdit', 'id' => $r->id]) ?>" class="btn btn-sm btn-outline-secondary"><?php echo __('Edit') ?></a>
                <a href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'ruleRun', 'id' => $r->id]) ?>" class="btn btn-sm btn-outline-primary"><?php echo __('Run now') ?></a>
                <a href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'ruleDelete', 'id' => $r->id]) ?>" class="btn btn-sm btn-outline-danger" data-bs-confirm="<?php echo __('Delete this rule?') ?>" id="delete-rule-<?php echo (int) $r->id ?>"><?php echo __('Delete') ?></a>
            </td>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.querySelectorAll('a[data-bs-confirm]').forEach(function (a) {
    a.addEventListener('click', function (e) { if (!confirm(a.dataset.bsConfirm)) e.preventDefault(); });
});
</script>
